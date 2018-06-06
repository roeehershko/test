<?php

$obtainUserRecurringOrdersRequest = (object) array(
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
        'value' => array('ro_id', 'name', 'created', 'duedate', 'interval'),
        'null' => true
    ),
    'sorting' => (object) array(
        'type' => 'string',
        'value' => array('asc', 'desc'),
        'null' => true
    ),
    'search' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

$obtainUserRecurringOrdersResponse = (object) array(
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
            'recurring_orders' => (object) array(
                'type' => 'array of int',
                'null' => false
            )
        ),
        'null' => true
    )
);

function obtainUserRecurringOrders($args) {
    if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = $authenticate__error;
    } else {
        $FROM = array();
        $WHERE = array("1");
        $ORDERBY = preg_match('/^(ro_id|name|created|duedate|interval)$/', $args->orderby) ? $args->orderby : "ro_id";
        $SORTING = preg_match('/^(asc|desc)$/', $args->sorting) ? $args->sorting : "DESC";
        
        if ($args->search) {
            $FROM[] = "LEFT JOIN recurring_orders_params ON recurring_orders.ro_id = recurring_orders_params.ro_id AND recurring_orders_params.name = 'note'";
            
            $WHERE[] = "(" . implode(" OR ", array(
                "recurring_orders.ro_id LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders.merchant_id LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders.name LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders.duedate LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders.interval LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders.invoice_recipients LIKE '%" . mysql_real_escape_string($args->search) . "%'",
                "recurring_orders_params.value LIKE '%" . mysql_real_escape_string($args->search) . "%'",
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
        //$sql_query = mysql_query("SELECT recurring_orders.ro_id FROM recurring_orders " . @implode(" ", $FROM) . " WHERE merchant_id = '$merchant[merchant_id]' AND `terminated` = '0' AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
        $sql_query = mysql_query("SELECT recurring_orders.ro_id FROM recurring_orders " . @implode(" ", $FROM) . " WHERE ($merchants_where) AND `terminated` = '0' AND " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
        while ($sql = @mysql_fetch_object($sql_query)) {
            $recurring_orders[] = $sql->ro_id;
        }
        
        $total = count($recurring_orders);
        $limit = preg_match('/^\d{0,4}$/', $args->limit) ? $args->limit : $total;
        $offset = preg_match('/^\d{0,4}$/', $args->offset) ? $args->offset : 0;
        
        if ($recurring_orders) {
            $recurring_orders = array_slice($recurring_orders, $offset, $limit ? $limit : $total);
        }
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'total' => (int) $total,
            'offset' => (int) $offset,
            'limit' => (int) $limit,
            'recurring_orders' => (array) $recurring_orders
        )
    );
}

?>