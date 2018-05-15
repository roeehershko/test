<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_GET['ajax']) {
    echo renderListAJAX();
    exit;
}

if ($_GET['doc-type']) {
    if (!$docType = validateDocType($_GET['doc-type'])) {
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
}

if ($_GET['act'] == 'edit' && $docType) {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateDocTypeData($docType, $_POST, $validateDocTypeData__errors)) {
            recursive('mysql_real_escape_string', $_POST);
            if (saveDocType($docType, $_POST, $saveDocType__error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveDocType__error, 'saveDocType', __FILE__, __LINE__);
            }
        } else {
            echo renderDocTypeForm($docType, $_POST, $validateDocTypeData__errors);
        }
    } else {
        $_FORM = getDocTypeData($docType);
        $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
        echo renderDocTypeForm($docType, $_FORM);
    }
} else {
    echo renderDocTypesList();
}


// Functions.

function validateDocType($docType) {
    return @mysql_result(mysql_query("SELECT type FROM sys_docs_types WHERE type = '$docType'"), 0);
}

function getDocTypeData($docType) {
    $DocTypeData['doc_name_default_prefix'] = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = '$docType'"), 0);
    $DocTypeData['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_defaults WHERE doc_type = '$docType'"), 0);
    
    #
    
    $sql_query = mysql_query("SELECT doc_subtype, title, title_singular FROM sys_docs_subtypes WHERE doc_type = '$docType' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $DocTypeData['subtypes'][] = $sql;
    }
    
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = '$docType' ORDER BY section_id ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $DocTypeData['parameter_sections'][] = $sql;
    }
    
    return $DocTypeData;
}

function validateDocTypeData($docType, &$POST, &$errors) {
    if ($POST['doc_name_default_prefix'] && !preg_match('/[a-z0-9\_\-\/]+/', $POST['doc_name_default_prefix'])) {
        $errors['doc_name_default_prefix'] = 'Invalid <u>Doc Name Default Prefix</u>.';
    }
    
    $POST['subtypes'] = getNonEmptyIterations($POST['subtypes']);
    for ($i = 0; $i != count($POST['subtypes']); $i++) {
        if (!preg_match('/^[a-z0-9\-\_]+$/', $POST['subtypes'][$i]['doc_subtype'])) {
            $errors['subtypes[' . $i . '][doc_subtype]'] = 'Invalid <u>subtype</u>.';
        }
        if (!$POST['subtypes'][$i]['title']) {
            $errors['subtypes[' . $i . '][title]'] = 'Invalid <u>Title</u>.';
        }
        if (!$POST['subtypes'][$i]['title_singular']) {
            $errors['subtypes[' . $i . '][title_singular]'] = 'Invalid <u>Title (singular)</u>.';
        }
    }
    
    /*
    Here we need to do something with all existing docs that have a subtype that was renamed/removed.
    */
    
    $POST['parameter_sections'] = getNonEmptyIterations($POST['parameter_sections']);
    for ($i = 0; $i != count($POST['parameter_sections']); $i++) {
        if (!$POST['parameter_sections'][$i]['title']) {
            $errors['parameter_sections[' . $i . '][title]'] = 'Invalid <u>Title</u>.';
        }
    }
    
    $sql_query = mysql_query("SELECT section_id FROM sys_docs_types_params WHERE doc_type = '$docType' AND is_iteratable = '1'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $flag = false;
        for ($i = 0; $i != count($POST['parameter_sections']); $i++) {
            if ($sql['section_id'] == $POST['parameter_sections'][$i]['section_id']) {
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            $section = @mysql_result(mysql_query("SELECT title FROM sys_docs_types_params_sections WHERE doc_type = '$docType' AND section_id = '" . $sql['section_id'] . "'"), 0);
            $errors[] = 'The section <u>' . $section . '</u> cannot be deleted because it contains an iteratable parameter. Return to undo all changes or re-associate the parameter to another section.';
        }
    }
    
    return $errors ? false : true;
}

function saveDocType($docType, $POST, &$error) {
    $sql_errors = false;
    mysql_query("BEGIN");
    
    ##
    
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = '$docType'"), 0);
    
    if ($POST['doc_name_default_prefix'] != $docNameDefaultPrefix) {
        mysql_query("UPDATE sys_docs_types SET doc_name_default_prefix = " . SQLNULL($_POST['doc_name_default_prefix']) . " WHERE type = '$docType'");
        mysql_query("UPDATE sys_docs SET doc_name = CONCAT('$_POST[doc_name_default_prefix]', SUBSTRING(doc_name, " . (strlen($docNameDefaultPrefix) + 1) . ")) WHERE doc_type = '$docType' AND SUBSTRING(doc_name, 1, " . strlen($docNameDefaultPrefix) . ") = '" . $docNameDefaultPrefix . "'");
    }
    
    #
    
    mysql_query("DELETE FROM type_review__fsets_defaults WHERE doc_type = '$docType'");
    if ($_POST['fset_id']) {
        mysql_query("INSERT INTO type_review__fsets_defaults (doc_type, fset_id) VALUES ('$docType', '$_POST[fset_id]')");
    }
    
    #
    
    mysql_query("DELETE FROM sys_docs_subtypes WHERE doc_type = '$docType'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($_POST['subtypes']); $i++) {
        mysql_query("INSERT INTO sys_docs_subtypes (doc_type, doc_subtype, title, title_singular, idx) VALUES ('$docType', '" . $_POST['subtypes'][$i]['doc_subtype'] . "', '" . $_POST['subtypes'][$i]['title'] . "', '" . $_POST['subtypes'][$i]['title_singular'] . "', '$i')") || $sql_errors[] = mysql_error();
    }
    
    mysql_query("DELETE FROM sys_docs_types_params_sections WHERE doc_type = '$docType'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($_POST['parameter_sections']); $i++) {
        $newId = $i + 1;
        mysql_query("INSERT INTO sys_docs_types_params_sections (doc_type, section_id, title, description) VALUES ('$docType', '$newId', '" . $_POST['parameter_sections'][$i]['title'] . "', '" . $_POST['parameter_sections'][$i]['description'] . "')") || $sql_errors[] = mysql_error();
        
        $mapping[$_POST['parameter_sections'][$i]['section_id']] = $newId;
    }
    
    // Remap parameters to the updated section list.
    // Note: parameters are not required to be assigned to a parameter section, so deleting a section simply unassignes its paramters.
    $sql_query = mysql_query("SELECT param_name, section_id FROM sys_docs_types_params WHERE doc_type = '$docType' AND section_id IS NOT NULL");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $newId = $mapping[$sql['section_id']] ? "'" . $mapping[$sql['section_id']] . "'" : "NULL";
        mysql_query("UPDATE sys_docs_types_params SET section_id = $newId WHERE doc_type = '$docType' AND param_name = '" . mysql_real_escape_string($sql['param_name']) . "'");
    }
    
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

function renderDocTypeForm($docType, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = '$docType'"), 0);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Doc Types</title>
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
    
    <div class="location-bar">
        <span id="collapse-all-icon" onclick="toggleSectionDisplay_all()">[-]</span>
        <a href="<?=href('/')?>">Main</a>
        / <a href="<?=href('/?doc=admin')?>">Administration</a>
        / <a href="<?=href('/?doc=admin/doc-types')?>">Doc Types</a>
        / <span style="font-variant: small-caps; font-size: 14px;"><?=$docType?></span>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__doc_type_general">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">general</div>
        </div>
        
        <table class="section">
            <tr>
                <td class="col a"><div class="caption">doc name default prefix</div></td>
                <td class="col b"><input type="text" name="doc_name_default_prefix" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['doc_name_default_prefix'])?>" style="width: 350px;"></td>
                <td class="col c"><div class="annotation">Updating this will update all existing doc-names of this doc-type.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">default review feature set</div></td>
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
                <td class="col c"></td>
            </tr>
        </table>
    </div>
    
    <div class="section" id="section__doc_type_subtypes">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">doc subtypes</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['subtypes']) > 0 ? count($_FORM['subtypes']) : 1); $i++): ?>
        <div class="iteration" arrayname="subtypes[<?=$i?>]">
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
                    <td class="col a"><div class="caption">subtype</div></td>
                    <td class="col b"><input type="text" name="subtypes[<?=$i?>][doc_subtype]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['subtypes'][$i]['doc_subtype'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                <tr>
                    <td class="col a"><div class="caption">title</div></td>
                    <td class="col b"><input type="text" name="subtypes[<?=$i?>][title]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['subtypes'][$i]['title'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                <tr>
                    <td class="col a"><div class="caption">title (singular)</div></td>
                    <td class="col b"><input type="text" name="subtypes[<?=$i?>][title_singular]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['subtypes'][$i]['title_singular'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <div class="section" id="section__doc_type_parameter_sections">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">parameter sections</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['parameter_sections']) > 0 ? count($_FORM['parameter_sections']) : 1); $i++): ?>
        <div class="iteration" arrayname="parameter_sections[<?=$i?>]">
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
                    <td class="col b">
                        <input type="hidden" name="parameter_sections[<?=$i?>][section_id]" value="<?=htmlspecialchars($_FORM['parameter_sections'][$i]['section_id'])?>">
                        <input type="text" name="parameter_sections[<?=$i?>][title]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['parameter_sections'][$i]['title'])?>" style="width: 325px;">
                    </td>
                    <td class="col c"></td>
                </tr>
                <tr>
                    <td class="col a"><div class="caption">description</div></td>
                    <td class="col b"><textarea name="parameter_sections[<?=$i?>][description]" class="textarea"><?=htmlspecialchars($_FORM['parameter_sections'][$i]['description'])?></textarea></td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
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

function renderDocTypesList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Doc Types</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        
        <script type="text/javascript">
        var uri = '<?=href('/?' . $_SERVER['QUERY_STRING'])?>' + window.location.hash.replace(/^#/, '&');
        
        var items_metad = new Array(
            Array('doc-type', 'Doc Type'),
            Array('subtypes', 'Subtypes', 'Number of Doc Subtypes'),
            Array('docs', 'Docs', 'Number of Docs'),
            Array('parameters', 'Parameters', 'Number of Parameters (Number of Parameter Sections)')
        );
        
        /* ## */
        
        function adminList_updateList__central(XMLDocument) {
            var items = XMLDocument.getElementsByTagName('item');
            var list = new Array();
            
            for (var i = 0; i != items.length; i++) {
                list[i] = '<tr class="list ' + ((list.length == 0) ? 'first' : '') + '">'
                        +     '<td class="first" style="font-variant: small-caps; width: 100%;"><a href="<?=href('/?doc=' . constant('DOC'))?>&doc-type=' + items[i].getAttribute('doc_type') + '&act=edit">' + items[i].getAttribute('doc_type') + '</a></td>'
                        +     '<td style="text-align: center;"">' + items[i].getAttribute('subtypes') + '</td>'
                        +     '<td style="text-align: center;"">' + items[i].getAttribute('docs') + '</td>'
                        +     '<td style="text-align: center;"">' + items[i].getAttribute('parameters') + ' (' + items[i].getAttribute('parameter_sections') + ')</td>'
                        + '</tr>';
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
    <table>
        <thead>
            <tr class="path">
                <td>
                    <a href="<?=href('/')?>">Main</a>
                    / <a href="<?=href('/?doc=admin')?>">Administration</a>
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Doc Types</a>
                </td>
            </tr>
            
            <tr>
                <td style="text-align: center;">
                    <div id="bar-search-query"></div>
                    <div id="bar-limits"></div>
                    <div id="bar-selections" style="display: none;"></div>
                    
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="display: none;">
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
                <td><br></td>
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
        echo ' doc_type="' . $item['type'] . '"';
        echo ' subtypes="' . xmlentities($item['subtypes']) . '"';
        echo ' docs="' . xmlentities($item['docs']) . '"';
        echo ' parameters="' . xmlentities($item['parameters']) . '"';
        echo ' parameter_sections="' . xmlentities($item['parameter_sections']) . '"';
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
        $order = 'doc-type';
        $sort = 'ASC';
    }
    
    switch ($order) {
        case 'doc-type': $ORDERBY = "ORDER BY type $sort"; break;
        case 'subtypes': $ORDERBY = "ORDER BY subtypes $sort"; break;
        case 'docs': $ORDERBY = "ORDER BY docs $sort"; break;
        case 'parameters': $ORDERBY = "ORDER BY parameters $sort, parameter_sections $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('type');
        
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
    
    $sql_query = mysql_query("SELECT sdt.type, (SELECT count(*) FROM sys_docs_subtypes WHERE doc_type = sdt.type) AS subtypes, (SELECT COUNT(*) FROM sys_docs WHERE doc_type = sdt.type) AS docs, (SELECT COUNT(*) FROM sys_docs_types_params_sections WHERE doc_type = sdt.type) AS parameter_sections, (SELECT COUNT(*) FROM sys_docs_types_params WHERE doc_type = sdt.type) AS parameters FROM sys_docs_types sdt $HAVING $ORDERBY");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>