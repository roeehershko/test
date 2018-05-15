<?php

$submitUserTransactionRequest = (object) array(
    'type' => (object) array(
        'type' => 'string',
        'value' => array('credit','cash','check'),
        'null' => false
    ),
    'merchant_id' => (object) array(
        'type' => 'int',
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
            'track2' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'track2_sar_ksn' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_holder_name' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_holder_id_number' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'cc_number' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_exp' => (object) array(
                'type' => 'string',
                'value' => '{MMYY}',
                'null' => true
            ),
            'cc_cvv2' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'credit_terms' => (object) array(
                'type' => 'string',
                'value' => array('regular', 'payments', 'payments-credit'),
                'null' => true
            ),
            'j5' => (object) array(
                'type' => 'bool',
                'null' => true
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
            'use_so_credentials' => (object) array(
                'type' => 'bool',
                'null' => true
            ),
            'encryption_mode' => (object) array(
                'type' => 'string',
                'value' => 'CBC or ECB (default is ECB)',
                'null' => true
            ),
        ),
        'null' => true
    ),
    'pinpad_data' => (object) array(
        'type' => 'object',
        'value' => (object) array(
            'type' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'version' => (object) array(
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
            'customer_name' => (object) array(
                'type' => 'string',
                'null' => false
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
            )
        ),
        'null' => true
    ),
    'lang' => (object) array(
        'type' => 'string (he, en, ar, ru)',
        'null' => true
    ),
    'params' => (object) array(
        'type' => 'object',
        'null' => true
    )
);

$submitUserTransactionResponse = (object) array(
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

function submitUserTransaction($args) {

    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error, false, $args->lang);


        /*
        } elseif (!$merchant['support_new_trans']) {
            $error = err(90, false, $args->lang);
        */

    } else {

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

		$duplicated_request_block_time = 60;
        $request_hash = md5(serialize(array(
            $args->type,
            $args->amount,
            $args->currency,
            $args->credit_data->track2,
            $args->credit_data->track2_sar_ksn,
            $args->credit_data->cc_number,
            $args->credit_data->cc_exp,
            $args->credit_data->cc_cvv2,
            $args->credit_data->credit_terms,
            $args->credit_data->j5,
            $args->credit_data->payments_number,
            $args->credit_data->payments_first_amount,
            $args->credit_data->payments_standing_amount,
            $args->credit_data->use_so_credentials,
            $args->check_data->check_number,
            $args->check_data->bank_number,
            $args->check_data->branch_number,
            $args->check_data->account_number,
        )));

        if (!$merchant['allow_duplicated_transactions'] && @mysql_result(mysql_query("SELECT 1 FROM trans WHERE merchant_id = '$merchant[merchant_id]' AND TIMESTAMPDIFF(SECOND, timestamp, NOW()) < " . intval($duplicated_request_block_time) . " AND request_hash = '" . mysql_real_escape_string($request_hash) . "'"), 0)) {
            $error = err(38, false, $args->lang);
        } else if (!in_array($args->type, array('credit', 'cash', 'check'))) {
            $error = err(31, false, $args->lang);
        } else {
            if (!preg_match('/^\-?\d{1,11}(\.\d{1,2})?$/', $args->amount) || $args->amount == 0) {
                $error = err(101, false, $args->lang);
            } else if ($args->amount < 0 && !$merchant['support_refund_trans']) {
                $error = err(91, false, $args->lang);
            } else if (!in_array($args->currency, array('ILS', 'USD', 'EUR'))) {
                $error = err(102, false, $args->lang);
            } else {
                if ($args->type == 'credit') {
                    if (($merchant['processor'] == 'shva' || $merchant['processor'] == 'shva-emv') && !in_array($args->currency, array('ILS', 'USD'))) {
                        $error = err(102, false, $args->lang);
                    } else if ($merchant['processor'] == 'creditguard' && !in_array($args->currency, array('ILS', 'USD', 'EUR'))) {
                        $error = err(102, false, $args->lang);
                    } else if ($merchant['merchant_id'] != '2' && $args->credit_data->track2 && !$args->credit_data->track2_sar_ksn) {
                        // We only allow track2 if it's encrypted
                        // 2016-08-16 - Now allowing Track2, even not encrypted, for all merchants
                        //$error = err(103, false, $args->lang);
                    } else if (!$args->credit_data->track2 && !preg_match('/^\d{8,16}$/', $args->credit_data->cc_number)) {
                        $error = err(103, false, $args->lang);
                    } else if (!$args->credit_data->track2 && !preg_match('/^((0[1-9])|(1[0-2]))\d{2}$/', $args->credit_data->cc_exp)) {
                        $error = err(104, false, $args->lang);
                    } else if ($args->credit_data->credit_terms && !in_array($args->credit_data->credit_terms, array('regular', 'payments', 'payments-credit'))) {
                        $error = err(106, false, $args->lang);
                    } else if (($args->credit_data->credit_terms == 'payments' || $args->credit_data->credit_terms == 'payments-credit') && !preg_match('/^\d{1,2}$/', $args->credit_data->payments_number)) {
                        $error = err(107, false, $args->lang);
                    }

                    // Calculating Payments
                    if ($args->credit_data->credit_terms == 'payments') {
                        if ($args->credit_data->payments_first_amount || $args->credit_data->payments_standing_amount) {
                            if (!preg_match('/^\-?\d{1,11}(\.\d{1,2})?$/', $args->credit_data->payments_first_amount) || $args->credit_data->payments_first_amount == 0 || !preg_match('/^\-?\d{1,11}(\.\d{1,2})?$/', $args->credit_data->payments_standing_amount) || $args->credit_data->payments_standing_amount == 0) {
                                $error = err(105, false, $args->lang);
                            }
                        } else {
                            $args->credit_data->payments_standing_amount = $args->amount / $args->credit_data->payments_number;
                            $args->credit_data->payments_standing_amount = $args->amount > 0 ? floor($args->credit_data->payments_standing_amount) : ceil($args->credit_data->payments_standing_amount);
                            $args->credit_data->payments_first_amount = $args->credit_data->payments_standing_amount + fmod($args->amount, $args->credit_data->payments_number);
                        }

                        // Catch cases of a tiny amount being split such that the standing amount is effectively zero.
                        if ($args->credit_data->payments_standing_amount == 0) {
                            $error = err(105, false, $args->lang);
                        }

                        // Catch cases where the calculation failed and the total sum of payments does not equal the actual amount.
if (round($args->credit_data->payments_first_amount + ($args->credit_data->payments_standing_amount * ($args->credit_data->payments_number - 1)), 2) != $args->amount) {                        
//if ($args->credit_data->payments_first_amount + ($args->credit_data->payments_standing_amount * ($args->credit_data->payments_number - 1)) != $args->amount) {
                            $error = err(105, false, $args->lang);
                        }
                    } elseif ($args->credit_data->credit_terms == 'payments-credit') {
                        $args->credit_data->payments_first_amount = false;
                        $args->credit_data->payments_standing_amount = false;
                    } else {
                        $args->credit_data->credit_terms = 'regular';
                        $args->credit_data->payments_number = false;
                        $args->credit_data->payments_first_amount = false;
                        $args->credit_data->payments_standing_amount = false;
                    }

                } else if ($args->type == 'check') {
                    if (!preg_match('/^\d{1,19}$/', $args->check_data->check_number)) {
                        $error = err(110, false, $args->lang);
                    } else if (!preg_match('/^\d{1,5}$/', $args->check_data->bank_number)) {
                        $error = err(111, false, $args->lang);
                    } else if (!preg_match('/^\d{1,5}$/', $args->check_data->branch_number)) {
                        $error = err(112, false, $args->lang);
                    } else if (!preg_match('/^\d{1,18}$/', $args->check_data->account_number)) {
                        $error = err(113, false, $args->lang);
                    }
                }

                if (!$error && $args->invoice_data) {
                    $invoice_support = @mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '$merchant[merchant_id]' AND invoices_template IS NOT NULL"), 0);

                    if (!$invoice_support) {
                        $args->invoice_data = null;
                    } else if (!$args->invoice_data->gyro_details) {
                        if (!$args->invoice_data->customer_name) {
                            $error = err(122, false, $args->lang);
                        } else if (!$args->invoice_data->description) {
                            $error = err(123, false, $args->lang);
                        }
                    }
                }

                if (!$error) {
                    if ($args->params) {
                        $params = (array) $args->params;
                        foreach ($params as $name => $value) {
                            if (strlen($name) > 25) {
                                $error = err(61, false, $args->lang);
                            } elseif (strlen($value) > 500) {
                                $error = err(62, false, $args->lang);
                            }
                        }
                    }
                }

                if (!$error) {
                    $save_transaction_to_database = false;

                    if ($args->type == 'credit') {
                        if ($args->credit_data->track2_sar_ksn && $args->credit_data->track2) {
                            $request = array(
                                'track2_sar_ksn' => $args->credit_data->track2_sar_ksn,
                                'track2' => $args->credit_data->track2,
                                'encryption_mode' => $args->credit_data->encryption_mode,
                                'test' => $args->credit_data->test == 'test_bdk' ? true : false,
                            );
                            $response = file_get_contents('https://'.constant('GTS_ENC_HOST').'/rest/decryptTrack2', false, stream_context_create(array(
                                'http' => array (
                                    'method' => 'POST',
                                    'header' => 'Content-type: application/json' . "\r\n",
                                    'content' => json_encode($request)
                                )
                            )));
                            $response = json_decode($response);
                            if ($response->result == 'OKAY') {
                                $args->credit_data->track2 = $response->data->track2;
                            } else {
                                $error = err(201, false, $args->lang);
                            }
                        }

                        if (!$error) {
                            if ($args->credit_data->track2) {
                                if (preg_match('/\d{17}=\d{1}(\d{8})\d{5}(\d{2})(\d{2})(\d{1})/', $args->credit_data->track2, $matches)) {
                                    $args->credit_data->cc_number = $matches[4].$matches[1];
                                    $args->credit_data->cc_exp = $matches[3] . $matches[2];
                                } else {
                                    $args->credit_data->cc_number = preg_replace('/^(\d{9,19})\D.*$/', '$1', $args->credit_data->track2);
                                    $args->credit_data->cc_exp = preg_replace('/^\d{9,19}\D(\d{2})(\d{2}).*$/', '$2$1', $args->credit_data->track2);
                                }
                            }

                            if ($args->PWMDeviceSerialNumber) {
                                $args->card_reader_id = $args->PWMDeviceSerialNumber;
                            } elseif ($args->SARDevicePTID && $args->SARDevicePTID != '(null)') {
                                $args->card_reader_id = $args->SARDevicePTID;
                            } else {
                                $args->card_reader_id = null;
                            }

                            $args->merchant_id = $merchant['merchant_id'];
                            $args->merchant_number = $args->credit_data->use_so_credentials ? $merchant['shva_standing_orders_merchant_number'] : $merchant['shva_transactions_merchant_number'];
                            $args->merchant_parent_number = $merchant['shva_transactions_merchant_parent_number'];

                            if ($merchant['processor'] == 'shva-emv') {

                                $args->shva_username = $merchant['shva_transactions_username'];
                                $args->shva_password = $merchant['shva_transactions_password'];
                                $args->timestamp = time();
                                $return = submitUserTransaction_SHVAEMV($args);
                                //$debug = $return->authorization_number;
                                $shva_emv_response = $return->shva_emv_response;

                            } else if ($merchant['processor'] == 'shva') {

                                if ($args->credit_data->use_so_credentials) {
                                    $args->shva_username = $merchant['shva_standing_orders_username'];
                                    $args->shva_password = $merchant['shva_standing_orders_password'];
                                } else {
                                    $args->shva_username = $merchant['shva_transactions_username'];
                                    $args->shva_password = $merchant['shva_transactions_password'];
                                }
                                /*
                                return (object) array(
                        	        'result' => 'FAIL',
                        	        'debug' => $merchant['shva_transactions_password'],
                        	    );
                                */
                                $args->timestamp = time();

                                $return = submitUserTransaction_SHVA($args);

                            } elseif ($merchant['processor'] == 'creditguard') {

                                $return = submitUserTransaction_CreditGuard($args);

                            } else {

                                $return = (object) array(
                                    'error' => err(15, false, $args->lang)
                                );

                            }

                            $error = $return->error;

                            if (!$return->error) {
                                $save_transaction_to_database = true;
                            }
                        }
                    } else {

                        $save_transaction_to_database = true;

                    }

                    if ($save_transaction_to_database) {
                        if (mysql_query("INSERT INTO trans (merchant_id, timestamp, status, type, amount, currency, request_hash) VALUES ('$merchant[merchant_id]', NOW(), 'pending', '" . mysql_real_escape_string($args->type) . "', '" . mysql_real_escape_string($args->amount) . "', '" . mysql_real_escape_string($args->currency) . "', '" . mysql_real_escape_string($request_hash) . "')")) {
                            $trans_id = mysql_insert_id();

                            if ($args->type == 'credit') {
                                if (mysql_query("INSERT INTO trans_credit (trans_id, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, card_reader_id, acquirer, voucher_number, use_so_credentials, encrypted, credit_guard_token, credit_guard_tran_id) VALUES ('$trans_id', " . SQLNULL(mysql_real_escape_string($args->credit_data->cc_holder_name)) . ",'" . mysql_real_escape_string(substr($args->credit_data->cc_number, -4)) . "', '" . mysql_real_escape_string($args->credit_data->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($args->credit_data->cc_number))) . ", '" . mysql_real_escape_string($args->credit_data->credit_terms) . "', '" . mysql_real_escape_string($args->credit_data->track2 ? 'regular' : 'phone') . "', '" . mysql_real_escape_string($args->credit_data->j5 ? '1' : '0') . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_number)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_first_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_standing_amount)) . ", " . SQLNULL(mysql_real_escape_string($return->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($args->card_reader_id)) . ", " . SQLNULL(mysql_real_escape_string($return->acquirer)) . ", " . SQLNULL(mysql_real_escape_string($return->voucher_number)) . ", '" . mysql_real_escape_string($args->credit_data->use_so_credentials ? '1' : '0') . "', '" . mysql_real_escape_string($args->credit_data->encrypted) . "', " . SQLNULL(mysql_real_escape_string($return->credit_guard_token)) . ", " . SQLNULL(mysql_real_escape_string($return->credit_guard_tran_id)) . ")")) {
                                    if ($return->raw_dump) {
                                        // 2016-11-02 - Per Verifone request - we are making the result-record (int_ot) available (and not "private" as it used to be)
                                        if (!mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '0', 'raw_dump', '" . mysql_real_escape_string($return->raw_dump) . "')")) {
                                            $error = err(5, false, $args->lang);
                                        }
                                    }

                                    if ($merchant['processor'] == 'shva-emv' && $return->shva_emv_response->tranRecord->valueTag) {
                                        mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '1', 'shva_trans_record', '" . mysql_real_escape_string($return->shva_emv_response->tranRecord->valueTag) . "')");
                                    } else if ($merchant['processor'] == 'shva' && $return->shva_trans_record) {
                                        mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '1', 'shva_trans_record', '" . mysql_real_escape_string($return->shva_trans_record) . "')");
                                    }

                                } else {
                                    $error = err(5, false, $args->lang);
                                }
                            } else if ($args->type == 'check') {
                                if (!mysql_query("INSERT INTO trans_check (trans_id, check_number, bank_number, branch_number, account_number) VALUES ('$trans_id', '" . mysql_real_escape_string($args->check_data->check_number) . "', '" . mysql_real_escape_string($args->check_data->bank_number) . "', '" . mysql_real_escape_string($args->check_data->branch_number) . "', '" . mysql_real_escape_string($args->check_data->account_number) . "')")) {
                                    $error = err(5, false, $args->lang);
                                }
                            }

                            if ($args->invoice_data) {
                                if (mysql_query("INSERT INTO trans_invoices (trans_id, customer_name, customer_number, address_street, address_city, address_zip, phone, description, gyro_details) VALUES ('$trans_id', " . SQLNULL(mysql_real_escape_string($args->invoice_data->customer_name)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->customer_number)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->address_street)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->address_city)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->address_zip)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->phone)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->description)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_data->gyro_details)) . ")")) {
                                    if ($args->invoice_data->recipients) {
                                        $args->invoice_data->recipients = array_unique($args->invoice_data->recipients);

                                        foreach ($args->invoice_data->recipients as $email) {
                                            if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) {
                                                if (!mysql_query("INSERT INTO trans_invoices_recipients (trans_id, email) VALUES ('$trans_id', '" . mysql_real_escape_string(strtolower($email)) . "')")) {
                                                    $error = err(5, false, $args->lang);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $error = err(5, false, $args->lang);
                                }

                                if (!$error && $merchant['processor'] == 'creditguard') {
                                    generateInvoice($trans_id);
                                }
                            }

                            if (!$error && !$args->credit_data->j5 && $merchant['processor'] == 'creditguard') {
                                mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $trans_id . "'");
                            }

                            if ($args->params) {
                                $params = (array) $args->params;
                                foreach ($params as $name => $value) {
                                    if ($value) {
                                        if (!mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '0', '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($value) . "')")) {
                                            $error = err(5, false, $args->lang);
                                        }
                                    }
                                }
                            }
                        } else {
                            $error = err(5, false, $args->lang);
                        }
                    }
                }
            }
        }
    }

    include_once 'functions/obtainUserTransactionDetails.inc.php';
    $request = (object) array(
        'username' => $args->username,
        'password' => $args->password,
        'trans_id' => (int) $trans_id,
        'include' => array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params'),
    );
    $response = obtainUserTransactionDetails($request);
    /*
    return (object) array(
        'result' => 'FAIL',
        'error' => $error ?: null,
        'data' => $error ? null : (object) array(
            'trans_id' => (int) $trans_id,
            'debug' => array(
                'args' => $args,
                'request' => $request,
                'response' => $response
            )
        )
    );
    */

    if (!$error && $response->result == 'OKAY') {
    	// 2017-08-06 - Due to a bug in Verifone's devices, we must return "completed", as a temporary solution (for SHVA terminals to match Credit Guard)
        $response->data->status = 'completed';

        // SHVA EMV Full Response
        $response->data->shva_emv_response = $shva_emv_response;
        //$response->debug = $debug;

        return $response;
    } else {
        return (object) array(
	        'result' => !$error ? 'OKAY' : 'FAIL',
	        'error' => $error ?: null,
            //'debug' => $debug,
	        'data' => $error ? null : (object) array(
	            'trans_id' => (int) $trans_id,
	        )
	    );
    }

}

function submitUserTransaction_SHVAEMV($args) {
    $client = new SoapClient('https://www.shva-online.co.il/EMVWeb/prod/EMVRequest.asmx?wsdl', array('soap_version' => SOAP_1_2, 'cache_wsdl' => WSDL_CACHE_NONE , 'trace' => 1, 'exceptions' => 0));
    $request = (object) array(
        'MerchantNumber' => $args->merchant_number,
        'UserName' => $args->shva_username,
        'Password' => $args->shva_password,
		'inputObj' => (object) array(
			'bAnalysisXmlStr' => false,
			'status' => 'DEFAULT',
			'mti' => '100', // STRING; 100 - Charge; // 400 - Cancel that was not submitted; // 420 - Reversal (for J5 only)
			'panEntryMode' => $args->credit_data->track2 ? '00' : '50', // STRING; 50 Phone; 51 Signature; 52 Interent;
			'eci' => $args->credit_data->track2 ? '' : 1,
			'tranType' => $args->amount < 0 ? '53' : '01', // 1 - Charge; 53 - Refund
			'expirationDate' => substr($args->credit_data->cc_exp, 2, 2) . substr($args->credit_data->cc_exp, 0, 2), // YYMM
			'currency' => $args->currency == 'ILS' ? '376' : '840', // 376 NIS; 840 USD;
			'amount' => abs($args->amount)*100,
			'creditTerms' => $args->credit_data->credit_terms == 'payments' ? '8' : ($args->credit_data->credit_terms == 'payments-credit' ? '6' : '1'), // 1 - Regular; 8 - Payments; 6 - Credit
			'firstPayment' => $args->credit_data->credit_terms == 'payments' ? abs($args->credit_data->payments_first_amount)*100 : false,
			'notFirstPayment' => $args->credit_data->credit_terms == 'payments' ? abs($args->credit_data->payments_standing_amount)*100 : false,
			'noPayments' => $args->credit_data->credit_terms == 'payments' ? $args->credit_data->payments_number - 1 : ($args->credit_data->credit_terms == 'payments-credit' ? $args->credit_data->payments_number : false), // Number of payments
			'clientInputPan' => $args->credit_data->track2 ?: $args->credit_data->cc_number, // track2 or PAN

            'authorizationNo' => $args->credit_data->authorization_number,
            'authorizationCodeManpik' => $args->credit_data->authorization_number ? '5' : '',

			'id' => $args->credit_data->cc_holder_id_number,
            'cvv2' => $args->credit_data->cc_cvv2, //661

            'sapakMutavNo' => $args->merchant_parent_number
		),
        'pinpad' => $args->pinpad_data->serial ? (object) array(
            //'pinpadType' => $args->pinpad_data->type,
            //'resourceTransferCard' => 1,
            'pinpadSerialNumber' => $args->pinpad_data->serial ? substr($args->pinpad_data->serial, 0, 8) : '',
            'pinpadSoftwareVersion' => $args->pinpad_data->version ? substr($args->pinpad_data->version, 0, 6) : '',
            'tag9A' => '',
            'tag9F21' => '',
            'tag9F02' => '',
            'tag5F2A' => '',
            'tag9F03' => '',
            'authTxnAuthType' => 'Purchase',
            'endTransaction' => '',
            'txnStatus' => 'OK',
            'result' => '',
            'fallback' => 'None',
            'mobile' => '',
            'authForceManualEntry' => '',
            'cardEntryMode' => 'NotEntered',
            'bForceOnline' => '',
            'bBlackList' => '',
            'bFloorLimit' => '',
            'bRandomSelection' => '',
            'originalAuthTxnAuthType' => 'Purchase',
            'hostResult' => 'None',
            'txnOutcome' => 'ApprovedOnline',
            'bStopExamine' => '',
            'status' => 'OK',
            'serverOffDemand' => 'DEFAULT',
            'resourceTransferCard' => 'DEFAULT_VALUE',
            'bAshReason22' => '',
            'bIgnorePP' => '',
            'type' => 'verifone',
            'tag9C' => '',
            'ashStatus' => 'OK',
            'ashReason' => 'DEFAULT',
            'statusCode' => 'PromptForCardEntry',
            'connectionType' => 'Ethernet',
            'ipPort' => ''
        ) : null,
		'globalObj' => (object) array(
            'ravSapakMutav' => false
        ),
    );
    $response = $client->AshFull($request);

    //echo '<pre>Request: '; print_r($request); echo '</pre>';
    //echo '<pre>Response: '; print_r($response); echo '</pre>';
    //exit;

    if ($response->AshFullResult != '0') {
        // 2017-11-13 - If a PinPad device sent the transaction - we return the actual SHVA error code and description
        $error = err(500 + $response->AshFullResult, false, $args->lang, $response->globalObj && $response->globalObj->outputObj && $response->globalObj->outputObj->ashStatusDes ? $response->globalObj->outputObj->ashStatusDes->valueTag : false, $args->pinpad_data->serial ? $response->AshFullResult : false);
        //$debug = $response->globalObj && $response->globalObj->outputObj;
    }

    if ($error) {
        mysql_query("INSERT INTO trans_credit_errors (merchant_id, timestamp, amount, currency, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, card_reader_id, encrypted, error_code) VALUES ('" . $args->merchant_id . "', NOW(), '" . mysql_real_escape_string($args->amount) . "', '" . mysql_real_escape_string($args->currency) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->cc_holder_name)) . ", '" . mysql_real_escape_string(substr($args->credit_data->cc_number, -4)) . "', '" . mysql_real_escape_string($args->credit_data->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($args->credit_data->cc_number))) . ", '" . mysql_real_escape_string($args->credit_data->credit_terms) . "', '" . ($args->credit_data->track2 ? 'regular' : 'phone') . "', '" . mysql_real_escape_string($args->credit_data->j5) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_number)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_first_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_standing_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($args->card_reader_id)) . ", '" . mysql_real_escape_string($args->credit_data->encrypted) . "', '" . substr($error, 0, 3) . "')");
    } else {
        $acquirer_id = $response->outputObj->manpik;
        if ($acquirer_id == '1') {
            $acquirer = 'isracard';
        } elseif ($acquirer_id == '2') {
            $acquirer = 'visa-cal';
        } elseif ($acquirer_id == '3') {
            $acquirer = 'diners-club';
        } elseif ($acquirer_id == '4') {
            $acquirer = 'american-express';
        } elseif ($acquirer_id == '6') {
            $acquirer = 'leumicard';
        }
    }

    return (object) array(
        'error' => $error,
        //'debug' => $response,
        'voucher_number' => $response->globalObj->receiptObj->voucherNumber->valueTag,
        'authorization_number' => $response->globalObj->receiptObj->authNo->valueTag,
        'acquirer' => $acquirer,
        'shva_emv_response' => $response->globalObj ? $response->globalObj->outputObj : null
        //'shva_trans_record' => $return->TransactionRecord,
        //'raw_dump' => preg_replace('/^(\d{6})\d{13}/', '$1xxxxxxxxxxxxx', $return->ResultRecord)
    );
}

function submitUserTransaction_SHVA($args) {
    $client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
    $request = (object) array(
        'MerchantNumber' => $args->merchant_number,
        'UserName' => $args->shva_username,
        'Password' => $args->shva_password,
        'TransactionDate_yyyyMMdd' => date('Ymd', $args->timestamp),
        'TransactionTime_HHmm' => date('Hi', $args->timestamp),
        'Track2' => $args->credit_data->track2,
        'CardNum' => $args->credit_data->track2 ? false : $args->credit_data->cc_number,
        'ExpDate_YYMM' => $args->credit_data->track2 ? false : substr($args->credit_data->cc_exp, 2, 2) . substr($args->credit_data->cc_exp, 0, 2),
        'Amount' => abs($args->amount) * 100,
        'TransactionType' => $args->amount < 0 ? '51' : '01',
        'CreditTerms' => $args->credit_data->credit_terms == 'payments' ? '8' : ($args->credit_data->credit_terms == 'payments-credit' ? '6' : '1'),
        'Currency' => $args->currency == 'ILS' ? '1' : '2',
        'AuthNum' => $args->credit_data->authorization_number,
        'Code' => $args->credit_data->track2 ? '00' : '50',
        'FirstAmount' => $args->credit_data->credit_terms == 'payments' ? abs($args->credit_data->payments_first_amount) * 100 : false,
        'NonFirstAmount' => $args->credit_data->credit_terms == 'payments' ? abs($args->credit_data->payments_standing_amount) * 100 : false,
        'NumOfPayment' => $args->credit_data->credit_terms == 'payments' ? $args->credit_data->payments_number - 1 : ($args->credit_data->credit_terms == 'payments-credit' ? $args->credit_data->payments_number : false),
        'SapakMutav' => '1',
        'SapakMutavNo' => '8871000', // 7111000
        'ParamJ' => $args->credit_data->j5 ? '5' : '4',
        'Cvv2' => $args->credit_data->use_so_credentials ? false : $args->credit_data->cc_cvv2,
        'Id' => $args->credit_data->use_so_credentials ? false : $args->credit_data->cc_holder_id_number,
        'Last4Digits' => $args->credit_data->track2 ? substr($args->credit_data->cc_number, -4) : false
    );
    $return = $client->AuthCreditCardFull($request);

    //echo '<pre>Request: '; print_r($request); echo '</pre>';
    //echo '<pre>Response: '; print_r($return); echo '</pre>';
    //exit;

    if ($return->AuthCreditCardFullResult == 250) {
        $error = err(15, false, $args->lang);
    } elseif ($return->AuthCreditCardFullResult != 0) {
        $error = err(500 + $return->AuthCreditCardFullResult, false, $args->lang);
    } elseif (substr($return->ResultRecord, 0, 3) != '000') {
        $error = err(500 + substr($return->ResultRecord, 0, 3), false, $args->lang);
    }

    if ($error) {
        mysql_query("INSERT INTO trans_credit_errors (merchant_id, timestamp, amount, currency, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, card_reader_id, encrypted, error_code) VALUES ('" . $args->merchant_id . "', NOW(), '" . mysql_real_escape_string($args->amount) . "', '" . mysql_real_escape_string($args->currency) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->cc_holder_name)) . ", '" . mysql_real_escape_string(substr($args->credit_data->cc_number, -4)) . "', '" . mysql_real_escape_string($args->credit_data->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($args->credit_data->cc_number))) . ", '" . mysql_real_escape_string($args->credit_data->credit_terms) . "', '" . ($args->credit_data->track2 ? 'regular' : 'phone') . "', '" . mysql_real_escape_string($args->credit_data->j5) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_number)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_first_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_standing_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($args->card_reader_id)) . ", '" . mysql_real_escape_string($args->credit_data->encrypted) . "', '" . substr($error, 0, 3) . "')");
    } else {
        $acquirer_id = substr($return->ResultRecord, 25 - 1, 1);
        if ($acquirer_id == '1') {
            $acquirer = 'isracard';
        } elseif ($acquirer_id == '2') {
            $acquirer = 'visa-cal';
        } elseif ($acquirer_id == '3') {
            $acquirer = 'diners-club';
        } elseif ($acquirer_id == '4') {
            $acquirer = 'american-express';
        } elseif ($acquirer_id == '6') {
            $acquirer = 'leumicard';
        }
    }

    return (object) array(
        'error' => $error,
        'voucher_number' => substr($return->ResultRecord, 96 - 1, 8),
        'authorization_number' => substr($return->ResultRecord, 71 - 1, 7),
        'acquirer' => $acquirer,
        'shva_trans_record' => $return->TransactionRecord,
        'raw_dump' => preg_replace('/^(\d{6})\d{13}/', '$1xxxxxxxxxxxxx', $return->ResultRecord)
    );
}

function submitUserTransaction_CreditGuard($args) {

    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'creditguard_username'"));
    $creditguard_username = aes_decrypt($object->value);
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'creditguard_password'"));
    $creditguard_password = aes_decrypt($object->value);

    if ($args->merchant_id == '2') {
        //$host = 'https://verifonetest.creditguard.co.il/xpo/services/Relay?wsdl';
        //$host = '/var/www/sites/gts-app-test.pnc.co.il/verifonetest.creditguard.wsdl';
        $host = 'https://verifonetest.creditguard.co.il/xpo/Relay';
        $user = 'ivpayt';
        $pass = 'Iv@2pt3p';
    } else {
        //$host = 'https://veripayapi.creditguard.co.il/xpo/services/Relay?wsdl';
        //$host = '/var/www/sites/gts-app-test.pnc.co.il/verifonapi.creditguard.wsdl';
        $host = 'https://veripayapi.creditguard.co.il/xpo/Relay';
        $user = $creditguard_username;
        $pass = $creditguard_password;
    }

    try {
        /*
        $tls_ciphers = explode(':', shell_exec('openssl ciphers -v | grep TLSv1.2 | cut -d " " -f 1 | tr "\n" ":" | sed "s/:\$//"'));
        $context = stream_context_create(array('ssl' => array(
            //'protocol_version' => 'tls1',
            'ciphers' => $tls_ciphers
        )));
        $soapClientArray = array(
            //'user_agent' => 'salesforce-toolkit-php/20.0',
            //'encoding' => 'utf-8',
            //'trace' => 1,
            //'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'stream_context' => $context
        );
        $CreditGuardWSDL = new SoapClient($host, $soapClientArray);

        $response = $CreditGuardWSDL->ashraitTransaction(array(
            'user' => $user,
            'password' => $pass,
            'int_in' => '<ashrait>
                <request>
                    <command>doDeal</command>
                    <requestId/>
                    <version>1001</version>
                    <language>' . ($_GET['lang'] == 'he' ? 'HEB' : 'ENG') . '</language>
                    <doDeal>
                        <terminalNumber>' . substr($args->merchant_number, 0, 7) . '</terminalNumber>
                        <track2>' . ($args->credit_data->track2 ? $args->credit_data->track2 : false) . '</track2>
                        '.($args->credit_data->credit_terms == 'recurring_order' ? '
                            <cardId>' . (!$args->credit_data->track2 ? $args->credit_data->cc_number : false) . '</cardId>
                        ' : '
                            <cardNo>' . (!$args->credit_data->track2 ? $args->credit_data->cc_number : false) . '</cardNo>
                        ').'
                        <cardExpiration>' . (!$args->credit_data->track2 ? $args->credit_data->cc_exp : false) . '</cardExpiration>
                        <cvv>' . ($args->credit_data->use_so_credentials ? false : $args->credit_data->cc_cvv2) . '</cvv>
                        <id>' . ($args->credit_data->use_so_credentials ? false : $args->credit_data->cc_holder_id_number) . '</id>
                        <last4D>' . ($args->credit_data->track2 ? substr($args->credit_data->cc_number, -4) : false) . '</last4D>
                        <transactionType>' . ($args->amount < 0 ? 'Credit' : 'Debit') . '</transactionType>
                        <creditType>' . ($args->credit_data->credit_terms == 'payments' ? 'Payments' : ($args->credit_data->credit_terms == 'payments-credit' ? 'SpecialCredit' : 'RegularCredit')) . '</creditType>
                        <currency>' . $args->currency . '</currency>
                        <transactionCode>' . ($args->credit_data->track2 ? 'Regular' : 'Phone') . '</transactionCode>
                        <total>' . (abs($args->amount) * 100) . '</total>
                        <authNumber>' . ($args->credit_data->authorization_number ? $args->credit_data->authorization_number : false) . '</authNumber>
                        <firstPayment>' . ($args->credit_data->credit_terms == 'payments' ? (abs($args->credit_data->payments_first_amount) * 100) : false) . '</firstPayment>
                        <periodicalPayment>' . ($args->credit_data->credit_terms == 'payments' ? (abs($args->credit_data->payments_standing_amount) * 100) : false) . '</periodicalPayment>
                        <numberOfPayments>' . ($args->credit_data->credit_terms == 'payments' ? $args->credit_data->payments_number - 1 : ($args->credit_data->credit_terms == 'payments-credit' ? $args->credit_data->payments_number : false)) . '</numberOfPayments>
                        <validation>' . ($args->credit_data->j5 ? 'Verify' : 'AutoComm') . '</validation>
                    </doDeal>
                </request>
            </ashrait>'
        ));
        */

        // 2016-01-31 - Due to a problem with SoapClient of PHP and the CreditGuard SSL certificate, and after upgrading OpenSSL didn't help - we switched to CURL.
        $request = 'user='.$user;
        $request .= '&password='.$pass;
        $request .='&int_in=<ashrait>
            <request>
                <command>doDeal</command>
                <requestId/>
                <version>1001</version>
                <language>' . ($_GET['lang'] == 'he' ? 'HEB' : 'ENG') . '</language>
                <doDeal>
                    <terminalNumber>' . substr($args->merchant_number, 0, 7) . '</terminalNumber>
                    <track2>' . ($args->credit_data->track2 ? $args->credit_data->track2 : false) . '</track2>
                    '.($args->credit_data->credit_terms == 'recurring_order' ? '
                        <cardId>' . (!$args->credit_data->track2 ? $args->credit_data->cc_number : false) . '</cardId>
                    ' : '
                        <cardNo>' . (!$args->credit_data->track2 ? $args->credit_data->cc_number : false) . '</cardNo>
                    ').'
                    <cardExpiration>' . (!$args->credit_data->track2 ? $args->credit_data->cc_exp : false) . '</cardExpiration>
                    <cvv>' . ($args->credit_data->use_so_credentials ? false : $args->credit_data->cc_cvv2) . '</cvv>
                    <id>' . ($args->credit_data->use_so_credentials ? false : $args->credit_data->cc_holder_id_number) . '</id>
                    <last4D>' . ($args->credit_data->track2 ? substr($args->credit_data->cc_number, -4) : false) . '</last4D>
                    <transactionType>' . ($args->amount < 0 ? 'Credit' : 'Debit') . '</transactionType>
                    <creditType>' . ($args->credit_data->credit_terms == 'payments' ? 'Payments' : ($args->credit_data->credit_terms == 'payments-credit' ? 'SpecialCredit' : 'RegularCredit')) . '</creditType>
                    <currency>' . $args->currency . '</currency>
                    <transactionCode>' . ($args->credit_data->track2 ? 'Regular' : 'Phone') . '</transactionCode>
                    <total>' . (abs($args->amount) * 100) . '</total>
                    <authNumber>' . ($args->credit_data->authorization_number ? $args->credit_data->authorization_number : false) . '</authNumber>
                    <firstPayment>' . ($args->credit_data->credit_terms == 'payments' ? (abs($args->credit_data->payments_first_amount) * 100) : false) . '</firstPayment>
                    <periodicalPayment>' . ($args->credit_data->credit_terms == 'payments' ? (abs($args->credit_data->payments_standing_amount) * 100) : false) . '</periodicalPayment>
                    <numberOfPayments>' . ($args->credit_data->credit_terms == 'payments' ? $args->credit_data->payments_number - 1 : ($args->credit_data->credit_terms == 'payments-credit' ? $args->credit_data->payments_number : false)) . '</numberOfPayments>
                    <validation>' . ($args->credit_data->j5 ? 'Verify' : 'AutoComm') . '</validation>
                </doDeal>
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
        echo '<pre>Request: '; print_r(htmlspecialchars($request)); echo '</pre>';
        echo '<pre>Response: '; print_r($host); echo '</pre>';
        echo '<pre>Response: '; echo htmlspecialchars($response); echo '</pre>';
        echo '<pre>Error: '; print_r($error); echo '</pre>';
        exit;
        */
        if (preg_match('/<intOt>(.+)<\/intOt>/U', $response, $match)) {
            $ResultRecord = $match[1];
        }

        if ($ResultRecord) {
            if (substr($ResultRecord, 0, 3) != '000') {
                $error = err(500 + substr($ResultRecord, 0, 3), false, $args->lang);
            }

            preg_match('/<fileNumber>(.*)<\/fileNumber>.*<slaveTerminalNumber>(.*)<\/slaveTerminalNumber>.*<slaveTerminalSequence>(.*)<\/slaveTerminalSequence>/U', $response, $match);
            $voucher_number = $match[1] . $match[2] . $match[3];

            preg_match('/<authNumber>(.*)<\/authNumber>/U', $response, $match);
            $authorization_number = $match[1];

            preg_match('/<cardId>(.*)<\/cardId>/U', $response, $match);
            $credit_guard_token = $match[1];

            preg_match('/<tranId>(.*)<\/tranId>/U', $response, $match);
            $credit_guard_tran_id = $match[1];

        } else {
            preg_match('/<result>(.+)<\/result>.*<userMessage>(.*)<\/userMessage>/U', $response, $match);
            $error = err(200, array($match[1], $match[2]), $args->lang);
        }
    } catch (Exception $e) {
        $error = err(199, false, $args->lang);
    }

    if ($error) {
        mysql_query("INSERT INTO trans_credit_errors (merchant_id, timestamp, amount, currency, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, card_reader_id, encrypted, error_code) VALUES ('" . $args->merchant_id . "', NOW(), '" . mysql_real_escape_string($args->amount) . "', '" . mysql_real_escape_string($args->currency) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->cc_holder_name)) . ", '" . mysql_real_escape_string(substr($args->credit_data->cc_number, -4)) . "', '" . mysql_real_escape_string($args->credit_data->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($args->credit_data->cc_number))) . ", '" . mysql_real_escape_string($args->credit_data->credit_terms) . "', '" . ($args->credit_data->track2 ? 'regular' : 'phone') . "', '" . mysql_real_escape_string($args->credit_data->j5) . "', " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_number)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_first_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->payments_standing_amount)) . ", " . SQLNULL(mysql_real_escape_string($args->credit_data->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($args->card_reader_id)) . ", '" . mysql_real_escape_string($args->credit_data->encrypted) . "', '" . substr($error, 0, 3) . "')");
    } else {
        $acquirer_id = substr($ResultRecord, 25 - 1, 1);
        if ($acquirer_id == '1') {
            $acquirer = 'isracard';
        } elseif ($acquirer_id == '2') {
            $acquirer = 'visa-cal';
        } elseif ($acquirer_id == '3') {
            $acquirer = 'diners-club';
        } elseif ($acquirer_id == '4') {
            $acquirer = 'american-express';
        } elseif ($acquirer_id == '6') {
            $acquirer = 'leumicard';
        }
    }

    return (object) array(
        'error' => $error,
        'voucher_number' => $voucher_number,
        'authorization_number' => $authorization_number,
        'acquirer' => $acquirer,
        'raw_dump' => preg_replace('/^(\d{6})\d{13}/', '$1xxxxxxxxxxxxx', $ResultRecord),
        'credit_guard_token' => $credit_guard_token,
        'credit_guard_tran_id' => $credit_guard_tran_id,
    );
}

?>
