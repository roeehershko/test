<?php

$createUserRecurringOrderRequest = (object) array(
    'merchant_id' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'name' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'duedate' => (object) array(
        'type' => 'string',
        'value' => '{YYYY-MM-DD}',
        'null' => false
    ),
    'repetitions' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'interval' => (object) array(
        'type' => 'string',
        'value' => array('monthly', 'yearly'),
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
    'track2' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'track2_sar_ksn' => (object) array(
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
    'invoice_customer_name' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'invoice_description' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'invoice_recipients' => (object) array(
        'type' => 'array of string',
        'null' => true
    ),
    'params' => (object) array(
        'type' => 'object',
        'null' => true
    )
);

$createUserRecurringOrderResponse = (object) array(
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
            'ro_id' => (object) array(
                'type' => 'int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function createUserRecurringOrder($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error);
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
		
		// Making sure this merchant is allowed to use recurring orders
		if (!$merchant['support_recurring_orders']) {
			$error = err(13);
		} else if (!$args->name) {
            $error = err(130);
        } elseif (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->duedate) || !checkdate(substr($args->duedate, 5, 2), substr($args->duedate, 8, 2), substr($args->duedate, 0, 4)) || str_replace('-', '', $args->duedate) < date('Ymd')) {
            $error = err(137);
        } else if ($args->interval != 'monthly' && $args->interval != 'yearly') {
            $error = err(132);
        } elseif (!preg_match('/^\-?\d{1,11}(\.\d{1,2})?$/', $args->amount) || $args->amount == 0) {
            $error = err(138);
        } elseif ($args->currency != 'ILS' && $args->currency != 'USD' && $args->currency != 'EUR') {
            $error = err(139);
        } else if ($args->track2 && !$args->track2_sar_ksn) {
            // We only allow track2 if it's encrypted
            $error = err(103);
        } else if (!$args->track2 && !preg_match('/^\d{8,16}$/', $args->cc_number)) {
            $error = err(103);
        } else if (!$args->track2 && !preg_match('/^((0[1-9])|(1[0-2]))\d{2}$/', $args->cc_exp)) {
            $error = err(104);
        } elseif ($params && (!is_array($args->params) || count($args->params) > 10)) {
            $error = err(60);
        }
        
        if (!$error) {
            if ($args->params) {
                $params = (array) $args->params;
                foreach ($params as $name => $value) {
                    if (strlen($name) > 25) {
                        $error = err(61);
                    } elseif (strlen($value) > 500) {
                        $error = err(62);
                    }
                }
            }
        }
        
        if (!$error) {
            if ($args->track2_sar_ksn && $args->track2) {
                $request = array(
                    'track2_sar_ksn' => $args->track2_sar_ksn,
                    'track2' => $args->track2,
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
                    $args->track2 = $response->data->track2;
                } else {
                    $error = err(201);
                }
            }
    
            if (!$error) {
                if ($args->track2) {
                    if (preg_match('/\d{17}=0(\d{8})\d{5}(\d{2})(\d{2})0/', $args->track2, $matches)) {
                        $args->cc_number = $matches[1];
                        $args->cc_exp = $matches[3] . $matches[2];
                    } else {
                        $args->cc_number = preg_replace('/^(\d{9,19})\D.*$/', '$1', $args->track2);
                        $args->cc_exp = preg_replace('/^\d{9,19}\D(\d{2})(\d{2}).*$/', '$2$1', $args->track2);
                    }
                }
                
                // Transmit a J5 transaction to test the credit card data.
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
                    $CreditGuardWSDL = new SoapClient($host);
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
                                    <terminalNumber>' . substr($merchant['shva_transactions_merchant_number'], 0, 7) . '</terminalNumber>
                                    <cardNo>' . $args->cc_number . '</cardNo>
                                    <cardExpiration>' . $args->cc_exp . '</cardExpiration>
                                    <transactionType>Debit</transactionType>
                                    <creditType>RegularCredit</creditType>
                                    <currency>'.$args->currency.'</currency>
                                    <transactionCode>Phone</transactionCode>
                                    <total>100</total>
                                    <validation>Verify</validation>
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
                                <terminalNumber>' . substr($merchant['shva_transactions_merchant_number'], 0, 7) . '</terminalNumber>
                                <track2>' . ($args->track2 ? $args->track2 : false) . '</track2>
                                <cardNo>' . $args->cc_number . '</cardNo>
                                <cardExpiration>' . $args->cc_exp . '</cardExpiration>
                                <id>' . ($args->cc_holder_id_number ?: false) . '</id>
                                <cvv>' . ($args->credit_data->cc_cvv2 ?: false) . '</cvv>
                                <transactionType>Debit</transactionType>
                                <creditType>RegularCredit</creditType>
                                <currency>'.$args->currency.'</currency>
                                <transactionCode>Phone</transactionCode>
                                <total>100</total>
                                <validation>Verify</validation>
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
                    $curl_error = curl_error($CR);
                    curl_close($CR);
                        
                    //echo '<pre>Request: '; print_r($request); echo '</pre>';
                    //echo '<pre>Response: '; print_r($host); echo '</pre>';
                    //echo '<pre>Response: '; print_r($response); echo '</pre>';
                    //echo '<pre>Error: '; print_r($error); echo '</pre>';
                    //exit;
                    
                    if (preg_match('/<intOt>(.+)<\/intOt>/U', $response, $match)) {
                        $ResultRecord = $match[1];
                    }

                    if ($ResultRecord) {
                        if (substr($ResultRecord, 0, 3) != '000') {
                            $error = err(500 + substr($ResultRecord, 0, 3));
                        }

                        preg_match('/<cardId>(.*)<\/cardId>/U', $response, $match);
                        $credit_guard_token = $match[1];
                    
                    } else {
                        preg_match('/<result>(.+)<\/result>.*<userMessage>(.*)<\/userMessage>/U', $response, $match);
                        $error = err(200, array($match[1], $match[2]));
                    }
                } catch (Exception $e) {
                    $error = err(199);
                }
            }
            
            if (!$error) {
                
                $cc_data = $credit_guard_token;
                
                if (mysql_query("INSERT INTO recurring_orders (merchant_id, name, created, duedate, repetitions, `interval`, cc_last4, cc_exp, cc_type, cc_data, amount, currency, invoice_customer_name, invoice_description, invoice_recipients) VALUES ('$merchant[merchant_id]', '" . mysql_real_escape_string($args->name) . "', NOW(), '" . mysql_real_escape_string($args->duedate) . "', " . SQLNULL(mysql_real_escape_string($args->repetitions > 0 ? $args->repetitions : null)) . ", '" . mysql_real_escape_string($args->interval) . "', '" . mysql_real_escape_string(substr($args->cc_number, -4)) . "', '" . mysql_real_escape_string($args->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($args->cc_number))) . ", '" . mysql_real_escape_string($cc_data) . "', '" . mysql_real_escape_string($args->amount) . "', '" . mysql_real_escape_string($args->currency) . "', " . SQLNULL(mysql_real_escape_string($args->invoice_customer_name)) . ", " . SQLNULL(mysql_real_escape_string($args->invoice_description)) . ", " . SQLNULL(mysql_real_escape_string(@implode(' ', $args->invoice_recipients))) . ")")) {
                    $ro_id = mysql_insert_id();
                    
                    if ($args->params) {
                        $params = (array) $args->params;
                        foreach ($params as $name => $value) {
                            if ($value) {
                                if (!mysql_query("INSERT INTO recurring_orders_params (ro_id, name, value) VALUES ('$ro_id', '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($value) . "')")) {
                                    $error = err(5);
                                }
                            }
                        }
                    }
                } else {
                    $error = err(5);
                }
            }
        }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => $error ?: null,
        'data' => $error ? null : (object) array(
            'ro_id' => (int) $ro_id
        )
    );
}

?>
