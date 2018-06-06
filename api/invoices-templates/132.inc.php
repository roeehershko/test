<?php
	$business_details['company_name'] = 'פעם בשבוע';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '024246274';
	$business_details['street'] = 'בן גוריון';
	$business_details['number'] = '9';
	$business_details['city'] = 'רעננה';
	$business_details['zip'] = '43360';
	$business_details['phone'] = '077-5321244';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/132/logo__shavua.gif';
    include 'invoice.he.php';
    
?>
