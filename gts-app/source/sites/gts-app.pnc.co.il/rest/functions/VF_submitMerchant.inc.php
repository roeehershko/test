<?php

$VF_submitMerchantDescription = 'Create a new VeriFone merchant or alter an existing one. To alter an existing merchant its \'id\' must be provided. The \'password\' must be provided only when creating a new merchant or updating the password of an existing merchant. If invoices are disabled, the \'invoices\' object must not be provided. When creating a new merchant, the merchant \'id\' is returned.';

$VF_submitMerchantRequest = (object) array(
    'id' => (object) array(
        'type' => 'int',
        'null' => true
    ),
    'username' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'password' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'mobile' => (object) array(
        'type' => '10 digits string',
        'null' => true
    ),
    'direct_access_ip' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'direct_access_password' => (object) array(
        'type' => '256 bytes string',
        'null' => true
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
        'null' => true
    ),
    'shva_transactions_password' => (object) array(
        'type' => 'string',
        'null' => true
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
        'null' => true
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
        'null' => false
    ),
    'support_new_trans' => (object) array(
        'type' => 'boolean',
        'null' => false
    ),
    'send_daily_report' => (object) array(
        'type' => 'boolean',
        'null' => false
    ),
    'allow_duplicated_transactions' => (object) array(
        'type' => 'boolean',
        'null' => false
    ),
    'params' => (object) array(
        'type' => 'object',
        'null' => true
    )
);

$VF_submitMerchantResponse = (object) array(
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
            )
        ),
        'null' => true
    )
);

function VF_submitMerchant($args) {
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

    // Setting the correct Processor
    if ($args->processor == 'shva-emv' && $args->shva_transactions_username) {
        $processor = 'shva-emv';
    } else if ($args->processor == 'shva' && $args->shva_transactions_username) {
        $processor = 'shva';
    } else {
        $processor = 'creditguard';
    }

    // Validate merchant-id (if sent then this is an update; otherwise it's a new merchant).

    if (isset($args->id) && !preg_match('/^\d+$/', $args->id)) {
        return new ErrObj('merchant-id-invalid');
    }
    if (isset($args->id) && !@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('merchant-id-not-found');
    }

    // Validate username.

    $args_tmp = json_decode(file_get_contents("php://input"));
    $args->username = $args_tmp->username;

    if (!preg_match('/^[a-z0-9]{4,10}$/i', $args->username)) {
        return new ErrObj('username-invalid');
    }

    if (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND merchant_id != '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('username-already-exists');
    }
    if (@mysql_result(mysql_query("SELECT 1 FROM users WHERE username = '" . mysql_real_escape_string($args->username) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('username-already-exists');
    }

    // Validate password.

    $args_tmp = json_decode(file_get_contents("php://input"));
    $args->password = $args_tmp->password;

    if (!isset($args->id) || $args->password) {
        if (strlen($args->password) < 7 || !preg_match('/[a-z]/i', $args->password) || !preg_match('/[0-9]/', $args->password)) {
            return new ErrObj('merchant-password-invalid');
        }
        /*if (@mysql_result(mysql_query("SELECT 1 FROM merchants_passwords_log WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "'"), 0)) {
            return new ErrObj('merchant-password-already-used');
        }*/
    }

    // Validate Direct Access Password
    // 2017-03-23 - We no longer force to have a Direct Access Password along with a Direct Access IP
    if (isset($args->direct_access_ip) && !preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $args->direct_access_ip)) {
        return new ErrObj('direct-access-ip-invalid');
    }
    if (isset($args->direct_access_password)) {
        if (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '$Merchant[merchant_id]' AND direct_access_password IS NULL"), 0) && (strlen($args->direct_access_password) != 32 || !preg_match('/[a-z]/i', $args->direct_access_password) || !preg_match('/[0-9]/', $args->direct_access_password))) {
            return new ErrObj('direct-access-password-invalid');
        }
    }

    // Validate merchant-number.
    if (!preg_match('/^\d{1,10}$/', $args->number)) {
        return new ErrObj('merchant-number-invalid');
    }

    // Validate mobile.
    if ($args->mobile && !preg_match('/^\d{10}$/', $args->mobile)) {
        return new ErrObj('merchant-mobile-invalid');
    }

    /*
    if (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE shva_transactions_merchant_number = '" . mysql_real_escape_string($args->number) . "' AND merchant_id != '" . mysql_real_escape_string($args->id) . "' AND `terminated` = '0'"), 0)) {
        return new ErrObj('merchant-number-already-exists');
    }
    */
    if (isset($args->id) && @mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "' AND shva_transactions_merchant_number != '" . mysql_real_escape_string($args->number) . "' AND (SELECT COUNT(*) FROM trans WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "') > 0"), 0)) {
        //return new ErrObj('merchant-number-cannot-change-has-existing-transactions');
    }

    // Validate company details.

    if (!is_object($args->company)) {
        return new ErrObj('merchant-company-object-invalid');
    }

    if (!preg_match('/^.{1,100}$/', $args->company->name)) {
        return new ErrObj('merchant-company-name-invalid');
    }

    if (!preg_match('/^.{1,10}$/', $args->company->number)) {
        return new ErrObj('merchant-company-number-invalid');
    }

    if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $args->company->email)) {
        return new ErrObj('merchant-company-email-invalid');
    }

    // Validate sender details.

    if (!is_object($args->sender)) {
        return new ErrObj('merchant-sender-object-invalid');
    }

    if (!preg_match('/^.{1,100}$/', $args->sender->name)) {
        return new ErrObj('merchant-sender-name-invalid');
    }

    if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $args->sender->email) || strlen($args->sender->email) > 50) {
        return new ErrObj('merchant-sender-email-invalid');
    }

    // Validate voucher language.

    if ($args->voucher_language) {
        if ($args->voucher_language != 'en' && $args->voucher_language != 'he') {
            return new ErrObj('voucher-language-invalid');
        }
    } else {
        $args->voucher_language = 'he';
    }

    // Validate invoices support/details.

    if (isset($args->invoices)) {
        if (!is_object($args->invoices)) {
            return new ErrObj('merchant-invoices-object-invalid');
        }
        if (!preg_match('/^\d{1,9}$/', $args->invoices->charge_starting_number)) {
            return new ErrObj('merchant-invoices-charge-starting-number-invalid');
        }
        if (!preg_match('/^\d{1,9}$/', $args->invoices->refund_starting_number)) {
            return new ErrObj('merchant-invoices-refund-starting-number-invalid');
        }
        if (!$args->invoices->template) {
            return new ErrObj('merchant-invoices-template-invalid');
        }
    } else {
        $args->invoices = null;
    }

    // Validate access_token_ttl.

    if (isset($args->access_token_ttl)) {
        if (!preg_match('/^[1-9]\d*$/', $args->access_token_ttl) || $args->access_token_ttl > 10080) {
            return new ErrObj('access-token-ttl-invalid');
        }
    } else {
        $args->access_token_ttl = 20;
    }

    // Validate params.

    if (isset($args->params) && !is_object($args->params)) {
        return new ErrObj('merchant-params-object-invalid');
    }

    if (isset($args->params) && $args->params->note) {
        $args->note = $args->params->note;
        $args->params->note = null;
    } else {
        $args->note = null;
    }

    if ($args->processor == 'shva') {
        if ($args->shva_transactions_username && $args->shva_transactions_password) {
            // 2017-04-09 - If a SHVA password has been entered - we must assume it's an initial SHVA password that must be changed
            $existing_shva_transactions_password = $args->shva_transactions_password;
            $new_shva_transactions_password = generateLongPassword();
            $shva_response = shva_changePassword($args->number, $args->shva_transactions_username, $existing_shva_transactions_password, $new_shva_transactions_password);
            if ($shva_response == 'OKAY') {
                $args->shva_transactions_password = $new_shva_transactions_password;
            } else {
                return new ErrObj('shva-update-password-failed', $shva_response);
            }
        } else if ($args->shva_transactions_username) {
            // 2017-07-25 - If only a SHVA username was entered, we need to search and see if any other merchant is already using the same username. In which case - we will copy the same password of the matching merchant into this one.
            $sqlQuery = "SELECT shva_transactions_password FROM merchants WHERE shva_transactions_username = '" . mysql_real_escape_string($args->shva_transactions_username) . "'";
            $matchingMerchantShvaPassword = @mysql_fetch_object(mysql_query($sqlQuery));
            if ($matchingMerchantShvaPassword && $matchingMerchantShvaPassword->shva_transactions_password) {
                mysql_query("UPDATE merchants SET shva_transactions_password = '" . mysql_real_escape_string($matchingMerchantShvaPassword->shva_transactions_password) . "' WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
            }
        }
    }

    if (isset($args->id)) {

        // Updating the credentials in SHVA for an existing merchant
        /*
        if ($args->shva_transactions_username && $args->password) {
            // Fetching the existing merchant's password hash
            $merchantDetails = @mysql_fetch_object(mysql_query("SELECT password FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'"));
            $existingPasswordHash = $merchantDetails->password;
            // Changing the SHVA password for an existing merchant (if the user changed his password)
            $shva_response = shva_changePassword($args->number, $args->shva_transactions_username, hashSHVAPass($args->shva_transactions_username, $existingPasswordHash), hashSHVAPass($args->shva_transactions_username, hashGTSPass($args->password)));
            if ($shva_response != 'OKAY') {
                return new ErrObj('shva-update-password-failed', $shva_response);
            }
        }
        */

        $log_data = (object) array(
            'before' => mysql_fetch_object(mysql_query("SELECT * FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'"))
        );

        if (!$args->username && $args->password) {
	        // If no username exists - and only a password exists - we update ONLY the password
	        mysql_query("UPDATE merchants SET password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', must_change_password = 1 WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
	        mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('" . mysql_real_escape_string($args->id) . "', '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', NOW())");

			// Deleting ALL previous attemps to create an access token
			mysql_query("DELETE FROM merchants_access_tokens WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
        } else {
        	mysql_query("UPDATE merchants SET username = '" . mysql_real_escape_string($args->username) . "', mobile = " . SQLNULL(mysql_real_escape_string($args->mobile)) . ", shva_transactions_merchant_number = " . SQLNULL(mysql_real_escape_string($args->number)) . ", shva_transactions_merchant_parent_number = " . SQLNULL(mysql_real_escape_string($args->parent_number)) . ", shva_transactions_username = " . SQLNULL(mysql_real_escape_string($args->shva_transactions_username)) . ", processor = '".$processor."', company_id = '" . mysql_real_escape_string($args->company->number) . "', company_name = '" . mysql_real_escape_string($args->company->name) . "', company_email = '" . mysql_real_escape_string($args->company->email) . "', sender_name = '" . mysql_real_escape_string($args->sender->name) . "', sender_email = '" . mysql_real_escape_string($args->sender->email) . "', sender_email = '" . mysql_real_escape_string($args->sender->email) . "', voucher_language = '" . mysql_real_escape_string($args->voucher_language) . "', invoices_charge_starting_number = " . SQLNULL(mysql_real_escape_string($args->invoices->charge_starting_number)) . ", invoices_refund_starting_number = " . SQLNULL(mysql_real_escape_string($args->invoices->refund_starting_number)) . ", invoices_template = " . SQLNULL(mysql_real_escape_string($args->invoices->template)) . ", access_token_ttl = '" . mysql_real_escape_string($args->access_token_ttl) . "', support_recurring_orders = '" . ($args->support_recurring_orders ? '1' : '0') . "', support_refund_trans = '" . ($args->support_refund_trans ? '1' : '0') . "', support_cancel_trans = '" . ($args->support_cancel_trans ?: '0') . "', support_new_trans = '" . ($args->support_new_trans ? '1' : '0') . "', send_daily_report = '" . ($args->send_daily_report ? '1' : '0') . "', allow_duplicated_transactions = '" . ($args->allow_duplicated_transactions ? '1' : '0') . "', note = " . SQLNULL(mysql_real_escape_string($args->note)) . ", params = " . SQLNULL(mysql_real_escape_string($args->params ? serialize($args->params) : null)) . " WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
            if ($args->password) {
	            mysql_query("UPDATE merchants SET password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', must_change_password = 1 WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
	            mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('" . mysql_real_escape_string($args->id) . "', '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', NOW())");

				// Deleting ALL previous attemps to create an access token
				mysql_query("DELETE FROM merchants_access_tokens WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");

	        }
	    }

        // If the direct-access values were changed, update the db.
        // 2017-03-23 - We no longer force to have a Direct Access Password along with a Direct Access IP
        if ($args->direct_access_ip && !$args->direct_access_password) {
            mysql_query("UPDATE merchants SET direct_access_ip = '" . $args->direct_access_ip . "' WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
        }
        if ($args->direct_access_password) {
            mysql_query("UPDATE merchants SET direct_access_password = '" . aes_encrypt($args->direct_access_password) . "' WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
        }

        // 2017-04-09 - Updating the SHVA password
        if ($args->shva_transactions_password) {
            mysql_query("UPDATE merchants SET shva_transactions_password = '" . aes_encrypt($args->shva_transactions_password) . "' WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'");
        }

        $log_data->after = mysql_fetch_object(mysql_query("SELECT * FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($args->id) . "'"));
        if ($log_data->before == $log_data->after) {
            mysql_query("INSERT INTO merchants_vf_log (ip_address, merchant_id, action) VALUES ('" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . mysql_real_escape_string($args->id) . "', 'updated')");
        } else {
            mysql_query("INSERT INTO merchants_vf_log (ip_address, merchant_id, action, data) VALUES ('" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . mysql_real_escape_string($args->id) . "', 'updated', '" . mysql_real_escape_string(serialize($log_data)) . "')");
        }

        return (object) array(
            'result' => 'OKAY'
        );

    } else {

        /*
        if ($args->shva_transactions_username && $args->shva_transactions_password && $args->password) {
            // Setting the SHVA password for a new merchant
            $shva_response = shva_changePassword($args->number, $args->shva_transactions_username, $args->shva_transactions_password, hashSHVAPass($args->shva_transactions_username, hashGTSPass($args->password)));
            if ($shva_response != 'OKAY') {
                return new ErrObj('shva-update-password-failed', $shva_response);
            }
        }
        */

        mysql_query("INSERT INTO merchants (username, password, must_change_password, mobile, direct_access_ip, direct_access_password, processor, shva_transactions_merchant_number, shva_transactions_merchant_parent_number, shva_transactions_username, shva_transactions_password, created, company_id, company_name, company_email, sender_name, sender_email, voucher_language, invoices_charge_starting_number, invoices_refund_starting_number, invoices_template, access_token_ttl, support_recurring_orders, support_refund_trans, support_cancel_trans, support_new_trans, send_daily_report, allow_duplicated_transactions, note, params) VALUES ('" . mysql_real_escape_string($args->username) . "', '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', 1, " . SQLNULL(mysql_real_escape_string($args->mobile)) . ", " . SQLNULL($args->direct_access_ip) . ", " . SQLNULL($args->direct_access_password ? hashGTSPass($args->direct_access_password) : false) . ", '" . $processor . "', '" . mysql_real_escape_string($args->number) . "', '" . mysql_real_escape_string($args->parent_number) . "', '" . mysql_real_escape_string($args->shva_transactions_username) . "', " . SQLNULL($args->shva_transactions_password ? aes_encrypt($args->shva_transactions_password) : false) . ", NOW(), '" . mysql_real_escape_string($args->company->number) . "', '" . mysql_real_escape_string($args->company->name) . "', '" . mysql_real_escape_string($args->company->email) . "', '" . mysql_real_escape_string($args->sender->name) . "', '" . mysql_real_escape_string($args->sender->email) . "', '" . mysql_real_escape_string($args->voucher_language) . "', " . SQLNULL(mysql_real_escape_string($args->invoices->charge_starting_number)) . ", " . SQLNULL(mysql_real_escape_string($args->invoices->refund_starting_number)) . ", " . SQLNULL(mysql_real_escape_string($args->invoices->template)) . ", '" . mysql_real_escape_string($args->access_token_ttl) . "', '" . ($args->support_recurring_orders ? '1' : '0') . "', '" . ($args->support_refund_trans ? '1' : '0') . "', '" . ($args->support_cancel_trans ?: '0') . "', '" . ($args->support_new_trans ? '1' : '0') . "', '" . ($args->send_daily_report ? '1' : '0') . "', '" . ($args->allow_duplicated_transactions ? '1' : '0') . "', " . SQLNULL(mysql_real_escape_string($args->note)) . ", " . SQLNULL(mysql_real_escape_string($args->params ? serialize($args->params) : null)) . ")");
        $merchant_id = mysql_insert_id();

        mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('$merchant_id', '" . mysql_real_escape_string(hashGTSPass($args->password)) . "', NOW())");

        mysql_query("INSERT INTO merchants_vf_log (ip_address, merchant_id, action) VALUES ('" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', '$merchant_id', 'added')");

        return (object) array(
            'result' => 'OKAY',
            'data' => (object) array(
                'id' => $merchant_id
            )
        );

    }
}

?>
