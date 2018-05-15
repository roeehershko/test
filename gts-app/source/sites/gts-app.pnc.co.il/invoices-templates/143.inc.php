<?php
	$business_details['company_name'] = 'מיקרוליין התקנות';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '036801116';
	$business_details['street'] = 'עוזיאל';
	$business_details['number'] = '47';
	$business_details['city'] = 'שלומי';
	$business_details['zip'] = '22832';
	$business_details['phone'] = '1-700-700-636';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/143/logo__microline.png';
    include 'invoice.he.php';
?>
