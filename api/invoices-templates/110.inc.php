<?php
	$invoice['company']['details'] = 'מיזמי רשת אינטרנט והשקעות בע"מ, ח"פ 514509355, שביל הזהב 4, רעננה, 43524';
	'';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'מיזמי רשת אינטרנט והשקעות בע"מ, ח"פ 553905415, שביל הזהב 4, רעננה, 42534';
	}
	$invoice['company']['contact_person'] = 'מיזמי רשת אינטרנט והשקעות בע"מ';
	$invoice['company']['contact_person_title'] = ''; //'moc.fatuhs@li.troppus';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/shutaf.com/images/global/logo__shutaf.png';
    include 'invoice.he.php';
?>
