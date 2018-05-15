<?php

$realizeTransactionRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'credit_data' => (object) array(
        'type' => 'object',
        'value' => (object) array(
            'track2' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'track2_sar_ksn' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_number' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_exp' => (object) array(
                'type' => 'string',
                'value' => '{MMYY}',
                'null' => true
            ),
            'cc_cvv2' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'authorization_number' => (object) array(
                'type' => 'int',
                'null' => false
            )
        ),
        'null' => false
    )
);

$realizeTransactionResponse = (object) array(
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
            'trans_id' => (object) array(
                'type' => 'int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function realizeTransaction($args) {
    if (!$merchant = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error);
    } else {
        // check that the transaction being realized is a pending j5 transaction with matching cc-data.
        // merge all relevant data from the existing j5 transaction and this one and attempt to submit;
        // if okay, update the params of both transactions and mark the j5 transaction as canceled (actually realized),
        // otherwise, return an error.
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => $error ?: null,
        'data' => $error ? null : (object) array(
            'trans_id' => (int) $trans_id
        )
    );
}

?>
