<?php

$VF_terminateUserDescription = 'Terminate an existing user.';

$VF_terminateUserRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => false
    ));

$VF_terminateUserResponse = (object) array(
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

function VF_terminateUser($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    // Validate user-id.
    
    if (!preg_match('/^\d+$/', $args->id)) {
        return new ErrObj('user-id-invalid');
    }
    if (!@mysql_result(mysql_query("SELECT 1 FROM users WHERE user_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('user-id-not-found');
    }
    
    mysql_query("UPDATE users SET `terminated` = '1' WHERE user_id = '" . mysql_real_escape_string($args->id) . "'");
    
    //mysql_query("INSERT INTO users_vf_log (ip_address, user_id, action) VALUES ('" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . mysql_real_escape_string($args->id) . "', 'terminated')");
    
    return (object) array(
        'result' => 'OKAY'
    );
}

?>
