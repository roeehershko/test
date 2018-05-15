<?php
		
$strings['currency'] = $data->currency == 'ILS' ? '&#8362;' : ($data->currency == 'USD' ? '$' : '&euro;');

if ($voucher_language == 'en') {
	
	$direction = 'ltr';
	$align = 'left';
	
	$strings['transaction_voucher'] = 'Transaction Voucher';
	
	$strings['merchant_number'] = 'Terminal Number';
	$strings['company_name'] = 'Company';
	$strings['company_id'] = 'Company Number';
	
	$strings['transaction_details'] = 'Transaction Details';
	$strings['transaction_status'] = 'Transaction Status';
	$strings['transaction_type'] = 'Type';
	$strings['transaction_type__cash'] = 'Cash';
	$strings['transaction_type__check'] = 'Check';
	$strings['transaction_type__credit'] = 'Credit Card';
	$strings['timestamp'] = 'Timestamp';
	$strings['amount'] = 'Amount';
	
	$strings['credit_terms'] = 'Payments Type';
	$strings['credit_terms__regular'] = 'None';
	$strings['credit_terms__payments'] = 'Payments';
	$strings['credit_terms__payments-credit'] = 'Credit Payments';
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
	
	$strings['reply_by_email'] = 'To send a reply by email - click here';

	$strings['transaction_canceled'] = 'Cancelled Transaction';
	$strings['transaction_refunded'] = 'Refund Transaction';
	$strings['transaction_test'] = 'Test Trasnaction';

} else {

	$direction = 'rtl';
	$align = 'right';
	
	$strings['transaction_voucher'] = 'שובר עסקה';
	
	$strings['merchant_number'] = 'מספר מסוף';
	$strings['company_name'] = 'חברה / עסק';
	$strings['company_id'] = 'ח.פ / ע.מ';
	
	$strings['transaction_details'] = 'פרטי העסקה';
	$strings['transaction_status'] = 'סטטוס';
	$strings['transaction_type'] = 'אמצעי תשלום';
	$strings['transaction_type__cash'] = 'מזומן';
	$strings['transaction_type__check'] = 'המחאה';
	$strings['transaction_type__credit'] = 'אשראי';
	$strings['timestamp'] = 'תאריך ושעה';
	$strings['amount'] = 'סכום';
	$strings['credit_terms'] = 'סוג התשלום';
	$strings['credit_terms__regular'] = 'רגיל';
	$strings['credit_terms__payments'] = 'תשלומים';
	$strings['credit_terms__payments-credit'] = 'תשלומי קרדיט';
	$strings['payments_number'] = 'מספר תשלומים';
	
	$strings['card_details'] = 'פרטי כרטיס אשראי';
	$strings['card_holder_name'] = 'בעל הכרטיס';
	$strings['last_4_digits'] = '4 ספרות אחרונות';
	$strings['expiration'] = 'תוקף';
	$strings['card_type'] = 'סוג הכרטיס';
	$strings['approval_code'] = 'מספר אישור';
	$strings['voucher_code'] = 'מספר שובר';
	$strings['reference_number'] = 'אסמכתא';
	
	$strings['check_details'] = 'פרטי ההמחאה';
	$strings['check_number'] = 'מספר ההמחאה';
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
	
	$strings['reply_by_email'] = 'לשליחת הודעה חזרה לבעל העסק - לחצו כאן';
	
	$strings['transaction_canceled'] = 'עסקת ביטול';
	$strings['transaction_refunded'] = 'עסקת זיכוי';
	$strings['transaction_test'] = 'עסקת בדיקה';
}

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	</head>
	<body dir="<?=$direction?>">
		<div align="center">
		
			<div align="center" style="font-family:Arial;font-size:24px;font-weight:bold;text-align:center"><?=$strings['transaction_voucher']?></div>
			<?
			if ($sender_email) {
				?>
				<div align="center" style="padding:5px;text-align:center"><a align="center" href="mailto:<?=$sender_email?>" style="font-family:Arial;font-size:14px"><?=$strings['reply_by_email']?></a></div>
				<?
			}
			?>
			<div style="width:240px;padding:20px;border:1px solid #EEE;margin:20px auto;direction:<?=$direction?>;text-align:<?=$align?>" dir="<?=$direction?>">
				<div style="font-family:Arial;font-size:18px"><b><?=$company_name?></b> </div>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['company_id']?></b>: <?=$company_id?></div>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['merchant_number']?></b>: <?=substr($merchant_number, 0, 7)?></div>
				<br>
				<div style="font-family:Arial;font-size:18px">
					<b>
						<?
						if ($data->status == 'canceled') {
							echo $strings['transaction_canceled'];
						} else if ($data->amount < 0) {
							echo $strings['transaction_refunded'];
						} else if ($data->credit_data->j5) {
							echo $strings['transaction_test'];
						} else {
							echo $strings['transaction_details'];
						}
						?>
					</b>
				</div>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['transaction_type']?>:</b> <?=$strings['transaction_type__'.$data->type]?></div>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['timestamp']?>:</b> <?=date('d/m/Y, H:i', strtotime($data->timestamp))?></div>
				<div style="font-family:Arial;font-size:14px"><b><?=$strings['amount']?>:</b> <span dir="ltr" style="font-family:Arial;font-size:14px;direction:ltr"><?=$data->amount.'</span>'.$strings['currency']?></div>
				<?
				if ($data->type == 'credit') {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['credit_terms']?>:</b> <?=$strings['credit_terms__'.$data->credit_data->credit_terms]?></div>
					<?
					if ($data->credit_data->payments_number) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['payments_number']?>:</b> <?=$data->credit_data->payments_number?></div>
						<?
					}
					?>
					<br>
					<div style="font-family:Arial;font-size:18px"><b><?=$strings['card_details']?></b> </div>
					<?
					if ($data->credit_data->cc_holder_name) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['card_holder_name']?>:</b> <?=$data->credit_data->cc_holder_name?></div>
						<?
					}
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['last_4_digits']?>:</b> <?=$data->credit_data->cc_last_4?></div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['expiration']?>:</b> <?=$data->credit_data->cc_exp?></div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['card_type']?>:</b> <?=ucfirst($data->credit_data->cc_type)?></div>
					<?
					if ($data->credit_data->authorization_number) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['approval_code']?>:</b> <?=$data->credit_data->authorization_number?></div>
						<?
					}
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['voucher_code']?>:</b> <?=$data->credit_data->voucher_number?></div>
					<?
				} else if ($data->type == 'check') {
					?>
					<div style="font-family:Arial;font-size:18px"><b><?=$strings['check_details']?></b> </div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['check_number']?>:</b> <?=$data->check_data->check_number?></div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['bank_number']?>:</b> <?=$data->check_data->bank_number?></div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['branch_number']?>:</b> <?=$data->check_data->branch_number?></div>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['account_number']?>:</b> <?=$data->check_data->account_number?></div>
					<?
				}
				if ($data->credit_data->reference_number) {
					?>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['reference_number']?>:</b> <?=$data->credit_data->reference_number?></div>
					<?
				}
				if ($data->invoice_data) {
					?>
					<br>
					<div style="font-family:Arial;font-size:18px"><b><?=$strings['invoice_details']?></b> </div>
					<?
					if ($data->invoice_data->number) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_number']?>:</b> <?=$data->invoice_data->number?></div>
						<?
					}
					if ($data->invoice_data->customer_name) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_customer_name']?>:</b> <?=htmlspecialchars($data->invoice_data->customer_name)?></div>
						<?
					}
					if ($data->invoice_data->customer_number) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_customer_number']?>:</b> <?=htmlspecialchars($data->invoice_data->customer_number)?></div>
						<?
					}
					if ($data->invoice_data->address_city) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_address']?>:</b> <?=htmlspecialchars($data->invoice_data->address_street.($data->invoice_data->address_city ? ', '.$data->invoice_data->address_city : false).($data->invoice_data->address_zip ? ', '.$data->invoice_data->address_zip : false))?></div>
						<?
					}
					if ($data->invoice_data->phone) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_phone']?>:</b> <?=htmlspecialchars($data->invoice_data->phone)?></div>
						<?
					}
					if ($data->invoice_data->description) {
						?>
						<div style="font-family:Arial;font-size:14px"><b><?=$strings['invoice_description']?>:</b> <?=htmlspecialchars($data->invoice_data->description)?></div>
						<?
					}
					?>
					<div style="font-family:Arial;font-size:14px"><a href="<?=$data->invoice_data->link?>"><?=$strings['invoice_link']?></a></div>
					<?
				}
				if ($data->credit_data->signature_link) {
					?>
					<br>
					<div style="font-family:Arial;font-size:14px"><b><?=$strings['customer_signature']?>:</b></div>
					<div style="font-family:Arial;font-size:14px;padding:10px"><img width="240" src="<?=$data->credit_data->signature_link?>"></div>
					<?
				}
				?>
			</div>
		</div>
	</body>
</html>
