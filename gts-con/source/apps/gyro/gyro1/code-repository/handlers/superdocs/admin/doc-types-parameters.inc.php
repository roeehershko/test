<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_GET['ajax']) {
    echo renderListAJAX();
    exit;
}

if ($_GET['parameter']) {
    if (!$parameter = validateParameterName($_GET['parameter'])) {
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
            $itemsAll[] = $list[$i]['doc_type'] . '@' . $list[$i]['param_name'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    for ($i = 0; $i != count($items); $i++) {
        if (!deleteParameter($items[$i], $deleteParameter__error)) {
            abort($deleteParameter__error, 'deleteParameter', __FILE__, __LINE__);
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        if (validateParameterData($_POST['save-as-new'] ? false : $parameter, $_POST, $validateParameterData__errors)) {
            if ($_POST['save-as-new']) {
                $parameter = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveParameter($parameter, $_POST, $saveParameter_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveParameter_error, 'saveParameter', __FILE__, __LINE__);
            }
        } else {
            echo renderParameterForm($parameter, $_POST, $validateParameterData__errors);
        }
    } else {
        if ($parameter) {
            $_FORM = getParameterData($parameter);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderParameterForm($parameter, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderParameterForm(false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $parameter) {
    if (!deleteParameter($parameter, $deleteParameter__error)) {
        abort($deleteParameter__error, 'deleteParameter', __FILE__, __LINE__);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} else {
    if ($_POST['idx']) {
        foreach ($_POST['idx'] as $parameter => $idx) {
            list($docType, $parameterName) = @explode('@', $parameter);
            $sectionId = @mysql_result(mysql_query("SELECT section_id FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$parameterName'"), 0);
            $parameters[$docType][$sectionId][$parameterName] = $idx;
        }
        
        foreach ($parameters as $docType => $sectionId) {
            foreach ($sectionId as $params) {
                asort($params);
                $params = array_keys($params);
                
                for ($i = 0; $i != count($params); $i++) {
                    mysql_query("UPDATE sys_docs_types_params SET idx = '" . ($i + 1) . "' WHERE doc_type = '$docType' AND param_name = '" . $params[$i] . "'");
                }
            }
        }
        
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
    
    echo renderParametersList();
}


// Functions.

function validateParameterName($parameter) {
    list($docType, $parameterName) = @explode('@', $parameter);
    
    if ($sql = mysql_fetch_assoc(mysql_query("SELECT doc_type, param_name FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$parameterName'"))) {
        return $sql['doc_type'] . '@' . $sql['param_name'];
    } else {
        return false;
    }
}

function getParameterData($parameter) {
    list($docType, $parameterName) = @explode('@', $parameter);
    
    $param = mysql_fetch_assoc(mysql_query("SELECT doc_type, param_name, section_id, is_iteratable FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$parameterName'"));
    
    #
    
    $sql_query = mysql_query("SELECT param_data_name, caption, annotation, type, type__text__maxlength, type__selectparam__param FROM sys_docs_types_params_data WHERE doc_type = '$docType' AND param_name = '$parameterName' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $sql['param_data_name_old'] = $sql['param_data_name']; // Required so the old name doesn't update on a reload (when there was an error).
        $sub_sql_query = mysql_query("SELECT param_data_option_value AS value, text, is_selected FROM sys_docs_types_params_data_options WHERE doc_type = '$docType' AND param_name = '$parameterName' AND param_data_name = '" . mysql_real_escape_string($sql['param_data_name']) . "' ORDER BY idx ASC");
        while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
            $sql['options'][] = $sub_sql;
        }
        
        $param['data'][] = $sql;
    }
    
    #
    
    $sql_query = mysql_query("SELECT doc_subtype FROM sys_docs_types_params__subtypes WHERE doc_type = '$docType' AND param_name = '$parameterName'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param['subtypes'][] = $sql['doc_subtype'];
    }
    
    return $param;
}

function validateParameterData($parameter, &$POST, &$errors) {
    list($docType, $parameterName) = @explode('@', $parameter);
    
    if (!$parameterName) {
        $docType = $POST['doc_type'];
    }
    
    if (!preg_match('/^[a-z][a-z0-9_]*[a-z0-9]$/', $POST['param_name'])) {
        $errors['param_name'] = 'Invalid <u>Name</u>.';
    } elseif ($parameterName != $POST['param_name'] && @mysql_result(mysql_query("SELECT param_name FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$POST[param_name]'"), 0)) {
        $errors['param_name'] = 'Invalid <u>Name</u> (already assigned to another parameter in this doc-type).';
    }
    
    if ($POST['is_iteratable']) {
        if (!$POST['section_id']) {
            $errors['section_id'] = 'Invalid <u>Section</u> (an iteratable parameter must belong to a section).';
        } elseif ($POST['section_id'] == '@new' && !$POST['section_new']) {
            $errors['section_id'] = 'Invalid <u>Section</u> (invalid new section title).';
        } elseif (@mysql_result(mysql_query("SELECT COUNT(*) FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name != '$parameterName' AND section_id = '$POST[section_id]'"), 0)) {
            $errors['section_id'] = 'Invalid <u>Section</u> (an iteratable parameter must belong to a section that is otherwise empty).';
        }
    }
    
    for ($i = 0; $i != count($POST['data']); $i++) {
        if (count($POST['data']) > 1) {
            if (!preg_match('/^[a-z][a-z0-9_]*[a-z0-9]$/', $POST['data'][$i]['param_data_name'])) {
                $errors['data[' . $i . '][param_data_name]'] = 'Invalid <u>Data Name</u>.';
            } elseif (@in_array($POST['data'][$i]['param_data_name'], $parameterDataNames)) {
                $errors['data[' . $i . '][param_data_name]'] = 'Invalid <u>Data Name</u> (already assigned to another parameter data).';
            }
        }
        
        if (!$POST['data'][$i]['caption']) {
            $errors['data[' . $i . '][caption]'] = 'Invalid <u>Caption</u>.';
        }
        
        if ($POST['data'][$i]['type'] == 'select' || $POST['data'][$i]['type'] == 'radio') {
            //$POST['data[' . $i . '][options]'] = getNonEmptyIterations($POST['data'][$i]['options']);
            
            if (!$POST['data'][$i]['options']) {
                $errors['data[' . $i . '][options]'] = 'A multiple options type must have at least one option.';
            }
            
            $is_selected = false;
            for ($j = 0; $j != count($POST['data'][$i]['options']); $j++) {
                if ($POST['data'][$i]['options'][$j]['is_selected']) {
                    if ($is_selected) {
                        $errors['data[' . $i . '][options][' . $j . '][is_selected]'] = 'Invalid <u>Option</u> (another option is already selected as the default option).';
                    } else {
                        $is_selected = true;
                    }
                }
            }
        }
        
        if ($POST['data'][$i]['type'] == 'selectparam' && !$POST['data'][$i]['type__selectparam__param']) {
            $errors['data[' . $i . '][type__selectparam__param]'] = 'Invalid data-source param.';
        }
        
        $parameterDataNames[] = $POST['data'][$i]['param_data_name'];
    }
    
    return $errors ? false : true;
}

function saveParameter($parameter, $POST, &$error) {
    list($docType, $parameterName) = @explode('@', $parameter);
    
    if (!$parameterName) {
        $docType = $POST['doc_type'];
    }
    
    $sql_errors = false;
    
    mysql_query("BEGIN");
    
    ##
    
    if ($parameterName) {
        // If the parameter is iteratable, update existing non-iteratable values to be 'iteration 0'; otherwise remove all iterated values except the first one.
        if ($POST['is_iteratable']) {
            mysql_query("UPDATE sys_docs_params SET iteration = '0' WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND iteration IS NULL");
        } else {
            mysql_query("UPDATE sys_docs_params SET iteration = NULL WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND iteration = '0'");
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND iteration IS NOT NULL");
        }
        
        // Remap old parameter data names to new ones.
        for ($i = 0; $i != count($POST['data']); $i++) {
            mysql_query("UPDATE sys_docs_params SET param_data_name = '" . $POST['data'][$i]['param_data_name'] . "' WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND param_data_name = '" . $POST['data'][$i]['param_data_name_old'] . "'");
            
            
            $parameterDataNames[] = $POST['data'][$i]['param_data_name'];
        }
        
        // Delete all non-existent parameter data from all docs.
        // Switching to either single- or multiple-data from the other kind, deletes all existing parameter data.
        if (count($POST['data']) > 1) {
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND (param_data_name IS NULL OR param_data_name NOT IN ('" . implode("', '", $parameterDataNames) . "'))");
        } else {
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName' AND param_data_name IS NOT NULL");
        }
        
        // If renamed, rename in all docs and selectparams.
        if ($parameterName != $POST['param_name']) {
            mysql_query("UPDATE sys_docs_params SET param_name = '$POST[param_name]' WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$parameterName'");
            mysql_query("UPDATE sys_docs_types_params_data SET type__selectparam__param = '" . $docType . "@" . $POST['param_name'] . "' WHERE type__selectparam__param = '" . $docType . "@" . $parameterName . "'");
        }
    }
    
    #
    
    mysql_query("DELETE FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$parameterName'");
    mysql_query("DELETE FROM sys_docs_types_params_data WHERE doc_type = '$docType' AND param_name = '$parameterName'");
    mysql_query("DELETE FROM sys_docs_types_params_data_options WHERE doc_type = '$docType' AND param_name = '$parameterName'");
    mysql_query("DELETE FROM sys_docs_types_params__subtypes WHERE doc_type = '$docType' AND param_name = '$parameterName'");
    
    for ($i = 0; $i != count($POST['subtypes']); $i++) {
        mysql_query("INSERT INTO sys_docs_types_params__subtypes (doc_type, doc_subtype, param_name) VALUES ('$docType', '" . $POST['subtypes'][$i] . "', '$POST[param_name]')");
    }
    
    if ($POST['section_id'] == '@new') {
        $sectionId_tmp = @mysql_result(mysql_query("SELECT IFNULL(MAX(section_id), 0) + 1 FROM sys_docs_types_params_sections WHERE doc_type = '$docType'"), 0);
        mysql_query("INSERT INTO sys_docs_types_params_sections (doc_type, section_id, title) VALUES ('$docType', '$sectionId_tmp', '" . $POST['section_new'] . "')") || $sql_errors[] = mysql_error();
        $sectionId = "'" . $sectionId_tmp . "'";
    } else {
        $sectionId = ($POST['section_id'] == '') ? "NULL" : "'" . $POST['section_id'] . "'";
    }
    mysql_query("INSERT INTO sys_docs_types_params (doc_type, param_name, section_id, is_iteratable, idx) VALUES ('$docType', '" . $POST['param_name'] . "', $sectionId, '" . $POST['is_iteratable'] . "', '" . $POST['idx'] . "')") || $sql_errors[] = mysql_error();
    
    for ($i = 0; $i != count($POST['data']); $i++) {
        $paramDataName = (count($POST['data']) == 1) ? "NULL" : "'" . $POST['data'][$i]['param_data_name'] . "'";
        $annotation = ($POST['data'][$i]['annotation'] == '') ? "NULL" : "'" . $POST['data'][$i]['annotation'] . "'";
        
        if ($POST['data'][$i]['type'] == 'text') {
            $type__text__maxlength = (preg_match('/^\d+$/', $POST['data'][$i]['type__text__maxlength']) && $POST['data'][$i]['type__text__maxlength'] <= 255) ? "'" . $POST['data'][$i]['type__text__maxlength'] . "'" : "'255'";
        } else {
            $type__text__maxlength = "NULL";
        }
        
        if ($POST['data'][$i]['type'] == 'selectparam') {
            $type__selectparam__param = "'" . $POST['data'][$i]['type__selectparam__param'] . "'";
        } else {
            $type__selectparam__param = "NULL";
        }
        
        mysql_query("INSERT INTO sys_docs_types_params_data (doc_type, param_name, param_data_name, caption, annotation, type, type__text__maxlength, type__selectparam__param, idx) VALUES ('$docType', '" . $POST['param_name'] . "', $paramDataName, '" . $POST['data'][$i]['caption'] . "', $annotation, '" . $POST['data'][$i]['type'] . "', $type__text__maxlength, $type__selectparam__param, '" . ($i + 1) . "')") || $sql_errors[] = mysql_error();
        
        if ($POST['data'][$i]['type'] == 'select' || $POST['data'][$i]['type'] == 'radio') {
            for ($j = 0; $j != count($POST['data'][$i]['options']); $j++) {
                mysql_query("INSERT INTO sys_docs_types_params_data_options (doc_type, param_name, param_data_name, param_data_option_value, text, is_selected, idx) VALUES ('$docType', '" . $POST['param_name'] . "', '" . $POST['data'][$i]['param_data_name'] . "', '" . $POST['data'][$i]['options'][$j]['value'] . "', '" . $POST['data'][$i]['options'][$j]['text'] . "', '" . $POST['data'][$i]['options'][$j]['is_selected'] . "', '" . ($j + 1) . "')") || $sql_errors[] = mysql_error();
                
                $parameterDataOptions[] = $POST['data'][$i]['options'][$j]['value'];
            }
            
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id IN (SELECT doc_id FROM sys_docs WHERE doc_type = '$docType') AND param_name = '$POST[param_name]' AND param_data_name = $paramDataName AND (value_short NOT IN ('" . implode("', '", $parameterDataOptions) . "') OR value_long NOT IN ('" . implode("', '", $parameterDataOptions) . "'))");
        }
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

function deleteParameter($parameter, &$error) {
    if (validateParameterName($parameter)) {
        list($docType, $parameterName) = @explode('@', $parameter);
        
        mysql_query("DELETE FROM sys_docs_types_params WHERE doc_type = '$docType' AND param_name = '$parameterName'");
        mysql_query("DELETE FROM sys_docs_types_params_data WHERE doc_type = '$docType' AND param_name = '$parameterName'");
        mysql_query("DELETE FROM sys_docs_types_params_data_options WHERE doc_type = '$docType' AND param_name = '$parameterName'");
        mysql_query("DELETE FROM sys_docs_types_params__subtypes WHERE doc_type = '$docType' AND param_name = '$parameterName'");
        
        // Delete the parameter from all docs.
        $sql_query = mysql_query("SELECT doc_id FROM sys_docs WHERE doc_type = '$docType'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$sql[doc_id]' AND param_name = '$parameterName'");
        }
        
        $sql_query = mysql_query("SELECT doc_type, param_name FROM sys_docs_types_params_data WHERE type__selectparam__param = '" . $docType . "@" . $parameterName . "'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $sub_sql_query = mysql_query("SELECT doc_id FROM sys_docs WHERE doc_type = '$sql[doc_type]'");
            while ($sub_sql = mysql_fetch_assoc($sub_sql_query)) {
                mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$sub_sql[doc_id]' AND param_name = '$sql[param_name]'");
            }
        }
        mysql_query("UPDATE sys_docs_types_params_data SET type__selectparam__param = NULL WHERE type__selectparam__param = '" . $docType . "@" . $parameterName . "'");
        
        return true;
    } else {
        $error = 'Invalid parameter.';
        return false;
    }
}

function renderParameterForm($parameter, $_FORM = false, $errors = false) {
    list($docType, $parameterName) = @explode('@', $parameter);
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Doc Types / Parameters</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-form.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-form.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        var doc_subtypes = {};
        <?php
        $sql_query = mysql_query("SELECT doc_type, doc_subtype, title FROM sys_docs_subtypes ORDER BY doc_type ASC, idx ASC");
        for ($i = 0; $sql = mysql_fetch_assoc($sql_query); $i++) {
            $doc_types_subtypes__js[$sql['doc_type']][] = "{ doc_subtype: '" . $sql['doc_subtype'] . "', title: '" . str_replace("'", "\'", $sql['title']) . "' }";
        }
        foreach ($doc_types_subtypes__js as $doc_type => $doc_type_subtypes) {
            echo "doc_subtypes['" . $doc_type . "'] = [" . implode(', ', $doc_type_subtypes) . "];" . "\n";
        }
        ?>
        
        var sections = new Array();
        <?php
        $sql_query = mysql_query("SELECT doc_type, section_id, title FROM sys_docs_types_params_sections ORDER BY doc_type ASC, section_id ASC");
        for ($i = 0; $sql = mysql_fetch_assoc($sql_query); $i++) {
            echo "sections[" . $i . "] = Array('" . $sql['doc_type'] . "', '" . $sql['section_id'] . "', '" . str_replace("'", "\'", $sql['title']) . "');" . "\n";
        }
        ?>
        
        var selectparams = new Array();
        <?php
        $sql_query = mysql_query("SELECT sdtp.doc_type, sdtp.param_name FROM sys_docs_types_params sdtp LEFT JOIN sys_docs_types_params_data sdtpd ON sdtp.doc_type = sdtpd.doc_type AND sdtp.param_name = sdtpd.param_name WHERE is_iteratable = '1' AND type = 'text' AND param_data_name IS NULL ORDER BY doc_type ASC, param_name ASC");
        for ($i = 0; $sql = mysql_fetch_assoc($sql_query); $i++) {
            echo "selectparams[" . $i . "] = '" . str_replace("'", "\'", $sql['doc_type']) . "@" . str_replace("'", "\'", $sql['param_name']) . "';" . "\n";
        }
        ?>
        
        function updateParameterDocType(docType, subtypes, sectionId) {
            var subtypes_section_content = document.getElementById('subtypes-section-content');
            if (doc_subtypes[docType]) {
                subtypes_section_content.innerHTML = '';
                for (var i = 0, checked = false; i != doc_subtypes[docType].length; i++) {
                    checked = false;
                    if (subtypes) {
                        for (var j = 0; j != subtypes.length; j++) {
                            if (subtypes[j] == doc_subtypes[docType][i]['doc_subtype']) {
                                checked = true;
                            }
                        }
                    }
                    subtypes_section_content.innerHTML += '<div><label><input type="checkbox" name="subtypes[]" value="' + doc_subtypes[docType][i]['doc_subtype'] + '" ' + (checked ? 'checked' : '') + '> ' + doc_subtypes[docType][i]['title'] + '</label></div>';
                }
                
                document.getElementById('subtypes-section').style.display = '';
            } else {
                subtypes_section_content.innerHTML = '';
                document.getElementById('subtypes-section').style.display = 'none';
            }
            
            var sections_selectbox = document.getElementById('section_id');
            while (sections_selectbox.length > 2) {
                sections_selectbox.removeChild(sections_selectbox.lastChild);
            }
            for (var i = 0; i != sections.length; i++) {
                if (sections[i][0] == docType) {
                    var option = document.createElement('OPTION');
                    option.value = sections[i][1];
                    option.text = sections[i][2];
                    if (sections[i][1] == sectionId) {
                        option.setAttribute('selected', true);
                    }
                    sections_selectbox.options.add(option);
                }
            }
            
            if (sectionId == '@new') {
                sections_selectbox.selectedIndex = 1; // "[ new ]"
                document.getElementById('section_new').style.display = '';
            }
        }
        
        function updateParameterDataNames() {
            var iteration = document.getElementById('section__doc_type_parameter_data');
            var items = iteration.getElementsByTagName('INPUT');
            var items_tmp = new Array();
            for (var i = 0; i != items.length; i++) {
                if (/^data\[\d+\]\[param_data_name\]$/.test(items[i].getAttribute('name'))) {
                    items_tmp[items_tmp.length] = items[i];
                }
            }
            items = items_tmp;
            
            for (var i = 0; i != items.length; i++) {
                if (items.length > 1) {
                    items[i].parentNode.parentNode.style.display = '';
                } else {
                    items[i].parentNode.parentNode.style.display = 'none';
                    items[i].value = '';
                }
            }
        }
        
        function updateParameterDataType() {
            var items = document.getElementsByTagName('DIV');
            for (var i = 0; i != items.length; i++) {
                if (/^data\[\d+\]$/.test(items[i].getAttribute('arrayname'))) {
                    var inputs = items[i].getElementsByTagName('SELECT');
                    
                    for (var j = 0; j != inputs.length; j++) {
                        if (/^data\[\d+\]\[type\]$/.test(inputs[j].getAttribute('name'))) {
                            
                            // Show/hide options depending on the parameter type.
                            var options = items[i].getElementsByTagName('DIV');
                            for (var k = 0; k != options.length; k++) {
                                if (/^data\[\d+\]\[options\]\[\d+\]$/.test(options[k].getAttribute('arrayname'))) {
                                    if (inputs[j].value == 'select' || inputs[j].value == 'radio') {
                                        options[k].style.display = '';
                                    } else {
                                        options[k].style.display = 'none';
                                    }
                                }
                            }
                            
                            // Show/hide inputs relevant to 'text' type parameters.
                            var inputs_tmp = items[i].getElementsByTagName('INPUT');
                            for (var k = 0; k != inputs_tmp.length; k++) {
                                if (/^data\[\d+\]\[type__text__maxlength\]$/.test(inputs_tmp[k].getAttribute('name'))) {
                                    if (inputs[j].value == 'text') {
                                        inputs_tmp[k].parentNode.parentNode.style.display = '';
                                    } else {
                                        inputs_tmp[k].parentNode.parentNode.style.display = 'none';
                                    }
                                }
                            }
                            
                            // Show/hide inputs relevant to 'selectparam' type parameters.
                            var selects_tmp = items[i].getElementsByTagName('SELECT');
                            for (var k = 0; k != selects_tmp.length; k++) {
                                if (/^data\[\d+\]\[type__selectparam__param\]$/.test(selects_tmp[k].getAttribute('name'))) {
                                    while (selects_tmp[k].length > 0) {
                                        selects_tmp[k].removeChild(selects_tmp[k].lastChild);
                                    }
                                    
                                    if (inputs[j].value == 'selectparam') {
                                        for (var m = 0; m != selectparams.length; m++) {
                                            var option = document.createElement('OPTION');
                                            option.value = selectparams[m];
                                            option.text = selectparams[m];
                                            if (selectparams[m] == selects_tmp[k].getAttribute('selected')) {
                                                option.setAttribute('selected', true);
                                            }
                                            selects_tmp[k].options.add(option);
                                        }
                                        
                                        selects_tmp[k].parentNode.parentNode.style.display = '';
                                    } else {
                                        selects_tmp[k].parentNode.parentNode.style.display = 'none';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    <form method="POST" id="adminForm" onsubmit="if (document.getElementById('save_as_new').value == 0 && '<?=$docType?>' != '' && '<?=$docType?>' != document.getElementById('doc_type').value) { alert('If the doc-type was altered, the parameter can only be &quot;saved as new.&quot;'); return false; }">
    <input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
    <input type="hidden" name="save-as-new" id="save_as_new" value="0">
    <input type="hidden" name="idx" value="<?=$_FORM['idx']?>">
    
    <div class="location-bar">
        <span id="collapse-all-icon" onclick="toggleSectionDisplay_all()">[-]</span>
        <a href="<?=href('/')?>">Main</a>
        / <a href="<?=href('/?doc=admin')?>">Administration</a>
        / <a href="<?=href('/?doc=admin/doc-types')?>">Doc Types</a>
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Parameters</a>
        / <?=($parameterName ? 'Name: ' . $parameterName : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__parameters_general">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">general</div>
        </div>
        
        <table class="section">
            <tr>
                <td class="col a"><div class="caption">doc type</div></td>
                <td class="col b">
                    <select id="doc_type" name="doc_type" onchange="updateParameterDocType(this.value)" style="font-variant: small-caps;">
                        <?php
                        $sql_query = mysql_query("SELECT type FROM sys_docs_types ORDER BY type ASC");
                        while ($sql = mysql_fetch_assoc($sql_query)) {
                            $selected = ($_FORM['doc_type'] == $sql['type']) ? 'selected' : false;
                            echo '<option value="' . $sql['type'] . '" ' . $selected . '>' . $sql['type'] . '</option>' . "\n";
                        }
                        ?>
                    </select>
                </td>
                <td class="col c"><div class="annotation">Changing the doc-type will delete this parameter from all relevant docs.</div></td>
            </tr>
            <tr id="subtypes-section">
                <td class="col a"><div class="caption">subtypes</div></td>
                <td class="col b" id="subtypes-section-content"></td>
                <td class="col c"><div class="annotation">Unchecking a subtype will <em>eventually</em> delete all existing parameters belonging to docs of that subtype. Said parameters will be deleted only when the relevant docs are altered.</div></td>
            </tr>
            <tr>
                <td class="col a"><div class="caption">section</div></td>
                <td class="col b">
                    <select name="section_id" id="section_id" onchange="if (this.value == '@new') { document.getElementById('section_new').style.display = ''; document.getElementById('section_new').focus() } else { document.getElementById('section_new').style.display = 'none'; }">
                        <option value="">&nbsp;</option>
                        <option value="@new">[ new ]</option>
                    </select>
                    <p><input type="text" name="section_new" id="section_new" value="<?=$_FORM['section_new']?>" maxlength="50" style="width: 325px; display: none;">
                </td>
                <td class="col c"><div class="annotation">Changing the section will only affect the location of the parameter in the relevant doc-type admin page.</div></td>
            </tr>
            <script>updateParameterDocType('<?=$_FORM['doc_type']?>', ['<?=@implode("', '", $_FORM['subtypes'])?>'], '<?=$_FORM['section_id']?>')</script>
            
            <tr>
                <td class="col a"><div class="caption">name</div></td>
                <td class="col b"><input type="text" name="param_name" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['param_name'])?>" style="width: 175px;"></td>
                <td class="col c"><div class="annotation">Chaning the name will rename all this parameter for all relevant docs. The name must begin with a letter and may also contain numbers and underscores.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">iteratable</div></td>
                <td class="col b"><input type="checkbox" name="is_iteratable" value="1" <?=($_FORM['is_iteratable'] ? 'checked' : false)?>></td>
                <td class="col c"><div class="annotation">An iteratable parameter must belong to an otherwise empty section.</td>
            </tr>
        </table>
    </div>
    
    <div class="section" id="section__doc_type_parameter_data">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">data</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['data']) > 0 ? count($_FORM['data']) : 1); $i++): ?>
        <div class="iteration" arrayname="data[<?=$i?>]">
            <br>
            
            <div class="iteration-header">
                <div class="index" onclick="iteration_moveByIdx(this)">#<?=($i < 9 ? '0' . ($i+1) : $i+1)?></div>
                <div class="actions">
                    <span onclick="iteration_addAfter(this); updateParameterDataNames(); updateParameterDataType();">add after</span>
                    | <span onclick="iteration_addBefore(this); updateParameterDataNames(); updateParameterDataType();">add before</span>
                    | <span onclick="iteration_moveUp(this)">move up</span>
                    | <span onclick="iteration_moveDown(this)">move down</span>
                    | <span onclick="iteration_clear(this)">clear</span>
                    | <span onclick="iteration_delete(this); updateParameterDataNames();">delete</span>
                </div>
            </div>
            
            <table class="section">
                <tr>
                    <td class="col a"><div class="caption">name</div></td>
                    <td class="col b">
                        <input type="hidden" name="data[<?=$i?>][param_data_name_old]" value="<?=htmlspecialchars($_FORM['data'][$i]['param_data_name_old'])?>">
                        <input type="text" name="data[<?=$i?>][param_data_name]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['data'][$i]['param_data_name'])?>" style="width: 175px;">
                    </td>
                    <td class="col c"><div class="annotation">Required if there is more than one datum. Chaning the parameter data name will rename all existing instances of this parameter data in all relevant docs. To avoid this, clear or delete the parameter data first. The name must begin with a letter and may also contain numbers and underscores.</div></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">caption</div></td>
                    <td class="col b"><input type="text" name="data[<?=$i?>][caption]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['data'][$i]['caption'])?>" style="width: 325px;"></td>
                    <td class="col c"><div class="annotation">The text in the left-most column.</div></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">annotation</div></td>
                    <td class="col b"><textarea name="data[<?=$i?>][annotation]" class="textarea"><?=htmlspecialchars($_FORM['data'][$i]['annotation'])?></textarea></td>
                    <td class="col c"><div class="annotation">The optional text that appears in the right-most column. Used to specify addtional information about an input field. This text is the annotation of this field. Not shown if the type is "htmlarea".</div></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">type</div></td>
                    <td class="col b">
                        <? $selected = array($_FORM['data'][$i]['type'] => 'selected'); ?>
                        <select name="data[<?=$i?>][type]" onchange="updateParameterDataType()" class="select">
                            <option value="text" <?=$selected['text']?>>text (up to 255 characters)</option>
                            <option value="textarea" <?=$selected['textarea']?>>textarea</option>
                            <option value="htmlarea" <?=$selected['htmlarea']?>>htmlarea</option>
                            <option value="checkbox" <?=$selected['checkbox']?>>checkbox (binary value)</option>
                            <option value="select" <?=$selected['select']?>>select (multiple options)</option>
                            <option value="selectparam" <?=$selected['selectparam']?>>select (multiple options from another param)</option>
                            <option value="radio" <?=$selected['radio']?>>radio (multiple options)</option>
                            <option value="file" <?=$selected['file']?>>file (GFM)</option>
                        </select>
                    </td>
                    <td class="col c"><div class="annotation">Changing the parameter type will, in most cases, delete its value from all instances of the paramter in all relevant docs.</div></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">max length</div></td>
                    <td class="col b"><input type="text" name="data[<?=$i?>][type__text__maxlength]" maxlength="3" class="text" value="<?=htmlspecialchars($_FORM['data'][$i]['type__text__maxlength'])?>" style="width: 85px;"></td>
                    <td class="col c"><div class="annotation">The maximum length allowed in the text input, up to 255.</div></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">data-source param</div></td>
                    <td class="col b">
                        <select name="data[<?=$i?>][type__selectparam__param]" class="select" selected="<?=str_replace('"', '\"', $_FORM['data'][$i]['type__selectparam__param'])?>"></select> <!-- ' -->
                    </td>
                    <td class="col c"><div class="annotation">The single-data iteratable param of type 'text' that is used as the data-source.</div></td>
                </tr>
            </table>
            
            <? for ($j = 0; $j != (count($_FORM['data'][$i]['options']) > 0 ? count($_FORM['data'][$i]['options']) : 1); $j++): ?>
            <div class="iteration" arrayname="data[<?=$i?>][options][<?=$j?>]">
                <div class="iteration-header nested">
                    <div class="index" onclick="iteration_moveByIdx(this)">#<?=($j < 9 ? '0' . ($j+1) : $j+1)?></div>
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
                        <td class="col a"><div class="caption">value</div></td>
                        <td class="col b"><input type="text" name="data[<?=$i?>][options][<?=$j?>][value]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['data'][$i]['options'][$j]['value'])?>" style="width: 325px;"></td>
                        <td class="col c"><div class="annotation">The fixed value of the option. If changed, this parameter data value will be deleted from the relevant docs that have it.</div></td>
                    </tr>
                    
                    <tr>
                        <td class="col a"><div class="caption">text</div></td>
                        <td class="col b"><input type="text" name="data[<?=$i?>][options][<?=$j?>][text]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['data'][$i]['options'][$j]['text'])?>" style="width: 325px;"></td>
                        <td class="col c"><div class="annotation">The text to be shown and selected.</div></td>
                    </tr>
                    
                    <tr>
                        <td class="col a"><div class="caption">selected</div></td>
                        <td class="col b"><input type="checkbox" name="data[<?=$i?>][options][<?=$j?>][is_selected]" value="1" <?=($_FORM['data'][$i]['options'][$j]['is_selected'] ? 'checked' : false)?>></td>
                        <td class="col c"><div class="annotation">Mark this as the default option.</div></td>
                    </tr>
                </table>
            </div>
            <? endfor; ?>
        </div>
        <? endfor; ?>
    </div>
    
    <script>updateParameterDataNames(); updateParameterDataType();</script>
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($parameterName): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&parameter=<?=$docType . '@' . $parameterName?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
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

function renderParametersList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Doc Types / Parameters</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        
        <script type="text/javascript">
        var uri = '<?=href('/?' . $_SERVER['QUERY_STRING'])?>' + window.location.hash.replace(/^#/, '&');
        
        var items_metad = new Array(
            Array(null, '&nbsp;'),
            Array(null, 'Doc Type'),
            Array(null, 'Name'),
            Array(null, 'Idx'),
            Array(null, 'Section'),
            Array(null, 'Multiple'),
            Array(null, 'Iteratable')
        );
        
        /* ## */
        
        function adminList_updateList__central(XMLDocument) {
            var items = XMLDocument.getElementsByTagName('item');
            var list = new Array();
            
            for (var i = 0, checked; i != items.length; i++) {
                checked = ((!items_inver && inArray(items[i].getAttribute('id'), items_array)) || (items_inver && !inArray(items[i].getAttribute('id'), items_array))) ? 'checked' : '';
                
                list[i] = '<tr class="list ' + ((list.length == 0) ? 'first' : '') + '">'
                        +     '<td class="first check"><input type="checkbox" name="items[]" value="' + items[i].getAttribute('id') + '" onclick="adminList_selectItem(this)" ' + checked + ' style="margin: 0;"></td>'
                        +     '<td style="font-variant: small-caps; white-space: nowrap;">' + items[i].getAttribute('doc_type') + '</td>'
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&parameter=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('param_name') + '</a></td>'
                        +     '<td style="text-align: center;"><input type="text" name="idx[' + items[i].getAttribute('id') + ']" value="' + items[i].getAttribute('idx') + '" class="idx"></td>'
                        +     '<td style="width: 100%;">' + items[i].getAttribute('section') + '</td>'
                        +     '<td style="white-space: nowrap; text-align: center;">' + (items[i].getAttribute('is_multiple') != '0' ? '<img src="/repository/admin/images/check-icon.gif">' : '') + '</td>'
                        +     '<td style="white-space: nowrap; text-align: center;">' + (items[i].getAttribute('is_iteratable') != '0' ? '<img src="/repository/admin/images/check-icon.gif">' : '') + '</td>'
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
    <input type="hidden" id="adminList_action" name="act">
    <input type="hidden" id="adminList_items_inver" name="adminList_items_inver">
    <input type="hidden" id="adminList_items_array" name="adminList_items_array">
    <table>
        <thead>
            <tr class="path">
                <td>
                    <a href="<?=href('/')?>">Main</a>
                    / <a href="<?=href('/?doc=admin')?>">Administration</a>
                    / <a href="<?=href('/?doc=admin/doc-types')?>">Doc Types</a>
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Parameters</a>
                </td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Save" accesskey="s" class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'save-idx'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); this.blur();">
                    <input type="button" value="Add Parameter" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
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
        
        <tfoot>
            <tr>
                <td>
                    <input type="button" value="Save" accesskey="s" class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'save-idx'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); this.blur();">
                    <input type="button" value="Add Parameter" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
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
    echo '    <legend total="' . $total . '" limit="' . $limit . '" page="' . $page . '" order="" sort="" query="' . xmlentities(stripslashes($_GET['query'])) . '"/>' . "\n\n";
    for ($i = 0, $j = $offset; (!$limit || $i != $limit) && $j != $total && ($item = $list[$j]); $i++, $j++) {
        $item['is_multiple'] = @mysql_result(mysql_query("SELECT COUNT(*) > 1 FROM sys_docs_types_params_data WHERE doc_type = '$item[doc_type]' AND param_name = '$item[param_name]'"), 0);
        
        echo '    ';
        echo '<item';
        echo ' id="' . xmlentities($item['doc_type'] . '@' . $item['param_name']) . '"';
        echo ' doc_type="' . xmlentities($item['doc_type']) . '"';
        echo ' param_name="' . xmlentities($item['param_name']) . '"';
        echo ' idx="' . $item['idx'] . '"';
        echo ' section="' . xmlentities($item['section']) . '"';
        echo ' is_multiple="' . $item['is_multiple'] . '"';
        echo ' is_iteratable="' . $item['is_iteratable'] . '"';
        echo '/>' . "\n";
    }
    echo '</XMLResult>' . "\n";
    
    return ob_get_clean();
}

function renderListDocs() {
    if ($_GET['query']) {
        $searchArray = array('doc_type', 'param_name', 'section');
        
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
    
    $sql_query = mysql_query("SELECT doc_type, param_name, (SELECT title FROM sys_docs_types_params_sections WHERE doc_type = sdtp.doc_type AND section_id = sdtp.section_id) AS section, is_iteratable, idx FROM sys_docs_types_params sdtp $HAVING ORDER BY doc_type ASC, section_id ASC, idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    return array($list, $order, $sort);
}

?>