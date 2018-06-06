<?php
define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);
    
define('SECRET', 'Le Patineur');

define('AES_PASSPHRASE', 'skldaj48,scW1DPz)0');

define('GTS_CON_SERVER_IP', '127.0.0.1');

define('GTS_APP_HOST', $_ENV['HOSTNAME']);
define('GTS_CON_HOST', $_ENV['CON_HOST']);
define('GTS_ENC_HOST', $_ENV['ENC_HOST']);

if( ! isset($_SERVER['HTTP_X_EXTERNAL_REQUEST'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
?>
