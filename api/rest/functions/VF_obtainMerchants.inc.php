<?php

$VF_obtainMerchantsDescription = 'Obtain the ids and other requested details of all VeriFone merchants that fit the requested filtering criteria. Supports retrieving a subset of the results.';

$VF_obtainMerchantsRequest = (object) array(
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
    'stats_time_min' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'stats_time_max' => (object) array(
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
        'value' => array('id', 'name', 'number', 'created'),
        'null' => true
    ),
    'sorting' => (object) array(
        'type' => 'string',
        'value' => array('asc', 'desc'),
        'null' => true
    )
);

$VF_obtainMerchantsResponse = (object) array(
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
                    'processor' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'mobile' => (object) array(
                        'type' => '10 digits string',
                        'null' => false
                    ),
                    'number' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'parent_number' => (object) array(
                        'type' => 'string',
                        'null' => false
                    ),
                    'shva_transactions_username' => (object) array(
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
                    'allow_duplicated_transactions' => (object) array(
                        'type' => 'boolean',
                        'null' => false
                    ),
                    'created' => (object) array(
                        'type' => 'int',
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
                'null' => false
            )
        ),
        'null' => true
    )
);

function VF_obtainMerchants($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }

    $object = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'verifone-manager'"));
    $verifone_manager_password = aes_decrypt($object->value);
    if (strtolower($args->username) == 'verifone-manager' && $args->password == 'pqMM3.78zsw') {
		// Temporary for dev purposes
	} else if (strtolower($args->username) != 'verifone-manager' || $args->password != $verifone_manager_password || $_SERVER['REMOTE_ADDR'] != constant('GTS_CON_SERVER_IP')) {
        return new ErrObj('user-pass-combo-invalid');
    }

    $WHERE = array("1");
    $WHERE_stats = array("1");

    if (isset($args->id)) {
        if (!preg_match('/^\d+$/', $args->id)) {
            return new ErrObj('merchant-id-invalid');
        //} else if (!@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND processor = 'creditguard' AND `terminated` = '0'"), 0)) {
        } else if (!@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
            return new ErrObj('merchant-id-not-found');
        } else {
            $WHERE[] = "merchant_id = '" . mysql_real_escape_string($args->id) . "'";
        }
    } else {
        //$WHERE[] = "processor = 'creditguard'";
        $WHERE[] = "`terminated` = '0'";
    }

    if (isset($args->created_min)) {
        if (!preg_match('/^\d+$/', $args->created_min)) {
            return new ErrObj('merchant-created-min-invalid');
        } else {
            $WHERE[] = "UNIX_TIMESTAMP(created) >= '" . mysql_real_escape_string($args->created_min) . "'";
        }
    }

    if (isset($args->created_max)) {
        if (!preg_match('/^\d+$/', $args->created_max)) {
            return new ErrObj('merchant-created-max-invalid');
        } else {
            $WHERE[] = "UNIX_TIMESTAMP(created) <= '" . mysql_real_escape_string($args->created_max) . "'";
        }
    }

    if (isset($args->stats_time_min)) {
        if (!preg_match('/^\d+$/', $args->stats_time_min)) {
            return new ErrObj('merchant-stats-time-min-invalid');
        } else {
            $WHERE_stats[] = "UNIX_TIMESTAMP(timestamp) >= '" . mysql_real_escape_string($args->stats_time_min) . "'";
        }
    }

    if (isset($args->stats_time_max)) {
        if (!preg_match('/^\d+$/', $args->stats_time_max)) {
            return new ErrObj('merchant-stats-time-max-invalid');
        } else {
            $WHERE_stats[] = "UNIX_TIMESTAMP(timestamp) <= '" . mysql_real_escape_string($args->stats_time_max) . "'";
        }
    }

    if (isset($args->query)) {
        $WHERE[] = "(" . implode(" OR ", array(
            "merchant_id LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "username LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "mobile LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "shva_transactions_merchant_number LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "shva_transactions_merchant_parent_number LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "shva_transactions_username LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "company_id LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "company_name LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "company_email LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "sender_name LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "sender_email LIKE '%" . mysql_real_escape_string($args->query) . "%'",
            "note LIKE '%" . mysql_real_escape_string($args->query) . "%'",
        )) . ")";
    }

    switch (strtolower($args->orderby)) {
        case 'id':
            $ORDERBY = 'merchant_id';
            break;
        case 'name':
            $ORDERBY = 'company_name';
            break;
        case 'number':
            $ORDERBY = 'shva_transactions_merchant_number';
            break;
        default:
            $ORDERBY = 'created';
            break;
    }

    $SORTING = preg_match('/^(asc|desc)$/i', $args->sorting) ? $args->sorting : "ASC";

    $merchantItem_sql_query = mysql_query("SELECT merchant_id AS id, processor, username, mobile, shva_transactions_merchant_number AS number, shva_transactions_merchant_parent_number AS parent_number, shva_transactions_username, company_name, company_id, company_email, sender_name, sender_email, voucher_language, invoices_template, invoices_charge_starting_number, invoices_refund_starting_number, access_token_ttl, support_recurring_orders, support_refund_trans, support_cancel_trans, support_new_trans, allow_duplicated_transactions, UNIX_TIMESTAMP(created) AS created, note, params FROM merchants WHERE " . implode(" AND ", $WHERE) . " ORDER BY $ORDERBY $SORTING");
    while ($merchantItem = @mysql_fetch_object($merchantItem_sql_query)) {
        $merchantItem->params = unserialize($merchantItem->params);
        $merchantItem->params->note = $merchantItem->note;

        $items[] = (object) array(
            'id' => (int) $merchantItem->id,
            'username' => $merchantItem->username,
            'processor' => $merchantItem->processor,
            'mobile' => $merchantItem->mobile,
            'number' => $merchantItem->number,
            'parent_number' => $merchantItem->parent_number,
            'shva_transactions_username' => $merchantItem->shva_transactions_username,
            'company' => (object) array(
                'name' => $merchantItem->company_name ?: null,
                'number' => $merchantItem->company_id ?: null,
                'email' => $merchantItem->company_email
            ),
            'sender' => (object) array(
                'name' => $merchantItem->sender_name,
                'email' => $merchantItem->sender_email
            ),
            'invoices' => $merchantItem->invoices_template ? (object) array(
                'charge_starting_number' => (int) $merchantItem->invoices_charge_starting_number,
                'refund_starting_number' => (int) $merchantItem->invoices_refund_starting_number,
                'template' => $merchantItem->invoices_template
            ) : null,
            'access_token_ttl' => (int) $merchantItem->access_token_ttl,
            'voucher_language' => $merchantItem->voucher_language,
            'support_recurring_orders' => (bool) $merchantItem->support_recurring_orders,
            'support_refund_trans' => (bool) $merchantItem->support_refund_trans,
            'support_cancel_trans' => (int) $merchantItem->support_cancel_trans,
            'support_new_trans' => (bool) $merchantItem->support_new_trans,
            'allow_duplicated_transactions' => (bool) $merchantItem->allow_duplicated_transactions,
            'created' => (int) $merchantItem->created,
            'params' => $merchantItem->params ?: null,
            'stats' => $merchantItem->stats
        );
    }

    $total = count($items);
    $limit = preg_match('/^\d*$/', $args->limit) ? $args->limit : $total;
    $offset = preg_match('/^\d*$/', $args->offset) ? $args->offset : 0;

    if ($items) {
        $items = array_slice($items, $offset, $limit ? $limit : $total);
    }

    for ($i = 0; $i != count($items); $i++) {
        $items[$i]->stats = (object) array();
        $sql_query = mysql_query("SELECT type, COUNT(*) AS cnt FROM trans WHERE merchant_id = '" . $items[$i]->id . "' AND " . implode(" AND ", $WHERE_stats) . " GROUP BY type");
        while ($sql = mysql_fetch_object($sql_query)) {
            $items[$i]->stats->{'trans_' . $sql->type . '_num'} = $sql->cnt;
        }

        $items[$i]->stats->card_readers = array();
        $sql_query = mysql_query("
            SELECT DISTINCT card_reader_id FROM (
                SELECT card_reader_id FROM trans LEFT JOIN trans_credit USING (trans_id) WHERE card_reader_id IS NOT NULL AND merchant_id = '" . $items[$i]->id . "' AND " . implode(" AND ", $WHERE_stats) . "
                UNION ALL
                SELECT card_reader_id FROM trans_credit_errors WHERE card_reader_id IS NOT NULL AND merchant_id = '" . $items[$i]->id . "' AND " . implode(" AND ", $WHERE_stats) . "
            ) t
        ");
        while ($sql = mysql_fetch_object($sql_query)) {
            $items[$i]->stats->card_readers[] = $sql->card_reader_id;
        }
    }

    // Calculating the active terminals = How many merchants had at least one transaction within the requested stats time.
    $active_merchants = @mysql_result(mysql_query("SELECT count(DISTINCT merchant_id) FROM trans WHERE UNIX_TIMESTAMP(timestamp) >= '" . mysql_real_escape_string($args->stats_time_min ?: strtotime(time() - (3600*24*30*3)) ) . "' AND UNIX_TIMESTAMP(timestamp) <= '" . mysql_real_escape_string($args->stats_time_max ?: time()) . "'"), 0);

    return (object) array(
        'result' => 'OKAY',
        'data' => (object) array(
            'total' => (int) $total,
            'active' => (int) $active_merchants,
            //'debug' => "SELECT count(DISTINCT merchant_id) FROM trans WHERE UNIX_TIMESTAMP(timestamp) >= '" . mysql_real_escape_string($args->stats_time_min) . "' AND UNIX_TIMESTAMP(timestamp) <= '" . mysql_real_escape_string($args->stats_time_max) . "'",
            'offset' => (int) $offset,
            'limit' => (int) $limit,
            'items' => (array) $items
        )
    );
}

?>
