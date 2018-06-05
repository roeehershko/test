<?php

function __autoload($className) {
    $filename = 'handlers/objects/' . $className . '.inc.php';
    
    if (($fp = @fopen($filename, 'r', 1)) && fclose($fp)) {
        include($filename);
    } else {
        abort('Could not find class.', __FUNCTION__, __FILE__, __LINE__);
    }
}

##

class session {
    function session() {
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    function open($savePath, $sessName) {
        return true;
    }
    
    function close() {
        $this->gc(ini_get('session.gc_maxlifetime'));
        return true;
    }
    
    function read($id) {
        if ($data = @mysql_result(mysql_query("SELECT data FROM sys_sessions WHERE id = '" . mysql_real_escape_string($id) . "'"), 0)) {
            return $data;
        } else {
            return ''; // Must send an empty string if there's no session data.
        }
    }
    
    function write($id, $data) {
        mysql_query("REPLACE INTO sys_sessions SET id = '" . mysql_real_escape_string($id) . "', data = '" . mysql_real_escape_string($data) . "'");
        return mysql_affected_rows() ? true : false;
    }
    
    function destroy($id) {
        mysql_query("DELETE FROM sys_sessions WHERE id = '" . mysql_real_escape_string($id) . "'");
        return mysql_affected_rows() ? true : false;
    }
    
    function gc($maxLifetime) {
        mysql_query("DELETE FROM sys_sessions WHERE UNIX_TIMESTAMP(last_accessed) < '" . (time() - $maxLifetime) . "'");
        return mysql_affected_rows() ? true : false;
    }
}

##

// Notes on authentication:

// Sessions are used to keep track of logged-in users. The user's ip address is recorded on login, and checked on every access.
// If the ip address changes, the session will no longer consider the user as logged-in. The user remains logged-in as long as
// the expiration time is not reached. The expiration time is extended on every access.

// Check the user email-pass combo; if valid log-in the user and return the user-id.
function userLogIn($email, $password) {
    usleep(2000000);
    if ($userId = @mysql_result(mysql_query("SELECT user_id FROM type_user WHERE email = '$email'"), 0)) {
        userLogIn_init($userId);

        return $userId;
    } else {

        gyroLog('gyro_userLogIn_init - fail', $email);
        return false;
    }

}

function userLogIn_init($userId) {
    // Make the session recognize the user as logged-in and authenticated. Set the initial expiration time.
    mysql_query("UPDATE type_user SET auth_remote_address = '$_SERVER[REMOTE_ADDR]', auth_expiration_time = FROM_UNIXTIME(UNIX_TIMESTAMP() + 1200), last_login = NOW() WHERE user_id = '$userId'");
    
    // Set the $_SESSION['user'] variable with basic user details.
    $_SESSION['user'] = mysql_fetch_assoc(mysql_query("SELECT doc_id AS id, doc_subtype AS type, user_id, email, first_name, last_name FROM sys_docs LEFT JOIN type_user ON doc_id = user_id WHERE user_id = '$userId'"));
    $_SESSION['user']['authenticated'] = true;

    gyroLog('gyro_userLogIn_init - okay'); 
}

// Determine whether the user is still logged-in (determined by the ip address and the expiration time).
// If the user is recognized as logged-in, extend the expiration time.
function userLogIn_update() {
    if ($_SESSION['user']) {
        // Set a permanent cookie that is used to weakly authenticate the user after the session dies.
        setcookie('user', $_SESSION['user']['user_id'] . '.' . md5($_SESSION['user']['user_id'] . constant('SECRET_PHRASE') . constant('SITE_URL')), time()+31536000, constant('COOKIE_PATH'), constant('COOKIE_DOMAIN'), false, true);
        
        if ($_SESSION['user']['authenticated']) {
            $WHERE = constant('USE_IP_FOR_AUTHENTICATION') ? "auth_remote_address = '$_SERVER[REMOTE_ADDR]'" : "1";
            if (@mysql_result(mysql_query("SELECT user_id FROM type_user WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND $WHERE AND auth_expiration_time > NOW()"), 0)) {
                mysql_query("UPDATE type_user SET auth_expiration_time = FROM_UNIXTIME(UNIX_TIMESTAMP() + 1200), last_login = NOW() WHERE user_id = '" . $_SESSION['user']['user_id'] . "'");
                
                return true;
            } else {
                $_SESSION['user']['authenticated'] = false;
                
                return true;
            }
        } else {
            mysql_query("UPDATE type_user SET last_login = NOW() WHERE user_id = '" . $_SESSION['user']['user_id'] . "'");
            
            return true;
        }
    } elseif ($_COOKIE['user']) {
        list($userId, $hash) = @explode('.', $_COOKIE['user']);
        if ($hash == md5($userId . constant('SECRET_PHRASE') . constant('SITE_URL'))) {
            // Make the session recognize the user as logged-in but not authenticated.
            mysql_query("UPDATE type_user SET last_login = NOW() WHERE user_id = '$userId'");
            
            // Set the $_SESSION['user'] variable with basic user details.
            $_SESSION['user'] = mysql_fetch_assoc(mysql_query("SELECT doc_id AS id, doc_subtype AS type, user_id, email, first_name, last_name FROM type_user LEFT JOIN sys_docs ON doc_id = user_id WHERE user_id = '$userId'"));
            $_SESSION['user']['authenticated'] = false;
            
            return true;
        }
    }
    
    unset($_SESSION['user']);
    
    return false;
}

##

// If the user is not logged-in, redirect him to the login page; otherwise show the 403 page.
function doc_access_denied() {
    if ($_SESSION['user']['authenticated']) {
        error_forbidden();
    } else {
        // The reference is always the local (sometimes nested) request (i.e. always "/my-account/"; never "/artgallery18.com/my-account", as requests under secure.pnc.co.il would normally look).
        // This allows for a correct referrer when moving from both HTTP and HTTPS pages to both HTTP and HTTPS pages.
        // (without stripping the domain name under secure.pnc.co.il we'd end up with a referrer like "/artgallery18.com/?doc=xyz", which the login page would transfer to "/artgallery18.com/artgallery18.com/?doc=xyz" when moving to HTTPS pages)
        header('Location: ' . href('login/?referrer=' . rawurlencode(preg_replace('{^' . href('/') . '}i', '/', $_SERVER['REQUEST_URI']))));
        exit;
    }
}

##

function error_not_found() {
    header("HTTP/1.1 404 Not Found");
    
    $stdout = renderTemplate('superdocs/404', array(
        'url' => $_SERVER['REQUEST_URI']
    ));
    
    echo renderTemplate('global/main', array(
        'content' => $stdout
    ));
    
    exit;
}

function error_forbidden() {
    header("HTTP/1.1 403 Forbidden");
    
    $stdout = renderTemplate('superdocs/403', array(
        'url' => $_SERVER['REQUEST_URI']
    ));
    
    echo renderTemplate('global/main', array(
        'content' => $stdout
    ));
    
    exit;
}

##

function is_valid_doc($doc = false) {
	if ($doc) {
		// 2018-01-24 - Protection from vulnerability by 'sleep' command (e.g. "https://.../'%20and%20(select*from(select(sleep(10)))a)--" )
      		$doc = preg_replace('/[^0-9a-zA-Z-\/]+/', '', mysql_real_escape_string($doc));
    	}    	
	return mysql_fetch_assoc(mysql_query("SELECT doc_id, IFNULL(doc_name, doc_id) AS doc_name FROM sys_docs WHERE doc_id = '$doc' OR doc_name = '$doc'"));
}

function is_valid_doc_name($doc_name, $doc_name_prefix) {
    if (!preg_match('/^' . preg_quote($doc_name_prefix, '/') . '([^+\.]+)[^\/]$/', $doc_name)) {
        return false;
    } else if (preg_match('/[\?&=]/', $doc_name)) {
        return false;
    }
    return true;
}

##

// Perform some function recursively on every element, be it a variable, an array, or a nested array (i.e. trim, stripslashes).
function recursive($function, &$array) {
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            // Preventing XSS of the keys
            $protected_key = trim(strip_tags($key));
            $array[$protected_key] = $value;
            if ($protected_key != $key) {
                unset($array[$key]);
            }
        }
        foreach ($array as $variable => $null) {
            recursive($function, $array[$variable]);
        }
    } elseif ($array) {
        $array = call_user_func($function, $array);
    }
}

##

function abort($string, $function = false, $file = false, $line = false) {
    if ($file && $line) {
        //$string = '<u>' . $file . '</u> [' . $line . '] :: ' . $string;
    }
    if ($function) {
        $string = '<em>' . $function . '</em> :: ' . $string;
    }
    
    echo '<table style="width: 100%; height: 95%;">' . "\n";
    echo '    <tr>' . "\n";
    echo '        <td><table align="center"><tr><td style="font-family: Courier New; font-size: 12px; color: #666666;">' . $string . '</td></tr></table></td>' . "\n";
    echo '    </tr>' . "\n";
    echo '</table>' ."\n";
    
    exit;
}

##

function xmlentities($string, $reverse = false) {
    $arrayA = array('&', '"', "'", '<', '>');
    $arrayB = array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;');
    
    if (!$reverse) {
        return str_replace($arrayA, $arrayB, $string);
    } else {
        return str_replace($arrayB, $arrayA, $string);
    }
}

##

function html2pdf($url, $filename, $saveToDisk = false) {
    if ($saveToDisk && $filename && (strpos($filename, '/') === false)) {
        if (!file_exists(constant('FILES_BASE') . '.pdfs')) {
            mkdir(constant('FILES_BASE') . '.pdfs');
        }
        
        passthru("xhtml2pdf -q " . escapeshellarg($url) . " " . escapeshellarg(constant('FILES_BASE') . '.pdfs/' . $filename));
    } else {
        putenv('HTMLDOC_NOCGI=1');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $filename);
        //flush();
        passthru("xhtml2pdf -q " . escapeshellarg($url) . " -");
        exit;
    }
}

##

function scheduleAction($unix_timestamp, $action_url) {
    if ($stdout = shell_exec('/var/www/apps/at-proxy/at-proxy ' . escapeshellarg($unix_timestamp) . ' ' . escapeshellarg($action_url))) {
        if (preg_match('/^job (\d+) at ([\w\d- :]+)$/', $stdout, $match)) {
            return $match[1];
        }
    }
    
    return false;
}

function unscheduleAction($jobId) {
    shell_exec('/var/www/apps/at-proxy/at-proxy -d ' . preg_replace('/\D/', '', $jobId));
}

##

function renderTemplate($templateName, $dataArray) {
    
    static $flagFirst;
    
    static $site_name;
    static $site_title;
    static $site_url;
    
    static $http_url;
    static $https_url;
    
    static $home_doc_id;
    
    static $outgoing_emails_from_name;
    static $outgoing_emails_from_email;
    
    static $use_catchas;
    
    static $secure;
    static $doc;
    static $doc_id;
    static $doc_type;
    static $doc_parameters;
    static $user;
    static $groups;
    
    global $gyroTimer;
    
    global $_STR_CONTEXT;
    
    if (!$flagFirst) {
        $flagFirst = true;
        
        // Define some global variables that should be availbale to every template.
            $secure = $_SERVER['HTTPS'] ? 1 : 0;
            
            // For 404 pages the following are not defined.
            if (defined('DOC')) {
                $doc = constant('DOC');
                $doc_id = constant('DOC_ID');
                $doc_type = constant('DOC_TYPE');
            }
            
            $site_name = constant('SITE_NAME');
            $site_title = constant('SITE_TITLE');
            $site_url = constant('SITE_URL');
            
            $http_url = constant('HTTP_URL');
            $https_url = constant('HTTPS_URL');
            
            $home_doc_id = constant('HOME_DOC_ID');
            
            $outgoing_emails_from_name = constant('OUTGOING_EMAILS_FROM_NAME');
            $outgoing_emails_from_email = constant('OUTGOING_EMAILS_FROM_EMAIL');
            
            $use_captchas = constant('USE_CAPTCHAS');
            
            $doc_parameters = getDocParameters($doc_id);
            
            if ($_SESSION['user']) {
                $user = array(
                    'id' => $_SESSION['user']['user_id'],
                    'type' => $_SESSION['user']['type'],
                    'authenticated' => $_SESSION['user']['authenticated'],
                    'email' => $_SESSION['user']['email'],
                    'first_name' => $_SESSION['user']['first_name'],
                    'last_name' => $_SESSION['user']['last_name'],
                );
            }
            
            $groups = getGroups_complete();
    }
    
    // Define the passed variables in this scope by name.
        if ($dataArray) {
            foreach ($dataArray as $variable => $value) {
                $$variable = $value;
            }
        }
    
    ob_start();
    
    if (($fp = @fopen('templates/' . $templateName . '.inc.php', 'r', 1)) && fclose($fp)) {
        include('templates/' . $templateName . '.inc.php');
    } else {
        abort('Template not found [' . $templateName . '].', __FUNCTION__, __FILE__, __LINE__);
    }
    
    $stdout = ob_get_contents();
    ob_end_clean();
    return $stdout;
}

##

function generateCAPTCHA($width = 200, $height = 50, $backgroundImage = false) {
    require_once 'include/php-captcha.inc.php';
    
    $aFonts = array('/usr/share/fonts/bitstream-vera/VeraBd.ttf', '/usr/share/fonts/bitstream-vera/VeraIt.ttf', '/usr/share/fonts/bitstream-vera/Vera.ttf');
    $captcha = new PhpCaptcha($aFonts, $width, $height);
    
    $captcha->SetCharSet(array('2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z'));
    
    if ($backgroundImage && file_exists($backgroundImage)) {
        $captcha->SetBackgroundImages($backgroundImage);
    }
    
    $secretPhrase = 'CAPT' . constant('SECRET_PHRASE') . 'CHA';
    $directory = constant('FILES_BASE_VIRTUAL') . '.captchas';
    $filename = $captcha->Create($secretPhrase, $directory);
    
    // Delete old CAPTCHAs.
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && is_file($directory . '/' . $file)) {
                if (fileatime($directory . '/' . $file) < strtotime('-15 minutes')) {
                    @unlink($directory . '/' . $file);
                }
            }
        }
        closedir($handle);
    }
    
    if ($filename) {
        return array('file' => $directory . '/' . $filename, 'hash' => basename($filename, '.jpg'));
    } else {
        return false;
    }
}

function validateCAPTCHA($hash, $code) {
    usleep(2000000);
    
    $filename = constant('FILES_BASE_VIRTUAL') . '.captchas/' . $hash . '.jpg';
    
    // Only allow validation if the CAPTCHA (image) exists, to thwart guessing.
    if (!file_exists($filename)) {
        return false;
    }
    
    // Delete CAPTCHA immediately after the validation attempt.
    @unlink($filename);
    
    require_once 'include/php-captcha.inc.php';
    
    $secretPhrase = 'CAPT' . constant('SECRET_PHRASE') . 'CHA';
    
    if ($hash && $code && PhpCaptcha::Validate($secretPhrase, $hash, $code)) {
        return true;
    } else {
        return false;
    }
}

##

function imageResize($file, $width = false, $height = false, $quality = false, $newfilename = false) {
    if (!preg_match('/\.\./', $file) && !preg_match('{^/}', $file) && file_exists($file) && (!$newfilename || (!preg_match('/\.\./', $newfilename) && !preg_match('{^/}', $newfilename)))) {
        // Create a temporary file indicating that the file is currently being altered.
        if (!file_exists($file . '!')) {
            
            $cmd = false;
            
            if (($width && preg_match('/^[1-9]\d*$/', $width)) || ($height && preg_match('/^[1-9]\d*$/', $height))) {
                list($w, $h) = getimagesize($file);
                
                if ($w && $h) {
                    // Calculate the aspect ratio if only one dimension is given.
                    if ($width && !$height) {
                        $height = round($h * $width / $w);
                    } elseif (!$width && $height) {
                        $width = round($w * $height / $h);
                    }
                    
                    // Only resize when necessary.
                    if (($w && $w != $width) || ($h && $h != $height)) {
                        $cmd[] = '-resize ' . $width . 'x' . $height . '!';
                        $resize = true; // Added by oren 12/7/2009: We want to change quality ONLY if 'resize' is made.
                    }
                }
            }
            
            $action_required = false;
            if ($quality && $resize && preg_match('/^\d+$/', $quality) && $quality > 0 && $quality <= 100) {
                $cmd[] = '-quality ' . $quality;
                $action_required = true;
            } elseif ($resize) {
                $cmd[] = '-quality 100';
                $action_required = true;
            }
            
            if ($action_required || $newfilename) {
                touch($file . '!'); // Create temporary file.
                exec('convert ' . ($cmd ? implode(' ', $cmd) : '') . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($newfilename ? $newfilename : $file), $output, $return);
                unlink($file . '!'); // Remove temporary file.
            }
            
            if ($return == 0) {
                return true;
            }
        }
    }
    
    return false;
}

##

function pageNavbar($page, $itemsNum, $itemsPerPage, $url, $neighborsNum = 15, $strings = false) {
    if ($strings) {
        $str_prevPage = $strings['prevPage'];
        $str_prevPageDiv = $strings['prevPageDiv'];
        
        $str_pagesDiv = $strings['pagesDiv'];
        
        $str_nextPageDiv = $strings['nextPageDiv'];
        $str_nextPage = $strings['nextPage'];
    } else {
        $str_prevPage = '&lt; Previous';
        $str_prevPageDiv = ' | ';
        
        $str_pagesDiv = ' . ';
        
        $str_nextPageDiv = ' | ';
        $str_nextPage = 'Next &gt;';
    }
    
    ##
    
    if ($itemsPerPage) {
        $pagesNum = ceil($itemsNum / $itemsPerPage);
    }
    
    if ($pagesNum < 2) {
        return false;
    }
    
    if ($page > 1) {
        $str_prevPage = '<a href="' . $url . '&page=' . ($page - 1) . '" class="prev">' . $str_prevPage . '</a>';
    }
    if ($page != $pagesNum) {
        $str_nextPage = '<a href="' . $url . '&page=' . ($page + 1) . '" class="next">' . $str_nextPage . '</a>';
    }
    
    $start = $page - floor($neighborsNum / 2);
    if ($start > $pagesNum - $neighborsNum + 1) {
        $start = $pagesNum - $neighborsNum + 1;
    }
    
    if ($start > 1) {
        $pages[] = '<a href="' . $url . '&page=1" class="number">1</a>';
        $pages[] = '...';
    }
    for ($i = 0, $cnt = $start; $i != $neighborsNum && $cnt <= $pagesNum; $i++, $cnt++) {
        if ($cnt <= 0) {
            $i--;
        } elseif ($cnt == $page) {
            $pages[] = '<span class="number">' . $cnt . '</span>';
        } else {
            $pages[] = '<a href="' . $url . '&page=' . $cnt . '" class="number">' . $cnt . '</a>';
        }
        $last = $cnt;
    }
    if ($last < $pagesNum) {
        $pages[] = '...';
        $pages[] = '<a href="' . $url . '&page=' . $pagesNum . '" class="number">' . $pagesNum . '</a>';
    }
    
    ob_start();
    
    echo '<div id="page-nav-bar">';
    echo     $str_prevPage . $str_prevPageDiv;
    echo     implode($str_pagesDiv, $pages);
    echo     $str_nextPageDiv . $str_nextPage;
    echo '</div>';
    
    $stdout = ob_get_contents();
    ob_end_clean();
    return $stdout;
}

// Used in administrational scripts.
function pageNavbar_adminList($page, $itemsNum, $itemsPerPage, $url, $neighborsNum = 7) {
    $strings['prevPage'] = '« prev.';
    $strings['prevPageDiv'] = '&nbsp;&nbsp;&nbsp;';
    
    $strings['pagesDiv'] = '';
    
    $strings['nextPageDiv'] = '&nbsp;&nbsp;&nbsp;';
    $strings['nextPage'] = 'next »';
    
    return pageNavbar($page, $itemsNum, $itemsPerPage, $url, $neighborsNum, $strings);
}

##

// Used in administrational scripts.
function getNonEmptyIterations($array) {
    for ($i = 0; $i != count($array); $i++) {
        if ($array[$i] && @array_unique(@array_values($array[$i])) != array('')) {
            $array_new[] = $array[$i];
        }
    }
    
    return $array_new;
}

##

function embedDocTypeParametersIntoImportExportMeta($docType, &$importExportMeta) {
    if ($params = getDocTypeParameters($docType)) {
        foreach ($params as $param) {
            $section = @mysql_result(mysql_query("SELECT title FROM sys_docs_types_params_sections WHERE doc_type = '$docType' AND section_id = '$param[section_id]'"), 0);
            
            if ($param['is_iteratable']) {
                if (count($param['data']) > 1) {
                    unset($tmp);
                    for ($i = 0; $i != count($param['data']); $i++) {
                        $tmp[] = array('db' => $param['data'][$i]['param_data_name'], 'caption' => '[' . $section . '] ' . $param['data'][$i]['caption'], 'type' => 'variable');
                    }
                    $importExportMeta[] = array('db' => 'parameters__' . $param['param_name'], 'type' => $tmp);
                } else {
                    $importExportMeta[] = array('db' => 'parameters__' . $param['param_name'], 'caption' => '[' . $section . '] ' . $param['data'][0]['caption'], 'type' => 'array');
                }
            } else {
                if (count($param['data']) > 1) {
                    for ($i = 0; $i != count($param['data']); $i++) {
                        $importExportMeta[] = array('db' => 'parameters__' . $param['param_name'] . '__' . $param['data'][$i]['param_data_name'], 'caption' => ($section ? $section . ' ' : false) . $param['data'][$i]['caption'], 'type' => 'variable');
                    }
                } else {
                    $importExportMeta[] = array('db' => 'parameters__' . $param['param_name'], 'caption' => $param['data'][0]['caption'], 'type' => 'variable');
                }
            }
        }
    }
}

// Used in administrational scripts.
function renderParameterSection($sectionTag, $section, $_FORM) {
    if (!$section['params'][0]) {
        return false;
    }

    ob_start();
    ?>
    <div class="section" id="section__<?=$sectionTag?>_param_section_<?=($section['section_id'] ? $section['section_id'] : '0')?>">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="note">parameters</div>
            <div class="caption"><?=strtolower($section['title'])?></div>
        </div>

        <? if ($section['description']): ?>
        <table class="section">
            <tr>
                <td class="col" colspan="3">
                    <div class="annotation" style="padding: 3px;"><?=$section['description']?></div>
                </td>
            </tr>
        </table>
        <? endif; ?>

        <?php
        $iteratable = $section['params'][0]['is_iteratable'];

        if ($iteratable && count($_FORM['parameters'][$section['params'][0]['param_name']]) > 0) {
            $cnt = max(array_keys($_FORM['parameters'][$section['params'][0]['param_name']])) + 1; // Get the iteration count by the latest iteration (in case there are some empty ones).
        } else {
            $cnt = 1;
        }
        ?>

        <? for ($i = 0; $i != $cnt; $i++): ?>

            <? if ($iteratable): ?>
            <div class="iteration" arrayname="parameters[<?=$section['params'][0]['param_name']?>][<?=$i?>]">
                <div class="iteration-header">
                    <div class="index" onclick="iteration_moveByIdx(this)">#<?=($i < 9 ? '0' . ($i+1) : $i+1)?></div>
                    <div class="actions">
                        <span onclick="iteration_addAfter(this)">add after</span>
                        | <span onclick="iteration_addBefore(this)">add before</span>
                        | <span onclick="iteration_moveUp(this)">move up</span>
                        | <span onclick="iteration_moveDown(this)">move down</span>
                        | <span onclick="iteration_clear(this)">clear</span>
                        | <span onclick="iteration_delete(this)">delete</span>
                    </div>
                </div>
            <? endif; ?>

            <table class="section">
                <? for ($p = 0; $p != count($section['params']); $p++):
                    $param = $section['params'][$p];

                    for ($d = 0; $d != count($param['data']); $d++):
                        if ($iteratable) {
                            if (count($param['data']) == 1) {
                                $name = 'parameters[' . $param['param_name'] . '][' . $i . ']';
                                $value = $_FORM['parameters'][$param['param_name']][$i];
                            } else {
                                $name = 'parameters[' . $param['param_name'] . '][' . $i . '][' . $param['data'][$d]['param_data_name'] . ']';
                                $value = $_FORM['parameters'][$param['param_name']][$i][$param['data'][$d]['param_data_name']];
                            }
                        } else {
                            if (count($param['data']) == 1) {
                                $name = 'parameters[' . $param['param_name'] . ']';
                                $value = $_FORM['parameters'][$param['param_name']];
                            } else {
                                $name = 'parameters[' . $param['param_name'] . '][' . $param['data'][$d]['param_data_name'] . ']';
                                $value = $_FORM['parameters'][$param['param_name']][$param['data'][$d]['param_data_name']];
                            }
                        }
                    ?>
                        <tr subtypes=" <?=@implode(' ', $param['subtypes'])?> ">
                            <td class="col a" title="<?=$param['param_name']?><?=($param['data'][$d]['param_data_name'] ? ':' . $param['data'][$d]['param_data_name'] : false)?>"><div class="caption"><?=strtolower($param['data'][$d]['caption'])?></div></td>
                            <? if ($param['data'][$d]['type'] == 'htmlarea'): ?>
                            <td class="col bc" colspan="2">
                            <? else : ?>
                            <td class="col b">
                            <? endif; ?>
                                <? if ($param['data'][$d]['type'] == 'text'): ?>
                                    <input type="text" name="<?=$name?>" maxlength="<?=$param['data'][$d]['type__text__maxlength']?>" class="text" value="<?=htmlspecialchars($value)?>" style="width: 350px;">
                                <? elseif ($param['data'][$d]['type'] == 'textarea'): ?>
                                    <textarea name="<?=$name?>" class="textarea" onfocus="this.style.height = '350px'" onblur="this.style.height = ''"><?=htmlspecialchars($value)?></textarea>
                                <? elseif ($param['data'][$d]['type'] == 'htmlarea'): ?>
                                    <textarea name="<?=$name?>" class="htmlarea"><?=htmlspecialchars($value)?></textarea>
                                <? elseif ($param['data'][$d]['type'] == 'checkbox'): ?>
                                    <input type="checkbox" name="<?=$name?>" value="1" <?=(($value == '1') ? 'checked' : false)?>>
                                <? elseif ($param['data'][$d]['type'] == 'select'): ?>
                                    <select name="<?=$name?>">
                                    <? foreach ($param['data'][$d]['options'] as $option): ?>
                                    <option value="<?=htmlspecialchars($option['value'])?>" <?=((($value && $value == $option['value']) || (!$value && $option['is_selected'])) ? 'selected' : false)?>><?=$option['text']?></option>
                                    <? endforeach; ?>
                                    </select>
                                <? elseif ($param['data'][$d]['type'] == 'radio'): ?>
                                    <? foreach ($param['data'][$d]['options'] as $option): ?>
                                    <label><input type="radio" name="<?=$name?>" class="radio" value="<?=htmlspecialchars($option['value'])?>" <?=((($value && $value == $option['value']) || (!$value && $option['is_selected'])) ? 'checked' : false)?>> <?=$option['text']?></label><br>
                                    <? endforeach; ?>
                                <? elseif ($param['data'][$d]['type'] == 'file'): ?>
                                    <input type="text" name="<?=$name?>" maxlength="255" class="text browse" value="<?=htmlspecialchars($value)?>">&nbsp;<button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="gyroFileManager(this.previousSibling.previousSibling.id = Date().replace(/\W/g, ''), this.previousSibling.previousSibling.value)" onfocus="this.blur()" style="border-color: #F6F6F6;"><img src="<?=href('/repository/admin/images/act_find.gif')?>">&nbsp;&nbsp;Browse</button>
                                <? endif; ?>
                            </td>
                            <? if ($param['data'][$d]['type'] != 'htmlarea'): ?>
                            <td class="col c"><div class="annotation"><?=$param['data'][$d]['annotation']?></div></td>
                            <? endif; ?>
                        </tr>
                    <? endfor; ?>
                <? endfor; ?>
            </table>

            <? if ($iteratable): ?>
            </div>
            <? endif; ?>

        <? endfor; ?>
    </div>
    <?
    $stdout = ob_get_clean();
    return $stdout;
}
// Used in administrational scripts.
function validateDocParameters($docType, $parameters, &$errors) {
    if ($parameters && ($params = getDocTypeParameters($docType))) {
        foreach ($params as $param) {
            if (isset($parameters[$param['param_name']])) {
                if ($param['is_iteratable']) {
                    if (count($param['data']) > 1) {
                        // Parameter kind: multiple-data-iterated.
                        
                        for ($i = 0; $i != count($parameters[$param['param_name']]); $i++) {
                            for ($d = 0; $d != count($param['data']); $d++) {
                                if ($parameters[$param['param_name']][$i][$param['data'][$d]['param_data_name']]) {
                                    unset($options);
                                    if ($param['data'][$d]['options']) {
                                        foreach ($param['data'][$d]['options'] as $option) {
                                            $options[] = $option['value'];
                                        }
                                    }
                                    
                                    if ($options && !in_array($parameters[$param['param_name']][$i][$param['data'][$d]['param_data_name']], $options)) {
                                        $errors['parameters[' . $param['param_name'] . '][' . $i . '][' . $param['data'][$d]['param_data_name'] . ']'] = 'Invalid parameter option [1].';
                                    }
                                }
                            }
                        }
                    } else {
                        // Parameter kind: single-data-iterated.
                        
                        for ($i = 0; $i != count($parameters[$param['param_name']]); $i++) {
                            if ($parameters[$param['param_name']][$i]) {
                                unset($options);
                                if ($param['data'][0]['options']) {
                                    foreach ($param['data'][0]['options'] as $option) {
                                        $options[] = $option['value'];
                                    }
                                }
                                
                                if ($options && !in_array($parameters[$param['param_name']][$i], $options)) {
                                    $errors['parameters[' . $param['param_name'] . '][' . $i . ']'] = 'Invalid parameter option [2].';
                                }
                            }
                        }
                    }
                } else {
                    if (count($param['data']) > 1) {
                        // Parameter kind: multiple-data.
                        
                        for ($d = 0; $d != count($param['data']); $d++) {
                            if ($parameters[$param['param_name']][$param['data'][$d]['param_data_name']]) {
                                unset($options);
                                if ($param['data'][$d]['options']) {
                                    foreach ($param['data'][$d]['options'] as $option) {
                                        $options[] = $option['value'];
                                    }
                                }
                                
                                if ($options && !in_array($parameters[$param['param_name']][$param['data'][$d]['param_data_name']], $options)) {
                                    $errors['parameters[' . $param['param_name'] . '][' . $param['data'][$d]['param_data_name'] . ']'] = 'Invalid parameter option [3].';
                                }
                            }
                        }
                    } else {
                        // Parameter kind: single-data.
                        
                        unset($options);
                        if (isset($param['data'][0]['options'])) {
                            foreach ($param['data'][0]['options'] as $option) {
                                $options[] = $option['value'];
                            }
                        }
                        
                        if (isset($options) && !in_array($parameters[$param['param_name']], $options)) {
                            $errors['parameters[' . $param['param_name'] . ']'] = 'Invalid parameter option [4].';
                        }
                    }
                }
            }
        }
    }
}

// Used in administrational scripts.
function saveDocParameters($docType, $docId, $parameters, &$sql_errors) {
    mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$docId'") || $sql_errors[] = mysql_error();
    
    if ($params = getDocTypeParameters($docType)) {
        foreach ($params as $param) {
            if ($parameters[$param['param_name']]) {
                if ($param['is_iteratable']) {
                    if (count($param['data']) > 1) {
                        // Parameter kind: multiple-data-iterated.
                        
                        for ($i = 0; $i != count($parameters[$param['param_name']]); $i++) {
                            for ($d = 0; $d != count($param['data']); $d++) {
                                if ($parameters[$param['param_name']][$i][$param['data'][$d]['param_data_name']]) {
                                    unset($options);
                                    if ($param['data'][$d]['options']) {
                                        foreach ($param['data'][$d]['options'] as $option) {
                                            $options[] = mysql_real_escape_string($option['value']);
                                        }
                                    }
                                    
                                    if (!$options || in_array($parameters[$param['param_name']][$i][$param['data'][$d]['param_data_name']], $options)) {
                                        $valueType = in_array($param['data'][$d]['type'], array('textarea', 'htmlarea')) ? 'value_long' : 'value_short';
                                        mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, $valueType) VALUES ('$docId', '" . $param['param_name'] . "', '" . $i . "', '" . $param['data'][$d]['param_data_name'] . "', '" . $parameters[$param['param_name']][$i][$param['data'][$d]['param_data_name']] . "')") || $sql_errors[] = mysql_error();
                                    }
                                }
                            }
                        }
                    } else {
                        // Parameter kind: multiple-data.
                        
                        for ($i = 0; $i != count($parameters[$param['param_name']]); $i++) {
                            if ($parameters[$param['param_name']][$i]) {
                                unset($options);
                                if ($param['data'][0]['options']) {
                                    foreach ($param['data'][0]['options'] as $option) {
                                        $options[] = mysql_real_escape_string($option['value']);
                                    }
                                }
                                
                                if (!$options || in_array($parameters[$param['param_name']][$i], $options)) {
                                    $valueType = in_array($param['data'][0]['type'], array('textarea', 'htmlarea')) ? 'value_long' : 'value_short';
                                    mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, $valueType) VALUES ('$docId', '" . $param['param_name'] . "', '" . $i . "', NULL, '" . $parameters[$param['param_name']][$i] . "')") || $sql_errors[] = mysql_error();
                                }
                            }
                        }
                    }
                } else {
                    if (count($param['data']) > 1) {
                        // Parameter kind: single-data-iterated.
                        
                        for ($d = 0; $d != count($param['data']); $d++) {
                            if ($parameters[$param['param_name']][$param['data'][$d]['param_data_name']]) {
                                unset($options);
                                if ($param['data'][$d]['options']) {
                                    foreach ($param['data'][$d]['options'] as $option) {
                                        $options[] = mysql_real_escape_string($option['value']);
                                    }
                                }
                                
                                if (!$options || in_array($parameters[$param['param_name']][$param['data'][$d]['param_data_name']], $options)) {
                                    $valueType = in_array($param['data'][$d]['type'], array('textarea', 'htmlarea')) ? 'value_long' : 'value_short';
                                    mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, $valueType) VALUES ('$docId', '" . $param['param_name'] . "', NULL, '" . $param['data'][$d]['param_data_name'] . "', '" . $parameters[$param['param_name']][$param['data'][$d]['param_data_name']] . "')") || $sql_errors[] = mysql_error();
                                }
                            }
                        }
                    } else {
                        // Parameter kind: single-data.
                        
                        unset($options);
                        if ($param['data'][0]['options']) {
                            foreach ($param['data'][0]['options'] as $option) {
                                $options[] = mysql_real_escape_string($option['value']);
                            }
                        }
                        
                        if (!$options || in_array($parameters[$param['param_name']], $options)) {
                            $valueType = in_array($param['data'][0]['type'], array('textarea', 'htmlarea')) ? 'value_long' : 'value_short';
                            mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, $valueType) VALUES ('$docId', '" . $param['param_name'] . "', NULL, NULL, '" . $parameters[$param['param_name']] . "')") || $sql_errors[] = mysql_error();
                        }
                    }
                }
            }
        }
    }
}

// Used when users update parameters (i.e. not used in the Admin).
// The function recieves the POST data (after it has been trimmed, and slashes and tags stripped).
// Finds every parameter in the POST data (pattern: "param__$param_name" or "param__$param_name__$param_data_name").
// Determines whether the parameters are defined, and if so deletes them.
// The new parameters' values are only inserted if they are valid (length, proper options when relevant, etc.).
function updateDocParameters($docType, $docId, $_POST_DB) {
    if ($_POST_DB) {
        $parameters = false;
        
        foreach ($_POST_DB as $var => $val) {
            if ($matches = @explode('__', $var)) {
                $param = false;
                
                $param['name'] = $matches[1];
                $param['value'] = $val;
                
                if ($sql = mysql_fetch_assoc(mysql_query("SELECT is_iteratable FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($param['name']) . "'"))) {
                    if (isset($matches[2]) && isset($matches[3]) && preg_match('/^\d+$/', $matches[2]) && $sql['is_iteratable']) {
                        // multiple-data-iterated
                        $param['iteration'] = $matches[2];
                        $param['data'] = $matches[3];
                    } elseif (isset($matches[2]) && !isset($matches[3]) && preg_match('/^\d+$/', $matches[2]) && $sql['is_iteratable']) {
                        // single-data-iterated
                        $param['iteration'] = $matches[2];
                    } elseif (isset($matches[2]) && !isset($matches[3]) && !$sql['is_iteratable']) {
                        // multiple-data
                        $param['data'] = $matches[2];
                    } else {
                        // single-data
                    }
                    
                    if ($tmp = mysql_fetch_assoc(mysql_query("SELECT type, type__text__maxlength FROM sys_docs_types_params_data WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($param['name']) . "' AND param_data_name " . ($param['data'] ? "= '" . mysql_real_escape_string($param['data']) . "'" : "IS NULL")))) {
                        // Determine whether the parameter value is valid.
                        $flag = true;
                        
                        $param['type'] = $tmp['type'];
                        $param['type__text__maxlength'] = $tmp['type__text__maxlength'];
                        
                        if (($param['type'] == 'select' || $param['type'] == 'radio') && !@mysql_result(mysql_query("SELECT '1' FROM sys_docs_types_params_data_options WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($param['name']) . "' AND param_data_name = '" . mysql_real_escape_string($param['data']) . "' AND param_data_option_value = '" . mysql_real_escape_string($param['value']) . "'"), 0)) {
                            $flag = false;
                        }
                        
                        if ($param['type'] == 'text' && $param['type__text__maxlength'] < strlen($param['value'])) {
                            $flag = false;
                        }
                        
                        if ($param['type'] == 'checkbox' && $param['value'] != '1' && $param['value'] != '') {
                            $flag = false;
                        }
                        
                        if ($flag) {
                            $paramTypes[$param['name']] = array('multiple' => ($param['data'] ? 1 : 0), 'iteratable' => (isset($param['iteration']) ? 1 : 0));
                            
                            if (isset($param['iteration']) && $param['data']) {
                                // multiple-data-iterated
                                $parameters[$param['name']][$param['iteration']][$param['data']] = array('type' => $param['type'], 'value' => $param['value']);
                            } elseif (isset($param['iteration']) && !$param['data']) {
                                // single-data-iterated
                                $parameters[$param['name']][$param['iteration']] = array('type' => $param['type'], 'value' => $param['value']);
                            } elseif (!isset($param['iteration']) && $param['data']) {
                                // multiple-data
                                $parameters[$param['name']][$param['data']] = array('type' => $param['type'], 'value' => $param['value']);
                            } elseif (!isset($param['iteration']) && !$param['data']) {
                                // single-data
                                $parameters[$param['name']] = array('type' => $param['type'], 'value' => $param['value']);
                            }
                        }
                    }
                }
            }
        }
        
        if ($parameters) {
            foreach ($parameters as $parameterName => $tmp) {
                $parameter['name'] = $parameterName;
                
                if ($paramTypes[$parameterName]['iteratable'] && $paramTypes[$parameterName]['multiple']) {
                    // multiple-data-iterated
                    
                    mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$docId' AND param_name = '" . mysql_real_escape_string($parameter['name']) . "'");
                    
                    $cnt = 0;
                    foreach ($tmp as $iteration => $tmp2) {
                        foreach ($tmp2 as $parameterDataName => $tmp3) {
                            $parameter['data'] = $parameterDataName;
                            $parameter['type'] = $tmp3['type'];
                            $parameter['value'] = $tmp3['value'];
                            
                            if ($parameter['value']) {
                                if ($parameter['type'] == 'textarea' || $parameter['type'] == 'htmlarea') {
                                    mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', '" . $cnt . "', '" . mysql_real_escape_string($parameter['data']) . "', NULL, '" . mysql_real_escape_string($parameter['value']) . "')");
                                } else {
                                    mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', '" . $cnt . "', '" . mysql_real_escape_string($parameter['data']) . "', '" . mysql_real_escape_string($parameter['value']) . "', NULL)");
                                }
                            }
                        }
                        $cnt++;
                    }
                } elseif ($paramTypes[$parameterName]['iteratable'] && !$paramTypes[$parameterName]['multiple']) {
                    // single-data-iterated
                    
                    mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$docId' AND param_name = '" . mysql_real_escape_string($parameter['name']) . "'");
                    
                    $cnt = 0;
                    foreach ($tmp as $iteration => $tmp2) {
                        $parameter['type'] = $tmp2['type'];
                        $parameter['value'] = $tmp2['value'];
                        
                        if ($parameter['value']) {
                            if ($parameter['type'] == 'textarea' || $parameter['type'] == 'htmlarea') {
                                mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', '" . $cnt . "', NULL, NULL, '" . mysql_real_escape_string($parameter['value']) . "')");
                            } else {
                                mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', '" . $cnt . "', NULL, '" . mysql_real_escape_string($parameter['value']) . "', NULL)");
                            }
                            
                            $cnt++;
                        }
                    }
                } elseif (!$paramTypes[$parameterName]['iteratable'] && $paramTypes[$parameterName]['multiple']) {
                    // multiple-data
                    
                    foreach ($tmp as $parameterDataName => $tmp2) {
                        $parameter['data'] = $parameterDataName;
                        $parameter['type'] = $tmp2['type'];
                        $parameter['value'] = $tmp2['value'];
                        
                        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$docId' AND param_name = '" . mysql_real_escape_string($parameter['name']) . "' AND param_data_name = '" . mysql_real_escape_string($parameter['data']) . "'");
                        
                        if ($parameter['value']) {
                            if ($parameter['type'] == 'textarea' || $parameter['type'] == 'htmlarea') {
                                mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', NULL, '" . mysql_real_escape_string($parameter['data']) . "', NULL, '" . mysql_real_escape_string($parameter['value']) . "')");
                            } else {
                                mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', NULL, '" . mysql_real_escape_string($parameter['data']) . "', '" . mysql_real_escape_string($parameter['value']) . "', NULL)");
                            }
                        }
                    }
                } elseif (!$paramTypes[$parameterName]['iteratable'] && !$paramTypes[$parameterName]['multiple']) {
                    // single-data
                    
                    $parameter['type'] = $tmp['type'];
                    $parameter['value'] = $tmp['value'];
                    
                    mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$docId' AND param_name = '" . mysql_real_escape_string($parameter['name']) . "'");
                    
                    if ($parameter['value']) {
                        if ($parameter['type'] == 'textarea' || $parameter['type'] == 'htmlarea') {
                            mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', NULL, NULL, NULL, '" . mysql_real_escape_string($parameter['value']) . "')");
                        } else {
                            mysql_query("INSERT INTO sys_docs_params (doc_id, param_name, iteration, param_data_name, value_short, value_long) VALUES ('$docId', '" . mysql_real_escape_string($parameter['name']) . "', NULL, NULL, '" . mysql_real_escape_string($parameter['value']) . "', NULL)");
                        }
                    }
                }
            }
        }
    }
}

##

function getGroups_byType($docSubtype, $parentGroupId = false, $level = 0, $memberzoneId = false, $groupHierarchy = false) {
    $SELECT = $memberzoneId ? "'" . $memberzoneId . "' AS memberzone_id" : "(SELECT memberzone_id FROM type_memberzone WHERE group_id = tg.group_id) AS memberzone_id";
    $WHERE  = $parentGroupId ? "parent_group_id = '$parentGroupId'" : "parent_group_id IS NULL";
    
    $sql_query = mysql_query("SELECT sd.doc_id AS id, sd.doc_name, group_id, parent_group_id, title, status, idx, $SELECT FROM sys_docs sd LEFT JOIN type_group tg ON doc_id = group_id WHERE doc_type = 'group' AND doc_subtype = '$docSubtype' AND $WHERE ORDER BY idx ASC, group_id DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['doc'] = $sql['doc_name'] ? $sql['doc_name'] : $sql['group_id'];
        $sql['level'] = $level;
        $groupHierarchy[] = $sql;
        
        $groupHierarchy = getGroups_byType($docSubtype, $sql['group_id'], $level + 1, $sql['memberzone_id'], $groupHierarchy);
    }
    
    return $groupHierarchy;
}

function getGroups_byParent($parentGroupId = false, $level = 0, $groupHierarchy = false) {
    if ($parentGroupId) {
        $sql_query = mysql_query("SELECT IFNULL((SELECT doc_name FROM sys_docs WHERE doc_id = group_id), group_id) AS doc, group_id, parent_group_id, title, status, idx, group_id AS id FROM type_group WHERE parent_group_id = '$parentGroupId' ORDER BY idx ASC, group_id DESC");
    } else {
        $sql_query = mysql_query("SELECT IFNULL((SELECT doc_name FROM sys_docs WHERE doc_id = group_id), group_id) AS doc, group_id, parent_group_id, title, status, idx, group_id AS id FROM type_group WHERE parent_group_id IS NULL ORDER BY idx ASC, group_id DESC");
    }
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['level'] = $level;
        $groupHierarchy[] = $sql;
        
        $groupHierarchy = getGroups_byParent($sql['group_id'], $level + 1, $groupHierarchy);
    }
    
    return $groupHierarchy;
}

function getGroups_complete() {
    static $flagFirst;
    static $groupHierarchy;
    
    if (!$flagFirst) {
        $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $groupHierarchy[$sql['doc_subtype']]['title'] = $sql['title'];
            $groupHierarchy[$sql['doc_subtype']]['groups'] = getGroups_byType($sql['doc_subtype']);
            
            for ($i = 0; $i != count($groupHierarchy[$sql['doc_subtype']]['groups']); $i++) {
                $group = $groupHierarchy[$sql['doc_subtype']]['groups'][$i];
                $groupHierarchy[$sql['doc_subtype']]['groups_assoc'][$group['group_id']] =& $groupHierarchy[$sql['doc_subtype']]['groups'][$i];
            }
        }
    }
    
    return $groupHierarchy;
}

##

function getGroupMemberzone($groupId) {
    do {
        if ($memberzoneId = @mysql_result(mysql_query("SELECT memberzone_id FROM type_memberzone WHERE group_id = '$groupId'"), 0)) {
            return $memberzoneId;
        }
        
        $groupId = @mysql_result(mysql_query("SELECT parent_group_id FROM type_group WHERE group_id = '$groupId' AND parent_group_id IS NOT NULL"), 0);
    } while ($groupId);
}

##

function coin($sum, $forceZero = false, $alternative = false) {
    // Define the coin vars locally.
    if ($alternative && defined('COIN_ALT_VARS__pattern')) {
        $pattern = constant('COIN_ALT_VARS__pattern');
        $show_zero = constant('COIN_ALT_VARS__show_zero');
        $show_zero_decimal = constant('COIN_ALT_VARS__show_zero_decimal');
        $decimal_symbol = constant('COIN_ALT_VARS__decimal_symbol');
        $digits_after_decimal = constant('COIN_ALT_VARS__digits_after_decimal');
        $digit_grouping_symbol = constant('COIN_ALT_VARS__digit_grouping_symbol');
        $positive_symbol = constant('COIN_ALT_VARS__positive_symbol');
        $negative_symbol = constant('COIN_ALT_VARS__negative_symbol');
    } elseif (defined('COIN_VARS__pattern')) {
        $pattern = constant('COIN_VARS__pattern');
        $show_zero = constant('COIN_VARS__show_zero');
        $show_zero_decimal = constant('COIN_VARS__show_zero_decimal');
        $decimal_symbol = constant('COIN_VARS__decimal_symbol');
        $digits_after_decimal = constant('COIN_VARS__digits_after_decimal');
        $digit_grouping_symbol = constant('COIN_VARS__digit_grouping_symbol');
        $positive_symbol = constant('COIN_VARS__positive_symbol');
        $negative_symbol = constant('COIN_VARS__negative_symbol');
    } else {
        abort('Coin vars not defined.', __FUNCTION__, __FILE__, __LINE__);
    }
    
    $str = $pattern;
    
    if ($sum == 0 && !$show_zero && !$forceZero) {
        return;
    }
    
    // Determine whether to show decimal digits if the number is whole.
    if ($sum == sprintf('%d', $sum) && !$show_zero_decimal) {
        $sum = sprintf('%d', $sum);
        $digits_after_decimal = 0;
    }
    
    // Adjust the negative & positive symbols.
    if ($sum < 0) {
        $str = str_replace('{NEG}', $negative_symbol, $str);
        $str = str_replace('{POS}', '', $str);
        $sum = abs($sum);
    } else {
        $str = str_replace('{POS}', $positive_symbol, $str);
        $str = str_replace('{NEG}', '', $str);
    }
    
    return str_replace('{SUM}', number_format($sum, $digits_after_decimal, $decimal_symbol, $digit_grouping_symbol), $str);
}

##

function searchDocs($docType, $constraints = false, $query = false, $expression = false, $sort = false) {
    if (!is_array($constraints)) {
        $constraints = array();
    }
    if (!is_array($query)) {
        $query = array();
    }
    if (!is_string($expression)) {
        $expression = '';
    }
    if (!is_array($sort)) {
        $sort = array();
    }
    
    $schema = array(
        'ad' => array(
            'table' => 'type_ad',
            'doc_id' => 'ad_id',
            'columns' => array('ad_id', 'title', 'content', 'expiration_date', 'authorized'),
            'limit_by_user' => 'user_id',
        ),
        'article' => array(
            'table' => 'type_article',
            'doc_id' => 'article_id',
            'columns' => array('article_id', 'title', 'subtitle', 'description_short', 'keywords'),
            'limit_by_user' => false,
        ),
        'element' => array(
            'table' => 'type_element',
            'doc_id' => 'element_id',
            'columns' => array('element_id', 'title'),
            'limit_by_user' => false,
        ),
        'auction' => array(
            'table' => 'type_auction',
            'doc_id' => 'auction_id',
            'columns' => array('auction_id', 'title', 'description_short', 'description', 'keywords', 'authorized', 'created', 'modified', 'locked', 'is_locked'),
            'limit_by_user' => 'user_id',
            'timestamps' => array('created', 'modified', 'locked'),
        ),
        'post' => array(
            'table' => 'type_post',
            'doc_id' => 'post_id',
            'columns' => array('post_id', 'type', 'title', 'description_short', 'description', 'authorized', 'created', 'modified'),
            'limit_by_user' => 'user_id',
            'timestamps' => array('created', 'modified'),
        ),
        'user' => array(
            'table' => 'type_user',
            'doc_id' => 'user_id',
            'columns' => array('user_id', 'type', 'email', 'first_name', 'last_name', 'phone_1', 'phone_2', 'company', 'job_title', 'city', 'country', 'registration', 'last_login', 'send_notifications', 'send_newsletters', 'credits'),
            'limit_by_user' => 'user_id',
            'timestamps' => array('registration', 'last_login'),
        ),
        'group' => array(
            'table' => 'type_group',
            'doc_id' => 'group_id',
            'columns' => array('group_id', 'parent_group_id', 'type', 'title', 'description', 'status', 'idx'),
            'limit_by_user' => false,
        ),
        'product' => array(
            'table' => 'type_product',
            'doc_id' => 'product_id',
            'columns' => array('product_id', 'title', 'catalog_number', 'price_actual', 'description_short', 'description', 'keywords', 'status', 'thumbnail_image_url', 'quantity'),
            'limit_by_user' => false,
        ),
        'talkback' => array(
            'table' => 'type_talkback',
            'doc_id' => 'talkback_id',
            'columns' => array('talkback_id', 'parent_doc_id', 'title', 'content', 'timestamp', 'authorized'),
            'limit_by_user' => 'user_id',
            'timestamps' => array('timestamp'),
        ),
        'review' => array(
            'table' => 'type_review',
            'doc_id' => 'review_id',
            'columns' => array('review_id', 'parent_doc_id', 'title', 'content', 'timestamp', 'authorized'),
            'limit_by_user' => 'user_id',
            'timestamps' => array('timestamp'),
        ),
        'verifone-location' => array(
            'table' => 'type_vf_location',
            'doc_id' => 'vf_location_id',
            'columns' => array('vf_location_id', 'name', 'address', 'updated', 'authorized'),
            'limit_by_user' => false,
            'timestamps' => array('updated'),
        ),
        'newsletter' => array(
            'table' => 'type_newsletter',
            'doc_id' => 'newsletter_id',
            'columns' => array('newsletter_id', 'title', 'content', 'subject', 'sent', 'completed'),
            'limit_by_user' => false,
            'timestamps' => array('sent', 'completed'),
        ),
    );
    
    ##
    
    // If $docType is an array, assume the first value is the doc-type and the second is the doc-subtype.
    // Implement support for doc-subtype restriction (internally) via `constraints`.
    if (is_array($docType)) {
        $subtype = $docType[1];
        $docType = $docType[0];
        
        if (is_array($subtype)) {
            foreach ($subtype as &$tmp) {
                $tmp = "'" . mysql_real_escape_string($tmp) . "'";
            }
            $WHERE_constraints[] = "sd.doc_subtype IN (" . implode(",", $subtype) . ")";
        } else {
            $WHERE_constraints[] = "sd.doc_subtype = '" . mysql_real_escape_string($subtype) . "'";
        }
    }
    
    $expression = (strtolower($expression) == 'and' || strtolower($expression) == 'or') ? strtolower($expression) : 'and';
    
    ##
    
    // Define the users constraints (for doc types that have it).
    if ($constraints['users'] && $schema[$docType]['limit_by_user']) {
        for ($i = 0; $i != count($constraints['users']); $i++) {
            if (substr($constraints['users'][$i], 0, 1) != '-') {
                $constraints_users_pos[] = "'" . mysql_real_escape_string($constraints['users'][$i]) . "'";
            } else {
                $constraints_users_neg[] = "'" . mysql_real_escape_string(substr($constraints['users'][$i], 1)) . "'";
            }
        }
        if ($constraints_users_pos) {
            $WHERE_constraints[] = "t." . $schema[$docType]['limit_by_user'] . " IN (" . implode(",", $constraints_users_pos) . ")";
        }
        if ($constraints_users_neg) {
            $WHERE_constraints[] = "t." . $schema[$docType]['limit_by_user'] . " NOT IN (" . implode(",", $constraints_users_neg) . ")";
        }
    }
    
    // Define the groups constraints.
    if ($constraints['groups']) {
        for ($i = 0; $i != count($constraints['groups']); $i++) {
            if (substr($constraints['groups'][$i], 0, 1) != '-') {
                $constraints_groups_pos[] = "'" . mysql_real_escape_string($constraints['groups'][$i]) . "'";
            } else {
                $constraints_groups_neg[] = "'" . mysql_real_escape_string(substr($constraints['groups'][$i], 1)) . "'";
            }
        }
        // Account for the fact that some doc-types (group, review, talkback) are not associated with groups like other doc-types.
        if ($docType == 'group') {
            if ($constraints_groups_pos) {
                $WHERE_constraints[] = "t.parent_group_id IN (" . implode(",", $constraints_groups_pos) . ")";
            }
            if ($constraints_groups_neg) {
                $WHERE_constraints[] = "(t.parent_group_id NOT IN (" . implode(",", $constraints_groups_neg) . ") OR t.parent_group_id IS NULL)";
            }
        } elseif ($docType == 'review' || $docType == 'talkback') {
            if ($constraints_groups_pos) {
                $JOIN[] = "LEFT JOIN type_group ON (t.parent_doc_id = group_id)";
                $WHERE_constraints[] = "group_id IN (" . implode(",", $constraints_groups_pos) . ")";
            }
            if ($constraints_groups_neg) {
                $JOIN[] = "LEFT JOIN type_group ON (t.parent_doc_id = group_id)";
                $WHERE_constraints[] = "(group_id NOT IN (" . implode(",", $constraints_groups_neg) . ") OR group_id IS NULL)";
            }
        } else {
            if ($constraints_groups_pos) {
                $JOIN[] = "LEFT JOIN type_group__associated_docs ON (t." . $schema[$docType]['doc_id'] . " = associated_doc_id)";
                $WHERE_constraints[] = "group_id IN (" . implode(",", $constraints_groups_pos) . ")";
            }
            if ($constraints_groups_neg) {
                $JOIN[] = "LEFT JOIN type_group__associated_docs ON (t." . $schema[$docType]['doc_id'] . " = associated_doc_id)";
                $WHERE_constraints[] = "(group_id NOT IN (" . implode(",", $constraints_groups_neg) . ") OR group_id IS NULL)";
            }
        }
    }
    
    ##
    
    if ($query) {
        for ($i = 0; $i != count($query); $i++) {
            $conditions = false;
            
            $columns = $query[$i][0];
            $keywords = array_unique(explode(' ', $query[$i][1]));
            $expression_sub = in_array(strtolower($query[$i][2]), array('and', 'or', 'quote', '-quote', 'exact', '-exact', 'range', 'max', 'min')) ? strtolower($query[$i][2]) : 'exact';
            
            if ($keywords) {
                // Move the lookup expression into the keywords, so as to allow special cases such as 'range'.
                if ($expression_sub == 'quote' || $expression_sub == '-quote') {
                    $keywords = array("LIKE '%" . mysql_real_escape_string($query[$i][1]) . "%'");
                } elseif ($expression_sub == 'exact' || $expression_sub == '-exact') {
                    $keywords = array("LIKE '" . mysql_real_escape_string($query[$i][1]) . "'"); // Using "LIKE" insetead of "=" to allow later negation with "NOT" (done with `$expression_sub_neg`).
                } elseif ($expression_sub == 'range' && count($keywords) == 1 && preg_match('/^[\d\.]+\-[\d\.]+$/', $keywords[0])) {
                    list($min, $max) = explode('-', $keywords[0]);
                    $keywords = array("BETWEEN " . mysql_real_escape_string($min) . " AND " . mysql_real_escape_string($max));
                } elseif ($expression_sub == 'min' && count($keywords) == 1 && (preg_match('/^[\d\.]+$/', $keywords[0]) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $keywords[0]) || preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $keywords[0]))) {
                    if (preg_match('/^[\d\.]+$/', $keywords[0])) {
                        $keywords = array(">= " . mysql_real_escape_string($keywords[0]));
                    } else {
                        $keywords = array(">= '" . mysql_real_escape_string($keywords[0]) . "'");
                    }
                } elseif ($expression_sub == 'max' && count($keywords) == 1 && (preg_match('/^[\d\.]+$/', $keywords[0]) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $keywords[0]) || preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $keywords[0]))) {
                    if (preg_match('/^[\d\.]+$/', $keywords[0])) {
                        $keywords = array("<= " . mysql_real_escape_string($keywords[0]));
                    } else {
                        $keywords = array("<= '" . mysql_real_escape_string($keywords[0]) . "'");
                    }
                } else {
                    for ($j = 0; $j != count($keywords); $j++) {
                        $keywords[$j] = "LIKE '%" . mysql_real_escape_string($keywords[$j]) . "%'";
                    }
                }
                
                // Used with "-quote" and "-exact" to negate outside the parameter scope. This essentially allows to search for "if the parameter is not equal/exact X", with the added feature of also matching when the parameter doesn't exist at all.
                $expression_sub_neg = substr($expression_sub, 0, 1) == '-' ? true : false;
                
                if ($columns == '' || $columns == array()) {
                    foreach ($keywords as $keyword) {
                        foreach ($schema[$docType]['columns'] as $column) {
                            // If column is a defined as a timestamp but the keyword is not a MySQL timestamp, replace it in the query with a UNIX_TIMESTAMP.
                            if ($schema[$docType]['timestamps'] && in_array($column, $schema[$docType]['timestamps']) && !preg_match('/\'\d{4}-\d{2}-\d{2}\'$/', $keyword) && !preg_match('/\'\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\'$/', $keyword)) {
                                $conditions[] = "UNIX_TIMESTAMP(t." . $column . ") " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                            } else {
                                $conditions[] = "t." . $column . " " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                            }
                        }
                        $conditions[] = "t." . $schema[$docType]['doc_id'] . " " . ($expression_sub_neg ? "NOT" : false) . " IN (SELECT DISTINCT doc_id FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND IFNULL(value_short, value_long) " . $keyword . ")";
                        
                        if ($conditions) {
                            $WHERE_columns[] = "(" . @implode(" OR ", $conditions) . ")";
                        }    
                    }
                } elseif (is_array($columns)) {
                    if ($expression_sub == 'or') {
                        foreach ($keywords as $keyword) {
                            foreach ($columns as $column) {
                                if ($column) {
                                    if (in_array(strtolower($column), $schema[$docType]['columns'])) {
                                        /* deprecated */
                                        if ($column == 'type') {
                                            $conditions[] = "sd.doc_subtype " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                        } else {
                                            // If column is a defined as a timestamp but the keyword is not a MySQL timestamp, replace it in the query with a UNIX_TIMESTAMP.
                                            if ($schema[$docType]['timestamps'] && in_array($column, $schema[$docType]['timestamps']) && !preg_match('/\'\d{4}-\d{2}-\d{2}\'$/', $keyword) && !preg_match('/\'\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\'$/', $keyword)) {
                                                $conditions[] = "UNIX_TIMESTAMP(t." . $column . ") " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                            } else {
                                                $conditions[] = "t." . $column . " " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                            }
                                        }
                                    } else {
                                        if (strpos($column, ':')) {
                                            list($paramName, $paramDataName) = explode(':', $column);
                                            $conditions[] = "t." . $schema[$docType]['doc_id'] . " " . ($expression_sub_neg ? "NOT" : false) . " IN (SELECT DISTINCT doc_id FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' AND param_data_name = '" . mysql_real_escape_string($paramDataName) . "' AND IFNULL(value_short, value_long) " . $keyword . ")";
                                        } else {
                                            $paramName = $column;
                                            $conditions[] = "t." . $schema[$docType]['doc_id'] . " " . ($expression_sub_neg ? "NOT" : false) . " IN (SELECT DISTINCT doc_id FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' AND IFNULL(value_short, value_long) " . $keyword . ")";
                                        }
                                    }
                                }
                            }
                        }
                        
                        if ($conditions) {
                            $WHERE_columns[] = "(" . @implode(" OR ", $conditions) . ")";
                        }
                    } else {
                        foreach ($keywords as $keyword) {
                            $conditions_sub = false;
                            
                            foreach ($columns as $column) {
                                if ($column) {
                                    if (in_array(strtolower($column), $schema[$docType]['columns'])) {
                                        /* deprecated */
                                        if ($column == 'type') {
                                            $conditions_sub[] = "sd.doc_subtype " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                        } else {
                                            // If column is a defined as a timestamp but the keyword is not a MySQL timestamp, replace it in the query with a UNIX_TIMESTAMP.
                                            if ($schema[$docType]['timestamps'] && in_array($column, $schema[$docType]['timestamps']) && !preg_match('/\'\d{4}-\d{2}-\d{2}\'$/', $keyword) && !preg_match('/\'\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\'$/', $keyword)) {
                                                $conditions_sub[] = "UNIX_TIMESTAMP(t." . $column . ") " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                            } else {
                                                $conditions_sub[] = "t." . $column . " " . ($expression_sub_neg ? "NOT" : false) . " " . $keyword;
                                            }
                                        }
                                    } else {
                                        if (strpos($column, ':')) {
                                            list($paramName, $paramDataName) = explode(':', $column);
                                            $conditions_sub[] = "t." . $schema[$docType]['doc_id'] . " " . ($expression_sub_neg ? "NOT" : false) . " IN (SELECT DISTINCT doc_id FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' AND param_data_name = '" . mysql_real_escape_string($paramDataName) . "' AND IFNULL(value_short, value_long) " . $keyword . ")";
                                        } else {
                                            $paramName = $column;
                                            $conditions_sub[] = "t." . $schema[$docType]['doc_id'] . " " . ($expression_sub_neg ? "NOT" : false) . " IN (SELECT DISTINCT doc_id FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' AND IFNULL(value_short, value_long) " . $keyword . ")";
                                        }
                                    }
                                }
                            }
                            
                            if ($conditions_sub) {
                                $conditions[] = "(" . @implode(" OR ", $conditions_sub) . ")";
                            }    
                        }
                        
                        if ($conditions) {
                            $WHERE_columns[] = "(" . @implode(" AND ", $conditions) . ")";
                        }    
                    }
                }
            }    
        }
    }
    
    ##
    
    $WHERE[] = "1";
    if ($WHERE_constraints) {
        $WHERE[] = implode(" AND ", $WHERE_constraints);
    }
    if ($WHERE_columns) {
        if ($expression == 'or') {
            $WHERE[] = "(" . @implode(" OR ", $WHERE_columns) . ")";
        } else {
            $WHERE[] = "(" . @implode(" AND ", $WHERE_columns) . ")";
        }
    }
    $WHERE = implode(" AND ", $WHERE);
    
    ##
    
    if ($sort) {
        for ($i = 0; $i != count($sort); $i++) {
            // $sort[$i][0] - variable/parameter
            // $sort[$i][1] - order (asc/desc)
            // $sort[$i][2] - natural sorting flag (for numerals)
            
            $sort[$i][1] = (strtolower($sort[$i][1]) == 'desc') ? "DESC" : "ASC";
            
            if ($sort[$i][2]) {
                $sort[$i][1] = '+0 ' . $sort[$i][1];
            }
            
            if ($sort[$i][0] == 'rand') {
                $ORDERBY[] = "RAND()";
            } elseif (in_array($sort[$i][0], $schema[$docType]['columns'])) {
                $ORDERBY[] = mysql_real_escape_string($sort[$i][0]) . " " . $sort[$i][1];
            } elseif ($sort[$i][0] == 'idx' && $constraints['groups'] && $docType != 'review' && $docType != 'talkback') {
                $ORDERBY[] = "idx " . $sort[$i][1];
            } elseif (strpos($sort[$i][0], ':')) {
                list($paramName, $paramDataName) = explode(':', $sort[$i][0]);
                $SELECT[] = "(SELECT IFNULL(value_short, value_long) AS parameter FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' AND param_data_name = '" . mysql_real_escape_string($paramDataName) . "' LIMIT 1) AS p_sort_$i";
                $ORDERBY[] = "p_sort_$i " . $sort[$i][1];
            } else {
                $paramName = $sort[$i][0];
                $SELECT[] = "(SELECT IFNULL(value_short, value_long) AS parameter FROM sys_docs_params WHERE doc_id = t." . $schema[$docType]['doc_id'] . " AND param_name = '" . mysql_real_escape_string($paramName) . "' LIMIT 1) AS p_sort_$i";
                $ORDERBY[] = "p_sort_$i " . $sort[$i][1];
            }
        }
        $ORDERBY = "ORDER BY " . implode(", ", $ORDERBY);
    }
    
    ##
    
    $SQL = "SELECT t." . $schema[$docType]['doc_id'] . ($SELECT ? ", " . implode(", ", $SELECT) : false) . " FROM sys_docs sd LEFT JOIN " . $schema[$docType]['table'] . " t ON doc_id = " . $schema[$docType]['doc_id'] . ($JOIN ? " " . implode(" ", $JOIN) : false) . " WHERE doc_type = '" . $docType . "' AND $WHERE $ORDERBY";
    
    /*
    if ($_SESSION['user']['user_id'] == 500) {
        echo '<div style="font-family: Courier New; font-size: small;">';
        echo $SQL;
        echo '</div>';
    }
    //*/
    
    $sql_query = mysql_query($SQL);
    /*
    if ($_SESSION['user']['user_id'] == 500 && mysql_error()) {
        echo '<span style="color: red;">' . mysql_error() . '</span>';
    }
    //*/
    while ($sql = mysql_fetch_array($sql_query)) {
        $results[] = $sql[0];
    }
    
    if ($results) {
        $results = array_unique($results);
        $results = array_values($results);
        
        return $results;
    } else {
        return false;
    }
}

##

function dateFromStd($date) {
    $pattern = constant('DATE_VARS__pattern');
    $pattern = preg_replace('/{YEAR}/', constant('DATE_VARS__full_year') ? 'Y' : 'y', $pattern);
    $pattern = preg_replace('/{MONTH}/', constant('DATE_VARS__month_leading_zero') ? 'm' : 'n', $pattern);
    $pattern = preg_replace('/{DAY}/', constant('DATE_VARS__day_leading_zero') ? 'd' : 'j', $pattern);
    
    return date($pattern, mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4)));
}

function dateToStd($date) {
    $pattern = constant('DATE_VARS__pattern');
    $pattern = preg_replace('/{YEAR}/', '(?P<y>\d{2,4})', $pattern);
    $pattern = preg_replace('/{MONTH}/', '(?P<m>\d{1,2})', $pattern);
    $pattern = preg_replace('/{DAY}/', '(?P<d>\d{1,2})', $pattern);
    
    if (preg_match('{^' . $pattern . '$}', $date, $match)) {
        return date('Y-m-d', mktime(0, 0, 0, $match['m'], $match['d'], $match['y']));
    } else {
        return false;
    }
}

##

function calculateFormula($formula, $variables = false, $validate = false, &$error = false) {
    // Replace all variables with their values.
    if ($variables) {
        if ($validate) {
            foreach ($variables as $name) {
                $formula = preg_replace('/' . $name . '/', ' 0 ', $formula); // Added spaces so as to catch stuff like "VAR.5".
            }
        } else {
            foreach ($variables as $name => $value) {
                $formula = preg_replace('/' . $name . '/', ' ' . $value . ' ', $formula);
            }
        }
    }
    
    $formula = preg_replace('/x/i', '*', $formula); // Replace 'x' and 'X' with '*'.
    $formula = preg_replace("/[\r\n]+/", ' ', $formula); // Replace all superfluous newlines with spaces.
    $formula = preg_replace('/\s+/', ' ', $formula); // Replace all superfluous spaces.
    $formula = trim($formula);
    
    // Check that there is a formula
    if (strlen($formula) == 0) {
        $error = 'the formula is empty';
        return false;
    }
    
    // Check that there is a formula and that there are no illegal characters in it.
    if (!preg_match('/^[\d\.\)\(\s\+\-\/\*\<\>\=\&\|]+$/', $formula)) {
        $error = 'the formula contains invalid characters';
        return false;
    }
    
    // Evaluate the formula.
        ob_start();
        eval('?><?=' . $formula . '?>');
        $outcome = ob_get_contents();
        ob_end_clean();
    
    if (preg_match('/^[\d\.\-]+$/', $outcome)) {
        $outcome = round($outcome, 2);
    } else {
        $error = 'the formula cannot be evaluated';
        //$error .= ': <u>' . preg_replace('/([\+\-\*\/\<\>\=\&\|]+)/', ' $1 ', preg_replace('/\s/', '', $formula)) . '</u>';
        return false;
    }
    
    return $validate ? true : $outcome;
}

##

function getAffiliateProfitFormula($affiliateId, $price) {
    // If the affiliate is associated with a program, calculate the profit based on that.
    // Otherwise, use the affiliate's formulae to calculate the profit.
    $affiliateProgramId = @mysql_result(mysql_query("SELECT program_id FROM type_affiliate WHERE affiliate_id = '$affiliateId'"), 0);
    if (is_null($affiliateProgramId)) {
        // Select the default formula.
        $formula = @mysql_result(mysql_query("SELECT formula FROM type_affiliate__affiliates_formulae WHERE affiliate_id = '$affiliateId' AND `limit` IS NULL"), 0);
        
        // Check whether other formulae are more appropriate due to their limit (the closest higher value than that of $price).
        $sql_query = mysql_query("SELECT `limit`, formula FROM type_affiliate__affiliates_formulae WHERE affiliate_id = '$affiliateId' AND `limit` IS NOT NULL ORDER BY `limit` DESC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            if ($price <= $sql['limit']) {
                $formula = $sql['formula'];
            }
        }
    } else {
        // Select the default formula.
        $formula = @mysql_result(mysql_query("SELECT formula FROM type_affiliate__programs_formulae WHERE program_id = '$affiliateProgramId' AND `limit` IS NULL"), 0);
        
        // Check whether other formulae are more appropriate due to their limit (the closest higher value than that of $price).
        $sql_query = mysql_query("SELECT `limit`, formula FROM type_affiliate__programs_formulae WHERE program_id = '$affiliateProgramId' AND `limit` IS NOT NULL ORDER BY `limit` DESC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            if ($price <= $sql['limit']) {
                $formula = $sql['formula'];
            }
        }
    }
    
    return $formula;
}

##

function sendSMS($recipient, $message, $sender = false) {
    return sendSMS_internal($sender ? $sender : constant('SMS_SENDER'), $recipient, $message, $sendSMS_internal__error);
}

function sendSMS_internal($sender, $recipient, $message, &$error) {
    $result = file_get_contents('https://secure.pnc.co.il/sms/', false, stream_context_create(array(
        'http' => array (
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
            'content' => http_build_query(array(
                'username' => constant('SMS_USERNAME'),
                'password' => constant('SMS_PASSWORD'),
                'sender' => $sender,
                'recipient' => '+' . $recipient,
                'message' => $message,
                'associated-ip' => $_SERVER['REMOTE_ADDR']
            ))
        )
    )));
    
    list($result_sucess, $result_note) = @explode("\n", $result);
    
    if ($result_sucess == 'OKAY') {
        return true;
    } else {
        $error = $result_note;
        return false;
    }
}

##

function XMLExport($filename, $meta, $data) {
    // Create an empty template of the item with all the columns specified by the metadata, to later merge each item with tthe template, so that even if some coulmns are mising from the actuall data, they appear in the XML file.
    $template = XMLExport_itemTemplate($meta);
    
    // Merge every item with the meta template.
    for ($i = 0; $i != count($data); $i++) {
        $data[$i] = array_merge($template, $data[$i]);
    }
    
    // Transfer the items into a matrix, based on the metadata.
    $dataMatrix = XMLExport_dataToMatrix($meta, $data, $captions);
    
    // Prepare an empty matrix (the xml function requires that empty cells be defined).
    $matrix = array_fill(0, count($dataMatrix), array_fill(0, count($captions), false));
    
    // Fill in the data values into the prepared matrix.
    for ($i = 0; $i != count($dataMatrix); $i++) {
        for ($j = 0; $j != count($captions); $j++) {
            $matrix[$i][$j] = $dataMatrix[$i][$j];
        }
    }
    
    // Prepend the captions row.
    array_unshift($matrix, $captions);
    
    xml_create($filename, $matrix);
}

// Recursively build a matrix for all items.
// Level one handles the items (docs); level two is a nested-array (e.g. 'links'); level three is a second-level nested-array (e.g. product attribute options).
// Note: every item must have an identical data structure, even when some variables have no values (otherwise data can end up in the wrong columns).
// Note: presently there's an issue handling a simple-array inside a nested-array [this my be solved by turning simple-arrays into real (nested) arrays; this would require some touches in the admin].
function XMLExport_dataToMatrix($meta, $data, &$captions) {
    $r = 0; // The current row in the matrix.
    
    foreach ($data as $item) {
        $c = 0;
        
        foreach ($item as $cName => $cValue) {
            $mEntry = XMLExport_metaEntry($meta, $cName);
            
            if ($mEntry['type'] == 'variable') {
                $items[$r][$c] = $cValue;
                $captions[$c] = $mEntry['caption'];
                $c++;
            } elseif ($mEntry['type'] == 'array') {
                for ($i = 0; $i != count($cValue); $i++) {
                    if ($i > 0) {
                        //$items[$r + $i][0] = '#';
                        $items[$r + $i][0] = $items[$r][0];
                    }
                    $items[$r + $i][$c] = $cValue[$i];
                }
                $captions[$c] = $mEntry['caption'];
                $c++;
            } elseif (is_array($mEntry['type'])) {
                $subCaptions = false;
                $subData = XMLExport_dataToMatrix($mEntry['type'], $cValue, $subCaptions);
                
                $cMax = 0;
                for ($i = 0; $i != count($subData); $i++) {
                    if ($i > 0) {
                        //$items[$r + $i][0] = '#';
                        $items[$r + $i][0] = $items[$r][0];
                    }
                    
                    for ($j = 0; $j != count($subData[$i]); $j++) {
                        $items[$r + $i][$c + $j] = $subData[$i][$j];
                        
                        $captions[$c + $j] = $subCaptions[$j];
                    }
                    
                    if ($cMax < $c + count($subData[$i])) {
                        $cMax = $c + count($subData[$i]);
                    }
                }
                $c = $cMax;
            }
        }
        
        $r = count($items); // Before moving to the next item, update the row-index to the last row.
    }
    
    return $items;
}

// $meta is a subset of $importExportMeta, so no recursion is necessary.
function XMLExport_metaEntry($meta, $name) {
    for ($i = 0; $i != count($meta); $i++) {
        if ($meta[$i]['db'] == $name) {
            return $meta[$i];
        }
    }
}

// Recursively removes all empty but defined keys from an array.
function XMLExport_itemTemplate($meta) {
    for ($i = 0; $i != count($meta); $i++) {
        if ($meta[$i]['type'] == 'variable') {
            $array[$meta[$i]['db']] = false;
        } elseif ($meta[$i]['type'] == 'array') {
            $array[$meta[$i]['db']][] = false;
        } elseif (is_array($meta[$i]['type'])) {
            $array[$meta[$i]['db']][] = XMLExport_itemTemplate($meta[$i]['type']);
        }
    }
    
    return $array;
}

function xml_create($filename, $data) {
    header('Content-type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . $filename . '.xml');
    
    echo '<?xml version="1.0"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    echo ' <Worksheet ss:Name="' . $filename . '">' . "\n";
    echo '  <Table>' . "\n";
    
    for ($i = 0; $i != count($data); $i++) {
        echo '   <Row>' . "\n";
        for ($j = 0, $skipped = false; list($key, $value) = each($data[$i]); $j++) {
            $value = xmlentities($value);
            $value = str_replace("\n", '&#10;', str_replace("\r", '', $value)); // Excel's newline in XML.
            
            if ($value || $value == '0') {
                if ($skipped) {
                    echo '    <Cell ss:Index="' . ($j + 1) . '"><Data ss:Type="String">' . $value . '</Data></Cell>' . "\n";
                } else {
                    echo '    <Cell><Data ss:Type="String">' . $value . '</Data></Cell>' . "\n";
                }
                
                $skipped = false;
            } else {
                $skipped = true;
            }
        }
        echo '   </Row>' . "\n";
    }
    
    echo '  </Table>' . "\n";
    echo ' </Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    
    exit;
}

function XMLImport($uploadedFile, $docIdVar, $importExportMeta, $validateDocFunction, $saveDocFunction, $deleteDocFunction, $ignoreErrors = false) {
    if ($uploadedFile && $uploadedFile['error'] == 0) {
        if (in_array($uploadedFile['type'], array('text/xml', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-xml'))) {
            // Read the XML file into a matrix, preserve empty rows.
            if ($matrix = xml_read($uploadedFile['tmp_name'], $xml_read__error)) {
                $columns = array_flip(array_shift($matrix)); // An array that allows to get the column index by column caption.
                
                $data = XMLImport_matrixToData($importExportMeta, $columns, 0, $matrix);
                
                ##
                
                if ($data) {
                    mysql_query("BEGIN");
                    
                    foreach ($data as $item) {
                        if ($item) {
                            // Check whether the item has only its doc-id defined. If so, delete the item.
                            $tmp = XMLImport_cleanArray($item);
                            unset($tmp['row']);
                            $delete = ($tmp == array($docIdVar => $item[$docIdVar]));
                            
                            if ($delete) {
                                $deleteDoc_error = false;
                                
                                if (!call_user_func_array($deleteDocFunction, array($item[$docIdVar], &$deleteDoc_error))) {
                                    $import_errors[] = '[row: ' . $item['row'] . '] ' . $deleteDoc_error;
                                }
                            } else {
                                $validateDoc_errors = false;
                                
                                if (call_user_func_array($validateDocFunction, array($item[$docIdVar], &$item, true, &$validateDoc_errors))) {
                                    $saveDoc_error = false;
                                    
                                    recursive('mysql_real_escape_string', $item);
                                    if (!call_user_func_array($saveDocFunction, array($item[$docIdVar], $item, true, &$saveDoc_error))) {
                                        $import_errors[] = '[row: ' . $item['row'] . '] ' . $saveDoc_error;
                                    }
                                } elseif ($validateDoc_errors) {
                                    foreach ($validateDoc_errors as $variable => $error) {
                                        $import_errors[] = '[row: ' . $item['row'] . '] ' . $error;
                                    }
                                }
                            }
                        }
                    }
                    
                    mysql_query($import_errors && !$ignoreErrors ? "ROLLBACK" : "COMMIT");
                } else {
                    $import_errors[] = 'Error parsing XML file.';
                }
            } else {
                $import_errors[] = 'Error opening XML file (' . $xml_read__error . ').';
            }
        } else {
            $import_errors[] = 'Error uploading file: invalid file type (' . $uploadedFile['type'] . ').';
        }
    } else {
        $import_errors[] = 'Error uploading file.';
    }
    
    if ($import_errors) {
        $tmp = $ignoreErrors ? '<b>The following errors were <i>ignored</i>. All rows listed below were <i>not</i> imported.</b><br><br>' : false;
        abort($tmp . implode('<br>', $import_errors), false, __FILE__, __LINE__);
    } else {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/' . constant('DOC'))));
        exit;
    }
}

// Recursively build the data from the matrix.
// $meta is the metadata relevant to the present nesting level.
// $columns is an associative array that links between a meta entry caption to the proper column in the matrix.
// $cM is the column number in which the extended row marker ('#') appears in the present data set ('0' for the list of items; the column storing attributes-names, for the list of attributes-options).
// Note: instead of the extended row marker ('#'), there could also be a repetition of the string in the previous row (same doc-id, same attribute name, etc.). This is useful for sorting in spreadsheets, and it the new default XML export behavior.
function XMLImport_matrixToData($meta, $columns, $cM, $matrix) {
    // Determine where each item of the current data set begins and ends.
    for ($i = 0; $i != count($matrix); $i++) {
        $item = array($matrix[$i]);
        $rows = 1;
        
        // Nested data is identified based on the presense of the hash sign, or when the value (must not be empty for `$cM == 0` - indicating a new item) in the column is the same as the one in the previous row (e.g. same doc-id [only for existing items]).
        while ($matrix[$i + $rows][$cM] == '#' || ($matrix[$i + $rows][$cM] != '' && $matrix[$i + $rows][$cM] == $matrix[$i + $rows - 1][$cM])) {
            $item[] = $matrix[$i + $rows];
            
            $rows++;
        }
        $items[] = $item;
        
        $i += $rows - 1;
    }
    
    for ($x = 0; $x != count($items); $x++) {
        // The row of the item in the XML file is relevant only for items, not for nested data).
        if ($cM == 0) {
            $data[$x]['row'] = $items[$x][0]['row'];
        }
        
        for ($i = 0; $i != count($meta); $i++) {
            if ($meta[$i]['type'] == 'variable') {
                $data[$x][$meta[$i]['db']] = $items[$x][0][$columns[$meta[$i]['caption']]];
            } elseif ($meta[$i]['type'] == 'array') {
                for ($y = 0; $y != count($items[$x]); $y++) {
                    // Required because getNonEmptyIterations() is not called for simple-arrays, as they are usually created via a multiple select box, and cannot have empty values.
                    if ($items[$x][$y][$columns[$meta[$i]['caption']]]) {
                        $data[$x][$meta[$i]['db']][] = $items[$x][$y][$columns[$meta[$i]['caption']]];
                    }
                }
            } elseif (is_array($meta[$i]['type'])) {
                $data[$x][$meta[$i]['db']] = XMLImport_matrixToData($meta[$i]['type'], $columns, $columns[$meta[$i]['type'][0]['caption']], $items[$x]);
            }
        }
    }
    
    return $data;
}

// Recursively removes all empty but defined keys from an array.
function XMLImport_cleanArray($array) {
    $keys = array_keys($array);
    
    foreach ($keys as $k) {
        if (is_array($array[$k])) {
            $array[$k] = XMLImport_cleanArray($array[$k]);
        }
        if ($array[$k] == '' || $array[$k] == Array()) {
            unset($array[$k]);
        }
    }
    
    return $array;
}

function xml_read($file, &$error) {
    if ($data = @file_get_contents($file)) {
        $data = preg_replace('/ss:/', '', $data); // Without this simplexml_load_string() doesn't read tags prepended with "ss:".
        $xml = simplexml_load_string($data);
        
        for ($row = 0, $rowNum = 1; $row != count($xml->Worksheet->Table->Row); $row++, $rowNum++) {
            if ($xml->Worksheet->Table->Row[$row]['Index']) {
                $rowNum = (integer) $xml->Worksheet->Table->Row[$row]['Index'];
            }
            
            for ($j = 0, $col = 0; $j != count($xml->Worksheet->Table->Row[$row]->Cell); $j++, $col++) {
                $cell = $xml->Worksheet->Table->Row[$row]->Cell[$j];
                
                if ($cell['Index']) {
                    $col = (integer) $cell['Index'] - 1; // Excel counts from '1' instead of '0'.
                }
                
                /*
                $cell = xmlentities($cell, true); // Reverse xml entities.
                $cell = str_replace('&#10;', "\n", $cell); // Excel's newline in XML.
                $cell = trim($cell);
                */
                $data = trim((string) $cell->Data[0]);
                
                if ($data) {
                    $matrix[$row]['row'] = $rowNum; // Add a variable indicating the row of the item in the XML file.
                    $matrix[$row][$col] = $data;
                }
            }
        }
    }
    
    return $matrix;
    
    /* Note: this is the old XML read method, implemented with regular expressions. It failed to handle non-expanded tags (e.g. '<Cell/>').
    if ($data = @file_get_contents($file)) {
        // Excel sometimes skips empty rows and indicates the next non-empty row's position by a <Row> tag attribute. Catch and use it to keep track of the row number (to be shown in case of error).
        if (preg_match_all('{<Row[^>]*(?:ss:Index="(\d+)")?>.+</Row>}Us', $data, $matches)) {
            for ($row = 0, $rowNum = 1; $row != count($matches[0]); $row++, $rowNum++) {
                if ($matches[1][$row]) {
                    $rowNum = $matches[1][$row];
                }
                
                $matrix[$row]['row'] = $rowNum; // Add a variable indicating the row of the item in the XML file.
                
                if (preg_match_all('{<Cell[^>]*>.*<Data[^>]*>(.*)</Data>.*</Cell>}Us', $matches[0][$row], $sub_matches)) {
                    // $j runs on all input cells; $column tracks the cell position (because Excel sometimes skips empty cells).
                    for ($j = 0, $column = 0; $j != count($sub_matches[0]); $j++, $column++) {
                        // Excel sometimes skips empty cells and indicates the next non-empty cell's position by a <Cell> tag attribute.
                        if (preg_match('{<Cell[^>]*ss:Index="(\d+)"[^>]*>}s', $sub_matches[0][$j], $sub_sub_matches)) {
                            $column = $sub_sub_matches[1] - 1; // Excel counts from '1' instead of '0'.
                        }
                        
                        $cell = $sub_matches[1][$j];
                        $cell = xmlentities($cell, true); // Reverse xml entities.
                        $cell = str_replace('&#10;', "\n", $cell); // Excel's newline in XML.
                        $cell = trim($cell);
                        
                        if ($cell != '') {
                            $matrix[$row][$column] = $cell;
                        }
                    }
                } else {
                    $error = 'could not parse cells';
                    return false;
                }
            }
        } else {
            $error = 'could not parse rows';
            return false;
        }
    } else {
        $error = 'could not open XML file';
        return false;
    }
    
    return $matrix;
    */
}

##

// Functions available for templates (some are also used by non-template pages).

// Handles paths by making every URL absolute (i.e. begin with a slash).
// Especially needed when the site is nested.
// If CDN urls are defined for the current protocol and the URL matches a patch handled by a CDN, return the appropriate CDN URL.
function href($url) {
    static $base;
    
    if (!$base) {
        $base = preg_match('{/$}', dirname($_SERVER['SCRIPT_NAME'])) ? dirname($_SERVER['SCRIPT_NAME']) : dirname($_SERVER['SCRIPT_NAME']) . '/';
    }
    
    if (!preg_match('/^(http|ftp|mailto|tel|cid|#)/i', $url)) {
        $url = $base . preg_replace('{^/}', '', $url);
    }
    
    if ((($_SERVER['HTTPS'] && constant('HTTPS_CDN_URL')) || (!$_SERVER['HTTPS'] && constant('HTTP_CDN_URL'))) && preg_match('/^' . preg_quote($base, '/') . '(files|images|include|repository)\//i', $url) && !preg_match('/\.php/i', $url) && !preg_match('/^' . preg_quote($base, '/') . 'files\/.captchas/i', $url)) {
        $url = preg_replace('/^' . preg_quote($base, '/') . '(files|images|include|repository)\//i', ($_SERVER['HTTPS'] ? constant('HTTPS_CDN_URL') : constant('HTTP_CDN_URL')) . '$1/', $url);
    }
    
    return $url;
}

function href_HTMLArea($string) {
    $string = preg_replace_callback(
        '{(href|src|name="movie" value)="([^"]+)"}S',
        function ($matches) {
            return $matches[1] . "='" . href($matches[2]) . "'";
        },
        $string
    );
    $string = preg_replace_callback(
        '/background\-image: url\(([^)]+)\)/S',
        function ($matches) {
            return "background-image: url('" . href($matches[1]) . "')";
        },
        $string
    );
    return $string;
}

// Receives a recursive array and returns a flat list version of it.
// $recursionKey is the array key that containts each item's descendants.
function flattenRecursiveArray($arrayRecursive, $recursionKey, $arrayFlat = false) {
    for ($i = 0; $i != count($arrayRecursive); $i++) {
        $tmp = $arrayRecursive[$i];
        unset($tmp[$recursionKey]);
        $arrayFlat[] = $tmp;
        
        if ($arrayRecursive[$i][$recursionKey]) {
            $arrayFlat = flattenRecursiveArray($arrayRecursive[$i][$recursionKey], $recursionKey, $arrayFlat);
        }
    }
    
    return $arrayFlat;
}

function setVar($var, $val) {
    $_SESSION['variables'][$var] = $val;
}

function getVar($var) {
    return $_SESSION['variables'][$var];
}

function unsetVar($var) {
    unset($_SESSION['variables'][$var]);
}

// Retrieves docs that have parameters that match the relevant criteria.
function getDocsByParameter($docType, $parameterName, $parameterDataName, $value) {
    if ($value) {
        $WHERE = "(sdp.value_short LIKE '" . mysql_real_escape_string($value) . "' OR sdp.value_long LIKE '" . mysql_real_escape_string($value) . "')";
    } else {
        $WHERE = "1";
    }
    
    if ($parameterDataName) {
        $sql_query = mysql_query("SELECT sdp.doc_id FROM sys_docs_params sdp, sys_docs sd WHERE sdp.doc_id = sd.doc_id AND sd.doc_type = '" . mysql_real_escape_string($docType) . "' AND sdp.param_name = '" . mysql_real_escape_string($parameterName) . "' AND sdp.param_data_name = '" . mysql_real_escape_string($parameterDataName) . "' AND $WHERE GROUP BY sdp.doc_id");
    } else {
        $sql_query = mysql_query("SELECT sdp.doc_id FROM sys_docs_params sdp, sys_docs sd WHERE sdp.doc_id = sd.doc_id AND sd.doc_type = '" . mysql_real_escape_string($docType) . "' AND sdp.param_name = '" . mysql_real_escape_string($parameterName) . "' AND sdp.param_data_name IS NULL AND $WHERE GROUP BY sdp.doc_id");
    }
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $docs[] = $sql['doc_id'];
    }
    
    return $docs;
}

function getDocParameters($docId, $paramName = false, $dataName = false) {
    $parameters = false;
    
    // There are four kinds of parameters: single-data, single-data-iterated, multiple-data, multiple-data-iterated.
    // The kind of each parameter can be directly asserted from the 'sys_docs_params' db table.
    // The 'iteration' column is NULL if the parameter is not-iterated.
    // The 'param_data_name' column is NULL if the parameter is single-data.
    
    if ($paramName) {
        $WHERE = "param_name = '" . mysql_real_escape_string($paramName) . "'";
        
        if ($dataName) {
            $WHERE .= " AND param_data_name = '" . mysql_real_escape_string($dataName) . "'";
        }
    } else {
        $WHERE = "1";
    }
    
    $sql_query = mysql_query("SELECT type AS param_type, param_name, iteration, param_data_name, IFNULL(value_short, value_long) AS value FROM sys_docs_params LEFT JOIN sys_docs USING (doc_id) LEFT JOIN sys_docs_types_params_data USING (doc_type, param_name, param_data_name) WHERE doc_id = '" . mysql_real_escape_string($docId) . "' AND $WHERE ORDER BY iteration ASC");
    //$sql_query = mysql_query("SELECT param_name, iteration, param_data_name, IFNULL(value_short, value_long) AS value FROM sys_docs_params WHERE doc_id = '" . mysql_real_escape_string($docId) . "' AND $WHERE");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        if ($sql['param_type'] == 'htmlarea') {
            $sql['value'] = href_HTMLArea($sql['value']);
        }
        if ($sql['iteration'] == '' && $sql['param_data_name'] == '') {
            $parameters[$sql['param_name']] = $sql['value'];
        } elseif ($sql['iteration'] == '') {
            $parameters[$sql['param_name']][$sql['param_data_name']] = $sql['value'];
        } elseif ($sql['param_data_name'] == '') {
            $parameters[$sql['param_name']][$sql['iteration']] = $sql['value'];
        } else {
            $parameters[$sql['param_name']][$sql['iteration']][$sql['param_data_name']] = $sql['value'];
        }
    }
    
    return $parameters;
}

// Get all parameters the doc-type has defined (not structured according to the section association).
function getDocTypeParameters($docType) {
    $parameters = false;
    
    // Get all parameters.
    $sql_query = mysql_query("SELECT param_name, section_id, is_iteratable FROM sys_docs_types_params WHERE doc_type = '$docType' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        // Get all parameter subtypes associations.
        $sub_sql_query = mysql_query("SELECT doc_subtype FROM sys_docs_types_params__subtypes WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($sql['param_name']) . "'");
        while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
            $sql['subtypes'][] = $sub_sql['doc_subtype'];
        }
        
        // Get all parameter data.
        $sub_sql_query = mysql_query("SELECT param_data_name, caption, annotation, type, type__text__maxlength FROM sys_docs_types_params_data WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($sql['param_name']) . "' ORDER BY idx ASC");
        while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
            // For multiple-data get all the options.
            if ($sub_sql['type'] == 'select' || $sub_sql['type'] == 'radio') {
                $sub_sub_sql_query = mysql_query("SELECT param_data_option_value AS value, text, is_selected FROM sys_docs_types_params_data_options WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($sql['param_name']) . "' AND param_data_name = '" . mysql_real_escape_string($sub_sql['param_data_name']) . "' ORDER BY idx ASC");
                while ($sub_sub_sql = mysql_fetch_assoc($sub_sub_sql_query)) {
                    $sub_sql['options'][] = $sub_sub_sql;
                }
            }
            
            $sql['data'][] = $sub_sql;
        }
        
        $parameters[] = $sql;
    }
    
    return $parameters;
}

// Takes the parameter's data-names structure and merges them with the values (if exist)
// Added by Oren - 3/2/2009
function getDocParametersWithStructure($doc_type, $doc_id = false, $specific_parameter = false) {
        
    if ($doc_id) { $parameters = getDocParameters($doc_id, $specific_parameter); }
    if ($doc_type) { $structure = getDocTypeParameters($doc_type); }
    
    for ($i = 0; $i != count($structure); $i++) {       
        for ($j = 0; $j != count($structure[$i]['data']); $j++) {
            
            if ($structure[$i]['is_iteratable'] == 1) {
                
                if (empty($parameters[$structure[$i]['param_name']])) {
                    // No values exist, only structure
                    
                    $k = 0;
                    
                    if ($structure[$i]['data'][$j]['param_data_name']) {
                        
                        $tmp['value'] = $parameters[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']];
                        $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                        $tmp['type'] = $structure[$i]['data'][$j]['type'];
                        $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                        $tmp['options'] = $structure[$i]['data'][$j]['options'];
                        
                        $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']] = $tmp;
                        // Input Parameters are the "input format" of the parameters - param__...
                        $input_parameters['param__'.$structure[$i]['param_name'].'__'.$k.'__'.$structure[$i]['data'][$j]['param_data_name']] = $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']]['value'];
                    
                    } else {
                        
                        $tmp['value'] = $parameters[$structure[$i]['param_name']][$k];
                        $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                        $tmp['type'] = $structure[$i]['data'][$j]['type'];
                        $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                        $tmp['options'] = $structure[$i]['data'][$j]['options'];
                        
                        $parameters_with_structure[$structure[$i]['param_name']][$k] = $tmp;
                        // Input Parameters are the "input format" of the parameters - param__...
                        $input_parameters['param__'.$structure[$i]['param_name'].'__'.$k] = $parameters_with_structure[$structure[$i]['param_name']]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']]['value'];
                    
                    }
                    
                } else {
                    
                    for ($k = 0; $k != count($parameters[$structure[$i]['param_name']]); $k++) {
                        
                        if ($structure[$i]['data'][$j]['param_data_name']) {
                            // Data-names exist
                            
                            $tmp['value'] = $parameters[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']];
                            $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                            $tmp['type'] = $structure[$i]['data'][$j]['type'];
                            $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                            $tmp['options'] = $structure[$i]['data'][$j]['options'];
                            
                            $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']] = $tmp;
                            // Input Parameters are the "input format" of the parameters - param__...
                            $input_parameters['param__'.$structure[$i]['param_name'].'__'.$k.'__'.$structure[$i]['data'][$j]['param_data_name']] = $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']][$k][$structure[$i]['data'][$j]['param_data_name']]['value'];
                        
                        } else {
                            
                            $tmp['value'] = $parameters[$structure[$i]['param_name']][$k];
                            $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                            $tmp['type'] = $structure[$i]['data'][$j]['type'];
                            $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                            $tmp['options'] = $structure[$i]['data'][$j]['options'];
                            
                            $parameters_with_structure[$structure[$i]['param_name']][$k] = $tmp;
                            // Input Parameters are the "input format" of the parameters - param__...
                            $input_parameters['param__'.$structure[$i]['param_name'].'__'.$k] = $parameters_with_structure[$structure[$i]['param_name']][$k]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']][$k]['value'];
                        
                        }
                        
                    }
                    
                }
                
            } else {
                
                if ($structure[$i]['data'][$j]['param_data_name']) {
                    
                    $tmp['value'] = $parameters[$structure[$i]['param_name']][$structure[$i]['data'][$j]['param_data_name']];
                    $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                    $tmp['type'] = $structure[$i]['data'][$j]['type'];
                    $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                    $tmp['options'] = $structure[$i]['data'][$j]['options'];
                    
                    $parameters_with_structure[ $structure[$i]['param_name'] ][ $structure[$i]['data'][$j]['param_data_name'] ] = $tmp;
                    
                    // Input Parameters are the "input format" of the parameters - param__...
                    $input_parameters['param__'.$structure[$i]['param_name'].'__'.$structure[$i]['data'][$j]['param_data_name']] = $parameters_with_structure[$structure[$i]['param_name']][$structure[$i]['data'][$j]['param_data_name']]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']][$structure[$i]['data'][$j]['param_data_name']]['value'];
                
                } else {
                    
                    $tmp['value'] = $parameters[$structure[$i]['param_name']];
                    $tmp['caption'] = $structure[$i]['data'][$j]['caption'];
                    $tmp['type'] = $structure[$i]['data'][$j]['type'];
                    $tmp['maxlength'] = $structure[$i]['data'][$j]['type__text__maxlength'];
                    $tmp['options'] = $structure[$i]['data'][$j]['options'];
                    
                    $parameters_with_structure[$structure[$i]['param_name']] = $tmp;
                    // Input Parameters are the "input format" of the parameters - param__...
                    $input_parameters['param__'.$structure[$i]['param_name']] = $parameters_with_structure[$structure[$i]['param_name']]['value'] == '+' ? '' : $parameters_with_structure[$structure[$i]['param_name']]['value'];
                
                }
                
                
            }
        
        }
    }
    
    return array($parameters_with_structure, $input_parameters);
    
}

function getDocGroups($docId) {
    $groups = false;
    
    if ($groups_temp = getGroups_complete()) {
        foreach ($groups_temp as $type => $group) {
            for ($i = 0; $i != count($group['groups']); $i++) {
                if (@mysql_result(mysql_query("SELECT group_id FROM type_group__associated_docs WHERE group_id = '" . $group['groups'][$i]['group_id'] . "' AND associated_doc_id = '" . mysql_real_escape_string($docId) . "'"), 0)) {
                    $groups[$type][] = array(
                        'doc' => $group['groups'][$i]['doc'],
                        'id' => $group['groups'][$i]['group_id'],
                        'parent_group_id' => $group['groups'][$i]['parent_group_id'],
                        'title' => $group['groups'][$i]['title'],
                        'status' => $group['groups'][$i]['status'],
                        'level' => $group['groups'][$i]['level']
                    );
                }
            }
        }
    }
    
    return $groups;
}

function getProductsInGroup($groupId) {
    $products = false;
    
    $sql_query = mysql_query("
        SELECT tp.product_id
        FROM type_group__associated_docs tg_ad, type_product tp
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = tp.product_id AND tp.status != 'hidden'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $products[] = $sql['product_id'];
    }
    
    return $products;
}

function getProduct($productId, $memberzoneId = false) {
    $product = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.title, t.catalog_number, t.price_base, t.price_retail, t.price_actual, t.handling_factor, t.delivery_factor, t.quantity, t.quantity_min, t.description_short, t.description, t.thumbnail_image_url, t.keywords, t.seo_title, t.seo_description, t.seo_keywords, t.status
        FROM sys_docs sd JOIN type_product t ON doc_id = product_id
        WHERE
            product_id = '" . mysql_real_escape_string($productId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($product) {
        $product['doc'] = $product['doc_name'] ? $product['doc_name'] : $product['doc_id'];
        
        $product['id'] = $product['doc_id'];
        $product['price_retail'] = ($product['price_retail'] != 0) ? $product['price_retail'] : false;
        $product['quantity'] = is_null($product['quantity']) ? '-' : $product['quantity'];
        $product['description'] = href_HTMLArea($product['description']);
        
        if ($memberzoneId) {
            if ($zonePrice = @mysql_result(mysql_query("SELECT price_zone FROM type_memberzone__products WHERE memberzone_id = '$memberzoneId' AND product_id = '$product[doc_id]'"), 0)) {
                $product['price_retail'] = $product['price_actual'];
                $product['price_actual'] = $zonePrice;
            }
        }
    }
    
    return $product;
}

function getProductGroups($productId) {
    return getDocGroups($productId);
}

function getProductImages($productId) {
    $images = false;
    
    $sql_query = mysql_query("SELECT url FROM type_product__images WHERE product_id = '" . mysql_real_escape_string($productId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $images[] = $sql;
    }
    
    return $images;
}

function getProductLinks($productId) {
    $links = false;
    
    $sql_query = mysql_query("SELECT title, url FROM type_product__links WHERE product_id = '" . mysql_real_escape_string($productId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $links[] = $sql;
    }
    
    return $links;
}

function getProductAttributes($productId) {
    $attributes = false;
    
    $sql_query = mysql_query("SELECT attribute, `option`, price_delta FROM type_product__attributes WHERE product_id = '" . mysql_real_escape_string($productId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        unset($option);
        
        $option['option'] = $sql['option'];
        $option['price_delta'] = ($sql['price_delta'] != 0) ? $sql['price_delta'] : false;
        
        $attributes[$sql['attribute']][] = $option;
    }
    // Transform attributes and options from an associative array into an ordinal array.
    if ($attributes) {
        $attributes_temp = $attributes;
        unset($attributes);
        
        $cnt = 0;
        foreach ($attributes_temp as $attribute => $options) {
            $attributes[$cnt]['attribute'] = $attribute;
            foreach ($options as $option) {
                $attributes[$cnt]['options'][] = $option;
            }
            $cnt++;
        }
    }
    
    return $attributes;
}

function getLicensesInGroup($groupId) {
    $licenses = false;
    
    $sql_query = mysql_query("
        SELECT tp.license_id
        FROM type_group__associated_docs tg_ad, type_license tl
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = tl.license_id AND tl.status != 'hidden'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $licenses[] = $sql['license_id'];
    }
    
    return $licenses;
}

function getLicense($licenseId) {
    $license = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.title, t.catalog_number, t.price_formula, t.expiration_period_units, t.expiration_period_count, t.description_price, t.description_short, t.description, t.thumbnail_image_url, t.seo_title, t.seo_description, t.seo_keywords, t.status
        FROM sys_docs sd JOIN type_license t ON doc_id = license_id
        WHERE
            license_id = '" . mysql_real_escape_string($licenseId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($license) {
        $license['doc'] = $license['doc_name'] ? $license['doc_name'] : $license['doc_id'];
        
        $license['id'] = $license['doc_id'];
    }
    
    return $license;
}

function getLicenseGroups($licenseId) {
    return getDocGroups($licenseId);
}

function getLicenseImages($licenseId) {
    $images = false;
    
    $sql_query = mysql_query("SELECT url FROM type_license__images WHERE license_id = '" . mysql_real_escape_string($licenseId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $images[] = $sql;
    }
    
    return $images;
}

function getLicenseLinks($licenseId) {
    $links = false;
    
    $sql_query = mysql_query("SELECT title, url FROM type_license__links WHERE license_id = '" . mysql_real_escape_string($licenseId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $links[] = $sql;
    }
    
    return $links;
}

function getLicenseUserVariables($licenseId) {
    $userVariables = false;
    
    $sql_query = mysql_query("SELECT name, title, description FROM type_license__user_variables WHERE license_id = '" . mysql_real_escape_string($licenseId) . "'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $userVariables[] = $sql;
    }
    
    return $userVariables;
}

function getArticlesInGroup($groupId) {
    $articles = false;
    
    $sql_query = mysql_query("
        SELECT ta.article_id
        FROM type_group__associated_docs tg_ad, type_article ta
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = ta.article_id
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $articles[] = $sql['article_id'];
    }
    
    return $articles;
}

function getArticle($articleId) {
    $article = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.title, t.subtitle, t.description_short, t.description, t.thumbnail_image_url, t.seo_title, t.seo_description, t.seo_keywords
        FROM sys_docs sd JOIN type_article t ON doc_id = article_id
        WHERE
            article_id = '" . mysql_real_escape_string($articleId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($article) {
        $article['doc'] = $article['doc_name'] ? $article['doc_name'] : $article['doc_id'];
        
        $article['id'] = $article['doc_id'];
        $article['description'] = href_HTMLArea($article['description']);
    }
    
    return $article;
}

function getArticleGroups($articleId) {
    return getDocGroups($articleId);
}

function getAuctionsInGroup($groupId) {
    $auctions = false;
    
    $sql_query = mysql_query("
        SELECT ta.auction_id
        FROM type_group__associated_docs tg_ad, type_auction ta
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = ta.auction_id
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $auctions[] = $sql['auction_id'];
    }
    
    return $auctions;
}

function getAuction($auctionId) {
    $auction = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.user_id,
            (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = t.user_id) AS user_name,
            (SELECT email FROM type_user WHERE user_id = t.user_id) AS user_email,
            t.title, t.description_short, t.description, t.best_bid, t.show_bids, t.authorized, t.is_locked, t.created, t.modified, t.locked,
            t.notify__new_bid, t.notify__new_best_bid,
            (SELECT MAX(bid) FROM type_auction__bids WHERE auction_id = t.auction_id) AS max_bid, (SELECT MIN(bid) FROM type_auction__bids WHERE auction_id = t.auction_id) AS min_bid,
            (SELECT COUNT(*) FROM type_auction__bids WHERE auction_id = t.auction_id AND is_winner = '1') AS winners
        FROM sys_docs sd JOIN type_auction t ON doc_id = auction_id
        WHERE
            auction_id = '" . mysql_real_escape_string($auctionId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($auction) {
        $auction['doc'] = $auction['doc_name'] ? $auction['doc_name'] : $auction['doc_id'];
        
        $auction['id'] = $auction['doc_id'];
        $auction['description'] = href_HTMLArea($auction['description']);
        
        $auction['notify__new_bid'] = @explode(',', $auction['notify__new_bid']);
        $auction['notify__new_best_bid'] = @explode(',', $auction['notify__new_best_bid']);
    }
    
    return $auction;
}

function getAuctionBids($auctionId) {
    $bids = false;
    
    $sql_query = mysql_query("SELECT bid_id AS id, bid_id, user_id, (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = ta_b.user_id) AS user_name, (SELECT email FROM type_user WHERE user_id = ta_b.user_id) AS user_email, bid, note, created, modified, notify__new_bid, notify__new_best_bid, notify__auction_close, is_winner FROM type_auction__bids ta_b WHERE auction_id = '" . mysql_real_escape_string($auctionId) . "' ORDER BY IF(modified != 0, modified, created) DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['notify__new_bid'] = @explode(',', $sql['notify__new_bid']);
        $sql['notify__new_best_bid'] = @explode(',', $sql['notify__new_best_bid']);
        $sql['notify__auction_close'] = @explode(',', $sql['notify__auction_close']);
        
        $bids[] = $sql;
    }
    
    return $bids;
}

function getAuctionImages($auctionId) {
    $images = false;
    
    $sql_query = mysql_query("SELECT url FROM type_auction__images WHERE auction_id = '" . mysql_real_escape_string($auctionId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $images[] = $sql;
    }
    
    return $images;
}

function getAuctionLinks($auctionId) {
    $links = false;
    
    $sql_query = mysql_query("SELECT title, url FROM type_auction__links WHERE auction_id = '" . mysql_real_escape_string($auctionId) . "' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $links[] = $sql;
    }
    
    return $links;
}

function getAuctionGroups($auctionId) {
    return getDocGroups($auctionId);
}

function getAuctionFiles($auctionId) {
    $files = false;
    
    $sql_query = mysql_query("SELECT file_id, `set`, url FROM type_auction__files WHERE auction_id = '" . mysql_real_escape_string($auctionId) . "' ORDER BY `set` ASC, idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $files[] = $sql;
    }
    
    return $files;
}

function getAuctionsLast($num = 1, $locked = 0, $onlyWithWinners = 0) {
    $auctions = false;
    
    $WHERE[] = "1";
    
    if ($locked == '1') {
        $WHERE[] = "is_locked = '1'";
    } elseif ($locked == '-1') {
        $WHERE[] = "is_locked = '0'";
    }
    
    if ($onlyWithWinners == '1') {
        $WHERE[] = "(SELECT '1' FROM type_auction__bids WHERE auction_id = type_auction.auction_id AND is_winner = '1' LIMIT 1) = '1'";
    }
    
    $sql_query = mysql_query("SELECT auction_id FROM type_auction WHERE " . implode(" AND ", $WHERE) . " ORDER BY created DESC LIMIT " . mysql_real_escape_string($num) . "");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $auctions[] = $sql['auction_id'];
    }
    
    return $auctions;
}

function getAuctionsTotalWinningBids() {
    return @mysql_result(mysql_query("SELECT SUM(bid) FROM type_auction__bids WHERE is_winner = '1'"), 0);
}

function lockAuctions($auctions) {
    if (is_array($auctions)) {
        for ($i = 0; $i != count($auctions); $i++) {
            mysql_query("UPDATE type_auction SET is_locked = '1', locked = NOW() WHERE auction_id = '" . mysql_real_escape_string($auctions[$i]) . "'");
        }
    }
}

function getObject($objectId, $direct = false) {
    if ($object = mysql_fetch_assoc(mysql_query("SELECT object_id, content FROM type_object WHERE object_id = '" . mysql_real_escape_string($objectId) . "' OR name = '" . mysql_real_escape_string($objectId) . "'"))) {
        $object['id'] = $object['object_id'];
        $object['content'] = href_HTMLArea($object['content']);
    }
    
    return $direct ? $object['content'] : $object;
}

function getPoll($pollId = false) {
    if ($pollId) {
        $poll = mysql_fetch_assoc(mysql_query("
            SELECT
                sd.doc_id, sd.doc_name,
                t.title, t.description, t.require_login, t.is_active, '0' AS votes 
            FROM sys_docs sd JOIN type_poll t ON doc_id = poll_id
            WHERE
                poll_id = '" . mysql_real_escape_string($pollId) . "'
                AND sd.doc_active = '1'
        "));
    } else {
        $poll = mysql_fetch_assoc(mysql_query("
            SELECT
                sd.doc_id, sd.doc_name,
                t.title, t.description, t.require_login, t.is_active, '0' AS votes 
            FROM sys_docs sd JOIN type_poll t ON doc_id = poll_id
            WHERE
                sd.doc_active = '1'
            ORDER BY poll_id DESC LIMIT 1
        "));
    }
    if ($poll) {
        $poll['doc'] = $poll['doc_name'] ? $poll['doc_name'] : $poll['doc_id'];
        
        $poll['id'] = $poll['doc_id'];
        $poll['description'] = href_HTMLArea($poll['description']);
        
        $sql_query = mysql_query("SELECT option_id AS id, `option`, image_url, votes FROM type_poll__options WHERE poll_id = '$poll[doc_id]' ORDER BY option_id ASC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $poll['options'][] = $sql;
            $poll['votes'] += $sql['votes'];
        }
        
        if (@in_array($poll['doc_id'], $_SESSION['participated_polls']) || ($_SESSION['user'] && @mysql_result(mysql_query("SELECT user_id FROM type_poll__participants WHERE poll_id = '$poll[doc_id]' AND user_id = '" . $_SESSION['user']['user_id'] . "'"), 0))) {
            $poll['already_voted'] = 1;
        } else {
            $poll['already_voted'] = 0;
        }
    }
    
    return $poll;
}

function getQuiz($quizId = false) {
    if ($quizId) {
        $quiz = mysql_fetch_assoc(mysql_query("
            SELECT
                sd.doc_id, sd.doc_name,
                t.title, t.description, t.start_date, t.is_locked
            FROM sys_docs sd JOIN type_quiz t ON doc_id = quiz_id
            WHERE
                quiz_id = '" . mysql_real_escape_string($quizId) . "'
                AND start_date <= NOW()
                AND sd.doc_active = '1'
        "));
    } else {
        $quiz = mysql_fetch_assoc(mysql_query("
            SELECT
                sd.doc_id, sd.doc_name,
                t.title, t.description, t.start_date, t.is_locked
            FROM sys_docs sd JOIN type_quiz t ON doc_id = quiz_id
            WHERE
                start_date <= NOW()
                AND sd.doc_active = '1'
            ORDER BY quiz_id DESC
            LIMIT 1
        "));
    }
    if ($quiz) {
        $quiz['doc'] = $quiz['doc_name'] ? $quiz['doc_name'] : $quiz['doc_id'];
        
        $quiz['id'] = $quiz['doc_id'];
        $quiz['description'] = href_HTMLArea($quiz['description']);
        
        $sql_query = mysql_query("SELECT question_id AS id, question, score FROM type_quiz__questions WHERE quiz_id = '$quiz[doc_id]' ORDER BY question_id ASC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $sub_sql_query = mysql_query("SELECT answer_id AS id, answer FROM type_quiz__answers WHERE quiz_id = '$quiz[doc_id]' AND question_id = '$sql[id]' ORDER BY question_id ASC, answer_id ASC");
            while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
                $sql['answers'][] = $sub_sql;
            }
            
            $quiz['questions'][] = $sql;
        }
        
        if ($_SESSION['user']) {
            if (@mysql_result(mysql_query("SELECT user_id FROM type_quiz__participants WHERE quiz_id = '$quiz[doc_id]' AND user_id = '" . $_SESSION['user']['user_id'] . "'"), 0)) {
                $quiz['already_participated'] = 1;
            } else {
                $quiz['already_participated'] = 0;
            }
        }
    }
    
    return $quiz;
}

function getExamsInGroup($groupId) {
    $exams = false;
    
    $sql_query = mysql_query("
        SELECT te.exam_id
        FROM type_group__associated_docs tg_ad, type_exam te
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = te.exam_id
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $exam[] = $sql['exam_id'];
    }
    
    return $exam;
}

function getExam($examId) {
    $exam = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.title, t.description, t.allowed_retrials,
            (SELECT SUM(score) FROM type_exam__questions WHERE exam_id = '" . constant('DOC_ID') . "') AS score_max
        FROM sys_docs sd JOIN type_exam t ON doc_id = exam_id
        WHERE
            exam_id = '" . mysql_real_escape_string($examId) . "'
            AND t.is_active = '1'
            AND sd.doc_active = '1'
    "));
    
    if ($exam) {
        $exam['doc'] = $exam['doc_name'] ? $exam['doc_name'] : $exam['doc_id'];
        
        $exam['id'] = $exam['doc_id'];
        $exam['description'] = href_HTMLArea($exam['description']);
        
        if ($_SESSION['user']['user_id']) {
            $temp = mysql_fetch_assoc(mysql_query("SELECT retrials, timestamp, is_graded FROM type_exam__participants WHERE exam_id = '$exam[doc_id]' AND user_id = '" . $_SESSION['user']['user_id'] . "'"));
            
            $exam['retrials'] = $temp['retrials'] ? $temp['retrials'] : 0;
            
            if ($temp['timestamp']) {
                $exam['timestamp'] = $temp['timestamp'];
            }
            
            if ($temp['is_graded']) {
                $exam['is_graded'] = $temp['is_graded'];
                $exam['score_user'] = @mysql_result(mysql_query("SELECT SUM(score) FROM type_exam__participants_answers WHERE exam_id = '$exam[doc_id]' AND user_id = '" . $_SESSION['user']['user_id'] . "'"), 0);
            }
        }
    }
    
    return $exam;
}

function getTalkbacks($docId, $level = 0) {
    $talkbacks = false;
    
    $ORDERBY = ($level > 0) ? "timestamp ASC" : "timestamp DESC";
    
    $sql_query = mysql_query("SELECT talkback_id, parent_doc_id, user_id, IFNULL(name, (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = type_talkback.user_id)) AS name, title, content, timestamp, votes_yay, votes_nay FROM type_talkback WHERE parent_doc_id = '" . mysql_real_escape_string($docId) . "' AND authorized = 1 ORDER BY $ORDERBY");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['level'] = $level;
        $sql['descendants'] = getTalkbacks($sql['talkback_id'], $level + 1);
        $talkbacks[] = $sql;
    }
    
    return $talkbacks;
}

function getTalkback($talkbackId) {
    $talkback = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.parent_doc_id, user_id, IFNULL(t.name, (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = t.user_id)) AS name, t.title, t.content, t.timestamp, t.votes_yay, t.votes_nay
        FROM sys_docs sd JOIN type_talkback t ON doc_id = talkback_id
        WHERE
            talkback_id = '" . mysql_real_escape_string($talkbackId) . "'
            AND sd.doc_active = '1'
            AND t.authorized = 1
    "));
    
    if ($talkback) {
        $talkback['doc'] = $talkback['doc_name'] ? $talkback['doc_name'] : $talkback['doc_id'];
        
        $talkback['id'] = $talkback['doc_id'];
    }
    
    return $talkback;
}

function deleteTalkback_m($talkbackId) {
    if (@mysql_result(mysql_query("SELECT talkback_id FROM type_talkback WHERE talkback_id = '$talkbackId'"), 0)) {
        $tree = flattenRecursiveArray(getTalkbacks($talkbackId), 'descendants');
        $tree[] = array('talkback_id' => $talkbackId);
        
        foreach ($tree as $talkback) {
            mysql_query("DELETE FROM sys_docs WHERE doc_id = '" . $talkback['talkback_id'] . "'");
            mysql_query("DELETE FROM type_talkback WHERE talkback_id = '" . $talkback['talkback_id'] . "'");
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '" . $talkback['talkback_id'] . "'");
        }
        
        return true;
    }
    
    return false;
}

function deleteReview_m($reviewId) {
    mysql_query("DELETE FROM sys_docs WHERE doc_id = '$reviewId'");
    mysql_query("DELETE FROM type_review WHERE review_id = '$reviewId'");
    mysql_query("DELETE FROM type_review__features WHERE review_id = '$reviewId'");
    
    return true;
}

function getReviewFeatureSet_byDocId($docId) {
    $features = false;
    
    if ($fsetId = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($docId) . "'"), 0)) {
        $features = getReviewFeatureSet_byFsetId($fsetId);
    }
    
    return $features;
}

function getReviewFeatureSet_byFsetId($fsetId) {
    $features = false;
    
    $sql_query = mysql_query("SELECT feature FROM type_review__fsets_features WHERE fset_id = '$fsetId' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $features[] = $sql['feature'];
    }
    
    return $features;
}

function getReviews($docId) {
    $reviews = false;
    
    $sql_query = mysql_query("SELECT review_id, user_id, IFNULL(name, (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = type_review.user_id)) AS name, title, content, timestamp, votes_yay, votes_nay FROM type_review WHERE parent_doc_id = '" . mysql_real_escape_string($docId) . "' AND authorized = '1' ORDER BY timestamp DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sub_sql_query = mysql_query("SELECT feature, factor, score, comment FROM type_review__features WHERE review_id = '$sql[review_id]' ORDER BY idx ASC");
        while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
            $sql['features'][] = $sub_sql;
        }
        
        $sql['score'] = @mysql_result(mysql_query("SELECT SUM(value) / SUM(factor) FROM (SELECT factor, (score * factor) AS value FROM type_review__features WHERE review_id = '$sql[review_id]' AND score IS NOT NULL) t"), 0);
        
        $reviews[] = $sql;
    }
    
    return $reviews;
}

function getDocReviewsAverage($docId) {
    $sql_query = mysql_query("SELECT review_id FROM type_review WHERE parent_doc_id = '" . mysql_real_escape_string($docId) . "' AND authorized = '1'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $score = @mysql_result(mysql_query("SELECT SUM(value) / SUM(factor) FROM (SELECT factor, (score * factor) AS value FROM type_review__features WHERE review_id = '$sql[review_id]' AND score IS NOT NULL) t"), 0);
        
        if (!is_null($score)) {
            $scores[] = $score;
        }
    }
    
    return ($scores && $scores) ? array_sum($scores) / count($scores) : false;
}

function getUser($userId) {
    $user = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name, sd.doc_subtype,
            t.email, t.first_name, t.last_name, t.company, t.job_title, t.street_1, t.street_2, t.city, t.state, t.zipcode, t.country, t.phone_1, t.phone_2, t.mobile, t.date_of_birth, t.seo_title, t.seo_description, t.seo_keywords, t.credits, t.registration, t.last_login, t.send_notifications
        FROM sys_docs sd JOIN type_user t ON doc_id = user_id
        WHERE
            user_id = '" . mysql_real_escape_string($userId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($user) {
        $user['doc'] = $user['doc_name'] ? $user['doc_name'] : $user['doc_id'];
        $post['type'] = $post['doc_subtype']; /* deprecated */
        
        $user['id'] = $user['doc_id'];
    }
    
    return $user;
}

function getUserAuctions($userId) {
    $auctions = false;
    
    $sql_query = mysql_query("SELECT auction_id FROM type_auction WHERE user_id = '" . mysql_real_escape_string($userId) . "' ORDER BY IF(modified != 0, modified, created) DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $auctions[] = $sql['auction_id'];
    }
    
    return $auctions;
}

function getUserBids($userId) {
    $bids = false;
    
    $sql_query = mysql_query("SELECT bid_id AS id, auction_id, user_id, (SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = ta_b.user_id) AS user_name, (SELECT email FROM type_user WHERE user_id = ta_b.user_id) AS user_email, bid, note, created, modified, notify__new_bid, notify__new_best_bid, notify__auction_close, is_winner FROM type_auction__bids ta_b WHERE user_id = '" . mysql_real_escape_string($userId) . "' ORDER BY IF(modified != 0, modified, created) DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['notify__new_bid'] = @explode(',', $sql['notify__new_bid']);
        $sql['notify__new_best_bid'] = @explode(',', $sql['notify__new_best_bid']);
        $sql['notify__auction_close'] = @explode(',', $sql['notify__auction_close']);
        
        $bids[] = $sql;
    }
    
    return $bids;
}

function getUserMemberzones($userId) {
    $memberzones = false;
    
    $sql_query = mysql_query("SELECT memberzone_id FROM type_memberzone__users WHERE user_id = '$userId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $memberzones[] = $sql['memberzone_id'];
    }
    
    return $memberzones;
}

function getMemberzoneUsers($memberzoneId) {
    $users = false;
    
    $sql_query = mysql_query("SELECT user_id FROM type_memberzone__users WHERE memberzone_id = '$memberzoneId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $users[] = $sql['user_id'];
    }
    
    return $users;
}

function getUserPosts($userId) {
    $posts = false;
    
    $sql_query = mysql_query("SELECT post_id FROM type_post WHERE user_id = '" . mysql_real_escape_string($userId) . "' ORDER BY IF(modified != 0, modified, created) DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $posts[] = $sql['post_id'];
    }
    
    return $posts;
}

function getUserFiles($userId) {
    $files = false;
    
    $sql_query = mysql_query("SELECT file_id, `set`, url FROM type_user__files WHERE user_id = '" . mysql_real_escape_string($userId) . "' ORDER BY `set` ASC, idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $files[] = $sql;
    }
    
    return $files;
}

function getAdsInGroup($groupId) {
    $ad = false;
    
    $sql_query = mysql_query("
        SELECT
            ta.ad_id,
            IF(CPD_limit IS NOT NULL, IF(CPL IS NOT NULL, IFNULL((SELECT CPL * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'lead'), 0), 0) + IF(CPC IS NOT NULL, IFNULL((SELECT CPC * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'click'), 0), 0) + IF(CPA IS NOT NULL, IFNULL((SELECT CPA * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'actions'), 0), 0) >= CPD_limit, 0) AS CPD_limit_reached
        FROM type_group__associated_docs tg_ad, type_ad ta
        WHERE
            tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "'
            AND tg_ad.associated_doc_id = ta.ad_id
            AND ta.authorized = '1'
            AND (ta.expiration_date IS NULL OR ta.expiration_date > NOW())
        HAVING CPD_limit_reached = '0'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $ad[] = $sql['ad_id'];
    }
    
    return $ad;
}

function getUserAds($userId) {
    $ads = false;
    
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_ad t ON doc_id = ad_id
        WHERE
            t.user_id = '" . mysql_real_escape_string($userId) . "'
            AND sd.doc_active = '1'
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $ads[] = $sql['doc_id'];
    }
    
    return $ads;
}

function getAd($adId) {
    $ad = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.user_id, t.title, t.content, t.banner_url, t.link_url,
            IF(CPD_limit IS NOT NULL, IF(CPL IS NOT NULL, IFNULL((SELECT CPL * cnt FROM type_ad__log WHERE ad_id = t.ad_id AND `date` = DATE(NOW()) AND type = 'lead'), 0), 0) + IF(CPC IS NOT NULL, IFNULL((SELECT CPC * cnt FROM type_ad__log WHERE ad_id = t.ad_id AND `date` = DATE(NOW()) AND type = 'click'), 0), 0) + IF(CPA IS NOT NULL, IFNULL((SELECT CPA * cnt FROM type_ad__log WHERE ad_id = t.ad_id AND `date` = DATE(NOW()) AND type = 'actions'), 0), 0) >= CPD_limit, 0) AS CPD_limit_reached
        FROM sys_docs sd JOIN type_ad t ON doc_id = ad_id
        WHERE
            ad_id = '" . mysql_real_escape_string($adId) . "'
            AND t.authorized = '1'
            AND (t.expiration_date IS NULL OR t.expiration_date > NOW())
            AND sd.doc_active = '1'
        HAVING CPD_limit_reached = '0'
    "));
    
    if ($ad) {
        $ad['doc'] = $ad['doc_name'] ? $ad['doc_name'] : $ad['doc_id'];
        
        $ad['id'] = $ad['doc_id'];
    }
    
    return $ad;
}

function getAdLog($adId, $rangeBegin = false, $rangeEnd = false) {
    if ($rangeBegin && $rangeEnd) {
        $WHERE = "date BETWEEN '" . mysql_real_escape_string($rangeBegin) . "' AND '" . mysql_real_escape_string($rangeEnd) . "'";
    } else {
        $WHERE = "1";
    }
    
    $ad = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id,
            ta.user_id, ta.title, ta.content, ta.banner_url, ta.link_url,
            (SELECT SUM(cnt) FROM type_ad__log WHERE ad_id = ta.ad_id AND type = 'lead' AND $WHERE) AS leads,
            (SELECT SUM(cnt) FROM type_ad__log WHERE ad_id = ta.ad_id AND type = 'click' AND $WHERE) AS clicks,
            (SELECT SUM(cnt) FROM type_ad__log WHERE ad_id = ta.ad_id AND type = 'action' AND $WHERE) AS actions,
            ta.CPL, ta.CPC, ta.CPA,
            IF(CPL IS NOT NULL, IFNULL((SELECT CPL * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'lead' AND $WHERE), 0), 0) + IF(CPC IS NOT NULL, IFNULL((SELECT CPC * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'click' AND $WHERE), 0), 0) + IF(CPA IS NOT NULL, IFNULL((SELECT CPA * cnt FROM type_ad__log WHERE ad_id = ta.ad_id AND `date` = DATE(NOW()) AND type = 'action' AND $WHERE), 0), 0) AS CPD,
            ta.CPD_limit, ta.expiration_date, ta.authorized
        FROM sys_docs sd JOIN type_ad ta ON doc_id = ta.ad_id
        WHERE
            ta.ad_id = '" . mysql_real_escape_string($adId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($ad) {
        $ad['doc'] = $ad['doc_name'] ? $ad['doc_name'] : $ad['doc_id'];
        
        $ad['id'] = $ad['doc_id'];
    }
    
    return $ad;
}

function incrementAdCounter($adId, $type) {
    if ($type == 'lead' || $type == 'click' || $type == 'action') {
        mysql_query("INSERT INTO type_ad__log (ad_id, `date`, type, cnt) VALUE ('$adId', NOW(), '$type', 1)");
        if (mysql_error()) {
            mysql_query("UPDATE type_ad__log SET cnt = cnt + 1 WHERE ad_id = '$adId' AND `date` = DATE(NOW()) AND type = '$type'");
        }
    }
    
    return false;
}

function getPostsInGroup($groupId) {
    $posts = false;
    
    $sql_query = mysql_query("
        SELECT t.post_id
        FROM type_group__associated_docs tg_ad, type_post t
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = t.post_id
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $posts[] = $sql['post_id'];
    }
    
    return $posts;
}

function getPost($postId) {
    $post = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_subtype, sd.doc_name,
            t.user_id, t.title, t.description_short, t.description, t.authorized, t.created, t.modified
        FROM sys_docs sd JOIN type_post t ON doc_id = post_id
        WHERE
            post_id = '" . mysql_real_escape_string($postId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($post) {
        $post['doc'] = $post['doc_name'] ? $post['doc_name'] : $post['doc_id'];
        $post['type'] = $post['doc_subtype']; /* deprecated */
        
        $post['id'] = $post['doc_id'];
        $post['description'] = href_HTMLArea($post['description']);
    }
    
    return $post;
}

function getPostFiles($postId) {
    $files = false;
    
    $sql_query = mysql_query("SELECT file_id, `set`, url FROM type_post__files WHERE post_id = '" . mysql_real_escape_string($postId) . "' ORDER BY `set` ASC, idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $files[] = $sql;
    }
    
    return $files;
}

function getPostGroups($postId) {
    return getDocGroups($postId);
}

function getPostsLast($num = 1) {
    $posts = false;
    
    $sql_query = mysql_query("SELECT post_id FROM type_post ORDER BY IF(modified != 0, modified, created) DESC LIMIT " . mysql_real_escape_string($num) . "");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $posts[] = $sql['post_id'];
    }
    
    return $posts;
}

function getPostTypes() {
    $postTypes = false;
    
    $sql_query = mysql_query("SELECT doc_subtype AS name, title, days_active, credits_price FROM sys_docs_subtypes LEFT JOIN type_post__subtypes USING (doc_subtype) WHERE doc_type = 'post' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $postTypes[] = $sql;
    }
    
    return $postTypes;
}

function getVFLocationsInGroup($groupId) {
    $locations = false;
    
    $sql_query = mysql_query("
        SELECT t.vf_location_id
        FROM type_group__associated_docs tg_ad, type_vf_location t
        WHERE tg_ad.group_id = '" . mysql_real_escape_string($groupId) . "' AND tg_ad.associated_doc_id = t.vf_location_id
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $locations[] = $sql['vf_location_id'];
    }
    
    return $locations;
}

function getVFLocation($locationId) {
    $vf_location = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.parent_vf_location_id, t.name, t.address, t.status, t.authorized
        FROM sys_docs sd JOIN type_vf_location t ON doc_id = vf_location_id
        WHERE
            vf_location_id = '" . mysql_real_escape_string($locationId) . "'
            AND sd.doc_active = '1'
    "));
    
    if ($vf_location) {
        $vf_location['doc'] = $vf_location['doc_name'] ? $vf_location['doc_name'] : $vf_location['doc_id'];
        
        $vf_location['id'] = $vf_location['doc_id'];
        
        $sql_query = mysql_query("SELECT t_vft.vf_ticket_id FROM type_vf_ticket__vf_locations t_vft_vfl, type_vf_ticket t_vft WHERE t_vft_vfl.vf_location_id = '$vf_location[doc_id]' AND t_vft_vfl.vf_ticket_id = t_vft.vf_ticket_id AND t_vft.cost IS NOT NULL AND t_vft.price IS NOT NULL");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $vf_location['vf_tickets'][] = $sql['vf_ticket_id'];
        }
    }
    
    return $vf_location;
}

function getVFLocationGroups($locationId) {
    return getDocGroups($locationId);
}

function getVFTicket($ticketId) {
    $vf_ticket = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id, sd.doc_name,
            t.name, t.status, t.description, t.cost, t.price
        FROM sys_docs sd JOIN type_vf_ticket t ON doc_id = vf_ticket_id
        WHERE
            vf_ticket_id = '" . mysql_real_escape_string($ticketId) . "'
            AND t.cost IS NOT NULL
            AND t.price IS NOT NULL
            AND sd.doc_active = '1'
    "));
    
    if ($vf_ticket) {
        $vf_ticket['doc'] = $vf_ticket['doc_name'] ? $vf_ticket['doc_name'] : $vf_ticket['doc_id'];
        
        $vf_ticket['id'] = $vf_ticket['doc_id'];
        
        $sql_query = mysql_query("SELECT vf_location_id FROM type_vf_ticket__vf_locations WHERE vf_ticket_id = '$vf_ticket[doc_id]'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $vf_ticket['vf_locations'][] = $sql['vf_location_id'];
        }
    }
    
    return $vf_ticket;
}

##

function addUserToMemberzone($userId, $memberzoneId) {
    $userId = @mysql_result(mysql_query("SELECT doc_id FROM sys_docs WHERE doc_id = '" . mysql_real_escape_string($userId) . "' AND doc_type = 'user'"), 0);
    $memberzoneId = @mysql_result(mysql_query("SELECT doc_id FROM sys_docs WHERE doc_id = '" . mysql_real_escape_string($memberzoneId) . "' AND doc_type = 'memberzone'"), 0);
    
    if ($userId && $memberzoneId) {
        mysql_query("INSERT INTO type_memberzone__users (memberzone_id, user_id) VALUES ('$memberzoneId', '$userId')");
        return true;
    } else {
        return false;
    }
}

function removeUserFromMemberzone($userId, $memberzoneId) {
    $userId = @mysql_result(mysql_query("SELECT doc_id FROM sys_docs WHERE doc_id = '" . mysql_real_escape_string($userId) . "' AND doc_type = 'user'"), 0);
    $memberzoneId = @mysql_result(mysql_query("SELECT doc_id FROM sys_docs WHERE doc_id = '" . mysql_real_escape_string($memberzoneId) . "' AND doc_type = 'memberzone'"), 0);
    
    if ($userId && $memberzoneId) {
        mysql_query("DELETE FROM type_memberzone__users WHERE memberzone_id = '$memberzoneId' AND user_id = '$userId'");
        return true;
    } else {
        return false;
    }
}

##

function updateUserCredits($userId, $value) {
    $credits = @mysql_result(mysql_query("SELECT credits FROM type_user WHERE user_id = '" . mysql_real_escape_string($userId) . "'"), 0);
    
    if (preg_match('/^\+\d+$/', $value)) {
        $credits += abs($value);
    } elseif (preg_match('/^\-\d+$/', $value)) {
        $credits -= abs($value);
    } elseif (preg_match('/^\d+$/', $value)) {
        $credits = abs($value);
    }
    
    mysql_query("UPDATE type_user SET credits = " . mysql_real_escape_string($credits) . " WHERE user_id = '" . mysql_real_escape_string($userId) . "'");
}

##

function SQLNULL($value) {
    return (isset($value) && $value == '') ? "NULL" : "'$value'";
}

##

# stripUnapprovedHTMLTags() removes all but approved ($APPROVED_HTML_TAGS) HTML tags from a string.
# HTML tags can be approved with all attributes; specific attributes only, with all values; and specific attributes, with specific values.

# Examples:
# To approve <br> with all attributes (`clear`, `style`) -> $APPROVED_HTML_TAGS['br'] = true;
# To approve <font> with only the color attribute -> $APPROVED_HTML_TAGS['font']['color'] = true;
# To approve <font> with only the size attribute, and specific values (7, 8, 9) -> $APPROVED_HTML_TAGS['font']['size'] = array('7', '8', '9');

# Todo: add support to allow blocking specific sub-attributes in the `style` attribute.

$APPROVED_HTML_TAGS['a'] = array('href', 'target', 'class', 'style');
$APPROVED_HTML_TAGS['p'] = array('class', 'style');
$APPROVED_HTML_TAGS['span'] = array('class', 'style');
$APPROVED_HTML_TAGS['div'] = array('class', 'style');
$APPROVED_HTML_TAGS['table'] = array('class', 'style', 'cellspacing', 'cellpadding', 'align');
$APPROVED_HTML_TAGS['tr'] = array('class', 'style');
$APPROVED_HTML_TAGS['td'] = array('colspan', 'rowspan', 'class', 'style');
$APPROVED_HTML_TAGS['th'] = array('colspan', 'class', 'style');
$APPROVED_HTML_TAGS['b'] = true;
$APPROVED_HTML_TAGS['strong'] = true;
$APPROVED_HTML_TAGS['h1'] = array('class', 'style');
$APPROVED_HTML_TAGS['h2'] = array('class', 'style');
$APPROVED_HTML_TAGS['h3'] = array('class', 'style');
$APPROVED_HTML_TAGS['img'] = array('src', 'title', 'alt', 'border', 'class', 'style'); // Doesn't have a closing tag.
$APPROVED_HTML_TAGS['object'] = array('class', 'style');
$APPROVED_HTML_TAGS['embed'] = array('class', 'style');
$APPROVED_HTML_TAGS['ol'] = array('type', 'class', 'style');
$APPROVED_HTML_TAGS['ul'] = array('type', 'class', 'style');
$APPROVED_HTML_TAGS['li'] = array('type', 'class', 'style');

function stripUnapprovedHTMLTags($string) {
    // Check every HTML tag with inspectHTMLTag().
    return preg_replace_callback(
        '/(<[^>]*>)/s',
        function ($matches) {
            return inspectHTMLTag($matches[1]);
        },
        $string
    );
}

function inspectHTMLTag($HTMLTag) {
    global $APPROVED_HTML_TAGS;
    
    // The pattern for a valid HTML tag.
    $pattern = '/
    <
    (\/)? (?# CLOSING TAG SLASH )
    ([a-zA-Z]+) (?# TAG NAME )
    (?: \s* ([a-zA-Z]+ = (?(?=") "[^>"]*" | (?(?=\') \'[^>\']*\' | [^>\s\/]+ ) ) ) \s* )* (?# ATTRIBUTES )
    \s*(\/)? (?# XML CLOSING TAG SLASH )
    >
    /x';
    
    // If the tag isn't in the correct pattern, strip it.
    if (!preg_match($pattern, $HTMLTag, $matches)) {
        return false;
    }
    
    $HTMLTagClose = $matches[1];
    $HTMLTagName = $matches[2];
    $HTMLTagAttributes = $matches[3];
    $HTMLTagXMLClose = $matches[4];
    
    // A closing tag (i.e. </font>) can't have attributes.
    if ($HTMLTagClose && $HTMLTagAttributes) {
        $HTMLTagAttributes = false;
    }
    
    // A closing tag (i.e. </font>) can't be a closing XML tag (<br/>).
    if ($HTMLTagClose && $HTMLTagXMLClose) {
        $HTMLTagXMLClose = false;
    }
    
    // If the tag is not in the list of approved tags, strip it.
    if (!$APPROVED_HTML_TAGS[$HTMLTagName]) {
        return false;
    }
    
    if ($HTMLTagAttributes) {
        // Match all of the tag's attributes.
        preg_match_all('/([a-zA-Z]+) = ((?(?=") "[^>"]*" | (?(?=\') \'[^>\']*\' | [^>\s\/]+ ) ))/x', $HTMLTag, $matches);
        
        $attributesNumber = count($matches[0]);
        for ($i = 0; $i != $attributesNumber; $i++) {
            $attributeName = $matches[1][$i];
            $attributeValue = $matches[2][$i];
            
            // Check every attribute with isApprovedAttribute().
            if ($attributeName && isset($attributeValue) && isApprovedAttribute($HTMLTagName, $attributeName, $attributeValue)) {
                $attributes[] = $attributeName . '=' . $attributeValue;
            }
        }
    }
    
    if ($attributes) {
        $attributes = ' ' . implode(' ', $attributes);
    }
    
    // Return the valid, approved tag.
    return '<' . $HTMLTagClose . $HTMLTagName . $attributes . $HTMLTagXMLClose . '>';
}

function isApprovedAttribute($HTMLTagName, $attributeName, $attributeValue) {
    global $APPROVED_HTML_TAGS;
    
    if (@in_array($attributeName, $APPROVED_HTML_TAGS[$HTMLTagName])) {
        return true;
    } elseif (is_array($APPROVED_HTML_TAGS[$HTMLTagName][$attributeName])) {
        $attributeValue = preg_replace('/^["\'](.*)["\']$/U', '$1', $attributeValue);
        if (in_array($attributeValue, $APPROVED_HTML_TAGS[$HTMLTagName][$attributeName])) {
            return true;
        }
    }
    
    return false;
}

##

// Added by Oren - 14/12/2010
// Facebook Cookie
function getFacebookCookie($app_id, $application_secret) {
    $args = array();
    parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
    ksort($args);
    $payload = '';
    foreach ($args as $key => $value) {
        if ($key != 'sig') {
            $payload .= $key . '=' . $value;
        }
    }
    if (md5($payload . $application_secret) != $args['sig']) {
      return null;
    }
    return $args;
}

##

function setGroupAssociatedDocIdx($group_id, $doc_id, $idx) {
    mysql_query("UPDATE type_group__associated_docs SET idx = '" . mysql_real_escape_string($idx) . "' WHERE group_id = '" . mysql_real_escape_string($group_id) . "' AND associated_doc_id = '" . mysql_real_escape_string($doc_id) . "'");
    return mysql_affected_rows() > 0;
}

function getMemberzones() {
    $memberzones = false;
    
    $sql_query = mysql_query("SELECT memberzone_id, title FROM type_memberzone");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $memberzones[] = $sql;
    }
    
    return $memberzones;
}

function getNewsletters() {
    $newsletters = false;
    
    $sql_query = mysql_query("SELECT newsletter_id, title FROM type_newsletter WHERE sent IS NOT NULL AND completed IS NOT NULL");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $newsletters[] = $sql;
    }
    
    return $newsletters;
}

function importCSV($file, $preview = false) {
    if (file_exists($file)) {
        $count = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                for ($j = 0; $j != count($data); $j++) {
                    //$data[$j] = trim(iconv('WINDOWS-1255', 'UTF-8', $data[$j]));
                    $data[$j] = $data[$j];
                }
                $result_array[$count] = $data;
                $count++;
                //echo '<pre>'; print_r($data); echo '</pre>';
            }
            fclose($handle);
        }
    }
    if ($preview) {
        echo '<pre>'; print_r($result_array); echo '</pre>';
        exit;   
    } else {
        return $result_array;
    }
}

function gyroLog($title, $type = false, $data = false) {
    
    $post = $_POST;
    $post['password'] = false;
    $session = $_SESSION;
    $session['variables'] = false;

    openlog('Gyro', LOG_PID | LOG_PERROR, LOG_LOCAL0);
    syslog($type ?: LOG_INFO, json_encode(array('TITLE' => $title, 'SERVER' => $_SERVER, 'POST' => $post, 'SESSION' => $session, 'DATA' => $data)));
    closelog();
}

?>
