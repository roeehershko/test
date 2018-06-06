<?php
	$invoice['company']['details'] = 'מדריך חופה בע"מ, ח"פ 513772186, רח\' החשמונאים 88, תל-אביב 20216, ת.ד. 52202, טלפון: 035620024';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'מדריך חופה בע"מ, ח"פ 681277315, רח\' החשמונאים 88, תל-אביב 61202, ת.ד. 20225, טלפון: 035620024';
	}
	$invoice['company']['contact_person'] = 'רועי גזית';
	$invoice['company']['contact_person_title'] = 'מנכ"ל';
	$invoice['company']['logo'] = 'https://secure.hoopa.com/hoopa.com/images/global/logo__he_hoopa.png';
    include 'invoice.he.php';
?>
