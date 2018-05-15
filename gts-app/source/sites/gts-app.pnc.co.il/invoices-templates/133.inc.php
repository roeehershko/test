<?php
	$business_details['company_name'] = 'הצימר של לילי';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '054506712';
	$business_details['street'] = 'נוף יערה';
	$business_details['number'] = '223';
	$business_details['city'] = 'מושב-יערה';
	$business_details['zip'] = '22840';
	$business_details['phone'] = '050-4719102';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/133/logo__lili.jpg';
    include 'invoice.he.php';
    
?>
