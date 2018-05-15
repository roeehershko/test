var submission_in_progress = false;

function htmlspecialchars(phrase) {
	// Doesn't cause the expected behaviour...
	//return phrase.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
	return phrase.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function stop_propagation() {
	if (event && event.stopPropagation) {
		event.stopPropagation();
	} else if (window.event) {
		window.event.cancelBubble = true;
	}
	return false;
}

/* GTS */

var current_user = {};
var distributor = /isracard/.test(window.location.href) ? 'isracard' : 'verifone';

var isMobile = {
    Android: navigator.userAgent.match(/Android/i),
    BlackBerry: navigator.userAgent.match(/BlackBerry/i),
    iOS: navigator.userAgent.match(/iPhone|iPad|iPod/i),
    Windows: navigator.userAgent.match(/Trident|Edge/i)
};

function generate_password(element) {
    //var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz';
    var digits = '123456789';
    var small_caps = 'abcdefghikmnpqrstuvwxyz'; // 'abcdefghiklmnopqrstuvwxyz';
    var capitals = 'ABCDEFGHJKLMNPQRSTUVWXTZ'; // 'ABCDEFGHIJKLMNOPQRSTUVWXTZ';
	var password_1 = '';
	var password_2 = '';
	var password_3 = '';
	var password = '';
	var rnum;
	for (var i = 0; i < 2; i++) {
		rnum = Math.floor(Math.random()*digits.length);
		password_1 += digits.substring(rnum, rnum+2);
		rnum = Math.floor(Math.random()*small_caps.length);
		password_2 += small_caps.substring(rnum, rnum+1);
		rnum = Math.floor(Math.random()*capitals.length);
		password_3 += capitals.substring(rnum, rnum+1);
	}
	var password = password_1 + password_2 + password_3;
	password = password.split('').sort(function(){return 0.5-Math.random()}).join('');
	
	// 2013-07-09 - Verifone and Isracard requested that the first letter of the password will always be a "letter", since otherwise they have problems in their emails (due to RTL bugs in the system)
	rnum = Math.floor(Math.random()*small_caps.length);
	password = small_caps.substring(rnum, rnum+1) + password;
	
	element.val(password);
}

function send_password(method, username_element, password_element, mobile_element, email_element) {
	// Validating the password
	if (password_element.val() && (password_element.val().length < 6 || !/\D/.test(password_element.val()) || !/\d/.test(password_element.val()))) {
		alert(strings.error__invalid_password);
		return false;
	}
	
	// Validating the mobile
	if (mobile_element && mobile_element.val() && (mobile_element.val().length != 10 || !/\d/.test(mobile_element.val()))) {
		alert(strings.error__invalid_mobile);
		return false;
	}
	
	if (!password_element.val()) {
		generate_password(password_element);
	}
	if (method == 'sms') {
		if (mobile = prompt(strings.enter_mobile_number, mobile_element.val())) {
			mobile = mobile.replace(/[^0-9\.]+/g, '');
			$.post(href_url, {action:'send_password', method:method, mobile:mobile, username:username_element.val(), password:password_element.val(), merchant_type:($('select[name="params[merchant_type]"]').val() ? $('select[name="params[merchant_type]"]').val() : $('select[name="gts_user[type]"]').val())}, function(data) {
				alert(strings.send_password__successfully_sent);
			}, 'json');
		}
	} else if (method == 'email') {
		if (email = prompt(strings.enter_email, email_element.val())) {
			$.post(href_url, {action:'send_password', method:method, email:email, username:username_element.val(), password:password_element.val(), merchant_type:($('select[name="params[merchant_type]"]').val() ? $('select[name="params[merchant_type]"]').val() : $('select[name="gts_user[type]"]').val())}, function(data) {
				if (data.status == 'OKAY') {
					alert(strings.send_password__successfully_sent);
				} else {
					alert(strings.send_password__submission_failed);
				}
			}, 'json');
		}
	}
}

function toggle_company_form(element) {
	
	var id = '';
	var company_name = '';
	var company_number = '';
	var mailer_name = '';
	var mailer_email = '';
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_company);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_company_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_company);
		if (element) {
			id = $(element).find('.id').html();
			company_name = htmlspecialchars($(element).find('.company_name').html());
			company_number = $(element).find('.company_number').html();
			mailer_name = htmlspecialchars($(element).find('.mailer_name').html());
			mailer_email = $(element).find('.mailer_email > a').html();
			note = $(element).find('.note').attr('title');
		}
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_company_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_save_company">';
		ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.company_name+'*</div><div class="annotation">'+strings.company_name__annotation+'</div><div class="field"><input type="text" name="name" value="'+company_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.company_number+'*</div><div class="annotation">'+strings.company_number__annotation+'</div><div class="field"><input type="text" name="number" value="'+company_number+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.mailer_name+'*</div><div class="annotation">'+strings.mailer_name__annotation+'</div><div class="field"><input type="text" name="mailer_name" value="'+mailer_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.mailer_email+'*</div><div class="annotation">'+strings.mailer_email__annotation+'</div><div class="field"><input class="ltr" type="text" name="mailer_email" value="'+mailer_email+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="params[note]" value="'+note+'"></div></div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		if (id) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_type(\'company\', \''+id+'\')">'+strings.delete_type+'</button>';
		}
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_merchant_form(element) {
	
	var id = '';
	var merchant_name = '';
	var merchant_number = '';
	var company_id = '';
	var pos_number = '';
	var report_inactivity = 1;
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_merchant);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_merchant_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_merchant);
		if (element) {
			id = $(element).find('.id').html();
			merchant_name = htmlspecialchars($(element).find('.merchant_name').html());
			merchant_number = $(element).find('.merchant_number').html();
			company_id = $(element).find('.company_name').attr('title');
			pos_number = htmlspecialchars($(element).find('.pos_number').html());
			report_inactivity = $(element).find('.report_inactivity').html() == '+' ? 1 : 0;
		}
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_merchant_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_save_merchant">';
		ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.merchant_name+'*</div><div class="annotation">'+strings.merchant_name__annotation+'</div><div class="field"><input type="text" name="name" value="'+merchant_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.merchant_number+'*</div><div class="annotation">'+strings.merchant_number__annotation+'</div><div class="field"><input type="text" name="number" value="'+merchant_number+'"></div></div>';
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.company_id+'*</div><div class="annotation">'+strings.company_id__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="company_id">';
		ticket_form += 						'<option value=""></option>';
		$.each(all_companies, function(i) {
			ticket_form += 					'<option value="'+this.id+'"'+(this.id == company_id ? ' selected' : '')+'>'+this.name+'</option>';
		});
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.pos_number+'</div><div class="annotation">'+strings.pos_number__annotation+'</div><div class="field"><input type="text" name="pos_number" value="'+pos_number+'"></div></div>';
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.report_inactivity+'*</div><div class="annotation">'+strings.report_inactivity__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="report_inactivity">';
		ticket_form += 						'<option value="1"'+(report_inactivity == 1 ? ' selected' : '')+'>'+strings.report_inactivity+'</option>';
		ticket_form += 						'<option value="0"'+(report_inactivity == 0 ? ' selected' : '')+'>'+strings.do_not_report_inactivity+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		if (id) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_type(\'merchant\', \''+id+'\')">'+strings.delete_type+'</button>';
		}
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_vf_merchant_form(element) {
	
	var id = '';
	var username = '';
	var merchant_number = '';
	var merchant_type = '';
	var merchant_status = '';
	var company_name = '';
	var company_number = '';
	var company_email = '';
	var mailer_name = '';
	var mailer_email = '';
	var access_token_ttl = '30';
	var voucher_language = 'he';
	var allow_duplicated_transactions = '';
	var support_recurring_orders = '';
	
	var support_refund_trans = 1;
	var support_cancel_trans = 1;
	var support_new_trans = 1;
	
	var send_daily_report;
	
	var charge_starting_number = '';
	var refund_starting_number = '';
	var invoice_template = '';
	var purchased_card_readers = '';

	var company_type = '';
	var company_address_street = '';
	var company_address_number = '';
	var company_address_city = '';
	var company_address_zip = '';
	var company_phone = '';
	var company_logo = '';

	var business_category = '';
	var business_address = '';
	var business_contact_name = '';

	var shva_transactions_username = '';
	var shva_transactions_password = '';

	var note = '';
	var mobile = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_merchant);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_vf_merchant_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_merchant);
		if (element) {
			id = $(element).find('.id').html();
			username = $(element).find('.username').html();
			merchant_number = $(element).find('.merchant_number').html();
			merchant_type = $(element).find('.merchant_type').html();
			merchant_status = $(element).find('.merchant_status').html();
			company_name = htmlspecialchars($(element).find('.company_name').html());
			company_number = $(element).find('.company_name').attr('title');
			company_email = $(element).find('.company_email').val();
			mailer_name = htmlspecialchars($(element).find('.mailer_name').val());
			mailer_email = $(element).find('.mailer_email').val();
			access_token_ttl = $(element).find('.access_token_ttl').val();
			voucher_language = $(element).find('.voucher_language').val();
			
			allow_duplicated_transactions = $(element).find('.allow_duplicated_transactions').val();
			support_recurring_orders = $(element).find('.support_recurring_orders').val();
			
			support_refund_trans = $(element).find('.support_refund_trans').val();
			support_cancel_trans = $(element).find('.support_cancel_trans').val();
			support_new_trans = $(element).find('.support_new_trans').val();
			
			send_daily_report = $(element).find('.send_daily_report').val();
			
			charge_starting_number = $(element).find('.charge_starting_number').val();
			refund_starting_number = $(element).find('.refund_starting_number').val();
			invoice_template = $(element).find('.invoice_template').val();
			purchased_card_readers = $(element).find('.purchased_card_readers').val();
			
			company_type = htmlspecialchars($(element).find('.company_type').val());
			company_address_street = htmlspecialchars($(element).find('.company_address_street').val());
			company_address_number = $(element).find('.company_address_number').val();
			company_address_city = htmlspecialchars($(element).find('.company_address_city').val());
			company_address_zip = $(element).find('.company_address_zip').val();
			company_phone = $(element).find('.company_phone').val();
			company_logo = $(element).find('.company_logo').val();

			business_category = $(element).find('.business_category').val();
			business_address = $(element).find('.business_address').val();
			business_contact_name = $(element).find('.business_contact_name').val();

			shva_transactions_username = $(element).find('.shva_transactions_username').val();
			
			note = $(element).find('.note').attr('title');
			mobile = $(element).find('.mobile').attr('title');
		}
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_vf_merchant_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_save_vf_merchant">';
		ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
		ticket_form += 			'<input type="hidden" name="access_token_ttl" value="'+access_token_ttl+'">';
		ticket_form += 			'<input type="hidden" name="allow_duplicated_transactions" value="'+allow_duplicated_transactions+'">';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.username+'*</div><div class="annotation">'+strings.username__annotation+'</div><div class="field"><input class="ltr" type="text" name="username" value="'+username+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.password+(id ? '' : '*')+'</div><div class="annotation">'+strings.password__annotation+'</div><div class="field password"><input class="ltr" type="text" name="password" value=""> <a class="generate_password" onclick="generate_password($(\'input[name=password]\'))">'+strings.generate_password+'</a></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.merchant_number+'*</div><div class="annotation">'+strings.merchant_number__annotation+'</div><div class="field"><input class="ltr" type="text" name="number" value="'+merchant_number+'"></div></div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.shva_transactions_username+'</div><div class="annotation">'+strings.shva_transactions_username__annotation+'</div><div class="field"><input class="ltr" type="text" name="shva_transactions_username" value="'+shva_transactions_username+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.shva_transactions_password+'</div><div class="annotation">'+strings.shva_transactions_password__annotation+'</div><div class="field"><input class="ltr" type="text" name="shva_transactions_password" value=""></div></div>';

		ticket_form += 				'<div class="field_container">';
		ticket_form += 					'<div class="label">'+strings.merchant_type+'*</div><div class="annotation">'+strings.merchant_type__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="params[merchant_type]">';
		ticket_form += 							'<option value="'+strings.merchant_type__isracard+'"'+(merchant_type == strings.merchant_type__isracard ? ' selected' : '')+'>'+strings.merchant_type__isracard+'</option>';
		ticket_form += 							'<option value="'+strings.merchant_type__verifone+'"'+(merchant_type == strings.merchant_type__verifone ? ' selected' : '')+'>'+strings.merchant_type__verifone+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';

		ticket_form += 				'<div class="field_container">';
		ticket_form += 					'<div class="label">'+strings.merchant_status+'</div><div class="annotation">'+strings.merchant_status__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="params[merchant_status]" onchange="$(this).val() == \'disabled\' ? generate_password($(\'input[name=password]\')) : $(\'input[name=password]\').val(\'\') ">';
		ticket_form += 							'<option value=""'+(!merchant_status ? ' selected' : '')+'>'+strings.status__active+'</option>';
		ticket_form += 							'<option value="disabled"'+(merchant_status == strings.status__disabled ? ' selected' : '')+'>'+strings.status__disabled+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';

		ticket_form += 			'<div class="field_container"><div class="label">'+strings.company_name+'*</div><div class="annotation">'+strings.company_name__annotation+'</div><div class="field"><input type="text" name="company[name]" value="'+company_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.company_number+'*</div><div class="annotation">'+strings.company_number__annotation+'</div><div class="field"><input class="ltr" type="text" name="company[number]" value="'+company_number+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.company_email+'*</div><div class="annotation">'+strings.company_email__annotation+'</div><div class="field"><input class="ltr company_email" type="email" name="company[email]" value="'+company_email+'"></div></div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.mailer_name+'*</div><div class="annotation">'+strings.mailer_name__annotation+'</div><div class="field"><input type="text" name="sender[name]" value="'+mailer_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.mailer_email+'*</div><div class="annotation">'+strings.mailer_email__annotation+'</div><div class="field"><input class="ltr" type="email" name="sender[email]" value="'+mailer_email+'"></div></div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.purchased_card_readers+'</div><div class="annotation">'+strings.purchased_card_readers__annotation+'</div><div class="field"><input class="ltr" type="text" name="params[purchased_card_readers]" value="'+purchased_card_readers+'"></div></div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.enable_invoices+'</div><div class="annotation">'+strings.enable_invoices__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="enable_invoices" onchange="merchant_enable_invoices($(this).val())">';
		ticket_form += 						'<option value="0">'+strings.no+'</option>';
		ticket_form += 						'<option value="1"'+(invoice_template ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="invoice_parameters_container" style="'+(invoice_template ? 'display:block' : 'display:none')+'">';
		ticket_form += 				'<input type="hidden" name="invoices[template]" value="'+invoice_template+'">';
		
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.charge_starting_number+'*</div><div class="annotation">'+strings.charge_starting_number__annotation+'</div><div class="field mandatory"><input class="ltr" type="text" name="invoices[charge_starting_number]" value="'+charge_starting_number+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.refund_starting_number+'*</div><div class="annotation">'+strings.refund_starting_number__annotation+'</div><div class="field mandatory"><input class="ltr" type="text" name="invoices[refund_starting_number]" value="'+refund_starting_number+'"></div></div>';
		
		ticket_form += 				'<div class="field_container">';
		ticket_form += 					'<div class="label">'+strings.company_type+'</div><div class="annotation">'+strings.company_type__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="params[company_type]">';
		ticket_form += 							'<option value="'+strings.company_type__vat+'"'+(company_type == strings.company_type__vat ? ' selected' : '')+'>'+strings.company_type__vat+'</option>';
		ticket_form += 							'<option value="'+strings.company_type__biz+'"'+(company_type == strings.company_type__biz ? ' selected' : '')+'>'+strings.company_type__biz+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';
		
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_address_street+'</div><div class="annotation">'+strings.company_address_street__annotation+'</div><div class="field"><input type="text" name="params[company][address][street]" value="'+company_address_street+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_address_number+'</div><div class="annotation">'+strings.company_address_number__annotation+'</div><div class="field"><input type="text" name="params[company][address][number]" value="'+company_address_number+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_address_city+'</div><div class="annotation">'+strings.company_address_city__annotation+'</div><div class="field"><input type="text" name="params[company][address][city]" value="'+company_address_city+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_address_zip+'</div><div class="annotation">'+strings.company_address_zip__annotation+'</div><div class="field"><input class="ltr" type="text" name="params[company][address][zip]" value="'+company_address_zip+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_phone+'</div><div class="annotation">'+strings.company_phone__annotation+'</div><div class="field"><input class="ltr" type="tel" name="params[company][phone]" value="'+company_phone+'"></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.company_logo+'</div><div class="annotation">'+strings.company_logo__annotation+'</div><div class="field"><input class="ltr" type="text" name="params[company][logo]" value="'+company_logo+'"></div></div>';
		ticket_form += 			'</div>';

		ticket_form += 			'<div class="field_container"><div class="label">'+strings.business_category+'</div><div class="annotation">'+strings.business_category__annotation+'</div><div class="field"><input type="text" name="params[business][category]" value="'+business_category+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.business_address+'</div><div class="annotation">'+strings.business_address__annotation+'</div><div class="field"><input type="text" name="params[business][address]" value="'+business_address+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.business_contact_name+'</div><div class="annotation">'+strings.business_contact_name__annotation+'</div><div class="field"><input type="text" name="params[business][contact_name]" value="'+business_contact_name+'"></div></div>';

		ticket_form += 			'<div class="field_container"><div class="label">'+strings.access_token_ttl+'*</div><div class="annotation">'+strings.access_token_ttl__annotation+'</div><div class="field"><input class="ltr" type="text" name="access_token_ttl" value="'+access_token_ttl+'"></div></div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.voucher_language+'*</div><div class="annotation">'+strings.voucher_language__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="voucher_language">';
		ticket_form += 						'<option value="he"'+(voucher_language == 'he' ? ' selected' : '')+'>'+strings.voucher_language__he+'</option>';
		ticket_form += 						'<option value="en"'+(voucher_language == 'en' ? ' selected' : '')+'>'+strings.voucher_language__en+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.support_recurring_orders+'*</div><div class="annotation">'+strings.support_recurring_orders__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="support_recurring_orders">';
		ticket_form += 						'<option value="0">'+strings.no+'</option>';
		ticket_form += 						'<option value="1"'+(support_recurring_orders ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.support_refund_trans+'*</div><div class="annotation">'+strings.support_refund_trans__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="support_refund_trans">';
		ticket_form += 						'<option value="1"'+(support_refund_trans ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 						'<option value="0"'+(support_refund_trans ? ' ' : 'selected')+'>'+strings.no+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.support_cancel_trans+'*</div><div class="annotation">'+strings.support_cancel_trans__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="support_cancel_trans">';
		ticket_form += 						'<option value="2"'+(support_cancel_trans == 2 ? ' selected' : '')+'>'+strings.last_only+'</option>';
		ticket_form += 						'<option value="1"'+(support_cancel_trans == 1 ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 						'<option value="0"'+(support_cancel_trans ? ' ' : 'selected')+'>'+strings.no+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.support_new_trans+'*</div><div class="annotation">'+strings.support_new_trans__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="support_new_trans">';
		ticket_form += 						'<option value="1"'+(support_new_trans ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 						'<option value="0"'+(support_new_trans ? ' ' : 'selected')+'>'+strings.no+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.send_daily_report+'</div><div class="annotation">'+strings.send_daily_report__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="send_daily_report">';
		ticket_form += 						'<option value="1"'+(send_daily_report ? ' selected' : '')+'>'+strings.yes+'</option>';
		ticket_form += 						'<option value="0"'+(send_daily_report ? ' ' : 'selected')+'>'+strings.no+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.mobile+'*</div><div class="annotation">'+strings.mobile__annotation+'</div><div class="field mandatory"><input class="ltr validate" type="text" name="mobile" value="'+mobile+'" onkeyup="$(this).val($(this).val().replace(/[^0-9]/g, \'\')); setTimeout(function() { validate_field($(\'input[name=mobile]:visible\')); }, 100);"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="params[note]" value="'+note+'"></div></div>';
		
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.approve+'</button>';
		//ticket_form += 			'<a class="generate_password" onclick="generate_password($(\'input[name=password]\'))">'+strings.generate_password+'</a>&nbsp;&nbsp;&nbsp;<a class="generate_password" onclick="send_password(\'sms\', $(\'input[name=password]\'))">'+strings.send_password_by_sms+'</a>&nbsp;&nbsp;&nbsp;<a class="generate_password" onclick="send_password(\'email\', $(\'input[name=password]\'))">'+strings.send_password_by_email+'</a>';
		ticket_form +=				'<button type="button" onclick="send_password(\'email\', $(\'input[name=username]\'), $(\'input[name=password]\'), \'\', $(\'input.company_email\'))">'+strings.send_password_by_email+'</button>';
		ticket_form +=				'<button type="button" onclick="send_password(\'sms\', $(\'input[name=username]\'), $(\'input[name=password]\'), $(\'input[name=mobile]\'))">'+strings.send_password_by_sms+'</button>';
		if (id && (current_user.type == 'tech-admin' || current_user.type == 'content-admin' || current_user.type == 'gts_admin')) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_type(\'vf_merchant\', \''+id+'\')">'+strings.delete_type+'</button>';
		}
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function merchant_enable_invoices(value) {
	if (value == 1) {
		$('.invoice_parameters_container').show();
		if (!$('input[name="invoices[template]"]').val()) {
			$('input[name="invoices[template]"]').val('default.he');
		}
	} else {
		$('.invoice_parameters_container').hide();
		$('.invoice_parameters_container input').val('');
	}
}

function toggle_vf_user_form(element) {
	
	var id = '';
	var username = '';
	var name = '';
	var email = '';
	var company = '';
	var company_id = '';
	var mobile = '';
	var type = '';
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_user);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_vf_user_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_user);
		if (element) {
			id = $(element).find('.id').html();
			username = $(element).find('.username').html();
			name = $(element).find('.name').html();
			email = $(element).find('.email').html();
			company = $(element).find('.company').html();
			company_id = $(element).find('.company').attr('title');
			mobile = $(element).find('.mobile').html();
			type = $(element).find('.type').html();
			note = $(element).find('.note').attr('title');
		}
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_vf_user_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_save_vf_user">';
		ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_username+'*</div><div class="annotation">'+strings.vf_user_username__annotation+'</div><div class="field"><input type="text" class="gts_user_username ltr" name="gts_user[username]" value="'+username+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.password+(id ? '' : '*')+'</div><div class="annotation">'+strings.password__annotation+'</div><div class="field password"><input class="gts_user_password ltr" type="text" name="gts_user[password]" value=""> <a class="generate_password" onclick="generate_password($(this).parent().find(\'input\'))">'+strings.generate_password+'</a></div></div>';
		
		ticket_form += 				'<div class="field_container">';
		ticket_form += 					'<div class="label">'+strings.type+'*</div><div class="annotation">'+strings.type__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="gts_user[type]">';
		ticket_form += 							'<option value="Isracard"'+(type == 'Isracard' ? ' selected' : '')+'>'+strings.type__isracard+'</option>';
		ticket_form += 							'<option value="Verifone"'+(type == 'Verifone' ? ' selected' : '')+'>'+strings.type__verifone+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_name+'*</div><div class="annotation">'+strings.vf_user_name__annotation+'</div><div class="field"><input type="text" name="gts_user[name]" value="'+name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_email+'*</div><div class="annotation">'+strings.vf_user_email__annotation+'</div><div class="field"><input class="gts_user_email ltr" type="text" name="gts_user[email]" value="'+email+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_company+'*</div><div class="annotation">'+strings.vf_user_company__annotation+'</div><div class="field"><input type="text" name="gts_user[company]" value="'+company+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_company_id+'*</div><div class="annotation">'+strings.vf_user_company_id__annotation+'</div><div class="field"><input type="text" name="gts_user[company_id]" value="'+company_id+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.vf_user_mobile+'*</div><div class="annotation">'+strings.vf_user_mobile__annotation+'</div><div class="field"><input class="gts_user_mobile ltr" type="text" name="gts_user[mobile]" value="'+mobile+'" onkeyup="$(this).val($(this).val().replace(/[^0-9]/g, \'\')); setTimeout(function() { validate_field($(\'input.gts_user_mobile:visible\')); }, 100);"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="gts_user[note]" value="'+note+'"></div></div>';
		
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.approve+'</button>';
		ticket_form +=				'<button type="button" onclick="send_password(\'email\', $(\'input.gts_user_username\'), $(\'input.gts_user_password\'), \'\', $(\'input.gts_user_email\'))">'+strings.send_password_by_email+'</button>';
		ticket_form +=				'<button type="button" onclick="send_password(\'sms\', $(\'input.gts_user_username\'), $(\'input.gts_user_password\'), $(\'input.gts_user_mobile\'))">'+strings.send_password_by_sms+'</button>';
		if (id && (current_user.type == 'tech-admin' || current_user.type == 'content-admin' || current_user.type == 'gts_admin')) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_type(\'vf_user\', \''+id+'\')">'+strings.delete_user+'</button>';
		}
		
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_user_form(element) {
	
	var id = '';
	var username = '';
	var user_name = '';
	var merchant_ids = [];
	var merchant_id = '';
	var note = '';
	
	var merchant_associated;
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_user);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_user_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_user);
		if (element) {
			id = $(element).find('.id').html();
			username = $(element).find('.username').html();
			user_name = $(element).find('.name').html();
			$.each($(element).find('.merchant_id'), function(i) {
				merchant_ids[i] = $(this).val();
			});
			note = $(element).find('.note').attr('title');
		}
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_user_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_save_user">';
		ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.username+'*</div><div class="annotation">'+strings.username__annotation+'</div><div class="field"><input class="ltr" type="text" name="username" value="'+username+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.password+(id ? '' : '*')+'</div><div class="annotation">'+strings.password__annotation+'</div><div class="field"><input class="ltr" type="text" name="password" value=""></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.user_name+'*</div><div class="annotation">'+strings.user_name__annotation+'</div><div class="field"><input type="text" name="name" value="'+user_name+'"></div></div>';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="params[note]" value="'+note+'"></div></div>';
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.merchant_id+'</div><div class="annotation">'+strings.merchant_id__annotation+'</div>';
		if (id) {
			$.each(merchant_ids, function(i, merchant_id) {
				ticket_form += 				'<div class="field">';
				ticket_form += 					'<select name="merchant_id[]">';
				ticket_form += 						'<option value=""></option>';
				$.each(all_merchants, function(i) {
					if (this.id == merchant_id) {
						merchant_associated = true;
					}
					ticket_form += 					'<option value="'+this.id+'"'+(this.id == merchant_id ? ' selected' : '')+'>'+this.name+' - '+this.number+'</option>';
				});
				ticket_form += 					'</select>';
				ticket_form += 				'</div>';
			});
		}
		// If we're editing a merchant - we allow him to add another one
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="merchant_id[]">';
		ticket_form += 						'<option value=""></option>';
		$.each(all_merchants, function(i) {
			ticket_form += 					'<option value="'+this.id+'">'+this.name+' - '+this.number+'</option>';
		});
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		if (id) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_type(\'user\', \''+id+'\')">'+strings.delete_type+'</button>';
		}
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_change_merchant_password_form() {
	window.location = '?reset_password=1';
	// 2015-06-02 - Deprecated due to PCI requirements.
	// We use the "reset password" flow instead.
	/*
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.change_password').html(strings.change_password);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
		});
	} else {
		$('.actions button.change_password').html(strings.cancel_change_password);
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_change_merchant_password_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_change_merchant_password">';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.password+'*</div><div class="annotation">'+strings.password__annotation+'</div><div class="field"><input class="ltr" type="text" name="password" value=""></div></div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
	*/
}

function toggle_change_password_form() {
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.change_password').html(strings.change_password);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
		});
	} else {
		$('.actions button.change_password').html(strings.cancel_change_password);
		var ticket_form;
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_change_password_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_change_user_password">';
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.password+'*</div><div class="annotation">'+strings.password__annotation+'</div><div class="field"><input class="ltr" type="text" name="password" value=""></div></div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_signature_form(element) {
	
	var id = '';
	var timestamp = '';
	var amount = '';
	var cc_last_4 = '';
	var signature_link = '';
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_company);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_signature_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel);
		var ticket_form;
		
		if (element) {
			id = $(element).find('.id').html();
			timestamp = $(element).find('.timestamp').html();
			amount = $(element).find('.amount').html();
			cc_last_4 = $(element).find('.cc_last_4').html();
			signature_link = $(element).find('.signature_link').val();
			note = $(element).find('.note').attr('title');
			
			ticket_form  =	'<div class="ticket_form_container">';
			ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_email_voucher_confirmation_object)">';
			ticket_form += 			'<input type="hidden" name="action" value="gts_email_voucher">';
			ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.amount+'</div><div class="annotation">'+amount+'</div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.timestamp+'</div><div class="annotation">'+timestamp+'</div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.cc_last_4+'</div><div class="annotation">'+cc_last_4+'</div></div>';
			if (signature_link) {
				ticket_form += 			'<div class="field_container"><div class="label">'+strings.signature+'</div><div class="annotation"><img src="'+signature_link+'" /></div></div>';
			}
			if (note) {
				ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+note+'</div></div>';
			}
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.email_voucher+'*</div><div class="annotation">'+strings.email_voucher__annotation+'</div><div class="field"><input type="text" name="email" value=""></div></div>';
			ticket_form += 			'<div class="submit_container">';
			ticket_form += 				'<button type="submit">'+strings.send+'</button>';
			ticket_form += 				'<button type="button" class="close" onclick="toggle_signature_form()">'+strings.close+'</button>';
			ticket_form += 			'</div>';
			ticket_form += 		'</form>';
			ticket_form += '</div>';
		}
		
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function gts_edit_note(element) {
	if (element) {
		var id = $(element).find('.id').html();
		var note = prompt('ערוך הערה:', ($(element).find('.note').attr('title') && $(element).find('.note').attr('title') != null ? $(element).find('.note').attr('title') : ''));
		if (note != null) {
			$.post(href_url, {action:'gts_edit_note', id:id, note:note}, function(data) {
				if (data.status == 'okay') {
					$(element).find('.note').attr('title', note);	
					if (note == '') {
						$(element).find('.note').html('');
					}
				} else if (data.errors) {
					alert(data.errors);
				} else {
					alert(strings.error__general);
				}
			}, 'json');
		}
	}
}

function gts_export(parameters) {
	var answer = prompt('אנא הזינו כתובת אימייל אליה ישלח הדו״ח');
	if (answer != null) {
		parameters.recipient = answer;
		$.post(href_url, parameters);
		alert('הדו״ח ישלח אליכם לאימייל בסיום התהליך.');
	}
}

function toggle_voucher_form(element) {
	
	var id = '';
	var timestamp = '';
	var company_number = '';
	var amount = '';
	var cc_last_4 = '';
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_voucher_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel);
		var ticket_form;
		
		if (element) {
			id = $(element).find('.id').html();
			timestamp = $(element).find('.timestamp').html();
			amount = $(element).find('.amount').html();
			cc_last_4 = $(element).find('.cc_last_4').html();
			note = $(element).find('.note').attr('title');
			
			ticket_form  =	'<div class="ticket_form_container">';
			ticket_form +=		'<form name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_send_voucher_confirmation_object)">';
			ticket_form += 			'<input type="hidden" name="action" value="gts_send_voucher">';
			ticket_form += 			'<input type="hidden" name="id" value="'+id+'">';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.amount+'</div><div class="annotation">'+amount+'</div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.timestamp+'</div><div class="annotation">'+timestamp+'</div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.cc_last_4+'</div><div class="annotation">'+cc_last_4+'</div></div>';
			if (note) {
				ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+note+'</div></div>';
			}
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.email_voucher+'*</div><div class="annotation">'+strings.email_voucher__annotation+'</div><div class="field"><input type="text" name="email" value=""></div></div>';
			ticket_form += 			'<div class="submit_container">';
			ticket_form += 				'<button type="submit">'+strings.send+'</button>';
			ticket_form += 				'<button type="button" class="close" onclick="toggle_voucher_form()">'+strings.close+'</button>';
			ticket_form += 			'</div>';
			ticket_form += 		'</form>';
			ticket_form += '</div>';
		}
		
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_transaction_form(element) {
	
	var id = '';
	var merchant_id = '';
	if ($('select[name=merchant_id]').length > 0 && $('select[name=merchant_id]').val()) {
		merchant_id = $('select[name=merchant_id]').val();
	} else if ($('select[name=merchant_id] option:eq(1)').length > 0 && $('select[name=merchant_id] option:eq(1)').val()) {
		merchant_id = $('select[name=merchant_id] option:eq(1)').val();
	}
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_transaction_form(element);
			}
		});
	} else {
		$('.actions button.add').html('- '+strings.cancel_add_transaction);
		var ticket_form;
		
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form autocomplete="off" name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_transaction_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_submit_transaction">';
		ticket_form += 			'<input type="hidden" name="transaction[merchant_id]" value="'+merchant_id+'">';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.transaction_type+'*</div><div class="annotation">'+strings.transaction_type__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="transaction[type]" onchange="$(\'.ticket_form .data\').hide(); $(\'.ticket_form .\'+$(this).val()+\'.data\').show();">';
		ticket_form += 						'<option value=""></option>';
		if (distributor == 'isracard') {
			ticket_form += 						'<option value="credit">'+strings.transaction_type_credit+'</option>';
		} else {
			ticket_form += 						'<option value="credit">'+strings.transaction_type_credit+'</option>';
			ticket_form += 						'<option value="cash">'+strings.transaction_type_cash+'</option>';
			ticket_form += 						'<option value="check">'+strings.transaction_type_check+'</option>';
		}
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.transaction_amount+'*</div><div class="annotation">'+strings.transaction_amount__annotation+'</div><div class="field"><input type="text" name="transaction[amount]" value=""></div></div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.transaction_currency+'*</div><div class="annotation">'+strings.transaction_currency__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<select name="transaction[currency]">';
		ticket_form += 						'<option value="ILS">'+strings.currency_ils+'</option>';
		ticket_form += 						'<option value="USD">'+strings.currency_usd+'</option>';
		ticket_form += 						'<option value="EUR">'+strings.currency_eur+'</option>';
		ticket_form += 					'</select>';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		// Credit Data
		ticket_form += 			'<div class="credit data">';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_cc_holder_name+'</div><div class="annotation">'+strings.transaction_cc_holder_name__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][cc_holder_name]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_cc_holder_id_number+'</div><div class="annotation">'+strings.transaction_cc_holder_id_number__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][cc_holder_id_number]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_cc_number+'</div><div class="annotation">'+strings.transaction_cc_number__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][cc_number]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_cc_exp+'</div><div class="annotation">'+strings.transaction_cc_exp__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][cc_exp]" pattern="[0-9]{4}" maxlength="4" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_cc_cvv2+'</div><div class="annotation">'+strings.transaction_cc_cvv2__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][cc_cvv2]" value=""></div></div>';
		
		ticket_form += 				'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.transaction_credit_terms+'</div><div class="annotation">'+strings.transaction_credit_terms__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="transaction[credit_data][credit_terms]" onchange="$(this).val() != \'regular\' ? $(\'.payments_number\').show() : $(\'.payments_number\').hide()">';
		ticket_form += 							'<option value=""></option>';
		ticket_form += 							'<option value="regular">'+strings.credit_terms__regular+'</option>';
		ticket_form += 							'<option value="payments">'+strings.credit_terms__payments+'</option>';
		ticket_form += 							'<option value="payments-credit">'+strings.credit_terms__payments_credit+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';
		
		ticket_form += 				'<div class="field_container payments_number" style="display:none">';
		ticket_form += 					'<div class="label">'+strings.transaction_payments_number+'</div><div class="annotation">'+strings.transaction_payments_number__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="transaction[credit_data][payments_number]">';
		for (i = 1; i <= 36; i++) {
			ticket_form += 						'<option value="'+i+'">'+i+'</option>';
		}
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';
		
		ticket_form += 				'<div class="field_container">';
		ticket_form += 					'<div class="label">'+strings.transaction_j5+'</div><div class="annotation">'+strings.transaction_j5__annotation+'</div>';
		ticket_form += 					'<div class="field">';
		ticket_form += 						'<select name="transaction[credit_data][j5]">';
		//ticket_form += 							'<option value=""></option>';
		ticket_form += 							'<option value="0">'+strings.no+'</option>';
		ticket_form += 							'<option value="1">'+strings.yes+'</option>';
		ticket_form += 						'</select>';
		ticket_form += 					'</div>';
		ticket_form += 				'</div>';
		
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_authorization_number+'</div><div class="annotation">'+strings.transaction_authorization_number__annotation+'</div><div class="field"><input type="text" name="transaction[credit_data][authorization_number]" value=""></div></div>';
		ticket_form += 			'</div>';
		
		// Check Data
		ticket_form += 			'<div class="check data">';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_check_number+'</div><div class="annotation">'+strings.transaction_check_number__annotation+'</div><div class="field"><input type="text" name="transaction[check_data][check_number]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_bank_number+'</div><div class="annotation">'+strings.transaction_bank_number__annotation+'</div><div class="field"><input type="text" name="transaction[check_data][bank_number]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_branch_number+'</div><div class="annotation">'+strings.transaction_branch_number__annotation+'</div><div class="field"><input type="text" name="transaction[check_data][branch_number]" value=""></div></div>';
		ticket_form += 				'<div class="field_container"><div class="label">'+strings.transaction_account_number+'</div><div class="annotation">'+strings.transaction_account_number__annotation+'</div><div class="field"><input type="text" name="transaction[check_data][account_number]" value=""></div></div>';
		ticket_form += 			'</div>';
		
		// Invoice Data
		if (gts_merchant.invoice_support == 1) {
			ticket_form += 			'<div class="field_container">';
			ticket_form += 				'<div class="label">'+strings.invoice_enabled+'</div><div class="annotation">'+strings.invoice_enabled__annotation+'</div>';
			ticket_form += 				'<div class="field">';
			ticket_form += 					'<select onchange="if ($(this).val() == 1) { $(\'.ticket_form .invoice\').show(); } else { $(\'.ticket_form .invoice\').hide(); $(\'.ticket_form .invoice input\').val(\'\'); }">';
			ticket_form += 						'<option value="0">'+strings.no+'</option>';
			ticket_form += 						'<option value="1">'+strings.yes+'</option>';
			ticket_form += 					'</select>';
			ticket_form += 				'</div>';
			ticket_form += 			'</div>';
			ticket_form += 			'<div class="invoice">';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_customer_name+'</div><div class="annotation">'+strings.invoice_customer_name__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][customer_name]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_customer_number+'</div><div class="annotation">'+strings.invoice_customer_number__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][customer_number]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_address_street+'</div><div class="annotation">'+strings.invoice_address_street__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][address_street]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_address_city+'</div><div class="annotation">'+strings.invoice_address_city__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][address_city]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_address_zip+'</div><div class="annotation">'+strings.invoice_address_zip__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][address_zip]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_phone+'</div><div class="annotation">'+strings.invoice_phone__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][phone]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_description+'</div><div class="annotation">'+strings.invoice_description__annotation+'</div><div class="field"><input type="text" name="transaction[invoice_data][description]" value=""></div></div>';
			ticket_form += 				'<div class="field_container"><div class="label">'+strings.invoice_recipients+'</div><div class="annotation">'+strings.invoice_recipients__annotation+'</div><div class="field"><input type="text" class="ltr" name="transaction[invoice_data][recipients]" value=""></div></div>';
			ticket_form += 			'</div>';
		}

		ticket_form += 			'<div class="field_container"><div class="label">'+strings.email_voucher+'</div><div class="annotation">'+strings.email_voucher__annotation+'</div><div class="field"><input type="text" name="email" value=""></div></div>';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="transaction[params][note]" value="'+note+'"></div></div>';
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		ticket_form += 			'</div>';
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function toggle_recurring_order_form(element) {
	
	var ro_id = '';
	if ($('select[name=merchant_id]').length > 0 && $('select[name=merchant_id]').val()) {
		var merchant_id = $('select[name=merchant_id]').val();
	} else if ($('select[name=merchant_id] option:eq(1)').length > 0 && $('select[name=merchant_id] option:eq(1)').val()) {
		var merchant_id = $('select[name=merchant_id] option:eq(1)').val();	
	} else {
		var merchant_id = '';
	}
	var name = '';
	var duedate = '';
	var duedate__visual = '';
	var repetitions = '';
	var interval = '';
	var amount = '';
	var currency = '';
	var cc_number = '';
	var cc_type = '';
	var cc_exp = '';
	var cc_last_4 = '';
	var invoice_customer_name = '';
	var invoice_description = '';
	var invoice_recipients = '';
	var note = '';
	
	if ($('.ticket_form_container').length > 0) {
		
		$('.actions button.add').html('+ '+strings.add_recurring_order);
		$('.ticket_form_container').slideUp('fast', function() {
			$('.ticket_form_container').remove();
			if (element) {
				// If the form has already been open - we close it first, then toggle the form again.
				toggle_recurring_order_form(element);
			}
		});
		
	} else {
		
		var ticket_form;
		
		if ($(element).length > 0) {
			var ro_id = $(element).find('.ro_id').html();
			var name = $(element).find('.name').html();
			var duedate = $(element).find('.duedate').val();
			var duedate__visual = duedate.substr(8,2)+'/'+duedate.substr(5,2)+'/'+duedate.substr(0,4);
			var repetitions = $(element).find('.repetitions').html();
			var interval = $(element).find('.interval').val();
			var amount = $(element).find('.amount').html().replace(/[^0-9\.]+/g, '');
			var currency = $(element).find('.currency').val();
			var cc_type = $(element).find('.cc_type').html();
			var cc_exp = $(element).find('.cc_exp').html().replace(/[^0-9\.]+/g, '');
			var cc_last_4 = $(element).find('.cc_last_4').html();
			var invoice_customer_name = $(element).find('.invoice_customer_name').val() ? $(element).find('.invoice_customer_name').val() : '';
			var invoice_description = $(element).find('.invoice_description').val() ? $(element).find('.invoice_description').val() : '';
			var invoice_recipients = $(element).find('.invoice_recipients').val() ? $(element).find('.invoice_recipients').val() : '';
			var note = $(element).find('.note').attr('title');
		}
		
		if (ro_id) {
			$('.actions button.add').html('- '+strings.cancel_update_recurring_order);
		} else {
			$('.actions button.add').html('- '+strings.cancel_add_recurring_order);
		}
		
		ticket_form  =	'<div class="ticket_form_container">';
		ticket_form +=		'<form autocomplete="off" name="ticket_form" action="" class="ticket_form" method="POST" onsubmit="return submit_form(this, gts_recurring_order_confirmation_object)">';
		ticket_form += 			'<input type="hidden" name="action" value="gts_update_recurring_order">';
		ticket_form += 			'<input type="hidden" name="recurring_order[ro_id]" value="'+ro_id+'">';
		ticket_form += 			'<input type="hidden" name="recurring_order[merchant_id]" value="'+merchant_id+'">';
		ticket_form += 			'<input type="hidden" name="recurring_order[cc_type]" value="'+cc_type+'">';
		ticket_form += 			'<input type="hidden" name="recurring_order[cc_last4]" value="'+cc_last_4+'">';
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.recurring_order_name+'*</div><div class="annotation">'+strings.recurring_order_name__annotation+'</div><div class="field"><input type="text" name="recurring_order[name]" value="'+name+'"></div></div>';
		
		ticket_form += 			'<div class="field_container">';
		ticket_form += 				'<div class="label">'+strings.recurring_order_duedate+'*</div><div class="annotation">'+strings.recurring_order_duedate__annotation+'</div>';
		ticket_form += 				'<div class="field">';
		ticket_form += 					'<input type="hidden" id="duedate" name="recurring_order[duedate]" value="'+duedate+'">';
		ticket_form += 					'<input type="text" id="duedate__visual" value="'+duedate__visual+'">';
		ticket_form += 				'</div>';
		ticket_form += 			'</div>';
		
		if (ro_id) {
			ticket_form += 			'<input type="hidden" name="recurring_order[repetitions]" value="'+repetitions+'">';
			ticket_form += 			'<input type="hidden" name="recurring_order[interval]" value="'+interval+'">';
			ticket_form += 			'<input type="hidden" name="recurring_order[currency]" value="'+currency+'">';
		} else {
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.recurring_order_repetitions+'*</div><div class="annotation">'+strings.recurring_order_repetitions__annotation+'</div><div class="field"><input type="number" name="recurring_order[repetitions]" min="1" value="'+repetitions+'"></div></div>';
			
			ticket_form += 			'<div class="field_container">';
			ticket_form += 				'<div class="label">'+strings.recurring_order_interval+'*</div><div class="annotation">'+strings.recurring_order_interval__annotation+'</div>';
			ticket_form += 				'<div class="field">';
			ticket_form += 					'<select name="recurring_order[interval]">';
			ticket_form += 						'<option value="monthly"'+(interval == 'monthly' ? ' selected' : '')+'>'+strings.recurring_order_interval__monthly+'</option>';
			ticket_form += 						'<option value="yearly"'+(interval == 'yearly' ? ' selected' : '')+'>'+strings.recurring_order_interval__yearly+'</option>';
			ticket_form += 					'</select>';
			ticket_form += 				'</div>';
			ticket_form += 			'</div>';
			
			ticket_form += 			'<div class="field_container">';
			ticket_form += 				'<div class="label">'+strings.transaction_currency+'*</div><div class="annotation">'+strings.transaction_currency__annotation+'</div>';
			ticket_form += 				'<div class="field">';
			ticket_form += 					'<select name="recurring_order[currency]">';
			ticket_form += 						'<option value="ILS"'+(currency == 'ILS' ? ' selected' : '')+'>'+strings.currency_ils+'</option>';
			ticket_form += 						'<option value="USD"'+(currency == 'USD' ? ' selected' : '')+'>'+strings.currency_usd+'</option>';
			ticket_form += 						'<option value="EUR"'+(currency == 'EUR' ? ' selected' : '')+'>'+strings.currency_eur+'</option>';
			ticket_form += 					'</select>';
			ticket_form += 				'</div>';
			ticket_form += 			'</div>';
		}
		
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.recurring_order_amount+'*</div><div class="annotation">'+strings.recurring_order_amount__annotation+'</div><div class="field"><input type="text" name="recurring_order[amount]" value="'+amount+'"></div></div>';
		
		if (!ro_id) {
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.transaction_cc_number+'*</div><div class="annotation">'+strings.transaction_cc_number__annotation+'</div><div class="field"><input type="text" name="recurring_order[cc_number]" value=""></div></div>';
		}
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.transaction_cc_exp+'*</div><div class="annotation">'+strings.transaction_cc_exp__annotation+'</div><div class="field"><input type="text" name="recurring_order[cc_exp]" pattern="[0-9]{4}" maxlength="4" value="'+cc_exp+'"></div></div>';
		
		if (gts_merchant.invoice_support == 1) {
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.invoice_customer_name+'</div><div class="annotation">'+strings.invoice_customer_name__annotation+'</div><div class="field"><input type="text" name="recurring_order[invoice_customer_name]" value="'+invoice_customer_name+'"></div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.invoice_description+'</div><div class="annotation">'+strings.invoice_description__annotation+'</div><div class="field"><input type="text" name="recurring_order[invoice_description]" value="'+invoice_description+'"></div></div>';
			ticket_form += 			'<div class="field_container"><div class="label">'+strings.invoice_recipients+'</div><div class="annotation">'+strings.invoice_recipients__annotation+'</div><div class="field"><input type="text" name="recurring_order[invoice_recipients]" value="'+invoice_recipients+'"></div></div>';
		}
			
		ticket_form += 			'<div class="field_container"><div class="label">'+strings.note+'</div><div class="annotation">'+strings.note__annotation+'</div><div class="field"><input type="text" name="recurring_order[params][note]" value="'+note+'"></div></div>';
		
		ticket_form += 			'<div class="submit_container">';
		ticket_form += 				'<button type="submit">'+strings.send+'</button>';
		if (ro_id) {
			ticket_form += 				'<button type="button" class="delete" onclick="gts_delete_recurring_order(\''+ro_id+'\')">'+strings.delete_recurring_order+'</button>';
		}
		ticket_form += 			'</div>';
		
		ticket_form += 		'</form>';
		ticket_form += '</div>';
		
		$('.tickets .actions').after(ticket_form);
		$('.ticket_form_container').slideDown('fast', function() {
			
			// Applying the datepicker to the duedate field.
			$('#duedate__visual').datepicker({ changeYear:true, dateFormat:'dd/mm/yy', altField: '#duedate', altFormat: 'yy-mm-dd'});
			
			$.scrollTo('.tickets', {duration:400});
		});
	}
}

function gts_vf_merchant_associate_user(user_id, merchant_id) {
	if (merchant_id) {
		$.post(href_url, {action:'gts_vf_merchant_associate_user', user_id:user_id, merchant_id:merchant_id}, function(data) {
			if (data.status == 'okay') {
				//alert(strings.confirmation__action_done);
			} else {
				alert(data.errors);
			}
		}, 'json');
	}
}

function gts_delete_recurring_order(id) {
	if (confirm(strings.are_you_sure)) {
		$.post(href_url, {action:'gts_delete_recurring_order', id:id}, function(data) {
			if (data.status == 'okay') {
				alert(strings.confirmation__action_done);
				$('#transaction_'+id).remove();
				eval('toggle_recurring_order_form();');
			} else {
				alert(data.errors);
			}
		}, 'json');
	}
	return false;
}

function gts_delete_type(type, id) {
	if (confirm(strings.are_you_sure)) {
		$.post(href_url, {action:'gts_delete_type', type:type, id:id}, function(data) {
			if (data.status == 'okay') {
				alert(strings.confirmation__action_done);
				$('#'+type+'_'+id).remove();
				eval('toggle_'+type+'_form();');
			} else {
				alert(data.errors);
			}
		}, 'json');
	}
	return false;
}

function gts_cancel_transaction(id) {
	if (confirm(strings.are_you_sure)) {
		$.post(href_url, {action:'gts_cancel_transaction', id:id}, function(data) {
			if (data.status == 'okay') {
				alert(strings.confirmation__action_done);
				$('#transaction_'+id).find('.cancel').html('');
				if (data.transaction.type == 'cash') {
					$('#transaction_'+id).find('.trans_status img').attr('src', href_url+'images/gts/default/status-cash-canceled@2x.png');
					$('#transaction_'+id).find('.trans_type').html('בוטל');
				} else if (data.transaction.type == 'check') {
					$('#transaction_'+id).find('.trans_status img').attr('src', href_url+'images/gts/default/status-check-canceled@2x.png');
					$('#transaction_'+id).find('.trans_type').html('בוטל');
				} else {
					var transaction = $('#transaction_'+id).clone();
					$(transaction).attr('id', '#transaction_'+data.transaction.trans_id);
					$(transaction).find('.amount').html((eval('strings.currency_'+data.transaction.currency.toLowerCase())+' '+data.transaction.amount));
					$(transaction).find('.trans_status img').attr('src', href_url+'images/gts/default/status-credit-test@2x.png');
					$(transaction).find('.cancel').html('');
					$('#transaction_'+id).before(transaction);
				}
			} else {
				alert(data.errors);
			}
		}, 'json');
	}
	return false;
}

function gts_reset_user_password(form) {
	
	var username = $(form).find('input[name=username]').val();
	var company_id = $(form).find('input[name=company_id]').val();
	var link_html = $('.reset_password_link').html();
	
	if (!username || !company_id) {
		alert('אנא מלאו את כל השדות.');
		return;
	} else {
		$('.reset_password_link').html('אנא המתינו בסבלנות בזמן שליחת ההודעה... השליחה יכולה לקחת כ- 30 שניות.');
		$.post(href_url, {action:'gts_reset_user_password', username:username, company_id:company_id}, function(data) {
			$('.reset_password_link').html(link_html);
			if (data.status == 'okay') {
				alert(strings.confirmation__action_done);
			} else {
				alert(data.errors);
			}
		}, 'json');
	}
	return false;
}

/* ## */

var form_validated = true;

function validate_field(field, validation_container) {
	if (!validation_container) {
		validation_container = field;
	}
	
	var validation_required;
	if (/[\*]+/.test($(field).parent().parent().find('.label').text()) || $(field).hasClass('mandatory') || $(field).hasClass('validate')) {
		validation_required = true;
	} else {
		validation_required = false;
		$(field).data('field_validated', true);
	}
	
	if (!$(field).data('validation_in_progress') && validation_required) {
		$(field).data('validation_in_progress', true);

		// Validating - the ID is "stronger" than the NAME attribute, since sometimes the name is "file[]" while the ID is "image_0", and we want it to validate for an image.
		var field_name = $(field).attr('id');
		if (!field_name) {
			field_name = $(field).attr('name');
		}
		var regex;
		var signal_required = false;
		
		if (/mobile/.test(field_name)) {
			regex = /^[\d]{10}$/;
		} else if (/id_number/.test(field_name)) {
			regex = /^[\d]{8,10}$/;
		} else if (/cc_number/.test(field_name)) {
			regex = /^[\d]{8,16}$/;
		} else if (/cc_exp/.test(field_name)) {
			regex = /^[\d]{4}$/;
		} else if (/cc_cvv2/.test(field_name)) {
			regex = /^[\d]{3,4}$/;
		} else if (/title|category/.test(field_name)) {
			regex = /^[\D0-9]+$/;
		} else if (/country/.test(field_name)) {
			regex = /^\D+$/;
		} else if (/number|price|cost|code/.test(field_name)) {
			regex = /^\d+$/;
		} else if (/email/.test(field_name)) {
			regex = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		} else if (/password/.test(field_name)) {
			regex = /^.{6,50}$/;
		} else if (/youtube/.test(field_name)) {
			regex = /^http:\/\/(?:www\.)?youtube.com\/(.*)(\?v=|v\/)(.+)$/;
		} else if (/image/.test(field_name)) {
			// The order here is important - the image REGEX must come BEFORE the file REGEX, since sometimes the ID of the element is "#image_0__file"
			regex = /^([^\.])+\.(jpg|png|gif)$/i;
		} else if (/movie_file/.test(field_name)) {
			regex = /^([^\.])+\.(mov|wmv|avi|mp4|flv)$/i;
		} else if (/file/.test(field_name)) {
			regex = /^([^\.])+\.([a-zA-Z0-9]{3})+$/;
		} else if ($(field).attr('type') != 'checkbox') {
			regex = /(.+)/;
		}
		if (regex != null && regex.test($(field).val())) {
			$(field).data('field_validated', true);
		} else if ($(field).is(':checked')) {
			$(field).data('field_validated', true);
		} else {
			$(field).data('field_validated', false);
		}
		// Checking whether the current signal is correct or not, and placing the relevant one	
		if (($(field).data('field_validated') && $(validation_container).parent().find('.validation span').hasClass('fail')) || (!$(field).data('field_validated') && $(validation_container).parent().find('.validation span').hasClass('ok'))) {
			signal_required = true;
			$(validation_container).parent().find('.validation span:visible').fadeOut(400, function() {
				$(validation_container).parent().find('.validation').remove();
			});
		} else if ($(validation_container).parent().find('.validation span').length == 0) {
			signal_required = true;
		}
		setTimeout(function() {
			if (signal_required) {
				if ($(field).data('field_validated')) {
					$(validation_container).parent().prepend('<div class="validation"><span class="ok">&#10004;</span></div>');
				} else {
					$(validation_container).parent().prepend('<div class="validation"><span class="fail">&#10008;</span></div>');
				}
				$(validation_container).parent().find('.validation span').fadeIn('fast', function() {
					$(field).data('validation_in_progress', false);
				});
			} else {
				$(field).data('validation_in_progress', false);
			}
		}, 600); // Must be at least 100ms greater than the fadeOut speed (otherwise, sometimes the replacement of V with X won't work
	}
}

function validate_form(form) {
	form_validated = true;
	$(form).find('.field input:visible, label > input[type=checkbox]:visible, .field textarea:visible, .field select:visible').each(function(i, item) {
		if (!$(item).hasClass('validate')) {
			// If an item HAS the class "validate" - it means we only wanted to validate it in real-time, but we do NOT want to validate it when the form is submitted.
			validate_field(item);
			if (!$(item).data('field_validated')) {
				form_validated = false;
			}
		}
	});
	$(form).find('.checkbox_field .checkbox_container input[type=checkbox]').each(function(i, item) {
		validate_field(item, $(item).parent());
		if (!$(item).data('field_validated')) {
			form_validated = false;
		}
	});
	if (form_validated) {
		return true;
	} else {
		return false;
	}
}

function submit_form(form, confirmation_object) {
	error_object = {};
	if (!submission_in_progress) {
		if (validate_form(form)) {
			submission_in_progress = true;
			$(form).ajaxSubmit({
				success:function(data) {
					//console.log(data);
					if (data.status == 'okay') {
						if (confirmation_object.callback) {
							confirmation_object.callback(data);
						} else {
							alert(strings.done);
						}
					} else {
						alert(data.errors);
						if (data.callback) {
							eval(data.callback);
						}
					}
					submission_in_progress = false;
				},
		 	    dataType:'json'
			});
		} else {
			alert(strings.error__mandatory);
			submission_in_progress = false;
		}
	}
	return false;
}

var hebrew_chars = /^[אבגדהוזחטיכלמנסעפצקרשתךןףץ]+/;

$(document).ready(function() {
	
	/* jQuery - Datepicker */
	var date_format = 'dd/mm/yy'; // This is what the user will see
    var date_format_alt = 'yymmdd'; // This is what you need
    $.datepicker.setDefaults($.datepicker.regional['he']);
	$('#from_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#from_date', altFormat: date_format_alt});
	$('#to_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#to_date', altFormat: date_format_alt});
	$('#from_created_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#from_created_date', altFormat: date_format_alt});
	$('#to_created_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#to_created_date', altFormat: date_format_alt});
	$('#from_stats_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#from_stats_date', altFormat: date_format_alt});
	$('#to_stats_date__visual').datepicker({ changeYear:true, dateFormat: date_format, altField: '#to_stats_date', altFormat: date_format_alt});
	
	$(document).on('change', '#from_date__visual', function() {
		if (parseFloat($('#to_date').val()) < parseFloat($('#from_date').val())) {
			alert(strings.error__invalid_from_date);
			$('#from_date').val('');
			$('#from_date__visual').val('');
		}
	});
	$(document).on('change', '#to_date__visual', function() {
		if (parseFloat($('#to_date').val()) < parseFloat($('#from_date').val())) {
			alert(strings.error__invalid_to_date);
			$('#to_date').val('');
			$('#to_date__visual').val('');
		}
	});

	$('.ui-datepicker').hide(); // Sometimes the datepicker appears at the bottom of the page, but it shouldn't.. it's a bug in jQuery	
	
	
	$('.tickets .ticket.row').live('mouseover', function() {
		$(this).addClass('hover');
	});
	$('.tickets .ticket.row').live('mouseout', function() {
		$(this).removeClass('hover');
	});
	
	// Hebrew detection
    $('textarea').live('keypress', function (e) {
            var text_is_in_hebrew;
            
            if ($(this).val() == '') {
                text_is_in_hebrew = hebrew_chars.test(String.fromCharCode(e.keyCode));
            } else {
                text_is_in_hebrew = hebrew_chars.test($(this).val());
            }
            
            if (text_is_in_hebrew) {
                $(this).addClass('hebrew').removeClass('english');
            } else {
                $(this).addClass('english').removeClass('hebrew');
            }
        }
    );
	
	gts_company_confirmation_object = {};
	gts_company_confirmation_object.confirmation = strings.done;
	gts_company_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_company);
		alert(strings.confirmation__data_saved);
		//window.location = href_url+doc;
		// Appending the new row
		cells = '\
        	<div class="ticket_d id">'+data.company.id+'</div>\
			<div class="ticket_d company_name">'+data.company.name+'</div>\
			<div class="ticket_d company_number">'+data.company.number+'</div>\
			<div class="ticket_d mailer_name">'+data.company.mailer_name+'</div>\
			<div class="ticket_d mailer_email"><a href="mailto:'+data.company.mailer_email+'">'+data.company.mailer_email+'</a></div>\
			<div class="ticket_d note" title="'+(data.company.params && data.company.params.note ? data.company.params.note : '')+'">'+(data.company.params && data.company.params.note ? '[ ! ]' : '')+'</div>\
			<div class="ticket_d created">'+strings.just_now+'</div>\
        ';
		row = '<div id="company_'+data.company.id+'" class="ticket row" onclick="toggle_company_form(this)">'+cells+'</div>';
        if ($('#company_'+data.company.id).length > 0) {
	        // Updating an existing row
	        $('#company_'+data.company.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
		toggle_company_form();
	}
	
	gts_merchant_confirmation_object = {};
	gts_merchant_confirmation_object.confirmation = strings.done;
	gts_merchant_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_merchant);
		alert(strings.confirmation__data_saved);
		cells = '\
        	<div class="ticket_d id">'+data.merchant.id+'</div>\
			<div class="ticket_d merchant_name">'+data.merchant.name+'</div>\
			<div class="ticket_d merchant_number">'+data.merchant.number+'</div>\
			<div class="ticket_d company_name" title="'+data.merchant.company_id+'">'+(companies_ids_hash[data.merchant.company_id] && companies_ids_hash[data.merchant.company_id].name ? companies_ids_hash[data.merchant.company_id].name : '')+'</div>\
			<div class="ticket_d pos_number">'+(data.merchant.pos_number ? data.merchant.pos_number : '')+'</div>\
			<div class="ticket_d report_inactivity">'+(data.merchant.report_inactivity == 'true' ? '+' : '')+'</div>\
			<div class="ticket_d created">'+($('#merchant_'+data.merchant.id).length > 0 ? $('#merchant_'+data.merchant.id+' .created').html() : strings.just_now)+'</div>\
			<div class="ticket_d modified">'+strings.just_now+'</div>\
        ';
		row = '<div id="merchant_'+data.merchant.id+'" class="ticket row merchant" onclick="toggle_merchant_form(this)">'+cells+'</div>';
        if ($('#merchant_'+data.merchant.id).length > 0) {
	        // Updating an existing row
	        $('#merchant_'+data.merchant.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
		toggle_merchant_form();
	}
	
	gts_vf_merchant_confirmation_object = {};
	gts_vf_merchant_confirmation_object.confirmation = strings.done;
	gts_vf_merchant_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_merchant);
		alert(strings.confirmation__data_saved);
		cells = '\
			<input type="hidden" class="mailer_name" value="'+data.merchant.sender.name+'">\
			<input type="hidden" class="mailer_email" value="'+data.merchant.sender.email+'">\
			<input type="hidden" class="access_token_ttl" value="'+(data.merchant.access_token_ttl ? data.merchant.access_token_ttl : '')+'">\
			<input type="hidden" class="voucher_language" value="'+data.merchant.voucher_language+'">\
			<input type="hidden" class="allow_duplicated_transactions" value="'+data.merchant.allow_duplicated_transactions+'">\
			<input type="hidden" class="support_recurring_orders" value="'+data.merchant.support_recurring_orders+'">\
			<input type="hidden" class="support_refund_trans" value="'+data.merchant.support_refund_trans+'">\
			<input type="hidden" class="support_cancel_trans" value="'+data.merchant.support_cancel_trans+'">\
			<input type="hidden" class="support_new_trans" value="'+data.merchant.support_new_trans+'">\
			<input type="hidden" class="send_daily_report" value="'+data.merchant.send_daily_report+'">\
			<input type="hidden" class="charge_starting_number" value="'+(data.merchant.invoices && data.merchant.invoices.charge_starting_number ? data.merchant.invoices.charge_starting_number : '')+'">\
			<input type="hidden" class="refund_starting_number" value="'+(data.merchant.invoices && data.merchant.invoices.refund_starting_number ? data.merchant.invoices.refund_starting_number : '')+'">\
			<input type="hidden" class="invoice_template" value="'+(data.merchant.invoices && data.merchant.invoices.template ? data.merchant.invoices.template : '')+'">\
			<input type="hidden" class="purchased_card_readers" value="'+(data.merchant.params.purchased_card_readers ? data.merchant.params.purchased_card_readers : '')+'">\
			<input type="hidden" class="shva_transactions_username" value="'+(data.merchant.shva_transactions_username ? data.merchant.shva_transactions_username : '')+'">\
			\
			<input type="hidden" class="company_email" value="'+(data.merchant.company && data.merchant.company.email ? data.merchant.company.email : '')+'">\
			<input type="hidden" class="company_type" value="'+(data.merchant.params.company && data.merchant.params.company.number_type ? data.merchant.params.company.number_type : '')+'">\
			<input type="hidden" class="company_address_street" value="'+(data.merchant.params.company && data.merchant.params.company.address && data.merchant.params.company.address.street ? data.merchant.params.company.address.street : '')+'">\
			<input type="hidden" class="company_address_number" value="'+(data.merchant.params.company && data.merchant.params.company.address && data.merchant.params.company.address.number ? data.merchant.params.company.address.number : '')+'">\
			<input type="hidden" class="company_address_city" value="'+(data.merchant.params.company && data.merchant.params.company.address && data.merchant.params.company.address.city ? data.merchant.params.company.address.city : '')+'">\
			<input type="hidden" class="company_address_zip" value="'+(data.merchant.params.company && data.merchant.params.company.address && data.merchant.params.company.zip ? data.merchant.params.company.address.zip : '')+'">\
			<input type="hidden" class="company_phone" value="'+(data.merchant.params.company && data.merchant.params.company.phone ? data.merchant.params.company.phone : '')+'">\
			<input type="hidden" class="company_logo" value="'+(data.merchant.params.company && data.merchant.params.company.logo ? data.merchant.params.company.logo : '')+'">\
			\
			<input type="hidden" class="business_category" value="'+(data.merchant.params.business && data.merchant.params.business.category ? data.merchant.params.business.category : '')+'">\
			<input type="hidden" class="business_address" value="'+(data.merchant.params.business && data.merchant.params.business.address ? data.merchant.params.business.address : '')+'">\
			<input type="hidden" class="business_contact_name" value="'+(data.merchant.params.business && data.merchant.params.business.contact_name ? data.merchant.params.business.contact_name : '')+'">\
			\
			<div class="ticket_d id">'+data.merchant.id+'</div>\
			<div class="ticket_d username">'+data.merchant.username+'</div>\
			<div class="ticket_d merchant_number">'+data.merchant.number+'</div>\
			<div class="ticket_d merchant_type">'+(data.merchant.params && data.merchant.params.merchant_type ? data.merchant.params.merchant_type : '')+'</div>\
			<div class="ticket_d merchant_status">'+(data.merchant.params && data.merchant.params.merchant_status ? eval('strings.status__'+data.merchant.params.merchant_status) : strings.status__active)+'</div>\
			<div class="ticket_d company_name" title="'+data.merchant.company.number+'">'+data.merchant.company.name+'</div>\
			<div class="ticket_d card_readers">'+(data.merchant.params && data.merchant.params.purchased_card_readers ? (data.merchant.stats && data.merchant.stats.card_readers ? data.merchant.stats.card_readers : 0)+'/'+data.merchant.params.purchased_card_readers :(data.merchant.stats && data.merchant.stats.card_readers ? data.merchant.stats.card_readers : 0))+'</div>\
			<div class="ticket_d cc_count">'+(data.merchant.stats && data.merchant.stats.trans_count && data.merchant.trans_count.credit ? data.merchant.trans_count.credit : 0)+'</div>\
			<div class="ticket_d checks_count">'+(data.merchant.stats && data.merchant.stats.trans_check_num ? data.merchant.stats.trans_check_num : 0)+'</div>\
			<div class="ticket_d cash_count">'+(data.merchant.stats && data.merchant.stats.trans_cash_num ? data.merchant.stats.trans_cash_num : 0)+'</div>\
			<!-- "Enabled Invoices" is determined by the invoices.template variable. There is no "enable_invoices" parameter -->\
			<div class="ticket_d enable_invoices">'+(data.merchant.invoices && data.merchant.invoices.template ? '+' : '-')+'</div>\
			<div class="ticket_d mobile" title="'+(data.merchant.mobile ? data.merchant.mobile : '')+'">'+(data.merchant.mobile ? '✓' : '<span class="red">×</span>')+'</div>\
			<div class="ticket_d bounce" title="'+(data.merchant.company && data.merchant.company.email ? data.merchant.company.email : '')+'">'+(data.merchant.company && data.merchant.company.email ? '✓' : '<span class="red">×</span>')+'</div>\
			<div class="ticket_d created">'+($('#vf_merchant_'+data.merchant.id).length > 0 ? $('#vf_merchant_'+data.merchant.id+' .created').html() : strings.just_now)+'</div>\
			<div class="ticket_d note" title="'+(data.merchant.params && data.merchant.params.note ? data.merchant.params.note : '')+'">'+(data.merchant.params && data.merchant.params.note ? '[ ! ]' : '')+'</div>\
        ';
		row = '<div id="vf_merchant_'+data.merchant.id+'" class="ticket row merchant" onclick="toggle_vf_merchant_form(this)">'+cells+'</div>';
        if ($('#vf_merchant_'+data.merchant.id).length > 0) {
	        // Updating an existing row
	        $('#vf_merchant_'+data.merchant.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        toggle_vf_merchant_form();
	}
	
	gts_vf_user_confirmation_object = {};
	gts_vf_user_confirmation_object.confirmation = strings.done;
	gts_vf_user_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_user);
		alert(strings.confirmation__data_saved);
		cells = '\
        	<div class="ticket_d id">'+data.gts_user.id+'</div>\
			<div class="ticket_d username">'+data.gts_user.username+'</div>\
			<div class="ticket_d type">'+data.gts_user.type+'</div>\
			<div class="ticket_d name">'+data.gts_user.name+'</div>\
			<div class="ticket_d email">'+data.gts_user.email+'</div>\
			<div class="ticket_d company" title="'+data.gts_user.company_id+'">'+data.gts_user.company+'</div>\
			<div class="ticket_d mobile">'+data.gts_user.mobile+'</div>\
			<div class="ticket_d created">'+($('#vf_merchant_'+data.gts_user.id).length > 0 ? $('#vf_merchant_'+data.gts_user.id+' .created').html() : strings.just_now)+'</div>\
			<div class="ticket_d note" title="'+(data.gts_user.note ? data.gts_user.note : '')+'">'+(data.gts_user.note ? '[ ! ]' : '')+'</div>\
		';
		row = '<div id="vf_user_'+data.gts_user.id+'" class="ticket row merchant user" onclick="toggle_vf_user_form(this)">'+cells+'</div>';
        if ($('#vf_user_'+data.gts_user.id).length > 0) {
	        // Updating an existing row
	        $('#vf_user_'+data.gts_user.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        toggle_vf_user_form();
	}
	
	gts_user_confirmation_object = {};
	gts_user_confirmation_object.confirmation = strings.done;
	gts_user_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_user);
		alert(strings.confirmation__data_saved);
		cells = '\
        	<div class="ticket_d id">'+data.user.id+'</div>\
			<div class="ticket_d username">'+data.user.username+'</div>\
			<div class="ticket_d name">'+data.user.name+'</div>\
			<div class="ticket_d status">'+($('#user_'+data.user.id).length > 0 ? $('#user_'+data.user.id+' .status').html() : strings.status__active)+'</div>\
			<div class="ticket_d created">'+($('#user_'+data.user.id).length > 0 ? $('#user_'+data.user.id+' .created').html() : strings.just_now)+'</div>\
			<div class="ticket_d note" title="'+(data.user.params && data.user.params.note ? data.user.params.note : '')+'">'+(data.user.params && data.user.params.note ? '[ ! ]' : '')+'</div>\
        ';
        if (data.user.merchant_ids) {
			$.each(data.user.merchant_ids, function(i) {
    			cells = cells + '<input type="hidden" class="merchant_id" value="'+this+'">';
			});
		}
		row = '<div id="user_'+data.user.id+'" class="ticket row" onclick="toggle_user_form(this)">'+cells+'</div>';
        if ($('#user_'+data.user.id).length > 0) {
	        // Updating an existing row
	        $('#user_'+data.user.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        if (data.user.password) {
			// Updating the password first (if required)
			$.post(href_url, {action:'gts_change_user_password', id:data.user.id, password:data.user.password}, function(data) {
				if (data.status == 'okay') {
					toggle_user_form();
				} else {
					alert(data.errors);
				}
			}, 'json');
		} else {
			toggle_user_form();
		}
	}
	
	gts_change_merchant_password_confirmation_object = {};
	gts_change_merchant_password_confirmation_object.confirmation = strings.done;
	gts_change_merchant_password_confirmation_object.callback = function(data) {
		$('.actions button.change_password').html(strings.change_password);
		toggle_change_merchant_password_form();
		alert(strings.confirmation__data_saved);
	}
	
	gts_must_change_password_confirmation_object = {};
	gts_must_change_password_confirmation_object.confirmation = strings.done;
	gts_must_change_password_confirmation_object.callback = function(data) {
		window.location.reload();
	}
	
	// Used by signatures
	gts_change_password_confirmation_object = {};
	gts_change_password_confirmation_object.confirmation = strings.done;
	gts_change_password_confirmation_object.callback = function(data) {
		$('.actions button.change_password').html(strings.change_password);
		toggle_change_password_form();
		alert(strings.confirmation__data_saved);
	}
	
	gts_signature_confirmation_object = {};
	gts_signature_confirmation_object.confirmation = strings.done;
	gts_signature_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		alert(strings.confirmation__data_saved);
		cells = '\
        	<div class="ticket_d id">'+data.transaction.id+'</div>\
        	<div class="ticket_d note" title="'+(data.transaction.params && data.transaction.params.note ? data.transaction.params.note : '')+'">'+(data.transaction.params && data.transaction.params.note ? '[ ! ]' : '')+'</div>\
			<div class="ticket_d timestamp">'+($('#transaction_'+data.transaction.id).length > 0 ? $('#transaction_'+data.transaction.id+' .timestamp').html() : strings.just_now)+'</div>\
			<div class="ticket_d modified">'+strings.just_now+'</div>\
        ';
		row = '<div id="transaction_'+data.transaction.id+'" class="ticket row transaction" onclick="toggle_signature_form(this)">'+cells+'</div>';
        if ($('#transaction_'+data.transaction.id).length > 0) {
	        // Updating an existing row
	        $('#transaction_'+data.transaction.id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        toggle_signature_form();
	}
	
	gts_send_voucher_confirmation_object = {};
	gts_send_voucher_confirmation_object.confirmation = strings.done;
	gts_send_voucher_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		alert(strings.confirmation__data_sent);
		toggle_voucher_form();
	}
	
	gts_email_voucher_confirmation_object = {};
	gts_email_voucher_confirmation_object.confirmation = strings.done;
	gts_email_voucher_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_user);
		alert(strings.confirmation__data_sent);
		toggle_signature_form();
	}
	
	gts_transaction_confirmation_object = {};
	gts_transaction_confirmation_object.confirmation = strings.done;
	gts_transaction_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		alert(strings.confirmation__action_done);
		cells = '\
        	<div class="ticket_d cancel">\
        		'+(data.transaction.type == 'credit' && parseFloat(data.transaction.amount) > 0 && data.transaction.status != 'canceled' ? '\
        			<a title="'+strings.cancel_transaction+'" href="javascript:gts_cancel_transaction(\''+data.transaction.trans_id+'\');stop_propagation()">&times;</a>\
        		' : '')+'\
        	</div>\
        	<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d trans_status"><img src="'+data.transaction.status_icon_url+'"></div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d id">'+(data.transaction.trans_id)+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d amount">'+(eval('strings.currency_'+data.transaction.currency.toLowerCase())+' '+data.transaction.amount)+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d trans_type">'+(data.transaction.credit_data && data.transaction.credit_data.credit_terms ? eval('strings.credit_terms__'+data.transaction.credit_data.credit_terms.toLowerCase().replace('-', '_')) : (data.transaction.type ? eval('strings.transaction_type_'+data.transaction.type.toLowerCase()) : '-'))+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d cc_type">'+(data.transaction.credit_data && data.transaction.credit_data.cc_type ? data.transaction.credit_data.cc_type : '-')+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d cc_last_4">'+(data.transaction.credit_data && data.transaction.credit_data.cc_last_4 ? data.transaction.credit_data.cc_last_4 : '-')+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d cc_exp">'+(data.transaction.credit_data && data.transaction.credit_data.cc_exp ? data.transaction.credit_data.cc_exp : '-')+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d authorization_number">'+(data.transaction.credit_data && data.transaction.credit_data.authorization_number ? data.transaction.credit_data.authorization_number : '-')+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d voucher_number">'+(data.transaction.credit_data && data.transaction.credit_data.voucher_number ? data.transaction.credit_data.voucher_number : '-')+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d timestamp">'+($('#transaction_'+data.transaction.trans_id).length > 0 ? $('#transaction_'+data.transaction.trans_id+' .timestamp').html() : strings.just_now)+'</div>\
			<div onclick="toggle_voucher_form($(this).closest(\'.ticket.row.transaction\'))" class="ticket_d note" title="'+(data.transaction.params && data.transaction.params.note ? data.transaction.params.note : '')+'">'+(data.transaction.params && data.transaction.params.note ? '[ ! ]' : '')+'</div>\
			<div class="ticket_d invoice_link">\
				'+(data.transaction.invoice_data && data.transaction.invoice_data.link ? '\
        			<a href="'+data.transaction.invoice_data.link+'" target="_blank"><img src="'+href_url+'images/gts/default/invoice@2x.png"></a>\
        		' : '')+'\
			</div>\
        ';
        
        var title = '';
        if (data.transaction.type == 'check' && data.transaction.check_data) {
	        title = strings.transaction_check_number+': '+data.transaction.check_data.check_number+"\n"+strings.transaction_account_number+': '+data.transaction.check_data.account_number+"\n"+strings.transaction_bank_number+': '+data.transaction.check_data.bank_number;
	    }
        
		row = '<div id="transaction_'+data.transaction.trans_id+'" class="ticket row transaction" title="'+title+'">'+cells+'</div>';
        
        if ($('#transaction_'+data.transaction.trans_id).length > 0) {
	        // Updating an existing row
	        $('#transaction_'+data.transaction.trans_id).html(cells);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        toggle_transaction_form();
	}
	
	gts_recurring_order_confirmation_object = {};
	gts_recurring_order_confirmation_object.confirmation = strings.done;
	gts_recurring_order_confirmation_object.callback = function(data) {
		$('.actions button.add').html('+ '+strings.add_transaction);
		alert(strings.confirmation__action_done);
		cells = '\
        	<input type="hidden" class="currency" value="'+data.recurring_order.currency+'">\
			<input type="hidden" class="duedate" value="'+data.recurring_order.duedate+'">\
			<input type="hidden" class="interval" value="'+data.recurring_order.interval+'">\
			<input type="hidden" class="invoice_customer_name" value="'+(data.recurring_order.invoice_customer_name ? data.recurring_order.invoice_customer_name : '')+'">\
			<input type="hidden" class="invoice_description" value="'+(data.recurring_order.invoice_description ? data.recurring_order.invoice_description : '')+'">\
			<input type="hidden" class="invoice_recipients" value="'+(data.recurring_order.invoice_recipients ? data.recurring_order.invoice_recipients : '')+'">\
			\
        	<div class="ticket_d ro_id">'+data.recurring_order.ro_id+'</div>\
			<div class="ticket_d name">'+data.recurring_order.name+'</div>\
			<div class="ticket_d duedate__visual">'+(data.recurring_order.duedate.substr(8,2)+'/'+data.recurring_order.duedate.substr(5,2)+'/'+data.recurring_order.duedate.substr(0,4))+'</div>\
			<div class="ticket_d repetitions">'+data.recurring_order.repetitions+'</div>\
			<div class="ticket_d interval__visual">'+(eval('strings.recurring_order_interval__'+data.recurring_order.interval.toLowerCase()))+'</div>\
			<div class="ticket_d amount">'+(eval('strings.currency_'+data.recurring_order.currency.toLowerCase())+' '+data.recurring_order.amount)+'</div>\
			<div class="ticket_d cc_type">'+(data.recurring_order.cc_type ? data.recurring_order.cc_type : '-')+'</div>\
			<div class="ticket_d cc_last_4">'+(data.recurring_order.cc_last4 ? data.recurring_order.cc_last4 : '-')+'</div>\
			<div class="ticket_d cc_exp">'+(data.recurring_order.cc_exp ? data.recurring_order.cc_exp : '-')+'</div>\
			<div class="ticket_d created">'+strings.just_now+'</div>\
			<div class="ticket_d note" title="'+(data.recurring_order.params && data.recurring_order.params.note ? data.recurring_order.params.note : '')+'">'+(data.recurring_order.params && data.recurring_order.params.note ? '[ ! ]' : '')+'</div>\
        ';
		row = '<div id="transaction_'+data.recurring_order.ro_id+'" class="ticket row recurring_order" onclick="toggle_recurring_order_form(this)">'+cells+'</div>';
        if ($('#transaction_'+data.recurring_order.ro_id).length > 0) {
	        // Updating an existing row
	        $('#transaction_'+data.recurring_order.ro_id).html(row);
        } else {
	    	// Adding a new row
	    	$('.ticket.row.th').after(row);
        }
        toggle_recurring_order_form();
	}
	
	if (/gts|tasks|support|order-form/.test(doc) || $(document).width() < 600) {
		$('.background').css({opacity:1}).show();
		$('.toolbar_container').show();
		$('.footer_container').show();
		$('.box.high .paragon_logo').show();
		$('.content').show();
	} else {
		setTimeout(function() {
			$('.background').animate({opacity:1}, 1000, function () {
				$('.toolbar_container').slideToggle('fast', function () {
					$('.content').fadeIn('slow');
				});
			});
		}, 1000);
	
		setTimeout(function() {
			$('.box.high .paragon_logo').fadeIn(400);
		}, 3000);
	}
	
});
