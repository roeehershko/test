<?php

	/* AES */

	require_once('./aes.inc.php');

	function aes_encrypt($payload) {
	    $aes = new AES($payload, constant('AES_PASSPHRASE'), '256');
	    $enc = $aes->encrypt();

	    return $enc;
	}

	function aes_decrypt($payload) {
	    $aes = new AES($payload, constant('AES_PASSPHRASE'), '256');
	    $dec = $aes->decrypt();

	    return $dec;
	}

	if ($_GET['action'] == 'aes-encrypt') {
		echo 'Encrypted: '.aes_encrypt($_GET['payload']);
		echo '<br>';
		echo 'Decrypted: '.aes_decrypt(aes_encrypt($_GET['payload']));
		exit;
	}
	if ($_GET['action'] == 'aes-decrypt') {
		echo 'Decrypted: '.aes_decrypt(trim(strip_tags(getObject('GTS Verifone Manager', true))));
		exit;
	}


	/* ## */

	function debug($log) {
		?>
		<script>console.log(<?=$log?>)</script>
		<?
	}

	// Curl function for working with the GTS
	function gts_curl($url, $request, $credentials = false) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		if ($credentials) {
			curl_setopt($curl, CURLOPT_USERPWD, $credentials);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(strip_nulls($request)));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	// Recursivelly strip all null / false values from an object/array.
	function strip_nulls($variable) {
	    if (is_object($variable)) {
	        $return = (object) null;
	        foreach ($variable as $var => $val) {
	            if ($val) {
	                $return_tmp = strip_nulls($val);
	                if ($return_tmp) {
	                    $return->$var = $return_tmp;
	                }
	            }
	        }
	        if ($return != (object) null) {
	            return $return;
	        }
	    } else if (is_array($variable)) {
	        $return = null;
	        foreach ($variable as $var => $val) {
	            if ($val) {
	                $return_tmp = strip_nulls($val);
	                if ($return_tmp) {
	                    $return[$var] = $return_tmp;
	                }
	            }
	            if ($val) {
	                $return[$var] = strip_nulls($val);
	            }
	        }
	        return $return;
	    } else if ($variable) {
	        return $variable;
	    }
	}

	function select_csv($file) {
		if (file_exists($file)) {
			$count = 0;
			if (($handle = fopen($file, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
					for ($j = 0; $j != count($data); $j++) {
						//$data[$j] = trim(iconv('WINDOWS-1255', 'UTF-8', $data[$j]));
						$data[$j] = $data[$j];
					}
					$result_array[$count] = $data;
					$count++;
					//echo '<pre>'; print_r($data); echo '</pre>';
				}
				fclose($handle);
			}
		}
		return $result_array;
	}

	function generate_email_template($title, $name, $content, $standalone = false, $direct_url = false, $unsubscribe_url = false, $link = false, $parameters = false) {

		if ($parameters['header_image']) {
			$header_image = $parameters['header_image'];
		} else {
			$header_image = constant('HTTP_URL').'images/global/logo.png';
		}

		$direction = 'rtl';
		$align = 'right';
		$template = 	'<div id="newsletter" dir="'.$direction.'" style="direction:'.$direction.';text-align:'.$align.';padding:15px;font-family:Arial;font-size:14px;background:#EFEFEF">';
		$template .= 		'<div>';
		$template .= 			'<div align="'.($align == 'right' ? 'left' : 'right').'" style="padding:0 0 10px 0"><a href="'.constant('HTTP_URL').'" target="_blank"><img height="50" style="height:50px" src="'.$header_image.'" alt="'.htmlspecialchars(constant('SITE_TITLE')).'" title="'.htmlspecialchars(constant('SITE_TITLE')).'" border="0"></a></div>';
		if ($title) {
			$template .= 		'<div style="font-size:20px;font-weight:bold;padding:0 0 10px 0;color:#333">'.$title.'</div>';
		}
		if ($name) {
			$template .= 		'<div style="font-weight:bold;color:#333">שלום'.($name ? ' '.$name : false).',</div><br>';
		}
		$template .= 		'</div>';

		$template .= 		'<div style="padding:15px;margin:0;background:#FFF">';
		if ($content) {
			$template .= 	str_replace(array('<p>', '<p dir="rtl">'), array('<p style="padding:0;margin:0;color:#555">', '<p dir="rtl" style="padding:0;margin:0;color:#555">'), $content);
		}
		if (!empty($parameters['sections'])) {
			$count = 0;
			foreach ($parameters['sections'] as $section) {
				$template .= '<div dir="'.($parameters['direction'] ?: 'rtl').'" style="'.(($count != 0 || $content) ? 'padding:10px 0 0 0;margin:15px 0 0 0;border-top:1px solid #EFEFEF' : false).';font-size:14px;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'">';
				$template .= 	'<table width="100%" dir="'.($parameters['direction'] ?: 'rtl').'" cellspacing="0" cellpadding="0">';
				$template .= 		'<tr>';
				$template .= 			'<td valign="top" width="30%">';
				if ($section['link_url']) {
					$template .= 			'<a href="'.$section['link_url'].'" target="_blank"><img src="'.constant('HTTP_URL').$section['image'].'" width="100%" border="0" align="right" alt=""></a>';
				} else {
					$template .= 			'<img src="'.constant('HTTP_URL').$section['image'].'" width="100%" border="0" align="right" alt="">';
				}
				$template .= 			'</td>';
				$template .= 			'<td><div style="width:15px"></div></td>';
				$template .= 			'<td valign="top" width="70%">';
				$template .= 				'<h2 dir="'.($parameters['direction'] ?: 'rtl').'" style="display:block;margin:0;padding:0 0 5px 0;color:#3087CE;font-size:18px;font-weight:bold;font-weight:normal;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'">'.$section['title'].'</h2>';
				$template .= 				'<h3 dir="'.($parameters['direction'] ?: 'rtl').'" style="display:block;margin:0;padding:0 0 5px 0;color:#333;font-size:16px;font-weight:normal;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'">'.$section['subtitle'].'</h3>';
				$template .= 				'<div dir="'.($parameters['direction'] ?: 'rtl').'" style="color:#555;font-size:14px;padding:0 0 10px 0;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'">'.nl2br($section['description']).'</div>';
				if ($section['link_url']) {
					$template .= 			'<div dir="'.($parameters['direction'] ?: 'rtl').'" style="color:#3087CE;font-size:14px;padding:0 0 10px 0;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'"><a href="'.$section['link_url'].'" target="_blank" style="color:#3087CE;font-size:14px;padding:0;text-align:'.($parameters['direction'] == 'ltr' ? 'left' : 'right').';direction:'.($parameters['direction'] ?: 'rtl').'">'.($section['link_title'] ? $section['link_title'] : $section['link_url']).'</a> &rsaquo;</div>';
				}
				$template .= 			'</td>';
				$template .= 		'</tr>';
				$template .= 	'</table>';
				$template .= '</div>';

				$count++;
			}
		}
		if ($parameters['footer']) {
			$template .= 	'<div style="padding:10px 0 0 0;margin:15px 0 0 0;border-top:1px solid #EFEFEF">';
			$template .= 		str_replace(array('<p>', '<p dir="rtl">'), array('<p style="padding:0;margin:0;color:#555">', '<p dir="rtl" style="padding:0;margin:0;color:#555">'), $parameters['footer']);
			$template .= 	'</div>';
		}
		$template .= 		'</div>';

		if ($standalone || $link) {
			//$template .= 		'<div style="border-top:15px solid #EFEFEF;padding:15px 15px 0 15px;background:#FFF">';
			$template .= 		'<div style="padding:15px 0 0 0">';
			if ($standalone) {
				$template .= 		'<div style="padding-bottom:15px;text-align:left"><a href="'.$direct_url.'" style="color:#777">If you can\'t view this newsletter properly please click here</a></div>';
			}
			if ($standalone || $link) {
				$template .= 		'<div style="text-align:left"><a href="'.$unsubscribe_url.'" style="color:#777">If you wish to be removed from our list please click here</a></div>';
			}
			$template .= 			'<div style="clear:both;height:10px;line-height:10px;font-size:5px">&nbsp;</div>';
			$template .= 		'</div>';
		}
		$template .= 	'</div>';

		return $template;
	}

	function navigation_handler($total, $limit, $page = false) {

		$total = preg_replace('/\D/', '', $total);
		$limit = preg_replace('/\D/', '', $limit);
		if ($page) {
			$page = preg_replace('/\D/', '', $page);
		}

		$navigation = array();
		$navigation['total'] = $total;
		$navigation['limit'] = ($limit ?: 10);
		if ($navigation['limit'] > $navigation['total'] && $navigation['total'] != 0) {
			$navigation['limit'] = $navigation['total'];
		}
		if ($limit == 'no_limit') {
			$navigation['number_of_pages'] = 1;
		} elseif ($navigation['total'] <= $navigation['limit']) {
			$navigation['number_of_pages'] = 1;
		} else {
			if (($navigation['total'] % $navigation['limit']) != 0) {
				$navigation['number_of_pages'] = floor(($navigation['total'] / $navigation['limit']) + 1);
			} else {
				$navigation['number_of_pages'] = floor(($navigation['total'] / $navigation['limit']));

			}
		}

		if ($page) {
			$navigation['page'] = $page;
			if ($navigation['page'] > $navigation['number_of_pages'] ) {
				$navigation['page'] = $navigation['number_of_pages'];
			}
		} else {
			$navigation['page'] = 1;
		}
		if ($navigation['page'] > 1) {
			$$navigation['page_prev'] = $navigation['page'] - 1 ;
		}
		if ($navigation['page'] < $navigation['number_of_pages']) {
			$navigation['page_next'] = $navigation['page'] + 1 ;
		}
		$navigation['end'] = $navigation['limit']*$navigation['page']-1;
		if ($navigation['total'] <= $navigation['end']) {
			$navigation['end'] = $navigation['total']-1;
		}
		$navigation['start'] = $navigation['limit']*($navigation['page']-1);
		return $navigation;
	}

	/* GTS */

	if ($_POST['action'] == 'gts_save_company') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls(array(
			'id' => $_POST['id'],
			'name' => $_POST['name'],
			'number' => $_POST['number'],
			'mailer_name' => $_POST['mailer_name'],
			'mailer_email' => $_POST['mailer_email'],
			'params' => $_POST['params'],
		));
		//$response = gts_curl('https://'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitCompanyItem', $request, getVar('gts_credentials'));
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitCompanyItem', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
				$data['company'] = $request;
				$data['company']['id'] = isset($response['data']['id']) ? $response['data']['id'] : $_POST['id'];
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);
			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_save_vf_merchant') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		if (!$errors) {
			$request = strip_nulls(array(
				'id' => $_POST['id'],
				'username' => $_POST['username'],
				'password' => $_POST['password'],
				'mobile' => $_POST['mobile'],
				//'direct_access_ip' => $direct_access_ip,
				'direct_access_password' => $_POST['direct_access_password'],
				'number' => $_POST['number'],
				'parent_number' => $_POST['parent_number'],
				'processor' => $_POST['processor'],
				'shva_transactions_username' => $_POST['shva_transactions_username'],
				'shva_transactions_password' => $_POST['shva_transactions_password'],
				'company' => $_POST['company'],
				'sender' => $_POST['sender'],
				'invoices' => $_POST['invoices'],
				'access_token_ttl' => $_POST['access_token_ttl'],
				'voucher_language' => $_POST['voucher_language'],
				'support_recurring_orders' => $_POST['support_recurring_orders'],
				'support_refund_trans' => $_POST['support_refund_trans'],
				'support_cancel_trans' => $_POST['support_cancel_trans'],
				'support_new_trans' => $_POST['support_new_trans'],
				'send_daily_report' => $_POST['send_daily_report'],
				'allow_duplicated_transactions' => $_POST['allow_duplicated_transactions'],
				'params' => $_POST['params']
			));
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_submitMerchant', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode($request)
				)
			)));
			if ($response = json_decode($response, true)) {

				//$data['request'] = $request;
				//$data['response'] = $response;

				if ($response['result'] == 'OKAY') {
					$data['status'] = 'okay';
					$data['merchant'] = $request;
					$data['merchant']['password'] = $_POST['password'];
					$data['merchant']['id'] = isset($response['data']['id']) ? $response['data']['id'] : $_POST['id'];

					$data['debug'] = $response['data'];

					gyroLog('gts_save_vf_merchant - okay', false, $data);

				} else {
					$data['status'] = 'fail';
					$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);
					if ($response['error']['debug']) {
						$data['debug'] = $response['error']['debug'];
					}

					gyroLog('gts_save_vf_merchant - fail', false, $data);

				}
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = $errors ?: 'An Error Occurred';

			gyroLog('gts_save_vf_merchant - fail', false, $data);
		}
		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_save_vf_user') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		if (getVar('gts_admin')) {
			$_POST['gts_user']['id'] = $_POST['id'];
			$request = strip_nulls($_POST['gts_user']);
			$data['request'] = $request;
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_submitUser', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode($request)
				)
			)));
			if ($response = json_decode($response, true)) {
				$data['response'] = $response;
				if ($response['result'] == 'OKAY') {
					$data['status'] = 'okay';
					$data['gts_user'] = $request;
					$data['gts_user']['id'] = isset($response['data']['id']) ? $response['data']['id'] : $_POST['id'];
					//$data['debug'] = $response;

					gyroLog('gts_save_vf_user - okay', false, $data);

				} else {
					$data['status'] = 'fail';
					$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);

					gyroLog('gts_save_vf_user - fail', false, $data);

				}
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = 'Permission Denied';

			gyroLog('gts_save_vf_user - fail', false, $data);
		}

		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_vf_merchant_associate_user') {

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		if (getVar('gts_admin')) {
			$request = strip_nulls(array(
				'user_id' => $_POST['user_id'],
				'merchant_id' => $_POST['merchant_id'] ?: null,
			));
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_associateMerchantToUser', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode($request)
				)
			)));
			if ($response = json_decode($response, true)) {
				$data['response'] = $response;
				if ($response['result'] == 'OKAY') {
					$data['status'] = 'okay';

					gyroLog('gts_vf_merchant_associate_user - okay', false, $data);

				} else {
					$data['status'] = 'fail';
					$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);

					gyroLog('gts_vf_merchant_associate_user - fail', false, $data);

				}
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = 'Permission Denied';

			gyroLog('gts_vf_merchant_associate_user - fail', false, $data);
		}

		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_save_merchant') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		$request = strip_nulls(array(
					'id' => $_POST['id'],
					'company_id' => $_POST['company_id'],
					'name' => $_POST['name'],
					'number' => $_POST['number'],
					'pos_number' => $_POST['pos_number'],
					'report_inactivity' => $_POST['report_inactivity'] == 1 ? true : false,
					'params' => $_POST['params'],
		));
		//$response = gts_curl('https://'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitMerchantItem', $request, getVar('gts_credentials'));
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitMerchantItem', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
				$data['merchant'] = $request;
				$data['merchant']['id'] = isset($response['data']['id']) ? $response['data']['id'] : $_POST['id'];

				gyroLog('gts_save_merchant - okay', false, $data);

			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);

				gyroLog('gts_save_merchant - fail', false, $data);

			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_save_user') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$requeubmit_ut = strip_nulls(array(
			'id' => $_POST['id'],
			'username' => $_POST['username'],
			'name' => $_POST['name'],
			'merchant_ids' => $_POST['merchant_id'] ?: null,
			'params' => $_POST['params'],
		));
		$data['request'] = $request;
		//$response = gts_curl('https://'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitUserItem', $request, getVar('gts_credentials'));
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/submitUserItem', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
				$data['user'] = $request;
				$data['user']['password'] = $_POST['password'];
				$data['user']['id'] = isset($response['data']['id']) ? $response['data']['id'] : $_POST['id'];

				gyroLog('gts_save_user - okay', false, $data);

			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);

				gyroLog('gts_save_user - fail', false, $data);

			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_change_user_password') {
		// SIGNATURES user password change
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		$request = strip_nulls(array(
			'id' => $_POST['id'],
			'password' => $_POST['password'],
		));
		$data['request'] = $request;
		//$response = gts_curl('https://'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/changeUserPassword', $request, getVar('gts_credentials'));
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/changeUserPassword', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
				$data['user'] = $request;
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = 'An error occurred. Please try again.';
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_change_merchant_password') {

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		if ($_POST['password'] == $_POST['confirm_password']) {
			$lang_code = 'he';
			$request = strip_nulls(array(
				'username' => $_POST['username'],
				'password_new' => $_POST['password'],
			));
			$data['request'] = $request;
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/changeUserPassword', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode($request)
				)
			)));
			if ($response = json_decode($response, true)) {

				$data['response'] = $response;

				if ($response['result'] == 'OKAY') {
					$data['status'] = 'okay';
					$data['user'] = $request;
				} else {
					$data['status'] = 'fail';
					$data['errors'] = ucfirst($response['error']);
				}
			} else {
				$data['status'] = 'fail';
				$data['errors'] = 'An error occurred. Please try again.';
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = 'הסיסמא וסיסמת האימות אינן תואמות.';
		}
		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_delete_recurring_order') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls(array(
			'ro_id' => $_POST['id'],
		));
		$data['request'] = $request;

		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/cancelUserRecurringOrder'.ucfirst($_POST['type']), false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode($request)
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_delete_type') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		if (getVar('gts_admin')) {
			$request = strip_nulls(array(
				'id' => $_POST['id'],
			));
			$data['request'] = $request;
			if ($_POST['type'] == 'vf_user') {
				$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_terminateUser', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode($request)
					)
				)));
			} else if ($_POST['type'] == 'vf_merchant') {
				$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_terminateMerchant', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode($request)
					)
				)));
			} else {
				//$response = gts_curl('https://'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/delete'.ucfirst($_POST['type']), $request, getVar('gts_credentials'));
				$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/delete'.ucfirst($_POST['type']), false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode(strip_nulls($request))
					)
				)));
			}
			if ($response = json_decode($response, true)) {

				$data['response'] = $response;

				if ($response['result'] == 'OKAY') {
					$data['status'] = 'okay';

					gyroLog('gts_delete_type - okay', false, $data);

				} else {
					$data['status'] = 'fail';
					$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);

					gyroLog('gts_delete_type - fail', false, $data);

				}
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = 'Permission Denied';

			gyroLog('gts_delete_type - fail', false, $data);
		}
		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_reset_user_password') {

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		// Finding the merchant ID
		$gts_credentials = 'verifone-manager:'.aes_decrypt(trim(strip_tags(getObject('GTS Verifone Manager', true))));
		$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_resetUserPassword', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'username' => $_POST['username'],
					'company_id' => $_POST['company_id'],
				)))
			)
		)));
		//$data['debug1'] = json_decode($response, true);
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$new_password = $response['data']['password'];
				$user_id = $response['data']['user_id'];
				$merchant_id = $response['data']['merchant_id'];
				if ($user_id) {
					$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainUsers', false, stream_context_create(array(
						'http' => array (
							'method' => 'POST',
							'header' => 'Content-type: application/json' . "\r\n",
							'content' => json_encode(strip_nulls(array(
								'id' => $user_id,
							)))
						)
					)));
					//$data['debug2'] = json_decode($response, true);
					if ($response = json_decode($response, true)) {
						if ($response['result'] == 'OKAY') {
							$user = $response['data']['items'][0];
							$mobile = $user['mobile'];
							$email_recipient = $user['email'];
							// Determening the Sender by checking the first merchant type
							if ($response['data']['items'][0]['type']) {
								$sender = $response['data']['items'][0]['type'];
							} else if ($response['data']['items'][0]['merchants'][0]) {
								$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
									'http' => array (
										'method' => 'POST',
										'header' => 'Content-type: application/json' . "\r\n",
										'content' => json_encode(strip_nulls(array(
											'id' => $response['data']['items'][0]['merchants'][0],
										)))
									)
								)));
								if ($response = json_decode($response, true)) {
									if ($response['result'] == 'OKAY') {
										if ($merchant['params']['merchant_type'] == 'וריפון') {
											$sender = 'Verifone';
										} else {
											$sender = 'Isracard';
										}
									}
									$data['debug'] = $response;
								}
							} else {
								$sender = 'Isracard';
							}
						}
					}
				} else if ($merchant_id) {
					$response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
						'http' => array (
							'method' => 'POST',
							'header' => 'Content-type: application/json' . "\r\n",
							'content' => json_encode(strip_nulls(array(
								'id' => $merchant_id,
							)))
						)
					)));
					//$data['debug3'] = json_decode($response, true);
					if ($response = json_decode($response, true)) {
						if ($response['result'] == 'OKAY') {
							$merchant = $response['data']['items'][0];
							$mobile = $merchant['mobile'] ?: $merchant['params']['mobile'];
							$email_recipient = $merchant['company']['email'];
							if ($merchant['params']['merchant_type'] == 'וריפון') {
								$sender = 'Verifone';
							} else {
								$sender = 'Isracard';
							}
						}
					}
				}

				if ($user_id || $merchant_id) {
					$message = 'סיסמתך הזמנית החדשה היא:'."\n".$new_password."\n".'אנא הכנס לאפליקציה לקביעת סיסמא חדשה או לחץ על הקישור המצורף.';

					$token = aes_encrypt(json_encode(array(
						'username' => $_POST['username'],
						'password' => $new_password,
						'type' => $sender == 'Isracard' ? 'isracard' : 'verifone'
					)));

					if ($sender == 'Isracard') {
						$renew_password_url = 'https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?token='.rawurlencode($token);
					} else if ($sender == 'Verifone') {
						$renew_password_url = 'https://'.constant('GTS_CON_HOST').'/gts/console/verifone/transactions?token='.rawurlencode($token);
					}
					$message = $message."\n".$renew_password_url;

					if ($mobile) {
						$mobile = preg_replace('/^0(.+)/', '+972$1', $mobile);
						$mobile = preg_replace('/^972(.+)/', '+972$1', $mobile);

						$request = array(
							'username' => constant('SMS_USERNAME'),
							'password' => constant('SMS_PASSWORD'),
							'sender' => $sender,
							'recipient' => $mobile,
							'message' => $message,
						);
						$response = file_get_contents('https://secure.pnc.co.il/sms/', false, stream_context_create(array(
							'http' => array (
								'method' => 'POST',
								'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
								'content' => http_build_query($request)
							)
						)));
					}
					if ($email_recipient) {
						$object = getObject('GTS - Verifone - Merchants - Reset Password by Email - '.$sender);
						$object['parameters'] = getDocParameters($object['id']);

						if ($sender == 'Isracard') {
							$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/isracard/logo.png';
						} else if ($sender == 'Verifone') {
							$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/verifone/logo.png';
						}
						$email = new gyroMail();
						$email->Subject = $object['parameters']['form_general']['subject'];
						$content = $object['content'];
						$content = str_replace('{PASSWORD}', '<span dir="ltr" style="font-family:Courier;direction:ltr">'.$new_password.'</span>', $content);
						$content = str_replace('{URL}', '<a href="'.$renew_password_url.'" target="_blank">לחץ כאן</a>', $content);
						$email->Body = generate_email_template($email->Subject, false, $content, false, false, false, false, $object['parameters']);
						$email->AddAddress($email_recipient);

						if ($object['parameters']['form_general']['from_name']) {
							$email->FromName = $object['parameters']['form_general']['from_name'];
						}
						if ($object['parameters']['form_general']['from_email']) {
							$email->From = $object['parameters']['form_general']['from_email'];
						}
						$email->Send();
					}

					// Debug
					/*
					$email = new gyroMail();
					$email->Subject = 'Debug GTS Renew Password';
					$content = 'SERVER: <pre>'.print_r($_SERVER, true).'</pre>; POST: <pre>'.print_r($_POST, true).'</pre>'.'</pre>; Debug1: <pre>'.print_r($debug1, true).'</pre>'.'</pre>; Debug2: <pre>'.print_r($debug2, true).'</pre>'.'</pre>; Debug3: <pre>'.print_r($debug3, true).'</pre>';
					$email->Body = $content;
					$email->AddAddress('oa@pnc.co.il');
					$email->Send();
					*/

					$data['status'] = 'okay';

				} else {
					$error = $response['error']['text'] ?: $strings['error__invalid_username_or_id'];
				}
			} else {
				$error = $response['error']['text'] ?: $strings['error__invalid_username_or_id'];
			}
		}

		if ($error) {
			$data['status'] = 'fail';
			$data['errors'] = $error;
		}

		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_edit_note') {

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = (array(
			'trans_id' => $_POST['id'],
			'params' => array('note' => $_POST['note']),
		));
		$data['request'] = $request;
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/updateTransaction', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode($request)
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		}
		echo json_encode($data);
		exit;

	} else if ($_POST['action'] == 'gts_email_voucher') {
		// User by SIGNATURES

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls(array(
			'id' => $_POST['id'],
			'emails' => array($_POST['email']),
		));
		$data['request'] = $request;
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/emailTransactionVoucher', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode($request)
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']['text'] ?: $response['error']['code']);
				$data['callback'] = 'window.location.reload();';
			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_send_voucher') {
		// Used by the GTS CONSOLE
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls(array(
			'admin' => $_POST['admin'] ? true : false,
			'merchant_id' => $_POST['merchant_id'],
			'trans_id' => $_POST['id'],
			'recipients' => array($_POST['email']),
		));
		$data['request'] = $request;
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/emailUserTransactionVoucher', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode($request)
			)
		)));
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_submit_transaction') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls($_POST['transaction']);
		if ($request['invoice_data']['recipients']) {
			$request['invoice_data']['recipients'] = preg_replace('/[; ]+/', ',', $request['invoice_data']['recipients']);
			$request['invoice_data']['recipients'] = explode(',', $request['invoice_data']['recipients']);
		}
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/submitUserTransaction', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));

		$data['debug']['submitUserTransaction']['url'] = 'https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/submitUserTransaction';
		$data['debug']['submitUserTransaction']['request'] = $request;
		$data['debug']['submitUserTransaction']['response'] = json_decode($response, true);

		if ($response = json_decode($response, true)) {

			$data['transaction'] = $response['data'];
			$data['transaction']['status_icon_url'] = href('images/gts/default/status-'.$request['type'].'-pending@2x.png');

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';

				// Emailing the voucher
				if ($_POST['email']) {
					$lang_code = 'he';

					$request = strip_nulls(array(
						'trans_id' => $data['transaction']['trans_id'],
						'recipients' => array($_POST['email']),
					));
					$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/emailUserTransactionVoucher', false, stream_context_create(array(
						'http' => array (
							'method' => 'POST',
							'header' => 'Content-type: application/json' . "\r\n",
							'content' => json_encode($request)
						)
					)));

					//$data['debug']['emailUserTransactionVoucher']['request'] = $request;
					//$data['debug']['emailUserTransactionVoucher']['response'] = json_decode($response, true);

				}
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = ucfirst($strings['error__general']);
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_cancel_transaction') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls(array(
			'trans_id' => $_POST['id']
		));
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/cancelUserTransaction', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		$data['response'] = $response;
		if ($response = json_decode($response, true)) {

			$data['transaction'] = $response['data'];

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = ucfirst($strings['error__general']);
		}
		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_update_recurring_order') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';

		$request = strip_nulls($_POST['recurring_order']);
		if ($request['invoice_recipients']) {
			$request['invoice_recipients'] = preg_replace('/[;\s]+/', ',', $request['invoice_recipients']);
			$request['invoice_recipients'] = explode(',', $request['invoice_recipients']);
		}
		$data['request'] = $request;
		if ($request['ro_id']) {
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/updateUserRecurringOrder', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		} else {
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/createUserRecurringOrder', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		}
		if ($response = json_decode($response, true)) {

			$data['response'] = $response;
			$data['recurring_order'] = $request;
			$data['recurring_order']['ro_id'] = $data['response']['data']['ro_id'] ?: $data['recurring_order']['ro_id'];
			$data['recurring_order']['amount'] = number_format($request['amount'], 2);
			$data['recurring_order']['cc_exp'] = substr($data['recurring_order']['cc_exp'], 0, 2).'/'.substr($data['recurring_order']['cc_exp'], 2, 2);

			if ($response['result'] == 'OKAY') {
				$data['status'] = 'okay';
			} else {
				$data['status'] = 'fail';
				$data['errors'] = ucfirst($response['error']);
			}
		} else {
			$data['status'] = 'fail';
			$data['errors'] = ucfirst($strings['error__general']);
		}

		echo json_encode($data);
		exit;
	} else if ($_POST['action'] == 'gts_export_signatures') {

		if ($_POST['recipient'] && !$_POST['debug']) {
			ignore_user_abort(0);
		}
        set_time_limit(0);
        ob_implicit_flush();

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		// In order to prevent server load - we pre-define the from-to dates
		/*
		if (!$_POST['from_date'] || !$_POST['to_date']) {
			$_POST['from_date'] = date('Ymd', time()-(3600*24*7));
			$_POST['to_date'] = date('Ymd');
		}
		*/

		// Getting all the merchants IDs and names
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainMerchantItems', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls(array(
					'query' => false,
					'offset' => false,
					'limit' => false,
					'orderby' => 'name',
					'sorting' => 'asc',
					'return' => array('name', 'number'),
				)))
			)
		)));
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$all_merchants = $response['data']['merchant_items'];
				if (!empty($all_merchants)) {
					foreach ($all_merchants as $merchant) {
						$merchants_ids_hash[$merchant['id']] = array(
							'name' => $merchant['name'],
							'number' => $merchant['number'],
						);
						$merchants_names_hash[$merchant['name']] = $merchant['id'];
					}
				}
			}
		}

		$offset = 0;
		$limit = 100000;

		// The following is just in order to get the total.
		$request = array(
			'merchant_id' => $_POST['merchant_id'] ?: null,
			'created_min' => strtotime($_POST['from_date']),
			'created_max' => strtotime($_POST['to_date']) + (3600*24),
			'query' => $_POST['query'],
			'offset' => 0,
			'limit' => 1,
			'orderby' => 'created',
			'sorting' => 'desc',
			//'return' => array('merchant_id', 'amount', 'currency', 'cc_last_4', 'cc_exp', 'cc_issuer', 'credit_terms', 'transaction_code', 'payments_number', 'payments_currency_linked', 'payments_first_amount', 'payments_standing_amount', 'authorization_number', 'reference_number', 'signature_link', 'created', 'modified', 'params'),
		);
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainTransactionItems', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));
		if ($response = json_decode($response, true)) {
			if ($response['result'] == 'OKAY') {
				$output = '"'.implode('","', array($strings['id'], $strings['amount'], $strings['transaction_currency'], $strings['payments'], $strings['cc_last_4'], $strings['cc_exp'], $strings['cc_issuer'], $strings['credit_terms'], $strings['reference_number'], $strings['merchant_number'], $strings['merchant_name'], $strings['created'], $strings['note'])).'"'."\r\n";

				$transactions = $response['data']['transaction_items'];

				// Since the GTS will disconnect the request when there's alot of data invovled - we fetch the data in bulks.
				$trans_total = $response['data']['total'];
				for ($i = 0; $i < ceil($trans_total/$limit); $i++) {
					$request = array(
						'merchant_id' => $_POST['merchant_id'],
						'created_min' => strtotime($_POST['from_date']),
						'created_max' => strtotime($_POST['to_date']) + (3600*24),
						'query' => $_POST['query'],
						'offset' => $i == 0 ? 0 : ($i * $limit) + 1,
						'limit' => $limit,
						'orderby' => 'created',
						'sorting' => 'desc',
						'return' => array('merchant_id', 'amount', 'currency', 'cc_last_4', 'cc_exp', 'cc_issuer', 'credit_terms', 'transaction_code', 'payments_number', 'payments_currency_linked', 'payments_first_amount', 'payments_standing_amount', 'authorization_number', 'reference_number', 'signature_link', 'created', 'modified', 'params'),
					);
					$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_SIG_HOST').'/'.$lang_code.'/rest/obtainTransactionItems', false, stream_context_create(array(
						'http' => array (
							'method' => 'POST',
							'header' => 'Content-type: application/json' . "\r\n",
							'content' => json_encode(strip_nulls($request))
						)
					)));
					/*
					echo '<pre>Request: '; print_r($request); echo '</pre>';
					echo '<pre>Response: '; print_r($response); echo '</pre>';
					exit;
					*/
					$response = json_decode($response, true);
					$transactions = $response['data']['transaction_items'];
					if (!empty($transactions)) {
						for ($j = 0; $j < count($transactions); $j++) {
				    		/*
				    		if ($transactions[$j]['payments_number'] < 2) {
		        				$amount = $transactions[$j]['amount'].' '.$transactions[$j]['currency'];
		        			} else {
			        			$amount = $transactions[$j]['payments_first_amount'].' '.$transactions[$j]['currency'].' + '.$transactions[$j]['payments_standing_amount'].' x '.$transactions[$j]['payments_number'];
		        			}
		        			$output .= '"'.implode('","', array($transactions[$j]['id'], $amount, $transactions[$j]['cc_last_4'], substr($transactions[$j]['cc_exp'], 0, 2).'/'.substr($transactions[$j]['cc_exp'], 2, 2), ucwords($transactions[$j]['cc_issuer']), ucwords($strings['credit_terms__'.$transactions[$j]['credit_terms']]), $transactions[$j]['reference_number'], $merchants_ids_hash[$transactions[$j]['merchant_id']]['number'], $merchants_ids_hash[$transactions[$j]['merchant_id']]['name'], date('d/m/Y H:i', $transactions[$j]['created']), $transactions[$j]['params']['note'])).'"'."\r\n";
							*/
							$amount = $transactions[$j]['amount'];
							$payments = $transactions[$j]['payments_number'] ?: 1;

							if ($transactions[$j]['cc_issuer'] == 'isracard' && $transactions[$j]['credit_terms'] == 'isracredit-amexcredit') {
	        					$credit_terms = $strings['credit_terms__isracard__'.$transactions[$j]['credit_terms']];
	        				} else if ($transactions[$j]['cc_issuer'] == 'visa-cal' && $transactions[$j]['credit_terms'] == 'isracredit-amexcredit') {
	        					$credit_terms = $strings['credit_terms__visacal__'.$transactions[$j]['credit_terms']];
	        				} else if ($transactions[$j]['cc_issuer'] == 'leumi-card' && $transactions[$j]['credit_terms'] == 'isracredit-amexcredit') {
	        					$credit_terms = $strings['credit_terms__leumicard__'.$transactions[$j]['credit_terms']];
	        				} else {
	        					$credit_terms = $strings['credit_terms__'.$transactions[$j]['credit_terms']];
	        				}

		        			$line = '"'.implode('","', array($transactions[$j]['id'], $amount, $strings['currency_'.strtolower($transactions[$j]['currency'])], $payments, $transactions[$j]['cc_last_4'], substr($transactions[$j]['cc_exp'], 0, 2).'/'.substr($transactions[$j]['cc_exp'], 2, 2), ucwords($transactions[$j]['cc_issuer']), $credit_terms, $transactions[$j]['reference_number'], $merchants_ids_hash[$transactions[$j]['merchant_id']]['number'], $merchants_ids_hash[$transactions[$j]['merchant_id']]['name'], date('d/m/Y H:i', $transactions[$j]['created']), $transactions[$j]['params']['note'])).'"'."\r\n";
				    		$output .= iconv('UTF-8', 'WINDOWS-1255', $line);
				    	}
				    }
				}
			} else {
				// GTS access token expired
				//Header('Location:'.href($doc.'?action=logout'));
				exit;
			}
		}

		if ($_POST['debug']) {
			echo $output;
		} else if ($_POST['recipient']) {
			//$output = iconv('UTF-8', 'WINDOWS-1255', $output);

			$zip = new ZipArchive();
			$zip_file = 'signatures_'.time().'.zip';
			$zip_path = CONSTANT('FILES_BASE').'exported/'.$zip_file;
			if (file_exists($zip_path)) {
				if ($zip->open($zip_path, ZIPARCHIVE::OVERWRITE) !== true) {
					exit('an error occured while creating the archive');
				}
			} else {
				if ($zip->open($zip_path, ZIPARCHIVE::CREATE) !== true) {
					exit('an error occured while creating the archive');
				}
			}
			$zip->addFromString('signatures_from_'.($_POST['from_date'] ?: '').'_to_'.($_POST['to_date'] ?: '').'.csv', $output);
			//set_time_limit(60*10);
			//echo 'The zip archive contains '.$zip->numFiles.' files with a status of '.$zip->status."\n";
			//exit;
			$zip->close();
			if (file_exists($zip_path)) {
				$email = new gyroMail();
				$email->Subject = 'דו״ח עסקאות';
				$content = 'דו״ח העסקאות שביקשתם להפיק מצורף.<br><a href="'.constant('HTTPS_URL').'files/exported/'.$zip_file.'">לחצו כאן להורדה</a>';
				$object = false;
				$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/verifone/logo.png';
				$email->Body = generate_email_template($email->Subject, false, $content, false, false, false, false, $object['parameters']);
				$email->AddAddress($_POST['recipient']);
				//$email->AddStringAttachment($output, 'Report.csv', 'base64', 'text/csv');
				//$email->AddAttachment($zip_path, 'signatures.zip');
				$email->Send();
			} else {
				echo 'Error creating zip file - '.$zip_path;
			}
		} else {
			//$output = iconv('UTF-8', 'WINDOWS-1255', $output);
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename=signatures.csv');
			echo $output;
		}

		exit;

	} else if ($_GET['action'] == 'gts_export_transactions') {

		set_time_limit(0);

		recursive('trim', $_GET);
		recursive('stripslashes', $_GET);
		recursive('strip_tags', $_GET);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		if ($_GET['recurring_order']) {
			$type = 'recurring_order';
		} else {
			$type = 'transaction';
		}

		// In order to prevent server load - we pre-define the from-to dates
		/*
		if (!$_GET['from_date'] || !$_GET['to_date']) {
			$default_from_date = date('Ymd', time()-(3600*24*7));
			$default_to_date = date('Ymd');
		}
		*/

		if ($type == 'recurring_order') {
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserRecurringOrderDetails', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls(array(
						'ro_id' => $_GET['recurring_order'],
						'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
						'exportTransactions' => 1,
					)))
				)
			)));
		} else {

			if ($_GET['query'] == 'מזומן') {
				$query = 'cash';
			} else if ($_GET['query'] == 'צ׳ק' || $_GET['query'] == 'צק' || $_GET['query'] == 'ציק') {
				$query = 'check';
			} else if ($_GET['query'] == 'אשראי') {
				$query = 'credit';
			} else {
				$query = $_GET['query'];
			}

			$request = array(
				'admin' => $_GET['admin'],
				'merchant_id' => $_GET['merchant_id'],
				'date_start' => (!$query && ($_GET['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_GET['from_date'] ?: $default_from_date)) : false,
				'date_end' => (!$query && ($_GET['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_GET['to_date'] ? $_GET['to_date'] : $default_to_date) + (3600*23)) : false,
				'search' => $query,
				'offset' => null,
				'limit' => null,
				'orderby' => 'timestamp',
				'sorting' => 'desc',
				'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
				'exportTransactions' => 1,
				//'exportInvoices' => $_GET['export_invoices'] ? 1 : null,
			);
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactions', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
		}

		$output = $response;

		if (!$_GET['debug']) {
			// Redirect output to a client’s web browser (Excel5)
	        header('Content-Type: application/vnd.ms-excel');
	        header('Content-Disposition: attachment;filename="transactions.xls"');
	        header('Cache-Control: max-age=0');
	        // If you're serving to IE 9, then the following may be needed
	        header('Cache-Control: max-age=1');
	        // If you're serving to IE over SSL, then the following may be needed
	        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	        header ('Pragma: public'); // HTTP/1.0
		}

		echo $output;
		exit;

	} else if ($_POST['action'] == 'gts_export_invoices') {

		set_time_limit(0);

		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		if ($_POST['recurring_order']) {
			$type = 'recurring_order';
		} else {
			$type = 'transaction';
		}

		if ($_POST['recipients']) {
			// In order to prevent server load - we pre-define the from-to dates
			/*
			if (!$_POST['from_date'] || !$_POST['to_date']) {
				$default_from_date = date('Ymd', time()-(3600*24*7));
				$default_to_date = date('Ymd');
			}
			*/

			if ($type == 'recurring_order') {
				$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserRecurringOrderDetails', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode(strip_nulls(array(
							'ro_id' => $_POST['recurring_order'],
							'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
							'exportInvoices' => explode(',', str_replace(array(', ', ';', ' '), array(',', ',', ','), $_POST['recipients'])),
						)))
					)
				)));
			} else {
				$request = array(
					'merchant_id' => $_POST['merchant_id'] ?: null,
					'date_start' => (!$query && ($_POST['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_POST['from_date'] ?: $default_from_date)) : false,
					'date_end' => (!$query && ($_POST['from_date'] || $default_from_date)) ? date('Y-m-d', strtotime($_POST['to_date'] ? $_POST['to_date'] : $default_to_date) + (3600*23)) : false,
					'search' => $_POST['query'],
					'offset' => null,
					'limit' => null,
					'orderby' => 'timestamp',
					'sorting' => 'desc',
					'obtainUserTransactionDetails' => array('credit_data', 'check_data', 'invoice_data', 'params'),
					'exportInvoices' => explode(',', str_replace(array(', ', ';', ' '), array(',', ',', ','), $_POST['recipients'])),
				);
				$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/obtainUserTransactions', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode(strip_nulls($request))
					)
				)));
			}

			$data['status'] = 'OKAY';

		} else {
			$data['status'] = 'FAIL';
			$data['error'] = $strings['error__no_recipients_found'];
		}
		echo json_encode($data);
		exit;

	} else if ($_GET['action'] == 'gts_export_vf_merchants') {

		set_time_limit(0);

		recursive('trim', $_GET);
		recursive('stripslashes', $_GET);
		recursive('strip_tags', $_GET);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		$offset = 0;
		$limit = 50;

		// Since the GTS will disconnect the request when there's alot of data invovled - we fetch the data in bulks.
		$request = array(
			'merchant_id' => null,
			'created_min' => $_GET['from_created_date'] ? strtotime($_GET['from_created_date']) : null,
			'created_max' => $_GET['to_created_date'] ? strtotime($_GET['to_created_date'])+(3600*24) : null,
			'stats_time_min' => $_GET['from_stats_date'] ? strtotime($_GET['from_stats_date']) : null,
			'stats_time_max' => $_GET['to_stats_date'] ? strtotime($_GET['to_stats_date'])+(3600*24) : null,
			'query' => $_GET['query'],
			'offset' => 0,
			'limit' => 1,
			'orderby' => 'created',
			'sorting' => 'desc',
		);
		$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($request))
			)
		)));

		$response = json_decode($response, true);

		//echo '<pre>'; print_r($response); echo '</pre>';
		//exit;

		$output = '"'.implode('","', array($strings['id'], $strings['username'], $strings['mobile'], $strings['merchant_number'], $strings['merchant_type'], $strings['merchant_status'], $strings['company_name'], $strings['company_number'], $strings['company_email'], $strings['mailer_name'], $strings['mailer_email'], $strings['card_readers'], $strings['purchased_card_readers'], $strings['cc_count'], $strings['checks_count'], $strings['cash_count'], $strings['enable_invoices__short'], $strings['created'], $strings['note'])).'"'."\r\n";
		$output = iconv('UTF-8', 'WINDOWS-1255', $output);

		$merchants_total = $response['data']['total'];

		for ($i = 0; $i < ceil($merchants_total/$limit); $i++) {
			$request = array(
				'merchant_id' => null,
				'created_min' => $_GET['from_created_date'] ? strtotime($_GET['from_created_date']) : null,
				'created_max' => $_GET['to_created_date'] ? strtotime($_GET['to_created_date'])+(3600*24) : null,
				'stats_time_min' => $_GET['from_stats_date'] ? strtotime($_GET['from_stats_date']) : null,
				'stats_time_max' => $_GET['to_stats_date'] ? strtotime($_GET['to_stats_date'])+(3600*24) : null,
				'query' => $_GET['query'],
				'offset' => $i == 0 ? 0 : ($i * $limit) + 1,
				'limit' => $limit,
				'orderby' => 'created',
				'sorting' => 'desc',
			);
			$response = file_get_contents('https://'.getVar('gts_credentials').'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/VF_obtainMerchants', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json' . "\r\n",
					'content' => json_encode(strip_nulls($request))
				)
			)));
			if ($response = json_decode($response, true)) {
				if ($response['result'] == 'OKAY') {
					$merchants = $response['data']['items'];
					for ($j = 0; $j < count($merchants); $j++) {
			    		$line = '"'.implode('","', array($merchants[$j]['id'], $merchants[$j]['username'], $merchants[$j]['mobile'], $merchants[$j]['number'], $merchants[$j]['params']['merchant_type'], $strings['status__'.($merchants[$j]['params']['merchant_status'] ?: 'active')], $merchants[$j]['company']['name'], $merchants[$j]['company']['number'], $merchants[$j]['company']['email'], $merchants[$j]['sender']['name'], $merchants[$j]['sender']['email'], ($merchants[$j]['stats']['card_readers'][0] ? count($merchants[$j]['stats']['card_readers']) : '0'), $merchants[$j]['params']['purchased_card_readers'], $merchants[$j]['stats']['trans_credit_num'], $merchants[$j]['stats']['trans_check_num'], ($merchants[$j]['invoices']['template'] ? 'ח"ן' : 'ללא'), $merchants[$j]['stats']['trans_cash_num'], date('d/m/Y H:i', $merchants[$j]['created']), $merchants[$j]['params']['note'])).'"'."\r\n";
			    		$output .= iconv('UTF-8', 'WINDOWS-1255', $line);
			    	}
				} else {
					// GTS access token expired
					echo 'Result FAIL';
					exit;
				}
			}

		}
		if (!$_GET['debug']) {
			//$output = iconv('UTF-8', 'WINDOWS-1255', $output);
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename=merchants.csv');
		}
		echo $output;
		exit;

	} else if ($_POST['action'] == 'send_password') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$lang_code = 'he';
		include 'include/strings/strings.inc.php';

		$new_password = $_POST['password'];

		//$message = 'סיסמתך הזמנית החדשה היא:'."\n".$new_password."\n".'אנא הכנס לאפליקציה לקביעת סיסמא חדשה או לחץ על הקישור המצורף.';
		$message = 'אנא הכנס לאפליקציה לקביעת סיסמא חדשה - לחצו על הקישור המצורף.';

		$token = aes_encrypt(json_encode(array(
			'username' => $_POST['username'],
			'password' => $_POST['password'],
			'type' => ($_POST['merchant_type'] == 'ישראכרט' || $_POST['merchant_type'] == 'Isracard') ? 'isracard' : 'verifone'
		)));

		if ($_POST['merchant_type'] == 'ישראכרט' || $_POST['merchant_type'] == 'Isracard') {
			$renew_password_url = 'https://'.constant('GTS_CON_HOST').'/gts/console/isracard/transactions?token='.rawurlencode($token);
		} else {
			$renew_password_url = 'https://'.constant('GTS_CON_HOST').'/gts/console/verifone/transactions?token='.rawurlencode($token);
		}
		$message = $message."\n".$renew_password_url;

		if ($_POST['method'] == 'sms') {
			$mobile = preg_replace('/^0(.+)/', '+972$1', $_POST['mobile']);
			$mobile = preg_replace('/^972(.+)/', '+972$1', $mobile);

			if ($_POST['merchant_type']) {
				$sender = ($_POST['merchant_type'] == 'ישראכרט' || $_POST['merchant_type'] == 'Isracard') ? 'Isracard' : 'Verifone';
			} else {
				$sender = 'Verifone';
			}

			$request = array(
				'username' => constant('SMS_USERNAME'),
				'password' => constant('SMS_PASSWORD'),
				'sender' => $sender,
				'recipient' => $mobile,
				'message' => $message,
			);
			$response = file_get_contents('https://secure.pnc.co.il/sms/', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
					'content' => http_build_query($request)
				)
			)));
			$data['status'] = 'OKAY';
			$data['debug']['request'] = $request;
			$data['debug']['response'] = $response;
		} else if ($_POST['method'] == 'email') {
			$object = getObject('GTS - Verifone - Merchants - Send Password by Email - '.($_POST['merchant_type'] == 'ישראכרט' || $_POST['merchant_type'] == 'Isracard' ? 'Isracard' : 'Verifone'));
			$object['parameters'] = getDocParameters($object['id']);
			if ($_POST['merchant_type'] == 'ישראכרט' || $_POST['merchant_type'] == 'Isracard') {
				$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/isracard/logo.png';
			} else {
				$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/verifone/logo.png';
			}
			$email = new gyroMail();
			$email->Subject = $object['parameters']['form_general']['subject'];

			$content = $object['content'];
			$content = str_replace('{PASSWORD}', $_POST['password'], $content);
			$content = str_replace('{URL}', '<a href="'.$renew_password_url.'" target="_blank">לחץ כאן</a>', $content);

			$email->Body = generate_email_template($email->Subject, false, $content, false, false, false, false, $object['parameters']);
			$email->AddAddress($_POST['email']);
			if ($object['parameters']['form_general']['from_name']) {
				$email->FromName = $object['parameters']['form_general']['from_name'];
			}
			if ($object['parameters']['form_general']['from_email']) {
				$email->From = $object['parameters']['form_general']['from_email'];
			}
			try {
				if ($email->Send()) {
					$data['status'] = 'OKAY';
				} else {
					$data['status'] = 'FAIL';
					$data['error'] = $email->ErrorInfo;
				}
			} catch (phpmailerException $e) {
				$data['status'] = 'FAIL';
				$data['error'] = $e->errorMessage();
			} catch (Exception $e) {
				$data['status'] = 'FAIL';
				$data['error'] = $e->getMessage();
			}
		}

		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}

	if ($_POST['action'] == 'confirm_invitation') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		// Searching for an existing name
		$search__doc_type = array('element', 'confirmation');
		$constraints = false;
		$query = false;
		$query[] = array(array('confirmation:name'), $_POST['confirmation']['name']);
		$query[] = array(array('confirmation:from'), 'liron');
		$expression = 'and';
		$sort = false;
		$results = searchDocs($search__doc_type, $constraints, $query, $expression, $sort);

		if (!empty($results)) {
			$response['error'] = 'אופס :) כבר אישרתם הגעה...';
		} else {

			if ($_POST['confirmation']['cc_number']) {
				// If a CC Number exists - we try to perform the payment
				$request = array(
					'type' => 'credit',
					'amount' => $_POST['confirmation']['amount'],
					'currency' => 'ILS',
					'credit_data' => array(
						'test' => 0,
						'cc_holder_name' => $_POST['confirmation']['name'],
						'cc_number' => $_POST['confirmation']['cc_number'],
						'cc_exp' => $_POST['confirmation']['cc_exp'],
						//'cc_cvv2' => 123
						'payments_number' => $_POST['confirmation']['payments_number'],
					)
				);
				$result = file_get_contents('https://noigy:083430@'.constant('GTS_APP_HOST').'/he/rest/submitUserTransaction', false, stream_context_create(array(
					'http' => array (
						'method' => 'POST',
						'header' => 'Content-type: application/json' . "\r\n",
						'content' => json_encode($request)
					)
				)));
				$result = json_decode($result, true);
				if ($result['result'] == 'OKAY') {
					$response['data'] = $result['data'];
				} else {
					$response['debug'] = $result;
					$response['error'] = $result['error'];
				}
			}

			if (!$_POST['confirmation']['name']) {
				$response['debug'] = $errors;
				$response['error'] = 'אופס... ארעה שגיאה.'."\n".'אנא בדקו שכל הפרטים נכונים.';
			} else if (!$response['error']) {
				$element_object = new Element();
				$element_object->doc_subtype = 'confirmation';
				$element_object->doc_note = '[ LD ] '.($_POST['confirmation']['require_transportation'] == 1 ? 'Require Trans.' : '');
				if ($_POST['confirmation']['cc_number']) {
					$element_object->title = 'אישור הגעה כולל מתנה'.' - '.$_POST['confirmation']['name'];
					$_POST['confirmation']['trans_id'] = $response['data']['trans_id'];
				} else {
					$element_object->title = 'אישור הגעה - '.$_POST['confirmation']['name'];
				}

				$element_object->confirmation = $_POST['confirmation'];
				$errors = false;
				if ($element_object_id = $element_object->save($errors)) {
					$response['confirmation'] = '<b>תודה על שיתוף הפעולה!</b><br>'.'נתראה באירוע'.' :)';
					if ($response['data']['trans_id']) {
						$response['confirmation'] .= "\n".'מספר האישור שלכם: '.$response['data']['credit_data']['authorization_number'];
					}
				} else {
					$response['debug'] = $errors;
					$response['error'] = 'אופס... ארעה שגיאה.'."\n".'אנא בדקו שכל הפרטים נכונים.';
				}
			}
		}

		if (!$response['error']) {
			$response['status'] = 'OKAY';
		} else {
			$response['status'] = 'FAIL';
		}

		$response['request'] = $_POST;

		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}

	if ($_POST['action'] == 'order_form') {
		recursive('trim', $_POST);
		recursive('stripslashes', $_POST);
		recursive('strip_tags', $_POST);

		$group_object = new Group($_POST['doc_id']);

		$strings['general'] = 'כללי';
		$strings['device_type'] = 'סוג המכשיר';
		$strings['business_number'] = 'ע.מ. / ח.פ.';
		$strings['quantity'] = 'כמות';
		$strings['business_type'] = 'סוג העסק';
		$strings['contact_person'] = 'שם איש קשר';
		$strings['id_number'] = 'ת.ז.';
		$strings['fax'] = 'פקס';
		$strings['address'] = 'כתובת';
		$strings['mobile'] = 'נייד';
		$strings['phone'] = 'טלפון';
		$strings['alt_contact_person'] = 'איש קשר נוסף';
		$strings['alt_phone'] = 'טלפון של איש הקשר הנוסף';
		$strings['alt_id_number'] = 'ת.ז. של איש הקשר הנוסף';

		$strings['business_details'] = 'פרטי בית העסק';
		$strings['logo_name'] = 'לוגו מכשיר - שם העסק';
		$strings['logo_address'] = 'לוגו - כתובת העסק';
		$strings['logo_phone'] = 'לוגו - טלפון העסק';
		$strings['invoice_name'] = 'חשבונית על שם';
		$strings['invoice_address'] = 'כתובת למשלוח חשבונית';
		$strings['email'] = 'אימייל';

		$strings['credit_company_details'] = 'פרטי חברות האשראי';
		$strings['business_name'] = 'שם העסק';
		$strings['business_address'] = 'כתובת העסק';
		$strings['leumi_card_terminal_number'] = 'מספר ספק בלאומי קארד';
		$strings['leumi_card_mastercard'] = 'לאומי - סולק מאסטר-קארד';
		$strings['leumi_card_visa'] = 'לאומי - סולק ויזה';
		$strings['leumi_card_isracard'] = 'לאומי - סולק ישראכרט';
		$strings['visa_cal_terminal_number'] = 'מספר ספק בויזה כאל';
		$strings['visa_cal_mastercard'] = 'ויזה - סולק מאסטר-קארד';
		$strings['visa_cal_visa'] = 'ויזה - סולק ויזה';
		$strings['visa_cal_isracard'] = 'ויזה - סולק ישראכרט';
		$strings['isracard_terminal_number'] = 'מספר ספק בישראכרט';
		$strings['isracard_mastercard'] = 'ישראכרט - סולק מאסטר-קארד';
		$strings['isracard_visa'] = 'ישראכרט - סולק ויזה';
		$strings['isracard_isracard'] = 'ישראכרט - סולק ישראכרט';
		$strings['amex_terminal_number'] = 'מספר ספק באמקס';
		$strings['amex_mastercard'] = 'אמקס - סולק מאסטר-קארד';
		$strings['amex_visa'] = 'אמקס - סולק ויזה';
		$strings['amex_isracard'] = 'אמקס - סולק ישראכרט';
		$strings['diners_terminal_number'] = 'מספר ספק בדיינרס';
		$strings['diners_mastercard'] = 'דיינרס - סולק מאסטר-קארד';
		$strings['diners_visa'] = 'דיינרס - סולק ויזה';
		$strings['diners_isracard'] = 'דיינרס - סולק ישראכרט';

		$strings['payment'] = 'תשלום באמצעות הוראה לחיוב כרטיס אשראי';
		$strings['cc_number'] = '4 ספרות אחרונות של כרטיס האשראי';
		$strings['cc_expiration'] = 'תוקף';
		$strings['cc_id_number'] = 'ת.ז. של בעל הכרטיס';
		$strings['ro_id'] = 'מספר הוראת הקבע במערכת';

		$strings['misc'] = 'שונות';
		$strings['comments'] = 'הערות';
		$strings['approval_1'] = 'קראתי ואישרתי את הסכם שכירות ושירות DOC-ORDLEUMI-VER-01.00';
		$strings['approval_2'] = 'הריני מאשר/ת את נכונות הפרטים לעיל בטופס זה ואני מסכים/ה לכל התנאים בו';

		//echo '<pre>Server: '; print_r($_SERVER); echo '</pre>';
		// echo '<pre>DIR: '; print_r(__DIR__); echo '</pre>';
		//exit;

		$local_path = __DIR__.'/files/order-forms/';
		$file_name = 'order-form-'.date('Y-m-d-h-i-s').'.pdf';

		// Access Token
		$gts_credentials = 'veri:'.aes_decrypt(trim(strip_tags(getObject('GTS Verifone Order Form', true))));
		$lang_code = 'he';
		$access_token_response = file_get_contents('https://'.$gts_credentials.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/createUserAccessToken', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(array(
					'ip_address' => $_SERVER['REMOTE_ADDR']
				))
			)
		)));
		$access_token_response = json_decode($access_token_response, true);
		$access_token = 'veri:'.$access_token_response['data']['access_token'];

		// First we verify and store the credit card details
		$ro_request = strip_nulls(array(
			'name' => 'טופס הזמנה '.date('Y-m-d H:i').' - '.$_POST['credit_company_details']['business_name'].' (ח״פ '.$_POST['general']['business_number'].')',
			'duedate' => '2099-'.date('m-d'),
			'interval' => 'yearly',
			'amount' => 1,
			'currency' => 'ILS',
			'cc_holder_id_number' => $_POST['payment']['cc_id_number'],
			'cc_number' => $_POST['payment']['cc_number'],
			'cc_exp' => substr($_POST['payment']['cc_expiration'], 0, 2).substr($_POST['payment']['cc_expiration'], 3, 2),
		));
		$ro_response = file_get_contents('https://'.$access_token.'@'.constant('GTS_APP_HOST').'/'.$lang_code.'/rest/createUserRecurringOrder', false, stream_context_create(array(
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/json' . "\r\n",
				'content' => json_encode(strip_nulls($ro_request))
			)
		)));
		if ($ro_response = json_decode($ro_response, true)) {
			if ($ro_response['result'] == 'OKAY' && $ro_response['data']['ro_id']) {
				$_POST['payment']['ro_id'] = $ro_response['data']['ro_id'];
			} else {
				$response['status'] = 'FAIL';
				$response['error'] = 'כרטיס האשראי לא נשמר בהצלחה:'."\n".$ro_response['error'];
				header('Content-Type: application/json');
				echo json_encode($response);
				exit;
			}
		}

		ob_start();
		?>
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
				<style type="text/css">
					@page { margin: 20px; }

					body {direction:rtl;font-size:14px;font-family:Arial}

					.ltr {direction:ltr}

					table {direction:rtl;width:100%;border-collapse:collpase}
					table td {padding:10px;border:1px solid #EEE;text-align:right}
					table tr.title {page-break-inside:avoid}
					table tr.title td {font-size:2em}

				</style>
			</head>
			<body>
				<div>
					<h1><?=$group_object->title?></h1>
					<div>תאריך <span class="ltr" dir="ltr"><?=date('d/m/Y H:i')?></span></div>
					<br><br>
					<?
					if (!empty($_POST)) {
						?>
						<table cellspacing="0" cellpadding="0">
							<?
							foreach ($_POST as $section => $fields) {
								if ($section != 'action' && $section != 'doc_id') {
									?>
									<tr class="title"><td colspan="2"><?=($strings[$section] ?: $section)?></td></tr>
									<?
									if (is_array($fields) && !empty($fields)) {
										foreach ($fields as $field => $value) {
											if ($field == 'cc_number') {
												$value = substr($value, -4);
											}
											?>
											<tr>
												<td><?=($strings[$field] ?: $field)?></td>
												<td><?=($value == 'on' ? 'כן' : $value)?></td>
											</tr>
											<?
										}
									} else if ($fields) {
										$value = $fields;
										?>
										<tr>
											<td><?=($strings[$section] ?: $section)?></td>
											<td><?=($value == 'on' ? 'כן' : $value)?></td>
										</tr>
										<?
									}
								}
							}
							?>
						</table>
						<?
					}
					?>
				</div>
			</body>
		</html>
		<?
		$html = ob_get_clean();

		if ($_POST['debug']) {
			echo $html;
			exit;
		} else {
			$doc_raptor_response = file_get_contents('http://docraptor.com/docs?user_credentials=95gWBkqAtpdvRLTmfOU', false, stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
					'content' => http_build_query(array(
						'doc[document_content]' => $html,
			            'doc[document_type]'    => 'pdf',
			            'doc[name]'             => $file_name,
			            'doc[test]'             => true
					))
				)
			)));
			$file = fopen($local_path.$file_name, 'w');
			fwrite($file, $doc_raptor_response);
			fclose ($file);
		}

		//header('Content-type: application/pdf');
		//header('Content-Disposition: attachment; filename=shelf-tag-printing.pdf');
		$response['data']['pdf'] = constant('HTTPS_URL').'files/order-forms/'.$file_name;

		// Emailing the client and Verifone
		$email = new gyroMail();
		$email->Subject = $group_object->title;
		$content = 'שלום,<br>מצורף הסכם השכירות והשירות.';
		//$content = str_replace('{PASSWORD}', '<span dir="ltr" style="font-family:Courier;direction:ltr">'.$new_password.'</span>', $content);
		//$content = str_replace('{URL}', '<a href="'.$renew_password_url.'" target="_blank">לחץ כאן</a>', $content);
		$object['parameters']['header_image'] = 'https://'.constant('GTS_CON_HOST').'/images/gts/verifone/logo.png';
		$email->Body = generate_email_template($email->Subject, false, $content, false, false, false, false, $object['parameters']);
		$email->AddAttachment($local_path.$file_name);
		$email->AddAttachment(__DIR__.'/files/visa-order-form/DOC-ORDLEUMI-VER-01.00.pdf');
		$email->AddBCC('oa@pnc.co.il');
		if ($_POST['business_details']['email']) {
			$email->AddAddress($_POST['business_details']['email']);
		}
		$email->AddAddress('I_sales_tlv@verifone.com');

		$email->FromName = 'Verifone';
		$email->From = 'I_sales_tlv@verifone.com';

		$email->Send();


		$response['status'] = 'OKAY';

		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}

?>
