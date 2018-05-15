<?php

// This has been cancelled by Oren on 12/2/2011
// The reason: in IE we have problems moving the session between HTTP and HTTPS when inside Facebook iFrame
// Therefore we had to allow the login to be accessed without SSL using sys-docs
/*
if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}
*/

recursive('trim', $_GET);
recursive('stripslashes', $_GET);
recursive('strip_tags', $_GET);

recursive('trim', $_POST);
recursive('stripslashes', $_POST);
recursive('strip_tags', $_POST);

if ($_POST['json']) {
    header('Content-type: application/json');

    exit(json_encode(array(
        'success' => userLogIn(mysql_real_escape_string($_POST['email']), mysql_real_escape_string($_POST['password'])) ? true : false
    )));
} elseif ($_POST) {
    recursive('trim', $_POST);
    recursive('stripslashes', $_POST);
    
    if (!$_POST['email']) {
        $errors[] = $_STR_ERR['login']['invalid-email'];
    }
    if (!$_POST['password']) {
        $errors[] = $_STR_ERR['login']['invalid-password'];
    }
    
    if (!$errors) {
        recursive('mysql_real_escape_string', $_POST);
        if (userLogIn($_POST['email'], $_POST['password'])) {
            if ($_POST['referrer']) {
                header('Location: ' . href(rawurldecode($_POST['referrer'])));
            } else {
                header('Location: ' . href('/'));
            }

            exit;
        } else {

            $errors[] = $_STR_ERR['login']['authentication-failed'];
        }
    }
	
    $email = $_POST['email'];
} elseif ($_GET['authority'] == 'facebook') {
	// Added by Oren - 14/12/2010
	if ($_GET['fb_access_token']) {
		$FB_data = json_decode(@file_get_contents('https://graph.facebook.com/me?access_token=' . $_GET['fb_access_token'] . '&fields=email'), 0);
		$vars['access_token'] = $_GET['fb_access_token'];
	} elseif ($fb_cookie = getFacebookCookie(constant('FACEBOOK_APP_ID'), constant('FACEBOOK_SECRET'))) {
		// Checking whether a Facebook cookie exist
		// If a FB cookie exists, it means we used FB:login Javascript feature, and therefore the FB cookie with the access token exists
		if ($fb_cookie['access_token']) {
			$FB_data = json_decode(@file_get_contents('https://graph.facebook.com/me?access_token=' . $fb_cookie['access_token'] . '&fields=email'), 0);
			$vars['access_token'] = $_GET['fb_access_token'];
		} else {
			$errors[] = 'invalid facebook cookie';
		}
	} else {
		// The user returned from the Facebook with an Oauth2 verification code
		$redirect_uri = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . href('/login/') . '?authority=facebook' . ($_GET['referrer'] ? '&referrer=' . rawurlencode($_GET['referrer']) : false);
	   	if ($tmp = @file_get_contents('https://graph.facebook.com/oauth/access_token?client_id=' . constant('FACEBOOK_APP_ID') . '&client_secret=' . constant('FACEBOOK_SECRET') . '&redirect_uri=' . rawurlencode($redirect_uri) . '&code=' . $_GET['code'])) {
	        parse_str($tmp, $vars);
			$FB_data = json_decode(@file_get_contents('https://graph.facebook.com/me?access_token=' . $vars['access_token'] . '&fields=email'), 0);
		} else {
	        $errors[] = 'invalid facebook oauth access token - check application client secret';
	    }
	}
	if ($FB_data) {
        if ($userId = @mysql_result(mysql_query("SELECT user_id FROM type_user WHERE email = '" . mysql_real_escape_string($FB_data->email) . "'"), 0)) {
            userLogIn_init($userId);
            setVar('FB_access_token', $vars['access_token']);
			if ($_GET['json']) {
				$email = $FB_data->email;
			} else {
            	header('Location: ' . href($_GET['referrer'] ? rawurldecode($_GET['referrer']) : '/'));
            	exit;
			}
        } else {
            $errors[] = 'invalid local user' . ($FB_data->email ? ' (' . $FB_data->email . ')' : false);
        }
    } else {
        $errors[] = 'invalid facebook access token';
    }
} elseif ($_SESSION['user']) {
    $email = $_SESSION['user']['email'];
}

##

$T_superdoc['errors'] = $errors;
$T_superdoc['referrer'] = htmlspecialchars($_POST['referrer'] ?: $_GET['referrer']);
$T_superdoc['email'] = htmlspecialchars($email);

if ($_GET['json']) {
	header('Content-type: application/json');
	echo json_encode($T_superdoc);
	exit;
} else {
	echo renderTemplate('superdocs/login', $T_superdoc);
} 

?>
