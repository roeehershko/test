<?php
	
	if ($doc && strpos($doc, 'gts/') === false && $doc != 'order-form') {
		
		/*
		if (!$_COOKIE['wizard_status']) {
			setcookie('wizard_status', 'off', time()+3600*24*30, '/', '.doupto.com');
		} else {
			$wizard_status = $_COOKIE['wizard_status'];
		}
		*/
		
		$wizard_status = 'off';
		
		/*$store_name = create_token(constant('SITE_NAME').'wizard'.$secure.$user['id']);
		$apc_data = apc_fetch($store_name, $apc_success);
		if (!$apc_success || $_GET['update_cache'] == 1) {
			ob_start();*/
				
				?>
				<script type="text/javascript">
					function toggle_wizard() {
						if ($('.wizard').is(':visible')) {
							$('.wizard').slideToggle('fast', function() {
								$.get(href_url, {action:'save_wizard_status', mode:'off'});
								$('.step').hide();
								$('.step.one').show();
								$('.minimized_wizard').slideToggle('fast', function () {
									$('.minimized_wizard').effect('bounce', {times:3, distance:15}, 350);
								});
							});
						} else {
							$('.minimized_wizard').slideToggle('fast', function() {
								$.get(href_url, {action:'save_wizard_status', mode:'on'});
								$('.wizard').slideToggle('fast', function () {
									$('.wizard').effect('bounce', {times:3, distance:30}, 350);
								});
							});
						}
					}
					
					<? /*
					function toggle_step(step) {
						$('.wizard').slideToggle('fast', function() {
							$('.step').hide();
							$('.step.'+step).show();
							$('.wizard').slideToggle('fast', function () {
								$('.wizard').effect('bounce', {times:3, distance:30}, 350);
							});
						});
					}
					*/ ?>
					
					function submit_wizard_form() {
						if ($('.wizard input[name=full_name]').val() == '<?=$strings['full_name']?>') {
							$('.wizard input[name=full_name]').val('');
						}
						if ($('.wizard input[name=email]').val() == '<?=$strings['email']?>') {
							$('.wizard input[name=email]').val('');
						}
						if ($('.wizard input[name=phone]').val() == '<?=$strings['phone']?>') {
							$('.wizard input[name=phone]').val('');
						}
						if ($('.wizard textarea[name=message]').val() == 'תוכן ההודעה') {
							$('.wizard textarea[name=message]').val('');
						}
						$.post('<?=href('/')?>', {action:'send_contact', language:'<?=$language?>', full_name:$('.wizard input[name=full_name]').val(), email:$('.wizard input[name=email]').val(), phone:$('.wizard input[name=phone]').val(), message:$('.wizard textarea[name=message]').val()}, function(data) {
							if (data.status == 'okay') {
								$('.wizard .container').hide('slide', {direction:'left'}, 400, function() {
									$('.wizard .container').html('<div class="w_title"><?=$strings['confirmation']?></div><div class="description">'+data.confirmation+'</div>');
									$('.wizard .container').show('slide', {direction:'right'}, 400);
								});
								//toggle_wizard();
							} else {
								alert(data.errors);
								if ($('.wizard input[name=full_name]').val() == '') {
									$('.wizard input[name=full_name]').val('<?=$strings['full_name']?>');
								}
								if ($('.wizard input[name=email]').val() == '') {
									$('.wizard input[name=email]').val('<?=$strings['email']?>');
								}
								if ($('.wizard input[name=phone]').val() == '') {
									$('.wizard input[name=phone]').val('<?=$strings['phone']?>');
								}
								if ($('.wizard textarea[name=message]').val() == '') {
									$('.wizard textarea[name=message]').val('<?=$strings['message']?>');
								}
							}
						}, 'json');
					}
					
					$(document).ready(function() {
						<?
						//if (!$wizard_status) {
						if ($doc == 'צור-קשר' || $doc == 'en/contact') {
							?>
							setTimeout(function() {
								toggle_wizard();
							}, 3000);	
							<?
						}
						?>
					});
				</script>
				<? /*
				<div class="minimized_wizard" align="center" onclick="toggle_wizard();_gaq.push(['_trackEvent', 'wizard', 'open', '<?=$user_object ? 'User: '.$user_object->first_name.' '.$user_object->last_name.' ('.$user_object->doc_id.')' : false ?>'])">
					<div class="outer_container" align="center">
						<div class="inner_container"><?=$strings['start_here']?></div>
					</div>
				</div>
				*/ ?>
				<div class="minimized_wizard footer_container">
					<div class="box footer">
						<div class="container">
							<?
							if ($language == 'english') {
								?>
								<span><a href="<?=href('/he')?>">עברית</a></span>
								<?
							} else {
								?>
								<span><a href="<?=href('/')?>">English</a></span>
								<?
							}
							?>
							<span>Powered by Gyro &copy; Paragon Ltd. 2000-<?=date('Y')?></span>
							<a class="icon facebook" href="https://www.facebook.com/pages/Paragon-Creations-Ltd/129031567113124" target="_blank"><img src="<?=href('files/images/icons/grey_icon__facebook.png')?>"></a>
							<a class="icon twitter" href="https://twitter.com/#!/paragoncreation" target="_blank"><img src="<?=href('files/images/icons/grey_icon__twitter.png')?>"></a>
							<a class="icon contact" onclick="toggle_wizard();_gaq.push(['_trackEvent', 'wizard', 'open', '<?=$user_object ? 'User: '.$user_object->first_name.' '.$user_object->last_name.' ('.$user_object->doc_id.')' : false ?>'])"><img src="<?=href('files/images/icons/grey_icon__email.png')?>"></a>
						</div>
					</div>
				</div>
				<div class="wizard" align="center">
					<div class="outer_container" align="center">
						<div class="close" onclick="toggle_wizard();_gaq.push(['_trackEvent', 'wizard', 'close', '<?=$user_object ? 'User: '.$user_object->first_name.' '.$user_object->last_name.' ('.$user_object->doc_id.')' : false ?>'])">✖</div>
						<div class="inner_container">
							<div class="container">
								<div class="w_title"><?=$strings['wizard_title']?></div>
								<div class="description"><?=$strings['wizard_description']?></div>
								<div class="fields_container">
									<div class="field"><input type="text" name="full_name" value="<?=$strings['full_name']?>" onfocus="($(this).val() == '<?=$strings['full_name']?>' ? $(this).val('') : false)" onblur="($(this).val() == '' ? $(this).val('<?=$strings['full_name']?>') : false)"></div>
									<div class="field"><input type="text" name="email" value="<?=$strings['email']?>" onfocus="($(this).val() == '<?=$strings['email']?>' ? $(this).val('') : false)" onblur="($(this).val() == '' ? $(this).val('<?=$strings['email']?>') : false)"></div>
									<div class="field"><input type="text" name="phone" value="<?=$strings['phone']?>" onfocus="($(this).val() == '<?=$strings['phone']?>' ? $(this).val('') : false)" onblur="($(this).val() == '' ? $(this).val('<?=$strings['phone']?>') : false)"></div>
									<? /*<div class="field textarea"><textarea name="message" onfocus="$(this).val() == 'Message' ? $(this).val('') : false "><?=$strings['message']?></textarea></div>*/ ?>
									<div class="button" onclick="submit_wizard_form()"><?=$strings['submit']?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?
			/*$apc_stdout = ob_get_clean();
			apc_store($store_name, $apc_stdout, 3600*24);
			echo $apc_stdout;
		} else {
			echo $apc_data;
		}*/
	}
?>
