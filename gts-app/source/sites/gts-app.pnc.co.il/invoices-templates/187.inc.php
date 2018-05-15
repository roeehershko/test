<?php
    $business_details['company_name'] = 'ש.ח יבוא ושיווק';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '558067518';
	$business_details['street'] = 'לולב';
	$business_details['number'] = '54';
	$business_details['city'] = 'נצרת עילית' ;
	$business_details['zip'] = '17511';
	$business_details['phone'] = '052-3465767';
	//$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://secure.pnc.co.il/pnc.co.il/images/global/spacer.gif';
    
    include 'invoice.he.php';
?>
