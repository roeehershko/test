<?php

// Deposits the merchant's transactions to SHVA and generates the invoices

$depositMerchantTransactionsRequest = (object) array(
    // No details required (Merchant ID is taken from the access token response)
);

$depositMerchantTransactionsResponse = (object) array(
    'result' => (object) array(
        'type' => 'string',
        'value' => array('OKAY', 'FAIL'),
        'null' => false
    ),
    'error' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'data' => (object) array(
        'type' => 'object',
        'value' => (object) array(
            'reference_number' => (object) array(
                'type' => 'string',
                'null' => true
            ),
			'total_debit' => (object) array(
                'type' => 'string',
                'null' => true
            ),
			'total_credit' => (object) array(
                'type' => 'string',
                'null' => true
            ),
			'total_number' => (object) array(
                'type' => 'string',
                'null' => true
            ),
			'transactions' => (object) array(
                'type' => 'array',
                'null' => true
            ),
        ),
        'null' => true
    )
);

function depositMerchantTransactions($args) {

    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
    	if ($merchants[1] || strtolower($args->username) != strtolower($merchants[0]['username'])) {
	    	// This is a USER
	    	$error = $authenticate__error;
        } else if (strtolower($args->username) == strtolower($merchants[0]['username'])) {

            $merchant = $merchants[0];

            $sql_query = "SELECT merchant_id, password, processor, shva_transactions_merchant_number, shva_transactions_username, shva_transactions_password, shva_standing_orders_merchant_number, shva_standing_orders_username FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($merchant[merchant_id]) . "' AND `terminated` = '0' AND (processor = 'shva' OR processor = 'shva-emv')";
            $sql_query = mysql_query($sql_query);
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $standing_orders = $transactions = false;

                $sub_sql_query = "SELECT trans_id, type FROM trans WHERE merchant_id = '" . $sql['merchant_id'] . "' AND status = 'pending'";
                $sub_sql_query = mysql_query($sub_sql_query);
                while ($sub_sql = mysql_fetch_object($sub_sql_query)) {
                    if ($sub_sql->type == 'credit') {
                        if ($credit_tmp = mysql_fetch_object(mysql_query("SELECT use_so_credentials, trans_params.value AS shva_trans_record FROM trans_credit LEFT JOIN trans_params USING (trans_id) WHERE trans_id = '" . $sub_sql->trans_id . "' AND reference_number IS NULL AND trans_params.private = '1' AND trans_params.name = 'shva_trans_record'"))) {
                            $sub_sql->shva_trans_record = $credit_tmp->shva_trans_record;
                            $is_so = @mysql_result(mysql_query("SELECT 1 FROM standing_orders_transactions WHERE trans_id = '" . $sub_sql->trans_id . "'"), 0);

                            if ($credit_tmp->use_so_credentials || $is_so) {
                                $standing_orders[] = $sub_sql;
                            } else {
                                $transactions[] = $sub_sql;
                            }
                        }
                    } else {
                        generateInvoice($sub_sql->trans_id);

                        // Required in case the transaction type is not 'credit' and does not have an invoice.
                        mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $sub_sql->trans_id . "'");
                    }
                }

                //$debug[] = $transactions;

                if ($standing_orders) {
                    $depositTransactionsInformation = depositSHVA($sql['shva_standing_orders_merchant_number'], $sql['shva_standing_orders_username'], aes_decrypt($sql['shva_transactions_password']), $standing_orders);
                }
                if ($sql['processor'] == 'shva-emv') {
                    // 2017-11-09 - For SHVA EMV we execute the deposit EVEN if there are no transactions in the pipeline.
                    $depositTransactionsInformation = depositSHVAEMV($sql['shva_transactions_merchant_number'], $sql['shva_transactions_username'], aes_decrypt($sql['shva_transactions_password']), $transactions);
                } else if ($transactions) {
                    // 2017-11-09 - For SHVA 96 we execute the deposit ONLY if there are transactions in the pipeline.
                    $depositTransactionsInformation = depositSHVA($sql['shva_transactions_merchant_number'], $sql['shva_transactions_username'], aes_decrypt($sql['shva_transactions_password']), $transactions);
                }
            }
        }
    }

    if (!$standing_orders && !$transactions && $merchant['processor'] != 'shva-emv') {
        // 2017-11-09 - For SHVA EMV we execute the deposit EVEN if there are no transactions in the pipeline.
        $error = 210;
    }

    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : $depositTransactionsInformation,
        //'debug' => $debug
    );

}

function depositSHVA($merchant_number, $merchant_username, $merchant_password, $transactions) {
    $transactions_records = array();
    if (!empty($transactions)) {
        foreach ($transactions as $transaction) {
            $transactions_records[] = $transaction->shva_trans_record;
        }
    }

    $client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
    $return = $client->DepositTransactionsEx((object) array(
        'MerchantNumber' => $merchant_number,
        'UserName' => $merchant_username,
        'Password' => $merchant_password,
        'TransArr' => $transactions_records
    ));

    if ($return->DepositTransactionsExResult == 250) {
        $error = 'Invalid identification tokens.';
    } elseif ($return->DepositTransactionsExResult != 0) {
        $error = 'Unknown error [' . $return->DepositTransactionsExResult . '].';
    }

    //echo 'Merchant #' . $merchant_number . ' - Deposit (' . count($transactions) . '): ' . (!$error ? 'OKAY' : 'ERROR: ' . $error) . "\n";

    if ($error) {
		$depositTransactionsInformation['error'] = $error;
	} else {
        // Generate invoices.
        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                generateInvoice($transaction->trans_id);
            }
        }

        // Get the deposit reference code.
        // Set the reference to something other than NULL until the real reference code is retrieved.
        // Required so that deposited transactions would not get deposited again in the event of the reference not being retrieved.
        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                mysql_query("UPDATE trans_credit SET reference_number = '0' WHERE trans_id = '" . $transaction->trans_id . "'");
            }
        
            // Set the actual reference code, replacing the temporary mark.
            $depositTransactionsInformation = false;
    		$depositTransactionsInformation['reference_number'] = $return->ReferenceNumber;
    		$depositTransactionsInformation['total_debit'] = $return->TotalDebit;
    		$depositTransactionsInformation['total_credit'] = $return->TotalCredit;
    		$depositTransactionsInformation['total_number'] = $return->TotalNumber;
            
            foreach ($transactions as $transaction) {
                mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $transaction->trans_id . "'");
                mysql_query("UPDATE trans_credit SET reference_number = '" . mysql_real_escape_string($return->ReferenceNumber) . "' WHERE trans_id = '" . $transaction->trans_id . "'");
                mysql_query("DELETE FROM trans_params WHERE trans_id = '" . $transaction->trans_id . "' AND private = '1' AND name = 'shva_trans_record'");

                $depositTransactionsInformation['transactions'][] = $transaction->trans_id;
            }
        }
    }

    return $depositTransactionsInformation;

}

function depositSHVAEMV($merchant_number, $merchant_username, $merchant_password, $transactions) {
    $transactions_records = array();
    foreach ($transactions as $transaction) {
        $transactions_records[] = $transaction->shva_trans_record;
    }

    $client = new SoapClient('https://www.shva-online.co.il/EMVWeb/prod/EMVRequest.asmx?wsdl');

    // Get Terminal Data
    /*
    $get_terminal_data_response = $client->GetTerminalData((object) array(
        'MerchantNumber' => $merchant_number,
        'UserName' => $merchant_username,
        'Password' => $merchant_password,
    ));
    */

    $return = $client->TransEMV((object) array(
        'MerchantNumber' => $merchant_number,
        'UserName' => $merchant_username,
        'Password' => $merchant_password,
        'DATA' => implode(';', $transactions_records)
    ));

    if ($return->TransEMVResult == 250) {
        $error = 'Invalid identification tokens.';
    } elseif ($return->TransEMVResult != 0) {
        $error = 'Unknown error [' . $return->TransEMVResult . '].';
    }

    //echo 'Merchant #' . $merchant_number . ' - Deposit (' . count($transactions) . '): ' . (!$error ? 'OKAY' : 'ERROR: ' . $error) . "\n";

    if ($error) {
		$depositTransactionsInformation['error'] = $error;
	} else {
        // Generate invoices.
        foreach ($transactions as $transaction) {
            generateInvoice($transaction->trans_id);
        }

        // Get the deposit reference code.
        // Set the reference to something other than NULL until the real reference code is retrieved.
        // Required so that deposited transactions would not get deposited again in the event of the reference not being retrieved.
        foreach ($transactions as $transaction) {
            mysql_query("UPDATE trans_credit SET reference_number = '0' WHERE trans_id = '" . $transaction->trans_id . "'");
        }

        // Set the actual reference code, replacing the temporary mark.
        $depositTransactionsInformation = false;
		$depositTransactionsInformation['reference_number'] = $return->RefNumber;
		$depositTransactionsInformation['total_debit'] = $return->TotalDebitTransSum;
		$depositTransactionsInformation['total_credit'] = $return->TotalCreditTransSum;
		$depositTransactionsInformation['total_number'] = $return->TotalXML;

        foreach ($transactions as $transaction) {
            mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $transaction->trans_id . "'");
            mysql_query("UPDATE trans_credit SET reference_number = '" . mysql_real_escape_string($return->ReferenceNumber) . "' WHERE trans_id = '" . $transaction->trans_id . "'");
            mysql_query("DELETE FROM trans_params WHERE trans_id = '" . $transaction->trans_id . "' AND private = '1' AND name = 'shva_trans_record'");

            $depositTransactionsInformation['transactions'][] = $transaction->trans_id;
        }
    }

    return $depositTransactionsInformation;

}

?>
