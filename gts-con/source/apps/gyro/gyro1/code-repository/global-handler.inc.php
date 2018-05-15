<?php

if (defined('GYRO') && constant('GYRO') == '2') {
    set_include_path('.:/usr/share/pear:/usr/share/php:/var/www/apps/gyro/gyro2');
    require 'gyro2-init.php';
    exit;
};

$gyroTimer = new Timer();

// TEMPORARILY HERE UNTIL ALL INDEX FILES ARE REMOVED FROM USE: include the default and then the local strings files.
if (!@include('/var/www/apps/gyro/gyro1/code-repository/strings.inc.php')) abort('Default strings file not found.', __FUNCTION__, __FILE__, __LINE__);
if (!@include('strings.inc.php')) abort('Strings file not found.', __FUNCTION__, __FILE__, __LINE__);

// Include the system-wide functions.
    include('global-functions.inc.php');

// Establish connection with the database.
    if (!mysql_connect(constant('DB_SERVER'), constant('DB_USERNAME'), constant('DB_PASSWORD'))) {
        abort('Could not establish connection with the database.', __FUNCTION__, __FILE__, __LINE__);
    }
    if (!mysql_select_db(constant('DB_NAME'))) {
        abort(mysql_error(), __FUNCTION__, __FILE__, __LINE__);
    }

    mysql_query("SET NAMES utf8");

// Define system-wide constants.
    define('JSON_REQUEST', strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    $sql_query = mysql_query("SELECT name, value FROM sys_variables WHERE `set` = '" . mysql_real_escape_string(defined('SYS_VARIABLES_SET') ? constant('SYS_VARIABLES_SET') : '') . "'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        define($sql['name'], $sql['value']);
    }
    
    define('COOKIE_DOMAIN', preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']));
    define('COOKIE_PATH', href('/'));
    
    define('SECRET_PHRASE', "Don't Stop Me Now");
    define('GYRO_LOCATION', '/var/www/apps/gyro/gyro1/');

// Configure sessions but do not initialize one; pages that use sessions initialize them on their own.
    ini_set('session.name', 'session');
    ini_set('session.use_cookies', 1);
    ini_set('session.use_trans_sid', 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_domain', constant('COOKIE_DOMAIN'));
    ini_set('session.cookie_path', constant('COOKIE_PATH'));

// Initialize session.
// Note: sessions are used constantly to track logged-in users, recently viewed items, etc.
    new session(); // Activate a MySQL session handler, which will replace the default file-based handler.
    
    // If a session variable is passed, than tap into that session (it's probably, but not necessarily from a different host) and refresh.
    // This is used to allow sharing a session accross both the secure and non-secure hosts of the site (as the session cookie cannot be set for a foreign host).
    // The session can be hijacked, but user authentication relies on the ip address, so once it's different from the one expected, the user will no longer be considered authenticated.
    if ($_GET[session_name()]) {
        session_id($_GET[session_name()]);
        session_start();
        
        header('Location: ' . ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . preg_replace('/[&\?]?' . session_name() . '=' . session_id() . '/', '', $_SERVER['REQUEST_URI']));
        exit;
    }
    
    session_start();
    
    // Used to keep track on what hosts the session cookie is set. Because it doesn't expire, once it's set it's there, so we can stop tryinng to extend the session to the other host on every redirect.
    if ($_SERVER['HTTPS']) {
        $_SESSION['session-cookie']['https'] = true;
    } else {
        $_SESSION['session-cookie']['http'] = true;
    }

// Initialize the gyroMail class.
    require '/var/www/apps/phpmailer/class.phpmailer.php';
    class gyroMail extends PHPMailer {
        function gyroMail() {
            $this->From = constant('OUTGOING_EMAILS_FROM_EMAIL');
            $this->FromName = constant('OUTGOING_EMAILS_FROM_NAME');
            $this->IsSMTP();
            $this->Host = '127.0.0.1'; // mail.pnc.co.il
            $this->CharSet = 'UTF-8';
            $this->IsHTML(true);
            $this->Encoding = 'quoted-printable';
        }
        
        function Send() {
            $key = 'Concerto pour une Voix';
            $input = trim($this->to[0][0]); // Note: this requires that the member `$to` is defined as `protected` in `class.phpmailer.php`.
            
            // Encrypt the email and
            $td = mcrypt_module_open('tripledes', '', 'ecb', ''); // Open the cipher.
            $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND); // Create the IV.
            mcrypt_generic_init($td, $key, $iv); // Intialize encryption.
            $encrypted = @mcrypt_generic($td, $input); // Encrypt data.
            mcrypt_generic_deinit($td); // Terminate encryption handler.
            mcrypt_module_close($td); // Close module.
            
            $this->Sender = $this->urlsafe_b64encode($encrypted) . '@' . constant('BOUNCE_HOST');
            
            // Fix a bug in PHPMailer that sometimes breaks a line in the middle of a multibyte character (due to a problematic word-wrap function).
            $this->Body = wordwrap($this->Body, 100, "\n");
            
            $return = parent::Send();
            
            return $return;
        }
        
        function urlsafe_b64encode($input) {
            return preg_replace('/^\-/', 'AAA', strtr(base64_encode($input), '/=', '-_'));
        }
    }

    class Timer {
        var $start_time;
        var $end_time;
        
        function timer() {
            $this->start();
        }
        
        function start() {
            $time = explode(' ', microtime());
            $this->start_time = $time[1] . substr($time[0], 1);
        }
        
        function stop() {
            $time = explode(' ', microtime());
            $this->end_time = $time[1] . substr($time[0], 1);
        }
        
        function time($decimals = 3) {
            if ($this->end_time) {
                return number_format($this->end_time - $this->start_time, $decimals);
            } else {
                $time = explode(' ', microtime());
                $elapsed_time = $time[1] . substr($time[0], 1);
                return number_format($elapsed_time - $this->start_time, $decimals);
            }
        }
    }

// Update the user log-in status (verify and extend its expiration time). Also sets $_SESSION['user'].
    userLogIn_update();

header('Cache-Control: store, cache, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');

// Include the local (site-specific) handler, if it exists.
    if ($_GET['disable-local-handler'] != 1 && ($fp = @fopen('local-handler.inc.php', 'r', 1)) && fclose($fp)) {
        include('local-handler.inc.php');
    }

// Define the DOC_ID & DOC constants (the latter used for links).
    if ($_SERVER['REDIRECT_STATUS'] == 403) {
        error_forbidden();
    } elseif ($_SERVER['REDIRECT_STATUS'] == 404) {
        error_not_found();
    } elseif (!$_REQUEST['doc']) {
        define('DOC_ID', constant('HOME_DOC_ID'));
        define('DOC', constant('DOC_ID'));
    } else {
        $_REQUEST['doc'] = preg_replace('{/+$}', '', $_REQUEST['doc']);
        
        if ($doc_tmp = is_valid_doc($_REQUEST['doc'])) {
            define('DOC_ID', $doc_tmp['doc_id']);
            define('DOC', $doc_tmp['doc_name']);
        } else {
            error_not_found();
        }
    }

// Redirect to the doc-name if the doc was requested by doc-id and a doc-name exists.
    if ($_REQUEST['doc'] == constant('DOC_ID') && constant('DOC_ID') != constant('DOC')) {
        /* DEBUG
        if ($_SESSION['user']['user_id'] == 500) {
            echo constant('DOC_ID') . '<br>';
            echo constant('DOC') . '<br>';
            $GET_VARS_STRING = preg_replace('{doc=' . constant('DOC_ID') . '/?&?}', '', $_SERVER['REDIRECT_QUERY_STRING']);
            echo href(constant('DOC')) . '/' . ($GET_VARS_STRING ? '?' . $GET_VARS_STRING : false);
            echo '<pre dir="ltr">' . print_r($_SERVER, 1) . '</pre>';
            exit;
        }
        */
        $GET_VARS_STRING = preg_replace('{doc=' . constant('DOC_ID') . '/?&?}', '', $_SERVER['REDIRECT_QUERY_STRING']);
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . href(constant('DOC')) . ($GET_VARS_STRING ? '?' . $GET_VARS_STRING : false));
        exit;
    }

// Make sure docs marked as HTTPS are accessed via HTTPS and docs marked as HTTP are access via HTTP. Unmarked docs (NULL) can be accessed either way.
// Try to extend the session to the other host by appending the `session` GET variable, unless it is already known that the session cookie is set on that host.
    $doc_https = @mysql_result(mysql_query("SELECT doc_https FROM sys_docs WHERE doc_id = '" . constant('DOC_ID') . "'"), 0);
    if ($doc_https == '1' && !$_SERVER['HTTPS']) {
        //header('HTTP/1.1 301 Moved Permanently');
        if ($_SESSION['session-cookie']['https']) {
            header('Location: ' . constant('HTTPS_URL') . preg_replace('{^' . href('/') . '}', '', $_SERVER['REQUEST_URI']));
        } else {
            header('Location: ' . constant('HTTPS_URL') . preg_replace('{^' . href('/') . '}', '', $_SERVER['REQUEST_URI']) . (preg_match('/\?/', $_SERVER['REQUEST_URI']) ? '&'  : '?') . session_name() . '=' . session_id());
        }
        exit;
    } elseif ($doc_https == '0' && $_SERVER['HTTPS']) {
        //header('HTTP/1.1 301 Moved Permanently');
        if ($_SESSION['session-cookie']['http']) {
            header('Location: ' . constant('HTTP_URL') . preg_replace('{^' . href('/') . '}i', '', $_SERVER['REQUEST_URI']));
        } else {
            header('Location: ' . constant('HTTP_URL') . preg_replace('{^' . href('/') . '}i', '', $_SERVER['REQUEST_URI']) . (preg_match('/\?/', $_SERVER['REQUEST_URI']) ? '&'  : '?') . session_name() . '=' . session_id());
        }    
        exit;
    }

// Handle affiliate referrals.
    if (!$_SESSION['affiliate']) {
        if ($_GET['a']) {
            // Check that this is a valid affiliate and increment its referrals count.
            if (@mysql_result(mysql_query("SELECT affiliate_id FROM type_affiliate WHERE affiliate_id = '$_GET[a]' AND is_authorized = '1'"), 0)) {
                $_SESSION['affiliate'] = $_GET['a'];
                
                // Set a four-week cookie that will remember that this user came from an affiliate, and associate it with his future sessions.
                setcookie('a', $_GET['a'], time()+2419200, constant('COOKIE_PATH'), constant('COOKIE_DOMAIN'), false, true);
            }
        } elseif ($_COOKIE['a']) {
            // Check that this is a valid affiliate and associate it with the session.
            if (@mysql_result(mysql_query("SELECT affiliate_id FROM type_affiliate WHERE affiliate_id = '$_COOKIE[a]' AND is_authorized = '1'"), 0)) {
                $_SESSION['affiliate'] = $_COOKIE['a'];
            }
        }
        
        if ($_SESSION['affiliate']) {
            mysql_query("UPDATE type_affiliate__log SET cnt = cnt + 1 WHERE affiliate_id = '$_SESSION[affiliate]' AND `date` = DATE(NOW()) AND type = 'click'");
            if (mysql_affected_rows() == 0) {
                mysql_query("INSERT INTO type_affiliate__log (affiliate_id, `date`, type, cnt) VALUE ('$_SESSION[affiliate]', DATE(NOW()), 'click', 1)");
            }
        }
    }

// Retrieve the appropriate handler and pass the doc to the handler.
    list($doc_type, $doc_access) = mysql_fetch_row(mysql_query("SELECT doc_type, doc_access FROM sys_docs WHERE doc_id = '" . constant('DOC_ID') . "'"));
    
    define('DOC_TYPE', $doc_type);
    
    if ($doc_access == '0'
    || ($doc_access == '1' && $_SESSION['user'])
    || ($doc_access == '2' && $_SESSION['user']['authenticated'])
    || ($doc_access == '3' && $_SESSION['user']['authenticated'] && ($_SESSION['user']['type'] == 'content-admin' || $_SESSION['user']['type'] == 'tech-admin'))
    || ($doc_access == '4' && $_SESSION['user']['authenticated'] && $_SESSION['user']['type'] == 'tech-admin')) {
        if (($fp = @fopen('handlers/' . $doc_type . '.inc.php', 'r', 1)) && fclose($fp)) {
            switch ($doc_type) {
                case 'element': $docObj = new Element(constant('DOC_ID')); break;
                case 'article': $docObj = new Article(constant('DOC_ID')); break;
                case 'post':    $docObj = new Post(constant('DOC_ID'));    break;
                case 'user':    $docObj = new User(constant('DOC_ID'));    break;
            }
            
            include('handlers/' . $doc_type . '.inc.php');
        } else {
            abort('Handler not found.', __FUNCTION__, __FILE__, __LINE__);
        }
    } else {
        doc_access_denied();
    }

// Update affiliate associated page-views count.
    if ($_SESSION['affiliate']) {
        mysql_query("UPDATE type_affiliate__log SET cnt = cnt + 1 WHERE affiliate_id = '$_SESSION[affiliate]' AND `date` = DATE(NOW()) AND type = 'view'");
        if (mysql_affected_rows() == 0) {
            mysql_query("INSERT INTO type_affiliate__log (affiliate_id, `date`, type, cnt) VALUE ('$_SESSION[affiliate]', DATE(NOW()), 'view', 1)");
        }
    }

exit; // Useful for possibly appended stuff.

?>
