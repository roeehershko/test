<?php

$obtainUserDetailsRequest = (object) array(
);

$obtainUserDetailsResponse = (object) array(
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
            'username' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'name' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'type' => (object) array(
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
            'email' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'mobile' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'created' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'must_change_password' => (object) array(
                'type' => 'boolean',
                'null' => true
            ),
            'merchants' => (object) array(
                'type' => 'array or objects',
                'value' => (object) array(
                	'id' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'username' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'number' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'company' => (object) array(
                        'type' => 'object',
                        'value' =>  (object) array(
                            'name' => (object) array(
                                'type' => 'string',
                                'null' => false
                            ),
                            'number' => (object) array(
                                'type' => 'string',
                                'null' => false
                            ),
                            'email' => (object) array(
                                'type' => 'string',
                                'null' => false
                            )
                        ),
                        'null' => false
                    ),
                    'sender' => (object) array(
                        'type' => 'object',
                        'value' => (object) array(
                            'name' => (object) array(
                                'type' => 'string',
                                'null' => false
                            ),
                            'email' => (object) array(
                                'type' => 'string',
                                'null' => false
                            )
                        ),
                        'null' => false
                    ),
                    'voucher_language' => (object) array(
                        'type' => 'string',
                        'value' => array('en', 'he'),
                        'null' => false
                    ),
                    'invoices' => (object) array(
                        'type' => 'object',
                        'value' => (object) array(
                            'charge_starting_number' => (object) array(
                                'type' => 'int',
                                'null' => false
                            ),
                            'refund_starting_number' => (object) array(
                                'type' => 'int',
                                'null' => false
                            ),
                            'template' => (object) array(
                                'type' => 'string',
                                'null' => false
                            )
                        ),
                        'null' => true
                    ),
                    'access_token_ttl' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'allow_duplicated_transactions' => (object) array(
                        'type' => 'boolean',
                        'null' => false
                    ),
                    'invoice_support' => (object) array(
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
                    'created' => (object) array(
                        'type' => 'int',
                        'null' => true
                    ),
                    'must_change_password' => (object) array(
                        'type' => 'boolean',
                        'null' => true
                    ),
                    'params' => (object) array(
                        'type' => 'object',
                        'null' => true
                    ),
                    'stats' => (object) array(
                        'type' => 'object',
                        'value' => (object) array(
                            'trans_credit_num' => (object) array(
                                'type' => 'int',
                                'null' => true
                            ),
                            'trans_check_num' => (object) array(
                                'type' => 'int',
                                'null' => true
                            ),
                            'trans_cash_num' => (object) array(
                                'type' => 'int',
                                'null' => true
                            ),
                            'card_readers' => (object) array(
                                'type' => 'array of string',
                                'null' => true
                            )
                        ),
                        'null' => true
                    )
                ),
                'null' => true
            ),
            'note' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'params' => (object) array(
                'type' => 'object',
                'null' => true
            ),
        ),
        'null' => true
    )
);

function obtainUserDetails($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
        $userDetails = @mysql_fetch_object(mysql_query("SELECT user_id, username, type, name, company, company_id, email, mobile, UNIX_TIMESTAMP(created) AS created, must_change_password, note, params FROM users WHERE username = '$args->username'"));
     	
        if (!empty($merchants)) {
        	foreach ($merchants as $merchant) {
        		$sql_query = "SELECT merchant_id AS id, username, shva_transactions_merchant_number AS number, company_name, company_id, company_email, sender_name, sender_email, voucher_language, invoices_template, invoices_charge_starting_number, invoices_refund_starting_number, access_token_ttl, support_recurring_orders, support_refund_trans, support_cancel_trans, support_new_trans, allow_duplicated_transactions, UNIX_TIMESTAMP(created) AS created, must_change_password, note, params FROM merchants WHERE merchant_id = '$merchant[merchant_id]'";
	            $merchantItem = mysql_fetch_object(mysql_query($sql_query));
		        $merchantItem->params = unserialize($merchantItem->params);
		        $merchantItem->params->note = $merchantItem->note;
		        
		        $merchants_details[] = (object) array(
		            'id' => (int) $merchantItem->id,
		            'username' => $merchantItem->username,
		            'number' => $merchantItem->number,
		            'company' => (object) array(
		                'name' => $merchantItem->company_name ?: null,
		                'number' => $merchantItem->company_id ?: null,
		                'email' => $merchantItem->company_email
		            ),
		            'sender' => (object) array(
		                'name' => $merchantItem->sender_name,
		                'email' => $merchantItem->sender_email
		            ),
		            'invoice_support' => $merchantItem->invoices_template ? true : false,
		            'invoices' => $merchantItem->invoices_template ? (object) array(
		                'charge_starting_number' => (int) $merchantItem->invoices_charge_starting_number,
		                'refund_starting_number' => (int) $merchantItem->invoices_refund_starting_number,
		                'template' => $merchantItem->invoices_template
		            ) : null,
		            'access_token_ttl' => (int) $merchantItem->access_token_ttl,
		            'voucher_language' => $merchantItem->voucher_language,
		            'allow_duplicated_transactions' => (bool) $merchantItem->allow_duplicated_transactions,
		            'support_recurring_orders' => (bool) $merchantItem->support_recurring_orders,
		            'support_refund_trans' => (bool) $merchantItem->support_refund_trans,
		            'support_cancel_trans' => (int) $merchantItem->support_cancel_trans,
		            'support_new_trans' => (bool) $merchantItem->support_new_trans,
		            'created' => (int) $merchantItem->created,
		            'must_change_password' => (boolean) $merchantItem->must_change_password,
		            'params' => $merchantItem->params ?: null,
		        );
			}
        }
     	   
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'id' => $userDetails->user_id ? (int)$userDetails->user_id : null,
            'username' => $userDetails->username,
            'type' => $userDetails->type,
            'name' => $userDetails->name,
            'company' => $userDetails->company,
            'company_id' => $userDetails->company_id,
            'email' => $userDetails->email,
            'mobile' => $userDetails->mobile,
            'merchants' => $merchants_details,
            'note' => $userDetails->note,
            'created' => $userDetails->created ? (int)$userDetails->created : null,
            'must_change_password' => $userDetails->must_change_password ? (boolean)$userDetails->must_change_password : null,
            'params' => $userDetails->params ?: null,
        )
    );
}

?>