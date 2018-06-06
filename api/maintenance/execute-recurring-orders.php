<?php

/*
 * This script should be executed daily.
 * Check recurring-orders and submit transcations as needed.
 * Due to security reasons this script can only be executed from shell.
*/

if (!$argv) {
    die('Access denied.');
}

set_time_limit(300);

header('Content-Type: text/plain; charset=UTF-8');

require(dirname($_SERVER['SCRIPT_NAME']) . '/../config.inc.php');
require(dirname($_SERVER['SCRIPT_NAME']) . '/../core.inc.php');
require(dirname($_SERVER['SCRIPT_NAME']) . '/../aes.inc.php');

require_once '/var/www/apps/phpmailer/class.phpmailer.php';

if (!connectDB($connectDB__error)) {
    die($connectDB__error);
}

require(dirname($_SERVER['SCRIPT_NAME']) . '/../rest/functions/submitUserTransaction.inc.php');

$sql_query = mysql_query("SELECT merchant_id, username, shva_transactions_merchant_number, company_id, company_name, company_email, processor, mobile, params FROM merchants WHERE processor = 'creditguard' AND `terminated` = '0'");
while ($sql = mysql_fetch_object($sql_query)) {
    $sub_sql_query = mysql_query("SELECT ro_id, name, repetitions, `interval`, cc_last4, cc_exp, cc_data, amount, currency, invoice_customer_name, invoice_description, invoice_recipients FROM recurring_orders WHERE merchant_id = '" . mysql_real_escape_string($sql->merchant_id) . "' AND duedate <= CURDATE() AND (repetitions IS NULL OR repetitions > 0) AND `terminated` = '0'");
    while ($sub_sql = mysql_fetch_object($sub_sql_query)) {
        $error = null;
        
        $cc_number = $sub_sql->cc_data;
        
        $request = (object) array(
            'username' => $sql->username,
            'merchant_id' => $sql->merchant_id,
            'merchant_number' => $sql->shva_transactions_merchant_number,
            'credit_data' => (object) array(
                'cc_number' => $cc_number,
                'cc_exp' => $sub_sql->cc_exp,
                'credit_terms' => 'recurring_order'
            ),
            'amount' => $sub_sql->amount,
            'currency' => $sub_sql->currency,
            'lang' => 'he'
        );
        $return = submitUserTransaction_CreditGuard($request);
        
        if (!$return->error) {
            if (mysql_query("INSERT INTO trans (merchant_id, timestamp, status, type, amount, currency) VALUES ('" . $sql->merchant_id . "', NOW(), 'pending', 'credit', '" . mysql_real_escape_string($sub_sql->amount) . "', '" . mysql_real_escape_string($sub_sql->currency) . "')")) {
                $trans_id = mysql_insert_id();
                
                if (mysql_query("INSERT INTO trans_credit (trans_id, cc_last_4, cc_exp, cc_type, credit_terms, transaction_code, acquirer, voucher_number, authorization_number, credit_guard_token, credit_guard_tran_id) VALUES ('$trans_id', '" . mysql_real_escape_string(substr($cc_number, -4)) . "', '" . mysql_real_escape_string($sub_sql->cc_exp) . "', " . SQLNULL(mysql_real_escape_string(identifyCCType($cc_number))) . ", 'recurring_order', 'phone', " . SQLNULL(mysql_real_escape_string($return->acquirer)) . ", " . SQLNULL(mysql_real_escape_string($return->voucher_number)) . ", " . SQLNULL(mysql_real_escape_string($return->authorization_number)) . ", " . SQLNULL(mysql_real_escape_string($return->credit_guard_token)) . ", " . SQLNULL(mysql_real_escape_string($return->credit_guard_tran_id)) . ")")) {
                    if ($return->raw_dump) {
                        if (!mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '1', 'raw_dump', '" . mysql_real_escape_string($return->raw_dump) . "')")) {
                            $error = err(5);
                        }
                    }
                } else {
                    $error = err(5);
                }
                
                if (mysql_query("INSERT INTO trans_invoices (trans_id, customer_name, description) VALUES ('$trans_id', " . SQLNULL(mysql_real_escape_string($sub_sql->invoice_customer_name)) . ", " . SQLNULL(mysql_real_escape_string($sub_sql->invoice_description)) . ")")) {
                    if ($sub_sql->invoice_recipients) {
                        if (preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $sub_sql->invoice_recipients, $matches)) {
                            $recipients = array_unique($matches[0]);
                            for ($i = 0; $i != count($recipients); $i++) {
                                if (!mysql_query("INSERT INTO trans_invoices_recipients (trans_id, email) VALUES ('$trans_id', '" . mysql_real_escape_string(strtolower($recipients[$i])) . "')")) {
                                    $error = err(5);
                                }
                            }
                        }
                    }
                } else {
                    $error = err(5);
                }
                
                $params_sql_query = mysql_query("SELECT name, value FROM recurring_orders_params WHERE ro_id = '" . mysql_real_escape_string($sub_sql->ro_id) . "'");
                while ($params_sql = mysql_fetch_object($params_sql_query)) {
                    if (!mysql_query("INSERT INTO trans_params (trans_id, private, name, value) VALUES ('$trans_id', '0', '" . mysql_real_escape_string($params_sql->name) . "', '" . mysql_real_escape_string($params_sql->value) . "')")) {
                        $error = err(5);
                    }
                }
                
                if (!$error) {
                    generateInvoice($trans_id);
                    
                    mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $trans_id . "'");
                }
            } else {
                $error = err(5);
            }
        } else {
            $error = $return->error;

            // Emailing the merchant about the error
            $mail = new PHPMailer();
            $mail->From = 'no-reply-gts@pnc.co.il';
            if ($params->merchant_type == 'ישראכרט') {
                $mail->FromName = 'Isracard';
            } else {
                $mail->FromName = 'VeriFone';
            }
            $mail->Subject = 'Recurring Order Failed';
            $mail->AddAddress($sql->company_email);
            //$mail->AddBCC('oa@pnc.co.il');
            
            ob_start();
            localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/recurring-order-notification.inc.php', array(
                'merchant_id' => $sql->merchant_id,
                'company_id' => $sql->company_id,
                'company_name' => $sql->company_name,
                'company_email' => $sql->company_email,
                'processor' => $sql->processor,
                'params' => unserialize($sql->params),
                'username' => $sql->username,
                'recurring_order_id' => $sub_sql->ro_id,
                'error' => $error
            ));
            $mail->Body = ob_get_clean();
            
            $mail->IsSMTP();
            $mail->Host = '127.0.0.1';
            $mail->CharSet = 'UTF-8';
            $mail->IsHTML(true);
            $mail->Send();
            
            // SMS the Merchant
            $mobile = preg_replace('/^0/', '+972', $sql->mobile);
            if ($mobile) {
                $message = "לקוח יקר,\nהוראת קבע מספר $sub_sql->ro_id עבור מסוף \"$sql->username\" לא עברה בהצלחה עקב השגיאה הבאה:\n$error";
                
                $params = unserialize($sql->params);
                if ($params->merchant_type) {
                    $sender = $params->merchant_type == 'ישראכרט' ? 'Isracard' : 'Verifone';
                } else {
                    $sender = 'Paragon';
                }
                $request = array(
                    'username' => 'verifone_backoffice',
                    'password' => '328577fXD.h',
                    'sender' => $sender,
                    'recipient' => $mobile,
                    'message' => $message,
                );
                $response = file_get_contents('https://secure.pnc.co.il/sms/', false, stream_context_create(array(
                    'http' => array (
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
                        'content' => http_build_query($request)
                    )
                )));
            }

        }

        // Updating to the next due date, even if the recurring order failed.
        mysql_query("UPDATE recurring_orders SET duedate = DATE_ADD(duedate, INTERVAL 1 " . ($sub_sql->interval == 'yearly' ? "YEAR" : "MONTH") . "), repetitions = IF(repetitions IS NULL, NULL, repetitions - 1) WHERE ro_id = '" . mysql_real_escape_string($sub_sql->ro_id) . "'");
        
        if (!$error) {
            mysql_query("INSERT INTO recurring_orders_transactions (ro_id, trans_id) VALUES ('" . mysql_real_escape_string($sub_sql->ro_id) . "', '" . mysql_real_escape_string($trans_id) . "')");
        }
        
        echo 'Merchant #' . $sql->shva_transactions_merchant_number . ' - Recurring-order #' . $sub_sql->ro_id . ' (' . $sub_sql->name . '): ' . (!$error ? 'OKAY' : 'ERROR: ' . $error) . "\n";

    }
}

echo 'Executing recurring-orders completed.' . "\n";

?>
