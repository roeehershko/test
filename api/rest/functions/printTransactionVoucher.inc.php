<?php

$printTransactionVoucherRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    )
);

$printTransactionVoucherResponse = (object) array(
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

// Added by oren - 10/7/2012
// Doc Raptor - PRINCE - html2pdf
function convert_to_pdf($document_content, $file_name = false, $test = false) {
	$response = @file_get_contents('https://docraptor.com/docs?user_credentials=95gWBkqAtpdvRLTmfOU', false, stream_context_create(array(
		'http' => array (
			'method' => 'POST',
			'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
			'content' => http_build_query(array(
				'doc[document_content]' => $document_content, 
	            'doc[document_type]'    => 'pdf',
	            'doc[name]'             => 'voucher.pdf',
	            'doc[test]'             => ($test ? 'true' : false)
			))
		)
	)));
	if ($response && $file_name) {
		$path = '/tmp/'.$file_name;
		$file = fopen ($path, 'w'); 
		fwrite($file, $response); 
		fclose ($file);
		return $path;
	} else if ($response) {
		return $response;
	} else {
		return false;
	}
}

function printTransactionVoucher($args) {
    include_once 'functions/obtainTransactionDetails.inc.php';
    $args->include = array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params');
    $transactionDetails = obtainTransactionDetails($args);
    
    if ($transactionDetails->result == 'FAIL') {
        $error = $transactionDetails->error;
    } elseif ($transactionDetails->data->type == 'credit' && $transactionDetails->data->credit_data->j5) {
        $error = '000 error printing voucher for j5 credit transaction';
    } else {
        if (!$error) {
            list($merchant_id, $sender_email, $sender_name, $shva_transactions_merchant_number, $company_name, $company_id) = mysql_fetch_row(mysql_query("SELECT merchant_id, sender_email, sender_name, shva_transactions_merchant_number, company_name, company_id FROM merchants WHERE merchant_id = (SELECT merchant_id FROM trans WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "')"));
            
            ob_start();
            localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/transaction-voucher.inc.php', array(
                'merchant_id' => $merchant_id,
                'merchant_number' => $shva_transactions_merchant_number,
                'company_name' => $company_name,
                'company_id' => $company_id,
                'data' => $transactionDetails->data
            ));
            $voucher = ob_get_clean();
            if ($voucher = convert_to_pdf($voucher)) {
	        	header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename=voucher.pdf');
				echo $voucher;
				exit;
				
				$printed = true;    
            }
            
            if (!$printed) {
                $error = 76;
            }
        }
    }

    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>