<?php
	$invoice['company']['details'] = 'קליאן די.קיי ליין, ח.פ. 557716883, אחימאיר 19 תל-אביב, טלפון 03-6417811';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'קליאן די.קיי ליין, ח.פ. 557716883, אחימאיר 91 תל-אביב, טלפון 1187146-30';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'קליאן די.קיי ליין';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/113/logo__wallart.png';
    include 'invoice.he.php';
?>



