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
        mysql_query("UPDATE sys_docs SET doc_note = " . SQLNULL(mysql_real_escape_string(stripslashes(trim($_GET['note'])))) . " WHERE doc_id = '" . mysql_real_escape_string($_GET['doc_id']) . "' AND doc_type = 'memberzone'");
    }
    
    echo renderListAJAX();
    exit;
}

if ($_GET['memberzone']) {
    if (!$memberzoneId = validateMemberzoneId($_GET['memberzone'])) {
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
            $itemsAll[] = $list[$i]['memberzone_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    for ($i = 0; $i != count($items); $i++) {
        if (!deleteMemberzone($items[$i], $deleteMemberzone__error)) {
            abort($deleteMemberzone__error, 'deleteMemberzone', __FILE__, __LINE__);
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateMemberzoneData($_POST['save-as-new'] ? false : $memberzoneId, $_POST, $validateMemberzoneData__errors)) {
            if ($_POST['save-as-new']) {
                $memberzoneId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveMemberzone($memberzoneId, $_POST, $saveMemberzone_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveMemberzone_error, 'saveMemberzone', __FILE__, __LINE__);
            }
        } else {
            echo renderMemberzoneForm($memberzoneId, $_POST, $validateMemberzoneData__errors);
        }
    } else {
        if ($memberzoneId) {
            $_FORM = getMemberzoneData($memberzoneId);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderMemberzoneForm($memberzoneId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderMemberzoneForm(false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $memberzoneId) {
    if (!deleteMemberzone($memberzoneId, $deleteMemberzone__error)) {
        abort($deleteMemberzone__error, 'deleteMemberzone', __FILE__, __LINE__);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} else {
    echo renderMemberzonesList();
}


// Functions.

function validateMemberzoneId($memberzoneId) {
    return @mysql_result(mysql_query("SELECT memberzone_id FROM type_memberzone WHERE memberzone_id = '$memberzoneId'"), 0);
}

function getMemberzoneData($memberzoneId) {
    $memberzone = mysql_fetch_assoc(mysql_query("SELECT sys_docs.doc_name, sys_docs.doc_note, sys_docs.doc_active, type_memberzone.* FROM sys_docs LEFT JOIN type_memberzone ON doc_id = memberzone_id WHERE memberzone_id = '$memberzoneId'"));
    
    #
    
    $memberzone['parameters'] = getDocParameters($memberzoneId);
    
    return $memberzone;
}

function validateMemberzoneData($memberzoneId, &$POST, &$errors) {
    if (!$POST['title']) {
        $errors['title'] = 'Invalid <u>Title</u>.';
    }
    
    if ($POST['group_id']) {
        // Check that the selected group is not already associate with another memberzone, or the descendants of another memberzone.
        $sql_query = mysql_query("SELECT group_id FROM type_memberzone WHERE memberzone_id != '$memberzoneId' AND group_id IS NOT NULL");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            if ($POST['group_id'] == $sql['group_id']) {
                $errors['group_id'] = 'Invalid <u>Group</u> (already part of another memberzone).';
            } elseif ($groups = getGroups_byParent($sql['group_id'])) {
                foreach ($groups as $group) {
                    if ($POST['group_id'] == $group['group_id']) {
                        $errors['group_id'] = 'Invalid <u>Group</u> (already part of another memberzone).';
                        break 2;
                    }
                }
            }
        }
        
        // Check that the selected group does not contain a group that is associate with another memberzone.
        if ($groups = getGroups_byParent($POST['group_id'])) {
            foreach ($groups as $group) {
                if (@mysql_result(mysql_query("SELECT group_id FROM type_memberzone WHERE group_id = '$group[group_id]' AND memberzone_id != '$memberzoneId'"), 0)) {
                    $errors['group_id'] = 'Invalid <u>Group</u> (already part of another memberzone).';
                    break;
                }
            }
        }
    }
    
    validateDocParameters('memberzone', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveMemberzone($memberzoneId, $POST, &$error) {
    $sql_errors = false;
    mysql_query("BEGIN");
    
    ##
    
    $doc_note = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    mysql_query("REPLACE sys_docs (doc_id, doc_type, doc_note) VALUES ('$memberzoneId', 'memberzone', $doc_note)") || $sql_errors[] = mysql_error();
    $memberzoneId = mysql_insert_id();
    
    $group_id = ($POST['group_id'] == '') ? "NULL" : "'" . $POST['group_id'] . "'";
    mysql_query("REPLACE type_memberzone (memberzone_id, title, description, group_id) VALUES ('$memberzoneId', '$POST[title]', '$POST[description]', $group_id)") || $sql_errors[] = mysql_error();
    
    #
    
    saveDocParameters('memberzone', $memberzoneId, $POST['parameters'], $sql_errors);
    
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

function deleteMemberzone($memberzoneId, &$error) {
    if (validateMemberzoneId($memberzoneId)) {
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '$memberzoneId'");
        mysql_query("DELETE FROM type_memberzone WHERE memberzone_id = '$memberzoneId'");
        #
        mysql_query("DELETE FROM type_memberzone__users WHERE memberzone_id = '$memberzoneId'");
        #
        mysql_query("DELETE FROM type_memberzone__products WHERE memberzone_id = '$memberzoneId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$memberzoneId'");
        
        return true;
    } else {
        $error = 'Invalid memberzone.';
        return false;
    }
}

function renderMemberzoneForm($memberzoneId, $_FORM = false, $errors = false) {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Memberzones</title>
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
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Memberzones</a>
        / <?=($memberzoneId ? 'Id: ' . $memberzoneId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__memberzones_general">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">general</div>
        </div>
        
        <table class="section">
            <tr>
                <td class="col a"><div class="caption">title</div></td>
                <td class="col b"><input type="text" name="title" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['title'])?>" style="width: 350px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">description</div></td>
                <td class="col bc" colspan="2"><textarea id="textarea__description" name="description" class="htmlarea"><?=htmlspecialchars($_FORM['description'])?></textarea></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">group</div></td>
                <td class="col b">
                    <select name="group_id" size="10" class="select" style="width: 350px; font-family: Courier New; font-size: 12px;">
                    <?
                    $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        echo '<option value="">[ ' . $sql['title'] . ' ]</option>' . "\n";
                        
                        $groups = getGroups_byType($sql['doc_subtype']);
                        for ($i = 0; $group = $groups[$i]; $i++) {
                            $selected = ($group['group_id'] == $_FORM['group_id']) ? 'selected' : false;
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
                <td class="col c"><div class="annotation">All docs associated with the selected group and all its descendants will be available in the memberzone. Changing the selected group will reset all the zone-prices set for products associated with that group.</div></td>
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
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'memberzone'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('memberzone');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('memberzones', $param_section, $_FORM);
    }
    ?>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($memberzoneId): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&memberzone=<?=$memberzoneId?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
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

function renderMemberzonesList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Memberzones</title>
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
            Array('group', 'Group'),
            Array('note', 'Note'),
            Array(null, '&nbsp;'),
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
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&memberzone=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('id') + '</a></td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('title') + '</td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('group') + '</td>'
                        +     '<td onclick="updateDocNote(\'' + items[i].getAttribute('id') + '\')" style="width: 3ex; text-align: center; cursor: pointer;">' + (items[i].getAttribute('note') ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' + items[i].getAttribute('note').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/\]/g, ']]').replace(/\[/g, '[[').replace(/\r?\n/g, '<br>')  + ']"">' : '&hellip;') + '</td>'
                        +     '<td><a href="<?=href('/?doc=' . constant('DOC'))?>/users&memberzone=' + items[i].getAttribute('id') + '" onclick="iframeDialog(this.href); return false;">Users</a></td>'
                        +     '<td><a href="<?=href('/?doc=' . constant('DOC'))?>/products&memberzone=' + items[i].getAttribute('id') + '" onclick="iframeDialog(this.href); return false;">Products</a></td>'
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
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Memberzones</a>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Add Memberz." class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
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
                    <input type="button" value="Add Memberz." class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
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
        echo ' id="' . $item['memberzone_id'] . '"';
        echo ' title="' . xmlentities($item['title']) . '"';
        echo ' group="' . xmlentities($item['group']) . '"';
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
        case 'group': $ORDERBY = "ORDER BY tm.group_id $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_note', 'memberzone_id', 'title', '`group`');
        
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
    
    $sql_query = mysql_query("SELECT sd.doc_note, sd.doc_active, tm.memberzone_id, tm.title, (SELECT title FROM type_group WHERE group_id = tm.group_id) AS `group` FROM sys_docs sd JOIN type_memberzone tm ON doc_id = memberzone_id $HAVING $ORDERBY");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>