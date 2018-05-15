<?php

$VF_associateMerchantToUserDescription = 'Associate a merchant to a user.';

$VF_associateMerchantToUserRequest = (object) array(
    'user_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'merchant_id' => (object) array(
        'type' => 'int',
        'null' => false
    )
);

$VF_associateMerchantToUserResponse = (object) array(
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

function VF_associateMerchantToUser($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    // Validate user
    
    if (isset($args->user_id) && !preg_match('/^\d+$/', $args->user_id)) {
        return new ErrObj('user-id-invalid');
    }
    if (isset($args->user_id) && !@mysql_result(mysql_query("SELECT 1 FROM users WHERE user_id = '" . mysql_real_escape_string($args->user_id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('user-id-not-found');
    }
    
    // Validate merchant
    
    if (isset($args->merchant_id) && !preg_match('/^\d+$/', $args->merchant_id)) {
        return new ErrObj('merchant-id-invalid');
    }
    if (isset($args->merchant_id) && !@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->merchant_id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('merchant-id-not-found');
    }
    
    if ($args->merchant_id && $args->user_id && !@mysql_result(mysql_query("SELECT 1 from merchants_users WHERE user_id = '" . mysql_real_escape_string($args->user_id) . "' AND merchant_id = '" . mysql_real_escape_string($args->merchant_id) . "'"), 0)) {
		// Associatet the merchant to a user
		$sql_query = "INSERT INTO merchants_users (user_id, merchant_id) VALUES ('" . mysql_real_escape_string($args->user_id) . "', '" . mysql_real_escape_string($args->merchant_id) . "')";
		mysql_query($sql_query);
    } else if ($args->merchant_id && !$args->user_id) {
	    // De-associate the merchant from ANY user
	    $sql_query = "DELETE FROM merchants_users WHERE merchant_id = '".mysql_real_escape_string($args->merchant_id)."'";
	    mysql_query($sql_query);
    }
    
    return (object) array(
        'result' => 'OKAY',
        'debug' => $sql_query,
    );
    
}

?>
