<?php

    define('DB_SERVER', 'mysql');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '123456');
    define('DB_NAME', 'gts');
    
    define('SECRET', 'Le Patineur');

    define('AES_PASSPHRASE', 'skldaj48,scW1DPz)0');

    define('GTS_CON_SERVER_IP', '127.0.0.1');

    define('GTS_APP_HOST', 'gts-app');
    define('GTS_CON_HOST', 'gts-con');
    define('GTS_ENC_HOST', 'gts-enc.pnc.co.il');

    if ($_ENV['GTS_ENV'] === 'development') {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }
?>
