<?php
	$invoice['company']['details'] = 'אביטל יהב, ע.מ. 032110553, שבט 2, נתניה, טלפון: 09-8335030';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'אביטל יהב, ע.מ. 032110553, שבא 2, נתניה, טלפון: 0305338-90';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'אביטל יהב';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/pnc.co.il/images/global/spacer.gif';
    include 'invoice.he.php';
?>
