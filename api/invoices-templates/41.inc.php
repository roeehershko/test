<?php
	$invoice['company']['details'] = 'סלביי בע"מ, ח"פ 514261379, רח\' חנוך לוין 13, רמלה, טלפון: 050-9754243';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'סלביי בע"מ, ח"פ 973162415, רח\' חנוך לוין 31, רמלה, טלפון: 050-9754243';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = '';
	$invoice['company']['logo'] = 'https://secure.salebuy.us/sellbuy.co.il/images/global/logo.gif';
    include 'invoice.he.php';
?>
