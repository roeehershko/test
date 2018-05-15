<?php

$createUserAccessTokenDescription = 'Used to login into the GTC. Verify the credentials and return an access-token if valid.';

$createUserAccessTokenRequest = (object) array(
    'ip_address' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

$createUserAccessTokenResponse = (object) array(
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
            'access_token' => (object) array(
                'type' => 'string',
                'null' => false
            ),
            'last_access_timestamp' => (object) array(
                'type' => 'string',
                'value' => '{YYYY-MM-DD HH:MM:SS}',
                'null' => true
            )
        ),
        'null' => true
    )
);

function createUserAccessToken($args) {
    if (connectDB($connectDB__error)) {
        // Try to authenticate with user/pass; on fail try to match the username to a merchant for logging purposes.
        if ($user = mysql_fetch_assoc(mysql_query("SELECT user_id FROM users WHERE password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "' AND username = '" . mysql_real_escape_string($args->username) . "' AND `terminated` = '0'"))) {
            $sql_query = mysql_query(("SELECT merchant_id FROM merchants_users WHERE user_id = '" . mysql_real_escape_string($user['user_id'])."'"));
            while ($sql = @mysql_fetch_object($sql_query)) {
                if ($merchant = mysql_fetch_assoc(mysql_query("SELECT merchant_id, username, processor, password, shva_transactions_merchant_number, shva_transactions_username, shva_transactions_password, shva_standing_orders_merchant_number, shva_standing_orders_username, support_recurring_orders, allow_duplicated_transactions, params FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($sql->merchant_id) . "'"))) {
                    $merchant['params'] = unserialize($merchant['params']);
                    $merchants[] = $merchant;
                }
            }
        }

        $user['user_id'] = @mysql_result(mysql_query("SELECT user_id FROM users WHERE username = '" . mysql_real_escape_string($args->username) . "' AND `terminated` = '0'"), 0);

        // Unless the login attempt can be linked to a merchant, there's no point in logging the attempt (regardless of the outcome).
        if ($merchants && $user['user_id']) {

            // Get the number of currently unsuccessful login attempts (starting from the last successful attempt).
            //$failedAttempts = @mysql_result(mysql_query("SELECT COUNT(*) FROM users_access_tokens WHERE user_id = '$user[user_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NULL AND timestamp > (SELECT timestamp FROM users_access_tokens WHERE user_id = '$user[user_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1)"), 0);
            // 2016-07-05 - Per Comsec demand - we must not rely on a per-IP constraint
            $failedAttempts = @mysql_result(mysql_query("SELECT COUNT(*) FROM users_access_tokens WHERE user_id = '$user[user_id]' AND access_token IS NULL AND timestamp > (SELECT timestamp FROM users_access_tokens WHERE user_id = '$user[user_id]' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1)"), 0);

            // Increment the number of unsuccessful login attempts if this attempt failed.
            if ($error) {
                $failedAttempts += 1;
            }

            // If 3 unsuccessful login attempts were reached, block further attempts until 15 have passed since the last attempt.
            // If 6 unsuccessful login attempts were reached, block all further attempts.
            if ($failedAttempts >= 3) {
                //$failedLast = @mysql_result(mysql_query("SELECT UNIX_TIMESTAMP(timestamp) FROM users_access_tokens WHERE user_id = '$user[user_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NULL ORDER BY timestamp DESC LIMIT 1"), 0);
                // 2016-07-05 - Per Comsec demand - we must not rely on a per-IP constraint
                $failedLast = @mysql_result(mysql_query("SELECT UNIX_TIMESTAMP(timestamp) FROM users_access_tokens WHERE user_id = '$user[user_id]' AND access_token IS NULL ORDER BY timestamp DESC LIMIT 1"), 0);

                if ($failedAttempts >= 3 && $failedAttempts < 6 && time() - $failedLast < 900) {
                    $blocked = true;
                    $error = 8;
                } elseif ($failedAttempts >= 6) {
                    $blocked = true;
                    $error = 9;
                }
            }

            // Log the current login attempt, regardless of the outcome, unless a block is currently in effect (otherwise, even successful attempts would be logged as unsuccessful).
            if (!$error) {
                $token = sha1($user['user_id'] . time() . constant('SECRET') . $args->ip_address);
                $access_token_ttl = $_ENV['ACCESS_TOKEN_TTL'] || 60; //@mysql_result(mysql_query("SELECT access_token_ttl FROM merchants WHERE id = '$user[user_id]'"), 0);

                $lastAccessTokenTimestamp = @mysql_result(mysql_query("SELECT timestamp FROM users_access_tokens WHERE user_id = '$user[user_id]' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1"), 0);

                mysql_query("INSERT INTO users_access_tokens (user_id, timestamp, remote_address, expiration, access_token) VALUES ('$user[user_id]', NOW(), '" . mysql_real_escape_string($args->ip_address) . "', NOW() + INTERVAL $access_token_ttl MINUTE, '$token')");
            //} elseif (!$blocked) {
            } else {
                mysql_query("INSERT INTO users_access_tokens (user_id, timestamp, remote_address, expiration, access_token) VALUES ('$user[user_id]', NOW(), '" . mysql_real_escape_string($args->ip_address) . "', NULL, NULL)");
            }

        } else {

            // Try to authenticate with user/pass or direct-access-password; on fail try to match the username to a merchant for logging purposes.
    	    $SELECT = "merchant_id, processor, password, shva_transactions_merchant_number, shva_transactions_username, shva_transactions_password, shva_standing_orders_merchant_number, shva_standing_orders_username, allow_duplicated_transactions, support_refund_trans, support_new_trans";
            $sql_query = "SELECT $SELECT, (SELECT TIMESTAMPADD(MONTH, 3, updated) < NOW() FROM merchants_passwords_log WHERE merchant_id = merchants.merchant_id AND password = merchants.password ORDER BY updated DESC LIMIT 1) AS password_expired FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "' AND `terminated` = '0'";
            //$sql_query_direct_access = "SELECT $SELECT FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND direct_access_ip = '" . $_SERVER['REMOTE_ADDR'] . "' AND direct_access_password = '" . mysql_real_escape_string(hashGTSPass($args->password)) . "' AND `terminated` = '0'";
            // 2017-03-23 - We no longer require a Direct Access IP
            $sql_query_direct_access = "SELECT $SELECT FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND direct_access_password = '" . mysql_real_escape_string(aes_encrypt($args->password)) . "' AND `terminated` = '0'";
            
            if (!$args->username || !$args->password || (!($sql = mysql_fetch_assoc(mysql_query($sql_query))) && !($sql = mysql_fetch_assoc(mysql_query($sql_query_direct_access))))) {
                $error = 6;
            } else if ($sql['password_expired']) {
                $error = 12;
            } else if ($sql['merchant_id']) {
                $merchant = array(
                    'merchant_id' => $sql['merchant_id'],
                    'processor' => $sql['processor'],
                    'shva_transactions_merchant_number' => $sql['shva_transactions_merchant_number'],
                    'shva_transactions_username' => $sql['shva_transactions_username'],
                    'shva_transactions_password' => aes_decrypt($sql['shva_transactions_password']), //hashSHVAPass($sql['shva_transactions_username'], $sql['password']),
                    //'shva_standing_orders_merchant_number' => $sql['shva_standing_orders_merchant_number'],
                    //'shva_standing_orders_username' => $sql['shva_standing_orders_username'],
                    //'shva_standing_orders_password' => aes_decrypt($sql['shva_standing_orders_password']), //hashSHVAPass($sql['shva_standing_orders_username'], $sql['password']),
                    'allow_duplicated_transactions' => $sql['allow_duplicated_transactions'],
                    'support_refund_trans' => (bool) $sql['support_refund_trans'],
                    'support_new_trans' => (bool) $sql['support_new_trans'],
                );
            }

            if (!$merchant) {
    	        $error = 6;

    	        $merchant['merchant_id'] = @mysql_result(mysql_query("SELECT merchant_id FROM merchants WHERE username = '" . mysql_real_escape_string($args->username) . "' AND `terminated` = '0'"), 0);
    	    }

    	    // Unless the login attempt can be linked to a merchant, there's no point in logging the attempt (regardless of the outcome).
    	    if ($merchant['merchant_id']) {
    	        // Get the number of currently unsuccessful login attempts (starting from the last successful attempt).
    	        //$failedAttempts = @mysql_result(mysql_query("SELECT COUNT(*) FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NULL AND timestamp > (SELECT timestamp FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1)"), 0);
    	        // 2016-07-05 - Per Comsec demand - we must not rely on a per-IP constraint
                $failedAttempts = @mysql_result(mysql_query("SELECT COUNT(*) FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND access_token IS NULL AND timestamp > (SELECT timestamp FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1)"), 0);

                // Increment the number of unsuccessful login attempts if this attempt failed.
    	        if ($error) {
    	            $failedAttempts += 1;
    	        }

    	        // If 3 unsuccessful login attempts were reached, block further attempts until 15 have passed since the last attempt.
    	        // If 6 unsuccessful login attempts were reached, block all further attempts.
    	        if ($failedAttempts >= 3 && $merchant['merchant_id'] != 1 && $merchant['merchant_id'] != 2) {
    	            //$failedLast = @mysql_result(mysql_query("SELECT UNIX_TIMESTAMP(timestamp) FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND remote_address = '" . mysql_real_escape_string($args->ip_address) . "' AND access_token IS NULL ORDER BY timestamp DESC LIMIT 1"), 0);
    	            // 2016-07-05 - Per Comsec demand - we must not rely on a per-IP constraint
                    $failedLast = @mysql_result(mysql_query("SELECT UNIX_TIMESTAMP(timestamp) FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND access_token IS NULL ORDER BY timestamp DESC LIMIT 1"), 0);

    	            if ($failedAttempts >= 3 && $failedAttempts < 6 && time() - $failedLast < 900) {
    	                $blocked = true;
    	                //$error = 8;
    	            } elseif ($failedAttempts >= 6) {
    	                $blocked = true;
                        // 2016-11-11 - Even if permanently blocked, the user can renew the password through the renewal password procedure
    	                //$error = 9;
    	            }
    	        }

    	        // Log the current login attempt, regardless of the outcome, unless a block is currently in effect (otherwise, even successful attempts would be logged as unsuccessful).
    	        if (!$error) {
    	            $token = sha1($merchant['merchant_id'] . time() . constant('SECRET') . $args->ip_address);
    	            $access_token_ttl = @mysql_result(mysql_query("SELECT access_token_ttl FROM merchants WHERE merchant_id = '$merchant[merchant_id]'"), 0);

    	            $lastAccessTokenTimestamp = @mysql_result(mysql_query("SELECT timestamp FROM merchants_access_tokens WHERE merchant_id = '$merchant[merchant_id]' AND access_token IS NOT NULL ORDER BY timestamp DESC LIMIT 1"), 0);

    	            mysql_query("INSERT INTO merchants_access_tokens (merchant_id, timestamp, remote_address, expiration, access_token) VALUES ('$merchant[merchant_id]', NOW(), '" . mysql_real_escape_string($args->ip_address) . "', NOW() + INTERVAL $access_token_ttl MINUTE, '$token')");
    	        // } elseif (!$blocked) {
    	        } else {
    	            mysql_query("INSERT INTO merchants_access_tokens (merchant_id, timestamp, remote_address, expiration, access_token) VALUES ('$merchant[merchant_id]', NOW(), '" . mysql_real_escape_string($args->ip_address) . "', NULL, NULL)");
    	        }
    	    } else {
    		    $error = $error ?: 6;
    	    }
        }
    } else {
        $error = $connectDB__error;
    }

    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        'data' => $error ? null : (object) array(
            'access_token' => $token,
            'last_access_timestamp' => $lastAccessTokenTimestamp ?: null,
        )
    );
}

?>
