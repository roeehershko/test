<?php

if ($merchant_id == '1') {
    $site_title = 'pnc.co.il';
	$language = 'he';
} else if ($merchant_id == '2') {
    $site_title = 'pnc.co.il';
	$language = 'he';
} else if ($merchant_id == '17') {
    $site_title = 'pnc.co.il';
	$language = 'he';
} else if ($merchant_id == '41') {
    $site_title = 'sellbuy.co.il';
	$language = 'he';
} else if ($merchant_id == '49') {
   	$site_title = 'visaform.co.il';
    $language = 'he';
} else if ($merchant_id == '55') {
    $site_title = 'hoopa.com';
	$language = 'he';
} else if ($merchant_id == '57') {
    $site_title = 'hoopa.com';
	$language = 'he';
} else if ($merchant_id == '59') {
    $site_title = 'hoopa.com';
	$language = 'he';
} else if ($merchant_id == '67') {
   	$site_title = 'linkusim.com';
    $language = 'en';
} else if ($merchant_id == '75') {
    $site_title = 'Speakeep';
	$language = 'he';
} else if ($merchant_id == '81') {
    $site_title = 'in4use';
	$language = 'he';
} else if ($merchant_id == '83') {
    $site_title = 'Kefel';
	$language = 'he';
} else if ($merchant_id == '89') {
    $site_title = 'Odyssey';
	$language = 'he';
} else if ($merchant_id == '91') {
    $site_title = 'KinderValt';
	$language = 'he';
} else if ($merchant_id == '97') {
    $site_title = 'Yes-I-Do';
	$language = 'he';
} else if ($merchant_id == '101') {
    $site_title = 'personaljudaica.com';
	$language = 'en';
} else if ($merchant_id == '102') {
    $site_title = 'ask4.co.il';
	$language = 'he';
} else {
    $site_title = 'Site Title';
    $language = 'he';
}

##

if ($language == 'he') {
	$direction = 'rtl';
	$align = 'right';
	?>
	<body dir="<?=$direction?>">
		<div style="font-family:Arial;font-size:14px;padding:20px;direction:<?=$direction?>;text-align:<?=$align?>" dir="<?=$direction?>">
שלום,
			<br>
			<? if (!$refund) { ?>
	ממתינה לכם חשבונית-מס חדשה.
			<? } else { ?>
	ממתינה לכם חשבונית זיכוי חדשה.
<? } ?>
			<br>
אתם יכולים לצפות בה בכתובת הבאה:
			<br>
			<a href="<?=$link?>" target="_blank"><?=$link?></a>
		</div>
	</body>
	<?
} else if ($language == 'en') {
	$direction = 'ltr';
	$align = 'left';
	?>
	<body dir="<?=$direction?>">
		<div style="font-family:Arial;font-size:14px;padding:20px;direction:<?=$direction?>;text-align:<?=$align?>" dir="<?=$direction?>">
			Hello,
			<br>
			<? if (!$refund) { ?>
				You have a new invoice from <?=$site_title?>.
			<? } else { ?>
				You have a new refund-invoice from <?=$site_title?>.
			<? } ?>
			<br>
			You may download it at the following link:
			<br>
			<a href="<?=$link?>" target="_blank"><?=$link?></a>
		</div>
	</body>
	<?
}
?>
