<?php
	$business_details['company_name'] = 'פאראגון קריאיישנס בע"מ';
	$business_details['company_number_type'] = 'ח"פ';
	$business_details['company_number'] = '513555615';
	$business_details['street'] = 'המגשימים';
	$business_details['number'] = '20';
	$business_details['city'] = 'פתח-תקווה';
	$business_details['zip'] = '52511';
	$business_details['phone'] = '03-5708989';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}
	$invoice['company']['contact_person'] = 'אורן עגיב';
	$invoice['company']['contact_person_title'] = 'מנכ"ל';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/paragon-creations.com/files/images/invoice/header.png';
    include 'invoice.he.php';
?>
