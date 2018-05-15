<?php

	if (strpos($doc, 'en/') !== false || $doc_id == constant('HOME_DOC_ID')) {
		$language = 'english';
		$language_prefix = 'English - ';
		$lang_prefix = 'en_';
	}
	
	if (!$user['id'] && strpos($doc, 'gts/signatures') === false && strpos($doc, 'gts/console') === false) {
		Header('Location:'.href('login?referrer='.rawurlencode($doc)));
		exit;
	}
	
	if ($doc == 'my-account') {
		// Redirecting to /support
		Header('Location:'.constant('HTTPS_URL').'support/');
		exit;
	}

	include 'include/strings/'.$lang_prefix.'strings.inc.php';

	$user_object = new User($user['id']);
	if ($doc_type == 'group') {
		$group_object = new Group($doc_id);
	}
	
	// gets a ticket id and returns its last message timestamp
	// or 'false' in case there are no messages associated with this ticket.
	function get_last_message_timestamp($ticket_id){
		$search__doc_type = array('element', 'message');
		$constraints = false;
		$query = false;
		$query[] = array(array('message:ticket_id'), $ticket_id);
		$expression = 'and';
		$sort = false;
		$sort[] = array('message:created_timestamp', 'DESC', 1);
		$messages = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);

		$last_message_timestamp = false;
		if ($messages[0]) {
			$message_object = new Element($messages[0]);
			$message_details = $message_object->message;
			$last_message_timestamp = $message_details['created_timestamp'];
		}
		return $last_message_timestamp;
	}
	
	$filler     = '<img src="' . href('images/global/spacer.gif') . '" alt="" class="filler">';
	$title_icon = $doc_parameters['title_icon'] ? href($doc_parameters['title_icon']) : href('files/icons/default_icon.png'); 
	?>
	<link type="text/css" rel="stylesheet" href="<?=href('include/css/my-account.css?version='.filectime('include/css/my-account.css'))?>"/>
	<!--[if IE]>
		<link type="text/css" rel="stylesheet" href="<?=href('include/css/my-account-ie.css?version='.filectime('include/css/my-account-ie.css'))?>"/>
	<![endif]-->
	<?
	if ($language == 'english') {
    	?>
    	<link type="text/css" rel="stylesheet" href="<?=href('include/css/'.$lang_prefix.'my-account.css?version='.filectime('include/css/'.$lang_prefix.'my-account.css'))?>"/>
    	<?
    }
	?>
	<div class="box high">
		<version><?=constant('VERSION')?></version>
		<?
		if ($group_object->header_image) {
			?>
			<img class="image" src="<?=href($group_object->header_image)?>">
			<?	
		} else {
			?>
			<a class="paragon_logo"><img src="<?=href('images/global/logo.png')?>" alt="<?=htmlspecialchars(constant('SITE_TITLE'))?>"></a>
			<img class="image" src="<?=href('images/global/wave_205.jpg')?>">
			<?
		}
		?>
	</div>
	<div class="box flex">
		<?
		if ($doc == 'support') {
			?>
			<h1 onclick="window.location='<?=href($doc.($_GET['status'] ? '&status='.$_GET['status'] : false).($_GET['assigned_to'] ? '&assigned_to='.$_GET['assigned_to'] : false).($_GET['customer'] ? '&customer='.$_GET['customer'] : false).($_GET['page'] ? '&page='.$_GET['page'] : false))?>'"><?=$group_object->title?></h1>
			<?
		} else if ($group_object->title) {
			?>
			<h1><?=$group_object->title?></h1>
			<?	
		}
		?>
		<div class="description">
			<?
			if (strpos($doc, 'gts/console/isracard/transactions') !== false || strpos($doc, 'gts/console/verifone/transactions') !== false) {
				
				include 'templates/superdocs/gts-console-transactions.inc.php';
			
			} else if (strpos($doc, 'gts/console/isracard/recurring-orders') !== false || strpos($doc, 'gts/console/verifone/recurring-orders') !== false) {
				
				include 'templates/superdocs/gts-console-recurring-orders.inc.php';
			
			} else if (strpos($doc, 'gts/console/isracard/errors') !== false || strpos($doc, 'gts/console/verifone/errors') !== false) {
				
				include 'templates/superdocs/gts-console-errors.inc.php';
			
			} else if (strpos($doc, 'gts/verifone/merchants/associate') !== false) {
				
				include 'templates/superdocs/gts-verifone-merchants-associate.inc.php';
				
			} else if (strpos($doc, 'gts/verifone/users') !== false) {
				
				include 'templates/superdocs/gts-verifone-users.inc.php';
			
			} else if (strpos($doc, 'gts/verifone/reports') !== false) {
				
				include 'templates/superdocs/gts-verifone-reports.inc.php';
				
			} else if (strpos($doc, 'gts/verifone') !== false) {
				
				include 'templates/superdocs/gts-verifone-merchants.inc.php';
				
			} else if (strpos($doc, 'gts/signatures') !== false) {
				
				include 'templates/superdocs/gts-signatures.inc.php';
				
			} else if (($doc == 'support' || $doc == 'en/support') && $_GET['ticket_id']) {
				
				include 'templates/superdocs/my-account-edit-ticket.inc.php';
				
			} else if ($doc == 'support' || $doc == 'en/support') {
				
				include 'templates/superdocs/my-account-tickets.inc.php';
				
			} else if (strpos($doc, 'tasks') !== false && $_GET['task_id']) {
				
				include 'templates/superdocs/my-account-edit-task.inc.php';
				
			} else if (strpos($doc, 'tasks') !== false) {
				
				include 'templates/superdocs/my-account-tasks.inc.php';
				
		    } else if ($doc == 'erp/projects' && $_GET['project_id']) {
				
				include 'templates/superdocs/my-account-edit-project.inc.php';
				
			} else if ($doc == 'erp/projects') {
				
				include 'templates/superdocs/my-account-projects.inc.php';
				
		    }
			?>
		</div>
	</div>
	<?        

?>
