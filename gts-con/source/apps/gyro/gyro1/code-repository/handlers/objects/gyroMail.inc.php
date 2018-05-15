<?php

require '/var/www/apps/phpmailer/class.phpmailer.php';

class gyroMail extends PHPMailer {
    function gyroMail() {
        $this->From = constant('OUTGOING_EMAILS_FROM_EMAIL');
        $this->FromName = constant('OUTGOING_EMAILS_FROM_NAME');
        $this->IsSMTP();
        $this->Host = '127.0.0.1'; // mail.pnc.co.il
        $this->CharSet = 'utf-8';
        $this->IsHTML(true);
    }
    
    function Send() {
        $key = 'Concerto pour une Voix';
        $input = trim($this->to[0][0]); // Note: this requires that the member `$to` is defined as `protected` in `class.phpmailer.php`.
        
        // Encrypt the email and
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');             // ..open the cipher..
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND); // ..create the IV..
        mcrypt_generic_init($td, $key, $iv);                              // ..intialize encryption..
        $encrypted = @mcrypt_generic($td, $input);                        // ..encrypt data..
        mcrypt_generic_deinit($td);                                       // ..terminate encryption handler..
        mcrypt_module_close($td);                                         // ..close module.
        
        $this->Sender = $this->urlsafe_b64encode($encrypted) . '@' . constant('BOUNCE_HOST');
        
        // Fix a bug in PHPMailer that sometimes breaks a line in the middle of a multibyte character (due to a problematic word-wrap function).
        $this->Body = wordwrap($this->Body, 100, "\n");
        
        $return = parent::Send();
        
        return $return;
    }
    
    function urlsafe_b64encode($input) {
        return preg_replace('/^\-/', 'AAA', strtr(base64_encode($input), '/=', '-_'));
    }
}

?>