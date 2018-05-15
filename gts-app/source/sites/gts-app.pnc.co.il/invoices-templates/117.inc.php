<?php
	$invoice['company']['details'] = 'ניוז-סאנקאר דיגיטל בע"מ, ח.פ. 514599679, יצחק שדה 5 תל-אביב 67775, ת.ד. 51665, טלפון: 03-6516761';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'ניוז-סאנקאר דיגיטל בע"מ, ח.פ. 514599679, יצחק שדה 5 תל-אביב 57776, ת.ד. 56615, טלפון: 1676156-30';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'ניוז-סאנקאר דיגיטל בע"מ';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/pnc.co.il/images/global/spacer.gif';
    include 'invoice.he.php';
?>
