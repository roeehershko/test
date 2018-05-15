<?php
	
	// Make sure the sys-variables: NEWSLETTER_DEFAULT_RECIPIENT_NAME and NEWSLETTER_DEFAULT_RECIPIENT_EMAIL are NOT Null, if you wish people will be able
	// to view a newsletter as a web-page (without a Token).
	
	if (!$standalone && !$_GET['preview']) {
		?>
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
		<html>
			<head>
				<title><? if ($seo['title']): ?><?=$seo['title']?><? else: ?><? if ($title): ?><?=$title?><? else: ?><?=$site_title?><? endif; ?><? endif; ?></title>
				<meta http-equiv="pragma" content="no-cache">
				<meta http-equiv="cache-control" content="0">
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="robots" content="index,follow">
				<meta name="keywords" content="<?=$seo['keywords']?>">
				<meta name="description" content="<?=$seo['description']?>">
			</head>
			<body>
		<?	
	}
?>
<table cellpadding="0" cellspacing="0" style="width:640px;" align="center"><tr><td>
    <a href="http://pnc.co.il" target="_blank"><img src="http://pnc.co.il/images/global/logo.png" style="height:91px;width:208px;" height="91" width="208" border="0"></a>
<?
    echo str_replace(array('<p'),array('<p style="margin-top:5px"'),$content);
    
    if ($standalone || $_GET['preview']) :
?>
    <div style="text-align:center;"><a style="color:#6699CC;font-family:arial;font-size:11px;" href="<?=$direct_url?>">If you can't view this properly please click here</a><br></div>
    <div style="text-align:center;"><a style="color:#6699CC;font-family:arial;font-size:11px;" href="<?=$unsubscribe_url?>">If you wish to be removed from our list please click here</a></div>
<?
    endif;
?>
</td></tr></table>
<?
	if (!$standalone && !$_GET['preview']) {
		?>
			</body>
		</html>
		<?	
	}
?>