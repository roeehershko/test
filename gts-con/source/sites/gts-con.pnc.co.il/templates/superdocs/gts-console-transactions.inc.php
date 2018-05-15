<?php

	include 'templates/superdocs/gts-console-init.inc.php';
	$gts_credentials = getVar('gts_credentials');
	
	// 2017-01-22 - Note that we allow a user to work EVEN if a merchant underneath him has an expired password or a password that needs to be changed.
	if ($gts_credentials && !$user_details['must_change_password'] && ($gts_admin || $user_details['id'] || !$merchant_details['must_change_password']) && !$_GET['reset_password'] && !$_GET['token']) {
		
		// In order to prevent server load - we pre-define the from-to dates
		if (!$_GET['from_date'] && !$_GET['to_date']) {
			// 2015-09-31 - Decided to show the last 50 transactions, instead of 'by date'.
			//$default_from_date = date('Ymd', time()-(3600*24*7));
			//$default_to_date = date('Ymd');
		}
		
		if ($type == 'recurring_order') {
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserRecurringOrderDetails', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls(array(
						'ro_id' => $_GET['recurring_order'],
						'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
					)))
				)
			)));
		} else {
			$limit = $_GET['limit'] ?: 50;
			
			if ($_GET['query'] == 'מזומן') {
				$query = 'cash';
			} else if ($_GET['query'] == 'צ׳ק' || $_GET['query'] == 'צק' || $_GET['query'] == 'ציק') {
				$query = 'check';
			} else if ($_GET['query'] == 'אשראי') {
				$query = 'credit';
			} else {
				$query = $_GET['query'];
			}
			
			$request = array(
				'admin' => $gts_admin,
				'merchant_id' => $_GET['merchant_id'],
				'date_start' => (!$query && ($_GET['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_GET['from_date'] ?: $default_from_date)) : false,
				'date_end' => (!$query && ($_GET['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_GET['to_date'] ? $_GET['to_date'] : $default_to_date) + (3600*23)) : false,
				'search' => $query,
				'offset' => (($_GET['page'] > 0 ? ($_GET['page']-1) : 0) * $limit),
				'limit' => $limit,
				'orderby' => 'timestamp',
				'sorting' => 'desc',
				'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
			);
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactions', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		}
		
		if ($_GET['t']) {
			echo '<pre>GTS Credentials: '; echo($gts_credentials); echo '</pre>';
			echo '<pre>GTS Request: '; print_r($request); echo '</pre>';
			echo '<pre>GTS Response: '; print_r($response); echo '</pre>';
			//echo '<pre>GTS Response: '; print_r(json_decode($response, true)); echo '</pre>';
			exit;	
		}
		
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$transactions = $response['data']['transactions'];
				if (!empty($transactions)) {
					
					if ($type == 'recurring_order') {
						$transactions = array_reverse($transactions);
						$limit = count($transactions);
						$total = $limit;
					} else {
						$total = $response['data']['total'];
					}
					
					for ($i = 0; $i < count($transactions); $i++) {
						if ($transactions[$i]['data']) {
							$transactions[$i] = $transactions[$i]['data'];
						} else {
							// Getting the transactions details
							$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactionDetails', false, stream_context_create(array(
								'http' => array (
									'method' => 'POST',
									'header' => 'Content-type: application/json' . "\r\n",
									'content' => json_encode(strip_nulls(array(
										'trans_id' => $transactions[$i],
										'include' => array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params')
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
				<?
				if ($type == 'recurring_order') {
					?>
					<button class="back" style="float:right" onclick="window.location='<?=href('gts/console/isracard/recurring-orders')?>'"><?=$strings['back']?></button>
					<?
				} else if (strpos($doc, 'gts/verifone/reports') !== false) {
					?>
					<button class="back" style="float:right" onclick="window.history.back()"><?=$strings['back']?></button>
					<?
				} else if ($merchant_details['support_new_trans'] && (count($user_details['merchants']) <= 1 || (count($user_details['merchants']) > 1 && $_GET['merchant_id']))) {
					?>
					<button class="add" style="float:right" onclick="toggle_transaction_form()">+ <?=$strings['add_transaction']?></button>
					<?	
				}
				?>
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
				<button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
                <button class="refresh hidden" onclick="window.location='<?=href($doc)?>'"><?=$strings['back_button']?></button>
				<?
				if (!$gts_admin) {
					?>
					<button class="change_password" onclick="toggle_change_merchant_password_form()"><?=$strings['change_password']?></button>
					<?
				}
				?>
				<script>
					function export_transactions() {
						var answer = confirm(strings.confirm_export_transactions);
						if (answer) {
							var export_transactions_url = '<?=href($doc.'?action=gts_export_transactions'.($gts_admin ? '&admin=1' : '').'&merchant_id='.rawurlencode($_GET['merchant_id']).'&recurring_order='.rawurlencode($_GET['recurring_order']).'&from_date='.rawurlencode($_GET['from_date']).'&to_date='.rawurlencode($_GET['to_date']).'&query='.rawurlencode($_GET['query']))?>';
							window.open(export_transactions_url);
						}
					}
					function export_invoices() {
						var recipients = prompt(strings.prompt_export_invoices);
						if (recipients) {
							var export_invoices_url = '<?=href($doc)?>';
							var parameters = {action:'gts_export_invoices', recurring_order:'<?=rawurlencode($_GET['recurring_order'])?>', from_date:'<?=rawurlencode($_GET['from_date'])?>', to_date:'<?=rawurlencode($_GET['to_date'])?>', query:'<?=rawurlencode($_GET['query'])?>', recipients:recipients};
							$.post(export_invoices_url, parameters);
							alert(strings.confirmation__in_progress__may_continue);
						} else {
							alert(strings.error__no_recipients_found);
						}
					}
				</script>
				<button class="export" onclick="export_transactions()"><?=$strings['export_transactions']?></button>
				<?
				if ($merchant_details['invoice_support']) {
					?>
					<button class="export" onclick="export_invoices()"><?=$strings['export_invoices']?></button>
					<?
				}
				?>
			</div>
			<?
			if ($type == 'transaction') {
				?>
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
							<select name="merchant_id" class="long" onchange="$('form[name=search_form]').submit()">
								<option value=""><?=$strings['all']?></option>
								<?
								foreach ($user_details['merchants'] as $merchant) {
									// Building the $merchants array for easy usage later
									$merchants[$merchant['id']] = $merchant;
									?>
									<option value="<?=$merchant['id']?>"<?=$_GET['merchant_id'] == $merchant['id'] ? ' selected' : false ?>><?=$merchant['username'].' - '.$merchant['company']['name'].' - '.$merchant['company']['number']?></option>
									<?
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
			}
			
			if (!empty($transactions)) {
				//$all_transactions = array_reverse($all_transactions);
				//debug(json_encode($transactions));
				?>
				<script type="text/javascript">
					// https://developers.google.com/chart/interactive/docs/gallery/columnchart
					google.load('visualization', '1', {'packages':['corechart'], 'language':'he'});
					google.setOnLoadCallback(drawChart);
					
					// Transactions Amounts Graph
					graph__transactions_amounts = [];
					graph__transactions_amounts[0] = ['תאריך', 'סכום'];
					<?
					foreach ($transactions as $transaction) {
						if ($transaction['credit_data']['payments_number'] < 2) {
	        				$amount = $transaction['amount'];
	        			} else {
		        			//$amount = $transaction['credit_data']['payments_first_amount'] + ($transaction['credit_data']['payments_standing_amount'] * ($transaction['credit_data']['payments_number']-1));
		        			$amount = $transaction['credit_data']['payments_standing_amount'];
	        			}
						$graph__transactions_amounts[date('d/m/y', strtotime($transaction['timestamp']))]['amount'] += $amount;
					}
					$count = 1;
					foreach ($graph__transactions_amounts as $date => $array) {
						?>
						graph__transactions_amounts[<?=$count?>] = ['<?=$date?>', <?=$array['amount']?>];
						<?
						$count++;
					}
					?>
					
			        // Transactions Counts Graph
					graph__transactions_counts = [];
					graph__transactions_counts[0] = ['תאריך', 'עסקאות'];
					<?
					foreach ($transactions as $transaction) {
						$graph__transactions_counts[date('d/m/y', strtotime($transaction['timestamp']))]['transactions'] += 1;
					}
					$count = 1;
					foreach ($graph__transactions_counts as $date => $array) {
						?>
						graph__transactions_counts[<?=$count?>] = ['<?=$date?>', <?=$array['transactions']?>];
						<?
						$count++;
					}
					?>
					
					// Transactions Types Graph
					graph__transactions_types = [];
					graph__transactions_types[0] = ['סוג עסקה', 'כמות'];
					<?
					foreach ($transactions as $transaction) {
						$graph__transactions_types[$transaction['type']]['transactions'] += 1;
					}
					$count = 1;
					foreach ($graph__transactions_types as $type => $array) {
						?>
						graph__transactions_types[<?=$count?>] = ['<?=$strings['transaction_type_'.$type]?>', <?=$array['transactions']?>];
						<?
						$count++;
					}
					?>
					
			        function drawChart() {
			        	var data__amounts = google.visualization.arrayToDataTable(graph__transactions_amounts);
			        	var options__amounts = {
					        title:'סכומי עסקאות',
					        width:325, height:130,
					        //chartArea:{top:0, left:0, width:'100%', height:'100%'},
					        //vAxis: {textPosition: 'in'},
					        hAxis: {format:'d/m/y', slantedText:true},
					        legend:{position:'bottom'},
					        //tooltip:{textStyle:{fontSize:0.1}},
					        isStacked:true
				        }
				        var chart__graph__transactions_amounts = new google.visualization.ColumnChart(document.getElementById('graph__transactions_amounts'));
				        chart__graph__transactions_amounts.draw(data__amounts, options__amounts);
				        
				        var data__counts = google.visualization.arrayToDataTable(graph__transactions_counts);
			        	var options__counts = {
					        title:'כמות עסקאות',
					        width:325, height:130,
					        //chartArea:{top:0, left:0, width:'100%', height:'100%'},
					        //vAxis: {textPosition: 'in'},
					        hAxis: {format:'d/m/y', slantedText:true},
					        legend:{position:'bottom'},
					        //tooltip:{textStyle:{fontSize:0.1}},
					        isStacked:true
					    }
					    var chart__graph__transactions_counts = new google.visualization.AreaChart(document.getElementById('graph__transactions_counts'));
					    chart__graph__transactions_counts.draw(data__counts, options__counts);
					    
					    var data__types = google.visualization.arrayToDataTable(graph__transactions_types);
			        	var options__types = {
					        title:'סוגי עסקאות',
					        width:180, height:130,
					        //chartArea:{top:0, left:0, width:'100%', height:'100%'},
					        //vAxis: {textPosition: 'in'},
					        //hAxis: {textPosition: 'in'},
					        legend:{position:'bottom'},
					        //tooltip:{textStyle:{fontSize:0.1}},
					        pieHole: 0.4,
					        is3D:true,
					        isStacked:true
					    }
					    var chart__graph__transactions_types = new google.visualization.PieChart(document.getElementById('graph__transactions_types'));
					    chart__graph__transactions_types.draw(data__types, options__types);
					    
			        };
			        
			    </script>
			    
			    <div class="graphs">
				    <div id="graph__transactions_amounts"></div>
				    <div id="graph__transactions_counts"></div>
				    <div id="graph__transactions_types"></div>
			    </div>
				<?
			}
			
			if (!empty($transactions)) {
				?>
				<div class="navigation filters">
					<div class="text"><?=$strings['showing_results']?>: <b><?=$navigation['start']+1?> - <?=$navigation['end']+1?></b> <?=$strings['from']?> <?=$navigation['total']?></div>
				</div>
				<div class="ticket row th transaction">
	    			<div class="ticket_d cancel"></div>
	    			<div class="ticket_d trans_status"></div>
	    			<div class="ticket_d id"><?=$strings['id']?></div>
					<div class="ticket_d amount"><?=$strings['amount']?></div>
	    			<div class="ticket_d trans_type"><?=$strings['credit_terms']?></div>
	    			<div class="ticket_d cc_type"><?=$strings['cc_type']?></div>
	    			<div class="ticket_d cc_last_4"><?=$strings['cc_last_4']?></div>
	    			<div class="ticket_d cc_exp"><?=$strings['cc_exp']?></div>
	    			<div class="ticket_d authorization_number"><?=$strings['authorization_number']?></div>
	    			<div class="ticket_d voucher_number"><?=$strings['voucher_number']?></div>
	    			<div class="ticket_d timestamp"><?=$strings['timestamp']?></div>
	    			<div class="ticket_d note"></div>
	    			<div class="ticket_d invoice_link"></div>
	    		</div>
	        	<?
	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
	        	for ($i = 0; $i < $limit && $i != count($transactions); $i++) {
	        		//debug(json_encode($transactions[$i]));
	        		
	        		// For a cancellation transaction - we automatically add a proper note
	        		if ($transactions[$i]['params']['refund_of_trans_id']) {
		        		if ($transactions[$i]['params']['note']) {
			        		$transactions[$i]['params']['note'] .= "\n";
		        		}
		        		$transactions[$i]['params']['note'] .= $strings['refund_of_trans_id'].' '.$transactions[$i]['params']['refund_of_trans_id'];
	        		}

	        		?>
	        		<div id="transaction_<?=$transactions[$i]['trans_id']?>" class="ticket row transaction" title="<?=$transactions[$i]['type'] == 'check' ? htmlspecialchars($strings['transaction_check_number'].': '.$transactions[$i]['check_data']['check_number']."\n".$strings['transaction_account_number'].': '.$transactions[$i]['check_data']['account_number']."\n".$strings['transaction_bank_number'].': '.$transactions[$i]['check_data']['bank_number']) : false ?>">
	        			<input type="hidden" class="signature_link" value="<?=$transactions[$i]['credit_data']['signature_link']?>">
	        			<input type="hidden" class="transaction_merchant_username" value="<?=$merchants[$transactions[$i]['merchant_id']]['username']?>">
						<input type="hidden" class="transaction_merchant_company_name" value="<?=$merchants[$transactions[$i]['merchant_id']]['company']['name']?>">
						
	        			<div class="ticket_d cancel">
	        				<?
	        				if ((($user_details['merchants'][0] && $_GET['merchant_id']) || !$user_details['merchants'][0]) && $merchant_details['support_cancel_trans'] > 0 && !$last_transaction_for_cancel && $transactions[$i]['status'] != 'canceled' && $transactions[$i]['amount'] > 0 && !$transactions[$i]['credit_data']['j5']) {
		        				if (date('Ymd', strtotime($transactions[$i]['timestamp'])) != date('Ymd') && $merchant_details['support_refund_trans']) {
			        				?>
									<a title="<?=htmlspecialchars($strings['refund_transaction'])?>" onclick="gts_cancel_transaction('<?=$transactions[$i]['trans_id']?>');stop_propagation()">&times;</a>
									<?
		        				} else if (date('Ymd', strtotime($transactions[$i]['timestamp'])) == date('Ymd')) {
			        				?>
									<a title="<?=htmlspecialchars($strings['cancel_transaction'])?>" onclick="gts_cancel_transaction('<?=$transactions[$i]['trans_id']?>');stop_propagation()">&times;</a>
									<?
		        				}
		        				if ($merchant_details['support_cancel_trans'] == 2) {
		        					$last_transaction_for_cancel = true;
		        				}	
	        				}
	        				?>
	        			</div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d trans_status">
	        				<?
        					$status_icon_url = href('images/gts/default/status').'-'.$transactions[$i]['type'];
	        				if ($transactions[$i]['status'] == 'completed') {
		        				$status_icon_url .= '-completed';
	        				} else if ($transactions[$i]['status'] == 'pending' && $transactions[$i]['credit_data']['j5']) {
		        				$status_icon_url .= '-test';
	        				} else if ($transactions[$i]['status'] == 'pending') {
		        				$status_icon_url .= '-pending';
	        				} else if ($transactions[$i]['status'] == 'canceled') {
		        				$status_icon_url .= '-canceled';
	        				}
	        				if ($transactions[$i]['amount'] < 0) {
		        				$status_icon_url .= '-refund';
	        				}
	        				$status_icon_url .= '@2x.png';
							?>
							<img src="<?=$status_icon_url?>">	
	        			</div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d id" title="<?=$merchants[$transactions[$i]['merchant_id']] ? $strings['username'].' '.$merchants[$transactions[$i]['merchant_id']]['username'].' | '.$merchants[$transactions[$i]['merchant_id']]['company']['name'].($merchants[$transactions[$i]['merchant_id']]['company']['number'] ? ' ('.$merchants[$transactions[$i]['merchant_id']]['company']['number'].')' : false) : false ?>"><?=$transactions[$i]['trans_id']?></div>
	        			<?
	        			$transactions[$i]['currency'] = $strings['currency_'.strtolower($transactions[$i]['currency'])];
        				$amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['amount'], 2);
        				if ($transactions[$i]['credit_data']['payments_number'] >= 2) {
	        				if ($transactions[$i]['credit_data']['credit_terms'] == 'payments-credit') {
		        				$detailed_amount = $transactions[$i]['currency'].' '.number_format(($transactions[$i]['amount'] / $transactions[$i]['credit_data']['payments_number']), 2).' x '.($transactions[$i]['credit_data']['payments_number']);
	        				} else if ($transactions[$i]['credit_data']['payments_first_amount'] == $transactions[$i]['credit_data']['payments_standing_amount']) {
		        				$detailed_amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['credit_data']['payments_standing_amount'], 2).' x '.($transactions[$i]['credit_data']['payments_number']);
	        				} else {
		        				$detailed_amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['credit_data']['payments_first_amount'], 2).' + '.number_format($transactions[$i]['credit_data']['payments_standing_amount'], 2).' x '.($transactions[$i]['credit_data']['payments_number']-1);	
	        				}
	        			} else {
		        			$detailed_amount = $transactions[$i]['currency'].' '.number_format($transactions[$i]['amount'], 2);
	        			}
	        			?>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d amount" title="<?=$detailed_amount?>"><?=$amount?></div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d trans_type">
	        				<?
	        				if ($transactions[$i]['status'] == 'canceled') {
		        				echo $strings['canceled'];
		        			} else if ($transactions[$i]['amount'] < 0) {
		        				echo $strings['refunded'];
	        				} else if ($transactions[$i]['credit_data']['j5']) {
	        					echo $strings['test'];
	        				} else if ($transactions[$i]['check_data']['check_number']) {
		        				echo $strings['transaction_type_'.$transactions[$i]['type']].' #'.$transactions[$i]['check_data']['check_number'];
	        				} else {
		        				echo ucwords($strings[$transactions[$i]['credit_data']['credit_terms'] ? 'credit_terms__'.$transactions[$i]['credit_data']['credit_terms'] : 'transaction_type_'.$transactions[$i]['type']]);
	        				}
	        				?>
	        			</div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d cc_type">
	        				<?
	        				if ($transactions[$i]['credit_data']['cc_type']) {
		        				echo ucwords($transactions[$i]['credit_data']['cc_type']);
	        				} else {
		        				echo '-';
	        				}
	        				?>
	        			</div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d cc_last_4"><?=$transactions[$i]['credit_data']['cc_last_4'] ?: '-' ?></div>
		    			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d cc_exp"><?=$transactions[$i]['credit_data']['cc_exp'] ? substr($transactions[$i]['credit_data']['cc_exp'], 0, 2).'/'.substr($transactions[$i]['credit_data']['cc_exp'], 2, 2) : '-' ?></div>
		    			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d authorization_number"><?=$transactions[$i]['credit_data']['authorization_number'] ?: '-' ?></div>
		    			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d voucher_number"><?=$transactions[$i]['credit_data']['voucher_number'] ?: '-' ?></div>
	        			<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d timestamp"><?=date('d/m/Y H:i', strtotime($transactions[$i]['timestamp']))?></div>
	        			<div onclick="gts_edit_note($(this).closest('.ticket.row.transaction'))" class="ticket_d note" title="<?=htmlspecialchars(strip_tags($transactions[$i]['params']['note']))?>"><?=$transactions[$i]['params']['note'] ? '[ ! ]' : ' + ' ?></div>
						<div onclick="toggle_voucher_form($(this).closest('.ticket.row.transaction'))" class="ticket_d invoice_link">
	        				<?
	        				if ($transactions[$i]['invoice_data']['link']) {
	        					?>
								<a href="<?=href($transactions[$i]['invoice_data']['link'])?>" target="_blank"><img src="<?=href('images/gts/default/invoice@2x.png')?>"></a>
								<?
	        				}
							?>	
	        			</div>
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