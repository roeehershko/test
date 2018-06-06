<?php

$VF_submitUserDescription = 'Create a new user or alter an existing one. To alter an existing user its \'id\' must be provided. The \'password\' must be provided only when creating a new user or updating the password of an existing user.';

$VF_submitUserRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'username' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'password' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'type' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'name' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'email' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'company' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'company_id' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'mobile' => (object) array(
        'type' => 'string',
        'null' => false
    ),
	'mobile' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'note' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'params' => (object) array(
        'type' => 'object',
        'null' => true
    )
);

$VF_submitUserResponse = (object) array(
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
            'id' => (object) array(
                'type' => 'int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function VF_submitUser($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    // Validate user-id (if sent then this is an update; otherwise it's a new user).
    
    if (isset($args->id) && !preg_match('/^\d+$/', $args->id)) {
        return new ErrObj('user-id-invalid');
    }
    if (isset($args->id) && !@mysql_result(mysql_query("SELECT 1 FROM users WHERE user_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('user-id-not-found');
    }
    
    // Validate username.
    
    $args_tmp = json_decode(file_get_contents("php://input"));
    $args->username = $args_tmp->username;
	
    if (!preg_match('/^[a-z0-9]{4,10}$/i', $args->username)) {
        return new ErrObj('username-invalid');
    }
    
    if (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('username-already-exists');
    }
    if (@mysql_result(mysql_query("SELECT 1 FROM users WHERE username = '" . mysql_real_escape_string($args->username) . "' AND user_id != '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('username-already-exists');
    }
    
    // Validate password.
    
    $args_tmp = json_decode(file_get_contents("php://input"));
    $args->password = $args_tmp->password;
    
    if (!isset($args->id) || $args->password) {
        if (strlen($args->password) < 7 || !preg_match('/[a-z]/i', $args->password) || !preg_match('/[0-9]/', $args->password)) {
            return new ErrObj('user-password-invalid');
        }
    }
        
    // Validate details.
    
    if (!preg_match('/^.{1,100}$/', $args->company)) {
        return new ErrObj('company-invalid');
    }
    
    if (!preg_match('/^.{1,10}$/', $args->company_id)) {
        return new ErrObj('company-id-invalid');
    }
    
    if (!preg_match('/^.{1,100}$/', $args->name)) {
        return new ErrObj('user-name-invalid');
    }
    
    if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $args->email)) {
        return new ErrObj('email-invalid');
    }
        
    // Validate params.
    
    if (isset($args->params) && !is_object($args->params)) {
        return new ErrObj('merchant-params-object-invalid');
    }
    
    if (isset($args->params) && $args->params->note) {
        $args->note = $args->params->note;
        $args->params->note = null;
    } else {
        $args->note = null;
    }
    
    if (isset($args->id)) {
        if (!$args->username && $args->password) {
	        // If no username exists - and only a password exists - we update ONLY the password
	        mysql_query("UPDATE users SET password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', must_change_password = 1 WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
			
			// Deleting ALL previous attemps to create an access token
			mysql_query("DELETE FROM users_access_tokens WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
			
	    } else {
        	mysql_query("UPDATE users SET username = '" . mysql_real_escape_string($args->username) . "', type = '" . mysql_real_escape_string($args->type) . "', name = '" . mysql_real_escape_string($args->name) . "', company = '" . mysql_real_escape_string($args->company) . "', company_id = '" . mysql_real_escape_string($args->company_id) . "', email = '" . mysql_real_escape_string($args->email) . "', mobile = '" . mysql_real_escape_string($args->mobile) . "', note = " . SQLNULL(mysql_real_escape_string($args->note)) . ", params = " . SQLNULL(mysql_real_escape_string($args->params ? serialize($args->params) : null)) . " WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
            if ($args->password) {
	            mysql_query("UPDATE users SET password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', must_change_password = 1 WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
	        
				// Deleting ALL previous attemps to create an access token
				mysql_query("DELETE FROM users_access_tokens WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
	        
	        }
	    }
                
        return (object) array(
            'result' => 'OKAY'
        );
    } else {
    	$sql_query = "INSERT INTO users (username, password, must_change_password, type, name, company, company_id, email, mobile, created, note, params) VALUES ('" . mysql_real_escape_string($args->username) . "', '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', 1, '" . mysql_real_escape_string($args->type) . "', '" . mysql_real_escape_string($args->name) . "', '" . mysql_real_escape_string($args->company) . "', '" . mysql_real_escape_string($args->company_id) . "', '" . mysql_real_escape_string($args->email) . "', '" . mysql_real_escape_string($args->mobile) . "', NOW(), " . SQLNULL(mysql_real_escape_string($args->note)) . ", " . SQLNULL(mysql_real_escape_string($args->params ? serialize($args->params) : null)) . ")";
    	mysql_query($sql_query);
        $user_id = mysql_insert_id();
        
        return (object) array(
            'result' => 'OKAY',
            'data' => (object) array(
                'id' => $user_id,
                //'debug' => $sql_query
            )
        );
    }
}

?>
