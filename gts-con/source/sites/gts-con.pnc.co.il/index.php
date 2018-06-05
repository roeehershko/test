<?php

define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

define('VERSION', 'v1.4');

define('GTS_APP_HOST', $_ENV['APP_HOST']);
define('GTS_CON_HOST', $_ENV['HOSTNAME']);

define('GTS_APP_URL_SCHEME', 'verifonescheme');

define('GTS_APP_STORE_URL__VERIFONE', 'https://itunes.apple.com/il/app/payware-il/id441782581');
define('GTS_APP_STORE_URL__ISRACARD', 'https://itunes.apple.com/il/app/isracard-payware-ysr-krt/id557138589');

define('GTS_GOOGLE_PLAY_URL__VERIFONE', '#Intent;scheme=verifonescheme;package=com.verifone.payware_verifone;end');
define('GTS_GOOGLE_PLAY_URL__ISRACARD', '#Intent;scheme=verifonescheme;package=com.verifone.payware_isracard;end');

define('SMS_USERNAME', 'verifone_backoffice');
define('SMS_PASSWORD', '328577fXD.h');

define('AES_PASSPHRASE', 'AOldaj56:scW1DPz4');

// Development Variables
define('BOUNCE_HOST', $_ENV['HOSTNAME']);
define('HTTPS_URL', 'https://'.$_ENV['HOSTNAME'].'/');
define('HTTP_URL', 'http://'.$_ENV['HOSTNAME'].'/');
define('SITE_NAME', $_ENV['HOSTNAME']);
define('SITE_URL', 'http://'.$_ENV['HOSTNAME'].'/');

// --------------------
if ($_GET['mobile'] == 1 || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'iphone') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ipad') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ipod') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') !== false) {
	define('MOBILE', 1);
} else {
	define('MOBILE', 0);
}


include('global-handler.inc.php');

?>
