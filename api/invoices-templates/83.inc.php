<?php
	$invoice['company']['details'] = 'כפל מועדון צרכנים, ח"פ 514358043, רחוב סחרוב-דוד 13, ראשון לציון 75707, טלפון: 037360567';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'כפל מועדון צרכנים, ח"פ 340853415, רחוב סחרוב-דוד 31, ראשון לציון 70757, טלפון: 765063730';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'כפל מועדון צרכנות';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/paragon-creations.com/files/images/invoice/header.png';
    include 'invoice.he.php';
?>
