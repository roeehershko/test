<?php

$emailUserTransactionVoucherRequest = (object) array(
    'trans_id' => (object) array(
        'type' => 'int',
        'null' => false
    ),
    'recipients' => (object) array(
        'type' => 'array of string',
        'null' => true
    )
);

$emailUserTransactionVoucherResponse = (object) array(
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

// Added by Oren - 10/7/2012
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

function emailUserTransactionVoucher($args) {
    include_once 'functions/obtainUserTransactionDetails.inc.php';
    $args->include = array('credit_data', 'check_data', 'invoice_data', 'gyro_details', 'params');
    $transactionDetails = obtainUserTransactionDetails($args);
    
    if ($transactionDetails->result == 'FAIL') {
        $error = $transactionDetails->error;
    } elseif ($transactionDetails->data->type == 'credit' && $transactionDetails->data->credit_data->j5) {
        $error = '000 error sending voucher for j5 credit transaction';
    } else {
        if (is_array($args->recipients)) {
            for ($i = 0; $i != count($args->recipients); $i++) {
                if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $args->recipients[$i])) {
                    $error = 75;
                }
            }
        } else {
            $error = 75;
        }
        
        if (!$error) {
            list($merchant_id, $sender_email, $sender_name, $processor, $shva_transactions_merchant_number, $company_name, $company_id, $voucher_language) = mysql_fetch_row(mysql_query("SELECT merchant_id, sender_email, sender_name, processor, shva_transactions_merchant_number, company_name, company_id, voucher_language FROM merchants WHERE merchant_id = (SELECT merchant_id FROM trans WHERE trans_id = '" . mysql_real_escape_string($args->trans_id) . "')"));
            
            require_once '/var/www/apps/phpmailer/class.phpmailer.php';
            
            $mail = new PHPMailer();
            $mail->From = 'no-reply-gts@pnc.co.il';
            $mail->FromName = $sender_name ?: 'Gyro Transactions Server';
            $mail->Subject = 'Transaction Voucher';
            if ($sender_email) {
                $mail->AddReplyTo($sender_email);
            }
            for ($i = 0; $i != count($args->recipients); $i++) {
                $mail->AddAddress($args->recipients[$i]);
            }
            
            ob_start();
            localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/transaction-voucher.inc.php', array(
                'merchant_id' => $merchant_id,
                'processor' => $processor,
                'merchant_number' => $shva_transactions_merchant_number,
                'company_name' => $company_name,
                'company_id' => $company_id,
                'sender_email' => $sender_email,
                'voucher_language' => $voucher_language,
                'data' => $transactionDetails->data
            ));
            $voucher = ob_get_clean();
            $mail->Body = $voucher;
            
            $mail->IsSMTP();
            $mail->Host = '127.0.0.1';
            $mail->CharSet = 'UTF-8';
            $mail->IsHTML(true);
            
            ob_start();
            $sent = $mail->Send();
            ob_get_clean();
            
            if (!$sent) {
                $error = 76;
            } else {
	            // Deleting the voucher PDF, if exists
	            if (file_exists($voucher)) {
		            unlink($voucher);
	            }
            }
        }
    }

    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>