<?php

$cancelUserRecurringOrderRequest = (object) array(
    'ro_id' => (object) array(
        'type' => 'int',
        'null' => false
    )
);

$cancelUserRecurringOrderResponse = (object) array(
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

function cancelUserRecurringOrder($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!preg_match('/^\d+$/', $args->ro_id)) {
        $error = 80;
    //} else if (!@mysql_result(mysql_query("SELECT 1 FROM recurring_orders WHERE merchant_id = '$merchant[merchant_id]' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "' AND `terminated` = '0'"), 0)) {
    } else if (!@mysql_result(mysql_query("SELECT 1 FROM recurring_orders WHERE ro_id = '" . mysql_real_escape_string($args->ro_id) . "' AND `terminated` = '0'"), 0)) {
        $error = 80;
    } else {
        mysql_query("UPDATE recurring_orders SET `terminated` = '1' WHERE ro_id = '" . mysql_real_escape_string($args->ro_id) . "'");
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>