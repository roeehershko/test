<?php

$user_info = mysql_fetch_assoc(mysql_query("SELECT user_id AS id, user_id, email, first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2, date_of_birth, registration AS registration_date, send_newsletters FROM type_user WHERE user_id = '" . $_SESSION['user']['user_id'] . "'"));

$user_info['affilaite_id'] = @mysql_result(mysql_query("SELECT affiliate_id FROM type_affiliate WHERE user_id = '$user_info[user_id]'"), 0);

##

$sql_query = mysql_query("SELECT order_id AS id, order_id, sequential, ordered AS ordered_date, paid AS paid_date, shipped AS shipped_date, delivered AS delivered_date, price_total AS price, cancelled FROM type_order WHERE billing_user_id = '$user_info[user_id]' ORDER BY ordered DESC");
while ($sql = mysql_fetch_assoc($sql_query)) {
    $orders[] = $sql;
}

$sql_query = mysql_query("SELECT activkey_id AS id, activkey_id, sequential, license_id, ordered AS ordered_date, paid AS paid_date, expiration_date, total AS price, cancelled FROM type_activkey WHERE billing_user_id = '$user_info[user_id]' ORDER BY ordered DESC");
while ($sql = mysql_fetch_assoc($sql_query)) {
    $activkeys[] = $sql;
}

$sql_query = mysql_query("SELECT credits_trans_id AS id, credits_trans_id, sequential, timestamp, price_total AS price, cancelled FROM type_credits_trans WHERE billing_user_id = '$user_info[user_id]' ORDER BY timestamp DESC");
while ($sql = mysql_fetch_assoc($sql_query)) {
    $credit_orders[] = $sql;
}

$has_exams = @mysql_result(mysql_query("SELECT exam_id FROM type_exam__participants WHERE user_id = '" . $_SESSION['user']['user_id'] . "'"), 0);

##

$T_superdoc['orders'] = $orders;
$T_superdoc['activkeys'] = $activkeys;
$T_superdoc['credit_orders'] = $credit_orders;
$T_superdoc['user_info'] = $user_info;
$T_superdoc['has_exams'] = $has_exams;

echo renderTemplate('superdocs/my-account', $T_superdoc);

?>