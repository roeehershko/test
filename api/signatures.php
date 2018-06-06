<?php

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	require('config.inc.php');
	require('core.inc.php');

	if (!connectDB($connectDB__error)) {
	    die($connectDB__error);
	}

	if (authSignature($_GET['trans_id']) == $_GET['auth'] && ($signatureData = @mysql_result(mysql_query("SELECT ts.signature FROM trans AS t LEFT JOIN trans_signatures ts USING (trans_id) WHERE t.trans_id = '" . mysql_real_escape_string($_GET['trans_id']) . "' AND t.type = 'credit' AND t.status != 'canceled'"), 0))) {
	    header('Content-Type: image/png');
	    exit(base64_decode($signatureData));
	} else {
	    header('HTTP/1.0 401 Unauthorized');
	    exit('Access denied; invalid invoice.');
	}

?>