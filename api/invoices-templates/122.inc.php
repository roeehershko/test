<?php
	
	//echo '<pre>Trans: '; print_r($trans); echo '</pre>';
	
	$timestamp = $trans->timestamp;
	$trans_id = $trans->trans_id;
	$invoice_number = $trans->invoice_data->number;
	$amount = $trans->amount;
	$currency = $trans->currency;
	
	$invoice_details = $trans->invoice_data->gyro_details;
	$invoice_pdf = $trans->invoice_data->link;
	$invoice_html = substr($trans->invoice_data->link, 0, -3).'html';
	
	$data = $trans;
	
	$strings['currency'] = $data->currency == 'ILS' ? '&#8362;' : ($data->currency == 'USD' ? '$' : '&euro;');
	
	if ($merchant_id == '1') {
		$language = 'english';
	} else if ($merchant_id == '120' || $merchant_id == '139') {
		$language = 'english';
	}
	
	if ($language == 'english') {
		$direction = 'ltr';
		$align = 'left';
		
		$strings['transaction_details'] = 'Transaction Details';
		$strings['transaction_type'] = 'Type';
		$strings['transaction_type__cash'] = 'Cash';
		$strings['transaction_type__check'] = 'Check';
		$strings['transaction_type__credit'] = 'Credit Card';
		$strings['timestamp'] = 'Timestamp';
		$strings['amount'] = 'Amount';
		
		$strings['payments_type'] = 'Payments Type';
		$strings['payments_number'] = 'Payments Number';
		
		$strings['card_details'] = 'Card Details';
		$strings['card_holder_name'] = 'Card Owner';
		$strings['last_4_digits'] = 'Last 4 Digits';
		$strings['expiration'] = 'Expiration';
		$strings['card_type'] = 'Card Type';
		$strings['approval_code'] = 'Approval Code';
		$strings['voucher_code'] = 'Voucher Code';
		$strings['reference_number'] = 'Reference Number';
		
		$strings['check_details'] = 'Check Details';
		$strings['check_number'] = 'Check Number';
		$strings['bank_number'] = 'Bank Number';
		$strings['branch_number'] = 'Branch Number';
		$strings['account_number'] = 'Account Number';
		
		$strings['invoice_details'] = 'Invoice Details';
		$strings['invoice_number'] = 'Invoice Number';
		$strings['invoice_customer_name'] = 'Customer Name';
		$strings['invoice_customer_number'] = 'ID / Company Number';
		$strings['invoice_address'] = 'Address';
		$strings['invoice_phone'] = 'Phone';
		$strings['invoice_description'] = 'Description';
		$strings['invoice_link'] = 'Click here to download the invoice';
		
		$strings['customer_signature'] = 'Customer Signature';
		
	} else {
		$direction = 'rtl';
		$align = 'right';
		
		$strings['transaction_details'] = 'פרטי העסקה';
		$strings['transaction_number'] = 'מספר עסקה';
		$strings['transaction_type'] = 'אמצעי תשלום';
		$strings['transaction_type__cash'] = 'מזומן';
		$strings['transaction_type__check'] = 'צ\'יק';
		$strings['transaction_type__credit'] = 'אשראי';
		$strings['timestamp'] = 'תאריך ושעה';
		$strings['amount'] = 'סכום';
		$strings['payments_type'] = 'סוג התשלומים';
		$strings['payments_number'] = 'מספר תשלומים';
		
		$strings['card_details'] = 'פרטי כרטיס אשראי';
		$strings['card_holder_name'] = 'בעל הכרטיס';
		$strings['last_4_digits'] = '4 ספרות אחרונות';
		$strings['expiration'] = 'תוקף';
		$strings['card_type'] = 'סוג הכרטיס';
		$strings['approval_code'] = 'מספר אישור';
		$strings['voucher_code'] = 'מספר שובר';
		$strings['reference_number'] = 'אסמכתא';
		
		$strings['check_details'] = 'פרטי הצ\'ק';
		$strings['check_number'] = 'מספר הצ\'יק';
		$strings['bank_number'] = 'מספר בנק';
		$strings['branch_number'] = 'מספר סניף';
		$strings['account_number'] = 'מספר חשבון';
		
		$strings['invoice_details'] = 'פרטי חשבונית-מס-קבלה';
		$strings['invoice_number'] = 'מספר חשבונית';
		$strings['invoice_customer_name'] = 'שם הלקוח';
		$strings['invoice_customer_number'] = 'ת.ז./ח"פ';
		$strings['invoice_address'] = 'כתובת';
		$strings['invoice_phone'] = 'טלפון';
		$strings['invoice_description'] = 'תיאור';
		$strings['invoice_link'] = 'לחצו כאן להורדת החשבונית';
		
		$strings['customer_signature'] = 'חתימת הלקוח';
		
	}
	
	function mb_strrev($str, $reverse_numbers = false, $flip_words = false) {
        if ($_GET['mode'] != 'html') {
			$str = str_replace('&#8362;','ILS', $str);
            $str = iconv('UTF-8', 'windows-1255', $str);
			$words_array = explode(' ', $str);
			if (!empty($words_array)) {
				$words_array = array_reverse($words_array);
				foreach ($words_array as $word) {
					if (!preg_match('/(^[\d\.,a-zA-Z]+)$/', $word)) {
						$hebrew_words_array[] = strrev($word);
					} elseif (!empty($hebrew_words_array)) {
						if (!empty($reversed_words_array)) {
							$reversed_words_array = array_merge($reversed_words_array, $hebrew_words_array);
						} else {
							$reversed_words_array = $hebrew_words_array;
						}
						$hebrew_words_array = false;
						$reversed_words_array[] = $word;
					} else {
						$reversed_words_array[] = $word;
					}
					
				}
				if (empty($reversed_words_array) && !empty($hebrew_words_array)) {
					// In case all words were Hebrew
					$reversed_words_array = $hebrew_words_array;
				} elseif (!empty($reversed_words_array) && !empty($hebrew_words_array)) {
					// In case the last word was in Hebrew
					$reversed_words_array = array_merge($reversed_words_array, $hebrew_words_array);
				}
				if (!empty($reversed_words_array)) {
					$letter_reversed = implode(' ', $reversed_words_array);
					$letter_reversed = str_replace('(', '#####', $letter_reversed);
					$letter_reversed = str_replace(')', '(', $letter_reversed);
					$letter_reversed = str_replace('#####', ')', $letter_reversed);
					$letter_reversed = iconv('windows-1255', 'UTF-8', $letter_reversed);
					$letter_reversed = str_replace('ILS','&#8362;', $letter_reversed);
				}
			}
			return $letter_reversed;
			/*
            if ($flip_words) {
                $letter_reversed_array = explode(' ', $letter_reversed);
                return join(' ',array_reverse($letter_reversed_array));
            } else {
                return $letter_reversed;
            }
			*/
        } else {
            return $str;
        }
    }
    
    if (!empty($strings)) {
    	foreach ($strings as $key => $value) {
    		$strings[$key] = mb_strrev($value);
    	}
    }
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="cache-control" content="0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <title><?=$invoice['number']?></title>
        <meta name="keywords" content="">
        <meta name="description" content="">
        
        <style type="text/css">
            @page {
                size: a4 portrait;
                margin-top: 0;
                margin-left: 1cm;
                margin-right: 1cm;
                margin-bottom: 1cm;
                /*
                @frame header {
                    -pdf-frame-content: headerContent;
                    margin-top: 1cm;
                    margin-left: 1cm;
                    margin-right: 1cm;
                }
                */
            }
            @font-face {
                font-family: arial;
                src: url(https://'.constant('GTS_APP_HOST').'/invoices-templates/fonts/arial.ttf);
            }
            html {
                font-size: 14px;
                line-height: 14px;
                direction: rtl;
                font-family: arial;
                padding-top:40px;
            }
            td, div, span {
                font-size: 14px;
                text-align: right;
                direction: rtl;
            }
			.small {
				font-size: 10px;
			}
			a {
                color: #000000;
            }
            .authenticated {
                width: 450px;
                border:1px solid #0C0;
                background:#0F9;
                padding:10px;
                font-size: 16px;
                font-weight: bold;
                text-align: center;
            }
            .fake {
                width: 210px;
                border:1px solid #930;
                background:#FFA6A8;
                padding:10px;
                font-size: 16px;
                font-weight: bold;
                text-align: center;
            }
            
            * {direction:<?=$direction?>}
        </style>
        
    </head>

    <body dir="<?=$direction?>">
		<div style="padding:20px;direction:<?=$direction?>;text-align:<?=$align?>" dir="<?=$direction?>">
			<div style="font-family:Arial;font-size:18px"><b><?=$strings['transaction_details']?></b> </div>
		
			<div style="font-family:Arial;font-size:14px"><?=$invoice_number?> :<b><?=$strings['transaction_number']?></b></div>
		
			<div style="font-family:Arial;font-size:14px"><?=$strings['transaction_type__'.$data->type]?> :<b><?=$strings['transaction_type']?></b></div>
			<div style="font-family:Arial;font-size:14px"><?=$data->timestamp?> :<b><?=$strings['timestamp']?></b></div>
			<div style="font-family:Arial;font-size:14px"><span dir="ltr" style="font-family:Arial;font-size:14px;direction:ltr"><?=$data->amount.'</span> '.$strings['currency']?> :<b><?=$strings['amount']?></b></div>
			<?
			if ($data->credit_data->payments_number) {
				?>
				<div style="font-family:Arial;font-size:14px"><?=mb_strrev($data->credit_data->payments_type)?> :<b><?=$strings['payments_type']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->payments_number?> :<b><?=$strings['payments_number']?></b></div>
				<?
			}
			?>
			<br>
			<?
			if ($data->type == 'credit') {
				?>
				<div style="font-family:Arial;font-size:16px"><b><?=$strings['card_details']?></b> </div>
				<div style="font-family:Arial;font-size:14px"><?=mb_strrev($data->credit_data->cc_holder_name)?> :<b><?=$strings['card_holder_name']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->cc_last_4?> :<b><?=$strings['last_4_digits']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->cc_exp?> :<b><?=$strings['expiration']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=ucfirst($data->credit_data->cc_type)?> :<b><?=$strings['card_type']?></b></div>
				<?
				if ($data->credit_data->authorization_number) {
					?>
					<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->authorization_number?> :<b><?=$strings['approval_code']?></b></div>
					<?
				}
				?>
				<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->voucher_number?> :<b><?=$strings['voucher_code']?></b></div>
				<?
			} else if ($data->type == 'check') {
				?>
				<div style="font-family:Arial;font-size:16px"><b><?=$strings['check_details']?></b> </div>
				<div style="font-family:Arial;font-size:14px"><?=$data->check_data->check_number?> :<b><?=$strings['check_number']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->check_data->bank_number?> :<b><?=$strings['bank_number']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->check_data->branch_number?> :<b><?=$strings['branch_number']?></b></div>
				<div style="font-family:Arial;font-size:14px"><?=$data->check_data->account_number?> :<b><?=$strings['account_number']?></b></div>
				<?
			}
			if ($data->credit_data->reference_number) {
				?>
				<div style="font-family:Arial;font-size:14px"><?=$data->credit_data->reference_number?> :<b><?=$strings['reference_number']?></b></div>
				<?
			}
			/*if ($data->invoice_data) {
				?>
				<br>
				<div style="font-family:Arial;font-size:16px"><b><?=$strings['invoice_details']?></b> </div>
				<?
				if ($data->invoice_data->number) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_number']?></b> <?=$data->invoice_data->number?></div>
					<?
				}
				if ($data->invoice_data->customer_name) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_customer_name']?></b> <?=$data->invoice_data->customer_name?></div>
					<?
				}
				if ($data->invoice_data->customer_number) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_customer_number']?></b> <?=$data->invoice_data->customer_number?></div>
					<?
				}
				if ($data->invoice_data->address_city) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_address']?></b> <?=$data->invoice_data->address_street.($data->invoice_data->address_city ? ', '.$data->invoice_data->address_city : false).($data->invoice_data->address_zip ? ', '.$data->invoice_data->address_zip : false) ?></div>
					<?
				}
				if ($data->invoice_data->phone) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_phone']?></b> <?=$data->invoice_data->phone?></div>
					<?
				}
				if ($data->invoice_data->description) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_description']?></b> <?=$data->invoice_data->description?></div>
					<?
				}
				?>
				<div style="font-family:Arial;font-size:14px"><a href="<?=$data->invoice_data->link?>"><?=$strings['invoice_link']?></a></div>
				<?
			}*/
			if ($data->credit_data->signature_link) {
				?>
				<br>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['customer_signature']?></b></div>
				<div style="font-family:Arial;font-size:14px;padding:10px"><img src="<?=$data->credit_data->signature_link?>"></div>
				<?
			}
			?>
		</div>
	</body>
</html>
