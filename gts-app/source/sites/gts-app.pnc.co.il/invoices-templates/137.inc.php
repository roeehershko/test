<?php
	$business_details['company_name'] = 'או.אס שירותים ותקשורת';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '034570887';
	$business_details['street'] = 'ת.ד.';
	$business_details['number'] = '3270';
	$business_details['city'] = 'חדרה';
	$business_details['zip'] = '84132';
	$business_details['phone'] = '050-2555553';
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'];
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/137/logo__os.png';
    include 'invoice.he.php';
?>
