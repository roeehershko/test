<?php

$updateTransactionDescription = 'Overwrite existing transaction params with the ones passed to the function.';

$updateTransactionRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'signature_data' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'params' => (object) array(
        'type' => 'object',
        'null' => true
    )
);

$updateTransactionResponse = (object) array(
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
            'signature_link' => (object) array(
                'type' => 'string',
                'null' => true
            ),
        ),
        'null' => true
    )
);

function updateTransaction($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    }
    $merchant = $merchants[0];
    if (!$merchant) {
        $error = 25;
    } else if (!preg_match('/^\d+$/', $args->trans_id)) {
        $error = 30;
    } else if (!($transactionType = @mysql_result(mysql_query("SELECT type FROM trans WHERE merchant_id = '$merchant[merchant_id]' AND trans_id = '" . mysql_real_escape_string($args->trans_id) . "'"), 0))) {
        $error = 30;
    } else if ($args->signature_data && $transactionType != 'credit') {
        $error = 33;
    } else if (!$args->signature_data && !$args->params) {
        $error = 34;
    } else {
        if ($args->params) {
            $params = (array) $args->params;
            foreach ($params as $name => $value) {
                if (strlen($name) > 25) {
                    $error = 61;
                } elseif (strlen($value) > 500) {
                    $error = 62;
                }
            }
        }
        
        if (!$error) {
            $data = (object) array();
            
            if ($args->signature_data) {
                $args->signature_data = str_replace(' ', '+', $args->signature_data);
                mysql_query("REPLACE INTO trans_signatures (trans_id, signature) VALUES ('" . mysql_real_escape_string($args->trans_id) . "', '" . mysql_real_escape_string($args->signature_data) . "')");
                
                $data->signature_link = 'https://'.constant('GTS_APP_HOST').'/signatures/' . authSignature($args->trans_id) . '/' . $args->trans_id . '.png';
            }
            
            if ($args->params) {
                $params = (array) $args->params;
                foreach ($params as $name => $value) {
                    if ($value) {
                        if (!@mysql_result(mysql_query("SELECT 1 FROM trans_params WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND private = '1' AND name = '" . mysql_real_escape_string($name) . "'"), 0)) {
                            mysql_query("REPLACE INTO trans_params (trans_id, private, name, value) VALUES ('" . mysql_real_escape_string($args->trans_id) . "', '0', '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($value) . "')");
                        }
                    } else {
                        mysql_query("DELETE FROM trans_params WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "' AND name = '" . mysql_real_escape_string($name) . "'");
                    }
                }
            }
        }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $data ?: null
    );
}

?>