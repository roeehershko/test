<?php

$obtainUserTransactionDetailsRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'include' => (object) array(
        'type' => 'array of string',
        'value' => array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params'),
        'null' => true
    )
);

$obtainUserTransactionDetailsResponse = (object) array(
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
            'trans_id' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'merchant_id' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'timestamp' => (object) array(
                'type' => 'string',
                'value' => '{YYYY-MM-DD HH:MM:SS}',
                'null' => false
            ),
            'status' => (object) array(
                'type' => 'string',
                'value' => array('completed', 'pending'),
                'null' => false
            ),
            'type' => (object) array(
                'type' => 'string',
                'value' => array('credit', 'cash', 'check'),
                'null' => false
            ),
            'amount' => (object) array(
                'type' => 'decimal',
                'null' => false
            ),
            'currency' => (object) array(
                'type' => 'string',
                'value' => array('ILS', 'USD', 'EUR'),
                'null' => false
            ),
            'credit_data' => (object) array(
                'type' => 'object',
                'value' => (object) array(
                    'cc_holder_name' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'cc_last_4' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'cc_exp' => (object) array(
                        'type' => 'string',
                        'value' => '{MMYY}',
                        'null' => false
                    ),
                    'cc_type' => (object) array(
                        'type' => 'string',
                        'value' => array('visa', 'mastercard', 'discover', 'american-express', 'diners-club', 'isracard'),
                        'null' => true
                    ),
                    'credit_terms' => (object) array(
                        'type' => 'string',
                        'value' => array('regular', 'payments-credit', 'payments'),
                        'null' => false
                    ),
                    'transaction_code' => (object) array(
                        'type' => 'string',
                        'value' => array('regular', 'phone'),
                        'null' => false
                    ),
                    'j5' => (object) array(
                        'type' => 'bool',
                        'null' => false
                    ),
                    'payments_number' => (object) array(
                        'type' => 'int',
                        'null' => true
                    ),
                    'payments_first_amount' => (object) array(
                        'type' => 'decimal',
                        'null' => true
                    ),
                    'payments_standing_amount' => (object) array(
                        'type' => 'decimal',
                        'null' => true
                    ),
                    'authorization_number' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'card_acquirer' => (object) array(
                        'type' => 'string',
                        'value' => array('isracard', 'visa-cal', 'diners-club', 'american-express', 'leumicard'),
                        'null' => true
                    ),
                    'voucher_number' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'reference_number' => (object) array(
                        'type' => 'int',
                        'null' => true
                    ),
                    'use_so_credentials' => (object) array(
                        'type' => 'bool',
                        'null' => false
                    ),
                    'signature_link' => (object) array(
                        'type' => 'string',
                        'null' => true
                    )
                ),
                'null' => true
            ),
            'check_data' => (object) array(
                'type' => 'object',
                'value' => (object) array(
                    'check_number' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'bank_number' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'branch_number' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'account_number' => (object) array(
                        'type' => 'int',
                        'null' => false
                    )
                ),
                'null' => true
            ),
            'invoice_data' => (object) array(
                'type' => 'object',
                'value' => (object) array(
                    'number' => (object) array(
                        'type' => 'int',
                        'null' => true
                    ),
                    'customer_name' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'customer_number' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'address_street' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'address_city' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'address_zip' => (object) array(
                        'type' => 'int',
                        'null' => true
                    ),
                    'phone' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'description' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'gyro_details' => (object) array(
                        'type' => 'string',
                        'null' => true
                    ),
                    'recipients' => (object) array(
                        'type' => 'array of string',
                        'null' => true
                    ),
                    'link' => (object) array(
                        'type' => 'string',
                        'null' => false
                    )
                ),
                'null' => true
            ),
            'params' => (object) array(
                'type' => 'object',
                'null' => true
            )
        ),
        'null' => true
    )
);

function obtainUserTransactionDetails($args) {
    
    if ($args->admin && $args->merchant_id && !$merchants = authenticateAdmin($args->merchant_id, $args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!$args->admin && !$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!preg_match('/^\d+$/', $args->trans_id)) {
        $error = 30;
    //} else if (!($transactionDetails = @mysql_fetch_object(mysql_query("SELECT trans_id, timestamp, status, type, amount, currency FROM trans WHERE status != 'canceled' AND trans_id = '" . mysql_real_escape_string($args->trans_id) . "'")))) {
    } else if (!($transactionDetails = @mysql_fetch_object(mysql_query("SELECT trans_id, merchant_id, timestamp, status, type, amount, currency FROM trans WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "'")))) {
        $error = 30;
    } else {
        // Making sure this transaction belongs to one of the user's authorized merchants
        $merchant_ids = false;
        foreach ($merchants as $merchant) {
            $merchant_ids[$merchant['merchant_id']] = true;
        }
        if (!$merchant_ids[$transactionDetails->merchant_id]) {
            $error = 30;
        } else {
            if ($transactionDetails->type == 'credit') {
                if (($sql = @mysql_fetch_object(mysql_query("SELECT cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, acquirer, voucher_number, reference_number, use_so_credentials, credit_guard_token, credit_guard_tran_id FROM trans_credit WHERE trans_id = '" . $transactionDetails->trans_id . "'")))) {
                    if (@in_array('credit_data', $args->include)) {
                        $transactionDetails->credit_data = (object) array(
                            'cc_holder_name' => $sql->cc_holder_name ?: null,
                            'cc_last_4' => $sql->cc_last_4,
                            'cc_exp' => $sql->cc_exp,
                            'cc_type' => $sql->cc_type ?: null,
                            'credit_terms' => $sql->credit_terms,
                            'transaction_code' => $sql->transaction_code,
                            'j5' => (bool) $sql->j5,
                            'payments_number' => $sql->payments_number ? (int) $sql->payments_number : null,
                            'payments_first_amount' => $sql->payments_first_amount ?: null,
                            'payments_standing_amount' => $sql->payments_standing_amount ?: null,
                            'authorization_number' => (string) $sql->authorization_number ?: null,
                            'card_acquirer' => $sql->acquirer ?: null,
                            'voucher_number' => (int) $sql->voucher_number ?: null,
                            'reference_number' => $sql->reference_number ? (int) $sql->reference_number : null,
                            'use_so_credentials' => (bool) $sql->use_so_credentials,
                            'credit_guard_token' => $sql->credit_guard_token ?: null,
                            'credit_guard_tran_id' => $sql->credit_guard_tran_id ?: null,
                        );
                        
                        if (@mysql_result(mysql_query("SELECT 1 FROM trans_signatures WHERE trans_id = '" . $transactionDetails->trans_id . "'"), 0)) {
                            $transactionDetails->credit_data->signature_link = 'https://'.constant('GTS_APP_HOST').'/signatures/' . authSignature($transactionDetails->trans_id) . '/' . $transactionDetails->trans_id . '.png';
                        } else {
                            $transactionDetails->credit_data->signature_link = null;
                        }
                    }
                }
            } else if ($transactionDetails->type == 'check' && @in_array('check_data', $args->include)) {
                if ($sql = @mysql_fetch_object(mysql_query("SELECT check_number, bank_number, branch_number, account_number FROM trans_check WHERE trans_id = '" . $transactionDetails->trans_id . "'"))) {
                    $transactionDetails->check_data = (object) array(
                        'check_number' => (int) $sql->check_number,
                        'bank_number' => (int) $sql->bank_number,
                        'branch_number' => (int) $sql->branch_number,
                        'account_number' => (int) $sql->account_number
                    );
                }
            }
            
            if ($sql = @mysql_fetch_object(mysql_query("SELECT number, customer_name, customer_number, address_street, address_city, address_zip, phone, description, gyro_details FROM trans_invoices WHERE trans_id = '" . $transactionDetails->trans_id . "'"))) {
                if (@in_array('invoice_data', $args->include) && !$transactionDetails->credit_data->j5) {
                    $transactionDetails->invoice_data = (object) array(
                        'number' => $sql->number ? (int) $sql->number : null,
                        'customer_name' => $sql->customer_name ?: null,
                        'customer_number' => $sql->customer_number ?: null,
                        'address_street' => $sql->address_street ?: null,
                        'address_city' => $sql->address_city ?: null,
                        'address_zip' => $sql->address_zip ? (int) $sql->address_zip : null,
                        'phone' => $sql->phone ?: null,
                        'description' => $sql->description ?: null,
                        'recipients' => null,
                        'link' => 'https://'.constant('GTS_APP_HOST').'/invoices/' . authInvoice($transactionDetails->trans_id) . '/' . $transactionDetails->trans_id . '.pdf'
                    );
                    
                    if (@in_array('gyro_details', $args->include)) {
                        $transactionDetails->invoice_data->gyro_details = $sql->gyro_details ?: null;
                    }
                    
                    $sql_query = mysql_query("SELECT email FROM trans_invoices_recipients WHERE trans_id = '" . $transactionDetails->trans_id . "'");
                    while ($sql = @mysql_fetch_object($sql_query)) {
                        $transactionDetails->invoice_data->recipients[] = $sql->email;
                    }
                }
            }
            
            if (@in_array('params', $args->include)) {
                $transactionDetails->params = (object) array();
                $sql_query = mysql_query("SELECT name, value FROM trans_params WHERE trans_id = '" . $transactionDetails->trans_id . "' AND private = '0'");
                while ($sql = @mysql_fetch_object($sql_query)) {
                    $transactionDetails->params->{$sql->name} = $sql->value;
                }
            }
        }
    }
    
    if (!$error) {
        $data = (object) array();
        $data->trans_id = (int) $transactionDetails->trans_id;
        $data->merchant_id = (int) $transactionDetails->merchant_id;
        $data->timestamp = $transactionDetails->timestamp;
        $data->status = $transactionDetails->status;
        $data->type = $transactionDetails->type;
        $data->amount = $transactionDetails->amount;
        $data->currency = $transactionDetails->currency;
        $data->credit_data = $transactionDetails->credit_data ?: null;
        $data->check_data = $transactionDetails->check_data ?: null;
        $data->invoice_data = $transactionDetails->invoice_data ?: null;
        $data->params = $transactionDetails->params ?: null;
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : $data
    );
}

?>