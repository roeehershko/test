<?php

	recursive('trim', $_GET);
	//recursive('stripslashes', $_GET);
	recursive('strip_tags', $_GET);
	
	$lang_code = 'he';

	if ($_GET['recurring_order']) {
		$type = 'recurring_order';
	} else {
		$type = 'transaction';	
	}
	
	//echo '<pre>Session: '; print_r($_SESSION); echo '</pre>';
	//echo '<pre>GTS Credentials: '; print_r(getVar('gts_credentials')); echo '</pre>';

	if ($gts_admin) {
		
		// 2017-01-31 - a GTS Admin can view transactions of all merchants from gts-verifone-reports.inc.php, so we need to skip all the steps below.	
		
	} else if ($_POST['username'] || $_POST['password']) {
		
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$username = $_POST['username'];
		$password = $_POST['password'];

		// Getting the access token
		$response = file_get_contents('https://'.rawurlencode($username).':'.rawurlencode($password).'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/createUserAccessToken', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'ip_address' => $_SERVER['REMOTE_ADDR']
				)))
			)
		)));
		$response = json_decode($response, true);
		//echo '<pre>'; print_r($response); echo '</pre>';
		//exit;
		if ($response['result'] == 'OKAY') {
			$gts_credentials = $username.':'.$response['data']['access_token'];
			setVar('gts_credentials', $gts_credentials);
			
			//echo '<pre>'; print_r($gts_credentials); echo '</pre>';
			//echo '<pre>GTS Credentials: '; print_r(getVar('gts_credentials')); echo '</pre>';
			//exit;
			
			Header('Location:'.href($doc));
			exit;
		} else {
			$errors[] = $response['error']; //'שם משתמש או סיסמא שגויים.';
			gyroLog('gts_console_login - failed');
			include 'templates/superdocs/gts-login.inc.php';
		}
	
	} else if (!getVar('gts_credentials') || $_GET['reset_password'] || $_GET['token']) {

		// If no access token exists, we show the login screen
		include 'templates/superdocs/gts-login.inc.php';
	
	} else if ($_GET['action'] == 'logout') {
	
		unsetVar('gts_credentials');
		unsetVar('gts_admin');
		unsetVar('gts_user_id');
		
		Header('Location:'.href($doc));
		exit;
	
	} else if ($gts_credentials = getVar('gts_credentials')) {
		
		// If the User is a Manager - getting his merchants
		$user_details = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserDetails', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => '',
			)
		)));
		$user_details = json_decode($user_details, true);
		$user_details = $user_details['data'];
		
		// Getting the merchant details (for checking if the user has invoices and recurring-orders enabled)
		if ($user_details['merchants'][0]) {
			if ($_GET['merchant_id']) {
				foreach ($user_details['merchants'] as $merchant) {
					if ($merchant['id'] == $_GET['merchant_id']) {
						$merchant_details = $merchant;
					}
				}
			} else {
				$merchant_details = $user_details['merchants'][0];
			}
		} else {
			$merchant_details = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainMerchantDetails', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => '',
				)
			)));
			$merchant_details = json_decode($merchant_details, true);
			$merchant_details = $merchant_details['data'];
		}
		//echo '<pre>'; print_r($merchant_details); echo '</pre>';
		//exit;
		
		if (!$merchant_details) {
			// GTS access token expired
			Header('Location:'.href($doc.'?action=logout'));
			exit;
		}
		
		if ($_GET['debug']) {
			echo '<pre>User Details: '; print_r($user_details); echo '</pre>';
			echo '<pre>Merchant Details: '; print_r($merchant_details); echo '</pre>';
			exit;
		}
		
		// Must change password
		if ($user_details['must_change_password'] == 1 || (!$user_details['id'] && $merchant_details['must_change_password'] == 1)) {
			include 'templates/superdocs/gts-login.inc.php';
		}
		
		?>
		<script type="text/javascript">
			var gts_merchant = {};
			<?
			if ($merchant_details) {
				if ($merchant_details['invoice_support']) {
					?>
					gts_merchant.invoice_support = true;
					<?
				} else {
					?>
					gts_merchant.invoice_support = null;
					<?
				}
			}
			?>
		</script>
		<?
	}
?>