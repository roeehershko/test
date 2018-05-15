<?php

$VF_obtainUsersDescription = 'Obtain the ids and other requested details of all users that fit the requested filtering criteria. Supports retrieving a subset of the results.';

$VF_obtainUsersRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'created_min' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'created_max' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'query' => (object) array(
        'type' => 'string',
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
        'value' => array('id', 'name', 'created'),
        'null' => true
    ),
    'sorting' => (object) array(
        'type' => 'string',
        'value' => array('asc', 'desc'),
        'null' => true
    )
);

$VF_obtainUsersResponse = (object) array(
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
            'items' => (object) array(
                'type' => 'array of object',
                'value' => (object) array(
                    'id' => (object) array(
                        'type' => 'int',
                        'null' => false
                    ),
                    'username' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'type' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'name' => (object) array(
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
                    'merchants' => (object) array(
                        'type' => 'array',
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
                'null' => false
            )
        ),
        'null' => true
    )
);

function VF_obtainUsers($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    
    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }
    
    $WHERE = array("1");
    $WHERE_stats = array("1");
    
    if (isset($args->id)) {
        if (!preg_match('/^\d+$/', $args->id)) {
            return new ErrObj('user-id-invalid');
        } else if (!@mysql_result(mysql_query("SELECT 1 FROM users WHERE user_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
            return new ErrObj('user-id-not-found');
        } else {
            $WHERE[] = "user_id = '" . mysql_real_escape_string($args->id) . "'";
        }
    } else {
        $WHERE[] = "`terminated` = '0'";
    }
    
    if (isset($args->created_min)) {
        if (!preg_match('/^\d+$/', $args->created_min)) {
            return new ErrObj('user-created-min-invalid');
        } else {
            $WHERE[] = "UNIX_TIMESTAMP(created) >= '" . mysql_real_escape_string($args->created_min) . "'";
        }
    }
    
    if (isset($args->created_max)) {
        if (!preg_match('/^\d+$/', $args->created_max)) {
            return new ErrObj('user-created-max-invalid');
        } else {
            $WHERE[] = "UNIX_TIMESTAMP(created) <= '" . mysql_real_escape_string($args->created_max) . "'";
        }
    }
    
    if (isset($args->query)) {
        $WHERE[] = "(" . implode(" OR ", array(
            "user_id LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "username LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "name LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "company LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "company_id LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "email LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "mobile LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "note LIKE '%" . mysql_real_escape_string($args->query) . "%'",
        )) . ")";
    }
    
    switch (strtolower($args->orderby)) {
        case 'id':
            $ORDERBY = 'user_id';
            break;
        case 'name':
            $ORDERBY = 'name';
            break;
        default:
            $ORDERBY = 'created';
            break;
    }
    
    $SORTING = preg_match('/^(asc|desc)$/i', $args->sorting) ? $args->sorting : "ASC";
    
    $userItem_sql_query = mysql_query("SELECT user_id, username, type, name, company, company_id, email, mobile, UNIX_TIMESTAMP(created) AS created, note, params FROM users WHERE " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
    while ($userItem = @mysql_fetch_object($userItem_sql_query)) {
        $userItem->params = unserialize($userItem->params);
        $userItem->params->note = $userItem->note;
        
        $merchants_query = mysql_query("SELECT merchant_id FROM merchants_users WHERE user_id = '".mysql_real_escape_string($userItem->user_id)."'");
        $merchants = false;
        while ($merchant = @mysql_fetch_object($merchants_query)) {
        	$merchants[] = $merchant->merchant_id;
        }
        
        $items[] = (object) array(
            'id' => (int) $userItem->user_id,
            'username' => $userItem->username,
            'type' => $userItem->type,
            'name' => $userItem->name,
            'company' => $userItem->company,
            'company_id' => $userItem->company_id,
            'email' => $userItem->email,
            'mobile' => $userItem->mobile,
            'merchants' => $merchants,
            'note' => $userItem->note,
            'created' => (int) $userItem->created,
            'params' => $userItem->params ?: null,
        );
    }
    
    $total = count($items);
    $limit = preg_match('/^\d*$/', $args->limit) ? $args->limit : $total;
    $offset = preg_match('/^\d*$/', $args->offset) ? $args->offset : 0;
    
    if ($items) {
        $items = array_slice($items, $offset, $limit ? $limit : $total);
    }
    
    return (object) array(
        'result' => 'OKAY',
        'data' => (object) array(
            'total' => (int) $total,
            'offset' => (int) $offset,
            'limit' => (int) $limit,
            'items' => (array) $items
        )
    );
}

?>
