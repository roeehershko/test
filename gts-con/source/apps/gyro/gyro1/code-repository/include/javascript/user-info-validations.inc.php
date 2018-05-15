<? /* if ($user['id'] == 501) { ?>
	<script type="text/javascript" src="<?=href('repository/include/javascript/form_fields_validation_debug.js')?>"></script>
<? } ?>
	<script type="text/javascript" src="<?=href('repository/include/javascript/form_fields_validation.js')?>"></script>
<? } */ ?>

<script type="text/javascript" src="<?=href('repository/include/javascript/form_fields_validation.js')?>"></script>

<script type="text/javascript">
	
	var submission_in_progress = 0;
    
	function validate(node) {
	
		// Variables
        validation = 1;
        
        // Validating the general fields automatically using the REPO form_fields_validation function
        field_error = form_fields_validation(node);
        
        if (field_error == 0) {
            // If all is okay, we remove the error alert, if exists
            document.getElementById('error__general').style.display = 'none';
        } else {
            // Displaying the client side error (the one at the top of the page)
            document.getElementById('error__general').style.display = '';
            validation = 0;
        }
        
        // Birth Date
		/*
		var date_of_birth_dd;
		var date_of_birth_mm;
		var date_of_birth_yyyy;
		if ( (document.getElementById('date_of_birth_tmp_day').value) && (document.getElementById('date_of_birth_tmp_month').value) && (document.getElementById('date_of_birth_tmp_year').value) ) {
			date_of_birth_dd = document.getElementById('date_of_birth_tmp_day').value;
			date_of_birth_mm = document.getElementById('date_of_birth_tmp_month').value;
			date_of_birth_yyyy = document.getElementById('date_of_birth_tmp_year').value;
			if ( 
					(!/^\d{2}$/.test(date_of_birth_dd)) &&
					(!/^\d{2}$/.test(date_of_birth_mm)) &&
			 		(!/^\d{4}$/.test(date_of_birth_yyyy))
			 	) {
				//document.getElementById('errors').style.display = '';
				//document.getElementById('error_date_of_birth').style.display = '';
				validation = 0;	
			} else {
				document.getElementById('date_of_birth').value = date_of_birth_yyyy+'-'+date_of_birth_mm+'-'+date_of_birth_dd;
				//document.getElementById('error_date_of_birth').style.display = 'none';
			}
		}
        */
        
        // Password
		<? if ($doc == 'register' || (($doc == 'checkout' || $doc == 'activation') && $_GET['step'] == 'billing-info' && !$user) ) { ?>
			
			if (document.getElementById('password_1').value == '' || document.getElementById('password_1').value.length < 6) {
                document.getElementById('error__password_1').style.display = '';
                document.getElementById('label_of_password_1').className = 'error ' + document.getElementById('label_of_password_1').className.replace(/(error ?)/,'');
                document.getElementById('password_1').className = 'error ' + document.getElementById('password_1').className.replace(/(error ?)/,'');
                document.getElementById('label_of_password_2').className = 'error ' + document.getElementById('label_of_password_2').className.replace(/(error ?)/,'');
                document.getElementById('password_2').className = 'error ' + document.getElementById('password_2').className.replace(/(error ?)/,'');
                validation = 0;	
            } else {
                document.getElementById('error__password_1').style.display = 'none';
                document.getElementById('label_of_password_1').className = document.getElementById('label_of_password_1').className.replace(/(error ?)/,'');
                document.getElementById('password_1').className = document.getElementById('password_1').className.replace(/(error ?)/,'');
                document.getElementById('label_of_password_2').className = document.getElementById('label_of_password_2').className.replace(/(error ?)/,'');
                document.getElementById('password_2').className = document.getElementById('password_2').className.replace(/(error ?)/,'');
            }
		
		<? } else { ?>
		
            if ( (document.getElementById('password_1').value != '') && (document.getElementById('password_1').value.length < 6) ) {
                document.getElementById('error__password_1').style.display = '';
                document.getElementById('label_of_password_1').className = 'error ' + document.getElementById('label_of_password_1').className.replace(/(error ?)/,'');
                document.getElementById('password_1').className = 'error ' + document.getElementById('password_1').className.replace(/(error ?)/,'');
                validation = 0;	
            } else {
                document.getElementById('error__password_1').style.display = 'none';
                document.getElementById('label_of_password_1').className = document.getElementById('label_of_password_1').className.replace(/(error ?)/,'');
                document.getElementById('password_1').className = document.getElementById('password_1').className.replace(/(error ?)/,'');
            }
		
		<? } ?>
		
		// Password 2 (confirmation)
		if (
		        <? if ($doc == 'register' || (($doc == 'checkout' || $doc == 'activation') && $_GET['step'] == 'billing-info' && !$user) ) { ?>
		            (document.getElementById('password_1').value == '') ||
		        <? } ?>
				(document.getElementById('password_1').value.length != document.getElementById('password_2').value.length) ||
				(document.getElementById('password_1').value != document.getElementById('password_2').value)
		) {
			document.getElementById('error__password_2').style.display = '';
            document.getElementById('label_of_password_2').className = 'error ' + document.getElementById('label_of_password_2').className.replace(/(error ?)/,'');
            document.getElementById('password_2').className = 'error ' + document.getElementById('password_2').className.replace(/(error ?)/,'');
            validation = 0;
		} else {
			document.getElementById('error__password_2').style.display = 'none';
		    document.getElementById('label_of_password_2').className = document.getElementById('label_of_password_2').className.replace(/(error ?)/,'');
            document.getElementById('password_2').className = document.getElementById('password_2').className.replace(/(error ?)/,'');
        }

        <? if ($doc == 'register' || $doc == 'checkout' || $doc == 'activation' || $doc == 'credits-purchase') { ?>
            // Approval of policy
            if (document.getElementById('read_policy').checked == false) {
                document.getElementById('error__read_policy').style.display = '';
                document.getElementById('label_of_read_policy').className = 'error ' + document.getElementById('label_of_read_policy').className.replace(/(error ?)/,'');
                validation = 0;
            } else {
                document.getElementById('error__read_policy').style.display = 'none';
                document.getElementById('label_of_read_policy').className = document.getElementById('label_of_read_policy').className.replace(/(error ?)/,'');
            }
		<? } ?>
		
		// Captcha Code
        <? if ($use_captchas && $doc != 'my-account/user-info') { ?>
			if (document.getElementById('captcha_code') && document.getElementById('captcha_code').value == '') {
				document.getElementById('error__general').style.display = '';
				document.getElementById('label_of_captcha_code').className = 'error ' + document.getElementById('label_of_captcha_code').className.replace(/(error ?)/,'');
                document.getElementById('captcha_code').className = 'error ' + document.getElementById('captcha_code').className.replace(/(error ?)/,'');
                validation = 0;	
			} else {
				document.getElementById('error__general').style.display = 'none';
				document.getElementById('label_of_captcha_code').className = document.getElementById('label_of_captcha_code').className.replace(/(error ?)/,'');
                document.getElementById('captcha_code').className = document.getElementById('captcha_code').className.replace(/(error ?)/,'');
                
			}
		<? } ?>
        
        
        // Removing Server Side errors and notes, if exist
        if (document.getElementById('server_side_notes')) {
            document.getElementById('server_side_notes').style.display='none';
        }
        if (document.getElementById('server_side_errors')) {
            document.getElementById('server_side_errors').style.display='none';
        }

        // Validation Conclusion
        if (validation == 0) {
            
            document.getElementById('server_side_errors').style.display = 'none';
            document.getElementById('client_side_errors').style.display = '';	
            window.location='#errors_anchor';
            return false;
            
        } else {
            
            document.getElementById('server_side_errors').style.display = 'none';
            document.getElementById('client_side_errors').style.display = 'none';
            
            if (submission_in_progress == 0) {
                submission_in_progress = 1;
                document.getElementById('error__submission_in_progress').style.display = 'none';
                return true;
            } else {
                document.getElementById('client_side_errors').style.display = '';
                document.getElementById('error__submission_in_progress').style.display = '';
                window.location='#errors_anchor';
                return false;
            }
        }
        
    }
	
</script>



<? /*
<script type="text/javascript">
		
	function validate() {
	
		// Variables
		var validation = 1;		
				
		// First Name
		if (document.getElementById('first_name').value.length < 2) {
			document.getElementById('error_first_name').style.display = '';
			document.getElementById('first_name').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_first_name').style.display = 'none';
			document.getElementById('first_name').className = '';
		}
		
		// Last name
		if (document.getElementById('last_name').value.length < 2) {
			document.getElementById('error_last_name').style.display = '';
			document.getElementById('last_name').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_last_name').style.display = 'none';
			document.getElementById('last_name').className = '';
		}
		
		// Phone 1
		document.getElementById('phone_1').value = document.getElementById('phone_1').value.replace(/(\+|-)/g,'');
		document.getElementById('phone_1').value = document.getElementById('phone_1').value.replace(/(^972)/,'0');
		if (!/^\d{9,10}$/.test(document.getElementById('phone_1').value)) {
			document.getElementById('error_phone_1').style.display = '';
			document.getElementById('phone_1').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_phone_1').style.display = 'none';
			document.getElementById('phone_1').className = '';
		}
		
		// Phone 2 (mobile)
		if (document.getElementById('phone_2').value != '') {
			document.getElementById('phone_2').value = document.getElementById('phone_2').value.replace(/(\+|-)/g,'');
			document.getElementById('phone_2').value = document.getElementById('phone_2').value.replace(/(^972)/,'0');
			if (!/^\d{9,10}$/.test(document.getElementById('phone_2').value)) {
				document.getElementById('error_phone_2').style.display = '';
				document.getElementById('phone_2').className = 'error_field';
				validation = 0;	
			} else {
				document.getElementById('error_phone_2').style.display = 'none';
				document.getElementById('phone_2').className = '';
			}
		}
		
		// Email
		if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(document.getElementById('email_1').value)) {
			document.getElementById('error_email_1').style.display = '';
			document.getElementById('email_1').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_email_1').style.display = 'none';
			document.getElementById('email_1').className = '';
		}
		
		// Password
		<? if ($doc == 'register' || ($doc == 'checkout' && $_GET['step'] == 'billing-info' && !$user) ) { ?>
		
		if (document.getElementById('password_1').value.length < 6 ) {
			document.getElementById('error_password_1').style.display = '';
			document.getElementById('password_1').className = 'error_field';
			document.getElementById('password_2').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_password_1').style.display = 'none';
		    document.getElementById('password_1').className = '';
		    document.getElementById('password_2').className = '';
		}
		
		<? } else { ?>
		
		if ( (document.getElementById('password_1').value != '') && (document.getElementById('password_1').value.length < 6) ) {
			document.getElementById('error_password_1').style.display = '';
			document.getElementById('password_1').className = 'error_field';
        	validation = 0;	
		} else {
			document.getElementById('error_password_1').style.display = 'none';
		    document.getElementById('password_1').className = '';
        }
		
		<? } ?>
		
		// Password 2 (confirmation)
		if (
		        <? if ($doc == 'register') { ?>
		            (document.getElementById('password_1').value == '') ||
		        <? } ?>
				(document.getElementById('password_1').value.length != document.getElementById('password_2').value.length) ||
				(document.getElementById('password_1').value != document.getElementById('password_2').value)
		) {
			document.getElementById('error_password_2').style.display = '';
            document.getElementById('password_2').className = 'error_field';
			validation = 0;
		} else {
			document.getElementById('error_password_2').style.display = 'none';
		    document.getElementById('password_2').className = '';
		}
		
		// Street
		if (document.getElementById("street_1").value.length < 5) {
			document.getElementById('error_street_1').style.display = '';
			document.getElementById('street_1').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_street_1').style.display = 'none';
			document.getElementById('street_1').className = '';
		}
		
		// City
		if (/(\d)/.test(document.getElementById('city').value)) {
			document.getElementById('error_city').style.display = '';
			document.getElementById('city').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_city').style.display = 'none';
			document.getElementById('city').className = '';
		}
		
		// Zipcode
		if (!/(\d)/.test(document.getElementById('zipcode').value)) {
			document.getElementById('error_zipcode').style.display = '';
			document.getElementById('zipcode').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_zipcode').style.display = 'none';
			document.getElementById('zipcode').className = '';
		}
		
		// Country
		if (!/([a-zA-Z])/.test(document.getElementById('country').value)) {
			document.getElementById('error_country').style.display = '';
			document.getElementById('country').className = 'error_field';
			validation = 0;	
		} else {
			document.getElementById('error_country').style.display = 'none';
		    document.getElementById('country').className = '';
		}
		
		// Birth Date
		var date_of_birth_dd;
		var date_of_birth_mm;
		var date_of_birth_yyyy;
		if ( (document.getElementById('date_of_birth_tmp_day').value) && (document.getElementById('date_of_birth_tmp_month').value) && (document.getElementById('date_of_birth_tmp_year').value) ) {
			
			date_of_birth_dd = document.getElementById('date_of_birth_tmp_day').value;
			date_of_birth_mm = document.getElementById('date_of_birth_tmp_month').value;
			date_of_birth_yyyy = document.getElementById('date_of_birth_tmp_year').value;
			
			if ( 
					(!/^\d{2}$/.test(date_of_birth_dd)) &&
					(!/^\d{2}$/.test(date_of_birth_mm)) &&
			 		(!/^\d{4}$/.test(date_of_birth_yyyy))
			 	) {
				document.getElementById('error_date_of_birth').style.display = '';
				validation = 0;	
			} else {
				document.getElementById('date_of_birth').value = date_of_birth_yyyy+'-'+date_of_birth_mm+'-'+date_of_birth_dd;
				document.getElementById('error_date_of_birth').style.display = 'none';
			}
			
		}
		
		// Captcha Code
		<? if ($doc != 'my-account/user-info') { ?>
			if (document.getElementById('captcha_code') && document.getElementById('captcha_code').value == '') {
				document.getElementById('error_captcha_code').style.display = '';
				document.getElementById('captcha_code').className = 'error_field';
				validation = 0;	
			} else {
				document.getElementById('error_captcha_code').style.display = 'none';
				document.getElementById('captcha_code').className = '';
			}
		<? } ?>
		
		// Referrer update (for 'register' page only)
		<? if (($doc == 'register') && ($_GET['referrer'])) { ?>
			document.getElementById('referrer').value = '<?=$_GET['referrer']?>';
		<? } ?>
		
		<? if ($doc == 'checkout' || $doc == 'activation') { ?>
		// Approval of policy
		if (document.getElementById('read_policy').checked != true) {
			document.getElementById('error_read_policy').style.display = '';
			validation = 0;
		} else {
			document.getElementById('error_read_policy').style.display = 'none';
		}
		<? } ?>
		
		//alert(document.getElementById('param__business_details__parent_groups').value+"\n"+document.getElementById('param__business_details__groups').value);
		
		// Validation Conclusion
		if (validation == 0) {
			document.getElementById('client_side_errors').style.display = '';
			document.getElementById('server_side_errors').style.display = 'none';	
			window.location='#errors_anchor';
			return false;
		} else {
			return true;
		}
	
	}
	
</script>
*/ ?>
