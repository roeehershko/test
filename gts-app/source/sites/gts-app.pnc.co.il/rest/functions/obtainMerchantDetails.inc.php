<?php

$obtainMerchantDetailsRequest = (object) array(
);

$obtainMerchantDetailsResponse = (object) array(
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
            'created' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'shva_transactions_merchant_number' => (object) array(
            	'type' => 'string',
            	'null' => true,
            ),
            'company_number' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'company_name' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'company_email' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'invoice_support' => (object) array(
                'type' => 'boolean',
                'null' => false
            ),
            'sender_name' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'sender_email' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'so_support' => (object) array(
                'type' => 'boolean',
                'null' => false
            ),
            'support_recurring_orders' => (object) array(
                'type' => 'boolean',
                'null' => false
            ),
            'support_refund_trans' => (object) array(
                'type' => 'boolean',
                'null' => false
            ),
            'support_cancel_trans' => (object) array(
                'type' => 'int',
                'value' => array('0 - No', '1 - Yes', '2 - Last Transaction Only'),
                'null' => false
            ),
            'support_new_trans' => (object) array(
                'type' => 'boolean',
                'null' => false
            ),
            'so_pending_trans_number' => (object) array(
                'type' => 'int',
                'null' => false
            ),
        ),
        'null' => true
    )
);

function obtainMerchantDetails($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
        $merchant = $merchants[0];
        $merchantDetails = @mysql_fetch_object(mysql_query("SELECT created, shva_transactions_merchant_number, company_id AS company_number, company_name, company_email, sender_name, sender_email, invoices_template, support_recurring_orders, support_standing_orders, support_refund_trans, support_cancel_trans, support_new_trans FROM merchants WHERE merchant_id = '$merchant[merchant_id]'"));
        
        if (!($merchantDetails->so_pending_trans_num = @mysql_result(mysql_query("SELECT SUM(trans_pending) FROM standing_orders WHERE merchant_id = '$merchant[merchant_id]' AND `terminated` = '0'"), 0))) {
            $merchantDetails->so_pending_trans_num = 0;
        }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'created' => $merchantDetails->created,
            'shva_transactions_merchant_number' => $merchantDetails->shva_transactions_merchant_number ?: null,
            'company_name' => $merchantDetails->company_name ?: null,
            'company_number' => $merchantDetails->company_number ? (int) $merchantDetails->company_number : null,
            'company_email' => $merchantDetails->company_email,
            'invoice_support' => $merchantDetails->invoices_template ? true : false,
            'sender_name' => $merchantDetails->sender_name ?: null,
            'sender_email' => $merchantDetails->sender_email ?: null,
            'so_support' => (bool) $merchantDetails->support_standing_orders,
            'support_refund_trans' => (bool) $merchantDetails->support_refund_trans,
            'support_cancel_trans' => (int) $merchantDetails->support_cancel_trans,
            'support_new_trans' => (bool) $merchantDetails->support_new_trans,
            'support_recurring_orders' => (bool) $merchantDetails->support_recurring_orders,
            'so_pending_trans_number' => (int) $merchantDetails->so_pending_trans_num,
        )
    );
}

?>