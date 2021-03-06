<?php
		
	/* Notes:
	 * 6/5/2009
	 * PDF conversion is done via xhtml2pdf (pisa v.3.0.31)
	 * When using <img...> with xhhtml2pdf the WIDTH and HEIGHT must NOT be set via 'style', but with labels. For example: <img src="..." width="20" height="20".
	 * Also, for some reason, images appear ONLY when inside TABLE.
	*/
	
	if ($_GET['debug']) {
		//echo '<pre dir="ltr" style="direction:ltr">Invoice Details: '; print_r($invoice_details); echo '</pre>';
		//echo '<pre dir="ltr" style="direction:ltr">Parameters: '; print_r($params); echo '</pre>';
		echo '<pre>'; print_r($trans); echo '</pre>';
		exit;
	}
	
	$strings['original'] = 'מקור';
	$strings['vat_number'] = 'ח"פ';
	$strings['phone'] = 'טלפון';
	if (!$strings['invoice']) {
		if (abs($amount) == $amount) {
		$strings['invoice'] = 'חשבונית-מס';
		} else {
			$strings['invoice'] = 'חשבונית-מס זיכוי';
		}	
	}
	$strings['receipt'] = 'קבלה';
	$strings['description'] = 'תיאור';
	$strings['price_per_unit'] = 'מחיר ליחידה';
	$strings['quantity'] = 'כמות';
	$strings['before_vat'] = 'לפני מע"מ';
	$strings['vat'] = 'מע"מ';
	$strings['price'] = 'מחיר';
	$strings['voucher'] = 'מס. שובר';
	$strings['payment_method'] = 'תשלום';
	$strings['biz_name'] = 'הלקוח';
	$strings['cc_exp'] = 'תפוגה';
	$strings['cc_last4'] = '4 ספרות';
	$strings['cc_type'] = 'כרטיס';
	$strings['date'] = 'תאריך';
	$strings['amount'] = 'סכום';
	$strings['total'] = 'סה"כ';
	$strings['signed_by'] = 'על החתום:';
	$strings['authentication_successful'] = 'מסמך דיגיטלי זה אומת בהצלחה';
	$strings['authentication_failed'] = 'המסמך מזויף!';
	$strings['digital_document'] = 'זהו מסמך ממוחשב, החתום דיגיטלית. לאימות מסמך זה';
	$strings['click_here'] = 'לחצו כאן';
	$strings['download_as_pdf'] = 'הורד כמסמך PDF';
	$strings['to'] = 'לכבוד: ';
	$strings['handling'] = 'אריזה';
	$strings['delivery'] = 'משלוח';
	$strings['tracking_number'] = 'מספר זיהוי עסקה: ';
	
	$strings['customer_name'] = 'לקוח';
	$strings['customer_number'] = 'ת.ז./ח"פ';
	$strings['customer_phone'] = 'טלפון';
	
	$strings['bank'] = 'בנק';
	$strings['branch'] = 'סניף';
	$strings['account'] = 'חשבון';
	$strings['check_number'] = 'מספר צ\'ק';
	
	/* ## */
	
	if (!$_GET['token']) {
	    foreach ($strings as $key => $string) {
	        $strings[$key] = mb_strrev($string);
	        $strings['cc_last4'] = $_GET['mode'] != 'html' ? 'תורפס 4' : '4 ספרות';
        }
    }
    
    /* ## */
    
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
	
	function coin($amount, $currency) {
		if ($currency == 'USD') {
			$currency = '$';
		} else if ($currency == 'EUR') {
			$currency = '&euro;';
		}
		if (!$_GET['token']) {
			return ($currency == 'ILS' ? mb_strrev('&#8362;', false, false) : $currency).number_format($amount, 2);
		} else {
			return number_format($amount, 2).($currency == 'ILS' ? mb_strrev('&#8362;', false, false) : $currency);
		}
	}
	
	/* ## */
	
	$timestamp = $trans->timestamp;
	$trans_id = $trans->trans_id;
	$invoice_number = $trans->invoice_data->number;
	$amount = $trans->amount;
	$currency = $trans->currency;
	
	$invoice_details = $trans->invoice_data->gyro_details;
	$invoice_pdf = $trans->invoice_data->link;
	$invoice_html = substr($trans->invoice_data->link, 0, -3).'html';
	
	$payments_type = $trans->credit_data->credit_terms;
	if ($trans->credit_data->payments_number > 0) {
		$payments_number = $trans->credit_data->payments_number - 1;
		$payments_standing_amount = floor($trans->amount / $trans->credit_data->payments_number);
   		$payments_foremost_amount = $payments_standing_amount + fmod($trans->amount, $trans->credit_data->payments_number);
   	}
	
	$cc_holder_name = $trans->credit_data->cc_holder_name;
	$cc_type = $trans->credit_data->cc_type;
	$cc_last4 = $trans->credit_data->cc_last_4;
	$cc_exp = $trans->credit_data->cc_exp;
	$voucher_number = $trans->credit_data->voucher_number;
	$reference_number = $trans->credit_data->reference_number;
	
	$params = json_decode(json_encode($trans->params), true);
	$params['cc_full_name'] = $params['cc_full_name'] ?: $cc_holder_name;
	
	if (!$params['name']) {
		$params['name'] = $trans->invoice_data->customer_name;
	}
	if (!$params['description']) {
		$params['description'] = $trans->invoice_data->description;
	}
	
	if ($_GET['debug']) {
		echo '<pre dir="ltr" style="direction:ltr;text-align:left">'; print_r($trans); echo '</pre>';
		//exit;
	}
	
	/* ## */
	
	$invoice['number'] = $invoice_number;
	$invoice['id'] = $trans_id;
	$invoice['timestamp'] = date('d-m-Y H:m:s', strtotime($timestamp));
	
	if (abs($amount) == $amount) {
		$invoice['title'] = 'חשבונית-מס קבלה';
	} else {
		$invoice['title'] = 'חשבונית-מס זיכוי';
	}
	
	if (!$_GET['token']) { $invoice['title'] = mb_strrev($invoice['title']); }
	
	if (empty($invoice['company'])) {
		$invoice['company']['details'] = 'בדיקה בלבד בע"מ, ח"פ 513772186, רח\' החשמונאים 88, תל-אביב 20216, ת.ד. 52202, טלפון: 035620024';
		if (!$_GET['token']) {
			$invoice['company']['details'] = 'בדיקה בלבד בע"מ, ח"פ 681277315, רח\' החשמונאים 88, תל-אביב 61202, ת.ד. 20225, טלפון: 035620024';
		}
		$invoice['company']['contact_person'] = 'בדיקה בלבד!';
		$invoice['company']['contact_person_title'] = 'חשבונית לבדיקה בלבד';
		$invoice['company']['logo'] = 'https://secure.pnc.co.il/paragon-creations.com/files/images/invoice/header.png';
	}
	
	if (!$_GET['token']) {
		$invoice['company']['details'] = mb_strrev($invoice['company']['details'], true); 
	}
	if (!$_GET['token']) { $invoice['company']['contact_person'] = mb_strrev($invoice['company']['contact_person']); }
	if (!$_GET['token']) { $invoice['company']['contact_person_title'] = mb_strrev($invoice['company']['contact_person_title']); }
	
	/* ## */
	
	$invoice['vat'] = 1; // We start with vat = 1, and change it if the products in $invoice_details include the VAT or if we need to use only $amount
	$vat_required = false;
	
	$count = 0;
	if ($invoice_details) {
		
		// If we got $invoice_details it means we have invoice-details from Gyro
		$invoice_details = unserialize($invoice_details);
		
		// Gyro Tracking Number
		if ($invoice_details['tracking_number']) {
			if (!$_GET['token']) {
				$invoice['client']['tracking_number'] = $invoice_details['tracking_number'].'<b>'.$strings['tracking_number'].'</b> ';
			} else {
				$invoice['client']['tracking_number'] = '<b>'.$strings['tracking_number'].'</b> '.$invoice_details['tracking_number'];
			}
		}
		
		// Gyro Billing Details
		if (!empty($invoice_details['billing_address'])) {
			if ($invoice_details['billing_address']['company']) {
				$invoice['client']['details'] = stripslashes($invoice_details['billing_address']['company']);
				if (!$_GET['token'] && $invoice['client']['details'] && preg_match('/([^a-zA-Z]+)/', $invoice['client']['details'])) { $invoice['client']['details'] = mb_strrev($invoice['client']['details']); }
			} else {
				$invoice['client']['details'] = stripslashes($invoice_details['billing_address']['first_name'].' '.$invoice_details['billing_address']['last_name']);
				if (!$_GET['token'] && $invoice['client']['details'] && preg_match('/([^a-zA-Z]+)/', $invoice['client']['details'])) { $invoice['client']['details'] = mb_strrev($invoice['client']['details']); }
			}
		}
		
		if (!empty($invoice_details['sub_carts'])) {
			// Gyro send PRODUCTS details
			foreach ($invoice_details['sub_carts'] as $sub_cart) {
				if (!empty($sub_cart['products'])) {
					foreach ($sub_cart['products'] as $product) {
						$invoice['items'][$count]['description'] = stripslashes($product['title']);
						if (!$_GET['token']) { $invoice['items'][$count]['description'] = mb_strrev($invoice['items'][$count]['description']); }
						// NOTE: The item price does NOT INCLUDE the VAT.
						$invoice['items'][$count]['price'] = $product['price_each'];
						$invoice['items'][$count]['quantity'] = $product['quantity'];
						$count++;
					}
				}
				if ($sub_cart['shipping_method']['handling_cost']) {
					$invoice['items'][$count]['description'] = stripslashes($sub_cart['shipping_method']['title'].' - '.$strings['handling']);
					if (!$_GET['token']) { $invoice['items'][$count]['description'] = mb_strrev($invoice['items'][$count]['description']); }
					$invoice['items'][$count]['price'] = $sub_cart['shipping_method']['handling_cost'];
					$invoice['items'][$count]['quantity'] = 1;
					$count++;
				}
				if ($sub_cart['shipping_method']['delivery_cost']) {
					$invoice['items'][$count]['description'] = stripslashes($sub_cart['shipping_method']['title'].' - '.$strings['delivery']);
					if (!$_GET['token']) { $invoice['items'][$count]['description'] = mb_strrev($invoice['items'][$count]['description']); }
					$invoice['items'][$count]['price'] = $sub_cart['shipping_method']['delivery_cost'];
					$invoice['items'][$count]['quantity'] = 1;
					$count++;
				}
				if (!empty($sub_cart['shipping_method']['additional_costs'])) {
					foreach ($sub_cart['shipping_method']['additional_costs'] as $additional_cost) {
						if (strpos(strtolower($additional_cost['title']), 'vat') === false) {
							// NOTE: If the additional cost represents VAT - we don't include it in the invoice description.
							$invoice['items'][$count]['description'] = $additional_cost['title'];
							$invoice['items'][$count]['price'] = $additional_cost['price'];
							$count++;
						} else {
							$vat_required = true;
						}
					}
				} else {
					$vat_required = true;
				}
			}
			
			if ($invoice_details['coupon_discount']) {
				$invoice['items'][$count]['description'] = $invoice_details['coupon_title'];
				if (!$_GET['token']) { $invoice['items'][$count]['description'] = mb_strrev($invoice['items'][$count]['description']); }
				$invoice['items'][$count]['price'] = $invoice_details['coupon_discount'];
				$invoice['items'][$count]['quantity'] = 1;
				$count++;
			}
			
			if ($vat_required && !$without_vat_override) {
				if (strtotime($timestamp) >= strtotime('2015-10-01')) {
					$invoice['vat'] = 1.17;
				} else if (strtotime($timestamp) >= strtotime('2013-06-02')) {
					$invoice['vat'] = 1.18;
				} else if (strtotime($timestamp) >= strtotime('2012-09-01')) {
					$invoice['vat'] = 1.17;
				} else if (strtotime($timestamp) >= strtotime('2010-01-01')) {
					$invoice['vat'] = 1.16;
				} else if (strtotime($timestamp) >= strtotime('2009-07-01')) {
					$invoice['vat'] = 1.165;
				} else {
					$invoice['vat'] = 1.155;
				}
				// Foreach product we need to check if VAT is required or not, and change its price accordingly
				$count = 0;
				foreach ($invoice_details['sub_carts'] as $sub_cart) {
					if (!empty($sub_cart['products'])) {
						foreach ($sub_cart['products'] as $product) {
							$invoice['items'][$count]['price'] = $invoice['items'][$count]['price'] / $invoice['vat'];
							$count++;
						}
					}
					if ($sub_cart['shipping_method']['handling_cost']) {
						$invoice['items'][$count]['price'] = $invoice['items'][$count]['price'] / $invoice['vat'];
						$count++;
					}
					if ($sub_cart['shipping_method']['delivery_cost']) {
						$invoice['items'][$count]['price'] = $invoice['items'][$count]['price'] / $invoice['vat'];
						$count++;
					}
					if (!empty($sub_cart['shipping_method']['additional_costs'])) {
						foreach ($sub_cart['shipping_method']['additional_costs'] as $additional_cost) {
							if (strpos(strtolower($additional_cost['title']), 'vat') === false) {
								$invoice['items'][$count]['price'] = $invoice['items'][$count]['price'] / $invoice['vat'];
								$count++;
							}
						}
					}
				}
				if ($invoice_details['coupon_discount']) {
					$invoice['items'][$count]['price'] = $invoice_details['coupon_discount'] / $invoice['vat'];
					$count++;
				}
			}
			
		} else {
			// Gyro send CREDITS details
			// If the billing details indicate that the country is Israel, we need to define the VAT
			if (!$invoice_details['billing_address']['country'] || $invoice_details['billing_address']['country'] == 'Israel') {
				$vat_required = true;
			}
			if ($vat_required && !$without_vat_override) {
				if (strtotime($timestamp) >= strtotime('2015-10-01')) {
					$invoice['vat'] = 1.17;
				} else if (strtotime($timestamp) >= strtotime('2013-06-02')) {
					$invoice['vat'] = 1.18;
				} else if (strtotime($timestamp) >= strtotime('2012-09-01')) {
					$invoice['vat'] = 1.17;
				} else if (strtotime($timestamp) >= strtotime('2010-01-01')) {
					$invoice['vat'] = 1.16;
				} else if (strtotime($timestamp) >= strtotime('2009-07-01')) {
					$invoice['vat'] = 1.165;
				} else {
					$invoice['vat'] = 1.155;
				}
			}
			$invoice['items'][$count]['description'] = 'Credits purchase';
			$invoice['items'][$count]['price'] = $amount / $invoice['vat'];
			$invoice['items'][$count]['quantity'] = 1;
			$count++;
		}
		
	} else {
		
		if (!$without_vat_override) {
			if (strtotime($timestamp) >= strtotime('2015-10-01')) {
				$invoice['vat'] = 1.17;
			} else if (strtotime($timestamp) >= strtotime('2013-06-02')) {
				$invoice['vat'] = 1.18;
			} else if (strtotime($timestamp) >= strtotime('2012-09-01')) {
				$invoice['vat'] = 1.17;
			} else if (strtotime($timestamp) >= strtotime('2010-01-01')) {
				$invoice['vat'] = 1.16;
			} else if (strtotime($timestamp) >= strtotime('2009-07-01')) {
				$invoice['vat'] = 1.165;
			} else {
				$invoice['vat'] = 1.155;
			}
		}
		
		// No details from Gyro are available
		// We use $params
		if ($params['company']) {
			$invoice['client']['details'] = stripslashes($params['company'].($params['company_number'] ? ', '.$params['company_number'] : false));
			if (!$_GET['token'] && $params['company'] && preg_match('/([^a-zA-Z]+)/', $params['company'])) { $invoice['client']['details'] = mb_strrev($invoice['client']['details']); }
		} elseif ($params['name']) {
			$invoice['client']['details'] = stripslashes($params['name']);
			if (!$_GET['token'] && $params['name'] && preg_match('/([^a-zA-Z]+)/', $params['name'])) { $invoice['client']['details'] = mb_strrev($invoice['client']['details']); }
		}
		
		// We only have the $amount from the GTS
		$invoice['items'][$count]['description'] = stripslashes($params['description']);
		if (!$_GET['token']) { $invoice['items'][$count]['description'] = mb_strrev($invoice['items'][$count]['description']); }
		$invoice['items'][$count]['price'] = $amount / $invoice['vat']; // The $amount already includes the VAT, so we need to remove it.
		$invoice['items'][$count]['quantity'] = 1;
		$count++;
		
	}
	
	/* ## */
	
	if (($payments_type == 'payments' || $payments_type == 'payments-club') && $payments_number >= 1) {
		$invoice['receipt']['payment_method'] = 'תשלומים';
	} else if ($payments_type == 'supercredit' && $payments_number >= 1) {
		$invoice['receipt']['payment_method'] = 'סופר-קרדיט';
	} else if ($payments_type == 'payments-credit' && $payments_number >= 1) {
		$invoice['receipt']['payment_method'] = 'תשלומי קרדיט';
	} else if ($trans->type == 'check') {
		$invoice['receipt']['payment_method'] = 'צ\'ק';
	} else if ($trans->type == 'cash') {
		$invoice['receipt']['payment_method'] = 'מזומן';
	} else {
		$invoice['receipt']['payment_method'] = 'כ"א';
	}
	if (!$_GET['token']) { $invoice['receipt']['payment_method'] = mb_strrev($invoice['receipt']['payment_method']); }
	
	$invoice['receipt']['voucher'] = $voucher_number;
	$invoice['receipt']['payments_number'] = $payments_number+1; // The $payments_number does NOT include the first payment
	$invoice['receipt']['payments_foremost_amount'] = $payments_foremost_amount;
	$invoice['receipt']['payments_standing_amount'] = $payments_standing_amount;
	$invoice['receipt']['cc_full_name'] = $params['cc_full_name'];
	if (!$_GET['token']) { $invoice['receipt']['cc_full_name'] = mb_strrev($invoice['receipt']['cc_full_name']); }
	$invoice['receipt']['cc_type'] = $cc_type;
	$invoice['receipt']['cc_last4'] = $cc_last4;
	$invoice['receipt']['cc_exp'] = $cc_exp;
	$invoice['receipt']['timestamp'] = date('d-m-Y H:m:s', strtotime($timestamp));
	
	/* ## */
	
	$authenticated = false;
	if ($_GET['token'] == sha1('Maya Kedem'.$voucher_number.$amount)) {
	    $authenticated = 'yes';
    }
    if ($_GET['token'] && $_GET['token'] != sha1('Maya Kedem'.$voucher_number.$amount)) {
	    $authenticated = 'no';
    }
    
    //echo '<pre>'; print_r($_GET); echo '</pre>';
    
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
                src: url(https://<?=constant('GTS_APP_HOST')?>/invoices-templates/fonts/arial.ttf);
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
        </style>
        
    </head>

    <body>
        <center>
            <br><br>
            <div style="width:760px;direction:rtl;text-align:right;font-size:14px">
			    
			    <table dir="ltr" style="width:100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="vertical-align: top;"><div style="text-align:left"><img src="<?=$invoice['company']['logo']?>" height="90"></div></td>
                        <td style="vertical-align: top;">
                            <div style="font-size: 16px; line-height: 20px; font-weight: bold;"><?=$strings['original']?></div>
                            <div><?=$invoice['timestamp']?></div>
                        </td>
                    </tr>
                </table>
                <br>
				<div><?=$invoice['company']['details']?></div>
                <br><br>
				
                <? if (!$_GET['token']) { ?>
                    <div style="font-weight: bold; font-size: 18px; line-height: 22px;"><span style="font-size: 16px; line-height: 22px;">[<?=$invoice['number']?>]</span> <?=$invoice['title']?></div>
                <? } else { ?>
                    <div style="font-weight: bold; font-size: 18px; line-height: 22px;"><?=$invoice['title']?> <span style="font-size: 16px; line-height: 22px;">[<?=$invoice['number']?>]</span></div>
                <? } ?>
                
                <? if (!$_GET['token']) { ?>
					<div><?=$invoice['client']['details']?><b><?=$strings['to']?></b> </div>
                <? } else { ?>
					<div><b><?=$strings['to']?></b> <?=$invoice['client']['details']?></div>
                <? } ?>
                <? if ($invoice['client']['tracking_number']) { ?>
					<div><?=$invoice['client']['tracking_number']?></div>
				<? } ?>
				<br><br>
                
                <? if ($authenticated == 'yes') { ?>
                    <center><div class="authenticated"><?=$strings['authentication_successful']?></a></div></center>
                    <br><br>
                <? } elseif ($authenticated == 'no') { ?>
                    <center><div class="fake"><?=$strings['authentication_failed']?></a></div></center>
                    <br><br>
                <? } ?>
                
                <div style="font-weight: bold; font-size: 18px; line-height: 18px; padding: 0 0 10px 0; margin: 0 0 12px 0; border-bottom: 1px dotted #333333;"><?=$strings['invoice']?></div>
                <table dir="ltr" style="width: 100%;" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><b><?=$strings['price']?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b><?=$strings['vat']?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b><?=$strings['before_vat']?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b><?=$strings['quantity']?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b><?=$strings['price_per_unit']?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td style="width:25%"><b><?=$strings['description']?></b></td>
                    </tr>
                    <tr><td colspan="11"><div style="border-bottom: 1px dotted #999999; height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
                    <tr><td colspan="11"><div style="height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
                    <?php
                        $invoice['quantity'] = false;
                        $invoice['total_without_vat'] = false;
                        $invoice['total'] = false;
                        foreach ($invoice['items'] as $item) {
                            ?>
                            <tr>
                                <td><div dir="ltr" style="direction:ltr"><?=coin($item['price'] * $item['quantity'] * $invoice['vat'], $currency)?></div></td>
                                <td><div style="width: 10px;"></div></td>
                                <td><div><?=$invoice['vat'] == 1 ? '0' : coin($item['price'] * $item['quantity'] * ($invoice['vat'] - 1), $currency)?></div></td>
                                <td><div style="width: 10px;"></div></td>
                                <td><div dir="ltr" style="direction:ltr"><?=coin($item['price'] * $item['quantity'], $currency)?></div></td>
                                <td><div style="width: 10px;"></div></td>
                                <td><div><?=$item['quantity']?></div></td>
                                <td><div style="width: 10px;"></div></td>
                                <td><div dir="ltr" style="direction:ltr"><?=coin($item['price'], $currency)?></div></td>
                                <td><div style="width: 10px;"></div></td>
                                <td><div><?=$item['description']?></div></td>
                            </tr>
                            <tr><td colspan="11"><div style="border-bottom: 1px dotted #999999; height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
                            <tr><td colspan="11"><div style="height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
                            <?
							$invoice['quantity'] = $invoice['quantity'] + $item['quantity'];
							$invoice['total_without_vat'] = $invoice['total_without_vat'] + ($item['price'] * $item['quantity']);
							$invoice['total'] = $invoice['total'] + ($item['price'] * $item['quantity'] * $invoice['vat']);
						}
                    ?>
                    <tr>
                        <td><b dir="ltr" style="direction:ltr"><?=coin($invoice['total'], $currency)?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b><?=$invoice['vat'] == 1 ? '0' : coin($invoice['total_without_vat'] * ($invoice['vat'] - 1), $currency)?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><b dir="ltr" style="direction:ltr"><?=coin($invoice['total_without_vat'], $currency)?></b></td>
                        <td><div style="width: 10px;"></div></td>
                        <td><div><?=$invoice['quantity']?></div></td>
                        <td colspan="4"><b dir="ltr" style="direction:ltr"><?=$strings['total']?></b></td>
                    </tr>
                </table>
                <br><br>
				<? if (abs($amount) == $amount) { ?>
					<div style="font-weight: bold; font-size: 18px; line-height: 18px; padding: 0 0 10px 0; margin: 0 0 12px 0; border-bottom: 1px dotted #333333;"><?=$strings['receipt']?></div>
					<table dir="ltr" style="width: 100%;" cellspacing="0" cellpadding="0">
						<tr>
							<td><b><?=$strings['amount']?></b></td>
							<td><div style="width: 10px;"></div></td>
							<td><b><?=$strings['date']?></b></td>
							<td><div style="width: 10px;"></div></td>
							<?
							if ($trans->type == 'cash') {
								?>
								<td><b><?=$strings['customer_phone']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['customer_number']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['customer_name']?></b></td>
								<?
							} else if ($trans->type == 'check') {
								?>
								<td><b><?=$strings['check_number']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['account']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['branch']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['bank']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['customer_number']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['customer_name']?></b></td>
								<?
							} else {
								?>
								<td><b><?=$strings['cc_exp']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['cc_last4']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['cc_type']?></b></td>
								<td><div style="width: 10px;"></div></td>
								<td><b><?=$strings['voucher']?></b></td>
								<?
							}
							?>
							<td><div style="width: 10px;"></div></td>
							<td><b><?=$strings['payment_method']?></b></td>
						</tr>
						<tr><td colspan="<?=$trans->type == 'check' ? '17' : ($trans->type == 'cash' ? '11' : '13') ?>"><div style="border-bottom: 1px dotted #999999; height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
						<tr><td colspan="<?=$trans->type == 'check' ? '17' : ($trans->type == 'cash' ? '11' : '13') ?>"><div style="height: 10px; line-height: 10px; font-size: 0;">&nbsp;</div></td></tr>
						<tr>
							<td><div><?=coin($invoice['total'], $currency)?></div></td>
							<td><div style="width: 10px;"></div></td>
							<td><div><?=$invoice['receipt']['timestamp']?></div></td>
							<td><div style="width: 10px;"></div></td>
							<?
							if (!$_GET['token']) { $trans->invoice_data->customer_name = mb_strrev($trans->invoice_data->customer_name); }
							if ($trans->type == 'cash') {
								?>
								<td><div><?=$trans->invoice_data->phone ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->invoice_data->customer_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->invoice_data->customer_name ?: '-' ?></div></td>
								<?
							} else if ($trans->type == 'check') {
								?>
								<td><div><?=$trans->check_data->check_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->check_data->account_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->check_data->branch_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->check_data->bank_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->invoice_data->customer_number ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$trans->invoice_data->customer_name ?: '-' ?></div></td>
								<?
							} else {
								?>
								<td><div><?=$invoice['receipt']['cc_exp'] ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$invoice['receipt']['cc_last4'] ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=ucfirst($invoice['receipt']['cc_type']) ?: '-' ?></div></td>
								<td><div style="width: 10px;"></div></td>
								<td><div><?=$invoice['receipt']['voucher'] ?: '-' ?></div></td>
								<?
							}
							?>
							<td><div style="width: 10px;"></div></td>
							<td>
								<div><?=$invoice['receipt']['payment_method'] ?: '-' ?></div>
								<? if ($payments_type != 'regular' && $invoice['receipt']['payments_number'] > 1) { ?>
									<? if ($invoice['receipt']['payments_foremost_amount'] == $invoice['receipt']['payments_standing_amount']) { ?>
										<div class="small" dir="ltr" style="direction:ltr">(<?=coin($invoice['receipt']['payments_standing_amount'], $currency)?> x <?=$invoice['receipt']['payments_number']?>)</div>
									<? } else { ?>
										<div class="small" dir="ltr" style="direction:ltr">(<?=coin($invoice['receipt']['payments_foremost_amount'], $currency)?> + <?=coin($invoice['receipt']['payments_standing_amount'], $currency)?> x <?=($invoice['receipt']['payments_number']-1) ?>)</div>
									<? } ?>
								<? } ?>
							</td>
						</tr>
					</table>
					<br><br>
				<? } ?>
                
                <br><br>
                
                <?
                if ($invoice['company']['contact_person'] || $invoice['company']['contact_person_title']) {
					?>
					<div style="line-height:150%;">
						<b><?=$strings['signed_by']?></b>
						<br>
						<?=$invoice['company']['contact_person']?>
						<br>
						<?=$invoice['company']['contact_person_title']?>
					</div>
					<br><br>
					<br><br>
					<?
					
				}
				?>
                
                <table dir="ltr" style="width: 100%;" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <? if (!$authenticated) { ?>
                                <? /* Note that 'autheticated' can be NO or YES, and if it's false - it means we don't have a token at all */ ?>
								<div align="center" style="text-align:center"><a href="<?=$invoice_html?>?token=<?=sha1('Maya Kedem'.$voucher_number.$amount)?>&amp;mode=html"><?=$strings['click_here']?></a> <?=$strings['digital_document']?><img src="https://<?=constant('GTS_APP_HOST')?>/invoices-templates/images/global/lock.png" width="40" height="40" style="margin: 0 5px 0 5px; position: relative; top: 5px;"></div> 
                               	<br><br>
                            <? } ?>
                                
                            <? if ($_GET['token']) { ?>
                                <div align="center" style="text-align:center"><img src="https://<?=constant('GTS_APP_HOST')?>/invoices-templates/images/global/pdf.gif" width="20" height="20" style="margin: 0 5px 0 5px; position: relative; top: 5px;"><a href="<?=$invoice_pdf?>"><?=$strings['download_as_pdf']?></a></div>
                            <? } ?>
                        </td>
                    </tr>
                </table>
                 <br><br>
                
            </div>
        </center>
    </body>
</html>

<?php
	//echo '<pre dir="ltr">'; print_r($invoice); echo '</pre>';
?>
