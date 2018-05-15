<?php
    $business_details['company_name'] = 'פאראגון קריאיישנס בע"מ';
	$business_details['company_number_type'] = 'ח"פ';
	$business_details['company_number'] = '513555615';
	$business_details['street'] = 'המגשימים';
	$business_details['number'] = '20';
	$business_details['city'] = 'פתח-תקווה';
	$business_details['zip'] = '52511';
	$business_details['phone'] = '03-5708989';
	$business_details['email'] = 'info@pnc.co.il';
	//$business_details['website'] = 'pnc.co.il';	
    
    $invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' '.$business_details['company_number'].', '.$business_details['street'].' '.$business_details['number'].', '.$business_details['city'].' '.$business_details['zip'].', טלפון: '.$business_details['phone'].' <span dir="ltr" style="direction:ltr;text-align:left">'.$business_details['website'].' '.$business_details['email'].'</span>';
    if (!$_GET['token']) {
    	$invoice['company']['details'] = $business_details['company_name'].', '.$business_details['company_number_type'].' ,'.$business_details['company_number'].' '.$business_details['street'].' ,'.$business_details['number'].' '.$business_details['city'].' ,'.$business_details['zip'].' טלפון: '.strrev($business_details['phone']).' '.strrev($business_details['email']).' '.$business_details['website'];
	}
    $invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = $business_details['company_name'];
    $invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/1/paragon_logo_310x80.png';
    
    if ($_GET['lang'] == 'en') {
        include 'invoice.en.php';
    } else {
        include 'invoice.he.php';
    }
?>
