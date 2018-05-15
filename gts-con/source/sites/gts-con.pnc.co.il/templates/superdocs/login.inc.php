<?php

	?>
	<div class="box high">
		<version><?=constant('VERSION')?></version>
		<a class="paragon_logo"><img src="<?=href('images/global/logo.png')?>" alt="<?=htmlspecialchars(constant('SITE_TITLE'))?>"></a>
		<img class="image" src="<?=href('images/global/wave_205.jpg')?>">
	</div>
	<div class="box flex">
		<div id="superdoc">
			<h1>כניסה למערכת</h1>
			<div class="description">
				<div class="login">
					<div>אנא הזינו דוא"ל וססמא על מנת להכנס למערכת.</div>
					<? /*
					<div style="font-weight: bold;">אם שכחתם את ססמתכם <a href="<?=href('/reset-password')?>">לחצו כאן</a></div>
					<div style="font-weight: bold;">אם אינכם רשומים למערכת <a href="<?=href('/register')?>">לחצו כאן</a></div>
					*/ ?>
					<br>
					<? // Server Side Errors ?>
					<div id="server_side_errors" class="errors" <? if (!$errors) { ?>style="display: none;"<? } ?>>
						<div class="e_title">נמצאו שגיאות:</div>
						<? for ($i = 0; $i != count($errors); ++$i): ?>
							<div>- <?=ucfirst($errors[$i])?></div>
						<? endfor; ?>
					</div>
					<div class="container">
						<form method="POST" id="main_login_form" name="login">    
							<input type="hidden" name="referrer" value="<?=htmlspecialchars($_GET['referrer'])?>">
							<table style="direction:rtl;" cellpadding="0" cellspacing="0">
								<tr>
									<td class="label">אימייל</td>
									<td><div class="separator">&nbsp;</div></td>
									<td><input class="field" type="text" name="email" dir="ltr" value="<?=$email?>"></td>
								</tr>
								<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
								<tr>
									<td class="label">ססמא</td>
									<td><div class="separator">&nbsp;</div></td>
									<td><input class="field" type="password" name="password" dir="ltr" value="" autocomplete="off"></td>
								</tr>
								<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
								<tr>
									<td></td>
									<td><div class="separator">&nbsp;</div></td>
									<td class="submit_container"><button>הכנס</button></td>
								</tr>
							</table>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript">
        $(document).ready(function() { setTimeout(function() {$('#main_login_form').find(':input:not(:submit)[value=""]:first').focus();}, 3000); });
	</script>
	<?
?>