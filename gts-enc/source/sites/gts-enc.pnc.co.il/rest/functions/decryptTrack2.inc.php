<?php

$decryptTrack2Request = (object) array(
    'track2_sar_ksn' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'track2' => (object) array(
        'type' => 'string',
        'null' => false
    ),
    'test' => (object) array(
        'type' => 'boolean',
        'null' => false
    ),
);

$decryptTrack2Response = (object) array(
    'result' => (object) array(
        'type' => 'string',
        'value' => array('OKAY', 'FAIL'),
        'null' => false
    ),
    'error' => (object) array(
        'type' => 'string',
        'null' => true
    ),
    'data' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

require('./../restricted/phpseclib/Crypt/Base.php');
require('./../restricted/phpseclib/Crypt/DES.php');
require('./../restricted/phpseclib/Crypt/TripleDES.php');
require('./../restricted/DUKPT/DerivedKey.php');
require('./../restricted/DUKPT/KeySerialNumber.php');
require('./../restricted/DUKPT/Utility.php');

use DUKPT\DerivedKey;
use DUKPT\KeySerialNumber;
use DUKPT\Utility;

function decryptTrack2($args) {
    if (!connectDB()) {
        return new ErrObj('database-connection-error');
    }
    // Fetching the BDK
    if (strtoupper($args->test)) {
        $encrypted_bdk = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'bdk_test'"));
    } else {
        $encrypted_bdk = @mysql_fetch_object(mysql_query("SELECT value FROM `keys` WHERE `key` = 'bdk_prod'"));
    }
    if ($encrypted_bdk->value) {
        // For SELinux - make sure that "setsebool -P httpd_ssi_exec=1"
        $decrypted_bdk = aes_decrypt($encrypted_bdk->value);
        //$decrypted_track2 = shell_exec('./../restricted/ijack-decrypt '.escapeshellarg($decrypted_bdk).' '.escapeshellarg($args->track2_sar_ksn).' '.escapeshellarg($args->track2));
        
        $encryptedHexData = str_replace(array('<', '>', ' '), array('', '', ''), $args->track2);
        $ksn = str_replace(array('<', '>', ' '), array('', '', ''), $args->track2_sar_ksn);
    	$key = new KeySerialNumber($ksn);
        $encryptionKey = @DerivedKey::calculateDataEncryptionRequestKey($key, $decrypted_bdk);
        if (strtolower($args->encryption_mode) == 'cbc') {
            $mode = true;
        } else {
            $mode = false;
        }
        $decrypted_track2 = @Utility::removePadding(Utility::tripleDesDecrypt($encryptedHexData, $encryptionKey, $mode));
    	$decrypted_track2 = preg_replace('/b([0-9]+)d([0-9]+)f.*/', '$1=$2', $decrypted_track2);
    } else {
        $error = true;
    }
	
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null,
        //'debug' => htmlspecialchars('./../restricted/ijack-decrypt '.escapeshellarg($decrypted_bdk).' '.escapeshellarg($args->track2_sar_ksn).' '.escapeshellarg($args->track2)),
        'data' => $error ? null : (object) array(
            'track2' => (string) $decrypted_track2
        )
    );

}

?>
