<?php
	$direction = 'ltr';
	$align = 'left';
	?>
	<body dir="<?=$direction?>">
		<div style="font-family:Arial;font-size:14px;padding:20px;direction:<?=$direction?>;text-align:<?=$align?>" dir="<?=$direction?>">
			<?
			if ($username) {
				echo 'Username: ' . $username . '<br><br>' . "\n";
			}
			if ($company_name) {
				echo 'Company Name: ' . $company_name . '<br><br>' . "\n";
			}
			if ($company_number) {
				echo 'Company Number: ' . $company_number . '<br><br>' . "\n";
			}
			if ($email) {
				echo 'Email: ' . $email . '<br><br>' . "\n";
			}
			if ($phone) {
				echo 'Phone: ' . $phone . '<br><br>' . "\n";
			}
			if ($address_street) {
				echo 'Address: ' . $address_street . '<br><br>' . "\n";
			}
			if ($address_city) {
				echo 'City: ' . ($address_city) . '<br><br>' . "\n";
			}
			if ($address_zip) {
				echo 'Zip Code: ' . $address_zip . '<br><br>' . "\n";
			}
			if ($contact_name) {
				echo 'Contact Name: ' . $contact_name . '<br><br>' . "\n";
			}
			?>
		</div>
	</body>
	<?
	
?>
