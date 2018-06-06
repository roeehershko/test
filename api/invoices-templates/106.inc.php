<?php
	$invoice['company']['details'] = 'איי-דאטה, ח"פ 303332340, ראול ולנברג 6, תל-אביב, טלפון: 03-6496065';
	if (!$_GET['token']) {
		$invoice['company']['details'] = 'איי-דאטה, ע"מ ,303332340 ראול ולנברג ,6 תל-אביב, טלפון: 5606946-30';
	}
	$invoice['company']['contact_person'] = '';
	$invoice['company']['contact_person_title'] = '';
	$invoice['company']['logo'] = 'https://'.constant('GTS_APP_HOST').'/invoices-templates/images/106/logo__idata.png';
    include 'invoice.he.php';
?>
