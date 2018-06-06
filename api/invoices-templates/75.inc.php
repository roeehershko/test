<?php
    $invoice['company']['details'] = 'סקיפיפ ישראל, עוסק מורשה 28916732, רח\' נרקיס 8, בצרה, מיקוד 60944, ת.ד. 118, טלפון: 097401272';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'סקיפיפ ישראל, עוסק מורשה 28916732, רח\' נרקיס 8, בצרה, מיקוד 44906, ת.ד. 118, טלפון: 097401272';
	}
	$invoice['company']['contact_person'] = 'זיו ערב';
	$invoice['company']['contact_person_title'] = 'מנכ"ל';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/75/logo__speakeep.png';
    include 'invoice.he.php';
?>
