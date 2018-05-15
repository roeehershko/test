<?php

	if ($_GET['debug']) {
		echo '<pre>'; print_r($trans); echo '</pre>';
		echo '<pre>'; print_r($merchant); echo '</pre>';
		exit;
	}

	$business_details['company_name'] = $merchant->company_name;
	$business_details['company_number_type'] = $merchant->params->company_type;
	$business_details['company_number'] = $merchant->company_id;
	$business_details['street'] = $merchant->params->company->address->street;
	$business_details['number'] = $merchant->params->company->address->number;
	$business_details['city'] = $merchant->params->company->address->city;
	$business_details['zip'] = $merchant->params->company->address->zip;
	$business_details['phone'] = $merchant->params->company->phone;
	$business_details['email'] = $merchant->company_email;
	//$business_details['website'] = 'pnc.co.il';
	$business_details['logo'] = $merchant->params->company->logo;

    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', Te: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' Tel: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = $business_details['logo'];

	$without_vat_override = true;

	include 'invoice.en.php';

?>
