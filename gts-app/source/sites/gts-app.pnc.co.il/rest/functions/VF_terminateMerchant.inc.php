<?php

$VF_terminateMerchantDescription = 'Terminate an existing VeriFone merchant.';

$VF_terminateMerchantRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => false
    ));

$VF_terminateMerchantResponse = (object) array(
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

function VF_terminateMerchant($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }

    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    // Validate merchant-id.
    
    if (!preg_match('/^\d+$/', $args->id)) {
        return new ErrObj('merchant-id-invalid');
    }
    if (!@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('merchant-id-not-found');
    }
    
    mysql_query("UPDATE merchants SET `terminated` = '1' WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
    
    mysql_query("INSERT INTO merchants_vf_log (ip_address, merchant_id, action) VALUES ('" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . mysql_real_escape_string($args->id) . "', 'terminated')");
    
    return (object) array(
        'result' => 'OKAY'
    );
}

?>
