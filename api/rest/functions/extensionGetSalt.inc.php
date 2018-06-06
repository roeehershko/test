<?php

$extensionGetSaltRequest = (object) array(
    'type' => (object) array(
        'type' => 'string',
        'value' => array('lifestyle'),
        'null' => false
    )
);

$extensionGetSaltResponse = (object) array(
    'result' => (object) array(
        'type' => 'string',
        'value' => array('OKAY', 'FAIL'),
        'null' => false
    ),
	'data' => (object) array(
		'type' => 'object',
		'value' => (object) array(
			'salt' => (object) array(
                'type' => 'string',
                'null' => false
            )
		),
		'null' => false
	),
    'error' => (object) array(
        'type' => 'string',
        'null' => true
    )
);

function extensionGetSalt($args) {

	if (!$merchants = authenticateUser($args->username, $args->password, $authenticate__error)) {
        $error = err($authenticate__error, false, $args->lang);
	} else {

    }

    // Generate Daily Salt
    $salt = sha1(1);

    return (object) array(
		'result' => !$error ? 'OKAY' : 'FAIL',
		'error' => ($error ?: null),
		'data' => $error ? null : (object) array(
			'salt' => $salt
		)
	);

}

?>
