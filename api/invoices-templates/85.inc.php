<?php
	$invoice['company']['details'] = 'ש.י. סחר, ח.פ. 558004941, טלפון: 0528886004';
	if (!$_GET['token']) {
		
		$invoice['company']['details'] = 'ש.י. סחר, ח.פ. 149400855, טלפון: 0528886004';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'ש.י. סחר';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/paragon-creations.com/images/global/spacer.gif';
    include 'invoice.he.php';
?>
