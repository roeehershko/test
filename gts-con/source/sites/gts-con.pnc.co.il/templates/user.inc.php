<?php

// iOS support based on: http://mobicontact.info/iphone/download-contact-from-web-page/

// Serve up a business card (vCard or HTML) if a vCard is defined for this user.
$userObj = new User($user_info['id']);
if ($userObj->vcard) {
    $lang = $_REQUEST['lang'] ? strtolower($_REQUEST['lang']) : strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)); // Determine the requested language.
        
    header('Cache-Control: store, cache, must-revalidate');
    
    // Create the vCard.
    ob_start();
    
    echo 'BEGIN:VCARD' . "\n";
    echo 'VERSION:2.1' . "\n";
    echo 'EMAIL;INTERNET:' . $userObj->vcard['email'] . "\n";
    
    if ($lang == 'he') {
        $fullName = $userObj->vcard['first_name_he'] . ' ' . $userObj->vcard['last_name_he'];
        $telCell = $userObj->vcard['mobile'] ? preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '(\1) \2-\3', preg_replace('/\D/', '', $userObj->vcard['mobile'])) : null;
        $company = 'פאראגון בע"מ';
        $telWork = '(03) 570-8989';
        $faxWork = '(03) 547-8070';
        $urlWork = 'www.pnc.co.il';
        $filename = $userObj->vcard['first_name_he'] . '-' . $userObj->vcard['last_name_he'];
        
        echo 'N;CHARSET=WINDOWS-1255:' . $userObj->vcard['last_name_he'] . ';' . $userObj->vcard['first_name_he'] . "\n";
        echo 'TITLE;CHARSET=WINDOWS-1255:' . $userObj->vcard['title_he'] . "\n";
        echo 'ORG;CHARSET=WINDOWS-1255:' . $company . "\n";
        echo 'ADR;WORK;CHARSET=WINDOWS-1255:' . 'ת"ד 7428' . ';;' . 'המגשימים 20' . ';' . 'פתח תקווה' . ';;' . '4934829' . "\n";
    } else {
        $fullName = $userObj->vcard['first_name_en'] . ' ' . $userObj->vcard['last_name_en'];
        $telCell = $userObj->vcard['mobile'] ? preg_replace('/^0(\d{2})(\d{3})(\d{4})$/', '+972 (\1) \2-\3', preg_replace('/\D/', '', $userObj->vcard['mobile'])) : null;
        $company = 'Paragon Ltd.';
        $telWork = '+972 (3) 570-8989';
        $faxWork = '+972 (3) 547-8070';
        $urlWork = 'www.pnc.co.il/en';
        $filename = $userObj->vcard['first_name_en'] . '-' . $userObj->vcard['last_name_en'];
        
        echo 'N:' . $userObj->vcard['last_name_en'] . ';' . $userObj->vcard['first_name_en'] . "\n";
        echo 'TITLE:' . $userObj->vcard['title_en'] . "\n";
        echo 'ORG:' . $company . "\n";
        echo 'ADR;WORK:' . 'P.O. Box 7428' . ';;' . '20 HaMagshimim St.' . ';' . 'Petah Tikva' . ';;' . '4934829' . ';' . 'Israel' . "\n";
    }
    
    echo 'TEL;CELL:' . $telCell . "\n";
    echo 'TEL;WORK:' . $telWork . "\n";
    echo 'TEL;FAX;WORK:' . $faxWork . "\n";
    echo 'URL;WORK:' . $urlWork . "\n";
    
    // If a photo is defined as part of the vCard, and it conforms to the required restrictions, add it to the vCard.
    if ($userObj->vcard['photo']) {
        $photoInfo = getimagesize($userObj->vcard['photo']);
        if ($photoInfo[0] == 96 && $photoInfo[1] == 96 && $photoInfo['mime'] == 'image/png') {
            echo wordwrap('PHOTO;ENCODING=b;TYPE=PNG:' . base64_encode(@file_get_contents($userObj->vcard['photo'])), 73, "\n ", true) . "\n";
        }
    }
    
    echo 'END:VCARD' . "\n";    
    
    // If the requested language is Hebrew, encode the vCard in the appropriate charset.
    if ($lang == 'he') {
        $vcard = iconv('UTF-8', 'WINDOWS-1255', ob_get_clean());
    } else {
        $vcard = ob_get_clean();
    }
    
    if ($_REQUEST['type'] == 'ics'): // Attach the vCard to an ad-hoc calendar event and serve that.
        
        $DT = date('Ymd') . 'T' . date('Hi') . '00';
        
        header('Content-type: text/x-vcalendar; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.ics');
        
        echo 'BEGIN:VCALENDAR' . "\n";
        echo 'VERSION:2.0' . "\n";
        echo 'BEGIN:VEVENT' . "\n";
        if ($lang == 'he') {
            echo 'SUMMARY:לחץ על ' . $filename . '.vcf' . ' כדי לשמור את כרטיס הביקור.' . "\n";
        } else {
            echo 'SUMMARY:Click ' . $filename . '.vcf' . ' to save the contact.' . "\n";
        }
        echo 'DTSTART:' . $DT . "\n";
        echo 'DTEND:'. $DT . "\n";
        echo 'DTSTAMP:' . $DT . 'Z' . "\n";
        echo 'ATTACH;VALUE=BINARY;ENCODING=BASE64;FMTTYPE=text/directory;' . "\n";
        echo 'X-APPLE-FILENAME=' . $filename . '.vcf' . ':' . "\n";
        echo preg_replace('/(.+)/', ' $1', chunk_split(base64_encode($vcard), 74, "\n"));
        echo 'END:VEVENT' . "\n";
        echo 'END:VCALENDAR' . "\n";
        
    elseif ($_REQUEST['type'] == 'vcf'): // Serve up the vCard as a standard VCF file.
        
        header('Content-Type: text/vcard; charset=WINDOWS-1255');
        header('Content-Disposition: attachment; filename=' . $filename . '.vcf');
        
        echo $vcard;
        
    else:
        
        // Determine wether the page is accessed from an iOS device and whether Mobile Safari is the accessing browser.
        $iOS = (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) || (strstr($_SERVER['HTTP_USER_AGENT'],'iPod') !== false) || (strstr($_SERVER['HTTP_USER_AGENT'],'iPad') !== false);
        $mobileSafari = strstr($_SERVER['HTTP_USER_AGENT'], ' AppleWebKit/') && strstr($_SERVER['HTTP_USER_AGENT'], ' Mobile/') && strstr($_SERVER['HTTP_USER_AGENT'], ' Safari/') && strstr($_SERVER['HTTP_USER_AGENT'], ' Version/');
        
        $fullName_he = $userObj->vcard['first_name_he'] . ' ' . $userObj->vcard['last_name_he'];
        $fullName_en = $userObj->vcard['first_name_en'] . ' ' . $userObj->vcard['last_name_en'];
        
        $unsupportedBrowserWarning_he = 'הדפדפן שבשימוש אינו תומך ביבוא כרטיסי ביקור אלקטרוניים.' . "\n" . '<p><b>על מנת לשמור את כרטיס הביקור ברשימת אנשים הקשר יש לפתוח את הדף הנוכחי בדפדפן ספארי.</b></p>';
        $unsupportedBrowserWarning_en = 'This browser does not support importing business cards.' . "\n" . '<p><b>To add this business card to your Contacts please open this page in Mobile Safari.</b></p>';
        
        // Do not convert to Hebrew twice.
        if ($lang != 'he') {
            $fullName_he = iconv('UTF-8', 'WINDOWS-1255', $fullName_he);
            $unsupportedBrowserWarning_he = iconv('UTF-8', 'WINDOWS-1255', $unsupportedBrowserWarning_he);
        }
        
        // Serve an HTML (jQuery Mobile) version of the business card.
        ob_start();
        
        ?>
        <!DOCTYPE html> 
        <html>
            <head>
            	<meta charset="UTF-8" />
            	<meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1" />
            	<meta name="apple-mobile-web-app-capable" content="yes" />
            	<meta name="apple-mobile-web-app-status-bar-style" content="black" />
            	
            	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.css" />
            	<script src="http://code.jquery.com/jquery-1.6.4.min.js"></script>
            	<script src="http://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.js"></script>
            	
        	    <style type='text/css'>
                    @media only screen and (min-width: 600px) {
                        .ui-page {
                            width: 600px !important;
                            margin: 0 auto !important;
                            position: relative !important;
                            border-right: 3px #666 outset !important;
                            border-left: 3px #666 outset !important;
                        }
                        .ui-header, .ui-footer-fixed {
                            width: 600px !important;
                            position: absolute !important;
                        }
                    }
                    div.hebrew .ui-btn-text {
                        font-size: 18px !important;
                    }
                </style>
        	</head> 
        	
            <body>
                <div data-role="page">
                    <div data-role="header" data-position="fixed" data-tap-toggle="false">
                        <? if ($lang == 'he'): ?>
                            <h2 dir="rtl">כרטיס ביקור</h2>
                        <? else: ?>
                            <h2>Business Card</h2>
                        <? endif; ?>
                    </div>
                    <div data-role="content">
                        <? if ($lang == 'he'): ?>
                            <div dir="rtl">
                                <? if ($userObj->vcard['photo']): ?>
                                    <img src="<?=href($userObj->vcard['photo'])?>" alt="<?=$fullName?>" style="float: right; margin: 8px 0 75px 20px; border: 1px solid #000; width: 96px; height: 96px; box-shadow: 3px 5px 5px #666;" />
                                <? endif; ?>
                                <div style="padding: 3px 0;"><b><?=$fullName?></b></div>
                                <div style="padding: 3px 0;"><?=$userObj->vcard['title_he']?></div>
                                <? if ($telCell): ?>
                                <div style="padding: 3px 0;">סלולרי: <nobr><a href="tel:<?=$telCell?>" dir="ltr"><?=$telCell?></nobr></a></div>
                                <? endif; ?>
                                <div style="padding: 3px 0;">משרד: <nobr><a href="tel:<?=$telWork?>" dir="ltr"><?=$telWork?></nobr></a></div>
                                <div style="padding: 3px 0;">אימייל: <nobr><a href="mailto:<?=$userObj->vcard['email']?>" dir="ltr"><?=$userObj->vcard['email']?></nobr></a></div>
                            </div>
                        <? else: ?>
                            <div>
                                <? if ($userObj->vcard['photo']): ?>
                                    <img src="<?=href($userObj->vcard['photo'])?>" alt="<?=$fullName?>" style="float: left; margin: 8px 20px 75px 0; border: 1px solid #000; width: 96px; height: 96px; box-shadow: 3px 5px 5px #666;" />
                                <? endif; ?>
                                <div style="padding: 3px 0;"><b><?=$fullName?></b></div>
                                <div style="padding: 3px 0;"><?=$userObj->vcard['title_en']?></div>
                                <? if ($telCell): ?>
                                <div style="padding: 3px 0;">mobile: <nobr><a href="tel:<?=$telCell?>"><?=$telCell?></nobr></a></div>
                                <? endif; ?>
                                <div style="padding: 3px 0;">office: <nobr><a href="tel:<?=$telWork?>"><?=$telWork?></nobr></a></div>
                                <div style="padding: 3px 0;">e-mail: <nobr><a href="mailto:<?=$userObj->vcard['email']?>"><?=$userObj->vcard['email']?></nobr></a></div>
                            </div>
                        <? endif; ?>
                        
                        <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid #AAA;">
                            <? if ($iOS && $mobileSafari): ?>
                                <div style="padding: 5px 0;"><a href="?type=ics&lang=en" rel="external" data-role="button" data-icon="plus"><?=$fullName_en?></a></div>
                                <div style="padding: 5px 0;"><a href="?type=ics&lang=he" rel="external" data-role="button" data-icon="plus" data-iconpos="right"><?=$fullName_he?></a></div>
                            <? elseif ($iOS && !$mobileSafari): ?>
                                <? if ($lang == 'he'): ?>
                                    <div dir="rtl"><?=$unsupportedBrowserWarning_he?></div>
                                <? else: ?>
                                    <?=$unsupportedBrowserWarning_en?>
                                <? endif; ?>
                            <? else: ?>
                                <div style="padding: 5px 0;"><a href="?type=vcf&lang=en" rel="external" data-role="button" data-icon="plus"><?=$fullName_en?></a></div>
                                <div style="padding: 5px 0;" class="hebrew"><a href="?type=vcf&lang=he" rel="external" data-role="button" data-icon="plus" data-iconpos="right"><?=$fullName_he?></a></div>
                            <? endif; ?>
                        </div>
                    </div>
                    <div data-role="footer" data-position="fixed" data-tap-toggle="false">
                        <h2><?=$company?></h2>
                    </div>
                </div>
            </body>
        </html>
        <?
        
        if ($lang == 'he') {
            $stdout = iconv('UTF-8', 'WINDOWS-1255', ob_get_clean());
        } else {
            $stdout = ob_get_clean();
        }
        
        header('Content-Type: text/html; charset=WINDOWS-1255');
        
        echo $stdout;
        
    endif;
    
    exit;
}

header('Location:' . href('/'));
exit;

?>
