<?php

$destroyAccessTokenRequest = (object) array(
    'ip_address' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

$destroyAccessTokenResponse = (object) array(
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

function destroyAccessToken($args) {
    if (!$merchant = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!(@mysql_result(mysql_query("SELECT 1 FROM merchants_access_tokens WHERE access_token = '" . mysql_real_escape_string($args->password) . "' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND expiration >= NOW()"), 0))) {
        $error = 7;
    } else {
        mysql_query("UPDATE merchants_access_tokens SET expiration = NOW() WHERE access_token = '" . mysql_real_escape_string($args->password) . "' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND expiration >= NOW()");
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>