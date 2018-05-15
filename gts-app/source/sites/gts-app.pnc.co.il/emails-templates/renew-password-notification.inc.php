<?php

	$title = 'תזכורת - חידוש סיסמא למסוף הסליקה';
	$content = 'סיסמת הגישה עבור משתמש "'.$username.'" תפוג בעוד ' . $days . ' ימים.';
	$content .= '<br><br>יש לחדש את הסיסמא בעמוד הבא:';
	if ($processor == 'shva') {
		$content .= '<br><br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?reset_password=1" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?reset_password=1</a>';
	} else if ($params->merchant_type == 'ישראכרט') {
		$content .= '<br><br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?reset_password=1" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?reset_password=1</a>';
	} else if ($params->merchant_type == 'וריפון') {
		$content .= '<br><br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/verifone/transactions?reset_password=1" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/verifone/transactions?reset_password=1</a>';
	}
	
	//$content .= '<br><br><br><br><br><br><br><br><br><br><br><br>';
	
	echo generate_email_template($title, false, $content, $processor, $params);

	/*
	?>
	<body bgcolor="#EFEFEF" style="margin:0;padding:0">
		<div align="center" style="margin:0;padding:20px 0 0 0;direction:rtl;color:#555;font-family:arial;font-size:14px;background:#EFEFEF">
			<table dir="rtl" width="600" align="center" cellspacing="0" cellpadding="0">
				<tr height="140">
					<td colspan="5" height="140"><a href="https://'.constant('GTS_APP_HOST').'/console/gtc-change-password.php" target="_blank"><img src="https://'.constant('GTS_APP_HOST').'/emails-templates/images/newsletter__header.png" width="600" height="140" border="0" align="center" alt=""></a></td>
				</tr>
				<tr>
					<td width="20" valign="top" bgcolor="#EFEFEF"><img src="https://'.constant('GTS_APP_HOST').'/emails-templates/images/newsletter__right.png" width="20" height="460" border="0" alt=""></td>
					<td width="20" bgcolor="#FFFFFF"></td>
					<td width="520" valign="top" bgcolor="#FFFFFF">
						<div align="right" style="text-align:right;direction:rtl">
							<div style="padding:10px 0 20px 0">
								<h1 dir="rtl" align="right" style="display:block;padding:0 0 10px 0;margin:0;color:#3087CE;text-align:right;font-weight:normal;font-size:24px;direction:rtl"><?=$title?></h1>
								<div dir="rtl" style="padding:0 0 20px 0;margin:0 0 17px 0;border-bottom:1px solid #EFEFEF;text-align:right;font-size:14px;direction:rtl"><?=$content?></div>
								<div dir="rtl" style="padding:0 0 10px 0;margin:0 0 10px 0;text-align:center;direction:rtl">
									<span dir="rtl" style="color:#555;font-size:11px;text-align:center;direction:rtl">פאראגון קריאיישנס בע"מ, ז'בוטינסקי 33 רמת-גן 52511, טלפון: 1-599-59-8070</span>
									<a href="https://'.constant('GTS_APP_HOST').'/console/" target="_blank" style="color:#3087CE;font-size:11px;text-align:center;direction:rtl">http://pnc.co.il</a>
								</div>
							</div>
						</div>
					</td>
					<td width="20" bgcolor="#FFFFFF"></td>
					<td width="20" valign="top" bgcolor="#EFEFEF"><img src="https://'.constant('GTS_APP_HOST').'/emails-templates/images/newsletter__left.png" width="20" height="460" border="0" alt=""></td>
				</tr>
			</table>
		</div>
	</body>
	<?
	*/

?>
