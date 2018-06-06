<?php
	$business_details['company_name'] = 'כוח קנייה בע"מ';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '514626050';
	$business_details['street'] = 'סוקולוב';
	$business_details['number'] = '111';
	$business_details['city'] = 'רמת-השרון';
	$business_details['zip'] = '47239';
	$business_details['phone'] = '054-7000046';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/136/logo__koah_kniya.jpg';
    include 'invoice.he.php';
?>
