<?php
	$invoice['company']['details'] = 'ניוטיים, ח.פ. 558051942, נתיב המזלות 9/3 ירושלים 97830, טלפון: 050-3390036';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'ניוטיים, ח.פ. 558051942, נתיב המזלות 3/9 ירושלים 97830, טלפון: 6300933-050';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'ניוטיים';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/118/logo__newtime.png';
    include 'invoice.he.php';
?>
