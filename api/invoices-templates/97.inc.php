<?php
    
    $invoice['company']['details'] = "רח' ז'בוטינסקי 3, מתחם הבורסה, בניין שמשון, רמת גן 52520, טלפון: 1-700-721-721";
    if (!$_GET['token']) {
		$invoice['company']['details'] = "רח' ז'בוטינסקי 3, מתחם הבורסה, בניין שמשון, רמת גן 02525, טלפון: 1-700-721-721";
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = '';
    $invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/97/logo__yesido.png';
    
    if ($_GET['lang'] == 'en') {
        include 'invoice.en.php';
    } else {
        include 'invoice.he.php';
    }
?>
