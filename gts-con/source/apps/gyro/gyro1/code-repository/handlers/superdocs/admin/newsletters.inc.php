<?php
if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = '" . constant('DOC') . "'"), 0)) {
    error_forbidden();
}

if ($_GET['ajax']) {
    if ($_GET['act'] == 'update-note' && $_GET['doc_id']) {
        mysql_query("UPDATE sys_docs SET doc_note = " . SQLNULL(mysql_real_escape_string(stripslashes(trim($_GET['note'])))) . " WHERE doc_id = '" . mysql_real_escape_string($_GET['doc_id']) . "' AND doc_type = 'newsletter'");
    }
    
    echo renderListAJAX();
    exit;
}

if ($_GET['newsletter']) {
    if (!$newsletterId = validateNewsletterId($_GET['newsletter'])) {
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
}

if ($_POST['act'] == 'delete') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs();
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['newsletter_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    for ($i = 0; $i != count($items); $i++) {
        if (!deleteNewsletter($items[$i], $deleteNewsletter__error)) {
            abort($deleteNewsletter__error, 'deleteNewsletter', __FILE__, __LINE__);
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        // If this newsletter has already been sent, save it as new.
        if ($newsletterId && @mysql_result(mysql_query("SELECT 1 FROM type_newsletter WHERE newsletter_id = '$newsletterId' AND sent IS NOT NULL"), 0)) {
            $_POST['save-as-new'] = true;
        }
        
        if (validateNewsletterData($_POST['save-as-new'] ? false : $newsletterId, $_POST, $validateNewsletterData__errors)) {
            if ($_POST['save-as-new']) {
                $newsletterId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveNewsletter($newsletterId, $_POST, $saveNewsletter_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveNewsletter_error, 'saveNewsletter', __FILE__, __LINE__);
            }
        } else {
            echo renderNewsletterForm($newsletterId, $_POST, $validateNewsletterData__errors);
        }
    } else {
        if ($newsletterId) {
            $_FORM = getNewsletterData($newsletterId);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderNewsletterForm($newsletterId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderNewsletterForm(false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $newsletterId) {
    if (!deleteNewsletter($newsletterId, $deleteNewsletter__error)) {
        abort($deleteNewsletter__error, 'deleteNewsletter', __FILE__, __LINE__);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} else {
    echo renderNewslettersList();
}


// Functions.

function validateNewsletterId($newsletterId) {
    return @mysql_result(mysql_query("SELECT newsletter_id FROM type_newsletter WHERE newsletter_id = '$newsletterId'"), 0);
}

function getNewsletterData($newsletterId) {
    $newsletter = mysql_fetch_assoc(mysql_query("SELECT sys_docs.doc_name, sys_docs.doc_note, sys_docs.doc_active, type_newsletter.* FROM sys_docs LEFT JOIN type_newsletter ON doc_id = newsletter_id WHERE newsletter_id = '$newsletterId'"));
    
    #
    
    $newsletter['parameters'] = getDocParameters($newsletterId);
    
    return $newsletter;
}

function validateNewsletterData($newsletterId, &$POST, &$errors) {
    if ($POST['doc_name']) {
        $POST['doc_name'] = strtolower($POST['doc_name']);
        
        $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'newsletter' AND doc_name_default_prefix IS NOT NULL"), 0);
        
        if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $POST['doc_name'], $match)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (must begin with <u>' . $docNameDefaultPrefix . '</u>).';
        } elseif (!is_valid_doc_name($POST['doc_name'], $docNameDefaultPrefix)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (contains illegal characters).';
        } elseif (@mysql_result(mysql_query("SELECT doc_name FROM sys_docs WHERE doc_name = '$POST[doc_name]' AND doc_id != '$newsletterId'"), 0)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (already in use).';
        }
    }
    
    if (!$POST['title']) {
        $errors['title'] = 'Invalid <u>Title</u>.';
    }
    
    if (!$POST['subject']) {
        $errors['subject'] = 'Invalid <u>Subject</u>.';
    }
    
    validateDocParameters('newsletter', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveNewsletter($newsletterId, $POST, &$error) {
    $sql_errors = false;
    mysql_query("BEGIN");
    
    ##
    
    $docName = ($POST['doc_name'] == '') ? "NULL" : "'" . $POST['doc_name'] . "'";
    $docNote = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_note) VALUES ('$newsletterId', $docName, 'newsletter', $docNote)") || $sql_errors[] = mysql_error();
    $newsletterId = mysql_insert_id();
    
    mysql_query("REPLACE type_newsletter (newsletter_id, title, content, subject, seo_title, seo_description, seo_keywords) VALUES ('$newsletterId', '$POST[title]', '$POST[content]', '$POST[subject]', '$POST[seo_title]', '$POST[seo_description]', '$POST[seo_keywords]')") || $sql_errors[] = mysql_error();
    
    #
    
    saveDocParameters('newsletter', $newsletterId, $POST['parameters'], $sql_errors);
    
    ##
    
    if ($sql_errors) {
        $error = 'SQL errors:<br>' . implode(',<br>', $sql_errors);
        mysql_query("ROLLBACK");
        
        return false;
    } else {
        mysql_query("COMMIT");
        
        return true;
    }
}

function deleteNewsletter($newsletterId, &$error) {
    if (validateNewsletterId($newsletterId)) {
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '$newsletterId'");
        mysql_query("DELETE FROM type_newsletter WHERE newsletter_id = '$newsletterId'");
        mysql_query("DELETE FROM type_newsletter__recipients WHERE newsletter_id = '$newsletterId'");
        mysql_query("DELETE FROM type_newsletter__links WHERE newsletter_id = '$newsletterId'");
        mysql_query("DELETE FROM type_newsletter__links_recipients WHERE newsletter_id = '$newsletterId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$newsletterId'");
        
        return true;
    } else {
        $error = 'Invalid newsletter.';
        return false;
    }
}

function renderNewsletterForm($newsletterId, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'newsletter' AND doc_name_default_prefix IS NOT NULL"), 0);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Newsletters</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-form.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-form.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
    </head>
    
    <body>
    
    <div align="center">
    <form method="POST" id="adminForm">
    <input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
    <input type="hidden" name="save-as-new" id="save_as_new" value="0">
    
    <div class="location-bar">
        <span id="collapse-all-icon" onclick="toggleSectionDisplay_all()">[-]</span>
        <a href="<?=href('/')?>">Main</a>
        / <a href="<?=href('/?doc=admin')?>">Administration</a>
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Newsletters</a>
        / <?=($newsletterId ? 'Id: ' . $newsletterId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__newsletters_general">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">general</div>
        </div>
        
        <table class="section">
            <tr>
                <td class="col a"><div class="caption">doc name</div></td>
                <td class="col b"><input type="text" name="doc_name" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['doc_name'])?>" onfocus="if (this.value == '') this.value = '<?=$docNameDefaultPrefix?>'" onblur="if (this.value == '<?=$docNameDefaultPrefix?>') this.value = ''" style="width: 350px;"></td>
                <td class="col c"><?php if ($docNameDefaultPrefix) echo '<div class="annotation">Optional. Must begin with <u>' . $docNameDefaultPrefix . '</u>.</div>'; ?></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">title</div></td>
                <td class="col b"><input type="text" name="title" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['title'])?>" style="width: 350px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">subject</div></td>
                <td class="col b"><input type="text" name="subject" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['subject'])?>" style="width: 350px;"></td>
                <td class="col c"><div class="annotation">The email subject of the newsletter.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">content</div></td>
                <td class="col bc" colspan="2"><textarea id="textarea__content" name="content" class="htmlarea"><?=htmlspecialchars($_FORM['content'])?></textarea></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">doc note</div></td>
                <td class="col b"><textarea name="doc_note" class="textarea"><?=htmlspecialchars($_FORM['doc_note'])?></textarea></td>
                <td class="col c"><div class="annotation">Accessible only within the admin, and should be used to attach a temporary or permanent note to the doc.</div></td>
            </tr>
        </table>
    </div>
    
    <div class="section" id="section__newsletters_seo">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">seo</div>
        </div>
        
        <table class="section">
            <tr>
                <td class="col a"><div class="caption">title</div></td>
                <td class="col b"><input type="text" name="seo_title" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['seo_title'])?>" style="width: 350px;"></td>
                <td class="col c" rowspan="3"><div class="annotation">Search Engine Optimization - this information is visible only by search engines, which use it for better indexing. Search engines are smart enough to detect both superfluous and incorrect information.<br><br>Attempts to fraud or manipulate the SEO in order to achieve a better listing often results in a complete ban of the web site.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">description</div></td>
                <td class="col b"><textarea name="seo_description" class="textarea"><?=htmlspecialchars($_FORM['seo_description'])?></textarea></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">keywords</div></td>
                <td class="col b"><input type="text" name="seo_keywords" maxlength="255" class="text" value="<?=htmlspecialchars($_FORM['seo_keywords'])?>" style="width: 350px;"></td>
            </tr>
        </table>
    </div>
    
    <?php
    $param_sections[] = array('section_id' => '', 'title' => 'General');
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'newsletter'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('newsletter');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('newsletters', $param_section, $_FORM);
    }
    ?>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <? if (!$newsletterId || @mysql_result(mysql_query("SELECT 1 FROM type_newsletter WHERE newsletter_id = '$newsletterId' AND sent IS NULL"), 0)): ?>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($newsletterId): ?><span class="separator">|&nbsp;</span><? endif; ?>
        <? endif; ?>
        <? if ($newsletterId): ?>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&newsletter=<?=$newsletterId?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
        <? endif; ?>
    </div>
    
    </form>
    </div>
    
    </body>
    </html>
    <?
    $stdout = ob_get_contents();
    ob_end_clean();
    
    return $stdout;
}

function renderNewslettersList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Newsletters</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/boxover.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/boxover.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        var uri = '<?=href('/?' . $_SERVER['QUERY_STRING'])?>' + window.location.hash.replace(/^#/, '&');
        var docNotes = new Array();
        
        var items_metad = new Array(
            Array(null, '&nbsp;'),
            Array('id', 'Id'),
            Array('title', 'Title'),
            Array('sent', 'Sent'),
            Array('recipients', 'Recipients'),
            Array('opened', 'Opened', 'Recipients that opened the newsletter'),
            Array('note', 'Note'),
            Array(null, '&nbsp;')
        );
        
        /* ## */
        
        function adminList_updateList__central(XMLDocument) {
            var items = XMLDocument.getElementsByTagName('item');
            var list = new Array();
            
            docNotes.splice(0, docNotes.length);
            
            for (var i = 0, checked; i != items.length; i++) {
                checked = ((!items_inver && inArray(items[i].getAttribute('id'), items_array)) || (items_inver && !inArray(items[i].getAttribute('id'), items_array))) ? 'checked' : '';
                
                list[i] = '<tr class="list ' + ((list.length == 0) ? 'first' : '') + '">'
                        +     '<td class="first check"><input type="checkbox" name="items[]" value="' + items[i].getAttribute('id') + '" onclick="adminList_selectItem(this)" ' + checked + ' style="margin: 0;"></td>'
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&newsletter=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('id') + '</a></td>'
                        +     '<td style="width: 100%;">' + items[i].getAttribute('title') + '</td>'
                        +     '<td style="white-space: nowrap;">' + items[i].getAttribute('sent') + '</td>'
                        +     '<td style="text-align: center;">' + (items[i].getAttribute('recipients') > 0 ? items[i].getAttribute('recipients') : '') + '</td>'
                        +     '<td style="text-align: center;">' + (items[i].getAttribute('recipients') > 0 ? items[i].getAttribute('opened') : '') + '</td>'
                        +     '<td onclick="updateDocNote(\'' + items[i].getAttribute('id') + '\')" style="width: 3ex; text-align: center; cursor: pointer;">' + (items[i].getAttribute('note') ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' + items[i].getAttribute('note').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/\]/g, ']]').replace(/\[/g, '[[').replace(/\r?\n/g, '<br>')  + ']"">' : '&hellip;') + '</td>'
                        +     '<td>' + (items[i].getAttribute('sent') ? '<a href="<?=href('/?doc=' . constant('DOC'))?>/log&newsletter=' + items[i].getAttribute('id') + '" onclick="iframeDialog(this.href); return false;">Log</a>' : '&nbsp;') + '</td>'
                        + '</tr>';
                
                if (items[i].getAttribute('note')) {
                    docNotes[items[i].getAttribute('id')] = items[i].getAttribute('note');
                }
            }
            
            document.getElementById('adminList_list').innerHTML = '<table>' + items_thead + list.join("\n") + '</table>';
        }
        
        window.onload = function () {
            adminList_updateList(adminList_updateList__initial);
            
            locationHashEval('<?=href('/?' . $_SERVER['QUERY_STRING'])?>');
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
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Newsletters</a>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Add Newsletter" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
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
                    <input type="button" value="Add Newsletter" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
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
        echo ' id="' . $item['newsletter_id'] . '"';
        echo ' title="' . xmlentities($item['title']) . '"';
        echo ' sent="' . xmlentities($item['sent']) . '"';
        echo ' recipients="' . xmlentities($item['recipients']) . '"';
        echo ' opened="' . xmlentities($item['opened']) . '"';
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
        case 'id': $ORDERBY = "ORDER BY doc_id $sort"; break;
        case 'title': $ORDERBY = "ORDER BY title $sort"; break;
        case 'sent': $ORDERBY = "ORDER BY sent $sort"; break;
        case 'recipients': $ORDERBY = "ORDER BY recipients $sort"; break;
        case 'opened': $ORDERBY = "ORDER BY opened $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_note', 'newsletter_id', 'title', 'sent');
        
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
    
    $sql_query = mysql_query("SELECT sd.doc_note, sd.doc_active, tn.newsletter_id, tn.title, tn.sent, (SELECT COUNT(*) FROM type_newsletter__recipients WHERE newsletter_id = tn.newsletter_id) AS recipients, (SELECT COUNT(*) FROM type_newsletter__recipients WHERE newsletter_id = tn.newsletter_id AND opened IS NOT NULL) AS opened FROM sys_docs sd JOIN type_newsletter tn ON doc_id = newsletter_id $HAVING $ORDERBY");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>