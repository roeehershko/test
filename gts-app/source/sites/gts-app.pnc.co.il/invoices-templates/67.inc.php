<?php
    
	$logo_alignment = 'left';
	
	$invoice['company']['details'] = 'LinkU Communications Ltd.';
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'Accounting Department';
	$invoice['company']['logo'] = 'https://secure.pnc.co.il/linkusim.com/images/global/logo.png';
    
	//echo '<pre dir="ltr">' . print_r(unserialize($details), 1) . '</pre>';
	//exit;
    
	include 'invoice.en.php';
	
?>
