<?php
define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['APP_DB_NAME']);
    
define('SECRET', 'Le Patineur');

define('AES_PASSPHRASE', 'skldaj48,scW1DPz)0');

define('GTS_CON_SERVER_IP', $_ENV['CON_IP']);

define('GTS_APP_HOST', $_ENV['APP_HOST']);
define('GTS_CON_HOST', $_ENV['CON_HOST']);
define('GTS_ENC_HOST', 'gts-enc.pnc.co.il');

if( ! isset($_SERVER['HTTP_X_EXTERNAL_REQUEST'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
?>
