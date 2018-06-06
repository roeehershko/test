<?php
	$invoice['company']['details'] = 'אינפוריוז, ח"פ 557940939, רחוב גשמי ברכה 20, הוד-השרון 45316, טלפון: 0545585250';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'אינפוריוז, ח"פ 939049755, רחוב גשמי ברכה 02, הוד-השרון 61354, טלפון: 0525855450';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'אינפוריוז';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/81/logo__in4use.jpg';
    include 'invoice.he.php';
?>
