<?php

header('Content-Type: text/xml; charset=UTF-8');

##

echo "<?xml version='1.0' encoding='UTF-8'?>" . "\n";

if (updateUserMobile($_GET['action'], $_GET['mobile'], $_GET['code'], $error)) {
    echo '<XMLResult>' . "\n";
    echo '    <result success="1"/>' . "\n";
    echo '</XMLResult>' . "\n";
} else {
    echo '<XMLResult>' . "\n";
    echo '    <result success="0" error="' . $error . '"/>' . "\n";
    echo '</XMLResult>' . "\n";
}

##

function updateUserMobile($action, $mobile, $code, &$error) {
    if ($action != 'send-code' && $action != 'verify-code') {
        $error = 'invalid action';
        return false;
    }
    
    if (!preg_match('/^\d{10,15}$/', $mobile)) {
        $error = 'invalid mobile';
        return false;
    }
    
    if ($action == 'verify-code' && !preg_match('/^[a-z0-9]{5}$/i', $code)) {
        $error = 'invalid code';
        return false;
    }
    
    if ($action == 'send-code') {
        if ($message = renderTemplate('sms/sms-verification-code', array('code' => getMobileCode($mobile)))) {
            if (sendSMS($mobile, $message)) {
                return true;
            } else {
                $error = 'unable to send sms';
                return false;
            }
        }
    }
    
    if ($action == 'verify-code') {
        if ($code == getMobileCode($mobile)) {
            mysql_query("UPDATE type_user SET mobile = '" . mysql_real_escape_string($mobile) . "' WHERE user_id = '" . $_SESSION['user']['user_id'] . "'");
            return true;
        } else {
            $error = 'incorrect code';
            return false;
        }
    }
}

function getMobileCode($mobile) {
    return substr(md5($mobile . constant('SECRET_PHRASE') . constant('SITE_NAME')), 19, 5);
}

?>
