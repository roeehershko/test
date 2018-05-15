<?php

$cancelUserTransactionRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
);

$cancelUserTransactionResponse = (object) array(
    'result' => (object) array(
        'type' => 'string',
        'value' => array('OKAY', 'FAIL'),
        'null' => false
    ),
    'error' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

function cancelUserTransaction($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!preg_match('/^\d+$/', $args->trans_id)) {
        $error = 30;
    } else if ($args->merchant_id = mysql_result(mysql_query("SELECT merchant_id FROM trans t WHERE t.trans_id = '" . mysql_real_escape_string($args->trans_id) . "'"), 0)) {

    	if ($args->merchant_id) {
			foreach ($merchants as $data) {
				if ($data['merchant_id'] == $args->merchant_id) {
					$merchant = $data;
				}
			}
		}
		if (!$merchant) {
			$merchant = $merchants[0];
		}

		// Getting the transaction details
		include_once 'functions/obtainUserTransactionDetails.inc.php';
        $response = obtainUserTransactionDetails((object) array(
            'username' => $args->username,
            'password' => $args->password,
            'trans_id' => $args->trans_id,
            'include' => array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params'),
        ));
        if ($response->result == 'OKAY') {
        	$transaction = $response->data;
        } else {
            $error = 30;
        }

        //echo '<pre>'; print_r($transaction); echo '</pre>';
		//exit;

        if (!$error) {

			$args->merchant_number = $transaction->credit_data->use_so_credentials ? $merchant['shva_standing_orders_merchant_number'] : $merchant['shva_transactions_merchant_number'];

        	if ($merchant['processor'] == 'shva-emv' || $merchant['processor'] == 'shva' || $transaction->type == 'cash' || $transaction->type == 'check') {

				$return = cancelUserTransaction_SHVA($args, $transaction);
				if ($return->status == 'FAIL') {
					$error = $return->error;
				}

				/*
	        	// For SHVA merchants or if the transaction type is Cash or Check - we simply mark it as cancelled.
	        	// But ONLY if NO invoice exists for this transaction.
				//$invoice_verification_sql_query = "SELECT * FROM trans t LEFT JOIN trans_invoices AS ti USING (trans_id) WHERE t.merchant_id = '$merchant[merchant_id]' AND t.trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND ti.number IS NULL AND (t.type != 'credit' OR t.status = 'pending')";
				$invoice_verification_sql_query = "SELECT * FROM trans t LEFT JOIN trans_invoices AS ti USING (trans_id) WHERE t.merchant_id = '$merchant[merchant_id]' AND t.trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND ti.number IS NOT NULL AND t.status = 'pending'";
				$invoices_exist = @mysql_result(mysql_query($invoice_verification_sql_query), 0);
				$debug = array(
					'invoice_verification_sql_query' => $invoice_verification_sql_query,
					'invoices_exist' => $invoices_exist,
				);
				if ($invoices_exist) {
	        		$error = 209;
				} else {
		        	mysql_query("UPDATE trans SET status = 'canceled' WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "'");
					mysql_query("DELETE FROM trans_params WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND private = '1' AND name = 'shva_trans_record'");
	        	}
				*/

	        } elseif ($merchant['processor'] == 'creditguard') {

                $return = cancelUserTransaction_CreditGuard($args, $transaction);

				if ($return->error) {
		        	$error = $return->error;
	        	} else {
		        	// We add a new transaction with status REFUND and issue an invoice if necessary
		        	$transaction->amount = abs($transaction->amount) * (-1);

		        	$request_hash = md5(serialize(array(
			            $transaction->type,
			            $transaction->amount,
			            $transaction->currency,
			            $transaction->credit_data->track2,
			            $transaction->credit_data->track2_sar_ksn,
			            $transaction->credit_data->cc_number,
			            $transaction->credit_data->cc_exp,
			            $transaction->credit_data->cc_cvv2,
			            $transaction->credit_data->credit_terms,
			            $transaction->credit_data->j5,
			            $transaction->credit_data->payments_number,
			            $transaction->credit_data->payments_first_amount,
			            $transaction->credit_data->payments_standing_amount,
			            $transaction->credit_data->use_so_credentials,
			            $transaction->check_data->check_number,
			            $transaction->check_data->bank_number,
			            $transaction->check_data->branch_number,
			            $transaction->check_data->account_number,
			        )));
		        	if (mysql_query("INSERT INTO trans (merchant_id, timestamp, status, type, amount, currency, request_hash) VALUES ('$merchant[merchant_id]', NOW(), 'pending', 'credit', '" . mysql_real_escape_string($transaction->amount) . "', '" . mysql_real_escape_string($transaction->currency) . "', '" . mysql_real_escape_string($request_hash) . "')")) {
	                    $trans_id = mysql_insert_id();
	                    $transaction->trans_id = $trans_id;

	                    if ($transaction->type == 'credit') {
	                        if (!mysql_query("INSERT INTO trans_credit (trans_id, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, acquirer, voucher_number, use_so_credentials, credit_guard_token, credit_guard_tran_id) VALUES ('$trans_id', " . SQLNULL(mysql_real_escape_string($transaction->credit_data->cc_holder_name)) . ", '" . mysql_real_escape_string($transaction->credit_data->cc_last_4) . "', '" . mysql_real_escape_string($transaction->credit_data->cc_exp) . "', " . SQLNULL(mysql_real_escape_string($transaction->credit_data->cc_type)) . ", '" . mysql_real_escape_string($transaction->credit_data->credit_terms) . "', '" . mysql_real_escape_string($transaction->credit_data->transaction_code) . "', '" . mysql_real_escape_string($transaction->credit_data->j5 ? '1' : '0') . "', " . SQLNULL(mysql_real_escape_string($transaction->credit_data->payments_number)) . ", " . SQLNULL(mysql_real_escape_string($transaction->credit_data->payments_first_amount)) . ", " . SQLNULL(mysql_real_escape_string($transaction->credit_data->payments_standing_amount)) . ", " . SQLNULL(mysql_real_escape_string($transaction->credit_data->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($transaction->credit_data->card_acquirer)) . ", " . SQLNULL(mysql_real_escape_string($transaction->credit_data->voucher_number)) . ", '" . mysql_real_escape_string($transaction->credit_data->use_so_credentials ? '1' : '0') . "', " . SQLNULL(mysql_real_escape_string($return->credit_guard_token)) . ", " . SQLNULL(mysql_real_escape_string($return->credit_guard_tran_id)) . ")")) {
	                            $error = err(5);
	                        }
	                    }

	                    if (!$error && $transaction->invoice_data) {
	                        if (mysql_query("INSERT INTO trans_invoices (trans_id, customer_name, customer_number, address_street, address_city, address_zip, phone, description, gyro_details) VALUES ('$trans_id', " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->customer_name)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->customer_number)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->address_street)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->address_city)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->address_zip)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->phone)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->description)) . ", " . SQLNULL(mysql_real_escape_string($transaction->invoice_data->gyro_details)) . ")")) {
	                            if ($transaction->invoice_data->recipients) {
	                                $transaction->invoice_data->recipients = array_unique($transaction->invoice_data->recipients);

	                                foreach ($transaction->invoice_data->recipients as $email) {
	                                    if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) {
	                                        if (!mysql_query("INSERT INTO trans_invoices_recipients (trans_id, email) VALUES ('$trans_id', '" . mysql_real_escape_string(strtolower($email)) . "')")) {
	                                            $error = err(5);
	                                        }
	                                    }
	                                }
	                            }
	                        } else {
	                            $error = err(5);
	                        }

	                        if (!$error) {
	                            generateInvoice($trans_id);
	                        }
	                    }

	                    if (!$error && !$transaction->credit_data->j5) {
	                        mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $trans_id . "'");
	                        // Marking the previous transaciton as canceled
	                        mysql_query("UPDATE trans SET status = 'canceled' WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "'");
	                    }

	                    if (!$error && $transaction->params) {
	                        $params = (array) $transaction->params;
	                        $params['refund_of_trans_id'] = $args->trans_id;
	                        foreach ($params as $name => $value) {
	                            if ($value) {
	                            	if (!mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '0', '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($value) . "')")) {
	                                    $error = err(5);
	                                }
	                            }
	                        }
	                        $transaction->params = $params;
	                    }
	                } else {
	                    $error = err(5);
	                }

	        	}

			} else {
	           $error = 30;
	        }
	    }
    }

    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
		'debug' => $debug,
        'data' => $error ? null : $transaction,
    );
}

function cancelUserTransaction_SHVA($args, $transaction) {
	if (($transaction->type != 'credit' || ($transaction->type == 'credit' && !$transaction->credit_data->reference_number)) && !$transaction->invoice_data->number) {
		mysql_query("UPDATE trans SET status = 'canceled' WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "'");
		mysql_query("DELETE FROM trans_params WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND private = '1' AND name = 'shva_trans_record'");
		return (object) array(
	        'status' => 'OKAY'
	    );
	} else if ($transaction->invoice_data->number) {
		// The transaction has already been deposited or has an invoice - so we cannot refund.
		return (object) array(
	       	'status' => 'FAIL',
		    'error' => '209'
	    );
	} else {
		return (object) array(
	       	'status' => 'FAIL',
		    'error' => '211'
	    );
	}
}

function cancelUserTransaction_CreditGuard($args, $transaction) {

    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'creditguard_username'"));
    $creditguard_username = aes_decrypt($object->value);
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'creditguard_password'"));
    $creditguard_password = aes_decrypt($object->value);

    if ($args->merchant_id == '2') {
        //$host = 'https://verifonetest.creditguard.co.il/xpo/services/Relay?wsdl';
        $host = 'https://verifonetest.creditguard.co.il/xpo/Relay';
        $user = 'ivpayt';
        $pass = 'Iv@2pt3p';
    } else {
        //$host = 'https://veripayapi.creditguard.co.il/xpo/services/Relay?wsdl';
        $host = 'https://veripayapi.creditguard.co.il/xpo/Relay';
        $user = $creditguard_username;
        $pass = $creditguard_password;
    }

    if (!$transaction->credit_data->credit_guard_tran_id) {
	    $error = err(208);
    } else {
	    try {
	    	// IMPORTANT NOTE: We use refundDeal and not cancelDeal because refund automatically first tries to perform a cancel (and only if the transaction had already been submitted to SVHA - it performs a refund).
	        // 2014-08-20 - We check if the merchant supports "refund" - and only if he does - we use refundDeal. Otherwise we use cancelDeal
	        if ($merchant['support_refund_trans']) {
		        $command_type = 'refundDeal';
	        } else {
		        $command_type = 'cancelDeal';
	        }

	        /*
	        $CreditGuardWSDL = new SoapClient($host);
	        $request = array(
	            'user' => $user,
	            'password' => $pass,
	            'int_in' => '<ashrait>
	                <request>
	                    <command>refundDeal</command>
	                    <requestId/>
	                    <version>1001</version>
	                    <language>' . ($_GET['lang'] == 'he' ? 'HEB' : 'ENG') . '</language>
	                    <refundDeal>
	                        <terminalNumber>' . substr($args->merchant_number, 0, 7) . '</terminalNumber>
	                        <tranId>' . $transaction->credit_data->credit_guard_tran_id . '</tranId>
	                        <authNumber>' . $transaction->credit_data->authorization_number . '</authNumber>
	                        <total>' . (abs($transaction->amount) * 100) . '</total>
	                    </refundDeal>
	                </request>
	            </ashrait>'
	        );
	        $response = $CreditGuardWSDL->ashraitTransaction($request);

	        //echo '<pre>Request: '; print_r($request); echo '</pre>';
	        //echo '<pre>Response: '; print_r($response); echo '</pre>';
	        //exit;

	        if (preg_match('/<result>(.+)<\/result>/U', $response->ashraitTransactionReturn, $match)) {
	            $ResultRecord = $match[1];
	        }

	        if ($ResultRecord) {

	            if (substr($ResultRecord, 0, 3) != '000') {
	            	preg_match('/<message>(.+)<\/message>/U', $response->ashraitTransactionReturn, $match);
	                $error = err(200, array(substr($ResultRecord, 0, 3), $match[1]));
	            } else {
	            	// Cancel successful

	            	/*
	            	preg_match('/<fileNumber>(.*)<\/fileNumber>.*<slaveTerminalNumber>(.*)<\/slaveTerminalNumber>.*<slaveTerminalSequence>(.*)<\/slaveTerminalSequence>/U', $response->ashraitTransactionReturn, $match);
		            $voucher_number = $match[1] . $match[2] . $match[3];

		            preg_match('/<authNumber>(.*)<\/authNumber>/U', $response->ashraitTransactionReturn, $match);
		            $authorization_number = $match[1];
		            *//*

		            preg_match('/<cardId>(.*)<\/cardId>/U', $response->ashraitTransactionReturn, $match);
		            $credit_guard_token = $match[1];

		            preg_match('/<tranId>(.*)<\/tranId>/U', $response->ashraitTransactionReturn, $match);
		            $credit_guard_tran_id = $match[1];

	            }

	        } else {
	            preg_match('/<result>(.+)<\/result>.*<message>(.*)<\/message>/U', $response->ashraitTransactionReturn, $match);
	            $error = err(200, array($match[1], $match[2]));
	        }
	        */

	        // 2016-01-31 - Due to a problem with SoapClient of PHP and the CreditGuard SSL certificate, and after upgrading OpenSSL didn't help - we switched to CURL.
	        $request = 'user='.$user;
	        $request .= '&password='.$pass;
	        $request .='&int_in=<ashrait>
                <request>
                    <command>refundDeal</command>
                    <requestId/>
                    <version>1001</version>
                    <language>' . ($_GET['lang'] == 'he' ? 'HEB' : 'ENG') . '</language>
                    <refundDeal>
                        <terminalNumber>' . substr($args->merchant_number, 0, 7) . '</terminalNumber>
                        <tranId>' . $transaction->credit_data->credit_guard_tran_id . '</tranId>
                        <authNumber>' . $transaction->credit_data->authorization_number . '</authNumber>
                        <total>' . (abs($transaction->amount) * 100) . '</total>
                    </refundDeal>
                </request>
            </ashrait>';

            $CR = curl_init();
	        curl_setopt($CR, CURLOPT_URL, $host);
	        curl_setopt($CR, CURLOPT_POST, 1);
	        curl_setopt($CR, CURLOPT_FAILONERROR, true);
	        curl_setopt($CR, CURLOPT_POSTFIELDS, $request);
	        curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($CR, CURLOPT_SSL_VERIFYHOST, 0);
	        curl_setopt($CR, CURLOPT_SSLVERSION, 1);
	        curl_setopt($CR, CURLOPT_FAILONERROR, true);
	        $response = curl_exec($CR);
	        $error = curl_error($CR);
	        curl_close($CR);

	        /*
            $debug_request .='<ashrait>
                <request>
                    <command>refundDeal</command>
                    <requestId/>
                    <version>1001</version>
                    <language>' . ($_GET['lang'] == 'he' ? 'HEB' : 'ENG') . '</language>
                    <refundDeal>
                        <terminalNumber>' . substr($args->merchant_number, 0, 7) . '</terminalNumber>
                        <tranId>' . $transaction->credit_data->credit_guard_tran_id . '</tranId>
                        <authNumber>' . $transaction->credit_data->authorization_number . '</authNumber>
                        <total>' . (abs($transaction->amount) * 100) . '</total>
                    </refundDeal>
                </request>
            </ashrait>';
	        echo '<pre>Credit Guard Server: '; print_r($host); echo '</pre>';
	        echo '<pre>Request: '; print_r(htmlspecialchars($debug_request)); echo '</pre>';
	        echo '<pre>Response: '; print_r(htmlspecialchars($response)); echo '</pre>';
	        //echo '<pre>Error: '; print_r($error); echo '</pre>';
	        exit;
	        */

	        if (preg_match('/<result>(.+)<\/result>/U', $response, $match)) {
	            $ResultRecord = $match[1];
	        }

	        if ($ResultRecord) {

	            if (substr($ResultRecord, 0, 3) != '000') {
	            	preg_match('/<message>(.+)<\/message>/U', $response, $match);
	                $error = err(200, array(substr($ResultRecord, 0, 3), $match[1]));
	            } else {
	            	// Cancel successful

	            	/*
	            	preg_match('/<fileNumber>(.*)<\/fileNumber>.*<slaveTerminalNumber>(.*)<\/slaveTerminalNumber>.*<slaveTerminalSequence>(.*)<\/slaveTerminalSequence>/U', $response, $match);
		            $voucher_number = $match[1] . $match[2] . $match[3];

		            preg_match('/<authNumber>(.*)<\/authNumber>/U', $response, $match);
		            $authorization_number = $match[1];
		            */

		            preg_match('/<cardId>(.*)<\/cardId>/U', $response, $match);
		            $credit_guard_token = $match[1];

		            preg_match('/<tranId>(.*)<\/tranId>/U', $response, $match);
		            $credit_guard_tran_id = $match[1];

	            }

	        } else {
	            preg_match('/<result>(.+)<\/result>.*<message>(.*)<\/message>/U', $response, $match);
	            $error = err(200, array($match[1], $match[2]));
	        }

	    } catch (Exception $e) {
	        $error = err(199);
	    }
    }
    return (object) array(
        'error' => $error,
        //'voucher_number' => $voucher_number,
        //'authorization_number' => $authorization_number,
        'credit_guard_token' => $credit_guard_token,
        'credit_guard_tran_id' => $credit_guard_tran_id,
    );
}

?>
