<?php

// generates a new Direct Access Password for a merchant

$generateDirectAccessPasswordRequest = (object) array(
    
);

$generateDirectAccessPasswordResponse = (object) array(
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
            'direct_access_password' => (object) array(
                'type' => 'string',
                'null' => false
            ),
        ),
        'null' => true
    )
);

function generateDirectAccessPassword($args) {
    
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error, false, $args->lang);
    } else {
    	
    	if ($merchants[1] || strtolower($args->username) != strtolower($merchants[0]['username'])) {
	    	// This is a USER
	    	$error = $authenticate__error;
        } else if (strtolower($args->username) == strtolower($merchants[0]['username'])) {
	    	
            $merchant = $merchants[0];
	    	
            // Generate the Direct Access password
            $direct_access_password = generateLongPassword();
            
            // Encrypt and save it
            mysql_query("UPDATE merchants SET direct_access_password = '" . aes_encrypt($direct_access_password) . "' WHERE merchant_id = '" . mysql_real_escape_string($merchant[merchant_id]) . "'");
            
    	}
        
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'direct_access_password' => $direct_access_password
        ),
    );
}

?>