<?php
	$invoice['company']['details'] = 'וריפון ישראל בע"מ, ח"פ 558015863, העמל 11, ראש העין, טלפון: 03-9029730';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'וריפון ישראל בע"מ, ח"פ 368510855, העמל 11, ראש העין, טלפון: 0379209-30';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'וריפון ישראל בע"מ';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/verifone.co.il/images/global/verifone_logo.png';
    include 'invoice.he.php';
?>
