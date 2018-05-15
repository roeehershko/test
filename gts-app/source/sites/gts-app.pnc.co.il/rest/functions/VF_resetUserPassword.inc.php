<?php

// Resets the password of a user or merchant if the username and company-id match.

$VF_resetUserPasswordRequest = (object) array(
    'username' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'company_id' => (object) array(
        'type' => 'string',
        'null' => false
    )
);

$VF_resetUserPasswordResponse = (object) array(
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
            'user_id' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'merchant_id' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'password' => (object) array(
                'type' => 'string',
                'null' => false
            )
        ),
        'null' => true
    )
);

function VF_resetUserPassword($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    $args_tmp = json_decode(file_get_contents("php://input"));
    $args->username = $args_tmp->username;
    
    // Validating the username / company_id combo
    
    $company_id_without_leading_zero = preg_replace('/^0(.+)/', '$1', $args->company_id);

    if (isset($args->username) && isset($args->company_id) && (
    	(@mysql_result(mysql_query("SELECT 1 FROM users WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0))
        || (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0))
    )) {
        // A user / merchant exists with this username and company-id combination
    } else if (isset($args->username) && isset($args->company_id) && (
        (@mysql_result(mysql_query("SELECT 1 FROM users WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($company_id_without_leading_zero) . "'"), 0))
        || (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($company_id_without_leading_zero) . "'"), 0))
    )) {
        // A user / merchant exists with this username and company-id combination without leading zero
        $args->company_id = $company_id_without_leading_zero;
    } else {
        return new ErrObj('invalid-username');
    }
    
    // Generating a new password
    //$new_password = substr(uniqid(), 0, 7);
    $chars = 'abcdefghikmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXTZ0123456789';
    $new_password = substr(str_shuffle($chars), 0, 7);
    
    // Changing the password
    if (strtolower($args->username) != 'noigy' && strtolower($args->username) != 'noigx') {
	    if ($user_id = @mysql_result(mysql_query("SELECT user_id FROM users WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0)) {
		
		    mysql_query("UPDATE users SET password = '".mysql_real_escape_string(hashGTSPass($new_password))."', must_change_password = 1 WHERE username = '".mysql_real_escape_string($args->username)."'");    
	    
			// Deleting ALL previous attemps to create an access token
			mysql_query("DELETE FROM users_access_tokens WHERE user_id = '$user_id'");
	    
	    } else if ($merchant_id = @mysql_result(mysql_query("SELECT merchant_id FROM merchants WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0)) {
		
			mysql_query("UPDATE merchants SET password = '".mysql_real_escape_string(hashGTSPass($new_password))."', must_change_password = 1 WHERE `terminated` != 1 AND username = '".mysql_real_escape_string($args->username)."'");    
	    
			// Deleting ALL previous attemps to create an access token
			mysql_query("DELETE FROM merchants_access_tokens WHERE merchant_id = '$merchant_id'");
	    
        } else {
            
            return new ErrObj('invalid-username');
            
        }
	} else {
		$user_id = @mysql_result(mysql_query("SELECT user_id FROM users WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0);
		$merchant_id = @mysql_result(mysql_query("SELECT merchant_id FROM merchants WHERE `terminated` != 1 AND username = '" . mysql_real_escape_string($args->username) . "' AND company_id = '" . mysql_real_escape_string($args->company_id) . "'"), 0);
	
		// Deleting ALL previous attemps to create an access token
		mysql_query("DELETE FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]'");
	}
        
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'data' => (object) array(
            'user_id' => $user_id,
            'merchant_id' => $merchant_id,
            'password' => $new_password
        )
    );
}

?>