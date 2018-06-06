<?php
	$business_details['company_name'] = 'איימקס';
	$business_details['company_number_type'] = 'עוסק פטור';
	$business_details['company_number'] = '028084564';
	$business_details['street'] = 'מקור חיים';
	$business_details['number'] = '4';
	$business_details['city'] = 'ירושלים';
	$business_details['zip'] = '93465';
	$business_details['phone'] = '052-6752828';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/142/logo__imex.png';
    include 'invoice.he.php';
?>
