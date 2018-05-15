<?php

	$group_object = new Group($doc_id);

	if ($group_object->header_image) {
		list($image_width, $image_height, $image_type, $image_attr) = getimagesize($group_object->header_image);
		?>
		<div class="box high" style="height:<?=$image_height?>px">
			<version><?=constant('VERSION')?></version>
			<img class="image" style="height:<?=$image_height?>px" src="<?=href($group_object->header_image)?>">
		</div>
		<?	
	} else {
		?>
		<div class="box high">
			<version><?=constant('VERSION')?></version>
			<a class="paragon_logo"><img src="<?=href('images/global/logo.png')?>" alt="<?=htmlspecialchars(constant('SITE_TITLE'))?>"></a>
			<img class="image" src="<?=href('images/global/wave_205.jpg')?>">
		</div>
		<?
	}
	?>

	<style>
		.order_form {display:block;direction:rtl;text-align:right;padding:0 0 40px 0}
		.order_form h2 {display:block;height:auto;line-height:100%;padding:0 0 20px 0;color:#999;font-weight:normal;font-size:2.2em;text-align:right}
		.order_form section {padding:20px 10px 0 10px;margin-top:20px;border-top:1px solid #EEE}
		.order_form fields_container {display:block}
		.order_form field {display:block;padding:0 0 10px 0}
		.order_form field title {display:block;line-height:150%;font-size:1.4em;color:#555}
		.order_form field subtitle {display:block;line-height:150%;font-size:1.2em;color:#BBB}
		.order_form field label {display:block}
		.order_form field input:not([type=checkbox]) {
			box-sizing:border-box;width:100%;height:35px;padding:5px;margin:0 0 5px 0;
			-webkit-appearance:none;-moz-appearance:none;appearance:none;
		}
		.order_form field select {box-sizing:border-box;width:100%;height:35px;padding:5px;margin:0 0 5px 0}
		.order_form field input[type=checkbox] {width:auto;height:auto}
		
		.order_form field.textarea {-webkit-column-span:all;column-span:all}
		.order_form field.textarea textarea {box-sizing:border-box;width:100%;height:200px}

		.order_form .notes {margin:10px 10px 0 10px;text-align:right}
		.order_form .notes .title {padding:0}

		.approvals {display:block;padding:20px 0 10px 0}
		.approvals label {display:block;text-align:right}

		.submit_visa_form {text-align:center}
		.submit_visa_form > button {width:250px;height:40px;font-size:1.4em;border-radius:10px;border:none;color:#000!important;background:#FFF;text-align:center;margin:30px auto 20px;cursor:pointer}

		@media all and (min-width:480px) {
			.order_form fields_container {-webkit-column-count:2;-moz-column-count:2;column-count:2}
			.order_form field {-webkit-column-break-inside:avoid;break-inside:avoid-column}
		}
	</style>

	<script>

		function validate_id(element) {
		   	var id_number = String($(element).val());
		   	// Validate correct input
		   	if ((id_number.length > 9) || (id_number.length < 5)) {
				$(element).addClass('error');
				return;
			}
		   	if (isNaN(id_number)) {
		   		$(element).addClass('error');
		   		return;
		   	}

		   	// The number is too short - add leading 0000
		   	if (id_number.length < 9) {
		    	while(id_number.length < 9) {
		    		id_number = '0' + id_number;         
		    	}
		   	}

		   	// CHECK THE ID NUMBER
		   	var mone = 0, incNum;
		   	for (var i=0; i < 9; i++) {
		    	incNum = Number(id_number.charAt(i));
		    	incNum *= (i%2)+1;
		    	if (incNum > 9) {
		    		incNum -= 9;
		    	}
		    	mone += incNum;
		   	}
		   	if ((mone % 10) == 0) {
		      $(element).removeClass('error');
		    } else {
		      $(element).addClass('error');
		    }

			return;
		}

		var submission_in_progress = false;

		$(document).ready(function() {
			$('form.order_form').submit(function() { 
		        if (!submission_in_progress) {
					submission_in_progress = true;
					
					$('.submit_visa_form > button').html('אנא המתן...');

					$(this).ajaxSubmit({
						success:function(response) {
							submission_in_progress = false;
							if (response && response.status == 'OKAY') {
								alert('הטופס נשלח בהצלחה!');
								$('form').clearForm();
							} else if (response && response.status == 'FAIL') {
								if (response.error) {
									alert(response.error);
								} else {
									alert('ארעה שגיאה - אנא נסו שוב.');	
								}
							}
							$('.submit_visa_form > button').html('שלח ואשר את הנ״ל');
						},
						error:function() {
							submission_in_progress = false;
							alert('ארעה שגיאה - אנא נסו שוב.');
							$('.submit_visa_form > button').html('שלח ואשר את הנ״ל');
						},
						/*
						beforeSend:function() {
							var percent = '0%';
							$('.progress_bar .percent').width(percent);
							$('.progress_bar .percent > span').html(percent);
							//$('.progress_bar .loader').hide(0);
						},
						uploadProgress: function(event, position, total, complete) {
							var percent = complete+'%';
							$('.progress_bar .percent').width(percent);
							$('.progress_bar .percent > span').html(percent);
						},
						complete:function(response) {
							console.log(response.responseText);
						},
						*/
				 		dataType:'json'
					});
				}
		        return false; 
		    });
		});

	</script>

	<form class="order_form" action="" method="POST" autocomplete="off">
		<input type="hidden" name="action" value="order_form" />
		<input type="hidden" name="doc_id" value="<?=$doc_id?>" />
		<div class="box flex<?=$group_object->layout ? ' '.$group_object->layout : false?>">
			<h1><?=$group_object->title?></h1>
			<?
			if ($_POST) {
				?>
				<div class="notes">
					<div class="title">אישור</div>
					טופס ההזמנה נשלח בהצלחה.
				</div>
				<?
			} else {
				?>
				<div class="description"><?=$group_object->description?></div>
				<section class="general">
					<h2>כללי</h2>
					<fields_container>
						<field>
							<title>סוג המכשיר*</title>
							<select name="general[device_type]" required>
								<option value=""></option>
								<option value="vx520_lan">VX520 נייח</option>
								<option value="vx520_cellular">VX520 נייד סלולארי</option>
							</select>
						</field>
						<field>
							<title>ע.מ. / ח.פ.*</title>
							<input type="text" name="general[business_number]" value="" pattern="[0-9]{0,9}" onchange="validate_id(this)" required />
						</field>
						<field>
							<title>כמות*</title>
							<input type="number" name="general[quantity]" value="1" required />
						</field>
						<field>
							<title>סוג העסק*</title>
							<select name="general[business_type]">
								<option value=""></option>
								<option value="בתי קפה, מסעדות, פאב - 102">בתי קפה, מסעדות, פאב - 102</option>
								<option value="חנויות מזון - 103">חנויות מזון - 103</option>
								<option value="בתי מרקחת ופרפומריות - 104">בתי מרקחת ופרפומריות - 104</option>
								<option value="הלבשה, הנעלה, אופנה - 105">הלבשה, הנעלה, אופנה - 105</option>
								<option value="ספרים CD צעצועים - 234">ספרים CD צעצועים - 234</option>
								<option value="כלי בית ואביזרים - 217">כלי בית ואביזרים - 217</option>
								<option value="בתי הארחה ומלונות - 269">בתי הארחה ומלונות - 269</option>
								<option value="תכשיטים - 109">תכשיטים - 109</option>
								<option value="דלק - 112">דלק - 112</option>
								<option value="מפעל הפיס - 139">מפעל הפיס - 139</option>
								<option value="עיריות, חברות ממשלה - 225">עיריות, חברות ממשלה - 225</option>
								<option value="פרחים, משתלות - 141">פרחים, משתלות - 141</option>
								<option value="מכוניות, חלקי חילוף - 212">מכוניות,חלקי חילוף - 212</option>
								<option value="אופטיקה, צילום וידאו - 143">אופטיקה, צילום וידאו - 143</option>
								<option value="פרסום - 145">פרסום - 145</option>
								<option value="נופש, תיירות וספורט - 221">נופש, תיירות וספורט - 221</option>
								<option value="חשמל אלק' מחשבים - 233">חשמל אלק' מחשבים - 233</option>
								<option value="ביגוד וריהוט תינוקות - 148">ביגוד וריהוט תינוקות - 148</option>
								<option value="עיריות, חברות ממשלה - 225">עיריות, חברות ממשלה - 225</option>
								<option value="חברות גז - 152">חברות גז - 152</option>
								<option value="מכבסות ומספרות - 154">מכבסות ומספרות - 154</option>
								<option value="חומרי בניין - 155">חומרי בניין - 155</option>
								<option value="ריהוט וציוד משרדי - 156">ריהוט וציוד משרדי - 156</option>
								<option value="מוסדות חינוך עמותות - 236">מוסדות חינוך עמותות - 236</option>
								<option value="אופטיקה, צילום וידאו - 143">אופטיקה, צילום וידאו - 143</option>
								<option value="אמנות תרבות תאטרון - 235">אמנות תרבות תאטרון - 235</option>
								<option value="ביטוח - 172">ביטוח - 172</option>
								<option value="תשמישי קדושה - 174">תשמישי קדושה - 174</option>
								<option value="יבוא ושיווק - 175">יבוא ושיווק - 175</option>
								<option value="נותני שירותים - 176">נותני שירותים - 176</option>
								<option value="ייצור ותעשייה - 270">ייצור ותעשייה - 270</option>
								<option value="מוסדות חינוך עמותות - 236">מוסדות חינוך עמותות - 236</option>
								<option value="עורך-דין, רואה-חשבון - 183">עורך-דין, רואה-חשבון - 183</option>
								<option value="טוטו/פיצוציה - 177">טוטו/פיצוציה - 177</option>
							</select>
						</field>
					</fields_container>
				</section>
				<section class="general">
					<h2>פרטים אישיים</h2>
					<fields_container>
						<field>
							<title>איש קשר - שם פרטי ומשפחה*</title>
							<input type="text" name="general[contact_person]" value="" required />
						</field>
						<field>
							<title>תעודת זהות*</title>
							<input type="text" name="general[id_number]" value="" pattern="[0-9]{0,9}" onchange="validate_id(this)" required />
						</field>
						<field>
							<title>פקס</title>
							<input type="tel" name="general[fax]" value="" />
						</field>
						<field>
							<title>כתובת פרטית*</title>
							<input type="text" name="general[address]" value="" required />
						</field>
						<field>
							<title>נייד*</title>
							<input type="tel" name="general[mobile]" value="" required />
						</field>
						<field>
							<title>טלפון בבית</title>
							<input type="tel" name="general[phone]" value="" />
						</field>
						<field>
							<title>איש קשר נוסף</title>
							<input type="text" name="general[alt_contact_person]" value="" />
						</field>
						<field>
							<title>טלפון של איש הקשר הנוסף</title>
							<input type="text" name="general[alt_phone]" value="" />
						</field>
						<field>
							<title>ת.ז. של איש הקשר הנוסף</title>
							<input type="number" name="general[alt_id_number]" value="" pattern="[0-9]{0,9}" onchange="validate_id(this)" />
						</field>
					</fields_container>
				</section>
				<section class="business_details">
					<h2>פרטי בית העסק</h2>
					<fields_container>
						<field>
							<title>לוגו מכשיר - שם העסק*</title>
							<input type="text" name="business_details[logo_name]" value="" required />
						</field>
						<field>
							<title>לוגו - כתובת העסק*</title>
							<input type="text" name="business_details[logo_address]" value="" required />
						</field>
						<field>
							<title>לוגו - טלפון העסק*</title>
							<input type="text" name="business_details[logo_phone]" value="" required />
						</field>
						<field>
							<title>חשבונית על שם*</title>
							<input type="text" name="business_details[invoice_name]" value="" required  />
						</field>
						<field>
							<title>כתובת למשלוח חשבונית*</title>
							<input type="text" name="business_details[invoice_address]" value="" required />
						</field>
						<field>
							<title>אימייל*</title>
							<input type="email" name="business_details[email]" value="" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$" required />
						</field>
					</fields_container>
				</section>
				<section class="credit_company_details">
					<h2>פרטי חברות האשראי</h2>
					<fields_container>
						<field>
							<title>שם העסק*</title>
							<input type="text" name="credit_company_details[business_name]" value="" required />
						</field>
						<field>
							<title>כתובת העסק*</title>
							<input type="text" name="credit_company_details[business_address]" value="" required />
						</field>

						<field>
							<title>מספר ספק בלאומי קארד*</title>
							<input type="text" name="credit_company_details[leumi_card_terminal_number]" value="" required />
							<label><input type="checkbox" name="credit_company_details[leumi_card_mastercard]" value="on" /> סולק מאסטר-קארד</label>
							<label><input type="checkbox" name="credit_company_details[leumi_card_visa]" value="on" /> סולק ויזה</label>
							<label><input type="checkbox" name="credit_company_details[leumi_card_isracard]" value="on" /> סולק ישראכרט</label>
						</field>
					</fields_container>
				</section>
				<section class="other_credit_company_details">
					<h2>פרטי חברות אשראי נוספות</h2>
					<fields_container>
						<field>
							<title>מספר ספק בויזה כאל</title>
							<input type="text" name="credit_company_details[visa_cal_terminal_number]" value="" />
							<label><input type="checkbox" name="credit_company_details[visa_cal_mastercard]" value="on" /> סולק מאסטר-קארד</label>
							<label><input type="checkbox" name="credit_company_details[visa_cal_visa]" value="on" /> סולק ויזה</label>
							<label><input type="checkbox" name="credit_company_details[visa_cal_isracard]" value="on" /> סולק ישראכרט</label>
						</field>
						<field>
							<title>מספר ספק בישראכרט</title>
							<input type="text" name="credit_company_details[isracard_terminal_number]" value="" />
							<label><input type="checkbox" name="credit_company_details[isracard_mastercard]" value="on" /> סולק מאסטר-קארד</label>
							<label><input type="checkbox" name="credit_company_details[isracard_visa]" value="on" /> סולק ויזה</label>
							<label><input type="checkbox" name="credit_company_details[isracard_isracard]" value="on" /> סולק ישראכרט</label>
						</field>
						<field>
							<title>מספר ספק באמקס</title>
							<input type="text" name="credit_company_details[amex_terminal_number]" value="" />
							<label><input type="checkbox" name="credit_company_details[amex_mastercard]" value="on" /> סולק מאסטר-קארד</label>
							<label><input type="checkbox" name="credit_company_details[amex_visa]" value="on" /> סולק ויזה</label>
							<label><input type="checkbox" name="credit_company_details[amex_isracard]" value="on" /> סולק ישראכרט</label>
						</field>
						<field>
							<title>מספר ספק בדיינרס</title>
							<input type="text" name="credit_company_details[diners_terminal_number]" value="" />
							<label><input type="checkbox" name="credit_company_details[diners_mastercard]" value="on" /> סולק מאסטר-קארד</label>
							<label><input type="checkbox" name="credit_company_details[diners_visa]" value="on" /> סולק ויזה</label>
							<label><input type="checkbox" name="credit_company_details[diners_isracard]" value="on" /> סולק ישראכרט</label>
						</field>
					</fields_container>
				</section>
				<? /*
				<section class="rented_devices">
					<h2>מכשירים בשכירות</h2>
					<fields_container>
						<field>
							<title>דמי חיבור, התקנה ופתיחה בשב״א (₪)</title>
							<input type="text" name="rented_devices[installation_fee]" value="" />
						</field>
						<field>
							<title>הו״ק / אחר</title>
							<select name="rented_devices[installation_payment_method]">
								<option value=""></option>
								<option value="recurring_order">הוראת קבע</option>
								<option value="other">אחר</option>
							</select>
						</field>
						<field>
							<title>תשלום חודשי (₪)</title>
							<input type="text" name="rented_devices[monthly_fee]" value="" />
						</field>
					</fields_container>
				</section>
				<section class="purchased_devices">
					<h2>מכשירים ברכישה</h2>
					<fields_container>
						<field>
							<title>מחיר (₪)</title>
							<input type="text" name="purchased_devices[price]" value="" />
						</field>
						<field>
							<title>הנחה (₪)</title>
							<input type="text" name="purchased_devices[discount]" value="" />
						</field>
						<field>
							<title>סה״כ לתשלום (₪)</title>
							<input type="text" name="purchased_devices[total_price]" value="" />
						</field>
						<field>
							<title>דמי רשיון ושימוש בתוכנה (₪)</title>
							<input type="text" name="purchased_devices[license_fee]" value="" />
						</field>
						<field>
							<title>ביטוח חומרה החל מחודש 13 (₪)</title>
							<input type="text" name="purchased_devices[insurance_fee]" value="" />
						</field>
					</fields_container>
				</section>
				<section class="additional_services">
					<h2>שירותים נוספים</h2>
					<fields_container>
						<field>
							<title>סוג השירות</title>
							<select name="additional_services[services]">
								<option value=""></option>
								<option value="pwm">PAYware</option>
								<option value="veripay">Veripay</option>
								<option value="other">אחר</option>
							</select>
						</field>
						<field>
							<title>דמי חיבור (₪)</title>
							<input type="text" name="additional_services[connection_fee]" value="" />
						</field>
						<field>
							<title>הנחה (₪)</title>
							<input type="text" name="additional_services[monthly_fee]" value="" />
						</field>
						<field>
							<title>תמיכה בחשבוניות</title>
							<select name="additional_services[invoices_support]">
								<option value=""></option>
								<option value="1">עם חשבוניות</option>
								<option value="0">בלי חשבוניות</option>
							</select>
						</field>
						<field>
							<title>מודול Batch (₪)</title>
							<input type="text" name="additional_services[batch_module]" value="" />
						</field>
						<field>
							<title>קורא כרטיסים מגנטי - תשלום חד-פעמי (₪)</title>
							<input type="text" name="additional_services[plug]" value="" />
						</field>
					</fields_container>
				</section>
				*/ ?>
				<section class="payment">
					<h2>תשלום באמצעות הוראה לחיוב כרטיס אשראי</h2>
					<fields_container>
						<field>
							<title>מספר כרטיס האשראי*</title>
							<input type="text" name="payment[cc_number]" value="" placeholder="הזינו רק ספרות - ללא רווחים או מקפים" pattern="[0-9]{8,16}" required />
						</field>
						<field>
							<title>תוקף*</title>
							<input type="text" name="payment[cc_expiration]" value="" placeholder="MM/YY" pattern="[0-9]{2}.{1}[0-9]{2}" title="MM/YY" required />
						</field>
						<field>
							<title>ת.ז. בעל הכרטיס*</title>
							<input type="text" name="payment[cc_id_number]" value="" required pattern="[0-9]{0,9}" onchange="validate_id(this)" />
						</field>
						<? /*
						<field>
							<title>מספר תשלומים</title>
							<input type="date" name="payment[number_of_payments]" value="" />
						</field>
						*/ ?>
					</fields_container>
				</section>
				<section class="comments">
					<h2>הערות</h2>
					<fields_container>
						<field class="textarea">
							<textarea name="misc[comments]"></textarea>
						</field>
					</fields_container>
				</section>
				<br><br>
				<?
			}
			
			if (!empty($group_object->items)) {
				?>
				<div class="items">
					<?
					$count = 0;
					foreach ($group_object->items as $item) {
						?>
						<div id="item_<?=$count?>" class="item">
							<?
							if ($item['title']) {
								?>
								<h2><?=$item['title']?></h2>
								<?
							}
							if ($item['sub_title']) {
								?>
								<h3><?=$item['sub_title']?></h3>
								<?
							}
							//echo $_SERVER['HTTP_USER_AGENT'];
							if (($item['image'] && !$item['iframe']) || (preg_match('/MSIE [2-9]{1}\./i', $_SERVER['HTTP_USER_AGENT']) && $item['image'])) {
								?>
								<div class="media image"><img src="<?=href($item['image'])?>"></div>
								<?
							} else if ($item['iframe']) {
								$iframe_size = false;
								if ($item['iframe_size']) {
									$iframe_size = explode('x', $item['iframe_size']);
									if ($iframe_size[0] && $iframe_size[1]) {
										$iframe_size = ' style="width:'.$iframe_size[0].'px;height:'.$iframe_size[1].'px"';
									}
								}
								?>
								<script>
									$(document).ready(function() {
										setTimeout(function() {
											$('#item_<?=$count?> .media.iframe').html('<iframe type="text/html"<?=$iframe_size?> src="<?=$item['iframe']?>" seamless allowfullscreen frameborder="0"></iframe>');
										}, 1000);
									});
								</script>
								<div class="media iframe"></div>
								<?
							}
							if ($item['description'] && !$_POST) {
								?>
								<div class="description">
									<?=$item['description']?>
									
									<div class="approvals">
										<label for="approval_1">
											<input id="approval_1" class="checkbox" type="checkbox" value="on" name="misc[approval_1]" required>
											שליחת טופס זה מאשרת שקראתי ואישרתי את <a href="<?=href('files/visa-order-form/DOC-ORDLEUMI-VER-01.00.pdf')?>" target="_blank">הסכם שכירות ושירות DOC-ORDLEUMI-VER-01.00</a>.
										</label>
										<label for="approval_2">
											<input id="approval_2" class="checkbox" type="checkbox" value="on" name="misc[approval_2]" required>
											הריני מאשר/ת את נכונות הפרטים לעיל בטופס זה ואני מסכים/ה לכל התנאים בו
										</label>
									</div>
									<div class="submit_visa_form">
										<button>שלח ואשר את הנ״ל</button>
									</div>
								</div>
								<?
							}
							?>
						</div>
						<?
						$count++;
					}
					?>
				</div>
				<?
			}
			?>
		</div>
	</form>
	<?
	
?>