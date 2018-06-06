<?php
    $business_details['company_name'] = 'עוד אבי חי';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '580508950';
	$business_details['street'] = 'הושע';
	$business_details['number'] = '13';
	$business_details['city'] = 'בני ברק';
	$business_details['zip'] = '51364';
	$business_details['phone'] = '054-6000761';
	//$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/215/logo.jpg';
    if (abs($amount) == $amount) {
		$strings['invoice'] = 'מס תרומה';
		
	} else {
		$strings['invoice'] = 'מס תרומה זיכוי';
	}
    include 'invoice.he.php';
?>
