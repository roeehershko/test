<?php
	$invoice['company']['details'] = 'עמותת ידידי מגלן, אצל אבי שניר , הגפן 107 ניר ישראל. ד.נ. לכיש צפון 79505, עמותה רשומה מספר 58-041524-8';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'עמותת ידידי מגלן, אצל אבי שניר , הגפן 701 ניר ישראל. ד.נ. לכיש צפון 50597, עמותה רשומה מספר 58-041524-8';
	}
	$invoice['company']['contact_person'] = 'הנהלת חשבונות';
	$invoice['company']['contact_person_title'] = 'עמותת ידידי מגלן';
    $invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/63/logo__maglan.png';
	include 'invoice.he.php';
?>
