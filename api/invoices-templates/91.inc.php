<?php
	$invoice['company']['details'] = 'קינדרוולט 2010 בע"מ, רחוב תכלת מרדכי 8/18, בית שמש 99082';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'קינדרוולט 0102 בע"מ, רחוב תכלת מרדכי 81/8, בית שמש 99082';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'קינדרוולט';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/91/logo__kindervalt.png';
    include 'invoice.he.php';
?>
