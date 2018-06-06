<?php
	$business_details['company_name'] = 'מעבדת כוננות';
	$business_details['company_number_type'] = 'עוסק מורשה';
	$business_details['company_number'] = '326809134';
	$business_details['street'] = 'שילה';
	$business_details['number'] = '40';
	$business_details['city'] = 'ת.ד.';
	$business_details['zip'] = '9380';
	$business_details['phone'] = '02-9400595';
	$business_details['email'] = '007193@gmail.com';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']);
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/141/logo__konenut.jpg';
    include 'invoice.he.php';
?>
