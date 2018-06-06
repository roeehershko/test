<?php
    $business_details['company_name'] = ' Nissim Ben Aderet';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '028015394';
	$business_details['street'] = 'Benbenisti';
	$business_details['number'] = '9';
	$business_details['city'] = 'Tel-Aviv';
	$business_details['zip'] = '66087';
	$business_details['phone'] = '972-54-6791036';
	//$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://secure.pnc.co.il/pnc.co.il/images/global/spacer.gif';
    
    include 'invoice.en.php';
?>
