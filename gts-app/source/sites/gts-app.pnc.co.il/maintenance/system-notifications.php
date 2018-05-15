<?php

/*
 * This script should be executed daily.
 * It checks whether merchants' passwords are about to expire and send email notifications to that effect.
 * For security reasons this script can only be executed from shell.
*/

if (!$argv) {
    die('Access denied.');
}

set_time_limit(300);

header('Content-Type: text/plain; charset=UTF-8');

require(dirname($_SERVER['SCRIPT_NAME']) . '/../config.inc.php');
require(dirname($_SERVER['SCRIPT_NAME']) . '/../core.inc.php');

require_once '/var/www/apps/phpmailer/class.phpmailer.php';

if (!connectDB($connectDB__error)) {
    die($connectDB__error);
}

$days = array(2, 5, 7, 14); // If this number of days remains, email the merchant.
$sql_query = mysql_query("SELECT merchant_id, company_id, company_name, company_email, processor, username, mobile, params, (SELECT FLOOR((UNIX_TIMESTAMP(TIMESTAMPADD(MONTH, 3, updated)) - UNIX_TIMESTAMP()) / 86400) FROM merchants_passwords_log WHERE merchants.merchant_id = merchant_id ORDER BY updated DESC LIMIT 1) AS days FROM merchants WHERE `terminated` = 0 HAVING days IN (" . implode(', ', $days) . ")");
//$sql_query = mysql_query("SELECT merchant_id, company_id, company_name, company_email, processor, username, mobile, params FROM merchants WHERE merchants.merchant_id = 2");

while ($sql = mysql_fetch_assoc($sql_query)) {
    $merchant_params = unserialize($sql['params']);
    if ($merchant_params->merchant_status != 'disabled') {
		
		$mail = new PHPMailer();
	    $mail->From = 'no-reply-gts@pnc.co.il';
	    if ($params->merchant_type == 'ישראכרט') {
	        $mail->FromName = 'Isracard';
	    } else {
	        $mail->FromName = 'VeriFone';
	    }
	    $mail->Subject = 'Password Change Reminder';
	    $mail->AddAddress($sql['company_email']);
		//$mail->AddBCC('oa@pnc.co.il');
		
		ob_start();
	    localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/renew-password-notification.inc.php', array(
	        'merchant_id' => $sql['merchant_id'],
	        'company_id' => $sql['company_id'],
	        'company_name' => $sql['company_name'],
	        'company_email' => $sql['company_email'],
	        'processor' => $sql['processor'],
	        'params' => unserialize($sql['params']),
	        'username' => $sql['username'],
	        'days' => $sql['days']
	    ));
	    $mail->Body = ob_get_clean();
	    
	    $mail->IsSMTP();
	    $mail->Host = '127.0.0.1';
	    $mail->CharSet = 'UTF-8';
	    $mail->IsHTML(true);
	    $mail->Send();
	    
	    // SMS the Merchant
	    $mobile = preg_replace('/^0/', '+972', $sql['mobile']);
		if ($mobile) {
			$message = "לקוח יקר,\nסיסמתך עבור משתמש {USERNAME} עומדת לפוג.\nלשינוי הסיסמא יש להכנס לאפליקציית PAYware, מסך ההגדרות, שינוי סיסמא.\nלידיעתך - ללא שינוי סיסמא גישתך תיחסם.";
			$message = str_replace('{USERNAME}', $sql['username'], $message);
			
			$params = unserialize($sql['params']);
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
}

?>