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
		$search__doc_type = array('element', 'gts_user');
		$constraints = false;
		$query = false;
		if ($_GET['query']) {
			$query[] = array(array('element_id', 'gts_user:username', 'gts_user:company', 'gts_user:mobile'), $_GET['query'], 'quote');	
		}
		$expression = 'and';
		$sort = false;
		$sort[] = array('element_id', 'DESC', 1);
		$users = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);
		*/
		
		$request = array(
			'user_id' => null,
			'created_min' => strtotime($_GET['from_created_date']),
			'created_max' => $_GET['to_created_date'] ? strtotime($_GET['to_created_date'])+(3600*24) : null,
			'query' => $_GET['query'],
			'offset' => (($_GET['page']-1) * $limit),
			'limit' => $limit,
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
		/*
		if ($user['id'] == 501) {
			echo '<pre>Response:'; print_r($response); echo '</pre>';
			exit;
		}
		*/
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$users = $response['data']['items'];
			} else {
				// GTS access token expired
				Header('Location:'.href($doc.'?action=logout'));
				exit;
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
		
		$navigation = navigation_handler(($users[0] ? count($users) : 0), $limit, $_GET['page']);
		
		/* END OF GTS LOGICS */
		
		echo $group_object->description;
		
		?>
		<div class="tickets">
			<div class="actions">
				<button class="add" style="float:right" onclick="toggle_vf_user_form()">+ <?=$strings['add_user']?></button>
				<button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button>
				<button class="refresh" onclick="location.reload();"><span>&#8634;</span> <?=$strings['refresh_list']?></button>
			</div>
			<div class="search_container">
		       	<div class="huge_blue"><?=$strings['search_index']?></div>
	    		<form name="search_form" action="" method="GET">
	    			<span><?=$strings['free_search']?></span>
	            	<input type="text" name="query" value="<?=htmlspecialchars($_GET['query'])?>">

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
			if (!empty($users)) {
				?>
				<div class="navigation filters">
					<div class="text"><?=$strings['showing_results']?>: <b><?=$navigation['start']+1?> - <?=$navigation['end']+1?></b> <?=$strings['from']?> <?=$navigation['total']?></div>
					<div class="filter_container">
						<?=$strings['results_per_page']?>: 
						<select name="limit" class="short" onchange="window.location=href_url+doc+'&limit='+this.value+'&query='+$('input[name=query]').val()">
							<option value="50" <?=$limit == 50 ? 'selected' : ''?>>50</option>		
							<option value="100" <?=$limit == 100 ? 'selected' : ''?>>100</option>		
							<option value="500" <?=$limit == 500 ? 'selected' : ''?>>500</option>
						</select>
					</div>
				</div>
				<div class="ticket row th merchant user">
	    			<div class="ticket_d id"><?=$strings['id']?></div>
	    			<div class="ticket_d username"><?=$strings['username']?></div>
	    			<div class="ticket_d type"><?=$strings['type']?></div>
	    			<div class="ticket_d name"><?=$strings['name']?></div>
	    			<div class="ticket_d email"><?=$strings['email']?></div>
	    			<div class="ticket_d company"><?=$strings['company']?></div>
	    			<div class="ticket_d mobile"><?=$strings['mobile']?></div>
	    			<div class="ticket_d created"><?=$strings['created']?></div>
	    			<div class="ticket_d note"></div>
	    		</div>
	        	<?
	        	for ($i = 0; $i < $limit && $i != count($users); $i++) {
	        		?>
	        		<div id="vf_user_<?=$users[$i]['id']?>" class="ticket row merchant user" onclick="toggle_vf_user_form(this)">
	        			<div class="ticket_d id"><?=$users[$i]['id']?></div>
	        			<div class="ticket_d username"><?=$users[$i]['username']?></div>
	        			<div class="ticket_d type"><?=$users[$i]['type']?></div>
	        			<div class="ticket_d name"><?=$users[$i]['name']?></div>
	        			<div class="ticket_d email"><?=$users[$i]['email']?></div>
	        			<div class="ticket_d company" title="<?=$users[$i]['company_id']?>"><?=$users[$i]['company']?></div>
	        			<div class="ticket_d mobile"><?=$users[$i]['mobile']?></div>
	        			<div class="ticket_d created" title="<?=date('d/m/Y H:i', $users[$i]['created'])?>"><?=date('d/m/Y', $users[$i]['created'])?></div>
	        			<div class="ticket_d note" title="<?=htmlspecialchars($users[$i]['note'])?>"><?=$users[$i]['note'] ? '[ ! ]' : false ?></div>
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