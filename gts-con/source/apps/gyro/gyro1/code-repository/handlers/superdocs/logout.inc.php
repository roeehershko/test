<?php

if ($_SESSION['user']) {
    
    gyroLog('gyro_logout - okay');

    mysql_query("UPDATE type_user SET auth_expiration_time = NOW() WHERE user_id = '" . $_SESSION['user']['user_id'] . "'");
    
    unset($_SESSION['user']);
    unset($_SESSION['cart']);
    
    setcookie('user', '', time()-3600, constant('COOKIE_PATH'), constant('COOKIE_DOMAIN'), false, true);
    if (getVar('FB_access_token')) {
        unsetVar('FB_access_token');
    }
	
    // The following is used to unset the user-recognition cookie from both the secure and non-secure host.
    // The login page can be accessed both via HTTP and HTTPS, so it redirects from on to the other to unset the cookie on both.
    // Once the cookie is removed from both domains, $_SESSION['user'] will no longer be set, and the redirection will cease.
    if (!$_SERVER['HTTPS']) {
        header('Location: ' . constant('HTTPS_URL') . preg_replace('{^' . href('/') . '}i', '', $_SERVER['REQUEST_URI']));
        exit;
    } else {
        header('Location: ' . constant('HTTP_URL') . preg_replace('{^' . href('/') . '}i', '', $_SERVER['REQUEST_URI']));
        exit;
    }
}

if ($_GET['referrer']) {
    header('Location: ' . href(rawurldecode($_GET['referrer'])));
} else {
    header('Location: ' . href('/'));
}
exit;

?>
