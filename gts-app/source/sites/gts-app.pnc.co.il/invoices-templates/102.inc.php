<?php
	$invoice['company']['details'] = 'אסק4 מכרזים בע"מ, ח"פ 514302256, רח\' נחום 8, גבעת שמואל, 54022, טלפון: 077-4306020';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'אסק4 מכרזים בע"מ, ח"פ 514302256, רח\' נחום 8, גבעת שמואל, 22045, טלפון: 0206034-770';
	}
	$invoice['company']['contact_person'] = 'מחלקת חשבונאות';
	
	$invoice['company']['contact_person_title'] = '077-4306020 שלוחה 215';
	if (!$_GET['token']) {
		$invoice['company']['contact_person_title'] = 'שלוחה 215'.' טלפון '.'0206034-770';
		
	}
	$invoice['company']['logo'] = 'https://secure.ask4.co.il/ask4.co.il/files/images/logo.gif';
    include 'invoice.he.php';
?>
