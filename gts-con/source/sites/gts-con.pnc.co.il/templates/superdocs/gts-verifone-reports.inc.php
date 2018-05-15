<?php
//test
	recursive('trim', $_GET);
	//recursive('stripslashes', $_GET);
	recursive('strip_tags', $_GET);
	
	$lang_code = 'he';

	if ($user['id'] && $user['authenticated']) {
		$user_object = new User($user['id']);
		unsetVar('gts_admin');
		if ($user_object->memberzones && in_array('165310', $user_object->memberzones)) {
			$user['type'] = 'gts_admin';
			setVar('gts_admin', true);
		} else if ($user_object->memberzones && in_array('165311', $user_object->memberzones)) {
			$user['type'] = 'gts_verifone_merchants_without_delete';
		} else if ($user['type'] == 'content-admin' || $user['type'] == 'tech-admin') {
			setVar('gts_admin', true);
		}

		//if ($user['type'] == 'content-admin' || $user['type'] == 'tech-admin' || ($_SERVER['REMOTE_ADDR'] == '192.116.39.130' && ($user['type'] == 'gts_admin' || $user['type'] == 'gts_verifone_merchants_without_delete'))) {
		if ($user['type'] == 'content-admin' || $user['type'] == 'tech-admin' || $user['type'] == 'gts_admin' || $user['type'] == 'gts_verifone_merchants_without_delete') {
			$gts_credentials = 'verifone-manager:'.aes_decrypt(trim(strip_tags(getObject('GTS Verifone Manager', true))));
			$gts_admin = true;
			setVar('gts_credentials', $gts_credentials);
			?>
			<script type="text/javascript">
				current_user = <?=json_encode($user)?>;
				<?
				if ($_GET['merchant_id']) {
					?>
					current_user.merchant_id = '<?=$_GET['merchant_id']?>';
					<?
				}
				?>
			</script>
			<?
		}
	} else {
		unsetVar('gts_credentials');
	}
	
	if (!getVar('gts_credentials')) {
		
		// If no access token exists, we show the login screen
		//include 'templates/superdocs/gts-login.inc.php';
		Header('Location:'.href('login?referrer='.rawurlencode($doc)));
		exit;
	
	} else if ($_GET['action'] == 'logout') {
	
		unsetVar('gts_credentials');
		unsetVar('gts_admin');
		
		Header('Location:'.href('logout?referrer='.rawurlencode($doc)));
		exit;
	
	} else if ($gts_credentials = getVar('gts_credentials')) {
		
		if ($_GET['type'] && $_GET['merchant_id']) {
            // When viewing a specific merchant - we are viewing the actual 
			if ($_GET['type'] == 'transactions' && $_GET['merchant_id']) {
    			include 'templates/superdocs/gts-console-transactions.inc.php';
    		} else if ($_GET['type'] == 'errors' && $_GET['merchant_id']) {
    			include 'templates/superdocs/gts-console-errors.inc.php';
    		}
        } else {
            $limit = $_GET['limit'] ?: 50;
    		
    		$request = array(
    			'merchant_id' => null,
    			'created_min' => strtotime($_GET['from_created_date']),
    			'created_max' => $_GET['to_created_date'] ? strtotime($_GET['to_created_date'])+(3600*24) : null,
    			'stats_time_min' => strtotime($_GET['from_stats_date']),
    			'stats_time_max' => $_GET['to_stats_date'] ? strtotime($_GET['to_stats_date'])+(3600*24) : null,
    			'query' => $_GET['query'],
    			'offset' => $_GET['page'] >= 1 ? (($_GET['page']-1) * $limit) : 0,
    			'limit' => $limit,
    			'orderby' => 'created',
    			'sorting' => 'desc',
    		);
    		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
    			'http' => array(
    				'method' => 'POST',
    				'header' => 'Content-type: application/json' . "\r\n",
    				'content' => json_encode(strip_nulls($request))
    			)
    		)));
    		/*
    		$response = gts_curl('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', array(
    			'merchant_id' => null,
    			'created_min' => strtotime($_GET['from_created_date']),
    			'created_max' => $_GET['to_created_date'] ? strtotime($_GET['to_created_date'])+(3600*24) : null,
    			'stats_time_min' => strtotime($_GET['from_stats_date']),
    			'stats_time_max' => $_GET['to_stats_date'] ? strtotime($_GET['to_stats_date'])+(3600*24) : null,
    			'query' => $_GET['query'],
    			'offset' => (($_GET['page']-1) * $limit),
    			'limit' => $limit,
    			'orderby' => 'created',
    			'sorting' => 'desc',
    		));
    		*/
    		//echo '<pre>'; print_r($user); echo '</pre>';
            
            // if ($user['id'] == '508') {
    		// 	echo '<pre>Credentials:'; echo($gts_credentials); echo '</pre>';
    		// 	echo '<pre>Request:'; print_r($request); echo '</pre>';
    		// 	echo '<pre>Response:'; print_r(json_decode($response)); echo '</pre>';
    		// 	exit;
    		// }
    		
    		if ($response = json_decode($response, true)) {
    			if ($response['result'] == 'OKAY') {
    				$all_merchants_tmp = $response['data']['items'];
    				if (!empty($all_merchants_tmp)) {
    					// TBD - Currently it is not possible to search over params, so if merchant_type filter is used - we must manually fitler the results.
    					// This is currently causing a problem with the page numbering...
    					if ($_GET['merchant_type']) {
    						foreach ($all_merchants_tmp as $merchant) {
    							if ($merchant['params']['merchant_type'] == $_GET['merchant_type']) {
    								$all_merchants[] = $merchant;
    							}
    						}
    					} else {
    						$all_merchants = $all_merchants_tmp;
    					}
    					/*
    					if (!empty($all_merchants)) {
    						$merchants = array_chunk($all_merchants, $limit);
    						$merchants = $merchants[$_GET['page'] ? $_GET['page']-1 : '0'];
    					}
    					*/
    					$merchants = $all_merchants;
    				}
    				//echo '<pre>'; print_r($merchants); echo '</pre>';
    				//exit;
    			} else {
    				// GTS access token expired
    				Header('Location:'.href($doc.'?action=logout'));
    				exit;
    			}
    		}
    		
    		if ($_GET['debug']) {
    			echo '<pre>'; print_r($merchants); echo '</pre>';
    			exit;
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
    		
    		$navigation = navigation_handler($response['data']['total'], $limit, $_GET['page']);
    		
    		/* END OF GTS LOGICS */
    		
    		echo $group_object->description;
    		
    		?>
    		<div class="tickets">
        
    			<div class="search_container">
    		       	<div class="huge_blue"><?=$strings['search_index']?></div>
    	    		<form id="report"name="search_form" action="" method="GET">
    	    			<select name="report_type">
    						<option value="transactions_report"<?=$_GET['report_type'] == 'transactions_report' ? ' selected="selected"' : '' ?>><?=$strings['transactions_report']?></option>
    						<option value="errors_report"<?=$_GET['report_type'] == 'errors_report' ? ' selected="selected"' : '' ?>><?=$strings['errors_report']?></option>
    					</select>
        				
    	    			<span><?=$strings['free_search']?></span>
    	            	<input type="text" name="query" value="<?=htmlspecialchars($_GET['query'])?>">

    	            	<span><?=$strings['dates']?></span>
    	            	<? /*
    	            	<select name="date_filter_type">
    						<option value="created"<?=$_GET['date_filter_type'] == 'created' ? ' selected="selected"' : false ?>><?=$strings['filter_by_created_date']?></option>
    						<option value="stats"<?=$_GET['date_filter_type'] == 'stats' ? ' selected="selected"' : false ?>><?=$strings['filter_by_stats_date']?></option>
    					</select>
    					*/ ?>
    					<input type="hidden" id="from_created_date" name="from_created_date" value="<?=$_GET['from_created_date']?>">
    					<input type="text" id="from_created_date__visual" class="date" placeholder="<?=$strings['from_created_date']?>" value="" onkeyup="!$(this).val() ? $('#from_created_date').val('') : false "> 
    					<input type="hidden" id="to_created_date" name="to_created_date" value="<?=$_GET['to_created_date']?>">
    					<input type="text" id="to_created_date__visual" class="date" placeholder="<?=$strings['to_created_date']?>" value="" onkeyup="!$(this).val() ? $('#to_created_date').val('') : false "> 

    					<input type="hidden" id="from_stats_date" name="from_stats_date" value="<?=$_GET['from_stats_date']?>">
    					<input type="text" id="from_stats_date__visual" class="date" placeholder="<?=$strings['from_stats_date']?>" value="" onkeyup="!$(this).val() ? $('#from_stats_date').val('') : false "> 
    					<input type="hidden" id="to_stats_date" name="to_stats_date" value="<?=$_GET['to_stats_date']?>">
    					<input type="text" id="to_stats_date__visual" class="date" placeholder="<?=$strings['to_stats_date']?>" value="" onkeyup="!$(this).val() ? $('#to_stats_date').val('') : false "> 

    	    			<button><?=$strings['search']?></button>
    	                <?
    	                // Clear filter button
    	                $search_filters = array_filter(array($_GET['query'], $_GET['from_created_date'], $_GET['to_created_date'], $_GET['from_stats_date'], $_GET['to_stats_date']));
    	                if (!empty($search_filters)) {
    	                	?>
    	                    <button onclick="window.location = '<?=href($doc)?>';return false;"><?=$strings['clear_search']?></button>
    	                    <?
    		            }
    	                ?>
    	    		</form>
    	    	</div>
    			<?
    			if (!empty($merchants)) {
    				/*
					?>
    				<script type="text/javascript">
    					graph_data = [];
    					graph_data[0] = ['תאריך', 'מסופים'];
    					<?
    					$merchants_reverse = array_reverse($merchants);
    					foreach ($merchants_reverse as $merchant) {
    						$graph_data[date('d/m/Y', $merchant['created'])] = $graph_data[date('d/m/Y', $merchant['created'])] + 1;
    					}
    					$count = 1;
    					foreach ($graph_data as $date => $value) {
    						?>
    						graph_data[<?=$count?>] = ['<?=$date?>', <?=$value?>];
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
					*/ ?>

    				<div class="navigation filters">
    					<div class="text"><?=$strings['showing_results']?>: <b><?=$navigation['start']+1?> - <?=$navigation['end']+1?></b> <?=$strings['from']?> <?=$navigation['total']?></div>
    					<div class="filter_container">
    						<?=$strings['results_per_page']?>: 
    						<? /* <select name="limit" class="short" onchange="window.location=href_url+doc+'&limit='+this.value+'&query='+$('input[name=query]').val()+'&from_date='+$('input[name=from_date]').val()+'&to_date='+$('input[name=to_date]').val()+'&date_filter_type='+$('select[name=date_filter_type]').val()"> */ ?>
    						<select name="limit" class="short" onchange="window.location=href_url+doc+'&limit='+this.value+'&query='+$('input[name=query]').val()+'&from_created_date='+$('input[name=from_created_date]').val()+'&to_created_date='+$('input[name=to_created_date]').val()+'&from_stats_date='+$('input[name=from_stats_date]').val()+'&to_stats_date='+$('input[name=to_stats_date]').val()">
    							<option value="50" <?=$limit == 50 ? 'selected' : ''?>>50</option>		
    							<option value="100" <?=$limit == 100 ? 'selected' : ''?>>100</option>		
    							<option value="500" <?=$limit == 500 ? 'selected' : ''?>>500</option>
    						</select>
    					</div>
    				</div>
    				<div class="ticket row th merchant">
    	    			<div class="ticket_d id"><?=$strings['id']?></div>
    	    			<div class="ticket_d username"><?=$strings['username']?></div>
    	    			<div class="ticket_d merchant_number"><?=$strings['merchant_number']?></div>
    	    			<div class="ticket_d merchant_type"><?=$strings['merchant_type']?></div>
    	    			<div class="ticket_d merchant_status"><?=$strings['merchant_status']?></div>
    	    			<div class="ticket_d company_name"><?=$strings['company_name']?></div>
    	    			<? /* <div class="ticket_d company_email"><?=$strings['company_email']?></div> */ ?>
    	    			<div class="ticket_d card_readers"><?=$strings['card_readers']?></div>
    	    			<div class="ticket_d cc_count"><?=$strings['cc_count']?></div>
    	    			<div class="ticket_d checks_count"><?=$strings['checks_count']?></div>
    	    			<div class="ticket_d cash_count"><?=$strings['cash_count']?></div>
    	    			<div class="ticket_d enable_invoices" title="<?=htmlspecialchars($strings['enable_invoices'])?>"><?=$strings['enable_invoices__short']?></div>
    	    			<div class="ticket_d mobile"><?=$strings['mobile']?></div>
    	    			<div class="ticket_d bounce" title="<?=htmlspecialchars($strings['bounce__short'])?>"><?=$strings['bounce']?></div>
                        <div class="ticket_d recurring_order" title="<?=htmlspecialchars($strings['support_recurring_orders'])?>"><?= $strings['recurring_order_abbreviation'] ?></div>
    	    			<div class="ticket_d created"><?=$strings['created']?></div>
    	    			<div class="ticket_d note"></div>
    	    		</div>
    	        	<?
    	        	//for ($i = $navigation['start']; $i <= $navigation['end']; $i++) {
    	        	for ($i = 0; $i < $limit && $i != count($merchants); $i++) {
    	        		
    	        		if ($merchants[$i]['company']['email'] || $merchants[$i]['sender']['email']) {
    		        		// Checking for "bounce"
    		        		if ($merchants[$i]['company']['email'] && $merchants[$i]['sender']['email']) {
    		        			$where = "email='".mysql_real_escape_string($merchants[$i]['sender']['email'])."' OR email='".mysql_real_escape_string($merchants[$i]['company']['email'])."'";
    		        		} else {
    		        			$where = "email = '".mysql_real_escape_string($merchants[$i]['sender']['email'] ?: $merchants[$i]['company']['email'])."'";
    		        		}
    		        		$sql_query = mysql_query("SELECT cnt FROM bounce.log WHERE ".$where);
    					    $sql = mysql_fetch_assoc($sql_query);
    					    if ($sql) {
    					   		$merchants[$i]['bounce'] = true;
    					    }
    	        		}
    	        		
    	        		?>
    	        		<div id="vf_merchant_<?=$merchants[$i]['id']?>" class="ticket row merchant" onclick="get_error_or_merchants_reports(<?=$merchants[$i]['id']?>)">
    	        			<input type="hidden" class="mailer_name" value="<?=htmlspecialchars($merchants[$i]['sender']['name'])?>">
    	        			<input type="hidden" class="mailer_email" value="<?=$merchants[$i]['sender']['email']?>">
    	        			<input type="hidden" class="access_token_ttl" value="<?=$merchants[$i]['access_token_ttl']?>">
    	        			<input type="hidden" class="voucher_language" value="<?=$merchants[$i]['voucher_language']?>">
    	        			<input type="hidden" class="allow_duplicated_transactions" value="<?=$merchants[$i]['allow_duplicated_transactions']?>">
    	        			<input type="hidden" class="support_recurring_orders" value="<?=$merchants[$i]['support_recurring_orders']?>">
    	        			
    	        			<input type="hidden" class="support_refund_trans" value="<?=$merchants[$i]['support_refund_trans']?>">
    	        			<input type="hidden" class="support_cancel_trans" value="<?=$merchants[$i]['support_cancel_trans']?>">
    	        			<input type="hidden" class="support_new_trans" value="<?=$merchants[$i]['support_new_trans']?>">

    	        			<input type="hidden" class="shva_transactions_username" value="<?=$merchants[$i]['shva_transactions_username']?>">
    	        			
    	        			<input type="hidden" class="send_daily_report" value="<?=$merchants[$i]['send_daily_report']?>">
    	        			
    	        			<input type="hidden" class="charge_starting_number" value="<?=$merchants[$i]['invoices']['charge_starting_number']?>">
    	        			<input type="hidden" class="refund_starting_number" value="<?=$merchants[$i]['invoices']['refund_starting_number']?>">
    	        			<input type="hidden" class="invoice_template" value="<?=$merchants[$i]['invoices']['template']?>">
    	        			<input type="hidden" class="purchased_card_readers" value="<?=$merchants[$i]['params']['purchased_card_readers']?>">
    	        			
    	        			<input type="hidden" class="company_email" value="<?=$merchants[$i]['company']['email']?>">
    	        			<input type="hidden" class="company_type" value="<?=$merchants[$i]['params']['company_type']?>">
    	        			<input type="hidden" class="company_address_street" value="<?=htmlspecialchars($merchants[$i]['params']['company']['address']['street'])?>">
    	        			<input type="hidden" class="company_address_number" value="<?=$merchants[$i]['params']['company']['address']['number']?>">
    	        			<input type="hidden" class="company_address_city" value="<?=htmlspecialchars($merchants[$i]['params']['company']['address']['city'])?>">
    	        			<input type="hidden" class="company_address_zip" value="<?=$merchants[$i]['params']['company']['address']['zip']?>">
    	        			<input type="hidden" class="company_phone" value="<?=$merchants[$i]['params']['company']['phone']?>">
    	        			<input type="hidden" class="company_logo" value="<?=$merchants[$i]['params']['company']['logo']?>">

    	        			<input type="hidden" class="business_category" value="<?=$merchants[$i]['params']['business']['category']?>">
    	        			<input type="hidden" class="business_address" value="<?=$merchants[$i]['params']['business']['address']?>">
    	        			<input type="hidden" class="business_contact_name" value="<?=$merchants[$i]['params']['business']['contact_name']?>">
    	        			
    	        			<div class="ticket_d id"><?=$merchants[$i]['id']?></div>
    	        			<div class="ticket_d username"><?=$merchants[$i]['username']?></div>
    	        			<div class="ticket_d merchant_number"><?=$merchants[$i]['number']?></div>
    	        			<div class="ticket_d merchant_type"><?=$merchants[$i]['params']['merchant_type']?></div>
    	        			<div class="ticket_d merchant_status<?=$merchants[$i]['params']['merchant_status'] == 'disabled' ? ' red' : false ?>"><?=$strings['status__'.($merchants[$i]['params']['merchant_status'] ?: 'active')]?></div>
    	        			<div class="ticket_d company_name" title="<?=htmlspecialchars($merchants[$i]['company']['number'])?>"><?=htmlspecialchars($merchants[$i]['company']['name'])?></div>
    	        			<div class="ticket_d card_readers"><?=$merchants[$i]['params']['purchased_card_readers'] != 0 ? ($merchants[$i]['stats']['card_readers'] ? count($merchants[$i]['stats']['card_readers']) : '0').'/'.$merchants[$i]['params']['purchased_card_readers'] : ($merchants[$i]['stats']['card_readers'] ? count($merchants[$i]['stats']['card_readers']) : '0') ?></div>
    	        			<div class="ticket_d cc_count"><?=number_format($merchants[$i]['stats']['trans_credit_num'])?></div>
    	        			<div class="ticket_d checks_count"><?=number_format($merchants[$i]['stats']['trans_check_num'])?></div>
    	        			<div class="ticket_d cash_count"><?=number_format($merchants[$i]['stats']['trans_cash_num'])?></div>
    	        			<div class="ticket_d enable_invoices"><?=$merchants[$i]['invoices']['template'] ? '✓' : '<span class="red">×</span>' ?></div>
    	        			<div class="ticket_d mobile" title="<?=htmlspecialchars($merchants[$i]['mobile'])?>"><?=!$merchants[$i]['mobile'] ? '<span class="red">×</span>' : '✓' ?></div>
    	        			<div class="ticket_d bounce" title="<?=htmlspecialchars($merchants[$i]['company']['email'] ?: $merchants[$i]['sender']['email'] ?: false)?>"><?=$merchants[$i]['bounce'] ? '<span class="red">×</span>' : '✓' ?></div>
                            <div class="ticket_d recurring_order" title="<?=htmlspecialchars($strings['support_recurring_orders'])?>"><?=!$merchants[$i]['support_recurring_orders'] ? '<span class="red">×</span>' : '✓' ?></div>
    	        			<div class="ticket_d created" title="<?=date('d/m/Y H:i', $merchants[$i]['created'])?>"><?=date('d/m/Y', $merchants[$i]['created'])?></div>
    	        			<div class="ticket_d note" title="<?=htmlspecialchars($merchants[$i]['params']['note'])?>"><?=$merchants[$i]['params']['note'] ? '[ ! ]' : false ?></div>
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
		
    }
    
?>