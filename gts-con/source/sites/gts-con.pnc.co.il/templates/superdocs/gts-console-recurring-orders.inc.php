<?php
	
	include 'templates/superdocs/gts-console-init.inc.php';
	
	$gts_credentials = getVar('gts_credentials');

	if (!$merchant_details['support_recurring_orders']) {
		
		// User is not allowed to use Recurring Orders
		?>
		<div class="tickets">
			<?=$strings['reucrring_orders_not_supported']?>
		</div>
		<?
		
	} else if ($gts_credentials && !$user_details['must_change_password'] && !$merchant_details['must_change_password'] && !$_GET['reset_password'] && !$_GET['token']) {
		
		// In order to prevent server load - we pre-define the from-to dates
		if (!$_GET['from_date'] || !$_GET['to_date']) {
			$_GET['from_date'] = date('Ymd', time()-(3600*24*1));
			$_GET['to_date'] = date('Ymd');
		}
		
		$limit = $_GET['limit'] ?: 10;
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserRecurringOrders', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'merchant_id' => $_GET['merchant_id'],
					'date_start' => strtotime($_GET['from_date']),
					'date_end' => strtotime($_GET['to_date']),
					'search' => $_GET['query'],
					'offset' => (($_GET['page'] > 0 ? ($_GET['page']-1) : 0) * $limit),
					'limit' => $limit,
					'orderby' => 'timestamp',
					'sorting' => 'desc'
				)))
			)
		)));
		
		//echo '<pre>'; print_r(json_decode($response, true)); echo '</pre>';
		//exit;
		
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$total = $response['data']['total'];
				$transactions = $response['data']['recurring_orders'];
				if (!empty($transactions)) {
					for ($i = 0; $i < count($transactions); $i++) {
						// Getting the transactions details
						$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserRecurringOrderDetails', false, stream_context_create(array(
							'http' => array (
								'method' => 'POST',
								'header' => 'Content-type: application/json' . "\r\n",
								'content' => json_encode(strip_nulls(array(
									'ro_id' => $transactions[$i],
								)))
							)
						)));
						
						//echo '<pre>'; print_r(json_decode($response, true)); echo '</pre>';
						//exit;
						if ($response = json_decode($response, true)) {
							$transactions[$i] = $response['data'];
						}
						
					}
				}
			}
		}
				
		$search_link = $doc;
		if ($_GET) {
			foreach($_GET as $name => $value ) {
				if ($name != 'doc' && $value && $name != 'page' && $name != 'sort' && $name != 'limit') {
					if ($flag) {
						$search_link .= '?'.$name.'='.$value;
						$flag = false;
					} else {
						$search_link .= '&'.$name.'='.$value;
					}
				}
			}
		}
		$search_link .= ($flag ? '?' : '&').'page=';
		$search_link = htmlspecialchars($search_link);
		
		$navigation = navigation_handler($total, $limit, $_GET['page']);
		
		/* END OF GTS LOGICS */
		
		echo $group_object->description;
		
		?>
		<div class="tickets">
			<div class="actions">
				<button class="add" style="float:right" onclick="toggle_recurring_order_form(null)">+ <?=$strings['add_recurring_order']?></button>
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
				<button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
			</div>
			<div class="search_container">
		       	<div class="huge_blue"><?=$strings['search_index']?></div>
	    		<form name="search_form" action="" method="GET">
	    			<?
	    			if ($user_details['merchants'][1]) {
		    			// We allow to swtich merchants only if there's more than one.
		    			?>
		    			<span><?=$strings['choose_merchant']?></span>
						<select name="merchant_id" class="long">
							<option value=""><?=$strings['all']?></option>
							<?
							foreach ($user_details['merchants'] as $merchant) {
								// Building the $merchants array for easy usage later
								$merchants[$merchant['id']] = $merchant;
								?>
								<option value="<?=$merchant['id']?>"<?=$_GET['merchant_id'] == $merchant['id'] ? ' selected' : false ?>><?=$merchant['company']['name'].' - '.$merchant['company']['number']?></option>
								<?
							}
							?>
						</select>
		    			<?
	    			}
	    			?>
	    			<span><?=$strings['dates']?></span>
	    			<input type="hidden" id="from_date" name="from_date" value="<?=$_GET['from_date']?>">
					<input type="text" id="from_date__visual" placeholder="<?=$strings['from_date']?>" value="<?=$_GET['from_date'] ? date('d/m/Y', strtotime($_GET['from_date'])) : false ?>" onkeyup="!$(this).val() ? $('#from_date').val('') : false "> 
					<input type="hidden" id="to_date" name="to_date" value="<?=$_GET['to_date']?>">
					<input type="text" id="to_date__visual" placeholder="<?=$strings['to_date']?>" value="<?=$_GET['to_date'] ? date('d/m/Y', strtotime($_GET['to_date'])) : false ?>" onkeyup="!$(this).val() ? $('#to_date').val('') : false "> 
					
					<span><?=$strings['free_search']?></span>
	            	<input type="text" class="long" name="query" value="<?=htmlspecialchars($_GET['query'])?>">
	    			<button><?=$strings['search']?></button>
	                <?
	                // Clear filter button
	                $search_filters = array_filter(array(isset($_GET['merchant_id']), isset($_GET['company_id']), isset($_GET['query']), isset($_GET['from_date']), isset($_GET['to_date'])));
	                if (!empty($search_filters)) {
	                	?>
	                    <button onclick="window.location = '<?=href($doc)?>';return false;"><?=$strings['clear_search']?></button>
	                    <?
		            }
	                ?>
	    		</form>
	    	</div>
			<?
			if (!empty($transactions)) {
				?>
				<div class="navigation filters">
					<div class="text"><?=$strings['showing_results']?>: <b><?=$navigation['start']+1?> - <?=$navigation['end']+1?></b> <?=$strings['from']?> <?=$navigation['total']?></div>
				</div>
				<?
			}
			if (!empty($transactions)) {
		        ?>
	    		<div class="ticket row th recurring_order">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
					<div class="ticket_d name"><?=$strings['name']?></div>
					<div class="ticket_d duedate__visual"><?=$strings['duedate']?></div>
					<div class="ticket_d repetitions"><?=$strings['repetitions']?></div>
					<div class="ticket_d interval__visual"><?=$strings['interval']?></div>
					<div class="ticket_d amount"><?=$strings['amount']?></div>
					<div class="ticket_d cc_type"><?=$strings['cc_type']?></div>
	    			<div class="ticket_d cc_last_4"><?=$strings['cc_last_4']?></div>
	    			<div class="ticket_d cc_exp"><?=$strings['cc_exp']?></div>
	    			<div class="ticket_d created"><?=$strings['modified']?></div>
	    			<div class="ticket_d note"></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($transactions); $i++) {
	        		//debug(json_encode($transactions[$i]));
	        		?>
	        		<div id="transaction_<?=$transactions[$i]['ro_id']?>" class="ticket row recurring_order">
	        			<input type="hidden" class="currency" value="<?=$transactions[$i]['currency']?>">
	        			<input type="hidden" class="duedate" value="<?=$transactions[$i]['duedate']?>">
	        			<input type="hidden" class="interval" value="<?=$transactions[$i]['interval']?>">
	        			<input type="hidden" class="invoice_customer_name" value="<?=$transactions[$i]['invoice_customer_name']?>">
	        			<input type="hidden" class="invoice_description" value="<?=$transactions[$i]['invoice_description']?>">
	        			<input type="hidden" class="invoice_recipients" value="<?=!empty($transactions[$i]['invoice_recipients']) ? implode(',', $transactions[$i]['invoice_recipients']) : '' ?>">
	        			
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d ro_id" title="<?=$merchants[$transactions[$i]['merchant_id']] ? $merchants[$transactions[$i]['merchant_id']]['company']['name'].($merchants[$transactions[$i]['merchant_id']]['company']['number'] ? ' ('.$merchants[$transactions[$i]['merchant_id']]['company']['number'].')' : false) : false ?>"><?=$transactions[$i]['ro_id']?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d name"><?=$transactions[$i]['name']?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d duedate__visual"><?=date('d/m/Y', strtotime($transactions[$i]['duedate']))?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d repetitions"><?=$transactions[$i]['repetitions'] ?: '-' ?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d interval__visual"><?=$strings['recurring_order_interval__'.strtolower($transactions[$i]['interval'])]?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d amount"><?=$strings['currency_'.strtolower($transactions[$i]['currency'])].' '.number_format($transactions[$i]['amount'], 2)?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d cc_type"><?=ucwords($transactions[$i]['cc_type'] ?: '-' )?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d cc_last_4"><?=$transactions[$i]['cc_last4'] ?: '-' ?></div>
		    			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d cc_exp"><?=$transactions[$i]['cc_exp'] ? substr($transactions[$i]['cc_exp'], 0, 2).'/'.substr($transactions[$i]['cc_exp'], 2, 2) : '-' ?></div>
		    			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d created"><?=date('d/m/Y', strtotime($transactions[$i]['created']))?></div>
	        			<div onclick="toggle_recurring_order_form($(this).parent())" class="ticket_d note" title="<?=htmlspecialchars($transactions[$i]['params']['note'])?>"><?=$transactions[$i]['params']['note'] ? '[ ! ]' : false ?></div>
						<?
						if (!empty($transactions[$i]['transactions'])) {
							?>
							<div class="ticket_d link"><a href="<?=href('gts/console/isracard/transactions?recurring_order='.$transactions[$i]['ro_id'])?>" title="<?=$strings['transactions_history']?>"><?=$strings['transactions']?></a></div>
							<?	
						}
						?>
	        		</div>
	        		<?
	        	}
        	} else {
	        	?>
	        	<div class="no_results"><?=$strings['no_results_found']?></div>
	        	<?
	        }
	        include 'templates/global/navigationbar.inc.php';
	        ?>
	    </div>
	    <?
    }
?>