<?php

	$title = 'הוראת קבע לא עברה בהצלחה';
	$content = 'הוראת קבע מספר '.$recurring_order_id.' עבור מסוף "'.$username.'" לא עברה בהצלחה.';
	$content .= '<br>התקבלה השגיאה הבאה: '.$error;
	$content .= '<br><br>אנא הכנסו לדו״ח השגיאות לקבלת פרטים נוספים: ';
	if ($processor == 'shva') {
		$content .= '<br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors</a>';
	} else if ($params->merchant_type == 'ישראכרט') {
		$content .= '<br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors</a>';
	} else if ($params->merchant_type == 'וריפון') {
		$content .= '<br><a href="https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors" style="font-size:12px;color:#3087CE;">https://'.constant('GTS_CON_HOST').'/gts/console/isracard/errors</a>';
	}
	
	echo generate_email_template($title, false, $content, $processor, $params);

?>
