<?php

$obtainUserRecurringOrderDetailsRequest = (object) array(
    'ro_id' => (object) array(
        'type' => 'int',
        'null' => false
    )
);

$obtainUserRecurringOrderDetailsResponse = (object) array(
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
            'ro_id' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'name' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'created' => (object) array(
                'type' => 'string',
                'value' => '{YYYY-MM-DD HH:MM:SS}',
                'null' => false
            ),
            'duedate' => (object) array(
                'type' => 'string',
                'value' => '{YYYY-MM-DD}',
                'null' => false
            ),
            'repetitions' => (object) array(
                'type' => 'int',
                'null' => true
            ),
            'interval' => (object) array(
                'type' => 'string',
                'value' => array('monthly', 'yearly'),
                'null' => false
            ),
            'cc_last4' => (object) array(
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
            'amount' => (object) array(
                'type' => 'decimal',
                'null' => false
            ),
            'currency' => (object) array(
                'type' => 'string',
                'value' => array('ILS', 'USD', 'EUR'),
                'null' => false
            ),
            'invoice_customer_name' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'invoice_description' => (object) array(
                'type' => 'string',
                'null' => true
            ),
            'invoice_recipients' => (object) array(
                'type' => 'array of string',
                'null' => true
            ),
            'params' => (object) array(
                'type' => 'object',
                'null' => true
            ),
            'transactions' => (object) array(
                'type' => 'array of int',
                'null' => true
            ),
            'obtainUserTransactionDetails' => (object) array(
		        'type' => 'array of string',
		        'value' => array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params'),
		        'null' => true
		    ),
		    'exportTransactions' => (object) array(
		        'type' => 'boolean',
		        'null' => true
		    ),
		    'exportInvoices' => (object) array(
		        'type' => 'array of the recipients emails',
		        'null' => true
		    ),
        ),
        'null' => true
    )
);

function obtainUserRecurringOrderDetails($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!preg_match('/^\d+$/', $args->ro_id)) {
        $error = 80;
    //} else if (!($recurringOrderDetails = @mysql_fetch_object(mysql_query("SELECT ro_id, merchant_id, name, created, duedate, repetitions, `interval`, cc_last4, cc_exp, cc_type, amount, currency, invoice_customer_name, invoice_description, invoice_recipients FROM recurring_orders WHERE merchant_id = '$merchant[merchant_id]' AND `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'")))) {
    } else if (!($recurringOrderDetails = @mysql_fetch_object(mysql_query("SELECT ro_id, merchant_id, name, created, duedate, repetitions, `interval`, cc_last4, cc_exp, cc_type, amount, currency, invoice_customer_name, invoice_description, invoice_recipients FROM recurring_orders WHERE `terminated` = '0' AND ro_id = '" . mysql_real_escape_string($args->ro_id) . "'")))) {
        $error = 80;
    } else {
        $recurringOrderDetails->params = (object) array();
        $sql_query = mysql_query("SELECT name, value FROM recurring_orders_params WHERE ro_id = '" . $recurringOrderDetails->ro_id . "'");
        while ($sql = @mysql_fetch_object($sql_query)) {
            $recurringOrderDetails->params->{$sql->name} = $sql->value;
        }
    }
    
    if ($recurringOrderDetails->invoice_recipients) {
        if (preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $recurringOrderDetails->invoice_recipients, $matches)) {
            $invoice_recipients = $matches[0];
        }
    }
    
    $transactions = array();
    $sql_query = mysql_query("SELECT trans_id FROM recurring_orders_transactions WHERE ro_id = '" . $recurringOrderDetails->ro_id . "' ORDER BY trans_id ASC");
    while ($sql = mysql_fetch_object($sql_query)) {
        $transactions[] = $sql->trans_id;
    }
    
    if ($args->obtainUserTransactionDetails) {
        include_once 'functions/obtainUserTransactionDetails.inc.php';
        if (!empty($transactions)) {
	        foreach ($transactions as &$transaction) {
		            $transaction = obtainUserTransactionDetails((object) array(
		            'username' => $args->username,
		            'password' => $args->password,
		            'trans_id' => $transaction,
		            'include' => $args->obtainUserTransactionDetails
		        ));
			}
        }
    }
    
    if (!$error) {
        $data = (object) array();
        $data->ro_id = (int) $recurringOrderDetails->ro_id;
        $data->merchant_id = (int) $recurringOrderDetails->merchant_id;
        $data->name = $recurringOrderDetails->name;
        $data->created = $recurringOrderDetails->created;
        $data->duedate = $recurringOrderDetails->duedate;
        $data->repetitions = $recurringOrderDetails->repetitions ?: null;
        $data->interval = $recurringOrderDetails->interval;
        $data->cc_last4 = $recurringOrderDetails->cc_last4;
        $data->cc_exp = $recurringOrderDetails->cc_exp;
        $data->cc_type = $recurringOrderDetails->cc_type ?: null;
        $data->amount = $recurringOrderDetails->amount;
        $data->currency = $recurringOrderDetails->currency;
        $data->invoice_customer_name = $recurringOrderDetails->invoice_customer_name;
        $data->invoice_description = $recurringOrderDetails->invoice_description;
        $data->invoice_recipients = $invoice_recipients ?: null;
        $data->params = $recurringOrderDetails->params ?: null;
        $data->transactions = $transactions;
    }
    
    if ($transactions && $args->exportTransactions) {
    	exportUserTransactions($transactions);
    	exit;
    } else if ($transactions && $args->exportInvoices) {
	    if (exportUserInvoices($merchants, $args, $transactions)) {
			return (object) array(
		        'result' => 'OKAY',
		        'error' => null,
		    );    
	    } else {
		    return (object) array(
		        'result' => 'FAIL',
		        'error' => err(200),
		    );
	    }
    } else {
	    return (object) array(
	        'result' => !$error ? 'OKAY' : 'FAIL',
	        'error' => err($error) ?: null,
	        'data' => $error ? null : $data
	    );
	}
}

?>