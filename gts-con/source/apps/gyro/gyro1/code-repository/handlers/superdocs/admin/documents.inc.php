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
        mysql_query("UPDATE sys_docs SET doc_note = " . SQLNULL(mysql_real_escape_string(stripslashes(trim($_GET['note'])))) . " WHERE doc_id = '" . mysql_real_escape_string($_GET['doc_id']) . "' AND doc_type = 'document'");
    }
    
    echo renderListAJAX();
    exit;
}

if ($_GET['document']) {
    if (!$documentId = validateDocumentId($_GET['document'])) {
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
}

$importExportMeta = array(
    array('db' => 'document_id', 'caption' => '[#] Doc Id', 'type' => 'variable'),
    array('db' => 'doc_name', 'caption' => 'Doc Name', 'type' => 'variable'),
    array('db' => 'title', 'caption' => 'Title', 'type' => 'variable'),
    array('db' => 'description', 'caption' => 'Description', 'type' => 'variable'),
    array('db' => 'status', 'caption' => 'Status', 'type' => 'variable'),
    array('db' => 'fset_id', 'caption' => 'Review Feature Set Id', 'type' => 'variable'),
    array('db' => 'doc_note', 'caption' => 'Doc Note', 'type' => 'variable'),
    
    array('db' => 'paragraphs', 'type' => array(
        array('db' => 'title', 'caption' => '[Paragraphs] Title', 'type' => 'variable'),
        array('db' => 'content', 'caption' => '[Paragraphs] Content', 'type' => 'variable'),
        array('db' => 'image_url', 'caption' => '[Paragraphs] Image URL', 'type' => 'variable'),
        array('db' => 'image_align', 'caption' => '[Paragraphs] Image Align', 'type' => 'variable')
    )),
    
    array('db' => 'seo_title', 'caption' => 'SEO Title', 'type' => 'variable'),
    array('db' => 'seo_description', 'caption' => 'SEO Description', 'type' => 'variable'),
    array('db' => 'seo_keywords', 'caption' => 'SEO Keywords', 'type' => 'variable'),
);
embedDocTypeParametersIntoImportExportMeta('document', $importExportMeta);

if ($_POST['act'] == 'delete' || $_POST['act'] == 'export-selected') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs();
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['document_id'];
        }
    }
    
    if ($_POST['act'] == 'delete') {
        for ($i = 0; $i != count($items); $i++) {
            if (!deleteDocument($items[$i], $deleteDocument__error)) {
                abort($deleteDocument__error, 'deleteDocument', __FILE__, __LINE__);
            }
        }
    } elseif ($_POST['act'] == 'export-selected') {
        for ($i = 0; $i != count($items); $i++) {
            $data[] = getDocumentData($items[$i], true);
        }
        
        if ($data) {
            XMLExport('documents-' . date('Y-m-d'), $importExportMeta, $data);
        }    
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateDocumentData($_POST['save-as-new'] ? false : $documentId, $_POST, false, $validateDocumentData__errors)) {
            if ($_POST['save-as-new']) {
                $documentId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveDocument($documentId, $_POST, false, $saveDocument_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveDocument_error, 'saveDocument', __FILE__, __LINE__);
            }
        } else {
            echo renderDocumentForm($documentId, $_POST, $validateDocumentData__errors);
        }
    } else {
        if ($documentId) {
            $_FORM = getDocumentData($documentId, false);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderDocumentForm($documentId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            $_FORM['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_defaults WHERE doc_type = 'document'"), 0);
            echo renderDocumentForm(false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $documentId) {
    if (!deleteDocument($documentId, $deleteDocument__error)) {
        abort($deleteDocument__error, 'deleteDocument', __FILE__, __LINE__);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} elseif ($_GET['act'] == 'export-xml') {
    $sql_query = mysql_query("SELECT document_id FROM type_document ORDER BY document_id ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $data[] = getDocumentData($sql['document_id'], true);
    }
    
    if ($data) {
        XMLExport('documents-' . date('Y-m-d'), $importExportMeta, $data);
    } else {
        abort('No data to export.', 'XMLExport', __FILE__, __LINE__);
    }
} elseif ($_FILES['import-xml']) {
    XMLImport($_FILES['import-xml'], 'document_id', $importExportMeta, 'validateDocumentData', 'saveDocument', 'deleteDocument');
} else {
    echo renderDocumentsList();
}


// Functions.

function validateDocumentId($documentId) {
    return @mysql_result(mysql_query("SELECT document_id FROM type_document WHERE document_id = '$documentId'"), 0);
}

function getDocumentData($documentId, $externalTransaction) {
    $document = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id AS document_id, sd.doc_name, sd.doc_note, sd.doc_active,
            td.title, td.description, td.status, td.seo_title, td.seo_description, td.seo_keywords
        FROM sys_docs sd JOIN type_document td ON doc_id = document_id
        WHERE document_id = '$documentId'
    "));
    
    #
    
    $sql_query = mysql_query("SELECT title, content, image_url, image_align FROM type_document__paragraphs WHERE document_id = '$documentId' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $document['paragraphs'][] = $sql;
    }
    
    #
    
    $document['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$documentId'"), 0);
    
    #
    
    $document['parameters'] = getDocParameters($documentId);
    
    if ($externalTransaction) {
        // Load every parameter in its defined template, even if some of the information is missing. This is required for the export mechanism.
        if ($params = getDocTypeParameters('document')) {
            foreach ($params as $param) {
                if ($param['is_iteratable']) {
                    if (count($param['data']) == 1) {
                        for ($i = 0; $i != count($document['parameters'][$param['param_name']]); $i++) {
                            foreach ($param['data'] as $param_data) {
                                $document['parameters__' . $param['param_name']][$i] = $document['parameters'][$param['param_name']][$i];
                            }
                        }
                    } else {
                        for ($i = 0; $i != count($document['parameters'][$param['param_name']]); $i++) {
                            foreach ($param['data'] as $param_data) {
                                $document['parameters__' . $param['param_name']][$i][$param_data['param_data_name']] = $document['parameters'][$param['param_name']][$i][$param_data['param_data_name']];
                            }
                        }
                    }
                } else {
                    if (count($param['data']) == 1) {
                        $document['parameters__' . $param['param_name']] = $document['parameters'][$param['param_name']];
                    } else {
                        foreach ($param['data'] as $param_data) {
                            $document['parameters__' . $param['param_name'] . '__' . $param_data['param_data_name']] = $document['parameters'][$param['param_name']][$param_data['param_data_name']];
                        }
                    }
                }
            }
        }
        
        unset($document['parameters']);
    }
    
    return $document;
}

function validateDocumentData($documentId, &$POST, $externalTransaction, &$errors) {
    if ($POST['doc_name']) {
        $POST['doc_name'] = strtolower($POST['doc_name']);
        
        $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'document' AND doc_name_default_prefix IS NOT NULL"), 0);
        
        if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $POST['doc_name'], $match)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (must begin with <u>' . $docNameDefaultPrefix . '</u>).';
        } elseif (!is_valid_doc_name($POST['doc_name'], $docNameDefaultPrefix)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (contains illegal characters).';
        } elseif (@mysql_result(mysql_query("SELECT doc_name FROM sys_docs WHERE doc_name = '$POST[doc_name]' AND doc_id != '$documentId'"), 0)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (already in use).';
        }
    }
    
    if (!$POST['title']) {
        $errors['title'] = 'Invalid <u>Title</u>.';
    }
    
    if (!$POST['status']) {
        $errors['status'] = 'Invalid <u>Status</u> (not selected).';
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
    
    if ($externalTransaction && $POST['fset_id']) {
        if (!@mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets WHERE fset_id = '$POST[fset]'"), 0)) {
            $errors['fset_id'] = 'Invalid <u>Review Feature Set</u>.';
        }
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
    validateDocParameters('document', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveDocument($documentId, $POST, $externalTransaction, &$error) {
    $sql_errors = false;
    
    if (!$externalTransaction) {
        mysql_query("BEGIN");
    }
    
    ##
    
    $docName = ($POST['doc_name'] == '') ? "NULL" : "'" . $POST['doc_name'] . "'";
    $docNote = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_note) VALUES ('$documentId', $docName, 'document', $docNote)") || $sql_errors[] = mysql_error();
    $documentId = mysql_insert_id();
    
    mysql_query("REPLACE type_document (document_id, title, description, seo_title, seo_description, seo_keywords, status) VALUES ('$documentId', '$POST[title]', '$POST[description]', '$POST[seo_title]', '$POST[seo_description]', '$POST[seo_keywords]', '$POST[status]')") || $sql_errors[] = mysql_error();
    
    #
    
    mysql_query("DELETE FROM type_document__paragraphs WHERE document_id = '$documentId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['paragraphs']); $i++) {
        mysql_query("INSERT INTO type_document__paragraphs (document_id, title, content, image_url, image_align, idx) VALUES ('$documentId', '" . $POST['paragraphs'][$i]['title'] . "', '" . $POST['paragraphs'][$i]['content'] . "', '" . $POST['paragraphs'][$i]['image_url'] . "', '" . $POST['paragraphs'][$i]['image_align'] . "', '$i')") || $sql_errors[] = mysql_error();
    }
    
    #
    
    mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$documentId'");
    if ($POST['fset_id']) {
        mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('$POST[fset_id]', '$documentId')");
    }
    
    #
    
    saveDocParameters('document', $documentId, $POST['parameters'], $sql_errors);
    
    ##
    
    if (!$externalTransaction) {
        if ($sql_errors) {
            $error = 'SQL errors:<br>' . implode(',<br>', $sql_errors);
            mysql_query("ROLLBACK");
            
            return false;
        } else {
            mysql_query("COMMIT");
            
            return true;
        }
    } else {
        if ($sql_errors) {
            $error = implode("\n", $sql_errors);
        }
        return $sql_errors ? false : true;
    }
}

function deleteDocument($documentId, &$error) {
    if (validateDocumentId($documentId)) {
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '$documentId'");
        mysql_query("DELETE FROM type_document WHERE document_id = '$documentId'");
        #
        mysql_query("DELETE FROM type_document__paragraphs WHERE document_id = '$documentId'");
        #
        mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$documentId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$documentId'");
        
        return true;
    } else {
        $error = 'Invalid document.';
        return false;
    }
}

function renderDocumentForm($documentId, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'document' AND doc_name_default_prefix IS NOT NULL"), 0);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Documents</title>
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
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Documents</a>
        / <?=($documentId ? 'Id: ' . $documentId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__documents_general">
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
    
    <div class="section" id="section__documents_paragraphs">
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
    
    <div class="section" id="section__documents_seo">
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
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'document'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('document');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('documents', $param_section, $_FORM);
    }
    ?>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($documentId): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&document=<?=$documentId?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
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

function renderDocumentsList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Documents</title>
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
            Array('status', 'Status'),
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
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&document=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('id') + '</a></td>'
                        +     '<td style="width: 100%;">' + items[i].getAttribute('title') + '</td>'
                        +     '<td>' + items[i].getAttribute('status') + '</td>'
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
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Documents</a>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Add Document" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Actions" class="button" style="width: 84px;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="actions-button-drop-down-menu" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Import XML" class="button" style="width: 100px;" onclick="importXML(); adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Export XML" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=export-xml'; adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Export Sel." class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'export-selected'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                        </div>
                    </div>
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
                    <input type="button" value="Add Document" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Actions" class="button" style="width: 84px;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="actions-button-drop-down-menu-2" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Import XML" class="button" style="width: 100px;" onclick="importXML(); adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Export XML" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=export-xml'; adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Export Sel." class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'export-selected'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                        </div>
                    </div>
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
        switch ($item['status']) {
            case 'normal': $item['status'] = 'Normal'; break;
            case 'unavailable': $item['status'] = 'Unavail.'; break;
            case 'hidden': $item['status'] = 'Hidden'; break;
            default: $item['status'] = ''; break;
        }
        
        echo '    ';
        echo '<item';
        echo ' id="' . $item['document_id'] . '"';
        echo ' title="' . xmlentities($item['title']) . '"';
        echo ' status="' . xmlentities($item['status']) . '"';
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
        case 'status': $ORDERBY = "ORDER BY status $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_note', 'document_id', 'title', 'status');
        
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
    
    $sql_query = mysql_query("SELECT sd.doc_note, sd.doc_active, td.document_id, td.title, td.status FROM sys_docs sd JOIN type_document td ON doc_id = document_id $HAVING $ORDERBY");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>