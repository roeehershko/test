<?php

$obtainUserTransactionsRequest = (object) array(
    'merchant_id' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'offset' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'limit' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'orderby' => (object) array(
        'type' => 'string',
        'value' => array('trans_id', 'timestamp', 'amount'),
        'null' => true
    ),
    'sorting' => (object) array(
        'type' => 'string',
        'value' => array('asc', 'desc'),
        'null' => true
    ),
    'limit_statuses' => (object) array(
        'type' => 'array',
        'value' => array('completed', 'pending'),
        'null' => true
    ),
    'limit_types' => (object) array(
        'type' => 'array',
        'value' => array('credit', 'check', 'cash'),
        'null' => true
    ),
    'date_start' => (object) array(
        'type' => 'string',
        'value' => '{YYYY-MM-DD}',
        'null' => true
    ),
    'date_end' => (object) array(
        'type' => 'string',
        'value' => '{YYYY-MM-DD}',
        'null' => true
    ),
    'search' => (object) array(
        'type' => 'string',
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
);

$obtainUserTransactionsResponse = (object) array(
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
            'total' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'offset' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'limit' => (object) array(
                'type' => 'int',
                'null' => false
            ),
            'transactions' => (object) array(
                'type' => 'array of int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function obtainUserTransactions($args) {
    
    if ($args->admin && $args->merchant_id && !$merchants = authenticateAdmin($args->merchant_id, $args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!$args->admin && !$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
        
        $FROM = array();
        $WHERE = array("1");
        $ORDERBY = preg_match('/^(trans_id|timestamp|amount)$/', $args->orderby) ? $args->orderby : "trans_id";
        $SORTING = preg_match('/^(asc|desc)$/', $args->sorting) ? $args->sorting : "DESC";
        
        if ($args->limit_statuses) {
            $WHERE[] = "status IN ('" . implode("','", @array_map('mysql_real_escape_string', $args->limit_statuses)) . "')";
        }
        if ($args->limit_types) {
            $WHERE[] = "type IN ('" . implode("','", @array_map('mysql_real_escape_string', $args->limit_types)) . "')";
        }
        if ($args->date_start && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->date_start)) {
            $WHERE[] = "DATE_FORMAT(timestamp, '%Y-%m-%d') >= '" . mysql_real_escape_string($args->date_start) . "'";
        }
        if ($args->date_end && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->date_end)) {
            $WHERE[] = "DATE_FORMAT(timestamp, '%Y-%m-%d') <= '" . mysql_real_escape_string($args->date_end) . "'";
        }
        
        if ($args->search) {
            $FROM[] = "LEFT JOIN trans_credit USING (trans_id)";
            $FROM[] = "LEFT JOIN trans_check USING (trans_id)";
            $FROM[] = "LEFT JOIN trans_invoices USING (trans_id)";
            $FROM[] = "LEFT JOIN trans_params ON trans.trans_id = trans_params.trans_id AND trans_params.private = '0' AND trans_params.name = 'note'";
            
            $WHERE[] = "(" . implode(" OR ", array(
                "trans.trans_id LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans.status LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans.type LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans.amount LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans.currency LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.cc_holder_name LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.cc_last_4 LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.cc_type LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.payments_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.voucher_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_credit.reference_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_check.check_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_check.bank_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_check.branch_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_check.account_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.customer_name LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.customer_number LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.address_street LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.address_city LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.address_zip LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.phone LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_invoices.description LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "trans_params.value LIKE '%" . mysql_real_escape_string($args->search) . "%'",
            )) . ")";
        }
        
        if (!empty($merchants)) {
	        foreach ($merchants as $merchant) {
		        //if (!$args->merchant_username || $args->merchant_username == $merchant['username']) {
		        if (!$args->merchant_id || $args->merchant_id == $merchant['merchant_id']) {
			    	$merchants_where[] = "merchant_id = '".$merchant[merchant_id]."'";    
		        }
	        }
        }
        $merchants_where = implode(' OR ', $merchants_where);
        //$sql_query = mysql_query("SELECT trans.trans_id FROM trans " . @implode(" ", $FROM) . " WHERE ($merchants_where) AND status != 'canceled' AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
        $sql_query = mysql_query("SELECT trans.trans_id FROM trans " . @implode(" ", $FROM) . " WHERE ($merchants_where) AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
        while ($sql = @mysql_fetch_object($sql_query)) {
            $transactions[] = $sql->trans_id;
        }
        
        $total = count($transactions);
        $limit = preg_match('/^\d{0,4}$/', $args->limit) ? $args->limit : $total;
        $offset = preg_match('/^\d{0,4}$/', $args->offset) ? $args->offset : 0;
        
        if ($transactions) {
            $transactions = array_slice($transactions, $offset, $limit ? $limit : $total);
            
            if ($args->obtainUserTransactionDetails) {
                include_once 'functions/obtainUserTransactionDetails.inc.php';
                
                foreach ($transactions as &$transaction) {
                    $transaction = obtainUserTransactionDetails((object) array(
	                    'admin' => $args->admin,
                        'merchant_id' => $args->merchant_id,
                        'username' => $args->username,
	                    'password' => $args->password,
	                    'trans_id' => $transaction,
	                    'include' => $args->obtainUserTransactionDetails
	                ));
                }
            }
        }
        
    }
    
    if ($args->exportTransactions) {
    	exportUserTransactions($transactions, $merchants);
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
	        'data' => $error ? null : (object) array(
	            'total' => (int) $total,
	            'offset' => (int) $offset,
	            'limit' => (int) $limit,
	            'transactions' => (array) $transactions
	        )
	    );
	}
}

?>