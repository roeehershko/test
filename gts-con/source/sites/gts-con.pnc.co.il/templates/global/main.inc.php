<?php

if (strpos($doc, 'en/') !== false || $doc_id == constant('HOME_DOC_ID')) {
	$language = 'english';
	$language_prefix = 'English - ';
	$lang_prefix = 'en_';
}

if ($user['id']) {
	$user_object = new User($user['id']);
}

include 'include/strings/'.$lang_prefix.'strings.inc.php';

if ($_GET['action'] == 'fb-invite') {
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
	<html>
		<head>
			<link type="text/css" rel="stylesheet" href="<?=href('include/css/global.css')?>">
		</head>
		<body>
			<div id="fb-root"></div>
			<script type="text/javascript">
				window.fbAsyncInit = function() {
					FB.init({appId: '<?=constant('FACEBOOK_APP_ID')?>', status: true, cookie: true, xfbml: true});
				};
				(function() {
					var e = document.createElement('script'); e.async = true;
					e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
					document.getElementById('fb-root').appendChild(e);
				}());
			</script>
			<fb:serverFbml>
			<script type="text/fbml">
				<fb:fbml>
					<fb:request-form
						action='https://secure.pnc.co.il/pnc.co.il/login/?action=close-window'
						method='POST'
						type='Join this App'
						content='Would you like to join this App? <fb:req-choice url="http://pnc.co.il/?action=fb-invite-confirmation" label="Yes" />'
						<fb:multi-friend-selector showborder="false" actiontext="Invite your friends to join this App"/>
					</fb:request-form>
				</fb:fbml>
			</script>
			</fb:serverFbml>
		</body>
	</html>
	<?
	exit;
} elseif ($_GET['action'] == 'close-window') {
	?>
	<script type="text/javascript">
		window.close();
	</script>
	<?
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
    <head>
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="cache-control" content="0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="robots" content="index,follow">
        <meta name="verify-v1" content="crk2zhRijPkc7tIFZrZSCOYqcL7q7Lid7zbz1W6dGKc=">
		<meta name="google-site-verification" content="aaWRR56cuJUszBV6uQ5Ud3Vr_9CYcE-3D2j6p6dZ1jg">

		<meta name="apple-mobile-web-app-capable" content="yes">
		<?
		if (strpos($doc, 'gts/') === false && strpos($doc, 'support') === false && strpos($doc, 'tasks') === false && $doc != 'order-form') {
			?>
			<meta name="viewport" content="width=1000" />
			<?
		} else if ($doc == 'order-form') {
			?>
			<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
			<?
		} else {
			?>
			<meta name="viewport" content="width=device-width" />
			<?
		}
		?>

        <title><?=$seo['title'] ? $seo['title'] : ($title ? $title : $site_title) ?></title>
        <meta name="keywords" content="<?=$seo['keywords']?>">
        <meta name="description" content="<?=$seo['description']?>">

        <link rel="shortcut icon" type="image/x-icon" href="<?=href('images/global/favicon.ico')?>">
        <link rel="icon" type="image/png" href="<?=href('images/global/favicon.png')?>">

        <link type="text/css" rel="stylesheet" href="<?=href('include/css/htmlarea.css')?>">
        <link type="text/css" rel="stylesheet" href="<?=href('include/css/global.css?'.filectime('include/css/global.css'))?>">
        <?
        if ($language == 'english') {
        	?>
        	<link type="text/css" rel="stylesheet" href="<?=href('include/css/'.$lang_prefix.'global.css?'.filectime('include/css/'.$lang_prefix.'global.css'))?>">
        	<?
        }
        ?>

        <? /*
		<link type="text/css" rel="stylesheet" href="https://'.constant('GTS_APP_HOST').'/widgets/gts_transaction_submit.css">
		<link type="text/css" rel="stylesheet" href="https://'.constant('GTS_APP_HOST').'/widgets/he_gts_transaction_submit.css">
		<script type="text/javascript" src="<?=href('repository/include/javascript/ieupdate/swfobject.js')?>"></script>
		<script type='text/javascript'>
			function loadScript(src) {
				var script = document.createElement("script");
				script.type = "text/javascript";
				document.getElementsByTagName("head")[0].appendChild(script);
				script.src = src;
			}
			loadScript("<?=href('repository/include/javascript/visible.js')?>");
			//loadScript("<?=href('repository/include/javascript/ieupdate/swfobject.js')?>");
			loadScript("<?=href('repository/include/javascript/showcase.js')?>");
			loadScript("<?=href('repository/include/javascript/adjust_height.js')?>");
		</script>
		*/ ?>

		<? /* JSON */ ?>
	    <script type="text/javascript" src="<?=href('/repository/include/javascript/json2.min.js')?>"></script>
	   	<? /* jQuery Latest 1.x.x version - http://ajax.googleapis.com/ajax/libs/jqueryui/1/MANIFEST */ ?>
		<? /*
		<link type="text/css" href="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jqueryui/1/themes/base/jquery.ui.all.css'?>" rel="stylesheet">
		<link type="text/css" href="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jqueryui/1/themes/redmond/jquery-ui.css'?>" rel="stylesheet">
		<link type="text/css" href="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jqueryui/1/themes/base/jquery.ui.autocomplete.css'?>" rel="stylesheet">
		<script type="text/javascript" src="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'?>"></script>
		<script type="text/javascript" src="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js'?>"></script>
		<script type="text/javascript" src="<?=($secure ? 'https://' : 'http://').'ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-he.min.js'?>"></script>
		*/ ?>
		<link type="text/css" href="<?=href('repository/include/css/jquery.ui.all.css')?>" rel="stylesheet">
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.min.js')?>"></script>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery-ui.min.js')?>"></script>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.ui.datepicker-he.min.js')?>"></script>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.ui.touch-punch.min.js')?>"></script>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.wipetouch.js')?>"></script>
		<?
		if (constant('MOBILE') || $_GET['iscroll']) {
			?>
			<script type="text/javascript" src="<?=href('repository/include/javascript/iscroll.js')?>"></script>
			<?
		}

		?>
		<? /* jQuery Plugin - Form */ ?>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.form.min.js')?>"></script>
		<? /* jQuery Plugin - Autocomplete */ ?>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.autocomplete.js')?>"></script>
		<? /* jQuery Plugin - ScrollTo */ ?>
		<script type="text/javascript" src="<?=href('repository/include/javascript/jquery.scrollTo-1.4.2-min.js')?>"></script>

	    <script type="text/javascript" src="<?=href('repository/include/javascript/jquery.jcarousel.min.js')?>"></script>

	    <? /* Google Charts */ ?>
	    <script type="text/javascript" src="https://www.google.com/jsapi"></script>

	    <script type="text/javascript" src="<?=href('include/javascript/global.js?version='.filectime('include/javascript/global.js'))?>"></script>
		<script type="text/javascript">
			var href_url = '<?=href('/')?>';
			var user_id = '<?=$user['id']?>';
			var doc_id = '<?=$doc_id?>';
			var doc = '<?=addslashes($doc)?>';
			var home_doc_id = '<?=addslashes($home_doc_id)?>';
			strings = <?=json_encode($strings)?>;
		</script>

        <?php

        	/*
        	// Conditional Styling

        	if ($_GET['debug']) {
	        	echo '<pre>'; print_r($groups['category']['groups']); echo '</pre>';
	        	exit;
        	}

            // Equal menubar cells width
            //if ($user['type'] == 'content-admin' || $user['type'] == 'tech-admin') {
			//	$filter_function       = 'return($g[\'level\'] == \'0\' && ($g[\'status\'] == \'normal\' || $g[\'status\'] == \'unavailable\'));';
			if (strpos($doc, 'gts') !== false) {
				$search__doc_type = 'group';
				$constraints = false;
				$constraints['groups'] = array('165124');
				$query = false;
				$query[] = array(array('status'), 'normal');
				$expression = 'and';
				$sort =false;
				$sort[] = array('title','ASC');
				$menubar_categories = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);
			} else {
				$filter_function       = 'return($g[\'level\'] == \'0\' && $g[\'status\'] == \'normal\');';
				$menubar_categories    = array_values(array_filter($groups['category']['groups'], create_function('$g', $filter_function)));
			}
			$menubar_width         = 903; // 960px - left_corner(8px) - gyro_logo(49px)
            $menubar_button_width  = ($menubar_width/count($menubar_categories)) / $menubar_width * 100;
             // Used in menubar.inc.php
            $last_menubar_category = $menubar_categories[count($menubar_categories)-1]['id'];
            */
		?>
		
			<script type="text/javascript" id="User1st_Loader">
        var _u1stSettings = {/*Add the settings if relevant*/};        
        var isActive = ((/u1stIsActive=1/).test(document.cookie));
        var script = "<script type='text/javascript'  id='User1st_Loader' src='https://fecdn.user1st.info/Loader/head' ";
        (!isActive) && (script += "async='true'");
        script += "><\/script>";
        document.write(script);
			</script>

	   <style type="text/css">
			body {background:#FFF}
			<? /* #menubar .categories td {width:<?=$menubar_button_width?>%} */ ?>
			<?
			if ($doc_id == $home_doc_id) {
				?>
				#frame #header #submenubar {margin:15px 200px 0 0;height:20px;direction:rtl}
				<?
			} else {
				?>
				#frame #header #submenubar {margin:15px 60px 0 0;height:20px;direction:rtl}
				<?
			}

			if ($doc_parameters['hide_menus']) {
				?>
				#frame #header #menubar .paragon_logo {cursor:default}
				#frame #header {height:135px}
				#frame #header #menubar .menubar_container {display:none}
				#frame #header #submenubar {display:none}
				.footer_logo_container {display:none}
				<?
			}
			?>
		</style>
		
		</head>
    <body>
    	<?
    	if ($_SERVER['HTTP_HOST'] == 'fb.pnc.co.il' || $_GET['fb']) {

			?>
			<style type="text/css">
				#facebook {padding:40px;direction:ltr;text-align:left}
				#facebook * {direction:ltr;text-align:left}
				body {zoom:0.7;-moz-transform:scale(0.75)}
				#fb_menubar {height:25px;margin-bottom:20px;padding:0 10px 0 0;<? /*background:#EDEFF4;*/ ?>border-bottom:1px solid #D8DFEA;direction:rtl}
				#fb_menubar a {display:block;float:right;position:relative;top:1px;z-index:1;width:100px;height:22px;line-height:22px;margin:3px 0 0 3px;text-align:center;font-size:14px;color:3B5998;font-variant:small-caps;text-decoration:none;background:#D8DFEA}
				#fb_menubar a:hover {color:#FFF;background:#627AAD}
				#fb_menubar a.selected {top:-0;color:#000;background:#FFF;border:1px solid #D8DFEA;border-bottom:1px solid #FFF}
			</style>
			<? /*
			<div id="fb_menubar">
				<a href="<?=href('/')?>" class="selected">עמוד הבית</a>
				<a href="<?=href('search/?sms=1')?>">מודעות SMS</a>
				<a href="<?=$http_url.('my-account/post-edit')?>" target="_blank">פרסם מודעה</a>
				<a href="<?=$http_url.('my-account')?>" target="_blank">החשבון שלי</a>
				<a href="<?=$http_url.('credits-purchase')?>" target="_blank">רכוש קרדיט</a>
				<a href="<?=href('158843')?>">אודות האתר</a>
				<a href="<?=href('153583')?>">תקנון האתר</a>
			</div>
			*/ ?>

			<div id="facebook">
				<?
				if ($fb_access_token = getVar('FB_access_token')) {
					echo '<pre>Facebook Access Token: '; echo $fb_access_token; echo '</pre>';
					if ($result = json_decode(file_get_contents('https://graph.facebook.com/me/?access_token='.$fb_access_token))) {
						echo '<pre>'; print_r($result); echo '</pre>';
					}
					if ($result->verified) {
						?>
						<div><a href="#" onclick="window.open('http://www.pnc.co.il/?action=fb-invite&fb=1', 'Facebook', 'width=768,height=628,resizable=yes,location=no,toolbar=no,menubar=no,status=no')">Click here to invite your friends</a></div>
						<?
					} else {
						?>
						<div>User has logged out of Facebook or access token expired.</div>
						<?
					}
				} else {
					$referrer = 'http://pnc.co.il/?fb=1';
					?>
					<div><a href="https://graph.facebook.com/oauth/authorize?client_id=<?=constant('FACEBOOK_APP_ID')?>&scope=email&redirect_uri=<?=rawurlencode($https_url.'login/?authority=facebook'.($referrer ? '&referrer='.urlencode($referrer) : false))?>"><img src="<?=href('images/global/facebook_connect_long.gif')?>" alt="Facebook Connect"></a></div>
					<?
				}
				?>
			</div>
			<?
		}
		?>
		<img class="background" src="<?=href('images/global/background_faded.jpg')?>">
		<div align="center" class="frame">
			<div class="new">
				<?
				$search__doc_type = 'group';
				$constraints = false;
				$query = false;
				if (strpos($doc, 'erp/') !== false) {
					$constraints['groups'] = array('168537');
				} else if (strpos($doc, 'gts/signatures') !== false) {
					$constraints['groups'] = array('165126');
				} else if (strpos($doc, 'gts/verifone') !== false) {
					$constraints['groups'] = array('165288');
				} else if (strpos($doc, 'gts/console/isracard') !== false) {
					$constraints['groups'] = array('169236');
				} else {
					$constraints['groups'] = array($language == 'english' ? '162317' : '161893');
					$query[] = array(array('status'), 'normal');
				}
				$expression = 'and';
				$sort = false;
				$sort[] = array('idx', 'ASC', 1);

				if (($user['type'] == 'tech-admin' || $user_object->projects) && (strpos($doc, 'support') !== false || strpos($doc, 'tasks') !== false)) {
					if ($lang_prefix == 'en_') {
						$categories = array('162318', '170909', '168609');
					} else {
						$categories = array('161894', '170904', '168535');
					}
				} else {
					$categories = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);
				}

				if ($doc != 'order-form') {
					?>
					<style type="text/css">
						@media all and (min-width:600px) {
							.new .toolbar_container .box.toolbar a {width:<?=100 / count($categories)?>%}
						}
					</style>
					<div class="toolbar_container">
						<div class="box toolbar">
							<div class="container">
								<?
								if (!empty($categories)) {
									foreach ($categories as $category_id) {
										$category_object = new Group($category_id);
										if ($category_object->status == 'normal' || getVar('gts_admin')) {
											if ($category_id == $doc_id || $category_object->alt_link == $doc) {
												echo '<a href="'.href($category_object->alt_link ?: $category_object->doc).'" class="selected">'.($category_object->button_title ?: $category_object->title).'</a>';
											} else {
												if($lang_prefix == 'en_'){
													echo '<a href="'.href($category_object->alt_link ?: $category_object->doc).'" onmouseover="$(this).addClass(\'selected\')" onmouseout="$(this).removeClass(\'selected\')">'.($category_object->button_title ?: $category_object->title).'</a>';
												}
												else{
													echo '<a href="'.href($category_object->alt_link ?: $category_object->doc).'" onmouseover="$(this).addClass(\'selected\')" onmouseout="$(this).removeClass(\'selected\')">'.($category_object->button_title ?: $category_object->title).'</a>';
												}
											}
										}
									}
								}
								?>
			            	</div>
						</div>
					</div>
					<?
				}
				?>

				<div class="content">
					<?=$content?>
				</div>

				<?
				// Wizard
				include 'templates/global/wizard.inc.php';
				?>

			</div>
		</div>
		<?
        /* Facebook SDK */
        if ($doc != 'my-account' && strpos($doc, 'gts/') === false && strpos($doc, 'erp/') === false && strpos($doc, 'gts/signatures') === false && strpos($doc, 'gts/merchants') === false && constant('FACEBOOK_APP_ID') && constant('FACEBOOK_SECRET')) {
			?>
			<div id="fb-root"></div>
			<script type="text/javascript">
				$(document).ready(function() {
					window.fbAsyncInit = function() {
						FB.init({appId: '<?=constant('FACEBOOK_APP_ID')?>', status: true, cookie: true, xfbml: true});
					};
					(function() {
						var e = document.createElement('script'); e.async = true;
						e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
						document.getElementById('fb-root').appendChild(e);
					}());
				});
			</script>
			<?
		}

		/* Google Analytics */
		if (strpos($doc, 'erp/') === false && strpos($doc, 'gts/signatures') === false && strpos($doc, 'gts/merchants') === false) {
			?>
	        <script type="text/javascript">
	            var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	            document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	        </script>
	        <script type="text/javascript">
	            try {
	                var pageTracker = _gat._getTracker("UA-3807466-2");
	                pageTracker._trackPageview();
	            } catch(err) {}
	        </script>
	        <?
	    }

    	if ($_GET['action'] == 'gts-pop-over') {
			?>
			<link type="text/css" rel="stylesheet" href="https://<?=constant('GTS_APP_HOST')?>/widgets/gts_transaction_submit.css">
			<link type="text/css" rel="stylesheet" href="https://<?=constant('GTS_APP_HOST')?>/widgets/he_gts_transaction_submit.css">
			<script type="text/javascript" src="https://<?=constant('GTS_APP_HOST')?>/widgets/gts_transaction_submit.js"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					setTimeout(function() {
						gts('he', 1, 'oa@pnc.co.il');
					}, 3000);
				});
			</script>
			<?
		}
		?>
    </body>
</html>
