<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = '" . constant('DOC') . "'"), 0)) {
    error_forbidden();
}

if ($_GET['group']) {
    if (!$groupId = validateGroupId($_GET['group'])) {
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
}

if ($_GET['action'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateGroupData($_POST['save-as-new'] ? false : $groupId, $_POST, $validateGroupData__errors)) {
            if ($_POST['save-as-new']) {
                $groupId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveGroup($groupId, $_POST, $saveGroup_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveGroup_error, 'saveGroup', __FILE__, __LINE__);
            }
        } else {
            echo renderGroupForm($groupId, $_POST, $validateGroupData__errors);
        }
    } else {
        if ($groupId) {
            $_FORM = getGroupData($groupId);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderGroupForm($groupId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            $_FORM['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_defaults WHERE doc_type = 'group'"), 0);
            echo renderGroupForm(false, $_FORM);
        }
    }
} elseif ($_GET['action'] == 'delete') {
    if ($_GET['groups'] && ($groups = @explode(',', $_GET['groups']))) {
        foreach ($groups as $groupId) {
            if (!deleteGroup($groupId, $_GET['reassign'], $deleteGroup__error)) {
                abort($deleteGroup__error, 'deleteGroup', __FILE__, __LINE__);
            }
        }
    } elseif ($groupId) {
        if (!deleteGroup($groupId, $_GET['reassign'], $deleteGroup__error)) {
            abort($deleteGroup__error, 'deleteGroup', __FILE__, __LINE__);
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} elseif ($_GET['action'] == 'coupon-associate' && $_GET['coupon']) {
    if ($_GET['groups'] && ($groups = @explode(',', $_GET['groups']))) {
        foreach ($groups as $groupId) {
            mysql_query("INSERT INTO type_coupon__groups (coupon_id, group_id) VALUES ('" . $_GET['coupon'] . "', '$groupId')");
        }
    } elseif ($groupId) {
        mysql_query("INSERT INTO type_coupon__groups (coupon_id, group_id) VALUES ('" . $_GET['coupon'] . "', '$groupId')");
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} else {
    if ($_POST['idx']) {
        foreach ($_POST['idx'] as $groupId => $idx) {
            mysql_query("UPDATE type_group SET idx = '$idx' WHERE group_id = '$groupId'");
        }
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
    
    echo renderGroupsList($_GET['subtype']);
}


// Functions.

function validateGroupId($groupId) {
    return @mysql_result(mysql_query("SELECT group_id FROM type_group WHERE group_id = '$groupId'"), 0);
}

function getGroupData($groupId) {
    $group = mysql_fetch_assoc(mysql_query("SELECT sys_docs.doc_name, sys_docs.doc_subtype, sys_docs.doc_https, sys_docs.doc_note, sys_docs.doc_active, type_group.* FROM sys_docs LEFT JOIN type_group ON doc_id = group_id WHERE group_id = '$groupId'"));
    
    if ($group['doc_https'] == '0') {
        $group['doc_https'] = 'http';
    } else if ($group['doc_https'] == '1') {
        $group['doc_https'] = 'https';
    } else {
        $group['doc_https'] = 'http-https';
    }
    
    if (!$group['parent_group_id']) {
        $group['parent_group_id'] = '@' . $group['doc_subtype'];
    }
    
    #
    
    $sql_query = mysql_query("SELECT title, content, image_url, image_align FROM type_group__paragraphs WHERE group_id = '$groupId' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $group['paragraphs'][] = $sql;
    }
    
    #
    
    $sql_query = mysql_query("SELECT coupon_id FROM type_coupon__groups WHERE group_id = '$groupId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $group['associated_coupons'][] = $sql['coupon_id'];
    }
    
    #
    
    $group['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$groupId'"), 0);
    
    #
    
    $group['parameters'] = getDocParameters($groupId);
    
    return $group;
}

function validateGroupData($groupId, &$POST, &$errors) {
    if ($POST['doc_name']) {
        $POST['doc_name'] = strtolower($POST['doc_name']);
        
        $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'group' AND doc_name_default_prefix IS NOT NULL"), 0);
        
        if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $POST['doc_name'], $match)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (must begin with <u>' . $docNameDefaultPrefix . '</u>).';
        } elseif (!is_valid_doc_name($POST['doc_name'], $docNameDefaultPrefix)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (contains illegal characters).';
        } elseif (@mysql_result(mysql_query("SELECT doc_name FROM sys_docs WHERE doc_name = '$POST[doc_name]' AND doc_id != '$groupId'"), 0)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (already in use).';
        }
    }
    
    if (!$POST['title']) {
        $errors['title'] = 'Invalid <u>Title</u>.';
    }
    
    if ($groupId) {
        if ($descendants_temp = getGroups_byParent($groupId)) {
            foreach ($descendants_temp as $descendant) {
                $descendants[] = $descendant['group_id'];
            }
        }
    }
    
    if ($POST['parent_group_id']) {
        if (substr($POST['parent_group_id'], 0, 1) == '@') {
            $POST['doc_subtype'] = substr($POST['parent_group_id'], 1);
            
            if (!@mysql_result(mysql_query("SELECT 1 FROM sys_docs_subtypes WHERE doc_type = 'group' AND doc_subtype = '" . $POST['doc_subtype'] . "'"), 0)) {
                $errors['parent_group_id'] = 'Invalid <u>Parent Group</u> (invalid group subtype).';
            }
        } else {
            $POST['doc_subtype'] = @mysql_result(mysql_query("SELECT doc_subtype FROM sys_docs WHERE doc_id = '$POST[parent_group_id]'"), 0);
            
            if (!validateGroupId($POST['parent_group_id'])) {
                $errors['parent_group_id'] = 'Invalid <u>Parent Group</u> (invalid Item Id - must be a group).';
            } elseif ($POST['parent_group_id'] == $groupId) {
                $errors['parent_group_id'] = 'Invalid <u>Parent Group</u> (canont be a descendant of itself).';
            } elseif ($descendants && @in_array($POST['parent_group_id'], $descendants)) {
                $errors['parent_group_id'] = 'Invalid <u>Parent Group</u> (canont be a descendant of one of its own descendants).';
            }
        }
    } else {
        $errors['parent_group_id'] = 'Invalid <u>Parent Group</u> (not selected).';
    }
    
    if ($POST['docs_per_page'] != '' && !preg_match('/^[1-9]\d*$/', $POST['docs_per_page'])) {
        $errors['docs_per_page'] = 'Invalid <u>Docs per Page</u>.';
    }
    
    if (!$POST['status']) {
        $errors['status'] = 'Invalid <u>Status</u> (not selected).';
    }
    
    if (!$POST['doc_https']) {
        $errors['doc_https'] = 'Invalid <u>Doc HTTPS</u> (not selected).';
    }
    
    $POST['paragraphs'] = getNonEmptyIterations($POST['paragraphs']);
    for ($i = 0; $i != count($POST['paragraphs']); $i++) {
        if ($POST['paragraphs'][$i]['image_url'] && !$POST['paragraphs'][$i]['image_align']) {
            $errors['paragraphs[' . $i . '][image_align]'] = 'Invalid <u>Paragraph</u> (missing image alignment).';
        }
        if ($POST['paragraphs'][$i]['image_align'] && !$POST['paragraphs'][$i]['image_url']) {
            $errors['paragraphs[' . $i . '][image_url]'] = 'Invalid <u>Paragraph</u> (missing image url).';
        }
    }
    
    validateDocParameters('group', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveGroup($groupId, $POST, &$error) {
    $sql_errors = false;
    mysql_query("BEGIN");
    
    ##
    
    $docName = ($POST['doc_name'] == '') ? "NULL" : "'" . $POST['doc_name'] . "'";
    $docNote = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    $docHttps = ($POST['doc_https'] == 'http') ? "'0'" : ($POST['doc_https'] == 'https' ? "'1'" : "NULL");
    mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_subtype, doc_https, doc_note) VALUES ('$groupId', $docName, 'group', '$POST[doc_subtype]', $docHttps, $docNote)") || $sql_errors[] = mysql_error();
    $groupId = mysql_insert_id();
    
    $parent_group_id = (substr($POST['parent_group_id'], 0, 1) == '@') ? "NULL" : "'" . $POST['parent_group_id'] . "'";
    $docs_per_page = ($POST['docs_per_page']) ? "'" . $POST['docs_per_page'] . "'" : "NULL";
    mysql_query("REPLACE type_group (group_id, parent_group_id, title, description, docs_per_page, seo_title, seo_description, seo_keywords, idx, status) VALUES ('$groupId', $parent_group_id, '$POST[title]', '$POST[description]', $docs_per_page, '$POST[seo_title]', '$POST[seo_description]', '$POST[seo_keywords]', '$POST[idx]', '$POST[status]')") || $sql_errors[] = mysql_error();
    
    // Also update the subtype for all descendants.
    if ($groups = getGroups_byParent($groupId)) {
        for ($i = 0; $i != count($groups); $i++) {
            mysql_query("UPDATE sys_docs SET doc_subtype = '$POST[doc_subtype]' WHERE doc_type = 'group' AND doc_id = '" . $groups[$i]['group_id'] . "'") || $sql_errors[] = mysql_error();
        }
    }
    
    #
    
    mysql_query("DELETE FROM type_group__paragraphs WHERE group_id = '$groupId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['paragraphs']); $i++) {
        mysql_query("INSERT INTO type_group__paragraphs (group_id, title, content, image_url, image_align, idx) VALUES ('$groupId', '" . $POST['paragraphs'][$i]['title'] . "', '" . $POST['paragraphs'][$i]['content'] . "', '" . $POST['paragraphs'][$i]['image_url'] . "', '" . $POST['paragraphs'][$i]['image_align'] . "', '$i')") || $sql_errors[] = mysql_error();
    }
    
    #
    
    mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$groupId'");
    if ($POST['fset_id']) {
        mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('$POST[fset_id]', '$groupId')");
    }
    
    #
    
    mysql_query("DELETE FROM type_coupon__groups WHERE group_id = '$groupId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['associated_coupons']); $i++) {
        if ($POST['associated_coupons'][$i]) {
            mysql_query("INSERT INTO type_coupon__groups (coupon_id, group_id) VALUES ('" . $POST['associated_coupons'][$i] . "', '$groupId')") || $sql_errors[] = mysql_error();
        }
    }
    
    #
    
    saveDocParameters('group', $groupId, $POST['parameters'], $sql_errors);
    
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

function deleteGroup($groupId, $reassign, &$error) {
    if (validateGroupId($groupId)) {
        if ($reassign) {
            // Fetch current group's parent/root, and reassign all direct descendants to that parent/root.
            
            if ($parent_group_id = @mysql_result(mysql_query("SELECT parent_group_id FROM type_group WHERE group_id = '$groupId'"), 0)) {
                $parent_group_id = "'" . $parent_group_id . "'";
            } else {
                $parent_group_id = "NULL";
            }
            
            $groupHierarchy = getGroups_byParent($groupId);
            for ($i = 0; $group = $groupHierarchy[$i]; $i++) {
                mysql_query("UPDATE type_group SET parent_group_id = $parent_group_id WHERE parent_group_id = '$groupId'");
            }
        } else {
            // Fetch and delete all nested descendants of the current group.
            
            $groups = getGroups_byParent($groupId);
            for ($i = 0; $group = $groups[$i]; $i++) {
                mysql_query("DELETE FROM sys_docs WHERE doc_id = '$group[group_id]'");
                mysql_query("DELETE FROM type_group WHERE group_id = '$group[group_id]'");
                #
                mysql_query("DELETE FROM type_group__paragraphs WHERE group_id = '$group[group_id]'");
                #
                mysql_query("DELETE FROM type_group__associated_docs WHERE group_id = '$group[group_id]'");
                #
                mysql_query("UPDATE type_memberzone SET group_id = NULL WHERE group_id = '$group[group_id]'");
                #
                mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$group[group_id]'");
                #
                mysql_query("DELETE FROM type_coupon__groups WHERE group_id = '$group[group_id]'");
                #
                mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$group[group_id]'");
            }
        }
        
        // Delete the current group.
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '$groupId'");
        mysql_query("DELETE FROM type_group WHERE group_id = '$groupId'");
        #
        mysql_query("DELETE FROM type_group__paragraphs WHERE group_id = '$groupId'");
        #
        mysql_query("DELETE FROM type_group__associated_docs WHERE group_id = '$groupId'");
        #
        mysql_query("UPDATE type_memberzone SET group_id = NULL WHERE group_id = '$groupId'");
        #
        mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$groupId'");
        #
        mysql_query("DELETE FROM type_coupon__groups WHERE group_id = '$groupId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$groupId'");
        
        return true;
    } else {
        $error = 'Invalid group.';
        return false;
    }
}

function renderGroupForm($groupId, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'group' AND doc_name_default_prefix IS NOT NULL"), 0);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Groups</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-form.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-form.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        function updateParentGroup(section_tag, selectbox) {
            var doc_subtype = '';
            if (selectbox.selectedIndex != -1) {
                for (var i = 0; i != selectbox['options'].length; i++) {
                    if (/^@/.test(selectbox['options'][i].value)) {
                        doc_subtype = selectbox['options'][i].value.substr(1);
                    }
                    if (selectbox['options'][i].selected) {
                        break;
                    }
                }
            }
            updateDocParametersByDocSubtype(section_tag, doc_subtype);
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    <form method="POST" id="adminForm">
    <input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
    <input type="hidden" name="save-as-new" id="save_as_new" value="0">
    <input type="hidden" name="idx" value="<?=htmlspecialchars($_FORM['idx'])?>">
    
    <div class="location-bar">
        <span id="collapse-all-icon" onclick="toggleSectionDisplay_all()">[-]</span>
        <a href="<?=href('/')?>">Main</a>
        / <a href="<?=href('/?doc=admin')?>">Administration</a>
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Groups</a>
        / <?=($groupId ? 'Id: ' . $groupId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__groups_general">
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
                <td class="col a"><div class="caption">description</div></td>
                <td class="col bc" colspan="2"><textarea id="textarea__description" name="description" class="htmlarea"><?=htmlspecialchars($_FORM['description'])?></textarea></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">parent group</div></td>
                <td class="col bc" colspan="2">
                    <select name="parent_group_id" id="parent_group_id" size="10" class="select" onchange="updateParentGroup('groups', this)" style="width: 580px; font-family: Courier New; font-size: 12px;">
                    <?
                    $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $selected = ('@' . $sql['doc_subtype'] == $_FORM['parent_group_id']) ? 'selected' : false;
                        
                        echo '<option value="@' . $sql['doc_subtype'] . '" ' . $selected . '>[ ' . $sql['title'] . ' ]</option>' . "\n";
                        
                        $groups = getGroups_byType($sql['doc_subtype']);
                        for ($i = 0; $group = $groups[$i]; $i++) {
                            $selected = ($group['group_id'] == $_FORM['parent_group_id']) ? 'selected' : false;
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
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">docs per page</div></td>
                <td class="col b"><input type="text" name="docs_per_page" maxlength="3" class="text" value="<?=htmlspecialchars($_FORM['docs_per_page'])?>" style="width: 40px;"></td>
                <td class="col c"><div class="annotation">Limits the number of elements shown on a page (optional).</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">associated coupons</div></td>
                <td class="col b">
                    <select name="associated_coupons[]" size="10" multiple class="select" style="width: 350px; font-family: Courier New; font-size: 12px;">
                    <?
                    $sql_query = mysql_query("SELECT coupon_id, title FROM type_coupon ORDER BY title ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $selected = @in_array($sql['coupon_id'], $_FORM['associated_coupons']) ? 'selected' : false;
                        echo '<option value="' . $sql['coupon_id'] . '" ' . $selected . '>' . $sql['title'] . '</option>' . "\n";
                    }
                    ?>
                    </select>
                </td>
                <td class="col c"><div class="annotation">All products associated with this group or its descendants will automatically be associated with the selected coupons.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">status</div></td>
                <td class="col b">
                    <? $_FORM['status'] = array($_FORM['status'] => 'checked'); ?>
                    <input type="radio" name="status" class="radio" value="normal" <?=$_FORM['status']['normal']?>> Normal
                    <br><input type="radio" name="status" class="radio" value="unavailable" <?=$_FORM['status']['unavailable']?>> Unavailable
                    <br><input type="radio" name="status" class="radio" value="hidden" <?=$_FORM['status']['hidden']?>> Hidden
                </td>
                <td class="col c"><div class="annotation">Normal items are listed on the web site. Unavailable items are listed in a customized manner. Hidden items are not listed on the web site but are accessible when manually entering their URL (for preview purposes).</div></td>
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
                <td class="col a"><div class="caption">doc note</div></td>
                <td class="col b"><textarea name="doc_note" class="textarea"><?=htmlspecialchars($_FORM['doc_note'])?></textarea></td>
                <td class="col c"><div class="annotation">Accessible only within the admin, and should be used to attach a temporary or permanent note to the doc.</div></td>
            </tr>
        </table>
    </div>
    
    <div class="section" id="section__groups_paragraphs">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">paragraphs</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['paragraphs']) > 0 ? count($_FORM['paragraphs']) : 1); $i++): ?>
        <div class="iteration" arrayname="paragraphs[<?=$i?>]">
            <div class="iteration-header">
                <div class="index" onclick="iteration_moveByIdx(this)">#<?=($i < 9 ? '0' . ($i+1) : $i+1)?></div>
                <div class="actions">
                    <span onclick="iteration_addAfter(this)">add after</span>
                    | <span onclick="iteration_addBefore(this)">add before</span>
                    | <span onclick="iteration_moveUp(this)">move up</span>
                    | <span onclick="iteration_moveDown(this)">move down</span>
                    | <span onclick="iteration_clear(this)">clear</span>
                    | <span onclick="iteration_delete(this)">delete</span>
                </div>
            </div>
            
            <table class="section">
                <tr>
                    <td class="col a"><div class="caption">title</div></td>
                    <td class="col b"><input type="text" name="paragraphs[<?=$i?>][title]" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['paragraphs'][$i]['title'])?>" style="width: 350px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">content</div></td>
                    <td class="col b"><textarea name="paragraphs[<?=$i?>][content]" class="textarea"><?=htmlspecialchars($_FORM['paragraphs'][$i]['content'])?></textarea></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">image url</div></td>
                    <td class="col b"><input type="text" name="paragraphs[<?=$i?>][image_url]" maxlength="255" class="text browse" value="<?=htmlspecialchars($_FORM['paragraphs'][$i]['image_url'])?>">&nbsp;<button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="gyroFileManager(this.previousSibling.previousSibling.id = Date().replace(/\W/g, ''), this.previousSibling.previousSibling.value)" onfocus="this.blur()" style="border-color: #F6F6F6;"><img src="/repository/admin/images/act_find.gif">&nbsp;&nbsp;Browse</button></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">image alignment</div></td>
                    <td class="col b">
                        <? $imageAlign[$i][$_FORM['paragraphs'][$i]['image_align']] = 'selected'; ?>
                        <select name="paragraphs[<?=$i?>][image_align]" class="select">
                            <option value="">&nbsp;</option>
                            <option value="left" <?=$imageAlign[$i]['left']?>>left</option>
                            <option value="right" <?=$imageAlign[$i]['right']?>>right</option>
                        </select>
                    </td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <div class="section" id="section__groups_seo">
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
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'group'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('group');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('groups', $param_section, $_FORM);
    }
    ?>
    <script>updateParentGroup('groups', document.getElementById('parent_group_id'))</script>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($groupId): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { if (confirm('Reassign descendants to the grandparent group (otherwise delete all descendants)?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&group=<?=$groupId?>&action=delete&reassign=1'; } else { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&group=<?=$groupId?>&action=delete'; } }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
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

function renderGroupsList($subtype = false) {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Groups</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/boxover.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/boxover.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        function getMarkedItems(arrayName) {
            var items = document.getElementsByName(arrayName);
            var items_array = new Array();
            for (var i = 0, j = 0; i != items.length; i++) {
                if (items[i].checked) {
                    items_array[j] = items[i].value;
                    j++;
                }
            }
            
            return items_array;
        }
        
        function couponAssoc() {
            if (!$('Dialog_couponAssoc')) {
                var dialog = document.createElement('div');
                dialog.id = 'Dialog_couponAssoc';
                dialog.style.display = 'none';
                dialog.innerHTML =
                      '<div style="font-family: Arial; font-size: 12px; font-weight: bold;">Associate Selected Groups with a Coupon</div>'
                    + '<table cellspacing="0" cellpadding="3" style="margin-top: 10px; margin-bottom: 10px; font-family: Arial; font-size: 12px;">'
                    + '    <tr>'
                    + '        <td>Coupon:</td>'
                    + '        <td>'
                    + '            <select id="Dialog_couponAssoc_coupon" style="width: 200px; height: 18px; font-size: 11px;">'
                    + '                <option value=""></option>'
                    <?php
                    $sql_query = mysql_query("SELECT coupon_id, title FROM type_coupon");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        echo '+ \'<option value="' . $sql['coupon_id'] . '">' . str_replace("'", "\'", htmlspecialchars($sql['title'])) . '</option>\'' . "\n";
                    }
                    ?>
                    + '            </select>'
                    + '        </td>'
                    + '    </tr>'
                    + '</table>'
                    + '<div style="text-align: center;">'
                    + '    <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onfocus="this.blur()" onclick="window.location = \'<?=href('/?doc=' . constant('DOC'))?>&coupon=\' + document.getElementById(\'Dialog_couponAssoc_coupon\').value + \'&groups=\' + getMarkedItems(\'items[]\') + \'&action=coupon-associate\';"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Associate</button>'
                    + '</div>';
                
                document.body.insertBefore(dialog, document.body.childNodes[0]);
                
                new Dialog.Box('Dialog_couponAssoc');
            }
            
            $('Dialog_couponAssoc').open();
            
            document.onkeydown = function (e) {
                if ((window.event && window.event.keyCode == 27) || (e && e.which == 27)) {
                    $('Dialog_couponAssoc').close();
                }
            }
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    
    <form method="POST" id="adminList">
    <table>
        <thead>
            <tr class="path">
                <td>
                    <a href="<?=href('/')?>">Main</a>
                    / <a href="<?=href('/?doc=admin')?>">Administration</a>
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Groups</a>
                </td>
            </tr>
            
            <tr>
                <td>
                    Filter groups by Subtype:
                    &nbsp;
                    <select id="filter-subtype" name="filter-subtype" class="groups" onchange="if (this.value != '<?=$subtype?>') window.location = '<?=href('/?doc=' . constant('DOC'))?>' + (this.value ? '&subtype=' + this.value : '')">
                    <option value="">&nbsp;</option>
                    <?
                    $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $groupsNum = @mysql_result(mysql_query("SELECT COUNT(*) FROM sys_docs WHERE doc_type = 'group' AND doc_subtype = '$sql[doc_subtype]'"), 0);
                        $selected = ($sql['doc_subtype'] == $subtype) ? 'selected' : false;
                        echo '<option value="' . $sql['doc_subtype'] . '" ' . $selected . '>' . $sql['title'] . ' [' . $groupsNum . ']</option>' . "\n";
                    }
                    ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td>
                    <input type="submit" value="Save" accesskey="s" class="button" style="width: 100px;">
                    <input type="button" value="Add Group" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&action=edit'">
                    <input type="button" value="Assoc. Coupon" class="button" style="width: 100px;" onclick="couponAssoc(); this.blur();">
                    <input type="button" value="Delete Marked" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { if (confirm('Reassign descendants to the grandparent group (otherwise delete all descendants)?')) { window.location = '<?=href('/?doc=' . constant('DOC'))?>&groups=' + getMarkedItems('items[]') + '&action=delete&reassign=1' } else { window.location = '<?=href('/?doc=' . constant('DOC'))?>&groups=' + getMarkedItems('items[]') + '&action=delete' } }">
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
        </thead>
        
        <tbody>
            <tr>
                <td>
                    <table>
                        <?
                        $flag = 0;
                        if ($subtype) {
                            $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' AND doc_subtype = '$subtype' ORDER BY idx ASC");
                        } else {
                            $sql_query = mysql_query("SELECT doc_subtype, title FROM sys_docs_subtypes WHERE doc_type = 'group' ORDER BY idx ASC");
                        }
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            if ($groups = getGroups_byType($sql['doc_subtype'])) {
                                ?>
                                <tr>
                                    <td colspan="6" expanded="1" onclick="toggleGroupTypeView('<?=$sql['doc_subtype']?>', this)" style="<?=($flag++ ? 'border-top: 1px dotted #999999;' : false)?> border-bottom: 1px dotted #999999; padding: 10px 2px 10px 5px; font-weight: bold; cursor: pointer;">
                                        <div id="gt_<?=$sql['doc_subtype']?>" style="float: right; font-family: Courier New; font-weight: normal;">[-]</div>
                                        <div style="font-size: 14px; font-variant: small-caps;"><?=strtolower($sql['title'])?></div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Id</th>
                                    <th>Title</th>
                                    <th>Doc Name</th>
                                    <th>Status</th>
                                    <th>Note</th>
                                </tr>
                                <?
                                
                                for ($i = 0; $i != count($groups); $i++) {
                                    if ($groups[$i]['parent_group_id']) {
                                        $groupsWithDescendants[$groups[$i]['parent_group_id']][] = $groups[$i]['group_id'];
                                        $groupsWithDescendants_subtype[$sql['doc_subtype']][] = $groups[$i]['parent_group_id'];
                                    }
                                }
                                if ($groupsWithDescendants_subtype[$sql['doc_subtype']]) {
                                    $groupsWithDescendants_subtype[$sql['doc_subtype']] = array_unique($groupsWithDescendants_subtype[$sql['doc_subtype']]);
                                }
                                
                                for ($i = 0; $group = $groups[$i]; $i++) {
                                    $firstFlag = !($i) ? 'first' : false;
                                    
                                    switch ($group['status']) {
                                        case 'normal': $status = 'Normal'; break;
                                        case 'unavailable': $status = 'Unavail.'; break;
                                        case 'hidden': $status = 'Hidden'; break;
                                        default: $status = ''; break;
                                    }
                                    
                                    list($group['doc_name'], $group['doc_note'], $group['doc_active']) = mysql_fetch_array(mysql_query("SELECT doc_name, doc_note, doc_active FROM sys_docs WHERE doc_id = '$group[group_id]'"));
                                ?>
                                    <tr class="list <?=$firstFlag?>" id="g_<?=$group['group_id']?>" expanded="1">
                                        <td class="first check"><input type="checkbox" name="items[]" value="<?=$group['group_id']?>" style="margin: 0;"></td>
                                        <td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&group=<?=$group['group_id']?>&action=edit"><?=$group['group_id']?></a></td>
                                        <td style="width: 100%; padding-left: <?=(5 + $group['level'] * 30)?>px;">
                                            <input type="text" name="idx[<?=$group['group_id']?>]" value="<?=$group['idx']?>" class="idx" style="margin-right: 5px;">
                                            <? if ($groupsWithDescendants[$group['group_id']]): ?>
                                            <span style="cursor: pointer;" onclick="toggleGroupView('<?=$group['group_id']?>')"><span id="gi_<?=$group['group_id']?>" style="margin-right: 5px; font-family: Courier New;">[-]</span><?=$group['title']?></span>
                                            <? else: ?>
                                            <?=$group['title']?>
                                            <? endif; ?>
                                        </td>
                                        <td><?=($group['doc_name'] ? $group['doc_name'] : '<br>')?></td>
                                        <td class="status"><?=$status?></td>
                                        <td><?=($group['doc_note'] ? '<img src="/repository/admin/images/boxover-info.gif" title="header=[Note] body=[' . nl2br(htmlspecialchars(str_replace('[', '[[', str_replace(']', ']]', $group['doc_note'])))) . ']" style="cursor: pointer;">' : false)?></td>
                                    </tr>
                                <?
                                }
                            }
                        }
                        ?>
                    </table>
                    
                    <script type="text/javascript">
                    var collapsed_groups_ids = new Array(); // A list of all collapsed groups.
                    var groupsWithDescendants = new Object; // A map of all groups-with-descendants.
                    <? while (list($groupId, $descendants) = each($groupsWithDescendants)): ?>
                    groupsWithDescendants[<?=$groupId?>] = [<?=implode(', ', $descendants)?>];
                    <? endwhile; ?>
                    
                    function toggleGroupView(groupId) {
                        try {
                            var group = document.getElementById('g_' + groupId);
                            var expanded = parseInt(group.getAttribute('expanded'));
                            toggleGroupView_recursive(groupId, expanded);
                            group.setAttribute('expanded', 1 - expanded);
                            document.getElementById('gi_' + groupId).innerHTML = expanded ? '[+]' : '[-]';
                            
                            // Keep an up-to-date list of collapsed groups in a cookie.
                            if (expanded) {
                                collapsed_groups_ids[collapsed_groups_ids.length] = groupId;
                            } else {
                                var collapsed_groups_ids_new = new Array();
                                for (var i = 0; i != collapsed_groups_ids.length; i++) {
                                    if (collapsed_groups_ids[i] != groupId) {
                                        collapsed_groups_ids_new[collapsed_groups_ids_new.length] = collapsed_groups_ids[i];
                                    }
                                }
                                collapsed_groups_ids = collapsed_groups_ids_new;
                            }
                            setCookie('collapsed_groups_ids', collapsed_groups_ids, 8640000, '<?=href('/')?>');
                        } catch (e) {}
                    }
                    
                    function toggleGroupView_recursive(groupId, expanded) {
                        if (typeof groupsWithDescendants[groupId] != 'undefined') {
                            for (var i = 0; i != groupsWithDescendants[groupId].length; i++) {
                                document.getElementById('g_' + groupsWithDescendants[groupId][i]).style.display = expanded ? 'none' : '';
                                
                                if (typeof groupsWithDescendants[groupsWithDescendants[groupId][i]] != 'undefined') {
                                    toggleGroupView_recursive(groupsWithDescendants[groupId][i], (expanded ? 1 : 1 - document.getElementById('g_' + groupsWithDescendants[groupId][i]).getAttribute('expanded')));
                                }
                            }
                        }
                    }
                    
                    var groupsWithDescendants_subtype = new Object; // A map of top-level groups-with-descendants by group subtype.
                    <? while (list($subtype, $groups) = each($groupsWithDescendants_subtype)): ?>
                    groupsWithDescendants_subtype['<?=$subtype?>'] = [<?=implode(', ', $groups)?>];
                    <? endwhile; ?>
                    
                    function toggleGroupTypeView(subtype, element) {
                        var expanded = parseInt(element.getAttribute('expanded'));
                        document.getElementById('gt_' + subtype).innerHTML = expanded ? '[+]' : '[-]';
                        for (var i = 0; i != groupsWithDescendants_subtype[subtype].length; i++) {
                            document.getElementById('g_' + groupsWithDescendants_subtype[subtype][i]).setAttribute('expanded', expanded);
                            toggleGroupView(groupsWithDescendants_subtype[subtype][i]);
                        }
                        element.setAttribute('expanded', 1 - expanded);
                    }
                    
                    // When loading the page, collapse all groups listed in the cookie.
                    var collapsed_groups_ids_tmp = '<?=$_COOKIE['collapsed_groups_ids']?>'.split(',');
                    for (var i = 0; i != collapsed_groups_ids_tmp.length; i++) {
                        toggleGroupView(collapsed_groups_ids_tmp[i]);
                    }
                    </script>
                </td>
            </tr>
        </tbody>
        
        <tfoot>
            <tr>
                <td>
                    <input type="submit" value="Save" accesskey="s" class="button" style="width: 100px;">
                    <input type="button" value="Add Group" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&action=edit'">
                    <input type="button" value="Assoc. Coupon" class="button" style="width: 100px;" onclick="couponAssoc(); this.blur();">
                    <input type="button" value="Delete Marked" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { if (confirm('Reassign descendants to the grandparent group (otherwise delete all descendants)?')) { window.location = '<?=href('/?doc=' . constant('DOC'))?>&groups=' + getMarkedItems('items[]') + '&action=delete&reassign=1' } else { window.location = '<?=href('/?doc=' . constant('DOC'))?>&groups=' + getMarkedItems('items[]') + '&action=delete' } }">
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

?>