<?php
	$invoice['company']['details'] = 'ויסנס, ח"פ 558015863, גלגלי הפלדה 20, הרצליה, טלפון: 054-7000765';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'ויסנס, ח"פ 368510855, גלגלי הפלדה 20, הרצליה, טלפון: 5670007-540';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'ויסנס';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/109/logo__vsense.png';
    include 'invoice.he.php';
?>
