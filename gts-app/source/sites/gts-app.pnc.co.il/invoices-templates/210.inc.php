<?php
    $business_details['company_name'] = 'ש.ט. קריאיישן בע"מ';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '514746932';
	$business_details['street'] = 'ארלוזרוב';
	$business_details['number'] = ' 37/2';
	$business_details['city'] = 'תל אביב';
	$business_details['zip'] = '62488';
	$business_details['phone'] = '054-4883939';
	//$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/spacer.gif';
    
    $without_vat_override = true;
    
    include 'invoice.he.php';
?>
