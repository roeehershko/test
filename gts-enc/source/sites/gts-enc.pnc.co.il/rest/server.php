<?php

require('../config.inc.php');
require('../aes.inc.php');

$help = $_GET['help'];
$func = preg_match('/^[_a-z0-9\.]+$/i', $_GET['func']) && file_exists('functions/' . $_GET['func'] . '.inc.php') ? $_GET['func'] : null;
$args = json_decode(file_get_contents("php://input"));

$_SERVER['PHP_AUTH_USER'] && $args->username = $_SERVER['PHP_AUTH_USER'];
$_SERVER['PHP_AUTH_PW'] && $args->password = $_SERVER['PHP_AUTH_PW'];

if ($help) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<style tyle="text/css">* { font-family: Verdana; font-size: 14px; } p { margin-bottom: 25px; } a, div { font-family: Courier New; font-weight: bold; } li { margin: 5px; } span { font-family: Courier New; }</style>' . "\n\n";
    
    if ($func) {
        include 'functions/' . $func . '.inc.php';
        
        echo '<div>[ <a href="/rest/help">GTS REST API</a> ]</div><br>' . "\n\n";
        echo '<div>[ <a href="/rest/help/' . $func . '">' . $func . '</a> ]</div><br>' . "\n\n";
        
        if (${$func . 'Description'}) {
            echo '<p>' . ${$func . 'Description'} . '</p>' . "\n\n";
        }
        
        echo '<div>' . $func . 'Request' . '</div>' . "\n\n";
        echo styleFuncDefinition(${$func . 'Request'}) . "\n\n";
        
        echo '<div>' . $func . 'Response' . '</div>' . "\n\n";
        echo styleFuncDefinition(${$func . 'Response'}) . "\n\n";
    } else {
        echo '<div>[ GTS REST API ]</div>' . "\n\n";
        
        if ($dh = opendir('functions')) {
            $rows = array();
            while (false !== ($file = readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    $func = substr($file, 0, -8);
                    $rows[] = '<li><a href="help/' . $func . '">' . $func . '</a></li>';
                }
            }
            closedir($dh);
            
            sort($rows);
            echo '<ul>' . implode("\n", $rows) . '</ul>' . "\n";
        }
    }
} else {
    if ($func) {
        include 'functions/' . $func . '.inc.php';
        
        $response = call_user_func($func, $args);
    } else {
        $response = array(
            'result' => 'FAIL',
            'error' => err(3)
        );
    }
    
    header('Content-Type: application/json; charset=UTF-8');
    exit(json_encode(strip_nulls($response)));
}

// Functions

function connectDB(&$error = false) {
    if (@mysql_connect(constant('DB_SERVER'), constant('DB_USERNAME'), constant('DB_PASSWORD')) && @mysql_select_db(constant('DB_NAME'))) {
        mysql_query("SET NAMES utf8");
        return true;
    } else {
        $error = 5;
        return false;
    }
}

function hashGTSPass($password) {
    return sha1($password . constant('SECRET'));
}

function hashSHVAPass($SHVA_username, $GTS_password_hashed) {
    return substr(md5($SHVA_username . constant('SECRET') . $GTS_password_hashed), 8, 16);
}

function authenticateUser($username, $password, &$error) {
    if (connectDB($connectDB__error)) {
        if (strlen($password) == 40 && ($user = mysql_fetch_assoc(mysql_query("SELECT id, user_id, username, UNIX_TIMESTAMP(expiration) AS expiration FROM users_access_tokens LEFT JOIN users USING (user_id) WHERE access_token = '" . mysql_real_escape_string($password) . "' AND username = '" . mysql_real_escape_string($username) . "' AND `terminated` = '0' AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(expiration) < 43200")))) {
            // Authenticating using Users Access Tokens
            $sql_query = mysql_query(("SELECT merchant_id FROM merchants_users WHERE user_id = '" . mysql_real_escape_string($user['user_id'])."'"));
            while ($sql = @mysql_fetch_object($sql_query)) {
                if ($merchant = mysql_fetch_assoc(mysql_query("SELECT merchant_id, username, processor, password, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username, support_recurring_orders, allow_duplicated_transactions, params FROM merchants WHERE merchant_id = '" . mysql_real_escape_string($sql->merchant_id) . "'"))) {
                    $merchant['params'] = unserialize($merchant['params']);
                    $merchants[] = $merchant;   
                }
            }
        } else if (strlen($password) == 40 && ($sql = mysql_fetch_assoc(mysql_query("SELECT id, merchant_id, username, processor, password, UNIX_TIMESTAMP(expiration) AS expiration, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username, access_token_ttl, support_recurring_orders, allow_duplicated_transactions, support_refund_trans, support_new_trans, params FROM merchants_access_tokens LEFT JOIN merchants USING (merchant_id) WHERE access_token = '" . mysql_real_escape_string($password) . "' AND username = '" . mysql_real_escape_string($username) . "' AND `terminated` = '0' AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(expiration) < 43200")))) {
            // Authenticating using Merchants Access Tokens
            if ($sql['expiration'] < time()) {
                $error = 7;
                return false;
            } else {
                mysql_query("UPDATE merchants_access_tokens SET expiration = NOW() + INTERVAL $sql[access_token_ttl] MINUTE WHERE id = '$sql[id]'");
            }
            
            $merchants[0] = array(
                'merchant_id' => $sql['merchant_id'],
                'username' => $sql['username'],
                'processor' => $sql['processor'],
                'shva_transactions_merchant_number' => $sql['shva_transactions_merchant_number'],
                'shva_transactions_username' => $sql['shva_transactions_username'],
                'shva_transactions_password' => hashSHVAPass($sql['shva_transactions_username'], $sql['password']),
                'shva_standing_orders_merchant_number' => $sql['shva_standing_orders_merchant_number'],
                'shva_standing_orders_username' => $sql['shva_standing_orders_username'],
                'shva_standing_orders_password' => hashSHVAPass($sql['shva_standing_orders_username'], $sql['password']),
                'allow_duplicated_transactions' => $sql['allow_duplicated_transactions'],
                'support_recurring_orders' => (bool) $sql['support_recurring_orders'],
                'support_refund_trans' => (bool) $sql['support_refund_trans'],
                'support_new_trans' => (bool) $sql['support_new_trans'],
                'params' => unserialize($sql['params']),
            );    
        }
        
        if (!empty($merchants)) {
            return $merchants;    
        } else {
            $error = 7;
            return false;
        }
        
    } else {
        $error = $connectDB__error;
        return false;
    }
}

function authInvoice($transaction_id) {
    return substr(md5($transaction_id . constant('SECRET') . $transaction_id), 7, 16);
}

function authVoucher($transaction_id) {
    return substr(md5($transaction_id . constant('SECRET') . $transaction_id), 7, 16);
}

function authSignature($transaction_id) {
    return substr(md5($transaction_id . constant('SECRET') . $transaction_id), 7, 16);
}

function generateInvoice($transaction_id) {
    if ($trans = mysql_fetch_object(mysql_query("SELECT m.sender_email, m.sender_name, t.merchant_id, t.trans_id, t.type, t.amount FROM trans AS t LEFT JOIN merchants AS m USING (merchant_id) LEFT JOIN trans_invoices AS ti USING (trans_id) WHERE m.invoices_template IS NOT NULL AND t.trans_id = '" . mysql_real_escape_string($transaction_id) . "' AND t.status = 'pending' AND ti.trans_id IS NOT NULL AND ti.number IS NULL AND (SELECT 1 FROM trans_credit WHERE trans_id = ti.trans_id AND j5 = '1') IS NULL"))) {
        $invoice_number = @mysql_result(mysql_query("SELECT IFNULL((SELECT MAX(ti.number) + 1 FROM trans AS t LEFT JOIN trans_invoices AS ti USING (trans_id) WHERE t.merchant_id = '" . $trans->merchant_id . "' AND " . ($trans->amount < 0 ? "t.amount < 0" : "t.amount > 0") . "), (SELECT " . ($trans->amount < 0 ? "invoices_refund_starting_number" : "invoices_charge_starting_number") . " FROM merchants WHERE merchant_id = '" . $trans->merchant_id . "'))"), 0);
        if (!$invoice_number) {
            $invoice_number = 1;
        }
        
        if ($trans->type != 'credit') {
            mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $trans->trans_id . "'");
        }
        
        mysql_query("UPDATE trans_invoices SET number = '$invoice_number' WHERE trans_id = '" . $trans->trans_id . "'");
        $invoice_number = @mysql_result(mysql_query("SELECT number FROM trans_invoices WHERE trans_id = '" . $trans->trans_id . "'"), 0);
        
        // Email invoice.
        $recipients = false;
        $sql_query = mysql_query("SELECT email FROM trans_invoices_recipients WHERE trans_id = '" . $trans->trans_id . "'");
        while ($sql = mysql_fetch_object($sql_query)) {
            $recipients[] = $sql->email;
        }
        
        if ($recipients) {
            require_once '/var/www/apps/phpmailer/class.phpmailer.php';
            $mail = new PHPMailer();
            $mail->From = $trans->sender_email;
            $mail->FromName = $trans->sender_name;
            $mail->Subject = 'Invoice #' . $trans->trans_id;
            
            foreach ($recipients as $recipient) {
                $mail->AddAddress($recipient);
            }
            
            $invoice_link = 'https://'.constant('GTS_APP_HOST').'/invoices/' . authInvoice($trans->trans_id) . '/' . $trans->trans_id . '.pdf';
            
            ob_start();
            localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/transaction-invoice.inc.php', array(
                'merchant_id' => $trans->merchant_id,
                'refund' => ($trans->amount < 0),
                'link' => $invoice_link
            ));
            $mail->Body = ob_get_clean();
            
            $mail->IsSMTP();
            $mail->Host = '127.0.0.1';
            $mail->CharSet = 'UTF-8';
            $mail->IsHTML(true);
            $mail->Send();
        }
        
        return $invoice_number;
    }
    
    return false;
}

function localscopeinclude($filename, $variables) {
    foreach ($variables as $name => $value) {
        $$name = $value;
    }
    
    include($filename);
}

function shva_encrypt($source, $publickey) {
    $maxlength = 245;
    while ($source) {
        $input = substr($source, 0, $maxlength);
        $source = substr($source, $maxlength);
        
        openssl_public_encrypt($input, $encrypted, $publickey);
        
        $output .= $encrypted;
    }
    
    return base64_encode($output);
}

function shva_decrypt($source, $privatekey, $passphrase) {
    $source = base64_decode($source);
    
    $maxlength = 256;
    while ($source){
        $input = substr($source, 0, $maxlength);
        $source = substr($source, $maxlength);
        
        openssl_private_decrypt($input, $decrypted, array($privatekey, $passphrase));
        
        $output .= $decrypted;
    }
    
    return $output;
}

function identifyCCType($cc_number) {
    if (preg_match('/^5[1-5]\d{14}$/', $cc_number)) {
        // All MasterCard numbers start with the numbers 51 through 55. All have 16 digits.
        return 'mastercard';
    } elseif (preg_match('/^4\d{15}$|^4\d{12}$/', $cc_number)) {
        // All Visa card numbers start with a 4. New cards have 16 digits. Old cards have 13.
        return 'visa';
    } elseif (preg_match('/^3[47]\d{13}$/', $cc_number)) {
        // American Express card numbers start with 34 or 37 and have 15 digits.
        return 'american-express';
    } elseif (preg_match('/^30[0-5]\d{11}$|^3[68]\d{12}$/', $cc_number)) {
        // Diners Club card numbers begin with 300 through 305, 36 or 38. All have 14 digits. There are Diners Club cards that begin with 5 and have 16 digits.
        // These are a joint venture between Diners Club and MasterCard, and should be processed like a MasterCard.
        return 'diners-club';
    } elseif (preg_match('/^6011\d{12}$|^65\d{14}$/', $cc_number)) {
        // Discover card numbers begin with 6011 or 65. All have 16 digits.
        return 'discover';
    } elseif (preg_match('/^35\d{14}$|^2131\d{11}$|^1800{11}$/', $cc_number)) {
        // JCB cards beginning with 2131 or 1800 have 15 digits. JCB cards beginning with 35 have 16 digits.
        return 'jcb';
    } elseif (preg_match('/^\d{8,9}$/', $cc_number)) {
        return 'isracard';
    } else {
        return false;
    }
}

function SQLNULL($value) {
    return (isset($value) && $value != '') ? "'$value'" : "NULL";
}

function err($e, $args = array()) {
    include '../errors.inc.php';
    
    $lang = in_array($_GET['lang'], array('en', 'he', 'ar', 'ru')) ? $_GET['lang'] : 'en';
    /*
    $subject = isset($errors[$e]) ? str_pad($e, 3, '0', STR_PAD_LEFT) . ' ' . ($errors[$e][$lang] ? @vsprintf($errors[$e][$lang], $args) : @vsprintf($errors[$e]['en'], $args)) : $e;
    $body = file_get_contents("php://input");
    mail('anton+GTSLogErr@pnc.co.il', $_GET['func'] . ': ' . $subject, $body);
    */
    if (isset($errors[$e])) {
        return (string) str_pad($e, 3, '0', STR_PAD_LEFT) . ' ' . ($errors[$e][$lang] ? @vsprintf($errors[$e][$lang], $args) : @vsprintf($errors[$e]['en'], $args));
    } else {
        return (string) $e;
    }
}

class ErrObj {
    public function __construct($code) {
        header('Content-Type: application/json; charset=UTF-8');
        exit(json_encode(strip_nulls((object) array(
            'result' => 'FAIL',
            'error' => (object) array(
                'code' => (string) $code
            )
        ))));
    }
}

/* ## */

function generate_email_template($title, $name, $content, $processor = false, $params = false) {
    
    $direction = 'rtl';
    $align = 'right';
    $template =     '<div id="newsletter" dir="'.$direction.'" style="direction:'.$direction.';text-align:'.$align.';padding:15px;font-family:Arial;font-size:14px;background:#EFEFEF">';
    $template .=        '<div>';
    $template .=            '<div align="'.($align == 'right' ? 'left' : 'right').'" style="padding:0 0 10px 0">';
    $template .=                '<a href="https://'.constant('GTS_APP_HOST').'/he/console/gtc-login.php" target="_blank">';
    $template .=            '<div align="'.($align == 'right' ? 'left' : 'right').'" style="padding:0 0 10px 0">';
    if ($processor == 'shva') {
        $template .=            '<a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions" target="_blank"><img height="70" style="height:70px" src="https://'.constant('GTS_CON_HOST').'/images/gts/default/logo.png" border="0"></a>';   
    } else if ($params->merchant_type == 'ישראכרט') {
        $template .=            '<a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions" target="_blank"><img height="70" style="height:70px" src="https://'.constant('GTS_CON_HOST').'/images/gts/isracard/logo.png" border="0"></a>';
    } else if ($params->merchant_type == 'וריפון') {
        $template .=            '<a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions" target="_blank"><img height="70" style="height:70px" src="https://'.constant('GTS_CON_HOST').'/images/gts/verifone/logo.png" border="0"></a>';  
    }
    $template .=            '</div>';
    if ($title) {
        $template .=        '<div dir="'.$direction.'" style="direction:'.$direction.';text-align:'.$align.';font-size:20px;font-weight:bold;padding:0 0 10px 0">'.$title.'</div>';
    }
    if ($name) {
        $template .=        '<div dir="'.$direction.'" style="direction:'.$direction.';text-align:'.$align.';font-weight:bold">שלום '.$name.',</div><br>';
    }
    $template .=        '</div>';
    $template .=        '<div style="padding:15px;margin:0;background:#FFF">';
    if ($content) {
        $template .=    str_replace('<p>', '<p style="padding:0;margin:0">', $content);
    }
    $template .=        '</div>';
    $template .=    '</div>';
    
    return $template;
}

function exportUserTransactions($transactions, $merchants) {
    function csv_entities($str) {
        return str_replace('"', '""', $str);
    }
    
    if (!empty($merchants)) {
        foreach ($merchants as $merchant) {
            $merchants_array[$merchant['merchant_id']] = $merchant;
        }
    }
    
    //header('Content-Type: application/vnd.ms-excel');
    //header('Content-Disposition: attachment; filename="transactions.csv"');
    
    //echo iconv('UTF-8', 'WINDOWS-1255',
    echo(
        '"Trans ID",' .
        '"Merchant ID",' .
        '"Merchant Username",' .
        '"Date",' .
        '"Time",' .
        '"Type",' .
        '"Status",' .
        '"Amount",' .
        '"Currency",' .
        '"CC Holder Name",' .
        '"CC Last 4",' .
        '"CC Exp.",' .
        '"CC Type",' .
        '"Payments",' .
        '"Approval",' .
        '"Voucher",' .
        '"Reference",' .
        '"Check Number",' .
        '"Bank Number",' .
        '"Branch Number",' .
        '"Account Number",' .
        '"Invoice Number",' .
        '"Customer Name",' .
        '"Customer Number",' .
        '"Street",' .
        '"Zip Code",' .
        '"City",' .
        '"Phone",' .
        '"Description",' .
        '"Recipients",' .
        '"Link",' .
        '"Note",' .
        "\n");
    
    foreach ($transactions as $transaction) {
        $transaction = $transaction->data;
        if ($transaction->type == 'credit') {
            if ($transaction->credit_data->credit_terms == 'payments' || $transaction->credit_data->credit_terms == 'payments-club') {
                $payments = $transaction->credit_data->payments_number . ' regular payments';
            } elseif ($transaction->credit_data->credit_terms == 'supercredit' || $transaction->credit_data->credit_terms == 'payments-credit') {
                $payments = $transaction->credit_data->payments_number . ' credit payments';
            } else {
                $payments = 'single payment';
            }
        } else {
            $payments = null;
        }
        
        //echo iconv('UTF-8', 'WINDOWS-1255',
        echo(
            '"' . csv_entities($transaction->trans_id) . '",' .
            '"' . csv_entities($transaction->merchant_id) . '",' .
            '"' . csv_entities($merchants_array[$transaction->merchant_id]['username']) . '",' .
            '"' . csv_entities(substr($transaction->timestamp, 0, 10)) . '",' .
            '"' . csv_entities(substr($transaction->timestamp, 11, 5)) . '",' .
            '"' . csv_entities($transaction->type) . '",' .
            '"' . csv_entities($transaction->status) . '",' .
            '"' . csv_entities(number_format($transaction->amount, 2)) . '",' .
            '"' . csv_entities($transaction->currency) . '",' .
            '"' . csv_entities($transaction->credit_data->cc_holder_name) . '",' .
            '"' . csv_entities($transaction->credit_data->cc_last_4) . '",' .
            '"' . csv_entities($transaction->credit_data->cc_exp ? date('m/Y', mktime(0, 0, 0, substr($transaction->credit_data->cc_exp, 0, 2), 1, substr($transaction->credit_data->cc_exp, 2, 2))) : '') . '",' .
            '"' . csv_entities($transaction->credit_data->cc_type) . '",' .
            '"' . csv_entities($payments) . '",' .
            '"' . csv_entities($transaction->credit_data->authorization_number) . '",' .
            '"' . csv_entities($transaction->credit_data->voucher_number) . '",' .
            '"' . csv_entities($transaction->credit_data->reference_number) . '",' .
            '"' . csv_entities($transaction->check_data->check_number) . '",' .
            '"' . csv_entities($transaction->check_data->bank_number) . '",' .
            '"' . csv_entities($transaction->check_data->branch_number) . '",' .
            '"' . csv_entities($transaction->check_data->account_number) . '",' .
            '"' . csv_entities($transaction->invoice_data->number) . '",' .
            '"' . csv_entities($transaction->invoice_data->customer_name) . '",' .
            '"' . csv_entities($transaction->invoice_data->customer_number) . '",' .
            '"' . csv_entities($transaction->invoice_data->address_street) . '",' .
            '"' . csv_entities($transaction->invoice_data->address_city) . '",' .
            '"' . csv_entities($transaction->invoice_data->address_zip) . '",' .
            '"' . csv_entities($transaction->invoice_data->phone) . '",' .
            '"' . csv_entities($transaction->invoice_data->description) . '",' .
            '"' . csv_entities(@implode(', ', $transaction->invoice_data->recipients)) . '",' .
            '"' . csv_entities($transaction->invoice_data->link) . '",' .
            '"' . csv_entities($transaction->params->note) . '",' .
            "\n");
    }
}

function exportUserInvoices($merchants, $args, $transactions) {
    
    ignore_user_abort(true);
    set_time_limit(60 * 60 * 24);
    
    $invoices = array();
    
    foreach ($transactions as $transaction) {
        $transaction = $transaction->data;
        if ($transaction->invoice_data->link) {
            $invoices[] = $transaction->invoice_data->link;
        }
    }
    
    if (!$invoices) {
        return false;
    }
    
    // Download and temporarily save all invoices in pdf form.
    $invoices_loc = '/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/invoices/';
    $invoices_dir = sha1(uniqid()) . '/';
    $invoices_zip = 'invoices.zip';
    
    mkdir($invoices_loc . $invoices_dir, 0777, true);
    foreach ($invoices as $invoice) {
        exec('wget ' . $invoice . ' -O "' . $invoices_loc . $invoices_dir . basename($invoice) . '"');
    }
    
    // Create a ZIP archive of all downloaded invoices.
    $zip = new ZipArchive();
    if ($zip->open($invoices_loc . $invoices_dir . $invoices_zip, ZIPARCHIVE::CREATE) === true) {
        foreach ($invoices as $invoice) {
            $zip->addFile($invoices_loc . $invoices_dir . basename($invoice), basename($invoice));
        }
        $zip->close();
    }
    
    // Delete temporary invoice pdf files.
    foreach ($invoices as $invoice) {
        unlink($invoices_loc . $invoices_dir . basename($invoice));
    }
    
    // Email the archive to all recipients.
    require '/var/www/apps/phpmailer/class.phpmailer.php';
    $mail = new phpmailer();
    $mail->IsSMTP();
    $mail->Host = 'localhost';
    $mail->CharSet = 'UTF-8';
    $mail->IsHTML(true);
    
    if ($merchants[0]['processor'] == 'shva') {
        $mail->From = 'no-reply-gts@pnc.co.il';
        $mail->FromName = 'Paragon Ltd.';
    } else {
        $mail->From = 'info@verifone.co.il';
        $mail->FromName = 'VeriFone - Isracard';
    }
    
    $title = 'קובץ חשבוניות';
    $content = 'החשבוניות שביקשתם ניתנות להורדה בלינק הבא:'.'<br>';
    $content .= '<a href="https://'.constant('GTS_APP_HOST').'/invoices/'.$invoices_dir.$invoices_zip.'" target="_blank">לחצו כאן להורדת קובץ החשבוניות</a><br><br>';
    $content .= 'תודה,'.'<br>';
    if ($merchants[0]['params']->merchant_type == 'ישראכרט') {
        $content .= 'צוות PAYware'.'<br>'.'ישראכרט';
    } else if ($merchants[0]['params']->merchant_type == 'וריפון') {
        $content .= 'צוות PAYware'.'<br>'.'וריפון';
    } else {
        $content .= 'צוות GTS'.'<br>'.'Paragon';
    }
    
    $mail->Subject = 'Invoices';
    $mail->Body = generate_email_template($title, false, $content, $merchants[0]['processor'], $merchants[0]['params']);
    
    foreach ($args->exportInvoices as $recipient) {
        $mail->AddAddress(trim($recipient));
    }
    if (!$mail->Send()) {
        return false;
    }
}

// Recursivelly strip all null values from an object/array.
function strip_nulls($variable) {
    if (is_object($variable)) {
        $return = (object) null;
        foreach ($variable as $var => $val) {
            if (isset($val)) {
                $return_tmp = strip_nulls($val);
                if (isset($return_tmp)) {
                    $return->$var = $return_tmp;
                }
            }
        }
        if ($return != (object) null) {
            return $return;
        }
    } else if (is_array($variable)) {
        $return = null;
        foreach ($variable as $var => $val) {
            if (isset($val)) {
                $return_tmp = strip_nulls($val);
                if (isset($return_tmp)) {
                    $return[$var] = $return_tmp;
                }
            }
            if (isset($val)) {
                $return[$var] = strip_nulls($val);
            }
        }
        return $return;
    } else if (isset($variable)) {
        return $variable;
    }
}

function styleFuncDefinition($definition) {
    ob_start();
    
    echo '<ul>' . "\n";
    foreach ($definition as $var => $def) {
        echo '<li>' . "\n";
        echo '(<em>' . $def->type . '</em>)' . "\n";
        echo  '<strong>' . $var . '</strong>' . "\n";
        
        if ($def->{'null'}) {
            echo '<span>[null]</span>' . "\n";
        }
        
        if (is_object($def->value) || $def->type == 'array of object') {
            echo styleFuncDefinition($def->value);
        } else if (is_string($def->value)) {
            echo ':' . "\n";
            echo $def->value . "\n";
        } else if (is_array($def->value)) {
            echo ':' . "\n";
            echo '"' . implode('" / "', $def->value) . '"' . "\n";
        }
        
        echo '</li>' . "\n";
    }
    echo '</ul>' . "\n";
    
    return ob_get_clean();
}

function ssl_encrypt($source, $publickey) {
    $source = base64_encode($source);
    
    $maxlength = 117;
    while ($source) {
        $input = substr($source, 0, $maxlength);
        $source = substr($source, $maxlength);
        
        openssl_public_encrypt($input, $encrypted, $publickey);
        
        $output .= $encrypted;
    }
    
    return base64_encode($output);
}

function aes_encrypt($payload) {
    $aes = new AES($payload, constant('AES_PASSPHRASE'), '256');
    $enc = $aes->encrypt();
    
    return $enc;
}

function aes_decrypt($payload) {
    $aes = new AES($payload, constant('AES_PASSPHRASE'), '256');
    $dec = $aes->decrypt();

    return $dec;
}

?>