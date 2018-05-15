<?php

	ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache

	header('Content-Type: text/plain; charset=UTF-8');
	
	/*
	$client = new SoapClient('https://www.shva-online.co.il/EMVWeb/Beta2/EMVRequest.asmx?wsdl');
    $request = (object) array(
        'MerchantNumber' => '0882711013',
        'UserName' => 'IGXZQ',
        'Password' => '329960',
		'NewPassword' => '88Paragon',
	);
    $response = $client->ChangePassword($request);
	
    echo '<pre>Change Password Request: '; print_r($request); echo '</pre>';
    echo '<pre>Change Password Response: '; print_r($response); echo '</pre>';
    exit;
	*/
	
	$client = new SoapClient('https://www.shva-online.co.il/EMVWeb/Beta2/EMVRequest.asmx?wsdl', array('soap_version' => SOAP_1_2, 'cache_wsdl' => WSDL_CACHE_NONE , 'trace' => 1, 'exceptions' => 0));
    $request = (object) array(
        'MerchantNumber' => '0882711013',
        'UserName' => 'IGXZQ',
        'Password' => '88Paragon',
		'inputObj' => (object) array(
			'bAnalysisXmlStr' => false,
			'status' => 'DEFAULT',
			'mti' => 100, // STRING; 100 - Charge; // 400 - Cancel that was not submitted; // 420 - Reversal (for J5 only)
			'panEntryMode' => 51, // STRING; 50 Phone; 51 Signature; 52 Interent; 
			'eci' => '', //1
			'tranType' => 1, // 1 - Charge; 53 - Refund
			'expirationDate' => '', //'1709', //1712, // YYMM
			'currency' => 376, // 376 NIS; 840 USD; 
			'amount' => 1,
			'creditTerms' => 1, // 1 - Regular; 8 - Payments; 6 - Credit
			//'firstPayment' =>
			//'notFirstPayment' =>
			//'noPayment' => // Number of payments
			'clientInputPan' => '4580080008587950=17092010000042692000', //'82788855', // track2
			'cvv2' => '' //'074' //661
		),
		'globalObj' => (object) array()
	);
    $response = $client->AshGen($request);
	
    echo '<pre>Request: '; print_r($request->inputObj); echo '</pre>';
    echo '<pre>Response: '; print_r($response); echo '</pre>';
	//echo '<pre>Function: '; echo $client->__getFunctions(); echo '</pre>';
	echo '<pre>XML Request: '; echo $client->__getLastRequest(); echo '</pre>';
    exit;
	
	//echo dirname($_SERVER['SCRIPT_NAME']);
	//exit;

	require('../config.inc.php');
	require('../core.inc.php');

	if (!connectDB($connectDB__error)) {
		die($connectDB__error);
	}

	$sql_query = mysql_query("SELECT merchant_id, password, shva_transactions_merchant_number, shva_transactions_username, shva_standing_orders_merchant_number, shva_standing_orders_username FROM merchants WHERE `terminated` = '0' AND processor = 'shva'");
	while ($sql = mysql_fetch_assoc($sql_query)) {
		$standing_orders = $transactions = false;
		
		$sub_sql_query = mysql_query("SELECT trans_id, type FROM trans WHERE merchant_id = '" . $sql['merchant_id'] . "' AND status = 'pending'");
		while ($sub_sql = mysql_fetch_object($sub_sql_query)) {
			if ($sub_sql->type == 'credit') {
				if ($credit_tmp = mysql_fetch_object(mysql_query("SELECT use_so_credentials, trans_params.value AS shva_trans_record FROM trans_credit LEFT JOIN trans_params USING (trans_id) WHERE trans_id = '" . $sub_sql->trans_id . "' AND reference_number IS NULL AND trans_params.private = '1' AND trans_params.name = 'shva_trans_record'"))) {
					$sub_sql->shva_trans_record = $credit_tmp->shva_trans_record;
					$is_so = @mysql_result(mysql_query("SELECT 1 FROM standing_orders_transactions WHERE trans_id = '" . $sub_sql->trans_id . "'"), 0);
					
					if ($credit_tmp->use_so_credentials || $is_so) {
						$standing_orders[] = $sub_sql;
					} else {
						$transactions[] = $sub_sql;
					}
				}
			} else {
				generateInvoice($sub_sql->trans_id);
			   
				// Required in case the transaction type is not 'credit' and does not have an invoice.
				mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $sub_sql->trans_id . "'");
			}
		}
		
		if ($standing_orders) {
			$depositTransactionsInformation = depositSHVA($sql['shva_standing_orders_merchant_number'], $sql['shva_standing_orders_username'], aes_decrypt($sql['shva_transactions_password']), $standing_orders);
		}
		if ($transactions) {
			$depositTransactionsInformation = depositSHVA($sql['shva_transactions_merchant_number'], $sql['shva_transactions_username'], aes_decrypt($sql['shva_transactions_password']), $transactions);
		}  
	}

	// Generate invoices for CreditGuard merchants (invoices for credit transactions are generated immediately after the transaction is made).
	$sql_query = mysql_query("SELECT merchant_id FROM merchants WHERE `terminated` = '0' AND processor = 'creditguard'");
	while ($sql = mysql_fetch_assoc($sql_query)) {
		$sub_sql_query = mysql_query("SELECT trans_id, type FROM trans WHERE merchant_id = '" . $sql['merchant_id'] . "' AND status = 'pending'");
		while ($sub_sql = mysql_fetch_object($sub_sql_query)) {
			if ($sub_sql->type != 'credit' || @mysql_result(mysql_query("SELECT 1 FROM trans_credit WHERE trans_id = '" . $sub_sql->trans_id . "' AND j5 = '0'"), 0)) {
				generateInvoice($sub_sql->trans_id);
				mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $sub_sql->trans_id . "'");
			}
		}
	}

	echo 'Automatic transactions deposit completed.' . "\n";


	// Functions.

	function depositSHVA($merchant_number, $merchant_username, $merchant_password, $transactions) {
		$transactions_records = array();
		foreach ($transactions as $transaction) {
			$transactions_records[] = $transaction->shva_trans_record;
		}
		
		$client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
		$return = $client->DepositTransactions((object) array(
			'MerchantNumber' => $merchant_number,
			'UserName' => $merchant_username,
			'Password' => $merchant_password,
			'TransArr' => $transactions_records
		));
		
		echo $merchant_username.' / '.$merchant_password; exit;
		
		if ($return->DepositTransactionsResult == 250) {
			$error = 'Invalid identification tokens.';
		} elseif ($return->DepositTransactionsResult != 0) {
			$error = 'Unknown error [' . $return->DepositTransactionsResult . '].';
		}
		
		echo 'Merchant #' . $merchant_number . ' - Deposit (' . count($transactions) . '): ' . (!$error ? 'OKAY' : 'ERROR: ' . $error) . "\n";
		
		if (!$error) {
			// Generate invoices.
			foreach ($transactions as $transaction) {
				generateInvoice($transaction->trans_id);
			}
			
			// Get the deposit reference code.
			// Set the reference to something other than NULL until the real reference code is retrieved.
			// Required so that deposited transactions would not get deposited again in the event of the reference not being retrieved.
			foreach ($transactions as $transaction) {
				mysql_query("UPDATE trans_credit SET reference_number = '0' WHERE trans_id = '" . $transaction->trans_id . "'");
			}
			
			$client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
			$return = $client->GetDepositInformation((object) array(
				'MerchantNumber' => $merchant_number,
				'UserName' => $merchant_username,
				'Password' => $merchant_password,
				'TransmitDate_yyyyMMdd' => date('Ymd'),
				'SequenceNumberOfDepositForDate' => 0
			));
			$sub_error = false;
			
			if ($return->GetDepositInformationResult == 250) {
				$sub_error = 'Invalid identification tokens.';
			} elseif ($return->GetDepositInformationResult == 257) {
				$sub_error = 'Information not found.';
			} elseif ($return->GetDepositInformationResult != 0) {
				$sub_error = 'Unknown error [' . $return->GetDepositInformationResult . '].';
			}
			
			echo 'Merchant #' . $merchant_number . ' - Get Deposit Information: ' . (!$sub_error ? 'OKAY' : 'ERROR: ' . $sub_error) . "\n";
			
			if (!$sub_error) {
				// Set the actual reference code, replacing the temporary mark.
				foreach ($transactions as $transaction) {
					mysql_query("UPDATE trans SET status = 'completed' WHERE trans_id = '" . $transaction->trans_id . "'");
					mysql_query("UPDATE trans_credit SET reference_number = '" . mysql_real_escape_string($return->ReferenceNumber) . "' WHERE trans_id = '" . $transaction->trans_id . "'");
					mysql_query("DELETE FROM trans_params WHERE trans_id = '" . $transaction->trans_id . "' AND private = '1' AND name = 'shva_trans_record'");
				}
			}
		}
		
		echo "\n";
	}

	/*
	$password_string = 'abcdefghijklmnpqrstuwxyzABCDEFGHJKLMNPQRSTUWXYZ0123456789';
	$password = substr(str_shuffle($password_string), 0, 32);

	$request = (object) array(
		'MerchantNumber' => '0962330012',
		'UserName' => 'NOIGX',
		'Password' => $_GET['old-password'],
		'NewPassword' => $password
	);

	$client = new SoapClient('https://www.shva-online.co.il/ash/abscheck/absrequest.asmx?wsdl');
	$response = $client->ChangePassword($request);
	
	echo '<div><h2><pre>https://www.shva-online.co.il/ash/abscheck/absrequest.asmx - ChangePassword</pre></h2></div>';
	echo '<pre>Request:'; print_r($request); echo '</pre>';
	echo '<pre>Response:'; print_r($response); echo '</pre>';
	exit;
	*/

	/*
	$shva_online = new SoapClient('https://www.shva-online.co.il/ash/test/RetManCustWS/Service.asmx?WSDL', array('exceptions' => 1, 'trace' => 1));
	$request = array(
		'KidometMefic' => '8871000',
        'HPMefic' => '520038944',
        'UserName' => 'TEST1',
        'Password' => 'pqMM3.78zsq',
        'Masof' => '8883281'
    );
	$response = $shva_online->CreateWebRetailer($request);
    echo '<pre>Request: '; print_r($request); echo '</pre>';
    echo '<pre>Response: '; print_r($response); echo '</pre>';
    exit;
	*/

?>