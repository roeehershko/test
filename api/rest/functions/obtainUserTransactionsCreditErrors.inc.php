<?php

$obtainUserTransactionsCreditErrorsRequest = (object) array(
    'merchant_id' => (object) array(
        'type' => 'int',
        'null' => false
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
        'value' => array('id', 'timestamp', 'amount', 'currency', 'error_code'),
        'null' => true
    ),
    'sorting' => (object) array(
        'type' => 'string',
        'value' => array('asc', 'desc'),
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
    )
);

$obtainUserTransactionsCreditErrorsResponse = (object) array(
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
            'transactions_errors' => (object) array(
                'type' => 'array of int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function obtainUserTransactionsCreditErrors($args) {
    
    if ($args->admin && $args->merchant_id && !$merchants = authenticateAdmin($args->merchant_id, $args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else if (!$args->admin && !$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
    	    
        $WHERE = array("1");
        $ORDERBY = preg_match('/^(id|timestamp|amount|currency|error_code)$/', $args->orderby) ? $args->orderby : "id";
        $SORTING = preg_match('/^(asc|desc)$/', $args->sorting) ? $args->sorting : "DESC";
        
        if ($args->date_start && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->date_start)) {
            $WHERE[] = "DATE_FORMAT(timestamp, '%Y-%m-%d') >= '" . mysql_real_escape_string($args->date_start) . "'";
        }
        if ($args->date_end && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $args->date_end)) {
            $WHERE[] = "DATE_FORMAT(timestamp, '%Y-%m-%d') <= '" . mysql_real_escape_string($args->date_end) . "'";
        }
        if ($args->search) {
            $columns = array(
                'id' => "id",
                'timestamp' => "timestamp",
                'amount' => "amount",
                'currency' => "currency",
                'cc_holder_name' => "cc_holder_name",
                'cc_last4' => "cc_last4",
                'cc_exp' => "cc_exp",
                'cc_type' => "cc_type",
                'credit_terms' => "credit_terms",
                'transaction_code' => "transaction_code",
                'authorization_number' => "authorization_number",
                'error_code' => "error_code"
            );
            
            if (strpos($args->search, ':') !== false) {
                list($column, $query) = @explode(':', $args->search);
            } else {
                $column = null;
                $query = $args->search;
            }
        
            if ($column && $columns[$column]) {
                $WHERE[] = $columns[$column] . " LIKE '%" . mysql_real_escape_string($query) . "%'";
            } else {
                foreach ($columns as $column) {
                    $WHERE_tmp[] = $column . " LIKE '%" . mysql_real_escape_string($query) . "%'";
                }
                $WHERE[] = "(" . implode(" OR ", $WHERE_tmp) . ")";
            }
        }
        
        if (!empty($merchants)) {
	        foreach ($merchants as $merchant) {
		        if (!$args->merchant_id || $args->merchant_id == $merchant['merchant_id']) {
			    	$merchants_where[] = "merchant_id = '".$merchant[merchant_id]."'";    
		        }
	        }
        }
        $merchants_where = implode(' OR ', $merchants_where);
        //echo "SELECT id FROM trans_credit_errors WHERE ($merchants_where) AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING"; exit; 
        $sql_query = mysql_query("SELECT id FROM trans_credit_errors WHERE ($merchants_where) AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
        while ($sql = @mysql_fetch_object($sql_query)) {
            $transactionsErrors[] = $sql->id;
        }
        
        $total = count($transactionsErrors);
        $limit = preg_match('/^\d{0,4}$/', $args->limit) ? $args->limit : $total;
        $offset = preg_match('/^\d{0,4}$/', $args->offset) ? $args->offset : 0;
        
        if ($transactionsErrors) {
            $transactionsErrors = array_slice($transactionsErrors, $offset, $limit ? $limit : $total);
        }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'total' => (int) $total,
            'offset' => (int) $offset,
            'limit' => (int) $limit,
            'transactions_errors' => (array) $transactionsErrors
        )
    );
}

?>