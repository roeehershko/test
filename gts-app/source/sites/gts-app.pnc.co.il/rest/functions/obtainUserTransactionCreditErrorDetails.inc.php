<?php

$obtainUserTransactionCreditErrorDetailsRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => false
    )
);

$obtainUserTransactionCreditErrorDetailsResponse = (object) array(
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
            ),
            'timestamp' => (object) array(
                'type' => 'string',
                'value' => '{YYYY-MM-DD HH:MM:SS}',
                'null' => false
            ),
            'amount' => (object) array(
                'type' => 'decimal',
                'null' => false
            ),
            'currency' => (object) array(
                'type' => 'string',
                'value' => array('ILS', 'USD', 'EUR'),
                'null' => false
            ),
            'cc_holder_name' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'cc_last_4' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'cc_exp' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'cc_type' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'credit_terms' => (object) array(
                'type' => 'string',
                'value' => array('regular', 'payments', 'payments-credit'),
                'null' => false
            ),
            'transaction_code' => (object) array(
                'type' => 'string',
                'value' => array('regular', 'phone'),
                'null' => false
            ),
            'j5' => (object) array(
                'type' => 'bool',
                'null' => false
            ),
            'payments_number' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'payments_first_amount' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'payments_standing_amount' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'authorization_number' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'error' => (object) array(
                'type' => 'string',
                'null' => false
            )
        ),
        'null' => true
    )
);

function obtainUserTransactionCreditErrorDetails($args) {
    
    if ($args->admin && $args->merchant_id && !$merchants = authenticateAdmin($args->merchant_id, $args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!$args->admin && !$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!preg_match('/^\d+$/', $args->id)) {
        $error = 25;
    //} else if (!($transactionCreditErrorDetails = @mysql_fetch_object(mysql_query("SELECT id, timestamp, amount, currency, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, error_code FROM trans_credit_errors WHERE merchant_id = '$merchant[merchant_id]' AND id = '" . mysql_real_escape_string($args->id) . "'")))) {
    } else if (!($transactionCreditErrorDetails = @mysql_fetch_object(mysql_query("SELECT id, merchant_id, timestamp, amount, currency, cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, j5, payments_number, payments_first_amount, payments_standing_amount, authorization_number, error_code FROM trans_credit_errors WHERE id = '" . mysql_real_escape_string($args->id) . "'")))) {
        $error = 25;
    }
    
    if ($args->merchant_id) {
		foreach ($merchants as $data) {
			if ($data['merchant_id'] == $args->merchant_id) {
				$merchant = $data;
			}
		}
	}
	if (!$merchant) {
		$merchant = $merchants[0];
	}

    // Making sure this transaction belongs to one of the user's authorized merchants
    $merchant_ids = false;
    foreach ($merchants as $merchant) {
        $merchant_ids[$merchant['merchant_id']] = true;
    }
    if (!$merchant_ids[$transactionCreditErrorDetails->merchant_id]) {
        $error = 25;
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'id' => (int) $transactionCreditErrorDetails->id,
            'merchant_id' => (int) $transactionCreditErrorDetails->merchant_id,
            'timestamp' => $transactionCreditErrorDetails->timestamp,
            'amount' => $transactionCreditErrorDetails->amount,
            'currency' => $transactionCreditErrorDetails->currency,
            'cc_holder_name' => $transactionCreditErrorDetails->cc_holder_name ?: null,
            'cc_last_4' => $transactionCreditErrorDetails->cc_last_4,
            'cc_exp' => $transactionCreditErrorDetails->cc_exp,
            'cc_type' => $transactionCreditErrorDetails->cc_type ?: null,
            'credit_terms' => $transactionCreditErrorDetails->credit_terms,
            'transaction_code' => $transactionCreditErrorDetails->transaction_code,
            'j5' => $transactionCreditErrorDetails->j5,
            'payments_number' => (int) $transactionCreditErrorDetails->payments_number ?: null,
            'payments_first_amount' => (int) $transactionCreditErrorDetails->payments_first_amount ?: null,
            'payments_standing_amount' => (int) $transactionCreditErrorDetails->payments_standing_amount ?: null,
            'authorization_number' => (int) $transactionCreditErrorDetails->authorization_number ?: null,
            'error' => err($transactionCreditErrorDetails->error_code),
        )
    );
}

?>