<?php

// Changes the password of a USER or MERCHANT; also changes password at SHVA (to a salted hash of the new password).

$resetUserPasswordRequest = (object) array(
    'password_new' => (object) array(
        'type' => 'string',
        'null' => false
    )
);

$resetUserPasswordResponse = (object) array(
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

function resetUserPassword($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
    	
    	if (strlen($args->password_new) < 7 || !preg_match('/[a-z]/i', $args->password_new) || !preg_match('/[0-9]/', $args->password_new)) {
            $error = 17;
        }
    	
    	if ($merchants[1] || strtolower($args->username) != strtolower($merchants[0]['username'])) {
	    	// This is a USER
	    	// If $merchants is an array with more than 1 merchant - then it's a user for sure	
	    	// A USER will never have a username of one of the merchants
	    	mysql_query("UPDATE users SET password = '".mysql_real_escape_string(hashGTSPass($args->password_new))."', must_change_password = 1 WHERE username = '".mysql_real_escape_string($args->username)."'");
    	
			// Obtain User ID
			$userDetails = @mysql_fetch_object(mysql_query("SELECT user_id FROM users WHERE username = '".mysql_real_escape_string($args->username)."'"));
			
			// Deleting ALL previous attemps to create an access token
			if ($userDetails->user_id) {
				mysql_query("DELETE FROM users_access_tokens WHERE user_id = '".mysql_real_escape_string($userDetails->user_id)."'");
			}

    	} else if (strtolower($args->username) == strtolower($merchants[0]['username'])) {
	    	// Otherwise - we need to check if it's a user or merchant	
	    	// So we know this is a MERCHANT
	    	$merchant = $merchants[0];
	    	if ($merchant['merchant_id'] == '1' || $merchant['merchant_id'] == '2') {
	            $error = 18;
	        } elseif (@mysql_result(mysql_query("SELECT 1 FROM merchants_passwords_log WHERE merchant_id = '$merchant[merchant_id]' AND password = '".mysql_real_escape_string(hashGTSPass($args->password_new))."'"), 0)) {
	            $error = 19;
	        } elseif ($merchant['processor'] == 'shva') {
	            // 2017-04-09 - We no longer use the SHVA Password hashing mechanism
                /*
                $client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
	            $return = $client->ChangePassword((object) array(
	                'MerchantNumber' => $merchant['shva_transactions_merchant_number'],
	                'UserName' => $merchant['shva_transactions_username'],
	                'Password' => $merchant['shva_transactions_password'],
	                'NewPassword' => hashSHVAPass($merchant['shva_transactions_username'], hashGTSPass($args->password_new))
	            ));
	            
	            if ($return->ChangePasswordResult == 250) {
	                $error = 16;
	            } elseif ($return->ChangePasswordResult != 0) {
	                $error = 500 + $return->ChangePasswordResult;
	            }
	            
	            if (!$error && $merchant['shva_standing_orders_username'] && $merchant['shva_transactions_username'] != $merchant['shva_standing_orders_username']) {
	                $client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
	                $return = $client->ChangePassword((object) array(
	                    'MerchantNumber' => $merchant['shva_standing_orders_merchant_number'],
	                    'UserName' => $merchant['shva_standing_orders_username'],
	                    'Password' => $merchant['shva_standing_orders_password'],
	                    'NewPassword' => hashSHVAPass($merchant['shva_standing_orders_username'], hashGTSPass($args->password_new))
	                ));
	                
	                if ($return->ChangePasswordResult == 250) {
	                    $error = 16;
	                } elseif ($return->ChangePasswordResult != 0) {
	                    $error = 500 + $return->ChangePasswordResult;
	                }
	            }
                */
	        }
	        if (!$error) {
	            mysql_query("UPDATE merchants SET password = '".mysql_real_escape_string(hashGTSPass($args->password_new))."', must_change_password = 1 WHERE merchant_id = '$merchant[merchant_id]'");
	            mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('$merchant[merchant_id]', '".mysql_real_escape_string(hashGTSPass($args->password_new))."', NOW())");
	        
				// Deleting ALL previous attemps to create an access token
				mysql_query("DELETE FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]'");
	        }
    	}
    	
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>