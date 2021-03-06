<?php

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
			setVar('gts_credentials', $gts_credentials);
			?>
			<script type="text/javascript">
				current_user = <?=json_encode($user)?>;
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
		
		$limit = $_GET['limit'] ?: 50;
		
		/*
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
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
				)))
			)
		)));
		*/
		
		/*
		if ($_GET['user']) {
			$user_object = new Element($_GET['user']);
			$merchants = json_decode($user_object->gts_user['merchants'], true);
			if (!empty($merchants)) {
				foreach ($merchants as $merchant_id => $value) {
					
				}
			}
		} else {
		*/
			$request = array(
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
			);
			//$response = gts_curl('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', $request);
			$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		//}
		//echo '<pre>Response:'; print_r($response); echo '</pre>';
		//exit;
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
		
		/* VF Users */
		
		/*
		$search__doc_type = array('element', 'gts_user');
		$constraints = false;
		$query = false;
		$expression = 'and';
		$sort = false;
		$sort[] = array('element_id', 'DESC', 1);
		$users = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);
		*/
		$request = array(
			'user_id' => null,
			'created_min' => null,
			'created_max' => null,
			'query' => null,
			'offset' => null,
			'limit' => null,
			'orderby' => 'created',
			'sorting' => 'desc',
		);
		//$response = gts_curl('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainUsers', $request);
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainUsers', false, stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		//echo '<pre>Response:'; print_r(json_decode($response, true)); echo '</pre>';
		//exit;
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$users = $response['data']['items'];
			}
		}
		//echo '<pre>Users:'; print_r($users); echo '</pre>';
		//exit;
		
		/* END OF GTS LOGICS */
		
		echo $group_object->description;
		
		?>
		<div class="tickets">
			<div class="actions">
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
				<button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
			</div>
			<div class="search_container">
		       	<div class="huge_blue"><?=$strings['search_index']?></div>
	    		<form name="search_form" action="" method="GET">
	    			<select name="merchant_type">
						<option value=""><?=$strings['merchant_type']?></option>
						<option value="<?=$strings['merchant_type__isracard']?>"<?=$_GET['merchant_type'] == $strings['merchant_type__isracard'] ? ' selected="selected"' : false ?>><?=$strings['merchant_type__isracard']?></option>
						<option value="<?=$strings['merchant_type__verifone']?>"<?=$_GET['merchant_type'] == $strings['merchant_type__verifone'] ? ' selected="selected"' : false ?>><?=$strings['merchant_type__verifone']?></option>
					</select>
    				
	    			<span><?=$strings['free_search']?></span>
	            	<input type="text" name="query" value="<?=htmlspecialchars($_GET['query'])?>">

	            	<span><?=$strings['dates']?></span>
	            	<input type="hidden" id="from_created_date" name="from_created_date" value="<?=$_GET['from_created_date']?>">
					<input type="text" id="from_created_date__visual" class="date" placeholder="<?=$strings['from_created_date']?>" value="<?=$_GET['from_created_date'] ? date('d/m/Y', strtotime($_GET['from_created_date'])) : false ?>" onkeyup="!$(this).val() ? $('#from_created_date').val('') : false "> 
					<input type="hidden" id="to_created_date" name="to_created_date" value="<?=$_GET['to_created_date']?>">
					<input type="text" id="to_created_date__visual" class="date" placeholder="<?=$strings['to_created_date']?>" value="<?=$_GET['to_created_date'] ? date('d/m/Y', strtotime($_GET['to_created_date'])) : false ?>" onkeyup="!$(this).val() ? $('#to_created_date').val('') : false "> 
					<? /*
	            	<select name="user">
						<option value=""><?=$strings['associated_user']?></option>
						<?
						if (!empty($users)) {
							foreach ($users as $user) {
								$element_object = new Element($user);
								?>
								<option value="<?=$element_object->doc_id?>"><?=$element_object->gts_user['name'].($element_object->gts_user['company'] ? ' ('.$element_object->gts_user['company'].')' : false)?></option>
								<?
							}
						}
						?>
					</select>
					*/ ?>
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
				?>
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
	    			<div class="ticket_d associated_user"><?=$strings['associated_user']?></div>
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
	        		<div id="vf_merchant_<?=$merchants[$i]['id']?>" class="ticket row merchant">
	        			<div class="ticket_d id"><?=$merchants[$i]['id']?></div>
	        			<div class="ticket_d username"><?=$merchants[$i]['username']?></div>
	        			<div class="ticket_d merchant_number"><?=$merchants[$i]['number']?></div>
	        			<div class="ticket_d merchant_type"><?=$merchants[$i]['params']['merchant_type']?></div>
	        			<div class="ticket_d merchant_status<?=$merchants[$i]['params']['merchant_status'] == 'disabled' ? ' red' : false ?>"><?=$strings['status__'.($merchants[$i]['params']['merchant_status'] ?: 'active')]?></div>
	        			<div class="ticket_d company_name" title="<?=htmlspecialchars($merchants[$i]['company']['number'])?>"><?=htmlspecialchars($merchants[$i]['company']['name'])?></div>
	        			<div class="ticket_d associated_user">
		        			<select onchange="gts_vf_merchant_associate_user($(this).val(), '<?=$merchants[$i]['id']?>')">
			        			<option value=""></option>
			        			<?
			        			if (!empty($users)) {
									foreach ($users as $user) {
										?>
										<option value="<?=$user['id']?>"<?=(!empty($user['merchants']) && in_array($merchants[$i]['id'], $user['merchants'])) ? ' selected' : false ?>><?=$user['name'].($user['company'] ? ' ('.$user['company'].')' : false)?></option>
										<?
									}
								}
								?>
		        			</select>
	        			</div>
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
?>