<?php
    $business_details['company_name'] = 'טרייד רום בר';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '514639640';
	$business_details['street'] = 'אהרון בקר';
	$business_details['number'] = '8';
	$business_details['city'] = 'תל-אביב';
	$business_details['zip'] = '69643';
	$business_details['phone'] = '08-9933352';
	//$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://secure.pnc.co.il/images/global/spacer.gif';
    
    include 'invoice.he.php';
?>
