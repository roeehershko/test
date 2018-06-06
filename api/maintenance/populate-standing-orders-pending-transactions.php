<?php

/*
 * This script should be executed daily.
 * It checks whether new pending standing-order-transactions should be generated and does so when necessary.
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

// Get those standing-orders that have a next due-date that is either today or prior (to catch situations in which the script was not executed on time).
$sql_query = mysql_query("SELECT so_id, beginon, `interval`, amount_default, currency_default, trans_duenext FROM standing_orders WHERE `terminated` = '0' AND `interval` != 'manual' AND trans_duenext <= NOW()");
while ($sql = mysql_fetch_assoc($sql_query)) {
    // Set the first standing-order-transaction due-date.
    $transactions = array(
        array(
            'y' => substr($sql['beginon'], 0, 4),
            'm' => substr($sql['beginon'], 5, 2),
            'd' => substr($sql['beginon'], 8, 2)
        )
    );
    
    $date = false; // On each iteration assumes the first due-date and is used to calculate from there.
    $date_set = false; // The date that's actually used for a transaction (only relevant for non-monthly intervals, when $date can be in the future, but not set as the transaction's date, and so the last transaction's due date is actually in the past).
    
    // Get all transaction due-dates starting from the first until the next due-date.
    if ($sql['interval'] == 'monthly' || $sql['interval'] == 'bimonthly' || $sql['interval'] == 'quarterly') {
        for ($i = 0; $date_set['y'] . $date_set['m'] . $date_set['d'] <= date('Ymd'); $i++) {
            $date = $transactions[0];
            
            $years = floor(($date['m'] + $i) / 12);
            
            $date['m'] += ($i - 12 * $years) + 1;
            $date['y'] += $years;
            
            // Adjust the day so as to maintain a valid date (required due to the number of days in a month not being constant).
            while (!checkdate($date['m'], $date['d'], $date['y'])) {
                $date['d']--;
            }
            
            $date['m'] = substr('0' . $date['m'], -2); // Format month to two digits.
            
            // Add the transaction if the interval is monthly, or bimonthly and the month is odd/even, or quarterly and the month is as required .
            if ($sql['interval'] == 'monthly' || ($sql['interval'] == 'bimonthly' && $i % 2) || ($sql['interval'] == 'quarterly' && !(($i + 1) % 3))) {
                $transactions[] = $date_set = $date;
            }
        }
    } elseif ($sql['interval'] == 'yearly') {
        for ($i = 0; $date_set['y'] . $date_set['m'] . $date_set['d'] <= date('Ymd'); $i++) {
            $date = $transactions[0];
            
            $date['y'] += $i;
            
            // Adjust the day so as to maintain a valid date (required due to the number of days in a month not being constant).
            while (!checkdate($date['m'], $date['d'], $date['y'])) {
                $date['d']--;
            }
            
            $transactions[] = $date_set = $date;
        }
    }
    
    // The last transaction due-date always reflects the next (future) due-date.
    $duenext = array_pop($transactions);
    
    // Update the standing-orders-transactions table so as to fill it up with pending transactions (unless otherwise marked).
    for ($i = 0; $i != count($transactions); $i++) {
        mysql_query("INSERT INTO standing_orders_transactions (so_id, date, amount_default, currency_default, status) VALUES ('$sql[so_id]', '" . $transactions[$i]['y'] . "-" . $transactions[$i]['m'] . "-" . $transactions[$i]['d'] . "', '$sql[amount_default]', '$sql[currency_default]', 'pending')");
    }
    
    $trans_charged = @mysql_result(mysql_query("SELECT COUNT(*) FROM standing_orders_transactions WHERE so_id = '$sql[so_id]' AND status = 'charged'"), 0);
    $trans_canceled = @mysql_result(mysql_query("SELECT COUNT(*) FROM standing_orders_transactions WHERE so_id = '$sql[so_id]' AND status = 'canceled'"), 0);
    $trans_pending = @mysql_result(mysql_query("SELECT COUNT(*) FROM standing_orders_transactions WHERE so_id = '$sql[so_id]' AND status = 'pending'"), 0);
    
    mysql_query("UPDATE standing_orders SET trans_charged = '$trans_charged', trans_canceled = '$trans_canceled', trans_pending = '$trans_pending', trans_duenext = '" . $duenext['y'] . "-" . $duenext['m'] . "-" . $duenext['d'] . "' WHERE so_id = '$sql[so_id]'");
}

?>