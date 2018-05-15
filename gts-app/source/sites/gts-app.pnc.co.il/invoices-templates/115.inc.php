<?php
	$invoice['company']['details'] = 'מגנט טטרקס ישראל, ע.מ. 557524774, ת.ד. 6001, הוד בשרון 45241, טלפון: 09-7404338';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'מגנט טטרקס ישראל, ע.מ. 557524774, ת.ד. 1006, הוד בשרון 14254, טלפון: 8334047-90';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'מגנט טטרקס ישראל';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/115/logo__magnet.jpg';
    include 'invoice.he.php';
?>
