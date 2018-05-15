<?php

	recursive('trim', $_GET);
	//recursive('stripslashes', $_GET);
	recursive('strip_tags', $_GET);
	
	$lang_code = 'he';

	if ($doc == 'gts/signatures/company') {
		$type = 'company';
	} else if ($doc == 'gts/signatures/merchant') {
		$type = 'merchant';
	} else if ($doc == 'gts/signatures/user') {
		$type = 'user';
	} else if ($doc == 'gts/signatures/transactions') {
		$type = 'transaction';
	}

	if ($_POST['username'] || $_POST['password']) {
		
		// Getting the access token
		$response = file_get_contents('https://'.$_POST['username'].':'.$_POST['password'].'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/createAccessToken', false, stream_context_create(array(
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
				// Checking if the user is an administrator
				$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainUserItems', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode(strip_nulls(array(
							'id' => $response['data']['user_id'],
							'return' => array('administrator'),
						)))
					)
				)));
				$response = json_decode($response, true);
				//echo '<pre>'; print_r($response); echo '</pre>';
				//exit;
				unsetVar('gts_admin');
				if ($response['result'] == 'OKAY' && $response['data']['user_items'][0]['administrator']) {
					setVar('gts_admin', true);
				}
				
				setVar('gts_user_id', $response['data']['user_items'][0]['id']);
				
				Header('Location:'.href($doc));
				exit;
			} else {
				$errors[] = $response['error']['text'] ?: $response['error']['code']; //'שם משתמש או סיסמא שגויים.';
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
	
	} else if ($type != 'transaction' && !getVar('gts_admin')) {
	
		unsetVar('gts_credentials');
		Header('Location:'.href($doc.'?errors[]='.rawurlencode($strings['error__access_denied'])));
		exit;
	
	} else if ($gts_credentials = getVar('gts_credentials')) {
		
		if ($type == 'transaction') {
			
			// In order to prevent server load - we pre-define the from-to dates
			if (!$_GET['from_date'] || !$_GET['to_date']) {
				$_GET['from_date'] = date('Ymd', time()-(3600*24*1));
				$_GET['to_date'] = date('Ymd');
			}
			
			$limit = $_GET['limit'] ?: 20;
			/*
			$request = array(
				'id' => is_numeric($_GET['query']) ? $_GET['query'] : false,
				'merchant_id' => $_GET['merchant_id'],
				'created_min' => !is_numeric($_GET['query']) ? strtotime($_GET['from_date']) : false,
				'created_max' => !is_numeric($_GET['query']) ? (strtotime($_GET['to_date']) + (3600*24)) : false,
				'query' => !is_numeric($_GET['query']) ? $_GET['query'] : false,
				'offset' => ($_GET['page'] * $limit),
				'limit' => $limit,
				'orderby' => 'created',
				'sorting' => 'desc',
				'return' => array('merchant_id', 'amount', 'currency', 'cc_last_4', 'cc_exp', 'cc_issuer', 'credit_terms', 'transaction_code', 'payments_number', 'payments_currency_linked', 'payments_first_amount', 'payments_standing_amount', 'authorization_number', 'cashier_voucher_number', 'tourist_card', 'reference_number', 'signature_link', 'created', 'modified', 'params'),
			);
			*/
			$request = array(
				'id' => false,
				'merchant_id' => $_GET['merchant_id'],
				'created_min' => strtotime($_GET['from_date']),
				'created_max' => strtotime($_GET['to_date']) + (3600*24),
				'query' => $_GET['query'],
				'offset' => ($_GET['page'] * $limit),
				'limit' => $limit,
				'orderby' => 'created',
				'sorting' => 'desc',
				'return' => array('merchant_id', 'amount', 'currency', 'cc_last_4', 'cc_exp', 'cc_issuer', 'credit_terms', 'transaction_code', 'payments_number', 'payments_currency_linked', 'payments_first_amount', 'payments_standing_amount', 'authorization_number', 'cashier_voucher_number', 'tourist_card', 'reference_number', 'signature_link', 'created', 'modified', 'params'),
			);
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainTransactionItems', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		} else {
			$limit = $_GET['limit'] ?: 100;
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtain'.ucfirst($type).'Items', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls(array(
						'query' => $_GET['query'],
						'offset' => ($_GET['page'] * $limit),
						'limit' => $limit,
						'orderby' => 'id',
						'sorting' => 'desc',
						'company_id' => $_GET['company_id'],
						'return' => array('username', 'name', 'number', 'company_id', 'merchant_ids', 'mailer_name', 'mailer_email', 'pos_number', 'report_inactivity', 'created', 'modified', 'params'),
					)))
				)
			)));
		}
		
		if ($_GET['d1']) {
			echo '<pre>'; print_r($request); echo '</pre>';
			echo '<pre>'; print_r($response); echo '</pre>';
			exit;
		}
		
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				if ($type == 'company') {
					$companies = $response['data']['company_items'];	
				} else if ($type == 'merchant') {
					$merchants = $response['data']['merchant_items'];
				} else if ($type == 'user') {
					$users = $response['data']['user_items'];
				} else if ($type == 'transaction') {
					$transactions = $response['data']['transaction_items'];
				}
				
				$total = $response['data']['total'];
				
			} else {
				// GTS access token expired
				Header('Location:'.href($doc.'?action=logout'));
				exit;
			}
		}
		
		if ($_GET['d2']) {
			echo '<pre>'; print_r($transactions); echo '</pre>';
			exit;
		}
		
		// Getting all the companies IDs and names
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainCompanyItems', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'query' => false,
					'offset' => false,
					'limit' => false,
					'orderby' => 'name',
					'sorting' => 'asc',
					'return' => array('name', 'number'),
				)))
			)
		)));
		
		if ($_GET['d3']) {
			echo '<pre>'; print_r(json_decode($response, true)); echo '</pre>';
			exit;
		}
		
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$all_companies = $response['data']['company_items'];
				if (!empty($all_companies)) {
					foreach ($all_companies as $company) {
						//$companies_ids_hash[$company['id']] = $company['name'];
						$companies_ids_hash[$company['id']] = array(
							'name' => $company['name'],
							'number' => $company['number'],
						);
						$companies_names_hash[$company['name']] = $company['id'];
					}
				}
			}
		}
		?>
		<script type="text/javascript">
			var all_companies = <?=json_encode($all_companies)?>;
			var companies_ids_hash = <?=json_encode($companies_ids_hash)?>;
			var companies_names_hash = <?=json_encode($companies_names_hash)?>;
		</script>
		<?
		
		// Getting all the merchants IDs and names
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainMerchantItems', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'query' => false,
					'offset' => false,
					'limit' => false,
					'orderby' => 'name',
					'sorting' => 'asc',
					'return' => array('name', 'number'), 
				)))
			)
		)));
		//echo '<pre>'; print_r($response); echo '</pre>';
		//exit;
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$all_merchants = $response['data']['merchant_items'];
				if (!empty($all_merchants)) {
					foreach ($all_merchants as $merchant) {
						$merchants_ids_hash[$merchant['id']] = array(
							'name' => $merchant['name'],
							'number' => $merchant['number'],
						);
						$merchants_names_hash[$merchant['name']] = $merchant['id'];
					}
				}
			}
		}
		?>
		<script type="text/javascript">
			var all_merchants = <?=json_encode($all_merchants)?>;
			var merchants_ids_hash = <?=json_encode($merchants_ids_hash)?>;
			var merchants_names_hash = <?=json_encode($merchants_names_hash)?>;
		</script>
		<?
		
		// For performance reasons - we disable the graph.
		$_GET['no_graph'] = 1;

		// Getting all the users IDs
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainUserItems', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'query' => false,
					'offset' => false,
					'limit' => false,
					'orderby' => 'name',
					'sorting' => 'asc',
					'return' => false, 
				)))
			)
		)));
		//echo '<pre>'; print_r($response); echo '</pre>';
		//exit;
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$all_users = $response['data']['user_items'];
			}
		}
		
		// Getting all the transactions IDs
		if ($type == 'transaction' && !$_GET['no_graph']) {
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainTransactionItems', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls(array(
						'query' => false,
						'merchant_id' => $_GET['merchant_id'],
						'created_min' => strtotime($_GET['from_date']),
						'created_max' => strtotime($_GET['to_date']) + (3600*24),
						'query' => $_GET['query'],
						'offset' => false,
						'limit' => false,
						'orderby' => 'name',
						'sorting' => 'asc',
						'return' => array('created', 'amount'), 
					)))
				)
			)));
			//echo '<pre>'; print_r(json_decode($response, true)); echo '</pre>';
			//exit;
			if ($response = json_decode($response, true)) {
				if ($response['result'] == 'OKAY') {
					$all_transactions = $response['data']['transaction_items'];
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
		
		//$navigation = navigation_handler(count($type == 'company' ? $all_companies : ($type == 'merchant' ? $all_merchants : ($type == 'user' ? $all_users : ($type == 'transaction' ? $all_transactions : false)))), $limit, $_GET['page']);
		$navigation = navigation_handler($total, $limit, $_GET['page']);
		
		/* END OF GTS LOGICS */
		
		echo $group_object->description;
		
		?>
		<div class="tickets">
			<div class="actions">
				<?
				if ($type == 'company') {
					?>
					<button class="add" style="float:right" onclick="toggle_company_form()">+ <?=$strings['add_company']?></button>
					<?
				} else if ($type == 'merchant') {
					?>
					<button class="add" style="float:right" onclick="toggle_merchant_form()">+ <?=$strings['add_merchant']?></button>
					<?
				} else if ($type == 'user') {
					?>
					<button class="add" style="float:right" onclick="toggle_user_form()">+ <?=$strings['add_user']?></button>
					<?
				}
				?>
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
				<button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
				<?
				if ($type == 'transaction') {
					?>
					<button class="change_password" onclick="toggle_change_password_form()"><?=$strings['change_password']?></button>
					<button class="export" onclick="gts_export({action:'gts_export_signatures', merchant_id:'<?=$_GET['merchant_id']?>', from_date:$('#from_date').val(), to_date:$('#to_date').val(), query:$('input[name=query]').val()})"><?=$strings['export_to_csv']?></button>
					<?
				}
				?>
			</div>
			<div class="search_container">
		       	<div class="huge_blue"><?=$strings['search_index']?></div>
	    		<form name="search_form" action="" method="GET">
	    			<?
	    			if ($type == 'merchant') {
    					?>
    					<span><?=$strings['choose_company']?></span>
    					<select name="company_id">
							<option value=""><?=$strings['all']?></option>
							<?
							if (!empty($companies_ids_hash)) {
								foreach ($companies_ids_hash as $company_id => $company) {
									?>
									<option value="<?=$company_id?>"<?=$_GET['company_id'] == $company_id ? ' selected' : false ?>><?=$company['name']?> - <?=$company['number']?></option>
									<?
								}
							}
							?>
						</select>
    					<?
    				} else if ($type == 'user' || $type == 'transaction') {
	    				?>
    					<span><?=$strings['choose_merchant']?></span>
    					<select name="merchant_id">
							<option value=""><?=$strings['all']?></option>
							<?
							if (!empty($merchants_ids_hash)) {
								foreach ($merchants_ids_hash as $merchant_id => $merchant) {
									?>
									<option value="<?=$merchant_id?>"<?=$_GET['merchant_id'] == $merchant_id ? ' selected' : false ?>><?=$merchant['name'].' - '.$merchant['number']?></option>
									<?
								}
							}
							?>
						</select>
    					<?
    				}
    				if ($type == 'transaction') {
	    				?>
	    				<span><?=$strings['dates']?></span>
	    				<input type="hidden" id="from_date" name="from_date" value="<?=$_GET['from_date']?>">
						<input type="text" id="from_date__visual" placeholder="<?=$strings['from_date']?>" value="<?=$_GET['from_date'] ? date('d/m/Y', strtotime($_GET['from_date'])) : false ?>" onkeyup="!$(this).val() ? $('#from_date').val('') : false "> 
						<input type="hidden" id="to_date" name="to_date" value="<?=$_GET['to_date']?>">
						<input type="text" id="to_date__visual" placeholder="<?=$strings['to_date']?>" value="<?=$_GET['to_date'] ? date('d/m/Y', strtotime($_GET['to_date'])) : false ?>" onkeyup="!$(this).val() ? $('#to_date').val('') : false "> 
						<?
    				}
	    			?>
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
			
			if (!empty($all_transactions) && !$_GET['no_graph']) {
				?>
				<script type="text/javascript">
					graph_data = [];
					graph_data[0] = ['תאריך', 'סכום באלפים', 'עסקאות'];
					<?
					foreach ($all_transactions as $transaction) {
						if ($transactions[$i]['payments_number'] < 2) {
	        				$amount = $transaction['amount'];
	        			} else {
		        			$amount = $transaction['payments_first_amount'] + ($transaction['payments_standing_amount'] * $transaction['payments_number']);
	        			}
						$graph_data[date('d/m/Y', $transaction['created'])]['amount'] = $graph_data[date('d/m/Y', $transaction['created'])]['amount'] + number_format($amount/1000, 2);
						$graph_data[date('d/m/Y', $transaction['created'])]['transactions'] = $graph_data[date('d/m/Y', $transaction['created'])]['transactions'] + 1;
					}
					$count = 1;
					foreach ($graph_data as $date => $array) {
						?>
						graph_data[<?=$count?>] = ['<?=$date?>', <?=$array['amount']?>, <?=$array['transactions']?>];
						<?
						$count++;
					}
					?>
					google.load('visualization', '1', {'packages':['corechart'], 'language':'he'});
					google.setOnLoadCallback(drawChart);
					function drawChart() {
			        	var data = google.visualization.arrayToDataTable(graph_data);
			        	var options = {
				        animation:{duration:1000},
				        width:910, height:150,
				        chartArea:{top:0, left:0, width:'100%', height:'100%'},
				        vAxis: {textPosition: 'in'},
				        //hAxis: {textPosition: 'in', format:'d/m/y', slantedText:true},
			        };
			        
			        var chart = new google.visualization.AreaChart(document.getElementById('graph'));
			        $(document).ready(function() {
				        setTimeout(function() {
					        chart.draw(data, options);
				        }, 1000);
			        });
			      }
			    </script>
			    <div id="graph"></div>
				<?
			}
			
			if (!empty($companies) || !empty($merchants) || !empty($users) || !empty($transactions)) {
				?>
				<div class="navigation filters">
					<div class="text"><?=$strings['showing_results']?>: <b><?=$navigation['start']+1?> - <?=$navigation['end']+1?></b> <?=$strings['from']?> <?=$navigation['total']?></div>
					<div class="filter_container">
						<?=$strings['results_per_page']?>: 
						<select name="limit" class="short" onchange="window.location=href_url+doc+'&limit='+this.value">
							<?
							if ($type == 'transaction') {
								?>
								<option value="20" <?=$limit == 20 ? 'selected' : ''?>>20</option>
								<option value="50" <?=$limit == 50 ? 'selected' : ''?>>50</option>
								<option value="100" <?=$limit == 100 ? 'selected' : ''?>>100</option>
								<option value="500" <?=$limit == 500 ? 'selected' : ''?>>500</option>
								<?	
							} else {
								?>
								<option value="25" <?=$limit == 25 ? 'selected' : ''?>>25</option>		
								<option value="50" <?=$limit == 50 ? 'selected' : ''?>>50</option>		
								<?
							}
							?>
						</select>
					</div>
				</div>
				<?
			}
			if (!empty($companies)) {
				?>
	    		<div class="ticket row th">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d company_name"><?=$strings['company_name']?></div>
	    			<div class="ticket_d company_number"><?=$strings['company_number']?></div>
	    			<div class="ticket_d mailer_name"><?=$strings['mailer_name']?></div>
	    			<div class="ticket_d mailer_email"><?=$strings['mailer_email']?></div>
	    			<div class="ticket_d created"><?=$strings['created']?></div>
	    			<div class="ticket_d note"></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($companies); $i++) {
	        		?>
	        		<div id="company_<?=$companies[$i]['id']?>" class="ticket row" onclick="toggle_company_form(this)">
	        			<div class="ticket_d id"><?=$companies[$i]['id']?></div>
	        			<div class="ticket_d company_name"><?=$companies[$i]['name']?></div>
	        			<div class="ticket_d company_number"><?=$companies[$i]['number']?></div>
	        			<div class="ticket_d mailer_name"><?=$companies[$i]['mailer_name']?></div>
	        			<div class="ticket_d mailer_email"><a href="mailto:<?=htmlspecialchars($companies[$i]['mailer_email'])?>"><?=$companies[$i]['mailer_email']?></a></div>
	        			<div class="ticket_d created"><?=date('d/m/Y', $companies[$i]['created'])?></div>
	        			<div class="ticket_d note" title="<?=htmlspecialchars($companies[$i]['params']['note'])?>"><?=$companies[$i]['params']['note'] ? '[ ! ]' : false ?></div>
	        		</div>
	        		<?
	        	}
	        } else if (!empty($merchants)) {
				?>
	    		<div class="ticket row th merchant">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d merchant_name"><?=$strings['merchant_name']?></div>
	    			<div class="ticket_d merchant_number"><?=$strings['merchant_number']?></div>
	    			<div class="ticket_d company_name"><?=$strings['company_name']?></div>
	    			<div class="ticket_d pos_number"><?=$strings['pos_number']?></div>
	    			<div class="ticket_d report_inactivity"><?=$strings['report_inactivity']?></div>
	    			<div class="ticket_d created"><?=$strings['created']?></div>
	    			<div class="ticket_d modified"><?=$strings['modified']?></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($merchants); $i++) {
	        		?>
	        		<div id="merchant_<?=$merchants[$i]['id']?>" class="ticket row merchant" onclick="toggle_merchant_form(this)">
	        			<div class="ticket_d id"><?=$merchants[$i]['id']?></div>
	        			<div class="ticket_d merchant_name"><?=$merchants[$i]['name']?></div>
	        			<div class="ticket_d merchant_number"><?=$merchants[$i]['number']?></div>
	        			<div class="ticket_d company_name" title="<?=$merchants[$i]['company_id']?>"><?=$companies_ids_hash[$merchants[$i]['company_id']]['name']?></div>
	        			<div class="ticket_d pos_number"><?=$merchants[$i]['pos_number']?></div>
	        			<div class="ticket_d report_inactivity"><?=$merchants[$i]['report_inactivity'] == 1 ? '+' : '-' ?></div>
	        			<div class="ticket_d created"><?=date('d/m/Y', $merchants[$i]['created'])?></div>
	        			<div class="ticket_d modified"><?=$merchants[$i]['modified'] ? date('d/m/Y', $merchants[$i]['modified']) : '-' ?></div>
	        		</div>
	        		<?
	        	}
	        } else if (!empty($users)) {
				?>
	    		<div class="ticket row th">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d username"><?=$strings['username']?></div>
	    			<div class="ticket_d name"><?=$strings['name']?></div>
	    			<div class="ticket_d status"><?=$strings['status']?></div>
	    			<div class="ticket_d created"><?=$strings['created']?></div>
	    			<div class="ticket_d note"></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($users); $i++) {
	        		?>
	        		<div id="user_<?=$users[$i]['id']?>" class="ticket row" onclick="toggle_user_form(this)">
	        			<?
	        			if (!empty($users[$i]['merchant_ids'])) {
		        			foreach ($users[$i]['merchant_ids'] as $merchant_id) {
			        			?>
			        			<input type="hidden" class="merchant_id" value="<?=$merchant_id?>">
			        			<?
		        			}
	        			}
	        			?>
	        			<div class="ticket_d id"><?=$users[$i]['id']?></div>
	        			<div class="ticket_d username"><?=$users[$i]['username']?></div>
	        			<div class="ticket_d name"><?=$users[$i]['name']?></div>
	        			<div class="ticket_d status"><?=$users[$i]['blocked'] ? $strings['status__'.$users[$i]['blocked'].'_blocked'] : $strings['status__active'] ?></div>
	        			<div class="ticket_d created"><?=date('d/m/Y', $users[$i]['created'])?></div>
	        			<div class="ticket_d note" title="<?=htmlspecialchars($users[$i]['params']['note'])?>"><?=$users[$i]['params']['note'] ? '[ ! ]' : false ?></div>
	        		</div>
	        		<?
	        	}
	        
	        } else if (!empty($transactions)) {
		        ?>
	    		<div class="ticket row th signature">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d amount"><?=$strings['amount']?></div>
	    			<div class="ticket_d cc_last_4"><?=$strings['cc_last_4']?></div>
	    			<div class="ticket_d cc_exp"><?=$strings['cc_exp']?></div>
	    			<div class="ticket_d cc_issuer"><?=$strings['cc_issuer']?></div>
	    			<div class="ticket_d credit_terms"><?=$strings['credit_terms']?></div>
	    			<div class="ticket_d reference_number"><?=$strings['reference_number']?></div>
	    			<div class="ticket_d merchant_name"><?=$strings['merchant_name']?></div>
	    			<div class="ticket_d timestamp"><?=$strings['created']?></div>
	    			<div class="ticket_d note"></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($transactions); $i++) {
	        		?>
	        		<div id="transaction_<?=$transactions[$i]['id']?>" class="ticket row signature" onclick="toggle_signature_form(this)">
	        			<input type="hidden" class="signature_link" value="<?=$transactions[$i]['signature_link']?>">
	        			
	        			<div class="ticket_d id"><?=$transactions[$i]['id']?></div>
	        			<div class="ticket_d amount" title="<?=htmlspecialchars($transactions[$i]['payments_number'].' '.$strings['payments'])?>">
	        				<?
	        				if ($transactions[$i]['payments_number'] > 1 && $transaction[$i]['payments_first_amount'] && $transaction[$i]['payments_standing_amount']) {
		        				echo $transactions[$i]['payments_first_amount'].' '.$transactions[$i]['currency'].' + '.$transactions[$i]['payments_standing_amount'].' x '.$transactions[$i]['payments_number'];
		        			} else {
		        				echo $transactions[$i]['amount'].' '.$transactions[$i]['currency'];
		        				if ($transactions[$i]['payments_number'] > 1) {
		        					echo ' ('.$transactions[$i]['payments_number'].')';
		        				}
		        			}
	        				?>
	        			</div>
	        			<div class="ticket_d cc_last_4"><?=$transactions[$i]['cc_last_4']?></div>
		    			<div class="ticket_d cc_exp"><?=substr($transactions[$i]['cc_exp'], 0, 2).'/'.substr($transactions[$i]['cc_exp'], 2, 2)?></div>
		    			<div class="ticket_d cc_issuer"><?=ucwords($transactions[$i]['cc_issuer'])?></div>
		    			<div class="ticket_d credit_terms">
		    				<?
	        				if ($transactions[$i]['cc_issuer'] == 'isracard' && $transactions[$i]['credit_terms'] == 'isracredit-amexcredit') {
	        					echo ucwords($strings['credit_terms__isracard__'.$transactions[$i]['credit_terms']]);
	        				} else if ($transactions[$i]['cc_issuer'] == 'visa-cal' && $transactions[$i]['credit_terms'] == 'isracredit-amexcredit') {
	        					echo ucwords($strings['credit_terms__visacal__'.$transactions[$i]['credit_terms']]);
	        				} else if ($transactions[$i]['cc_issuer'] == 'leumi-card' && $transactions[$i]['credit_terms'] == 'isracredit-amexcredit') {
	        					echo ucwords($strings['credit_terms__leumicard__'.$transactions[$i]['credit_terms']]);
	        				} else {
	        					echo ucwords($strings['credit_terms__'.$transactions[$i]['credit_terms']]);
	        				}
	        				?>
		    			</div>
		    			<div class="ticket_d reference_number"><?=$transactions[$i]['reference_number']?></div>
	        			<div class="ticket_d merchant_name" title="<?=ucwords($merchants_ids_hash[$transactions[$i]['merchant_id']]['name']).' - '.$merchants_ids_hash[$transactions[$i]['merchant_id']]['number']?>"><?=ucwords($merchants_ids_hash[$transactions[$i]['merchant_id']]['name'])?></div>
	        			<div class="ticket_d timestamp"><?=date('d/m/Y H:i', $transactions[$i]['created'])?></div>
	        			<div class="ticket_d note" title="<?=htmlspecialchars($transactions[$i]['params']['note'])?>"><?=$transactions[$i]['params']['note'] ? '[ ! ]' : false ?></div>
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