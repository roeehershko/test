<?php

	ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache

	header('Content-Type: text/plain; charset=UTF-8');
	
	require('../config.inc.php');
	require('../core.inc.php');

	if (!connectDB($connectDB__error)) {
		die($connectDB__error);
	}
	
	$password_string = 'abcdefghijklmnpqrstuwxyzABCDEFGHJKLMNPQRSTUWXYZ0123456789';
	$password = substr(str_shuffle($password_string), 0, 32);

	$request = (object) array(
		'MerchantNumber' => 'xxx0962330012',
		'UserName' => 'xxx',
		'Password' => $_GET['old-password'],
		'NewPassword' => $password
	);

	$client = new SoapClient('https://www.shva-online.co.il:5443/ash/abscheck/absrequest.asmx?wsdl');
	$response = $client->ChangePassword($request);
	
	echo '<div><h2><pre>https://www.shva-online.co.il:5443/ash/abscheck/absrequest.asmx - ChangePassword</pre></h2></div>';
	echo '<pre>Request:'; print_r($request); echo '</pre>';
	echo '<pre>Response:'; print_r($response); echo '</pre>';
	exit;
	
?>