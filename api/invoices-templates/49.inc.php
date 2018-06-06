<?php
	$invoice['company']['details'] = 'גליל אירית, ח"פ 028471084, רח\' ז\'בוטינסקי 24 חולון, 58285, טלפון: 035043311';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'גליל אירית, ח"פ 028471084, רח\' ז\'בוטינסקי 24 חולון, 58285, טלפון: 035043311';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'גליל אירית';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/49/logo__visaform.jpg';
   	
    include 'invoice.he.php';
    
	//echo '<pre dir="ltr">'; print_r($invoice); echo '</pre>';
?>
