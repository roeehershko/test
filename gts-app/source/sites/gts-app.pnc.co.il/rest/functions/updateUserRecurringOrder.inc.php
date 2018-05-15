<?php

$updateUserRecurringOrderDescription = 'Overwrite existing recurring-order attributes and params.';

$updateUserRecurringOrderRequest = (object) array(
    'ro_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'merchant_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'name' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'duedate' => (object) array(
        'type' => 'string',
        'value' => '{YYYY-MM-DD}',
        'null' => true
    ),
    'repetitions' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'interval' => (object) array(
        'type' => 'string',
        'value' => array('monthly', 'yearly'),
        'null' => true
    ),
    'amount' => (object) array(
        'type' => 'decimal',
        'null' => true
    ),
    'currency' => (object) array(
        'type' => 'string',
        'value' => array('ILS', 'USD', 'EUR'),
        'null' => true
    ),
    'cc_exp' => (object) array(
        'type' => 'string',
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

$updateUserRecurringOrderResponse = (object) array(
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

function updateUserRecurringOrder($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error);
    } else if (!preg_match('/^\d+$/', $args->ro_id)) {
        $error = err(80);
    //} else if (!($transactionType = @mysql_result(mysql_query("SELECT 1 FROM recurring_orders WHERE merchant_id = '$merchant[merchant_id]' AND `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'"), 0))) {
    } else if (!($transactionType = @mysql_result(mysql_query("SELECT 1 FROM recurring_orders WHERE `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'"), 0))) {
        $error = err(80);
    //} else if (!($recurringOrderDetails = @mysql_fetch_object(mysql_query("SELECT cc_data, cc_last4, cc_exp FROM recurring_orders WHERE merchant_id = '$merchant[merchant_id]' AND `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'")))) {
	} else if (!($recurringOrderDetails = @mysql_fetch_object(mysql_query("SELECT cc_data, cc_last4, cc_exp FROM recurring_orders WHERE `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'")))) {
	 	$error = err(80);
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
		} else {
	        /*
	        $cc_number = $recurringOrderDetails->cc_data;
	        
	        if ($cc_number && $args->cc_exp && $args->currency) {

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
	                            <cardNo>' . $cc_number . '</cardNo>
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
		    */
	
	        // Parameters
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
	        
	        if (!$error) {
	            if (isset($args->duedate) && (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->duedate) || !checkdate(substr($args->duedate, 5, 2), substr($args->duedate, 8, 2), substr($args->duedate, 0, 4)) || str_replace('-', '', $args->duedate) < date('Ymd'))) {
	                $error = err(137);
	            } else if (isset($args->interval) && ($args->interval != 'monthly' && $args->interval != 'yearly')) {
	                $error = err(132);
	            } elseif (isset($args->amount) && (!preg_match('/^\-?\d{1,11}(\.\d{1,2})?$/', $args->amount) || $args->amount == 0)) {
	                $error = err(138);
	            } elseif (isset($args->currency) && ($args->currency != 'ILS' && $args->currency != 'USD' && $args->currency != 'EUR')) {
	                $error = err(139);
	            } else if (isset($args->cc_exp) && !preg_match('/^((0[1-9])|(1[0-2]))\d{2}$/', $args->cc_exp)) {
	                $error = err(104);
	            } else {
	                $SQL_SET = array();
	                
	                if (isset($args->name)) {
	                    $SQL_SET[] = "name = '" . mysql_real_escape_string($args->name) . "'";
	                }
	                if (isset($args->duedate)) {
	                    $SQL_SET[] = "duedate = '" . mysql_real_escape_string($args->duedate) . "'";
	                }
	                if (isset($args->repetitions)) {
	                    $SQL_SET[] = "repetitions = " . SQLNULL(mysql_real_escape_string($args->repetitions > 0 ? $args->repetitions : null));
	                }
	                if (isset($args->interval)) {
	                    $SQL_SET[] = "`interval` = '" . mysql_real_escape_string($args->interval) . "'";
	                }
	                if (isset($args->amount)) {
	                    $SQL_SET[] = "amount = '" . mysql_real_escape_string($args->amount) . "'";
	                }
	                if (isset($args->currency)) {
	                    $SQL_SET[] = "currency = '" . mysql_real_escape_string($args->currency) . "'";
	                }
	                if (isset($args->cc_exp)) {
	                    $SQL_SET[] = "cc_exp = '" . mysql_real_escape_string($args->cc_exp) . "'";
	                }
	                if (isset($args->invoice_customer_name)) {
	                    $SQL_SET[] = "invoice_customer_name = " . SQLNULL(mysql_real_escape_string($args->invoice_customer_name));
	                }
	                if (isset($args->invoice_description)) {
	                    $SQL_SET[] = "invoice_description = " . SQLNULL(mysql_real_escape_string($args->invoice_description));
	                }
	                if (isset($args->invoice_recipients)) {
	                    $SQL_SET[] = "invoice_recipients = " . SQLNULL(mysql_real_escape_string(implode(' ', $args->invoice_recipients)));
	                }
	                
	                if ($SQL_SET) {
	                    mysql_query("UPDATE recurring_orders SET " . implode(', ', $SQL_SET) . " WHERE ro_id = '" . mysql_real_escape_string($args->ro_id) . "'");
	                }
	                
	                if ($args->params) {
	                    $params = (array) $args->params;
	                    foreach ($params as $name => $value) {
	                        if ($value) {
	                            if (!@mysql_result(mysql_query("SELECT 1 FROM recurring_orders_params WHERE ro_id = '" . mysql_real_escape_string($args->ro_id) . "' AND name = '" . mysql_real_escape_string($name) . "'"), 0)) {
	                                mysql_query("REPLACE INTO recurring_orders_params (ro_id, name, value) VALUES ('" . mysql_real_escape_string($args->ro_id) . "', '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($value) . "')");
	                            }
	                        } else {
	                            mysql_query("DELETE FROM recurring_orders_params WHERE ro_id = '" . mysql_real_escape_string($args->ro_id) . "' AND name = '" . mysql_real_escape_string($name) . "'");
	                        }
	                    }
	                }
	            }
	        }
	    }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => $error ?: null
    );
}

?>