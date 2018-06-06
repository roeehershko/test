<?php

	$invoice['company']['details'] = 'רביד מחשבים, ע.מ. 036392967, חטיבת כרמלי 16 חדרה 38268, טלפון: 050-6595547'.'<br>'.'info@ravid-pc.co.il, www.ravid-pc.co.il';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'li.oc.cp-divar.www ,li.oc.cp-divar@ofni'.'>rb<'.'רביד מחשבים, ע.מ. ,036392967 חטיבת כרמלי 16 חדרה 38268, טלפון: 7455956-050';
	}	
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = 'רביד מחשבים';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/125/logo__ravid.jpg';
    include 'invoice.he.php';
    
?>
