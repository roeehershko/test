<?php

	recursive('trim', $_GET);
	//recursive('stripslashes', $_GET);
	recursive('strip_tags', $_GET);

	if ($_GET['errors']) {
		$errors = $_GET['errors'];
	}
	
	// 2017-01-22 - Deprecated, since we now allow a user to work EVEN if a merchant underneath him has an expired password or a password that needs to be changed.
	/*
	if ($user_details['id'] && $merchant_details['must_change_password'] == 1) {
		// In case a user has merchants that must change a password - he must solve it first.
		?>
		<div id="superdoc">
			<div class="login">
				<div>אחד מהמסופים שלכם נדרש לחדש סיסמא. לא ניתן להמשיך עד לחידוש הסיסמא.</div>
				<div>מדובר במסוף <?=$merchant_details['username']?> שמספרו <?=$merchant_details['number']?> (<?=$merchant_details['company']['name']?> - <?=$merchant_details['company']['email']?>)</div>
				<br>
				<div><button class="logout" onclick="window.location='<?=href($doc.'?action=logout')?>'"><?=$strings['logout']?></button></div>
			</div>
		</div>
		<?
	} else
	*/
	if ($user_details['must_change_password'] == 1 || $merchant_details['must_change_password'] == 1) {
		?>
		<div id="superdoc">
			<div class="login">
				<div>עליכם לבחור סיסמא קבועה חדשה. אנא הזינו את הסיסמא הרצויה בשדות הבאים.</div>
				<br>
				<div class="container">
					<form method="POST" name="change_password" onsubmit="return submit_form(this, gts_must_change_password_confirmation_object)">    
						<input type="hidden" name="action" value="gts_change_merchant_password">
						<input type="hidden" name="username" value="<?=htmlspecialchars($user_details['username'] ?: $merchant_details['username'])?>">
						<table style="direction:rtl;" cellpadding="0" cellspacing="0">
							<tr>
								<td class="label">סיסמא קבועה חדשה</td>
								<td><div class="separator">&nbsp;</div></td>
								<td><input class="field" type="password" name="password" dir="ltr" value="" required autocomplete="off"></td>
							</tr>
							<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
							<tr>
								<td class="label">אימות סיסמא</td>
								<td><div class="separator">&nbsp;</div></td>
								<td><input class="field" type="password" name="confirm_password" dir="ltr" value="" required autocomplete="off"></td>
							</tr>
							<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
							<tr>
								<td></td>
								<td><div class="separator">&nbsp;</div></td>
								<td class="submit_container"><button>שלח</button></td>
							</tr>
						</table>
		
					</form>
				</div>
			</div>
		</div>
		<?
	} else if ($_GET['reset_password'] == 1) {
		?>
		<div id="superdoc">
			<div class="login">
				<div>אנא הזינו את שם המשתמש וח״פ או עוסק מורשה על מנת לשחזר או לשנות את סיסמתכם.</div>
				<div>לאחר מילוי הפרטים תשלח אליכם סיסמא זמנית, בעזרתה תוכלו לקבוע סיסמא קבועה חדשה.</div>
				<div class="reset_password_link">
					<span>לחזרה למסך הקודם</span>
					<a href="<?=href($doc)?>">לחצו כאן</a>
				</div>
				<br>
				<div class="container">
					<form method="POST" name="reset_password" onsubmit="gts_reset_user_password(this); return false;">    
						<table style="direction:rtl;" cellpadding="0" cellspacing="0">
							<tr>
								<td class="label">שם משתמש</td>
								<td><div class="separator">&nbsp;</div></td>
								<td><input class="field" type="text" name="username" dir="ltr" value=""></td>
							</tr>
							<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
							<tr>
								<td class="label">ח״פ או מס. עוסק מורשה</td>
								<td><div class="separator">&nbsp;</div></td>
								<td><input class="field" type="text" name="company_id" dir="ltr" value=""></td>
							</tr>
							<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
							<tr>
								<td></td>
								<td><div class="separator">&nbsp;</div></td>
								<td class="submit_container"><button>שלח</button></td>
							</tr>
						</table>
		
					</form>
				</div>
			</div>
		</div>
		<?
	} else {
		
		if ($_GET['token']) {
			$token_credentials = json_decode(aes_decrypt($_GET['token']), true);
		}
		if ($token_credentials['username'] && $token_credentials['password']) {
			$token_username = $token_credentials['username'];
			$token_password = $token_credentials['password'];
			$token_user_type = $token_credentials['type'];
			?>
			<script>
				if (isMobile.iOS) {
					// 2016-03-27 - iOS allows to trigger the URL Scheme without a user gesture (Safari shows its own confirmation alert)
					<? /*
					setTimeout(function() {
						window.location.href = '<?=$token_user_type == 'isracard' ? constant('GTS_APP_STORE_URL__ISRACARD') : constant('GTS_APP_STORE_URL__VERIFONE') ?>';
					}, 100);
					*/ ?>
					$(document).ready(function() {
						$('.login').prepend('<a class="button" href="<?=$token_user_type == 'isracard' ? constant('GTS_APP_STORE_URL__ISRACARD') : constant('GTS_APP_STORE_URL__VERIFONE') ?>">להתקנת האפליקציה - לחצו כאן</a>');
						// If we don't wait for the DOM to load - then due to the location.href - the rest of the content won't appear (and if the user rejects the opening of the App - he'll get a screen without the content of this page).
						window.location.href = '<?=constant('GTS_APP_URL_SCHEME')?>://?token-password=<?=$token_credentials['password']?>';
					});
				} else if (isMobile.Android) {
					// 2016-03-27 - Android no longer allow to trigger a URL Scheme without a user gesture. So we must show a confirmation modal.
					$(document).ready(function() {
						$('.login').prepend('<a class="button" href="intent://?token-password=<?=$token_credentials['password']?><?=$token_user_type == 'isracard' ? constant('GTS_GOOGLE_PLAY_URL__ISRACARD') : constant('GTS_GOOGLE_PLAY_URL__VERIFONE') ?>">לפתיחת האפליקציה - לחצו כאן</a>');
					});

				}
			</script>
			<?
		}

		?>
		<div id="superdoc">
			<div class="login">
				<?
				if ($token_password) {
					?>
					<div>אנא הזינו את שם המשתמש שלכם על מנת לעבור למסך בחירת סיסמא קבועה.</div>	
					<?
				} else {
					?>
					<div>אנא הזינו שם משתמש וססמא על מנת להכנס למערכת.</div>	
					<?
				}
				if (strpos($doc, 'signatures') === false) {
					?>
					<div class="reset_password_link">
						<span>שכחתם את הסיסמא?</span>
						<a href="<?=href($doc.'?reset_password=1')?>">לשחזור סיסמא או שחרור מנעילה לחצו כאן</a>
					</div>
					<?
				}
				?>
				<br>
				<? /* <div style="font-weight: bold;">אם שכחתם את ססמתכם <a href="<?=href('/reset-password')?>">לחצו כאן</a></div> */ ?>
				<? // Server Side Errors ?>
				<div id="server_side_errors" class="errors" <? if (!$errors) { ?>style="display:none;"<? } ?>>
					<div class="e_title">נמצאו שגיאות:</div>
					<? for ($i = 0; $i != count($errors); ++$i): ?>
						<div>- <?=ucfirst($errors[$i])?></div>
					<? endfor; ?>
				</div>
				<div class="container">
					<form method="POST" name="login">    
						<input type="hidden" name="referrer" value="<?=htmlspecialchars($_GET['referrer'])?>">
						<?
						if ($token_password) {
							?>
							<input type="hidden" name="password" value="<?=$token_password?>" autocomplete="off">
							<?
						}
						?>
						<table style="direction:rtl;" cellpadding="0" cellspacing="0">
							<tr>
								<td class="label">שם משתמש</td>
								<td><div class="separator">&nbsp;</div></td>
								<td><input class="field" type="text" name="username" dir="ltr" value=""></td>
							</tr>
							<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
							<?
							if (!$token_password) {
								?>
								<tr>
									<td class="label">סיסמא</td>
									<td><div class="separator">&nbsp;</div></td>
									<td><input class="field" type="password" name="password" dir="ltr" value="<?=$token_password ?: ''?>" autocomplete="off"></td>
								</tr>
								<tr><td colspan="3"><div class="separator">&nbsp;</div></td></tr>
								<?
							}
							?>
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
		<?	
	}
?>