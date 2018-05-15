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

if ($_POST['act'] == 'prepare') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs();
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['user_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    if ($_POST['act'] == 'prepare') {
        ?>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            
            <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
            <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
            
            <script type="text/javascript">
            window.onload = function () {
                initForm();
            }
            
            var oMessage;
            var oCounter;
            
            var newlineLen;
            
            function initForm() {
                oMessage = document.getElementById('SMS_message');
                oCounter = document.getElementById('SMS_counter');
                
                // A newline in SMS messages counts as two chars; check how the current browsers counts newlines for later adjustments.
                var textarea = document.createElement('textarea');
                textarea.value = "\n";
                newlineLen = textarea.value.length;
                textarea = null;
                
                count();
                oMessage.focus();
            }
            
            function char(e) {
                if (window.event) {
                    var key = window.event.keyCode;
                } else if (e) {
                    var key = e.which;
                } else {
                    return true;
                }
            }
            
            function count() {
                // Adjust text direction based on the presense of Right-to-Left characters.
                if (/[\u05d0-\u05ea]/.test(oMessage.value)) {
                    oMessage.style.direction = 'rtl';
                } else {
                    oMessage.style.direction = 'ltr';
                }
                
                // Adjust the counter to accomodate the browser-determined length of newlines.
                var newlinesCount = oMessage.value.split(/\n/).length - 1;
                var messageLength = oMessage.value.length + (newlinesCount * (2 - newlineLen));
                
                oCounter.innerHTML = messageLength;
                
                if (messageLength > (/[^\u0000-\u007f]/.test(oMessage.value) ? 70 : 160)) {
                    oCounter.style.color = '#FF0000';
                } else {
                    oCounter.style.color = '';
                }
            }
            
            function SMSSend() {
                if (document.getElementById('SMS_message').value == '') {
                    alert('Invalid message.');
                } else if (<?=count($items)?> == 0) {
                    alert('Invalid recipient(s).');
                } else if (confirm('Are you sure you want to send this SMS to <?=count($items)?> recipient(s)?')) {
                    window.parent.$('Dialog_iframe').showLoader();
                    document.getElementById('SMSForm').submit();
                }
            }
            </script>
            
            <style type="text/css">
            #SMS {
                width: 500px;
                font-family: Arial;
                font-size: 12px;
            }
            #SMS td {
                padding: 3px 5px 3px 5px;
                border-bottom: 1px solid #DDDDDD;
                border-right: 1px solid #DDDDDD;
                background-color: #F6F6F6;
            }
            #SMS td.a {
                width: 110px;
                text-align: right;
                font-variant: small-caps;
                line-height: 22px;
                white-space: nowrap;
            }
            #SMS td.c {
                font-size: 90%;
            }
            </style>
        </head>
        
        <body>
        
        <form method="POST" id="SMSForm" style="margin: 0;">
        <input type="hidden" name="act" value="send">
        <input type="hidden" name="recipients" value="<?=implode(',', $items)?>">
        <table id="SMS">
            <tr>
                <td class="a">sender</td>
                <td class="b"><input type="text" name="sender" readonly value="<?=(defined('SMS_SENDER') ? constant('SMS_SENDER') : 'Gyro')?>" style="width: 100px;"></td>
                <td class="c">The message will arrive from this sender.</td>
            </tr>
            <tr>
                <td class="a">message</td>
                <td class="b">
                    <textarea id="SMS_message" name="message" onKeyPress="count(); return char(event);" onKeyUp="count()" onBlur="count()" style="width: 175px; height: 90px; padding: 3px; overflow: auto; font-family: Arial; font-size: 12px;"></textarea>
                    <div id="SMS_counter" style="padding-top: 3px; padding-right: 1px; text-align: right; font-size: 10px;">0</div>
                </td>
                <td class="c">Messages in English are limited to 160 characters; other languages are limited to 70 characters.</td>
            </tr>
        </table>
        
        <div style="margin-top: 15px; text-align: center;">
            <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="SMSSend()" onfocus="this.blur()"><img src="/repository/admin/images/act_send.gif">&nbsp;&nbsp;Send SMS</button>
        </div>
        </form>
        
        </body>
        </html>
        <?
    }
    
    exit;
} elseif ($_POST['act'] == 'send') {
    $sender = constant('SMS_SENDER');
    $items = $_POST['recipients'] ? explode(',', $_POST['recipients']) : array();
    $message = trim(stripslashes($_POST['message']));
    
    for ($i = 0; $i != count($items); $i++) {
        if ($sql = mysql_fetch_assoc(mysql_query("SELECT TRIM(CONCAT(first_name, ' ', last_name)) AS name, mobile FROM type_user WHERE user_id = '" . $items[$i] . "'"))) {
            $recipients[] = $sql;
        }
    }
    
    if (!$recipients) {
        $error = 'Invalid recipient(s).';
    }
    if (!$message) {
        $error = 'Invalid message.';
    }
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
            width: 450px;
            height: 125px;
            position: relative;
            overflow: hidden;
            font-family: Courier New;
            font-size: 12px;
        }
        #list-frame div {
            width: 450px;
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
          (!fixPNG ? '<img src="/repository/admin/images/fadebar-t.png" style="width: 450px; height: 50px; position: absolute; top: 0; z-index: 10;">' : '<span style="width: 450px; height: 50px; position: absolute; top: 0; z-index: 10; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/fadebar-t.png\', sizingMethod=\'scale\');"></span>')
        + (!fixPNG ? '<img src="/repository/admin/images/fadebar-b.png" style="width: 450px; height: 50px; position: absolute; bottom: 0; z-index: 10;">' : '<span style="width: 450px; height: 50px; position: absolute; bottom: 0; z-index: 10; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/fadebar-b.png\', sizingMethod=\'scale\');"></span>');
    
    listInterval = window.setInterval(xU, 1);
    </script>
    <?
    if (!$error) {
        ignore_user_abort(1);
        set_time_limit(0);
        ob_implicit_flush();
        echo str_repeat(' ', 255) . "\n";
        
        $errors = 0;
        
        $listcnt = count($recipients);
        $listlen = strlen($listcnt);
        
        for ($i = 0; $i != count($recipients); $i++) {
            if (sendSMS_internal($sender, $recipients[$i]['mobile'], $message, $sendSMS_internal__error)) {
                $success = true;
            } else {
                $success = false;
                $errors++;
            }
            /*
            if (mt_rand(0, 100) <= 95) {
                $success = true;
            } else {
                $success = false;
                $errors++;
            }
            if (!$i) {
                echo '<var style="display: none;">[ DEBUG - NOTHING IS ACTUALLY BEING SENT ]</var>' . "\n";
            }
            */
            
            echo '<var style="display: none;">[' . str_pad(($listcnt - $cnt++), $listlen, '0', STR_PAD_LEFT) . '] ' . str_pad($recipients[$i]['name'] . ' - ' . $recipients[$i]['mobile'], 48, '.', STR_PAD_RIGHT) . ($success ? 'OKAY' : '<span style="color: #FF0000;">FAIL</span>') . '</var>' . "\n";
            
            // Briefly pause the script execution every 100 recipients. This seems to make the JS behave better.
            if ($i && !($i % 100)) {
                sleep(1);
            }
        }
        
        echo '<script>xR("The SMS has been sent to ' . $listcnt . ' recipient(s).<p>' . ($errors ? $errors . ' error(s)' : 'No errors') . ' encountered.")</script>' . "\n";
    } else {
        echo '<var style="display: none;"></var>' . "\n";
        echo '<script>xR("' . $error . '")</script>' . "\n";
    }
    ?>
    </body>
    </html>
    <?
    
    exit;
} elseif ($_GET['act'] == 'view-log') {
    echo @file_get_contents('https://secure.pnc.co.il/sms/report-gyro.php', false, stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
            'content' => http_build_query(array(
                'username' => constant('SMS_USERNAME'),
                'password' => constant('SMS_PASSWORD'),
                'sender' => constant('SMS_SENDER'),
                'sms_price' => constant('SMS_PRICE'),
                'filter-year' => $_GET['filter-year'],
                'filter-month' => $_GET['filter-month'],
                'filter-day' => $_GET['filter-day'],
            ))
        )
    )));
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
        <title>Admin. / Send SMS</title>
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
            Array('mobile', 'Mobile'),
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
                        +     '<td class="doc-id">' + items[i].getAttribute('id') + '</td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('name') + '</td>'
                        +     '<td style="width: 50%;"><a href="mailto:' + items[i].getAttribute('email') + '">' + items[i].getAttribute('email') + '</a></td>'
                        +     '<td style="white-space: nowrap;">' + items[i].getAttribute('mobile') + '</td>'
                        +     '<td>' + (items[i].getAttribute('note') ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' + items[i].getAttribute('note').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/\]/g, ']]').replace(/\[/g, '[[').replace(/\r?\n/g, '<br>')  + ']" style="cursor: pointer;">' : '') + '</td>'
                        + '</tr>';
            }
            
            document.getElementById('adminList_list').innerHTML = '<table>' + items_thead + list.join("\n") + '</table>';
        }
        
        window.onload = function () {
            adminList_updateList(adminList_updateList__initial);
            
            locationHashEval('<?=href('/?' . $_SERVER['QUERY_STRING'])?>');
        }
        </script>
        
        <script type="text/javascript">
        var recipientsNum;
        function SMSPrepare() {
            recipientsNum = items_inver ? items_total - items_array.length : items_array.length;
            
            if (items_inver || recipientsNum > 0) {
                iframeDialog('<?=href('void')?>', 500, 250);
                
                document.getElementById('adminList').action = uri;
                document.getElementById('adminList').target = 'Dialog_iframe_iframe';
                document.getElementById('adminList_action').value = 'prepare';
                document.getElementById('adminList_items_inver').value = items_inver;
                document.getElementById('adminList_items_array').value = items_array.join(',');
                document.getElementById('adminList').submit();
            } else {
                alert('Please select at least one recipient.');
            }
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    
    <form method="POST" id="adminList">
    <input type="hidden" id="adminList_action" name="act">
    <input type="hidden" id="adminList_items_inver" name="adminList_items_inver">
    <input type="hidden" id="adminList_items_array" name="adminList_items_array">
    <table>
        <thead>
            <tr class="path">
                <td>
                    <a href="<?=href('/')?>">Main</a>
                    / <a href="<?=href('/?doc=admin')?>">Administration</a>
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Send SMS</a>
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
                    Memberzone:
                    &nbsp;
                    <select id="filter-memberzone" name="memberzone" style="width: 350px;" onchange="if (this.value != '0' && this.value != '<?=$_GET['memberzone']?>') window.location = uri.replace(/&(memberzone|page)=[^&]+/g, '') + (this.value ? '&memberzone=' + this.value : '')">
                    <option value="">&nbsp;</option>
                    <?
                    $sql_query = mysql_query("SELECT memberzone_id, title FROM type_memberzone ORDER BY memberzone_id ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $selected = ($sql['memberzone_id'] == $_GET['memberzone']) ? 'selected' : false;
                        echo '<option value="' . $sql['memberzone_id'] . '" ' . $selected . '>' . $sql['title'] . '</option>' . "\n";
                    }
                    ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Send SMS" class="button" style="width: 100px;" onclick="SMSPrepare(); this.blur();">
                    <? if (constant('SMS_USERNAME') && constant('SMS_PASSWORD')): ?>
                    <input type="button" value="View Log" class="button" style="width: 100px;" onclick="iframeDialog('<?=href('/?doc=' . constant('DOC') . '&act=view-log')?>', 300, 175); this.blur();">
                    <? endif; ?>
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
                    <input type="button" value="Send SMS" class="button" style="width: 100px;" onclick="SMSPrepare(); this.blur();">
                    <? if (constant('SMS_USERNAME') && constant('SMS_PASSWORD')): ?>
                    <input type="button" value="View Log" class="button" style="width: 100px;" onclick="iframeDialog('<?=href('/?doc=' . constant('DOC') . '&act=view-log')?>'); this.blur();">
                    <? endif; ?>
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
        echo ' id="' . $item['user_id'] . '"';
        echo ' name="' . xmlentities($item['name']) . '"';
        echo ' email="' . xmlentities($item['email']) . '"';
        echo ' mobile="' . xmlentities($item['mobile']) . '"';
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
        case 'id': $ORDERBY = "ORDER BY user_id $sort"; break;
        case 'name': $ORDERBY = "ORDER BY name $sort"; break;
        case 'email': $ORDERBY = "ORDER BY email $sort"; break;
        case 'mobile': $ORDERBY = "ORDER BY mobile $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_note', 'user_id', 'name', 'email', 'mobile');
        
        if ($keywords = array_unique(explode(' ', $_GET['query']))) {
            foreach ($keywords as $keyword) {
                $HAVING_tmp = false;
                foreach ($searchArray as $searchVariable) {
                    $HAVING_tmp[] = $searchVariable . " LIKE '%" . $keyword . "%'";
                }
                $HAVING[] = "(" . implode(" OR ", $HAVING_tmp) . ")";
            }
            $HAVING = "HAVING " . implode(" AND ", $HAVING);
        }
    }
    
    if ($_GET['memberzone']) {
        $sql_query = mysql_query("SELECT sd.doc_note, sd.doc_active, tu.user_id, TRIM(CONCAT(tu.first_name, ' ', tu.last_name)) AS name, tu.email, tu.mobile FROM sys_docs sd JOIN type_user tu ON doc_id = user_id, type_memberzone__users tm_u WHERE sd.doc_active = '1' AND tm_u.memberzone_id = '" . $_GET['memberzone'] . "' AND tm_u.user_id = tu.user_id AND mobile IS NOT NULL AND tu.send_notifications = '1' $HAVING $ORDERBY");
    } else {
        $sql_query = mysql_query("SELECT sd.doc_note, sd.doc_active, user_id, TRIM(CONCAT(first_name, ' ', last_name)) AS name, email, mobile FROM sys_docs sd JOIN type_user t ON doc_id = user_id WHERE sd.doc_active = '1' AND mobile IS NOT NULL AND send_notifications = '1' $HAVING $ORDERBY");
    }
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>