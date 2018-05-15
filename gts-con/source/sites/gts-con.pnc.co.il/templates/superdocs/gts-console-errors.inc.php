<?php

	include 'templates/superdocs/gts-console-init.inc.php';
	$gts_credentials = getVar('gts_credentials');
	
	/*
	recursive('trim', $_GET);
	//recursive('stripslashes', $_GET);
	recursive('strip_tags', $_GET);
	
	$lang_code = 'he';

	if ($_POST['username'] || $_POST['password']) {
		
		// Getting the access token
		$response = file_get_contents('https://'.$_POST['username'].':'.$_POST['password'].'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/createAccessToken', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => '',
			)
		)));
		//echo '<pre>'; print_r($response); echo '</pre>';
		//exit;
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$gts_credentials = $_POST['username'].':'.$response['data']['access_token'];
				setVar('gts_credentials', $gts_credentials);
				// Checking if the user is an manager
				// TBD
				Header('Location:'.href($doc));
				exit;
			} else {
				$errors[] = $response['error']; //'שם משתמש או סיסמא שגויים.';
				include 'templates/superdocs/gts-login.inc.php';				
			}
		}
		
	} else if (!getVar('gts_credentials')) {
		
		// If no access token exists, we show the login screen
		include 'templates/superdocs/gts-login.inc.php';
	
	} else if ($_GET['action'] == 'logout') {
	
		unsetVar('gts_credentials');
		unsetVar('gts_admin');
		unsetVar('gts_user_id');
		
		Header('Location:'.href($doc));
		exit;
	
	} else 
	*/
	
	//echo '<pre>'; print_r($gts_credentials); echo '</pre>';
	//exit;
	
	if ($gts_credentials = getVar('gts_credentials')) {
		
		// In order to prevent server load - we pre-define the from-to dates
		/*
		if (!$_GET['from_date'] || !$_GET['to_date']) {
			$_GET['from_date'] = date('Ymd', time()-(3600*24*7));
			$_GET['to_date'] = date('Ymd');
		}
		*/
		
		$limit = $_GET['limit'] ?: 50;
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactionsCreditErrors', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'admin' => $gts_admin,
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
				$transactions = $response['data']['transactions_errors'];
				if (!empty($transactions)) {
					for ($i = 0; $i < count($transactions); $i++) {
						// Getting the transactions details
						$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactionCreditErrorDetails', false, stream_context_create(array(
							'http' => array (
								'method' => 'POST',
								'header' => 'Content-type: application/json' . "\r\n",
								'content' => json_encode(strip_nulls(array(
									'admin' => $gts_admin,
									'merchant_id' => $_GET['merchant_id'],
									'id' => $transactions[$i],
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
		
		$navigation = navigation_handler($total, $limit, $_GET['page']);
		
		/* END OF GTS LOGICS */
		
		echo $group_object->description;
		
		?>
		<div class="tickets">
			<div class="actions">
				<?
					if (strpos($doc, 'gts/verifone/reports') !== false) {
						?>
						<button class="back" style="float:right" onclick="window.history.back()"><?=$strings['back']?></button>
						<?
					}
				?>
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
                <button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
				<button class="refresh hidden" onclick="window.location='<?=href($doc)?>'"><?=$strings['back_button']?></button>
			</div>
			<div class="search_container">
		       	<div class="huge_blue"><?=$strings['search_index']?></div>
	    		<form name="search_form" action="" method="GET">
	    			<?
					if ($gts_admin) {
						?>
						<input type="hidden" name="type" value="<?=$_GET['type']?>">
						<input type="hidden" name="merchant_id" value="<?=$_GET['merchant_id']?>">
						<?
					}
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
	    		<div class="ticket row th transaction">
	    			<div class="ticket_d trans_status"></div>
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d error_description"><?=$strings['error_description']?></div>
					<div class="ticket_d amount"><?=$strings['amount']?></div>
	    			<div class="ticket_d trans_type"><?=$strings['credit_terms']?></div>
	    			<div class="ticket_d cc_type"><?=$strings['cc_type']?></div>
	    			<div class="ticket_d cc_last_4"><?=$strings['cc_last_4']?></div>
	    			<div class="ticket_d cc_exp"><?=$strings['cc_exp']?></div>
	    			<div class="ticket_d timestamp"><?=$strings['timestamp']?></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($transactions); $i++) {
	        		//debug(json_encode($transactions[$i]));
	        		?>
	        		<div id="transaction_<?=$transactions[$i]['trans_id']?>" class="ticket row transaction">
	        			<div class="ticket_d trans_status">
	        				<?
	        				$status_icon_url = href('images/gts/default/status-credit-test@2x.png');
	        				?>
	        				<img src="<?=$status_icon_url?>">
	        			</div>
	        			<div class="ticket_d id" title="<?=$merchants[$transactions[$i]['merchant_id']] ? $merchants[$transactions[$i]['merchant_id']]['company']['name'].($merchants[$transactions[$i]['merchant_id']]['company']['number'] ? ' ('.$merchants[$transactions[$i]['merchant_id']]['company']['number'].')' : false) : false ?>"><?=$transactions[$i]['id']?></div>
	        			<div class="ticket_d error_description" title="<?=htmlspecialchars($transactions[$i]['error'])?>"><?=$transactions[$i]['error'] ?: '-' ?></div>
	        			<?
	        			$transactions[$i]['currency'] = $transactions[$i]['currency'] == 'ILS' ? '₪' : $transactions[$i]['currency'];
        				if ($transactions[$i]['payments_number'] >= 2) {
	        				if ($transactions[$i]['payments_first_amount'] == $transactions[$i]['payments_standing_amount']) {
		        				$amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['payments_standing_amount'], 2).' x '.($transactions[$i]['payments_number']);
	        				} else {
		        				$amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['payments_first_amount'], 2).' + '.number_format($transactions[$i]['payments_standing_amount'], 2).' x '.($transactions[$i]['payments_number']-1);	
	        				}
	        			} else {
		        			$amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['amount'], 2);
	        			}
        				?>
	        			<div class="ticket_d amount" title="<?=$amount?>"><?=$amount?></div>
	        			<div class="ticket_d trans_type"><?=ucwords($strings[$transactions[$i]['credit_terms'] ? 'credit_terms__'.$transactions[$i]['credit_terms'] : 'transaction_type_'.$transactions[$i]['type']])?></div>
	        			<div class="ticket_d cc_type"><?=ucwords($transactions[$i]['cc_type'] ?: '-' )?></div>
	        			<div class="ticket_d cc_last_4"><?=htmlspecialchars($transactions[$i]['cc_last_4'] ?: '-') ?></div>
		    			<div class="ticket_d cc_exp"><?=htmlspecialchars($transactions[$i]['cc_exp'] ? substr($transactions[$i]['cc_exp'], 0, 2).'/'.substr($transactions[$i]['cc_exp'], 2, 2) : '-') ?></div>
		    			<div class="ticket_d timestamp"><?=date('d/m/Y H:i', strtotime($transactions[$i]['timestamp']))?></div>
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