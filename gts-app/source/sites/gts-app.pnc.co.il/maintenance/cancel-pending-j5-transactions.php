<?php

/*
 * This script should be executed daily.
 * It finds all pending J5 transactions that are older than 14 days and cancels them.
 * For security reasons this script can only be executed from shell.
*/

if (!$argv) {
    die('Access denied.');
}

set_time_limit(300);

header('Content-Type: text/plain; charset=UTF-8');

require(dirname($_SERVER['SCRIPT_NAME']) . '/../config.inc.php');
require(dirname($_SERVER['SCRIPT_NAME']) . '/../core.inc.php');

if (!connectDB($connectDB__error)) {
    die($connectDB__error);
}

// Get pending J5 credit transactions that are older than 14 days.
$sql_query = mysql_query("SELECT trans_id FROM trans LEFT JOIN trans_credit USING (trans_id) WHERE status = 'pending' AND j5 = '1' AND DATE(timestamp) < DATE(DATE_SUB(NOW(), INTERVAL 14 DAY))");
while ($sql = mysql_fetch_assoc($sql_query)) {
    mysql_query("UPDATE trans SET status = 'canceled' WHERE trans_id = '$sql[trans_id]'");
}

?>