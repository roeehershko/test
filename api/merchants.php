<?php

// openssl genrsa -aes256 -passout pass:yourpassword -out private 2048; openssl rsa -passin pass:yourpassword -in private -pubout > public;

if (!$_SERVER['HTTPS']) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

##

require('./config.inc.php');
require('./core.inc.php');

if (!connectDB($connectDB__error)) {
    die($connectDB__error);
}

if (($_SERVER['PHP_AUTH_USER'] != 'root' || md5($_SERVER['PHP_AUTH_PW']) != '9f1ca9b498aa559fc51b4694281c7d85') &&	
	($_SERVER['PHP_AUTH_USER'] != 'shani' || md5($_SERVER['PHP_AUTH_PW']) != '452198705f828489bb0ac66b0cf2577a')
) {
    header('WWW-Authenticate: Basic realm="GTS Merchants"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You must enter a valid login username and password to access this resource.' . "\n";
    exit;
}

/*
if ($_GET['kay']) {
    $merchant_id = '260';
    $shva_term = '7111238015';
    $shva_user = 'BRJIR';
    $shva_pass = '031739';
    
    shva_changePassword($shva_term, $shva_user, $shva_pass, hashSHVAPass($shva_user, hashGTSPass($shva_pass)));
    mysql_query("UPDATE merchants SET password = '" . mysql_real_escape_string(hashGTSPass($shva_pass)) . "' WHERE merchant_id = '" . $merchant_id . "'");
    mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('" . $merchant_id . "', '" . mysql_real_escape_string(hashGTSPass($shva_pass)) . "', NOW())");
    exit;
}
//*/

if ($_GET['merchant'] == 'new') {
    $Merchant_new = true;
} elseif ($_GET['merchant'] && ($Merchant = mysql_fetch_assoc(mysql_query("SELECT merchant_id, username, password, direct_access_ip, direct_access_password, processor, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username, company_id, company_name, company_email, sender_name, sender_email, invoices_charge_starting_number, invoices_refund_starting_number, invoices_template, support_standing_orders, support_refund_trans, support_new_trans, allow_duplicated_transactions, note FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($_GET['merchant']) . "' AND `terminated` = '0'")))) {
    $_FORM = $Merchant;
    unset($_FORM['password'], $_FORM['direct_access_password']);
} else {
    $sql_query = mysql_query("SELECT merchant_id, username, direct_access_ip, direct_access_password, processor, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username, company_id, company_name, company_email, invoices_template, support_standing_orders, support_refund_trans, support_new_trans, allow_duplicated_transactions, note FROM merchants WHERE `terminated` = '0' ORDER BY merchant_id");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $merchants[] = $sql;
    }
    
    $processors = array();
    $processors_summary = array();
    if ($merchants) {
        foreach ($merchants as $merchant) {
            $processors[$merchant['processor']]++;
        }
    }
    if ($processors) {
        arsort($processors);
        foreach ($processors as $processror => $count) {
            $processors_summary[] = ' ' . $processror . ': ' . number_format($count) . ' ';
        }
        $processors_summary = implode("\n", $processors_summary);
    }
}

if ($_GET['action'] == 'delete' && $_GET['merchant']) {
    mysql_query("UPDATE merchants SET `terminated` = '1' WHERE merchant_id = '$_GET[merchant]'");
    
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit;
}

if ($_POST) {
    $_POST = array_map('stripslashes', $_POST);
    $_POST = array_map('trim', $_POST);
    
    if (!preg_match('/^[a-z0-9]{5,}$/i', $_POST['username'])) {
        $errors['username'] = 'invalid username; must be at least 5 charcters long and contain only letters and digits';
    } elseif (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE username = '" . mysql_real_escape_string($_POST['username']) . "' AND merchant_id != '$Merchant[merchant_id]'"), 0)) {
        $errors['username'] = 'invalid username; already exists';
    }
    
    if (($Merchant_new || $_POST['password']) && (strlen($_POST['password']) < 6 || !preg_match('/[a-z]/i', $_POST['password']) || !preg_match('/[0-9]/', $_POST['password'])) && strtoupper($_POST['username']) != 'NOIGX' && strtoupper($_POST['username']) != 'NOIGY') {
        $errors['password'] = 'invalid password; must be at least 6 characters long and contain both letters and digits';
    }
        
    if ($_POST['direct_access_ip'] || $_POST['direct_access_password']) {
        if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $_POST['direct_access_ip'])) {
            $errors['direct_access_ip'] = 'invalid direct-access IP address';
        }
        
        if (@mysql_result(mysql_query("SELECT 1 FROM merchants WHERE merchant_id = '$Merchant[merchant_id]' AND direct_access_password IS NULL"), 0) && (strlen($_POST['direct_access_password']) != 32 || !preg_match('/[a-z]/i', $_POST['direct_access_password']) || !preg_match('/[0-9]/', $_POST['direct_access_password']))) {
            $errors['direct_access_password'] = 'invalid direct-access password; must be exactly 32 characters long and contain both letters and digits';
        }
    }
    
    if ($_POST['company_id'] && !preg_match('/^\d{6,9}$/', $_POST['company_id'])) {
        $errors['company_id'] = 'invalid company id; must be between 6 and 9 digits long';
    }
    
    if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $_POST['company_email'])) {
        $errors['company_email'] = 'invalid company email';
    }
    
    if (!$_POST['sender_name']) {
        $errors['sender_name'] = 'invalid sender name';
    }
    
    if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $_POST['sender_email'])) {
        $errors['sender_email'] = 'invalid sender email';
    }
    
    if ($Merchant_new && !preg_match('/^\d{7,10}$/', $_POST['shva_transactions_merchant_number'])) {
        $errors['shva_transactions_merchant_number'] = 'invalid SHVA transactions merchant number; must be 10 digits long';
    }
    
    if ($Merchant_new && $_POST['processor'] == 'shva' && !preg_match('/^[a-z]{5}$/i', $_POST['shva_transactions_username'])) {
        $errors['shva_transactions_username'] = 'invalid SHVA transactions username; must be 5 letters long';
    }
    
    if ($Merchant_new && $_POST['processor'] == 'shva' && !$_POST['shva_transactions_password']) {
        $errors['shva_transactions_password'] = 'invalid SHVA transactions password';
    }
    
    if (($Merchant_new || !$Merchant['shva_standing_orders_merchant_number']) && $_POST['support_standing_orders']) {
        if ($Merchant['shva_transactions_merchant_number'] != $_POST['shva_standing_orders_merchant_number']) {
            if (!preg_match('/^\d{7,10}$/', $_POST['shva_standing_orders_merchant_number'])) {
                $errors['shva_standing_orders_merchant_number'] = 'invalid SHVA standing-orders merchant number; must be 10 digits long';
            }
            
            if ($_POST['processor'] == 'shva' && !preg_match('/^[a-z]{5}$/i', $_POST['shva_standing_orders_username'])) {
                $errors['shva_standing_orders_username'] = 'invalid SHVA standing-orders username; must be 5 letters long';
            }
            
            if ($_POST['processor'] == 'shva' && !$_POST['shva_standing_orders_password']) {
                $errors['shva_standing_orders_password'] = 'invalid SHVA standing-orders password';
            }
        }
        
        if (!$_POST['publickey']) {
// this may be lacking as there is no verification against a private key
            $errors['publickey'] = 'invalid public key';
        }
    }
    
    if ($_POST['support_invoices']) {
        if (!$_POST['invoices_template']) {
            $errors['invoices_template'] = 'invalid invoices template';
        }
        
        if (!preg_match('/^\d{1,9}$/', $_POST['invoices_charge_starting_number'])) {
            $errors['invoices_charge_starting_number'] = 'invalid invoices (charge) starting number';
        }
        
        if (!preg_match('/^\d{1,9}$/', $_POST['invoices_refund_starting_number'])) {
            $errors['invoices_refund_starting_number'] = 'invalid invoices (refund) starting number';
        }
    }
    
    // Init SHVA credentials.
    if ($Merchant_new && $_POST['processor'] == 'shva' && !$errors) {
        if (!merchants__shva_changePassword($_POST['shva_transactions_merchant_number'], $_POST['shva_transactions_username'], $_POST['shva_transactions_password'], hashSHVAPass($_POST['shva_transactions_username'], hashGTSPass($_POST['password'])))) {
            $errors['shva_transactions_credentials'] = 'invalid SHVA transactions credentials; initialization failed';
        }
    }
    
    if (!$errors && $_POST['support_standing_orders'] && $_POST['processor'] == 'shva') {
        // If new merchant, check that the S.O. credentials are not identical to the trans. credentials.
        // If existing merchant, check that there are no existing S.O. credentials and that the ones entered are not identical to the trans. credentials.
        $m_new_cond = $Merchant_new && $_POST['shva_transactions_merchant_number'] != $_POST['shva_standing_orders_merchant_number'];
        $m_old_cond = !$Merchant_new && !$Merchant['shva_standing_orders_merchant_number'] && $Merchant['shva_transactions_merchant_number'] != $_POST['shva_standing_orders_merchant_number'];
        
        if ($m_new_cond || $m_old_cond) {
            // If new merchant, hash and send the currently submitted (GTS) password; otherwise send the already hashed password stored in the db.
            if (!merchants__shva_changePassword($_POST['shva_standing_orders_merchant_number'], $_POST['shva_standing_orders_username'], $_POST['shva_standing_orders_password'], hashSHVAPass($_POST['shva_standing_orders_username'], $Merchant_new ? hashGTSPass($_POST['password']) : $Merchant['password']))) {
                $errors['shva_standing_orders_credentials'] = 'invalid SHVA standing-orders credentials; initialization failed';
                
                // If S.O. credentials could not be initialized and this is a new merchant, reverse the trans. credentials initialization.
                if ($Merchant_new) {
                    if (!merchants__shva_changePassword($_POST['shva_transactions_merchant_number'], $_POST['shva_transactions_username'], hashSHVAPass($_POST['shva_transactions_username'], hashGTSPass($_POST['password'])), $_POST['shva_transactions_password'])) {
                        $errors['shva_transactions_credentials'] = 'failed to undo the SHVA transactions credentials initialization';
                    }
                }
            }
        }
    }
    
    // Update SHVA credentials to match a new password.
    if (!$errors && !$Merchant_new && $_POST['processor'] == 'shva' && $_POST['password']) {
        if (!merchants__shva_changePassword($Merchant['shva_transactions_merchant_number'], $Merchant['shva_transactions_username'], hashSHVAPass($Merchant['shva_transactions_username'], $Merchant['password']), hashSHVAPass($Merchant['shva_transactions_username'], hashGTSPass($_POST['password'])))) {
            $errors['shva_transactions_credentials'] = 'failed to update SHVA transactions credentials';
        }
        
        // If S.O. credentials exist or are added in this request, update them as well (unless identical to the trans. credentials).
        if (!$errors && ($Merchant['shva_standing_orders_merchant_number'] || $_POST['support_standing_orders'])) {
            if ($Merchant['shva_standing_orders_merchant_number']) {
                $shva_standing_orders_merchant_number = $Merchant['shva_standing_orders_merchant_number'];
                $shva_standing_orders_username = $Merchant['shva_standing_orders_username'];
                $shva_standing_orders_password = hashSHVAPass($Merchant['shva_standing_orders_username'], $Merchant['password']);
            } else {
                $shva_standing_orders_merchant_number = $_POST['shva_standing_orders_merchant_number'];
                $shva_standing_orders_username = $_POST['shva_standing_orders_username'];
                $shva_standing_orders_password = $_POST['password'];
            }
            
            if ($Merchant['shva_transactions_merchant_number'] != $shva_standing_orders_merchant_number) {
                if (!merchants__shva_changePassword($shva_standing_orders_merchant_number, $shva_standing_orders_username, $shva_standing_orders_password, hashSHVAPass($shva_standing_orders_username, hashGTSPass($_POST['password'])))) {
                    $errors['shva_standing_orders_credentials'] = 'failed to update SHVA standing-orders credentials';
                    
                    if (!merchants__shva_changePassword($Merchant['shva_transactions_merchant_number'], $Merchant['shva_transactions_username'], hashSHVAPass($Merchant['shva_transactions_username'], hashGTSPass($_POST['password'])), hashSHVAPass($Merchant['shva_transactions_username'], $Merchant['password']))) {
                        $errors['shva_transactions_credentials'] = 'failed to undo the SHVA transactions credentials update';
                    }
                }
            }
        }
    }
    
    if (!$errors) {
        if ($_POST['processor'] != 'shva') {
            unset(
                $_POST['shva_transactions_username'],
                $_POST['shva_transactions_password'],
                $_POST['shva_standing_orders_username'],
                $_POST['shva_standing_orders_password']
            );
        }
        
        if (!$_POST['support_standing_orders']) {
            unset(
                $_POST['shva_standing_orders_merchant_number'],
                $_POST['shva_standing_orders_username'],
                $_POST['shva_standing_orders_password'],
                $_POST['publickey']
            );
        }
        
        if (!$_POST['support_invoices']) {
            unset(
                $_POST['invoices_charge_starting_number'],
                $_POST['invoices_refund_starting_number'],
                $_POST['invoices_template']
            );
        }
        
        $_POST_DB = array_map('mysql_real_escape_string', $_POST);
        
        if ($Merchant_new) {
            mysql_query("INSERT INTO merchants (username, password, direct_access_ip, direct_access_password, processor, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username, publickey, created, company_id, company_name, company_email, sender_name, sender_email, invoices_charge_starting_number, invoices_refund_starting_number, invoices_template, support_standing_orders, support_refund_trans, support_new_trans, allow_duplicated_transactions, note) VALUES ('$_POST_DB[username]', '" . hashGTSPass($_POST_DB['password']) . "', " . SQLNULL($_POST_DB['direct_access_ip']) . ", " . SQLNULL($_POST_DB['direct_access_password'] ? hashGTSPass($_POST_DB['direct_access_password']) : false) . ", '$_POST_DB[processor]', '$_POST_DB[shva_transactions_merchant_number]', " . SQLNULL($_POST_DB['shva_transactions_username']) . ", " . SQLNULL($_POST_DB['shva_standing_orders_merchant_number']) . ", " . SQLNULL($_POST_DB['shva_standing_orders_username']) . ", " . SQLNULL($_POST_DB['publickey']) . ", NOW(), " . SQLNULL($_POST_DB['company_id']) . ", " . SQLNULL($_POST_DB['company_name']) . ", '$_POST_DB[company_email]', " . SQLNULL($_POST_DB['sender_name']) . ", " . SQLNULL($_POST_DB['sender_email']) . ", " . SQLNULL($_POST_DB['invoices_charge_starting_number']) . ", " . SQLNULL($_POST_DB['invoices_refund_starting_number']) . ", " . SQLNULL($_POST_DB['invoices_template']) . ", '$_POST_DB[support_standing_orders]', '$_POST_DB[support_refund_trans]', '$_POST_DB[support_new_trans]', '$_POST_DB[allow_duplicated_transactions]', " . SQLNULL($_POST_DB['note']) . ")");
            $merchant_id = mysql_insert_id();
            mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('$merchant_id', '" . hashGTSPass($_POST_DB['password']) . "', NOW())");
        } else {
            mysql_query("UPDATE merchants SET username = '$_POST_DB[username]', company_id = " . SQLNULL($_POST_DB['company_id']) . ", company_name = " . SQLNULL($_POST_DB['company_name']) . ", company_email = '$_POST_DB[company_email]', sender_name = " . SQLNULL($_POST_DB['sender_name']) . ", sender_email = " . SQLNULL($_POST_DB['sender_email']) . ", invoices_charge_starting_number = " . SQLNULL($_POST_DB['invoices_charge_starting_number']) . ", invoices_refund_starting_number = " . SQLNULL($_POST_DB['invoices_refund_starting_number']) . ", invoices_template = " . SQLNULL($_POST_DB['invoices_template']) . ", support_standing_orders = '$_POST_DB[support_standing_orders]', support_refund_trans = '$_POST_DB[support_refund_trans]', support_new_trans = '$_POST_DB[support_new_trans]', allow_duplicated_transactions = '$_POST_DB[allow_duplicated_transactions]', note = " . SQLNULL($_POST_DB['note']) . " WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
            
            // If the password was changed, update the db.
            if ($_POST['password']) {
                mysql_query("UPDATE merchants SET password = '" . hashGTSPass($_POST_DB['password']) . "' WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
                mysql_query("INSERT INTO merchants_passwords_log (merchant_id, password, updated) VALUES ('" . $Merchant['merchant_id'] . "', '" . hashGTSPass($_POST_DB['password']) . "', NOW())");
            }
            
            // If the direct-access values were changed, update the db.
            if ($_POST['direct_access_ip'] && $_POST['direct_access_password']) {
                mysql_query("UPDATE merchants SET direct_access_ip = '" . $_POST_DB['direct_access_ip'] . "', direct_access_password = '" . hashGTSPass($_POST_DB['direct_access_password']) . "' WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
            } elseif ($_POST['direct_access_ip'] && !$_POST['direct_access_password']) {
                mysql_query("UPDATE merchants SET direct_access_ip = '" . $_POST_DB['direct_access_ip'] . "' WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
            } elseif (!$_POST['direct_access_ip'] && !$_POST['direct_access_password']) {
                mysql_query("UPDATE merchants SET direct_access_ip = NULL, direct_access_password = NULL WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
            }
            
            // If S.O. credentials were submitted for the first time, update the db.
            if (!$Merchant['shva_standing_orders_merchant_number'] && $_POST['support_standing_orders']) {
                if ($Merchant['shva_transactions_merchant_number'] == $_POST['shva_standing_orders_merchant_number']) {
                    mysql_query("UPDATE merchants SET shva_standing_orders_merchant_number = shva_transactions_merchant_number, shva_standing_orders_username = shva_transactions_username, publickey = '$_POST_DB[publickey]' WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
                } else {
                    mysql_query("UPDATE merchants SET shva_standing_orders_merchant_number = '$_POST_DB[shva_standing_orders_merchant_number]', shva_standing_orders_username = " . SQLNULL($_POST_DB['shva_standing_orders_username']) . ", publickey = '$_POST_DB[publickey]' WHERE merchant_id = '" . $Merchant['merchant_id'] . "'");
                }
            }
        }
        
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }
    
    $_FORM = $_POST;
}

// The following is required because these fields can be marked `disabled` and the data will not be passed in case of an error.
if ($_POST && !$Merchant_new) {
    isset($_FORM['shva_transactions_merchant_number']) || $_FORM['shva_transactions_merchant_number'] = $Merchant['shva_transactions_merchant_number'];
    isset($_FORM['shva_transactions_username']) || $_FORM['shva_transactions_username'] = $Merchant['shva_transactions_username'];
    isset($_FORM['shva_standing_orders_merchant_number']) || $_FORM['shva_standing_orders_merchant_number'] = $Merchant['shva_standing_orders_merchant_number'];
    isset($_FORM['shva_standing_orders_username']) || $_FORM['shva_standing_orders_username'] = $Merchant['shva_standing_orders_username'];
    isset($_FORM['invoices_charge_starting_number']) || $_FORM['invoices_charge_starting_number'] = $Merchant['invoices_charge_starting_number'];
    isset($_FORM['invoices_refund_starting_number']) || $_FORM['invoices_refund_starting_number'] = $Merchant['invoices_refund_starting_number'];
    isset($_FORM['sender_name']) || $_FORM['sender_name'] = $Merchant['sender_name'];
    isset($_FORM['sender_email']) || $_FORM['sender_email'] = $Merchant['sender_email'];
}

if ($_FORM) {
    $_FORM = array_map('htmlspecialchars', $_FORM);
}

if ($errors) {
    foreach ($errors as $input => $error) {
        $err[$input] = 'style="color: #FF0000; cursor: help;" title="' . str_replace('"', '\"', $error) . '"><span style="margin-right: 5px; font-size: 12px; color: #CC0033;">&#10008;</span';
    }
}

##

$maxMerchantNumber = 0;
for ($i = 0; $i != count($merchants); $i++) {
    if ($merchants[$i]['shva_transactions_merchant_number'] > $maxMerchantNumber) {
        $maxMerchantNumber = $merchants[$i]['shva_transactions_merchant_number'];
    } elseif ($merchants[$i]['shva_standing_orders_merchant_number'] > $maxMerchantNumber) {
        $maxMerchantNumber = $merchants[$i]['shva_standing_orders_merchant_number'];
    }
}

##

set_time_limit(0);

header('Content-Type: text/html; charset=UTF-8');

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>GTS Merchants</title>
    
    <style type="text/css">
    * {
        font-family: Verdana;
        font-size: 11px;
    }
    
    body {
        padding: 0;
        margin: 0;
    }
    
    a {
        color: #666666;
        text-decoration: none;
    }
    a:hover {
        color: #000000;
    }
    
    /* ## */
    
    #merchant-form {
    }
    
    #merchant-form td.caption {
        font-weight: bold;
        font-variant: small-caps;
    }
    
    #merchant-form td {
        border-bottom: 1px solid #CCCCCC;
    }
    
    #merchant-form input,
    #merchant-form select {
        width: 150px;
    }
    
    #merchant-form textarea {
        width: 150px;
        height: 50px;
    }
    
    .err {
        width: 15px;
        float: left;
        font-size: 12px;
        color: #CC0033;
        cursor: help;
    }
    
    /* ## */
    
    #merchant-list {
        width: 100%;
    }
    
    #merchant-list th {
        border-top: 2px solid #CCCCCC;
        border-bottom: 2px solid #CCCCCC;
        background-color: none;
        font-variant: small-caps;
        text-align: left;
    }
    
    #merchant-list tr.item td {
        border-bottom: 1px solid #CCCCCC;
    }
    
    #merchant-list tr.item:hover {
        background-color: #E8EEF7;
    }
    
    #merchant-list th, #merchant-list td {
        padding: 5px 10px 5px 10px;
    }
    
    #merchant-list span#processors-summary {
        font-weight: normal;
        cursor: help;
    }
    </style>
    
    <script type="text/javascript">
    function toggle_processor() {
        if (document.getElementById('processor').value == 'shva') {
            var disabled = false;
        } else {
            var disabled = true;
        }
        
        document.getElementById('shva_transactions_username').disabled = disabled;
        document.getElementById('shva_transactions_password').disabled = disabled;
        document.getElementById('shva_standing_orders_username').disabled = disabled;
        document.getElementById('shva_standing_orders_password').disabled = disabled;
    }
    
    function toggle_support_standing_orders() {
        <? if ($Merchant_new || !$Merchant['shva_standing_orders_merchant_number']): ?>
        var disabled = document.getElementById('support_standing_orders').checked == false;
        <? else: ?>
        var disabled = true;
        <? endif; ?>
        
        document.getElementById('shva_standing_orders_merchant_number').disabled = disabled;
        if (document.getElementById('processor').value == 'shva') {
            document.getElementById('shva_standing_orders_username').disabled = disabled;
            if (document.getElementById('shva_standing_orders_password')) document.getElementById('shva_standing_orders_password').disabled = disabled;
            if (document.getElementById('publickey')) document.getElementById('publickey').disabled = disabled;
        }
    }
    
    function toggle_support_invoices() {
        var disabled = document.getElementById('support_invoices').checked == false;
        
        document.getElementById('invoices_charge_starting_number').disabled = disabled;
        document.getElementById('invoices_refund_starting_number').disabled = disabled;
        document.getElementById('invoices_template').disabled = disabled;
    }
    
    function txtdir(element) {
        element.style.direction = /[\u05d0-\u05ea]/.test(element.value) ? 'rtl' : 'ltr';
    }
    
    function generatePassword(length) {
        var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz';
		var password = '';
		for (var i = 0; i < length; i++) {
			var rnum = Math.floor(Math.random()*chars.length);
			password += chars.substring(rnum, rnum+1);
		}
		return password;
        /*
        for (var i = 0, rndNum; i < length; i++) {
            rndNum = Math.random();
            rndNum = parseInt(rndNum * 1000);
            rndNum = (rndNum % 94) + 33;
            
            if ((rndNum >= 45 && rndNum <= 46) || (rndNum >= 48 && rndNum <= 57) || (rndNum >= 64 && rndNum <= 90) || (rndNum >= 97 && rndNum <= 122)) {
                password = password + String.fromCharCode(rndNum);
            } else {
                i--;
            }
        }
        return password;
        */
    }
    
    <? if ($Merchant_new || $Merchant): ?>
    window.onload = function () {
        txtdir(document.getElementById('company_name'));
        txtdir(document.getElementById('sender_name'));
        toggle_processor();
        toggle_support_standing_orders();
        toggle_support_invoices();
    }
    <? endif; ?>
    </script>
</head>

<body>

<table style="width: 100%; height: 100%;">
    <tr>
        <td align="center">

<table cellspacing="15" cellpadding="0">
    <tr>
        <td style="padding-bottom: 10px; text-align: center;"><a href="<?=$_SERVER['SCRIPT_NAME']?>" style="font-family: Times New Roman; font-size: 18pt; font-weight: bold; font-style: italic; color: #669ACC; text-decoration: none;">Gyro Transactions Server - Merchants</a></td>
    </tr>
    
    <tr>
        <td>
            <? if ($Merchant || $Merchant_new): ?>
            
            <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="merchant_id" value="<?=($Merchant_new ? 'new' : $_FORM['merchant_id'])?>">
            <? if (!$Merchant_new): ?>
            <input type="hidden" name="processor" value="<?=$_FORM['processor']?>">
            <? endif; ?>
            
            <table id="merchant-form" cellpadding="5" cellspacing="0" align="center">
                <? if ($Merchant_new): ?>
                <tr>
                    <td class="caption" <?=$err['processor']?>>processor</td>
                    <td>
                        <? $processor[$_FORM['processor']] = 'selected'; ?>
                        <select id="processor" name="processor" onchange="toggle_processor()">
                            <option value="shva" <?=$processor['shva']?>>shva</option>
                            <option value="creditguard" <?=$processor['creditguard']?>>creditguard</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <? endif; ?>
                
                <tr>
                    <td class="caption" <?=$err['username']?>>username</td>
                    <td><input type="text" name="username" maxlength="10" value="<?=$_FORM['username']?>"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['password']?>>
                        <?=($Merchant_new ? 'password' : 'new password')?>
                        <a href="" onclick="document.getElementById('password').value = generatePassword(6); return false;" onfocus="this.blur()" style="display: block; float: right;">[ generate ]</a>
                    </td>
                    <td><input type="text" id="password" name="password" maxlength="40" value="<?=$_FORM['password']?>"></td>
                </tr>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['direct_access_ip']?>>direct-access ip</td>
                    <td><input type="text" name="direct_access_ip" maxlength="15" value="<?=$_FORM['direct_access_ip']?>"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['direct_access_password']?>>direct-access password</td>
                    <td><input type="text" name="direct_access_password" maxlength="32" value="<?=$_FORM['direct_access_password']?>"></td>
                </tr>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['company_id']?>>company id</td>
                    <td><input type="text" name="company_id" maxlength="10" value="<?=$_FORM['company_id']?>"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['company_name']?>>company name</td>
                    <td><input type="text" id="company_name" name="company_name" maxlength="100" value="<?=$_FORM['company_name']?>" onkeydown="txtdir(this)"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['company_email']?>>company email</td>
                    <td><input type="text" id="company_email" name="company_email" maxlength="50" value="<?=$_FORM['company_email']?>"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['sender_name']?>>sender name</td>
                    <td><input type="text" id="sender_name" name="sender_name" maxlength="25" value="<?=$_FORM['sender_name']?>" onkeydown="txtdir(this)"></td>
                </tr>
                <tr>
                    <td class="caption nested" <?=$err['sender_email']?>>sender email</td>
                    <td><input type="text" id="sender_email" name="sender_email" maxlength="50" value="<?=$_FORM['sender_email']?>"></td>
                </tr>
                
                <? if ($Merchant['processor'] == 'shva' || $Merchant_new): ?>
                
                <tr>
                    <td colspan="2" <?=$err['shva_transactions_credentials']?>><?=$errors['shva_transactions_credentials']?><br></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['shva_transactions_merchant_number']?>>shva transactions merchant number</td>
                    <td><input type="text" id="shva_transactions_merchant_number" name="shva_transactions_merchant_number" maxlength="10" value="<?=$_FORM['shva_transactions_merchant_number']?>" <?=($Merchant_new ? false : 'disabled')?>></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['shva_transactions_username']?>>shva transactions username</td>
                    <td><input type="text" id="shva_transactions_username" name="shva_transactions_username" maxlength="10" value="<?=$_FORM['shva_transactions_username']?>" <?=($Merchant_new ? false : 'disabled')?>></td>
                </tr>
                <? if ($Merchant_new): ?>
                <tr>
                    <td class="caption" <?=$err['shva_transactions_password']?>>shva transactions password</td>
                    <td><input type="text" id="shva_transactions_password" name="shva_transactions_password" maxlength="40" value="<?=$_FORM['shva_transactions_password']?>"></td>
                </tr>
                <? endif; ?>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption"><label for="support_refund_trans">support refund transactions</label></td>
                    <td><input type="checkbox" id="support_refund_trans" name="support_refund_trans" value="1" <?=($_FORM['support_refund_trans'] ? 'checked' : false)?> style="margin: 0; width: auto;"></td>
                </tr>
                <tr>
                    <td class="caption"><label for="support_new_trans">support new transactions</label></td>
                    <td><input type="checkbox" id="support_new_trans" name="support_new_trans" value="1" <?=($_FORM['support_new_trans'] ? 'checked' : false)?> style="margin: 0; width: auto;"></td>
                </tr>
                
                <tr>
                    <td colspan="2" <?=$err['shva_support_standing_credentials']?>><?=$errors['shva_support_standing_credentials']?><br></td>
                </tr>
                <tr>
                    <td class="caption"><label for="support_standing_orders">support standing-orders</label></td>
                    <td><input type="checkbox" id="support_standing_orders" name="support_standing_orders" value="1" onchange="toggle_support_standing_orders()" <?=($_FORM['support_standing_orders'] ? 'checked' : false)?> style="margin: 0; width: auto;"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['shva_standing_orders_merchant_number']?>>- shva standing-orders merchant number</td>
                    <td><input type="text" id="shva_standing_orders_merchant_number" name="shva_standing_orders_merchant_number" maxlength="10" value="<?=$_FORM['shva_standing_orders_merchant_number']?>" <?=($Merchant_new ? false : 'disabled')?>></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['shva_standing_orders_username']?>>- shva standing-orders username</td>
                    <td><input type="text" id="shva_standing_orders_username" name="shva_standing_orders_username" maxlength="10" value="<?=$_FORM['shva_standing_orders_username']?>" <?=($Merchant_new ? false : 'disabled')?>></td>
                </tr>
                <? if (!$Merchant['shva_standing_orders_merchant_number']): ?>
                <tr>
                    <td class="caption" <?=$err['shva_standing_orders_password']?>>- shva standing-orders password</td>
                    <td><input type="text" id="shva_standing_orders_password" name="shva_standing_orders_password" maxlength="40" value="<?=$_FORM['shva_standing_orders_password']?>"></td>
                </tr>
                <tr>
                    <td class="caption nested" <?=$err['publickey']?>>- public key</td>
                    <td><input type="text" id="publickey" name="publickey" value="<?=$_FORM['publickey']?>"></td>
                </tr>
                <? endif; ?>
                
                <? endif; ?>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption"><label for="support_invoices">support invoices</label></td>
                    <td><input type="checkbox" id="support_invoices" name="support_invoices" value="1" onchange="toggle_support_invoices()" <?=($_FORM['invoices_template'] ? 'checked' : false)?> style="margin: 0; width: auto;"></td>
                </tr>
                <tr>
                    <td class="caption" <?=$err['invoices_charge_starting_number']?>>- starting number (charge)</td>
                    <td><input type="text" id="invoices_charge_starting_number" name="invoices_charge_starting_number" maxlength="9" value="<?=($_FORM['invoices_charge_starting_number'] ? $_FORM['invoices_charge_starting_number'] : '000500000')?>"></td>
                </tr>
                <tr>
                    <td class="caption nested" <?=$err['invoices_refund_starting_number']?>>- starting number (refund)</td>
                    <td><input type="text" id="invoices_refund_starting_number" name="invoices_refund_starting_number" maxlength="9" value="<?=($_FORM['invoices_refund_starting_number'] ? $_FORM['invoices_refund_starting_number'] : '000700000')?>"></td>
                </tr>
                <tr>
                    <td class="caption nested" <?=$err['invoices_template']?>>- template</td>
                    <td><input type="text" id="invoices_template" name="invoices_template" maxlength="50" value="<?=($_FORM['invoices_template'] ? $_FORM['invoices_template'] : null)?>"></td>
                </tr>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption"><label for="allow_duplicated_transactions">allow duplicated transactions</label></td>
                    <td><input type="checkbox" id="allow_duplicated_transactions" name="allow_duplicated_transactions" value="1" <?=($_FORM['allow_duplicated_transactions'] ? 'checked' : false)?> style="margin: 0; width: auto;"></td>
                </tr>
                
                <tr>
                    <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td class="caption">note</td>
                    <td><textarea name="note" style="width: auto;"><?=$_FORM['note']?></textarea></td>
                </tr>
            </table>
            
            <div style="margin-top: 25px; text-align: center;">
                <input type="submit" value="save">
                <? if (!$Merchant_new): ?>
                <input type="button" value="delete" onclick="if (confirm('Are you sure you want to delete this merchant?')) window.location = '?action=delete&merchant=<?=$_FORM['merchant_id']?>'">
                <? endif; ?>
            </div>
            
            </form>
            
            <? else: ?>
            
            <script src="/console/boxover.js" type="text/javascript"></script>
            
            <div style="margin-bottom: 15px; font-variant: small-caps; color: #666; text-align: center;">
                last merchant number: <span style="font-size: 10px;"><?=substr($maxMerchantNumber, 0, 7)?></span>
            </div>
            
            <div style="margin-bottom: 15px; text-align: center;">
                <form method="GET">
                <input type="hidden" name="merchant" value="new">
                <input type="submit" value="add merchant">
                </form>
            </div>
            
            <table id="merchant-list" cellpadding="0" cellspacing="0">
                <tr>
                    <th>username</th>
                    <th>processor <span id="processors-summary" title="<?=$processors_summary?>">[?]</span></th>
                    <th title="Direct Access">d.a.</th>
                    <th>trans. merch.</th>
                    <th>s.o. merch.</th>
                    <th>company id</th>
                    <th>company name</th>
                    <th>company email</th>
                    <th>invoices</th>
                    <th title="Standing Orders">s.o.</th>
                    <th>note</th>
                </tr>
                
                <? for ($i = 0; $i != count($merchants); $i++): ?>
                <tr class="item">
                    <td><a href="?merchant=<?=$merchants[$i]['merchant_id']?>"><?=$merchants[$i]['username']?></a></td>
                    <td><?=$merchants[$i]['processor']?></td>
                    <td style="text-align: center;"><?=($merchants[$i]['direct_access_ip'] ? '&#10003;' : '-')?></td>
                    <td style="text-align: center;" title="<?=$merchants[$i]['shva_transactions_username']?>"><?=($merchants[$i]['shva_transactions_merchant_number'] ? substr($merchants[$i]['shva_transactions_merchant_number'], 0, 7) : '<div style="text-align: center;">-</div>')?></td>
                    <td title="<?=$merchants[$i]['shva_standing_orders_username']?>"><?=($merchants[$i]['shva_standing_orders_merchant_number'] ? substr($merchants[$i]['shva_standing_orders_merchant_number'], 0, 7) : '<div style="text-align: center;">-</div>')?></td>
                    <td><?=($merchants[$i]['company_id'] ? str_pad($merchants[$i]['company_id'], 9, '0', STR_PAD_LEFT) : '<div style="text-align: center;">-</div>')?></td>
                    <td><?=($merchants[$i]['company_name'] ? $merchants[$i]['company_name'] : '<div style="text-align: center;">-</div>')?></td>
                    <td><?=($merchants[$i]['company_email'])?></td>
                    <td style="text-align: center;"><?=($merchants[$i]['invoices_template'] ? '&#10003;' : '-')?></td>
                    <td style="text-align: center;"><?=($merchants[$i]['support_standing_orders'] ? '&#10003;' : '-')?></td>
                    <td style="text-align: center;"><?=($merchants[$i]['note'] ? '<img src="/console/images/boxover-info.gif" title="header=[Note] body=[' . str_replace(array("'", '"', ']', '[', "\r", "\n"), array('&apos;', '&quot;', ']]', '[[', '', '<br>'), $merchants[$i]['note']) . ']" style="cursor: pointer;">' : '-')?></td>
                </tr>
                <? endfor; ?>
            </table>
            
            <div style="margin-top: 15px; font-variant: small-caps; color: #666; text-align: center;">
                last merchant number: <span style="font-size: 10px;"><?=substr($maxMerchantNumber, 0, 7)?></span>
            </div>
            
            <div style="margin-top: 15px; text-align: center;">
                <form method="GET">
                <input type="hidden" name="merchant" value="new">
                <input type="submit" value="add merchant">
                </form>
            </div>
            
            <? endif; ?>
        </td>
    </tr>
</table>

        </td>
    </tr>
</table>

</body>
</html>

<?php

// Functions.

function merchants__shva_changePassword($merchant_number, $username, $password, $password_new) {
    $client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
    $return = $client->ChangePassword((object) array(
        'MerchantNumber' => $merchant_number,
        'UserName' => $username,
        'Password' => $password,
        'NewPassword' => $password_new
    ));
    
    return ($return->ChangePasswordResult == 0);
}

?>
