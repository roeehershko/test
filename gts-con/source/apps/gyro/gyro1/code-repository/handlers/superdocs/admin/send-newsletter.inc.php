<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = '" . constant('DOC') . "'"), 0)) {
    error_forbidden();
}

if ($_GET['ajax']) {
    echo renderListAJAX();
    exit;
}

if ($_POST['act'] == 'preview' || $_POST['act'] == 'send') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs();
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['recipient_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    if ($sql = mysql_fetch_assoc(mysql_query("SELECT newsletter_id, title, content, subject FROM type_newsletter WHERE newsletter_id = '$_POST[newsletter]' AND sent IS NULL"))) {
        $newsletter = $sql;
        
        for ($i = 0; $i != count($items); $i++) {
            if (substr($items[$i], 0, 1) == 'S' && ($sql = mysql_fetch_assoc(mysql_query("SELECT name, email FROM type_subscriber WHERE subscriber_id = '" . substr($items[$i], 1) . "'")))) {
                $recipients[] = $sql;
            } elseif ($sql = mysql_fetch_assoc(mysql_query("SELECT TRIM(CONCAT(first_name, ' ', last_name)) AS name, email FROM type_user WHERE user_id = '" . substr($items[$i], 1) . "'"))) {
                $recipients[] = $sql;
            }
        }
        
        if (preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $_POST['additional_recipients'], $matches)) {
            for ($i = 0; $i != count($matches[0]); $i++) {
                $recipients[] = array('email' => $matches[0][$i]);
            }
        }
    } else {
        $error = 'Invalid newsletter (cannot be re-sent).';
    }
    
    if ($_POST['act'] == 'preview') {
        ?>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        </head>
        
        <body>
        
        <table cellspacing="2" cellpadding="0" style="width: 100%; height: 100%; border: 1px solid #CCCCCC;">
            <tr style="background: #F0F0F0;">
                <td style="border-bottom: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; padding: 3px 10px 3px 10px; text-align: right; vertical-align: top; font-family: Arial; font-size: 12px; font-variant: small-caps; line-height: 16px;">subject</td>
                <td style="border-bottom: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; width: 100%; padding: 3px 10px 3px 10px; font-family: Arial; font-size: 12px;"><?=($newsletter['subject'] ? $newsletter['subject'] : 'Invalid newsletter.')?></td>
            </tr>
        
            <tr style="background: #F0F0F0;">
                <td style="border-bottom: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; padding: 3px 10px 3px 10px; text-align: right; vertical-align: top; font-family: Arial; font-size: 12px; font-variant: small-caps; line-height: 16px;">recipients</td>
                <td style="border-bottom: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; width: 100%; padding: 3px 10px 3px 10px; font-family: Arial; line-height: 16px;">
                <?php
                if ($recipients) {
                    foreach ($recipients as $recipient) {
                        $recipients_str[] = '<span style="font-size: 11px;">' . $recipient['name'] . ' &lt;' . $recipient['email'] . '&gt;</span>';
                    }
                    if (count($recipients_str) <= 3) {
                        echo implode(', ', $recipients_str) . "\n";
                    } else {
                        for ($i = 0; $i != 3; $i++) {
                            $recipients_str_first[] = $recipients_str[$i];
                        }
                        for ($i = 3; $i != count($recipients_str); $i++) {
                            $recipients_str_rest[] = $recipients_str[$i];
                        }
                        
                        echo implode(', ', $recipients_str_first);
                        echo '<span id="recipients_all" style="line-height: 16px; display: none;">, ' . implode(', ', $recipients_str_rest) . '</span><br><br>' . "\n";
                        echo '<span id="recipients_show_note" onclick="document.getElementById(\'recipients_all\').style.display = \'inline\'; document.getElementById(\'recipients_hide_note\').style.display = \'inline\'; this.style.display = \'none\';" style="font-size: 11px; cursor: pointer;">[ show all ' . count($recipients_str) . ' recipients ]</span>' . "\n";
                        echo '<span id="recipients_hide_note" onclick="document.getElementById(\'recipients_all\').style.display = \'none\'; document.getElementById(\'recipients_show_note\').style.display = \'inline\'; this.style.display = \'none\';" style="font-size: 11px; cursor: pointer; display: none;">[ show only the first few recipients ]</span>' . "\n";
                    }
                } else {
                    echo '<span style="font-size: 12px;">No recipients</span>' . "\n";
                }
                ?>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="height: 100%;">
                    <iframe src="<?=href('/?doc=' . constant('DOC'))?>&newsletter=<?=$_POST['newsletter']?>&act=preview-content" frameborder="0" style="width: 100%; height: 100%;"></iframe>
                </td>
            </tr>
        </table>
        
        </body>
        </html>
        <?
    } elseif ($_POST['act'] == 'send') {
        ?>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            
            <script type="text/javascript">
            window.focus();
            
            var listFrame;
            var listReport;
            var listInterval;
            
            var xI_t; // top
            var xI_b; // bottom
            var xI_c; // current
            
            function xI() {
                var elements = document.getElementsByTagName('VAR');
                if (elements.length > 0) {
                    var div = document.createElement('DIV');
                    div.style.position = 'absolute';
                    div.style.top = '125px';
                    div.innerHTML = elements[0].innerHTML;
                    
                    if (!listFrame) {
                        listFrame = document.getElementById('list-frame');
                        xI_t = xI_b = div;
                    }
                    
                    elements[0].parentNode.removeChild(elements[0]);
                    listFrame.appendChild(div);
                }
            }
            
            function xU() {
                // Initialize.
                if (!listFrame) {
                    xI();
                    return;
                }
                
                // If the report is available, halt the scrolling and show the report.
                if (listReport) {
                    window.clearInterval(listInterval);
                    
                    listFrame.innerHTML = listReport;
                    
                    window.parent.$('Dialog_iframe').hideLoader();
                    
                    return;
                }
                
                // Update the the bottom item if it moved completely inside the frame, and advance the new bottom item.
                if (xI_b.offsetTop + xI_b.offsetHeight <= 125) {
                    xI();
                    
                    // Pause if there are no pending items.
                    if (xI_b.nextSibling) {
                        xI_b = xI_b.nextSibling;
                    } else {
                        return;
                    }
                }
                
                // Update the top item if it moved outside the frame, and remove the old top item.
                // Upon reaching the last item, pause (until the report is available).
                if (xI_t.offsetTop + xI_t.offsetHeight <= 0) {
                    if (xI_t.nextSibling) {
                        xI_t = xI_t.nextSibling;
                        xI_t.parentNode.removeChild(xI_t.previousSibling);
                    } else {
                        xI_t.parentNode.removeChild(xI_t);
                        return;
                    }
                }
                
                // Advance all items within the frame, starting with the top item.
                xI_c = xI_t;
                while (xI_c != xI_b) {
                    xA(xI_c);
                    xI_c = xI_c.nextSibling;
                }
                xA(xI_b); // Advance the bottom item.
            }
            
            function xA(item) {
                item.style.top = item.offsetTop - 1 + 'px';
                item.style.top = item.offsetTop - 1 + 'px';
                item.style.top = item.offsetTop - 1 + 'px';
            }
            
            function xR(string) {
                listReport =
                      '<table cellspacing="0" cellpadding="0" style="width: 100%; height: 125px; padding: 5px; font-family: Courier New; font-size: 12px; text-align: center;">'
                    + '    <tr>'
                    + '        <td>' + string + '</td>'
                    + '    </tr>'
                    + '</table>';
            }
            </script>
            
            <style type="text/css">
            html, body {
                margin: 0;
                padding: 0;
            }
            #list-frame {
                width: 500px;
                height: 125px;
                position: relative;
                overflow: hidden;
                font-family: Courier New;
                font-size: 12px;
            }
            #list-frame div {
                width: 500px;
                padding: 5px 0 5px 0;
                text-align: center;
            }
            </style>
        </head>
        
        <body>
        
        <div id="list-frame"></div>
        
        <script type="text/javascript">
        var fixPNG = (parseFloat(navigator.appVersion.split('MSIE')[1]) <= 6) ? 1 : 0;
        
        document.getElementById('list-frame').innerHTML =
              (!fixPNG ? '<img src="/repository/admin/images/fadebar-t.png" style="width: 500px; height: 50px; position: absolute; top: 0; z-index: 10;">' : '<span style="width: 500px; height: 50px; position: absolute; top: 0; z-index: 10; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/fadebar-t.png\', sizingMethod=\'scale\');"></span>')
            + (!fixPNG ? '<img src="/repository/admin/images/fadebar-b.png" style="width: 500px; height: 50px; position: absolute; bottom: 0; z-index: 10;">' : '<span style="width: 500px; height: 50px; position: absolute; bottom: 0; z-index: 10; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/fadebar-b.png\', sizingMethod=\'scale\');"></span>');
        
        listInterval = window.setInterval(xU, 1);
        </script>
        
        <?
        if (!$error && !$recipients) {
            $error = 'Invalid recipient(s).';
        }
        if (!$error) {
            ignore_user_abort(1);
            set_time_limit(0);
            // Oren - 2013-07-17 - The following 2 lines caused extremely weird problems with PHP5.5 (the renderTemplate sometimes came out blank)
            //ob_implicit_flush();
            //echo str_repeat(' ', 255) . "\n";
            
            mysql_query("UPDATE type_newsletter SET sent = NOW() WHERE newsletter_id = '$newsletter[newsletter_id]'");
            
            shuffle($recipients); // Used to avoid giving away information about the recipients list size.
            
            $listcnt = count($recipients);
            $listlen = strlen($listcnt);
            
            $content = preg_replace('/(images|files)\/([^\'">);]+)/i', constant('HTTP_URL') . '$1/$2', $newsletter['content']);
            $parameters = getDocParameters($newsletter['newsletter_id']);
            
            // Create a general template of the newsletter from which to extract all the links.
            $template = renderTemplate('newsletters/newsletter', array(
                'standalone' => true,
                'recipient' => array(
                    'name' => constant('NEWSLETTER_DEFAULT_RECIPIENT_NAME'),
                    'email' => constant('NEWSLETTER_DEFAULT_RECIPIENT_EMAIL')
                ),
                'title' => $newsletter['title'],
                'content' => $content,
                'parameters' => $parameters
            ));
            
            // Find all links in the data-filled newsletter template.
            $links = array();
            if (preg_match_all('/<a\s[^>]*href=([\"\']?)(?!mailto|#)([^\s\"\']+)\\1[^>]*>/Uis', $template, $matches)) {
                for ($i = 0; $i != count($matches[0]); $i++) {
                    $links[] = array(
                        'tag' => $matches[0][$i],
                        'url' => $matches[2][$i]
                    );
                    
                    mysql_query("INSERT INTO type_newsletter__links (newsletter_id, link_id, url) VALUES ('$newsletter[newsletter_id]', '" . ($i + 1) . "', '" . mysql_real_escape_string(str_replace('&amp;', '&', $matches[2][$i])) . "')");
                }
            }
            
            //echo '<pre>'; print_r($links); echo '</pre>';
            //exit;
            
            $mail = new gyroMail();
            if ($_POST['reply_to_email']) {
                $mail->AddReplyTo($_POST['reply_to_email']);
            }
            $mail->Subject = $newsletter['subject'];
            
            for ($i = 0; $i != count($recipients); $i++) {
                $mail->AddAddress($recipients[$i]['email'], $recipients[$i]['name']);
                $mail->Body = renderTemplate('newsletters/newsletter', array(
                    'standalone' => true,
                    'recipient' => $recipients[$i],
                    'title' => $newsletter['title'],
                    'content' => preg_replace('/(images|files)\/([^\'">);]+)/i', constant('HTTP_URL') . '$1/$2', $newsletter['content']),
                    'parameters' => $parameters,
                    'direct_url' => constant('HTTP_URL') . $newsletter['newsletter_id'] . '?link=' . ($i + 1) . '-' . '0' . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $newsletter['newsletter_id'] . ($i + 1) . '0'), 16, 8)),
                    'unsubscribe_url' => constant('HTTP_URL') . $newsletter['newsletter_id'] . '?link=' . ($i + 1) . '-' . 'X' . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $newsletter['newsletter_id'] . ($i + 1) . 'X'), 16, 8))
                ));
                
                for ($j = 0; $j != count($links); $j++) {
                    $link = ($i + 1) . '-' . ($j + 1) . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $newsletter['newsletter_id'] . ($i + 1) . ($j + 1)), 16, 8));
                    $atag = preg_replace('/' . preg_quote($links[$j]['url'], '/') . '/', constant('HTTP_URL') . $newsletter['newsletter_id'] . '?link=' . $link, $links[$j]['tag']);
                    $mail->Body = preg_replace('/' . preg_quote($links[$j]['tag'], '/') . '/', $atag, $mail->Body);
                }
                
                // Add an image for logging when a recipient opens the newsletter.
                $mail->Body .= "\n" . '<img src="' . constant('HTTP_URL') . $newsletter['newsletter_id'] . '?link=' . ($i + 1) . '-' . 'O' . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $newsletter['newsletter_id'] . ($i + 1) . 'O'), 16, 8)) . '">';
                
                if ($mail->Send()) {
                    mysql_query("INSERT INTO type_newsletter__recipients (newsletter_id, recipient_id, name, email) VALUES ('$newsletter[newsletter_id]', '" . ($i + 1) . "', " . SQLNULL(mysql_real_escape_string($recipients[$i]['name'])) . ", '" . mysql_real_escape_string($recipients[$i]['email']) . "')");
                    $success = true;
                } else {
                    $success = false;
                    $errors[] = str_pad($recipients[$i]['email'], 52, '.', STR_PAD_RIGHT) . ' ' . $mail->ErrorInfo;
                }
                
                $report[] = str_pad($recipients[$i]['email'], 52, '.', STR_PAD_RIGHT) . ' ' . ($success ? 'OKAY' : $mail->ErrorInfo).' (length: '.strlen($mail->Body).')';
                
                if ($listcnt <= 100) {
                    echo '<var style="display: none;">[' . str_pad(($listcnt - $i), $listlen, '0', STR_PAD_LEFT) . '] ' . str_pad($recipients[$i]['email'], 52, '.', STR_PAD_RIGHT) . ($success ? 'OKAY' : '<span style="color: #FF0000;">FAIL</span>') . '</var>' . "\n";
                }
                
                $mail->ClearAddresses();
            }
            
            if ($listcnt > 100) {
                echo '<var style="display: none;"></var>' . "\n";
                echo '<script>xR("The newsletter is being sent to ' . $listcnt . ' recipient(s).<p>A report will be sent to the web-site administrator if errors occur.<p>You may now close this window.")</script>' . "\n";
            }
            
            mysql_query("UPDATE type_newsletter SET completed = NOW() WHERE newsletter_id = '$newsletter[newsletter_id]'");
            
            if ($listcnt <= 100) {
                echo '<script>xR("The newsletter has been sent to ' . $listcnt . ' recipient(s).<p>' . ($errors ? count($errors) . ' error(s)' : 'No errors') . ' encountered.")</script>' . "\n";
            } else {
                if ($report) {
                    $mail = new gyroMail();
                    $mail->Subject = 'Send Newsletter Report - ' . constant('SITE_NAME');
                    $mail->AddAddress(constant('SYSTEM_MANAGER_EMAIL'));
                    //$mail->AddBCC('oa@pnc.co.il', 'Oren Agiv');
                    //$mail->AddAddress('anton@kay.me', 'Anton Kay');
                    $mail->Body = '<pre style="font-size: 12px;">The newsletter from ' . constant('SITE_NAME') . ' has been sent to ' . $listcnt . ' recipient(s).<p>' . ($errors ? count($errors) . ' error(s) encountered:' : 'No errors encountered.') . "\n\n" . ($errors ? implode("\n", array_reverse($errors)) : false) . '</pre>';
                    $mail->Send();
                }
            }
        } else {
            echo '<var style="display: none;"></var>' . "\n";
            echo '<script>xR("' . $error . '")</script>' . "\n";
        }
        ?>
    </body>
    </html>
    <?
    }
    
    exit;
}

if ($_GET['act'] == 'preview-content') {
    if ($sql = mysql_fetch_assoc(mysql_query("SELECT newsletter_id, title, content, subject FROM type_newsletter WHERE newsletter_id = '$_GET[newsletter]'"))) {
        echo renderTemplate('newsletters/newsletter', array(
            'standalone' => true,
            'recipient' => array(
                'name' => constant('NEWSLETTER_DEFAULT_RECIPIENT_NAME'),
                'email' => constant('NEWSLETTER_DEFAULT_RECIPIENT_EMAIL')
            ),
            'title' => $sql['title'],
            'content' => $sql['content'],
            'parameters' => getDocparameters($sql['newsletter_id'])
        ));
    } else {
        echo 'Invalid newsletter.';
    }
    
    ?>
    <script type="text/javascript">
    document.onkeydown = function (e) {
        if ((window.event && window.event.keyCode == 27) || (e && e.which == 27)) {
             window.parent.parent.$('Dialog_iframe').close();
        }
    }
    </script>
    <?
    
    exit;
}

echo renderRecipientsList($errors);


// Functions.

function renderRecipientsList($errors = false) {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Send Newsletter</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/boxover.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/boxover.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        var uri = '<?=href('/?' . $_SERVER['QUERY_STRING'])?>' + window.location.hash.replace(/^#/, '&');
        
        var items_metad = new Array(
            Array(null, '&nbsp;'),
            Array('id', 'Id'),
            Array('name', 'Name'),
            Array('email', 'Email'),
            Array('type', 'Type'),
            Array('bounce', 'Bounce'),
            Array('note', 'Note')
        );
        
        /* ## */
        
        function adminList_updateList__central(XMLDocument) {
            var items = XMLDocument.getElementsByTagName('item');
            var list = new Array();
            
            for (var i = 0, checked; i != items.length; i++) {
                checked = ((!items_inver && inArray(items[i].getAttribute('id'), items_array)) || (items_inver && !inArray(items[i].getAttribute('id'), items_array))) ? 'checked' : '';
                
                list[i] = '<tr class="list ' + ((list.length == 0) ? 'first' : '') + '">'
                        +     '<td class="first check"><input type="checkbox" name="items[]" value="' + items[i].getAttribute('id') + '" onclick="adminList_selectItem(this)" ' + checked + ' style="margin: 0;"></td>'
                        +     '<td class="doc-id">' + items[i].getAttribute('id').substr(1) + '</td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('name') + '</td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('email') + '</td>'
                        +     '<td style="white-space: nowrap;">' + items[i].getAttribute('type') + '</td>'
                        +     '<td style="text-align: center;">' + items[i].getAttribute('bounce') + '</td>'
                        +     '<td>' + (items[i].getAttribute('note') ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' + items[i].getAttribute('note').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/\]/g, ']]').replace(/\[/g, '[[').replace(/\r?\n/g, '<br>')  + ']" style="cursor: pointer;">' : '') + '</td>'
                        + '</tr>';
            }
            
            document.getElementById('adminList_list').innerHTML = '<table>' + items_thead + list.join("\n") + '</table>';
            
            // Update page-specific filters.
            var filters__select = new Array('filter-newsletter', 'filter-recipient-type');
            for (var j = 0; j != filters__select.length; j++) {
                var re = new RegExp(filters__select[j] + '=([^&]+)');
                var tmp = re.exec(uri);
                if (tmp) {
                    for (var i = 0; i != document.getElementById(filters__select[j]).length; i++) {
                        if (tmp[1] == document.getElementById(filters__select[j]).options[i].value) {
                            document.getElementById(filters__select[j]).selectedIndex = i;
                        }
                    }
                } else {
                    document.getElementById(filters__select[j]).selectedIndex = 0;
                }
            }
            
            // Update page-specific filter: 'filter-memberzones' (multiple select-box).
            var re = new RegExp('filter-memberzones' + '=([^&]+)');
            var tmp = re.exec(uri);
            if (tmp) {
                tmp[1] = tmp[1].split(',');
                for (var i = 0; i != document.getElementById('filter-memberzones').length; i++) {
                    for (var j = 0; j != tmp[1].length; j++) {
                        if (tmp[1][j] == document.getElementById('filter-memberzones').options[i].value) {
                            document.getElementById('filter-memberzones').options[i].selected = true;
                        }
                    }
                }
            } else {
                document.getElementById('filter-memberzones').selectedIndex = -1;
            }
        }
        
        window.onload = function () {
            adminList_updateList(adminList_updateList__initial);
            
            locationHashEval('<?=href('/?' . $_SERVER['QUERY_STRING'])?>');
        }
        </script>
        
        <script type="text/javascript">
        function newsletterSend() {
            var newsletter = document.getElementById('filter-newsletter').value;
            var recipientsNum = items_inver ? items_total - items_array.length : items_array.length;
            var additionalRecipients = document.getElementById('additional-recipients').value.match(/\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/ig);
            
            if (!additionalRecipients) {
                additionalRecipients = '';
            }
            
            if (newsletter) {
                if (items_inver || recipientsNum > 0 || additionalRecipients.length > 0) {
                    if (confirm('Are you sure you want to send the newsletter to ' + (recipientsNum + additionalRecipients.length) + ' recipient(s)?')) {
                        iframeDialog('<?=href('void')?>', 500, 125);
                        
                        $('Dialog_iframe').showLoader();
                        $('Dialog_iframe').open();
                        
                        document.getElementById('adminList').action = uri;
                        document.getElementById('adminList').target = 'Dialog_iframe_iframe';
                        document.getElementById('adminList_action').value = 'send';
                        document.getElementById('adminList_items_inver').value = items_inver;
                        document.getElementById('adminList_items_array').value = items_array.join(',');
                        document.getElementById('adminList_additional_recipients').value = document.getElementById('additional-recipients').value;
                        document.getElementById('adminList_reply_to_email').value = document.getElementById('reply-to-email').value;
                        document.getElementById('adminList').submit();
                    }
                } else {
                    alert('Please select at least one recipient.');
                }
            } else {
                alert('Please select a newsletter.');
            }
        }
        
        function newsletterPreview() {
            var newsletter = document.getElementById('filter-newsletter').value;
            var recipientsNum = items_inver ? items_total - items_array.length : items_array.length;
            var additionalRecipients = document.getElementById('additional-recipients').value.match(/\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/ig);
            
            if (!additionalRecipients) {
                additionalRecipients = '';
            }
            
            if (newsletter) {
                if (items_inver || recipientsNum > 0 || additionalRecipients.length > 0) {
                    iframeDialog('<?=href('void')?>');
                    
                    document.getElementById('adminList').action = uri;
                    document.getElementById('adminList').target = 'Dialog_iframe_iframe';
                    document.getElementById('adminList_action').value = 'preview';
                    document.getElementById('adminList_items_inver').value = items_inver;
                    document.getElementById('adminList_items_array').value = items_array.join(',');
                    document.getElementById('adminList_additional_recipients').value = document.getElementById('additional-recipients').value;
                    document.getElementById('adminList').submit();
                } else {
                    alert('Please select at least one recipient.');
                }
            } else {
                alert('Please select a newsletter.');
            }
        }
        
        function getSelectValues(element) {
            var values = new Array();
            for (var i = 0; i != element.options.length; i++) {
                if (element.options[i].selected) {
                    values[values.length] = element.options[i].value;
                }
            }
            return values;
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    
    <form method="POST" id="adminList">
    <input type="hidden" id="adminList_action" name="act">
    <input type="hidden" id="adminList_items_inver" name="adminList_items_inver">
    <input type="hidden" id="adminList_items_array" name="adminList_items_array">
    <input type="hidden" id="adminList_additional_recipients" name="additional_recipients">
    <input type="hidden" id="adminList_reply_to_email" name="reply_to_email">
    <table>
        <thead>
            <tr class="path">
                <td>
                    <a href="<?=href('/')?>">Main</a>
                    / <a href="<?=href('/?doc=admin')?>">Administration</a>
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Send Newsletter</a>
                </td>
            </tr>
            
            <? if ($errors): ?>
            <tr>
                <td>
                    <div style="font-weight: bold; color: #FF0000;">Errors:</div>
                    <? foreach ($errors as $error): ?>
                    <div style="margin-top: 8px; margin-left: 15px;"><?=$error?></div>
                    <? endforeach; ?>
                </td>
            </tr>
            <? endif; ?>
            
            <tr>
                <td>
                    Newsletter &nbsp;
                    <select id="filter-newsletter" name="newsletter" style="width: 350px;" onchange="adminList_updateURI(Array('filter-newsletter'), Array(this.value))">
                        <option value="">&nbsp;</option>
                        <?
                        $sql_query = mysql_query("SELECT newsletter_id, title FROM type_newsletter WHERE sent IS NULL ORDER BY newsletter_id DESC");
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            echo '<option value="' . $sql['newsletter_id'] . '">' . $sql['newsletter_id'] . ' - ' . $sql['title'] . '</option>' . "\n";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td style="vertical-align: top;">
                    Additional Recipients &nbsp;
                    <input type="text" id="additional-recipients" style="width: 350px;">
                </td>
            </tr>
            
            <tr>
                <td style="vertical-align: top;">
                    Reply-to email &nbsp;
                    <input type="text" id="reply-to-email" style="width: 350px;">
                </td>
            </tr>
            
            <tr>
                <td>
                    Recipient Type &nbsp;
                    <select id="filter-recipient-type" style="width: 350px;" onchange="adminList_updateURI(Array('filter-recipient-type', 'page'), Array(this.value, 1)); adminList_updatePage(1);">
                        <option value="">Users & Subscribers</option>
                        <option value="users">Users</option>
                        <option value="subscribers">Subscribers</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td style="vertical-align: top;">
                    <?
                    $sql_query = mysql_query("SELECT memberzone_id, title FROM type_memberzone ORDER BY memberzone_id ASC");
                    $options = mysql_num_rows($sql_query);
                    ?>
                    Memberzones &nbsp;
                    <select id="filter-memberzones" style="width: 300px;" size="<?=($options > 5 ? 5 : $options)?>" multiple onchange="adminList_updateURI(Array('filter-memberzones', 'page'), Array(getSelectValues(this), 1)); adminList_updatePage(1);">
                        <?
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            echo '<option value="' . $sql['memberzone_id'] . '">' . $sql['title'] . '</option>' . "\n";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Preview" class="button" style="width: 100px;" onclick="newsletterPreview(); this.blur();">
                    <input type="button" value="Send" class="button" style="width: 100px;" onclick="newsletterSend(); this.blur();">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Select" class="button" style="width: 84px;" onclick="adminList_selectItems('default'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('select-button-drop-down-menu'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="select-button-drop-down-menu" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Select All" class="button" style="width: 100px;" onclick="adminList_selectItems('all'); adminList_buttonDropDownMenu('select-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Select None" class="button" style="width: 100px;" onclick="adminList_selectItems('none'); adminList_buttonDropDownMenu('select-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Inverse" class="button" style="width: 100px;" onclick="adminList_selectItems('inverse'); adminList_buttonDropDownMenu('select-button-drop-down-menu'); this.blur();">
                        </div>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td style="text-align: center;">
                    <div id="bar-search-query"></div>
                    <div id="bar-limits"></div>
                    <div id="bar-selections"></div>
                </td>
            </tr>
            
            <tr id="bar-pages-row">
                <td style="text-align: center;">
                    <div id="bar-pages"></div>
                </td>
            </tr>
        </thead>
        
        <tbody>
            <tr>
                <td id="adminList_list"></td>
            </tr>
        </tbody>
        
        <tfoot>
            <tr>
                <td>
                    <input type="button" value="Preview" class="button" style="width: 100px;" onclick="newsletterPreview()">
                    <input type="button" value="Send" class="button" style="width: 100px;" onclick="newsletterSend()">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Select" class="button" style="width: 84px;" onclick="adminList_selectItems('default'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('select-button-drop-down-menu-2'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="select-button-drop-down-menu-2" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Select All" class="button" style="width: 100px;" onclick="adminList_selectItems('all'); adminList_buttonDropDownMenu('select-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Select None" class="button" style="width: 100px;" onclick="adminList_selectItems('none'); adminList_buttonDropDownMenu('select-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Inverse" class="button" style="width: 100px;" onclick="adminList_selectItems('inverse'); adminList_buttonDropDownMenu('select-button-drop-down-menu-2'); this.blur();">
                        </div>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
    </form>
    
    </div>
    
    </body>
    </html>
    <?
    $stdout = ob_get_contents();
    ob_end_clean();
    
    return $stdout;
}

function renderListAJAX() {
    header('Content-Type: text/xml; charset=UTF-8');
    
    list($list, $order, $sort) = renderListDocs();
    
    $total = count($list);
    $limit = preg_match('/^\d+$/', $_GET['limit']) ? $_GET['limit'] : 25;
    $page = (preg_match('/^[1-9]\d*$/', $_GET['page']) && (!$limit || $_GET['page'] <= ceil($total / $limit))) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    ##
    
    ob_start();
    
    echo "<?xml version='1.0' encoding='UTF-8'?>" . "\n\n";
    
    echo '<XMLResult>' . "\n";
    echo '    <legend total="' . $total . '" limit="' . $limit . '" page="' . $page . '" order="' . $order . '" sort="' . $sort . '" query="' . xmlentities(stripslashes($_GET['query'])) . '"/>' . "\n\n";
    for ($i = 0, $j = $offset; (!$limit || $i != $limit) && $j != $total && ($item = $list[$j]); $i++, $j++) {
        echo '    ';
        echo '<item';
        echo ' id="' . $item['recipient_id'] . '"';
        echo ' name="' . xmlentities($item['name']) . '"';
        echo ' email="' . xmlentities($item['email']) . '"';
        echo ' type="' . xmlentities($item['type']) . '"';
        echo ' bounce="' . $item['bounce'] . '"';
        echo ' note="' . xmlentities($item['doc_note']) . '"';
        echo '/>' . "\n";
    }
    echo '</XMLResult>' . "\n";
    
    return ob_get_clean();
}

function renderListDocs() {
    if ($_GET['orderby']) {
        $order = $_GET['orderby'];
        $sort = ($_GET['sort'] == 'desc') ? 'DESC' : 'ASC';
    } else {
        $order = 'id';
        $sort = 'DESC';
    }
    
    switch ($order) {
        case 'id': $ORDERBY = "ORDER BY recipient_id $sort"; break;
        case 'name': $ORDERBY = "ORDER BY name $sort"; break;
        case 'email': $ORDERBY = "ORDER BY email $sort"; break;
        case 'type': $ORDERBY = "ORDER BY type $sort"; break;
        case 'bounce': $ORDERBY = "ORDER BY bounce $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_note', 'recipient_id', 'name', 'email', 'type');
        
        if ($keywords = array_unique(explode(' ', $_GET['query']))) {
            foreach ($keywords as $keyword) {
                list($keyword__column, $keyword__value) = @explode(':', $keyword);
                
                if (@in_array($keyword__column, $searchArray) && $keyword__value != '') {
                    $HAVING[] = $keyword__column . " LIKE '%" . $keyword__value . "%'";
                } else {
                    $HAVING_tmp = false;
                    foreach ($searchArray as $searchVariable) {
                        $HAVING_tmp[] = $searchVariable . " LIKE '%" . $keyword . "%'";
                    }
                    $HAVING[] = "(" . implode(" OR ", $HAVING_tmp) . ")";
                }
            }
            $HAVING = "HAVING " . implode(" AND ", $HAVING);
        }
    }
    
    if ($_GET['filter-memberzones']) {
        $SELECT_U = "SELECT DISTINCT sd.doc_note, sd.doc_active, CONCAT('U', t.user_id) AS recipient_id, TRIM(CONCAT(t.first_name, ' ', t.last_name)) AS name, t.email, (SELECT cnt FROM bounce.log WHERE email = t.email) AS bounce, 'regis. user' AS type FROM sys_docs sd JOIN type_user t ON doc_id = user_id, type_memberzone__users tm_u WHERE tm_u.memberzone_id IN ('" . implode("','", explode(',', $_GET['filter-memberzones'])) . "') AND tm_u.user_id = t.user_id AND sd.doc_active = '1' AND t.send_newsletters = '1' $HAVING";
    } else {
        $SELECT_U = "SELECT sd.doc_note, sd.doc_active, CONCAT('U', t.user_id) AS recipient_id, TRIM(CONCAT(t.first_name, ' ', t.last_name)) AS name, t.email, (SELECT cnt FROM bounce.log WHERE email = t.email) AS bounce, 'regis. user' AS type FROM sys_docs sd JOIN type_user t ON doc_id = user_id WHERE sd.doc_active = '1' AND t.send_newsletters = '1' $HAVING";
    }
    $SELECT_S = "SELECT sd.doc_note, sd.doc_active, CONCAT('S', t.subscriber_id) AS recipient_id, t.name, t.email, (SELECT cnt FROM bounce.log WHERE email = t.email) AS bounce, 'subscriber' AS type FROM sys_docs sd JOIN type_subscriber t ON doc_id = subscriber_id WHERE sd.doc_active = '1' $HAVING";
    
    if ($_GET['filter-memberzones']) {
        if (!$_GET['filter-recipient-type'] || $_GET['filter-recipient-type'] == 'users') {
            $sql_query = mysql_query("$SELECT_U $ORDERBY");
        } else {
            $sql_query = false;
        }
    } else {
        if (!$_GET['filter-recipient-type']) {
            $sql_query = mysql_query("($SELECT_U) UNION ($SELECT_S) $ORDERBY");
        } elseif ($_GET['filter-recipient-type'] == 'users') {
            $sql_query = mysql_query("$SELECT_U $ORDERBY");
        } elseif ($_GET['filter-recipient-type'] == 'subscribers') {
            $sql_query = mysql_query("$SELECT_S $ORDERBY");
        } else {
            $sql_query = false;
        }
    }
    if ($sql_query) {
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $list[] = $sql;
        }
    }
    
    return array($list, $order, $sort);
}

?>
