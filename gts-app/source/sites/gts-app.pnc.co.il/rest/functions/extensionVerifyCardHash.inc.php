<?php

$extensionVerifyCardHashRequest = (object) array(
    'type' => (object) array(
        'type' => 'string',
        'value' => array('lifestyle'),
        'null' => false
    ),
    'card_hash' => (object) array(
        'type' => 'string',
        'null' => false
    )
);

$extensionVerifyCardHashResponse = (object) array(
    'result' => (object) array(
        'type' => 'string',
        'value' => array('OKAY', 'FAIL'),
        'null' => false
    ),
    'error' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

function extensionVerifyCardHash($args) {

	if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error, false, $args->lang);
	} else {

    }

    // Verify Hash
    //$card_hash = @mysql_result(mysql_query("SELECT hash FROM extension_cards_hash_lifestyle WHERE hash = '" . $args->card_hash . "'"), 0);
    $whitelist_location = '/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/extensions/lifestyle/whitelists/pending/amexlifestylewhitelist.20170205.txt';
    $hashes = file_get_contents($whitelist_location);
    $hashes = explode("\r\n", $hashes);

    $hashes[] = '66185270075CA1BBA18919D3D084C9EF';
    $hashes[] = md5('5326101312546429');
    $hashes[] = md5('375511291232125');
    $hashes[] = md5('375511291232190');
    $hashes[] = md5('375513290421196');

	//H=MD5(MD5(PAN_NUMBER)||S)

	$found = false;
	foreach ($hashes as $hash) {
		if ($args->card_hash == md5(trim($hash).sha1(1))) {
			$found = true;
			break;
		}
	}

    if ($found) {
        // Success
    } else {
        $error = err(212, false, $args->lang);
    }

	return (object) array(
		'result' => !$error ? 'OKAY' : 'FAIL',
		'error' => ($error ?: null),
		'debug' => array(
            'md5(pan) - Original hash by Lifestyle: ' => md5('375511291232125'),
            'GTS Salt' => sha1(1),
            'md5(pan)+salt - Concatinating the salt to the md5(pan): ' => md5('5326101312546429').sha1(1),
            'md5(md5(pan)+salt) - Hash with salt and after md5' => md5(md5('5326101312546429').sha1(1)),
            'Hash that was sent in the request' => $args->card_hash,
            'Are they identical?' => ($args->card_hash == md5(md5('5326101312546429').sha1(1))) ? 'YES!' : 'NO...'
            /*
            'Original hash by Lifestyle' => '66185270075CA1BBA18919D3D084C9EF',
            'Hash after adding the salt' => '66185270075CA1BBA18919D3D084C9EF'.sha1(1),
            'Hash with salt and after md5' => md5('66185270075CA1BBA18919D3D084C9EF'.sha1(1)),
            'Hash that was sent in the request' => $args->card_hash,
            'Are they identical?' => ($args->card_hash == md5('66185270075CA1BBA18919D3D084C9EF'.sha1(1))) ? 'YES!' : 'NO...'
            */
        )
	);

}

?>
