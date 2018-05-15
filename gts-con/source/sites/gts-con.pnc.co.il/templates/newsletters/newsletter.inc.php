<?php

if ($parameters['clean_newsletter']) :
    include('templates/newsletters/newsletter_clean.inc.php');
    return;
endif;

	// Make sure the sys-variables: NEWSLETTER_DEFAULT_RECIPIENT_NAME and NEWSLETTER_DEFAULT_RECIPIENT_EMAIL are NOT Null, if you wish people will be able
	// to view a newsletter as a web-page (without a Token).
	if (!$standalone) {
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
			<body bgcolor="#EFEFEF">
				<center>
					<div style="width:960px">
					<?	
	}
	echo generate_email_template($title, $recipient['name'], $content, $standalone, $direct_url, $unsubscribe_url, $_GET['link'], $parameters);
	if (!$standalone) {
					?>
				<div>
			</center>
		</body>
	</html>
	<?	
	}
?>

<?php
	/*
	if (!$standalone) {
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
				<link type="text/css" rel="stylesheet" href="<?=href('include/css/global.css')?>">
			</head>
		<?	
	}
	?>
	<body bgcolor="#EFEFEF" style="margin:0;padding:0">
		<div align="center" style="margin:0;padding:20px 0 0 0;direction:<?=$parameters['direction'] ?: 'rtl' ?>;color:#555;font-family:arial;font-size:14px;background:#EFEFEF">
			<table dir="rtl" width="600" align="center" cellspacing="0" cellpadding="0">
				<tr height="140">
					<td colspan="5" height="140"><a href="<?=$http_url?>" target="_blank"><img src="<?=$http_url.'images/global/newsletter__header.png'?>" width="600" height="140" border="0" align="center" alt=""></a></td>
				</tr>
				<tr>
					<td width="20" valign="top" bgcolor="#EFEFEF"><img src="<?=$http_url.'images/global/newsletter__right.png'?>" width="20" height="460" border="0" alt=""></td>
					<td width="20" bgcolor="#FFFFFF"></td>
					<td width="520" valign="top" bgcolor="#FFFFFF">
						<div align="right" style="text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>">
							<div style="padding:10px 0 20px 0">
								<h1 dir="<?=$parameters['direction'] ?: 'rtl' ?>" align="right" style="display:block;padding:0 0 10px 0;margin:0;color:#3087CE;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;font-weight:normal;font-size:24px;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=$title?></h1>
								<? /*<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" align="right" style="font-weight:bold;font-size:18px">שלום <?=$recipient['name']?>,</div>*//* ?>
								<?
								if ($content) {
									$content = str_replace(array('<p', '</p>'), array('<div', '</div>'), $content);
									?>
									<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="padding:0 0 20px 0;margin:0 0 17px 0;border-bottom:1px solid #EFEFEF;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;font-size:14px;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=$content?></div>
									<?
								}
								if (!empty($parameters['sections'])) {
									foreach ($parameters['sections'] as $section) {
										?>
										<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="padding:0 0 10px 0;margin:0 0 17px 0;border-bottom:1px solid #EFEFEF;font-size:14px;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>">
											<table width="520" dir="<?=$parameters['direction'] ?: 'rtl' ?>" cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top" width="220">
														<? if ($section['link_url']) { ?>
															<a href="<?=$section['link_url']?>" target="_blank"><img src="<?=$http_url.$section['image']?>" width="220" border="0" align="right" alt=""></a>
														<? } else { ?>
															<img src="<?=$http_url.$section['image']?>" width="220" border="0" align="right" alt="">
														<? } ?>
													</td>
													<td><div style="width:10px"></div></td>
													<td valign="top" width="300">
														<h2 dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="display:block;margin:0;padding:0 0 5px 0;color:#3087CE;font-size:18px;font-weight:bold;font-weight:normal;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=$section['title']?></h2>
														<h3 dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="display:block;margin:0;padding:0 0 5px 0;color:#000;font-size:16px;font-weight:normal;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=$section['subtitle']?></h3>
														<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="color:#555;font-size:14px;padding:0 0 10px 0;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=nl2br($section['description'])?></div>
														<?
														if ($section['link_url']) { 
															?>
															<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="color:#3087CE;font-size:14px;padding:0 0 10px 0;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><a href="<?=$section['link_url']?>" target="_blank" style="color:#3087CE;font-size:14px;padding:0;text-align:<?=$parameters['direction'] == 'ltr' ? 'left' : 'right' ?>;direction:<?=$parameters['direction'] ?: 'rtl' ?>"><?=$section['link_title'] ? $section['link_title'] : $section['link_url'] ?></a> &rsaquo;</div>
															<?
														}
														?>
													</td>
												</tr>
											</table>
										</div>
										<?
									}
								}
								?>
								
								<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="padding:0 0 10px 0;margin:0 0 10px 0;text-align:center;direction:<?=$parameters['direction'] ?: 'rtl' ?>">
									<span dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="color:#555;font-size:11px;text-align:center;direction:<?=$parameters['direction'] ?: 'rtl' ?>">פאראגון בע"מ, המגשימים 20 פתח-תקווה 49348, טלפון: 1-599-59-8070</span> 
									<a href="<?=$http_url?>" target="_blank" style="color:#3087CE;font-size:11px;text-align:center;direction:<?=$parameters['direction'] ?: 'rtl' ?>">pnc.co.il</a>
								</div>
								
							</div>
							
							<div style="padding:20px">
								<? if ($standalone) { ?>
									<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="font-family:arial;color:#555;font-size:11px;text-align:center;padding-bottom:10px"><a style="font-family:arial;color:#555;font-size:11px;text-decoration:underline" href="<?=$direct_url?>">אם אינכם יכולים לראות את אימייל זה כראוי - לחצו כאן</a></div>
								<? } ?>
								<? if ($_GET['link'] || $standalone) { ?>
									<div dir="<?=$parameters['direction'] ?: 'rtl' ?>" style="font-family:arial;color:#555;font-size:11px;text-align:center;padding-bottom:10px"><a style="font-family:arial;color:#555;font-size:11px;text-decoration:underline" href="<?=$unsubscribe_url?>">אם ברצונכם להיות מוסרים מרשימת התפוצה - לחצו כאן</a></div>
								<? } ?>
							</div>
							
						</div>
					</td>
					<td width="20" bgcolor="#FFFFFF"></td>
					<td width="20" valign="top" bgcolor="#EFEFEF"><img src="<?=$http_url.'images/global/newsletter__left.png'?>" width="20" height="460" border="0" alt=""></td>
				</tr>
			</table>
		</div>
		<?
		if (!$standalone) {
			?>
			<? /* Google Analytics *//* ?>
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
		?>
	</body>
	<?
	if (!$standalone) {
		?>
		</html>
		<?	
	}
	*/
?>
