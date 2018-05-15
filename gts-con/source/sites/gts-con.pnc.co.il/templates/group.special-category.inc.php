<?php

	if ($doc == 'support' || $doc == 'en/support' || $doc == 'tasks' || $doc == 'en/tasks' || strpos($doc, 'erp/') !== false || strpos($doc, 'gts/') !== false) {
		
		include 'templates/superdocs/my-account.inc.php';
		
	} else {
		
		include 'templates/group.inc.php';
		
	}
	
?>