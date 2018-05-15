<?php
	$business_details['company_name'] = 'גיא טק פתרונות מיחשוב';
	$business_details['company_number_type'] = 'ע.מ.';
	$business_details['company_number'] = '034388900';
	$business_details['street'] = 'היהלום';
	$business_details['number'] = '18';
	$business_details['city'] = 'גבעת זאב';
	$business_details['zip'] = '';
	$business_details['phone'] = '052-6443366';
	$business_details['website'] = 'www.gaitech.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.$business_details['website'];
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/124/logo__guy_tech.png';
    include 'invoice.he.php';
?>
