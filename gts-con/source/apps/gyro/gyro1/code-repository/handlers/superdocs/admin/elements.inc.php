<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = '" . constant('DOC') . "'"), 0)) {
    error_forbidden();
}

if (!$Subtype = mysql_fetch_assoc(mysql_query("SELECT doc_subtype, title, title_singular FROM sys_docs_subtypes WHERE doc_type = 'element' AND doc_subtype = '" . $_GET['subtype'] . "'"))) {
    header('Location: ' . href('/?doc=admin'));
    exit;
}

if ($_GET['ajax']) {
    if ($_GET['act'] == 'update-note' && $_GET['doc_id']) {
        mysql_query("UPDATE sys_docs SET doc_note = " . SQLNULL(mysql_real_escape_string(stripslashes(trim($_GET['note'])))) . " WHERE doc_type = 'element' AND doc_subtype = '" . $Subtype['doc_subtype'] . "' AND doc_id = '" . mysql_real_escape_string($_GET['doc_id']) . "'");
    }
    
    echo renderListAJAX($Subtype);
    exit;
}

if ($_GET['element']) {
    if (!$elementId = validateElementId($Subtype, $_GET['element'])) {
        header('Location: ' . href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype']));
        exit;
    }
}

if ($_POST['act'] == 'delete' || $_POST['act'] == 'unassociate') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs($Subtype);
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['element_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    if ($_POST['act'] == 'delete') {
        for ($i = 0; $i != count($items); $i++) {
            if (!deleteElement($Subtype, $items[$i], $deleteElement__error)) {
                abort($deleteElement__error, 'deleteElement', __FILE__, __LINE__);
            }
        }
    } elseif ($_POST['act'] == 'unassociate') {
        for ($i = 0; $i != count($items); $i++) {
            mysql_query("DELETE FROM type_group__associated_docs WHERE group_id = '" . $_GET['group'] . "' AND associated_doc_id = '" . $items[$i] . "'");
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateElementData($Subtype, $_POST['save-as-new'] ? false : $elementId, $_POST, false, $validateElementData__errors)) {
            if ($_POST['save-as-new']) {
                $elementId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveElement($Subtype, $elementId, $_POST, false, $saveElement_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])));
                exit;
            } else {
                abort($saveElement_error, 'saveElement', __FILE__, __LINE__);
            }
        } else {
            echo renderElementForm($Subtype, $elementId, $_POST, $validateElementData__errors);
        }
    } else {
        if ($elementId) {
            $_FORM = getElementData($Subtype, $elementId, false);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderElementForm($Subtype, $elementId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            $_FORM['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_defaults WHERE doc_type = 'element'"), 0);
            echo renderElementForm($Subtype, false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $elementId) {
    if (!deleteElement($Subtype, $elementId, $deleteElement__error)) {
        abort($deleteElement__error, 'deleteElement', __FILE__, __LINE__);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])));
    exit;
} else {
    if ($_GET['group'] && $_POST['idx']) {
        asort($_POST['idx']);
        for ($i = 1; list($docId) = each($_POST['idx']); $i++) {
            mysql_query("UPDATE type_group__associated_docs SET idx = '$i' WHERE group_id = '$_GET[group]' AND associated_doc_id = '$docId'");
        }
        header('Location: ' . href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'] . '&group=' . $_GET['group']));
        exit;
    }
    
    echo renderElementsList($Subtype);
}


// Functions.

function validateElementId($Subtype, $elementId) {
    return @mysql_result(mysql_query("SELECT doc_id AS element_id FROM sys_docs LEFT JOIN type_element ON doc_id = element_id WHERE doc_type = 'element' AND doc_subtype = '" . $Subtype['doc_subtype'] . "' AND doc_id = '$elementId'"), 0);
}

function getElementData($Subtype, $elementId, $externalTransaction) {
    $element = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id AS element_id, sd.doc_name, sd.doc_https, sd.doc_note, sd.doc_active,
            ta.title
        FROM sys_docs sd JOIN type_element ta ON doc_id = element_id
        WHERE element_id = '$elementId'
    "));
    
    if ($element['doc_https'] == '0') {
        $element['doc_https'] = 'http';
    } else if ($element['doc_https'] == '1') {
        $element['doc_https'] = 'https';
    } else {
        $element['doc_https'] = 'http-https';
    }
    
    #
    
    $sql_query = mysql_query("SELECT group_id FROM type_group__associated_docs WHERE associated_doc_id = '$elementId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $element['groups'][] = $sql['group_id'];
    }
    
    #
    
    $element['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$elementId'"), 0);
    
    #
    
    $element['parameters'] = getDocParameters($elementId);
    
    return $element;
}

function validateElementData($Subtype, $elementId, &$POST, $externalTransaction, &$errors) {
    if ($POST['doc_name']) {
        $POST['doc_name'] = strtolower($POST['doc_name']);
        
        $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'element' AND doc_name_default_prefix IS NOT NULL"), 0);
        
        if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $POST['doc_name'], $match)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (must begin with <u>' . $docNameDefaultPrefix . '</u>).';
        } elseif (!is_valid_doc_name($POST['doc_name'], $docNameDefaultPrefix)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (contains illegal characters).';
        } elseif (@mysql_result(mysql_query("SELECT doc_name FROM sys_docs WHERE doc_name = '$POST[doc_name]' AND doc_id != '$elementId'"), 0)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (already in use).';
        }
    }
    
    if (!$POST['title']) {
        $errors['title'] = 'Invalid <u>Title</u>.';
    }
    
    if ($externalTransaction && $POST['fset_id']) {
        if (!@mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets WHERE fset_id = '$POST[fset]'"), 0)) {
            $errors['fset_id'] = 'Invalid <u>Review Feature Set</u>.';
        }
    }
    
    if (!$POST['doc_https']) {
        $errors['doc_https'] = 'Invalid <u>Doc HTTPS</u> (not selected).';
    }
    
    if ($externalTransaction && $POST) {
        foreach ($POST as $var => $val) {
            if (substr($var, 0, 12) == 'parameters__') {
                if (preg_match('/^(.+)__(.+)$/U', substr($var, 12), $match)) {
                    $POST['parameters'][$match[1]][$match[2]] = $val;
                } else {
                    $POST['parameters'][substr($var, 12)] = $val;
                }
                unset($POST[$var]);
            }
        }
    }
    validateDocParameters('element', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveElement($Subtype, $elementId, $POST, $externalTransaction, &$error) {
    $sql_errors = false;
    
    if (!$externalTransaction) {
        mysql_query("BEGIN");
    }
    
    ##
    
    $docName = ($POST['doc_name'] == '') ? "NULL" : "'" . $POST['doc_name'] . "'";
    $docNote = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    $docHttps = ($POST['doc_https'] == 'http') ? "'0'" : ($POST['doc_https'] == 'https' ? "'1'" : "NULL");
    mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_subtype, doc_https, doc_note) VALUES ('$elementId', $docName, 'element', '" . $Subtype['doc_subtype'] . "', $docHttps, $docNote)") || $sql_errors[] = mysql_error();
    
    $elementId = mysql_insert_id();
    
    mysql_query("REPLACE type_element (element_id, title) VALUES ('$elementId', '$POST[title]')") || $sql_errors[] = mysql_error();
    
    #
    
    $sql_query = mysql_query("SELECT group_id, idx FROM type_group__associated_docs WHERE associated_doc_id = '$elementId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $groupsIdx[$sql['group_id']] = $sql['idx'];
    }
    mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '$elementId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['groups']); $i++) {
        if ($POST['groups'][$i]) {
            $idx = $groupsIdx[$POST['groups'][$i]];
            mysql_query("INSERT INTO type_group__associated_docs (group_id, associated_doc_id, idx) VALUES ('" . $POST['groups'][$i] . "', '$elementId', '" . $idx . "')") || $sql_errors[] = mysql_error();
        }
    }
    
    #
    
    mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$elementId'");
    if ($POST['fset_id']) {
        mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('$POST[fset_id]', '$elementId')");
    }
    
    #
    
    saveDocParameters('element', $elementId, $POST['parameters'], $sql_errors);
    
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

function deleteElement($Subtype, $elementId, &$error) {
    if (validateElementId($Subtype, $elementId)) {
        mysql_query("DELETE FROM sys_docs WHERE doc_type = 'element' AND doc_subtype = '" . $Subtype['doc_subtype'] . "' AND doc_id = '$elementId'");
        mysql_query("DELETE FROM type_element WHERE element_id = '$elementId'");
        #
        mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '$elementId'");
        #
        mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$elementId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$elementId'");
        
        return true;
    } else {
        $error = 'Invalid element.';
        return false;
    }
}

function renderElementForm($Subtype, $elementId, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'element' AND doc_name_default_prefix IS NOT NULL"), 0);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / <?=$Subtype['title']?></title>
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
        / <a href="<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>"><?=$Subtype['title']?></a>
        / <?=($elementId ? 'Id: ' . $elementId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__elements_general">
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
                <td class="col a"><div class="caption">associated groups</div></td>
                <td class="col b">
                    <select name="groups[]" size="10" multiple class="select" style="width: 350px; font-family: Courier New; font-size: 12px;">
                    <?
                    $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        echo '<option value="">[ ' . $sql['title'] . ' ]</option>' . "\n";
                        
                        $groups = getGroups_byType($sql['doc_subtype']);
                        for ($i = 0; $group = $groups[$i]; $i++) {
                            $selected = (@in_array($group['group_id'], $_FORM['groups'])) ? 'selected' : false;
                            $margin = str_repeat('&nbsp;', ($group['level'] + 1) * 3);
                            
                            if (strlen($group['title']) > 43) {
                                $group['title'] = substr($group['title'], 0, 42) . '…';
                            }
                            echo '<option value="' . $group['group_id'] . '" ' . $selected . '>' . $margin . $group['title'] . '</option>' . "\n";
                        }
                    }
                    ?>
                    </select>
                </td>
                <td class="col c"><div class="annotation">Hold the <span style="font-variant: small-caps;">ctrl</span> key while clicking to select multiple groups.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">review feature set</div></td>
                <td class="col b">
                    <select name="fset_id" style="width: 350px;">
                        <option value="">&nbsp;</option>
                        <?php
                        $sql_query = mysql_query("SELECT fset_id, name FROM type_review__fsets ORDER BY name ASC");
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            $selected = ($sql['fset_id'] == $_FORM['fset_id']) ? 'selected' : false;
                            echo '<option value="' . $sql['fset_id'] . '" ' . $selected . '>' . $sql['name'] . '</option>' . "\n";
                        }
                        ?>
                    </select>
                </td>
                <td class="col c"><div class="annotation">The review feature set to be used when posting reviews associated with this doc.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">doc https</div></td>
                <td class="col b">
                    <? $_FORM['doc_https'] = array($_FORM['doc_https'] => 'checked'); ?>
                    <input type="radio" name="doc_https" class="radio" value="http" <?=$_FORM['doc_https']['http']?>> HTTP
                    <br><input type="radio" name="doc_https" class="radio" value="https" <?=$_FORM['doc_https']['https']?>> HTTPS
                    <br><input type="radio" name="doc_https" class="radio" value="http-https" <?=$_FORM['doc_https']['http-https']?>> HTTP & HTTPS
                </td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">doc note</div></td>
                <td class="col b"><textarea name="doc_note" class="textarea"><?=htmlspecialchars($_FORM['doc_note'])?></textarea></td>
                <td class="col c"><div class="annotation">Accessible only within the admin, and should be used to attach a temporary or permanent note to the doc.</div></td>
            </tr>
        </table>
    </div>
    
    <?php
    $param_sections[] = array('section_id' => '', 'title' => 'General');
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'element' AND section_id IN (SELECT DISTINCT section_id FROM sys_docs_types_params__subtypes LEFT JOIN sys_docs_types_params USING (doc_type, param_name) WHERE doc_type = 'element' AND doc_subtype = '" . $Subtype['doc_subtype'] . "' AND section_id IS NOT NULL)");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('element');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('elements', $param_section, $_FORM);
    }
    ?>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($elementId): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>&element=<?=$elementId?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
        <? endif; ?>
    </div>
    
    </form>
    </div>
    
    <script type="text/javascript">
    var errors = new Array('<?=@implode("','", array_keys($errors))?>');
    highlightErrors(errors);
    </script>
    
    </body>
    </html>
    <?
    $stdout = ob_get_contents();
    ob_end_clean();
    
    return $stdout;
}

function renderElementsList($Subtype) {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / <?=$Subtype['title']?></title>
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
            <? if ($_GET['group'] > 0): ?>
            Array('idx', 'Idx'),
            <? endif; ?>
            Array('title', 'Title'),
            Array('note', 'Note')
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
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>&element=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('id') + '</a></td>'
                              <? if ($_GET['group'] > 0): ?>
                        +     '<td><input type="text" name="idx[' + items[i].getAttribute('id') + ']" value="' + items[i].getAttribute('idx') + '" class="idx"></td>'
                              <? endif; ?>
                        +     '<td style="width: 100%;">' + items[i].getAttribute('title') + '</td>'
                        +     '<td onclick="updateDocNote(\'' + items[i].getAttribute('id') + '\')" style="width: 3ex; text-align: center; cursor: pointer;">' + (items[i].getAttribute('note') ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' + items[i].getAttribute('note').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/\]/g, ']]').replace(/\[/g, '[[').replace(/\r?\n/g, '<br>')  + ']"">' : '&hellip;') + '</td>'
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
                    / <a href="<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>"><?=$Subtype['title']?></a>
                </td>
            </tr>
            
            <tr>
                <td>
                    Filter by Group:
                    &nbsp;
                    <select id="filter-groups" class="groups" onchange="if (this.value != '0' && this.value != '<?=$_GET['group']?>') window.location = uri.replace(/&(orderby|sort|group|page)=[^&]+/g, '') + (this.value ? '&group=' + this.value : '')">
                        <option value="">&nbsp;</option>
                        <option value="-1" <?=($_GET['group'] == '-1' ? 'selected' : false)?>>&lt; Unassociated &gt;</option>
                        <?
                        $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            echo '<option value="0" style="color: #999999;">[ ' . $sql['title'] . ' ]</option>' . "\n";
                            
                            $groups = getGroups_byType($sql['doc_subtype']);
                            for ($i = 0; $group = $groups[$i]; $i++) {
                                $elementsNum = @mysql_result(mysql_query("SELECT COUNT(*) FROM type_group__associated_docs tg_ad, sys_docs sd WHERE tg_ad.group_id = '$group[group_id]' AND tg_ad.associated_doc_id = sd.doc_id AND sd.doc_type = 'element' AND sd.doc_subtype = '" . $Subtype['doc_subtype'] . "'"), 0);
                                
                                $selected = ($group['group_id'] == $_GET['group']) ? 'selected' : false;
                                $margin = str_repeat('&nbsp;', ($group['level'] + 1) * 3);
                                
                                if ($elementsNum) {
                                    echo '<option value="' . $group['group_id'] . '" ' . $selected . '>' . $margin . $group['title'] . ' [' . $elementsNum . ']' . '</option>' . "\n";
                                } else {
                                    echo '<option value="0" style="color: #999999;" ' . $selected . '>' . $margin . $group['title'] . '</option>' . "\n";
                                }
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <? if ($_GET['group'] > 0): ?>
                    <input type="button" value="Save" accesskey="s" class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'save-idx'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); this.blur();">
                    <input type="button" value="Unassociate" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to unassociate all selected items from the group?')) { document.getElementById('adminList_action').value = 'unassociate'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <? endif; ?>
                    <input type="button" value="Add <?=$Subtype['title_singular']?>" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>&act=edit'; this.blur();">
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
                    <? if ($_GET['group'] > 0): ?>
                    <input type="button" value="Save" accesskey="s" class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'save-idx'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); this.blur();">
                    <input type="button" value="Unassociate" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to unassociate all selected items from the group?')) { document.getElementById('adminList_action').value = 'unassociate'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <? endif; ?>
                    <input type="button" value="Add <?=$Subtype['title_singular']?>" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC') . '&subtype=' . $Subtype['doc_subtype'])?>&act=edit'; this.blur();">
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

function renderListAJAX($Subtype) {
    header('Content-Type: text/xml; charset=UTF-8');
    
    list($list, $order, $sort) = renderListDocs($Subtype);
    
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
        echo ' id="' . $item['element_id'] . '"';
        if ($_GET['group'] > 0) {
            echo ' idx="' . xmlentities($item['idx']) . '"';
        }
        echo ' title="' . xmlentities($item['title']) . '"';
        echo ' note="' . xmlentities($item['doc_note']) . '"';
        echo '/>' . "\n";
    }
    echo '</XMLResult>' . "\n";
    
    return ob_get_clean();
}

function renderListDocs($Subtype) {
    if ($_GET['orderby']) {
        $order = $_GET['orderby'];
        $sort = ($_GET['sort'] == 'desc') ? 'DESC' : 'ASC';
    } elseif ($_GET['group'] > 0 && !$_GET['orderby']) {
        $order = 'idx';
        $sort = 'ASC';
    } else {
        $order = 'id';
        $sort = 'DESC';
    }
    
    switch ($order) {
        case 'id': $ORDERBY = "ORDER BY doc_id $sort"; break;
        case 'idx': ($_GET['group'] > 0 ? $ORDERBY = "ORDER BY idx $sort" : false); break;
        case 'title': $ORDERBY = "ORDER BY title $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_name', 'doc_note', 'element_id', 'title');
        
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
    
    if ($_GET['group'] == -1) {
        $sql_query = mysql_query("SELECT sd.doc_name, sd.doc_note, sd.doc_active, ta.element_id, ta.title FROM sys_docs sd JOIN type_element ta ON doc_id = element_id WHERE sd.doc_subtype = '" . $Subtype['doc_subtype'] . "' AND ta.element_id NOT IN (SELECT associated_doc_id FROM type_group__associated_docs) $HAVING $ORDERBY");
    } elseif ($_GET['group'] > 0) {
        $sql_query = mysql_query("SELECT sd.doc_name, sd.doc_note, sd.doc_active, ta.element_id, ta.title, tg_ad.idx FROM sys_docs sd JOIN type_element ta ON doc_id = ta.element_id, type_group__associated_docs tg_ad WHERE sd.doc_subtype = '" . $Subtype['doc_subtype'] . "' AND tg_ad.group_id = '" . $_GET['group'] . "' AND ta.element_id = tg_ad.associated_doc_id $HAVING $ORDERBY");
    } else {
        $sql_query = mysql_query("SELECT sd.doc_name, sd.doc_note, sd.doc_active, ta.element_id, ta.title FROM sys_docs sd JOIN type_element ta ON doc_id = element_id WHERE sd.doc_subtype = '" . $Subtype['doc_subtype'] . "' $HAVING $ORDERBY");
    }
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>