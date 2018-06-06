<?php

$signUpNewMerchantRequest = (object) array(
    'company_name' => (object) array(
        'type' => 'string',
        'null' => true,
    ),
    'company_number' => (object) array(
        'type' => 'int',
        'null' => true,
    ),
    'email' => (object) array(
        'type' => 'string',
        'null' => true,
    ),
    'phone' => (object) array(
        'type' => 'int',
        'null' => true,
    ),
    'address_street' => (object) array(
        'type' => 'string',
        'null' => true,
    ),
    'address_city' => (object) array(
        'type' => 'string',
        'null' => true,
    ),
    'address_zip' => (object) array(
        'type' => 'int',
        'null' => true,
    ),
    'contact_name' => (object) array(
        'type' => 'string',
        'null' => true,
    )
);

$signUpNewMerchantResponse = (object) array(
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

function signUpNewMerchant($args) {
    unset($args->username);
    unset($args->password);
    
    if ($args && $args != (object) null) {
        require_once '/var/www/apps/phpmailer/class.phpmailer.php';
        
        $mail = new PHPMailer();
        $mail->From = 'no-reply-gts@pnc.co.il';
        $mail->FromName = 'PayWare IL';
        $mail->Subject = 'New Merchant Sign Up Request';
        $mail->AddAddress('i_sales_tlv@smokestack.verifone.com');
        
        ob_start();
        localscopeinclude('/var/www/sites/'.preg_replace('/([^:]+):?.*/', '$1', constant('GTS_APP_HOST')).'/emails-templates/new-merchant-sign-up-request.inc.php', $args);
        $mail->Body = ob_get_clean();
        
        $mail->IsSMTP();
        $mail->Host = '127.0.0.1';
        $mail->CharSet = 'UTF-8';
        $mail->IsHTML(true);
        
        ob_start();
        $result = $mail->Send();
        ob_get_clean();
        
        if ($result) {
            $result = (object) array(
                'result' => 'OKAY'
            );
        } else {
            $result = (object) array(
                'result' => 'FAIL',
                'error' => 71
            );
        }
    } else {
        $error = 70;
    }
    
    return (object) array(
        'result' => !$error ? 'OKAY' : 'FAIL',
        'error' => err($error) ?: null
    );
}

?>
