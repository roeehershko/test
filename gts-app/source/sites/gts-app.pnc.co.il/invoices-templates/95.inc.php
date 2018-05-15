<?php
    
    $invoice['company']['details'] = 'יונו אוסינט בע"מ, ח"פ 514455807, חולון, ת.ד. 349, 1-700-555-155';
    if (!$_GET['token']) {
		$invoice['company']['details'] = 'יונו אוסינט בע"מ, ח"פ 708554415, חולון, ת.ד. 943, 551-555-007-1';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = '';
    $invoice['company']['logo'] = 'https://secure.pnc.co.il/allisrael.co.il/images/global/logo__allisrael.png';
    
    if ($_GET['lang'] == 'en') {
        include 'invoice.en.php';
    } else {
        include 'invoice.he.php';
    }
?>
