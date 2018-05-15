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
        mysql_query("UPDATE sys_docs SET doc_note = " . SQLNULL(mysql_real_escape_string(stripslashes(trim($_GET['note'])))) . " WHERE doc_id = '" . mysql_real_escape_string($_GET['doc_id']) . "' AND doc_type = 'user'");
    }
    
    echo renderListAJAX();
    exit;
}

if ($_GET['user']) {
    if (!$userId = validateUserId($_GET['user'])) {
        header('Location: ' . href('/?doc=' . constant('DOC')));
        exit;
    }
}

// All the admin-access pages (for tech-admins).
$adminPages = array(
    'admin/elements' => 'Elements',
    'admin/groups' => 'Groups',
    'admin/documents' => 'Documents',
    'admin/objects' => 'Objects',
    'admin/products' => 'Products',
    'admin/articles' => 'Articles',
    'admin/auctions' => 'Auctions',
    'admin/posts' => 'Posts',
    'admin/licenses' => 'Licenses',
    'admin/activkeys' => 'Activekeys',
    'admin/polls' => 'Polls',
    'admin/quizzes' => 'Quizzes',
    'admin/exams' => 'Exams',
    'admin/users' => 'Users',
    'admin/subscribers' => 'Subscribers',
    'admin/memberzones' => 'Memberzones',
    'admin/newsletters' => 'Newsletters',
    'admin/newsletters-log' => 'Newsletters Log',
    'admin/send-newsletter' => 'Send Newsletter',
    'admin/send-sms' => 'Send SMS',
    'admin/talkbacks' => 'Talkbacks',
    'admin/reviews' => 'Reviews',
    'admin/reviews/feature-sets' => 'Reviews Feature Sets',
    'admin/ads' => 'Ads',
    'admin/coupons' => 'Coupons',
    'admin/shipping-methods' => 'Shipping Methods',
    'admin/orders' => 'Orders',
    'admin/credits' => 'Credits Trans.',
    'admin/affiliates' => 'Affiliates',
    'admin/affiliates/programs' => 'Affiliates Programs',
    'admin/verifone-locations' => 'VeriFone Locations',
    'admin/verifone-tickets' => 'VeriFone Tickets',
    'admin/verifone-transactions' => 'VeriFone Trans.',
    'admin/verifone-isracard-users' => 'Isracard Users'
);
asort($adminPages);

// Restrict the existing admin-access pages only to those the current content-admin has access to.
if ($_SESSION['user']['type'] == 'content-admin') {
    $sql_query = mysql_query("SELECT doc_name FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $adminPages_tmp[] = $sql['doc_name'];
    }
    
    foreach ($adminPages as $docName => $null) {
        if (!@in_array($docName, $adminPages_tmp)) {
            unset($adminPages[$docName]);
        }
    }
}

/* ## */

$importExportMeta = array(
    array('db' => 'user_id', 'caption' => '[#] Doc Id', 'type' => 'variable'),
    array('db' => 'doc_name', 'caption' => 'Doc Name', 'type' => 'variable'),
    array('db' => 'first_name', 'caption' => 'First Name', 'type' => 'variable'),
    array('db' => 'last_name', 'caption' => 'Last Name', 'type' => 'variable'),
    array('db' => 'email', 'caption' => 'Email', 'type' => 'variable'),
    array('db' => 'password', 'caption' => 'Password', 'type' => 'variable'),
    array('db' => 'company', 'caption' => 'Company', 'type' => 'variable'),
    array('db' => 'job_title', 'caption' => 'Job Title', 'type' => 'variable'),
    array('db' => 'date_of_birth', 'caption' => 'Date of Birth', 'type' => 'variable'),
    array('db' => 'street_1', 'caption' => 'Street 1', 'type' => 'variable'),
    array('db' => 'street_2', 'caption' => 'Street 2', 'type' => 'variable'),
    array('db' => 'city', 'caption' => 'City', 'type' => 'variable'),
    array('db' => 'state', 'caption' => 'State', 'type' => 'variable'),
    array('db' => 'zipcode', 'caption' => 'Zipcode', 'type' => 'variable'),
    array('db' => 'country', 'caption' => 'Country', 'type' => 'variable'),
    array('db' => 'phone_1', 'caption' => 'Phone 1', 'type' => 'variable'),
    array('db' => 'phone_2', 'caption' => 'Phone 2', 'type' => 'variable'),
    array('db' => 'mobile', 'caption' => 'Mobile', 'type' => 'variable'),
    array('db' => 'seo_title', 'caption' => 'SEO Title', 'type' => 'variable'),
    array('db' => 'seo_description', 'caption' => 'SEO Description', 'type' => 'variable'),
    array('db' => 'seo_keywords', 'caption' => 'SEO Keywords', 'type' => 'variable'),
    array('db' => 'send_newsletters', 'caption' => 'Recieve Newsletters', 'type' => 'variable'),
    array('db' => 'send_notifications', 'caption' => 'Recieve Notifications', 'type' => 'variable'),
    array('db' => 'fset_id', 'caption' => 'Review Feature Set Id', 'type' => 'variable'),
    array('db' => 'doc_note', 'caption' => 'Doc Note', 'type' => 'variable'),
    
    array('db' => 'addresses', 'type' => array(
        array('db' => 'first_name', 'caption' => '[Addresses] First Name', 'type' => 'variable'),
        array('db' => 'last_name', 'caption' => '[Addresses] Last Name', 'type' => 'variable'),
        array('db' => 'company', 'caption' => '[Addresses] Company', 'type' => 'variable'),
        array('db' => 'job_title', 'caption' => '[Addresses] Job Title', 'type' => 'variable'),
        array('db' => 'street_1', 'caption' => '[Addresses] Street 1', 'type' => 'variable'),
        array('db' => 'street_2', 'caption' => '[Addresses] Street 2', 'type' => 'variable'),
        array('db' => 'city', 'caption' => '[Addresses] City', 'type' => 'variable'),
        array('db' => 'state', 'caption' => '[Addresses] State', 'type' => 'variable'),
        array('db' => 'zipcode', 'caption' => '[Addresses] Zipcode', 'type' => 'variable'),
        array('db' => 'country', 'caption' => '[Addresses] Country', 'type' => 'variable'),
        array('db' => 'phone_1', 'caption' => '[Addresses] Phone 1', 'type' => 'variable'),
        array('db' => 'phone_2', 'caption' => '[Addresses] Phone 2', 'type' => 'variable'),
    )),
    
    array('db' => 'memberzones', 'caption' => '[Memberzones]', 'type' => 'array'),
);
embedDocTypeParametersIntoImportExportMeta('user', $importExportMeta);

if ($_POST['act'] == 'delete' || $_POST['act'] == 'make-admin' || $_POST['act'] == 'unmake-admin' || $_POST['act'] == 'export-selected') {
    $inver = $_POST['adminList_items_inver'];
    $items = $_POST['adminList_items_array'] ? explode(',', $_POST['adminList_items_array']) : array();
    
    if ($inver) {
        list($list) = renderListDocs();
        for ($i = 0; $i != count($list); $i++) {
            $itemsAll[] = $list[$i]['user_id'];
        }
        $items = array_values(array_diff($itemsAll, $items));
    }
    
    if ($_POST['act'] == 'delete') {
        for ($i = 0; $i != count($items); $i++) {
            if (!deleteUser($items[$i], $deleteUser__error)) {
                abort($deleteUser__error, 'deleteUser', __FILE__, __LINE__);
            }
        }
    } elseif ($_POST['act'] == 'make-admin' && ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin')) {
        for ($i = 0; $i != count($items); $i++) {
            mysql_query("UPDATE sys_docs SET doc_subtype = 'content-admin' WHERE doc_type = 'user' AND doc_id = '" . $items[$i] . "'");
        }
    } elseif ($_POST['act'] == 'unmake-admin' && ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin')) {
        for ($i = 0; $i != count($items); $i++) {
            // Do not allow a content-admin to unmark himself as a content-admin.
            if ($items[$i] != $_SESSION['user']['user_id']) {
                mysql_query("UPDATE sys_docs SET doc_subtype = 'user' WHERE doc_type = 'user' AND doc_subtype = 'content-admin' AND doc_id = '" . $items[$i] . "'");
                mysql_query("DELETE FROM type_user__admin_access WHERE user_id = '" . $items[$i] . "'");
            }
        }
    } elseif ($_POST['act'] == 'export-selected') {
        for ($i = 0; $i != count($items); $i++) {
            $tmp = getUserData($items[$i], true);
            if ($tmp['doc_subtype'] != 'tech-admin' && $tmp['doc_subtype'] != 'content-admin') {
                $data[] = $tmp;
            }
        }
        
        if ($data) {
            XMLExport('registered-users-' . date('Y-m-d'), $importExportMeta, $data);
        }    
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
}

if ($_GET['act'] == 'edit') {
    if ($_POST) {
        recursive('trim', $_POST);
        recursive('stripslashes', $_POST);
        
        gyroLog('gyro_users_edit - okay');

        if (validateUserData($_POST['save-as-new'] ? false : $userId, $_POST, false, $validateUserData__errors)) {
            if ($_POST['save-as-new']) {
                $userId = false;
            }
            
            recursive('mysql_real_escape_string', $_POST);
            if (saveUser($userId, $_POST, false, $saveUser_error)) {
                header('Location: ' . ($_POST['referer'] && !preg_match('/login/i', $_POST['referer']) ? $_POST['referer'] : href('/?doc=' . constant('DOC'))));
                exit;
            } else {
                abort($saveUser_error, 'saveUser', __FILE__, __LINE__);
            }
        } else {
            echo renderUserForm($userId, $_POST, $validateUserData__errors);
        }
    } else {
        if ($userId) {
            $_FORM = getUserData($userId, false);
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            echo renderUserForm($userId, $_FORM);
        } else {
            $_FORM['referer'] = $_SERVER['HTTP_REFERER'];
            $_FORM['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_defaults WHERE doc_type = 'user'"), 0);
            echo renderUserForm(false, $_FORM);
        }
    }
} elseif ($_GET['act'] == 'delete' && $userId) {
    if (!deleteUser($userId, $deleteUser__error)) {
        abort($deleteUser__error, 'deleteUser', __FILE__, __LINE__);
    }

    gyroLog('gyro_users_delete - okay');
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC'))));
    exit;
} elseif ($_GET['act'] == 'export-xml') {
    $sql_query = mysql_query("SELECT doc_id FROM sys_docs WHERE doc_type = 'user' AND doc_subtype NOT IN ('tech-admin', 'content-admin') ORDER BY doc_id ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $data[] = getUserData($sql['doc_id'], true);
    }
    
    if ($data) {
        XMLExport('registered-users-' . date('Y-m-d'), $importExportMeta, $data);
    } else {
        abort('No data to export.', 'XMLExport', __FILE__, __LINE__);
    }
} elseif ($_FILES['import-xml']) {
    XMLImport($_FILES['import-xml'], 'user_id', $importExportMeta, 'validateUserData', 'saveUser', 'deleteUser');
} else {
    echo renderUsersList();
}


// Functions.

function validateUserId($userId) {
    // Technical Administrators are off limits to non-tech-admins.
    if ($_SESSION['user']['type'] == 'tech-admin') {
        return @mysql_result(mysql_query("SELECT user_id FROM type_user WHERE user_id = '$userId'"), 0);
    } else {
        return @mysql_result(mysql_query("SELECT doc_id FROM sys_docs WHERE doc_type = 'user' AND doc_subtype != 'tech-admin' AND doc_id = '$userId'"), 0);
    }
}

function getUserData($userId, $externalTransaction) {
    $user = mysql_fetch_assoc(mysql_query("
        SELECT
            sd.doc_id AS user_id, sd.doc_name, sd.doc_subtype, sd.doc_note, sd.doc_active,
            tu.first_name, tu.last_name, tu.email, tu.company, tu.job_title, tu.date_of_birth, tu.street_1, tu.street_2, tu.city, tu.state, tu.zipcode, tu.country, tu.phone_1, tu.phone_2, tu.mobile, tu.seo_title, tu.seo_description, tu.seo_keywords, tu.credits, tu.registration, tu.last_login, tu.send_newsletters, tu.send_notifications
        FROM sys_docs sd JOIN type_user tu ON doc_id = user_id
        WHERE user_id = '$userId'
    "));
    
    #
    
    if ($user['doc_subtype'] == 'content-admin' && ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin')) {
        $sql_query = mysql_query("SELECT doc_name FROM type_user__admin_access WHERE user_id = '$userId'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $user['admin_access'][$sql['doc_name']] = true;
        }
    }
    
    #
    
    $sql_query = mysql_query("SELECT first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2 FROM type_user__addresses WHERE user_id = '$userId' ORDER BY idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $user['addresses'][] = $sql;
    }
    
    #
    
    $sql_query = mysql_query("SELECT memberzone_id FROM type_memberzone__users WHERE user_id = '$userId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $user['memberzones'][] = $sql['memberzone_id'];
    }
    
    #
    
    $sql_query = mysql_query("SELECT url, `set` FROM type_user__files WHERE user_id = '$userId' ORDER BY `set` ASC, idx ASC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $user['files'][] = $sql;
    }
    
    #
    
    $user['fset_id'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$userId'"), 0);
    
    #
    
    $user['parameters'] = getDocParameters($userId);
    
    if ($externalTransaction) {
        // Load every parameter in its defined template, even if some of the information is missing. This is required for the export mechanism.
        if ($params = getDocTypeParameters('user')) {
            foreach ($params as $param) {
                if ($param['is_iteratable']) {
                    if (count($param['data']) == 1) {
                        for ($i = 0; $i != count($user['parameters'][$param['param_name']]); $i++) {
                            foreach ($param['data'] as $param_data) {
                                $user['parameters__' . $param['param_name']][$i] = $user['parameters'][$param['param_name']][$i];
                            }
                        }
                    } else {
                        for ($i = 0; $i != count($user['parameters'][$param['param_name']]); $i++) {
                            foreach ($param['data'] as $param_data) {
                                $user['parameters__' . $param['param_name']][$i][$param_data['param_data_name']] = $user['parameters'][$param['param_name']][$i][$param_data['param_data_name']];
                            }
                        }
                    }
                } else {
                    if (count($param['data']) == 1) {
                        $user['parameters__' . $param['param_name']] = $user['parameters'][$param['param_name']];
                    } else {
                        foreach ($param['data'] as $param_data) {
                            $user['parameters__' . $param['param_name'] . '__' . $param_data['param_data_name']] = $user['parameters'][$param['param_name']][$param_data['param_data_name']];
                        }
                    }
                }
            }
        }
        
        unset($user['parameters']);
    }
    
    return $user;
}

function validateUserData($userId, &$POST, $externalTransaction, &$errors) {
    if (!$userId) {
        $newUser = 1;
    }
    
    if ($userId && 'user' != @mysql_result(mysql_query("SELECT doc_type FROM sys_docs WHERE doc_id = '$userId'"), 0)) {
        $errors['user_id'] = 'Invalid doc-id (another doc with this doc-id already exists).';
    }
    
    if ($externalTransaction && $userId && !validateUserId($userId)) {
        $errors[] = 'Invalid user.';
    }
    
    if ($POST['doc_name']) {
        $POST['doc_name'] = strtolower($POST['doc_name']);
        
        $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'user' AND doc_name_default_prefix IS NOT NULL"), 0);
        
        if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $POST['doc_name'])) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (must begin with <u>' . $docNameDefaultPrefix . '</u>).';
        } elseif (!is_valid_doc_name($POST['doc_name'], $docNameDefaultPrefix)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (contains illegal characters).';
        } elseif (@mysql_result(mysql_query("SELECT doc_name FROM sys_docs WHERE doc_name = '$POST[doc_name]' AND doc_id != '$userId'"), 0)) {
            $errors['doc_name'] = 'Invalid <u>Doc Name</u> (already in use).';
        }
    }
    
    if (!$POST['email']) {
        $errors['email'] = 'Invalid <u>Email</u>.';
    } elseif (@mysql_result(mysql_query("SELECT email FROM type_user WHERE email = '$POST[email]' AND user_id != '$userId'"), 0)) {
        $errors['email'] = 'Invalid <u>Email</u> (already assigned to another user).';
    } elseif (@mysql_result(mysql_query("SELECT email FROM type_subscriber WHERE email = '$POST[email]'"), 0)) {
        $errors['email'] = 'Invalid <u>Email</u> (already assigned to a subscriber).';
    }
    
    if ($newUser) {
        if (!$POST['password']) {
            $errors['password'] = 'Invalid <u>Password</u> (new users must be assigned a password).';
        }
    }
    
    if (!$POST['first_name']) {
        $errors['first_name'] = 'Invalid <u>First Name</u>.';
    }
    
    if (!$POST['last_name']) {
        $errors['last_name'] = 'Invalid <u>Last Name</u>.';
    }
    
    if ($POST['mobile'] && !preg_match('/^\d{10,15}$/', $POST['mobile'])) {
        $errors['mobile'] = 'Invalid <u>Mobile</u>.';
    }
    
    if ($POST['date_of_birth'] && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $POST['date_of_birth']) || !checkdate(substr($POST['date_of_birth'], 5, 2), substr($POST['date_of_birth'], 8, 2), substr($POST['date_of_birth'], 0, 4)))) {
        $errors['date_of_birth'] = 'Invalid <u>Date of Birth</u>.';
    }
    
    if ($POST['credits'] && !preg_match('/^\-?\d+$/', $POST['credits'])) {
        $errors['credits'] = 'Invalid <u>Credits</u>.';
    }
    
    $POST['addresses'] = getNonEmptyIterations($POST['addresses']);
    for ($i = 0; $i != count($POST['addresses']); $i++) {
        if (!$POST['addresses'][$i]['first_name']) {
            $errors['addresses[' . $i . '][first_name]'] = 'Invalid <u>Addresses</u> (missing first name).';
        }
        if (!$POST['addresses'][$i]['last_name']) {
            $errors['addresses[' . $i . '][last_name]'] = 'Invalid <u>Addresses</u> (missing last name).';
        }
        if (!$POST['addresses'][$i]['street_1']) {
            $errors['addresses[' . $i . '][street_1]'] = 'Invalid <u>Addresses</u> (missing street).';
        }
    }
    
    $POST['files'] = getNonEmptyIterations($POST['files']);
    
    $POST['memberzones'] = getNonEmptyIterations($POST['memberzones']);
    for ($i = 0, $memberzones_tmp; $i != count($POST['memberzones']); $i++) {
        if (@in_array($POST['memberzones'][$i], $memberzones_tmp)) {
            $errors['memberzones[' . $i . ']'] = 'Invalid <u>Memberzones</u> (duplicated memberzone).';
        } else {
            $memberzones_tmp[] = $POST['memberzones'][$i];
        }
    }
    
    if ($externalTransaction && $POST['fset_id']) {
        if (!@mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets WHERE fset_id = '$POST[fset_id]'"), 0)) {
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
    validateDocParameters('user', $POST['parameters'], $errors);
    
    return $errors ? false : true;
}

function saveUser($userId, $POST, $externalTransaction, &$error) {
    if (!$userId) {
        $newUser = 1;
    }
    
    $sql_errors = false;
    
    if (!$externalTransaction) {
        mysql_query("BEGIN");
    }
    
    ##
    
    $doc_name = ($POST['doc_name'] == '') ? "NULL" : "'" . $POST['doc_name'] . "'";
    $doc_subtype = $newUser ? "user" : mysql_result(mysql_query("SELECT doc_subtype FROM sys_docs WHERE doc_type = 'user' AND doc_id = '$userId'"), 0);
    $doc_note = ($POST['doc_note'] == '') ? "NULL" : "'" . $POST['doc_note'] . "'";
    mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_subtype, doc_note) VALUES ('$userId', $doc_name, 'user', '$doc_subtype', $doc_note)") || $sql_errors[] = mysql_error();
    $userId = mysql_insert_id();
    
    if ($newUser) {
        $password = md5(constant('SECRET_PHRASE') . $POST['password']);
        $mobile = ($POST['mobile'] == '') ? "NULL" : "'" . $POST['mobile'] . "'";
        $date_of_birth = ($POST['date_of_birth'] == '') ? "NULL" : "'" . $POST['date_of_birth'] . "'";
        mysql_query("INSERT INTO type_user (user_id, email, password, first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2, mobile, date_of_birth, seo_title, seo_description, seo_keywords, credits, registration, send_newsletters, send_notifications) VALUES ('$userId', '$POST[email]', '$password', '$POST[first_name]', '$POST[last_name]', '$POST[company]', '$POST[job_title]', '$POST[street_1]', '$POST[street_2]', '$POST[city]', '$POST[state]', '$POST[zipcode]', '$POST[country]', '$POST[phone_1]', '$POST[phone_2]', $mobile, $date_of_birth, '$POST[seo_title]', '$POST[seo_description]', '$POST[seo_keywords]', '$POST[credits]', NOW(), '$POST[send_newsletters]', '$POST[send_notifications]')") || $sql_errors[] = mysql_error();
    } else {
        $password = $POST['password'] ? "'" . md5(constant('SECRET_PHRASE') . $POST['password']) . "'" : "password";
        $mobile = ($POST['mobile'] == '') ? "NULL" : "'" . $POST['mobile'] . "'";
        $date_of_birth = ($POST['date_of_birth'] == '') ? "NULL" : "'" . $POST['date_of_birth'] . "'";
        mysql_query("UPDATE type_user SET email = '$POST[email]', password = $password, first_name = '$POST[first_name]', last_name = '$POST[last_name]', company = '$POST[company]', job_title = '$POST[job_title]', street_1 = '$POST[street_1]', street_2 = '$POST[street_2]', city = '$POST[city]', state = '$POST[state]', zipcode = '$POST[zipcode]', country = '$POST[country]', phone_1 = '$POST[phone_1]', phone_2 = '$POST[phone_2]', mobile = $mobile, date_of_birth = $date_of_birth, seo_title = '$POST[seo_title]', seo_description = '$POST[seo_description]', seo_keywords = '$POST[seo_keywords]', credits = '$POST[credits]', registration = registration, last_login = last_login, send_newsletters = '$POST[send_newsletters]', send_notifications = '$POST[send_notifications]', auth_remote_address = auth_remote_address, auth_expiration_time = auth_expiration_time WHERE user_id = '$userId'") || $sql_errors[] = mysql_error();
    }
    
    #
    
    // If edited by a tech-admin or content-admin, editing a content-admin (not himself), and not importing (XML), update the content-admin's admin-access data.
    $isContentAdmin = @mysql_result(mysql_query("SELECT 1 FROM sys_docs WHERE doc_type = 'user' AND doc_subtype ='content-admin' AND doc_id = '$userId'"), 0);
    if ($isContentAdmin && ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin') && $userId != $_SESSION['user']['user_id'] && !$externalTransaction) {
        foreach ($GLOBALS['adminPages'] as $docName => $null) {
            // The following is structured so that if a content-admin edits another content-admin which has access to more pages, the additional access rights will not be revoked to match the editing content-admin's access rights.
            if ($POST['admin_access'][$docName]) {
                mysql_query("REPLACE INTO type_user__admin_access (user_id, doc_name) VALUES ('$userId', '$docName')") || $sql_errors[] = mysql_error();
            } else {
                mysql_query("DELETE FROM type_user__admin_access WHERE user_id = '$userId' AND doc_name = '$docName'") || $sql_errors[] = mysql_error();
            }
        }
    }
    
    #
    
    mysql_query("DELETE FROM type_user__addresses WHERE user_id = '$userId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['addresses']); $i++) {
        mysql_query("INSERT INTO type_user__addresses (user_id, first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2, idx) VALUES ('$userId', '" . $POST['addresses'][$i]['first_name'] . "', '" . $POST['addresses'][$i]['last_name'] . "', '" . $POST['addresses'][$i]['company'] . "', '" . $POST['addresses'][$i]['job_title'] . "', '" . $POST['addresses'][$i]['street_1'] . "', '" . $POST['addresses'][$i]['street_2'] . "', '" . $POST['addresses'][$i]['city'] . "', '" . $POST['addresses'][$i]['state'] . "', '" . $POST['addresses'][$i]['zipcode'] . "', '" . $POST['addresses'][$i]['country'] . "', '" . $POST['addresses'][$i]['phone_1'] . "', '" . $POST['addresses'][$i]['phone_2'] . "', '" . $i . "')") || $sql_errors[] = mysql_error();
    }
    
    #
    
    mysql_query("DELETE FROM type_memberzone__users WHERE user_id = '$userId'") || $sql_errors[] = mysql_error();
    for ($i = 0; $i != count($POST['memberzones']); $i++) {
        mysql_query("INSERT INTO type_memberzone__users (memberzone_id, user_id) VALUES ('" . $POST['memberzones'][$i] . "', '$userId')") || $sql_errors[] = mysql_error();
    }
    
    #
    
    mysql_query("DELETE FROM type_user__files WHERE user_id = '$userId'") || $sql_errors[] = mysql_error();
    // Group the files by "sets" before inserting them into the database, so as to properly index them.
    for ($i = 0, $filesBySet = array(); $i != count($POST['files']); $i++) {
        $filesBySet[$POST['files'][$i]['set']][] = $POST['files'][$i];
    }
    if ($filesBySet) {
        foreach ($filesBySet as $set => $files) {
            for ($i = 0; $i != count($files); $i++) {
                mysql_query("INSERT INTO type_user__files (user_id, file_id, url, `set`, idx) VALUES ('$userId', '" . uniqid() . "', '" . mysql_real_escape_string($files[$i]['url']) . "', " . SQLNULL(mysql_real_escape_string($set)) . ", '$i')") || $errors[] = mysql_error();
            }
        }
    }
    
    #
    
    mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$userId'");
    if ($POST['fset_id']) {
        mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('$POST[fset_id]', '$userId')");
    }
    
    #
    
    saveDocParameters('user', $userId, $POST['parameters'], $sql_errors);
    
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

function deleteUser($userId, &$error) {
    if (validateUserId($userId)) {
        $name = @mysql_result(mysql_query("SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = '$userId'"), 0);
        
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '$userId'");
        mysql_query("DELETE FROM type_user WHERE user_id = '$userId'");
        #
        mysql_query("DELETE FROM type_user__addresses WHERE user_id = '$userId'");
        #
        mysql_query("DELETE FROM type_user__ext WHERE user_id = '$userId'"); // Exists in hot4fun
        #
        mysql_query("DELETE FROM type_memberzone__users WHERE user_id = '$userId'");
        #
        mysql_query("DELETE FROM type_user__admin_access WHERE user_id = '$userId'");
        #
        $sql_query = mysql_query("SELECT auction_id FROM type_auction WHERE user_id = '$userId'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            mysql_query("DELETE FROM sys_docs WHERE doc_id = '$sql[auction_id]'");
            mysql_query("DELETE FROM type_auction WHERE auction_id = '$sql[auction_id]'");
            #
            mysql_query("DELETE FROM type_auction__files WHERE auction_id = '$sql[auction_id]'");
            #
            mysql_query("DELETE FROM type_auction__images WHERE auction_id = '$sql[auction_id]'");
            mysql_query("DELETE FROM type_auction__links WHERE auction_id = '$sql[auction_id]'");
            mysql_query("DELETE FROM type_auction__bids WHERE auction_id = '$sql[auction_id]'");
            #
            mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '$sql[auction_id]'");
            #
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$sql[auction_id]'");
        }
        #
        mysql_query("DELETE FROM type_auction__bids WHERE user_id = '$userId'");
        #
        mysql_query("UPDATE type_talkback SET name = '" . mysql_real_escape_string($name) . "', user_id = NULL WHERE user_id = '$userId'");
        #
        mysql_query("UPDATE type_review SET name = '" . mysql_real_escape_string($name) . "', user_id = NULL WHERE user_id = '$userId'");
        #
        mysql_query("DELETE FROM type_user__files WHERE user_id = '$userId'");
        #
        mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '$userId'");
        #
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$userId'");
        #
        @unlink(constant('UPLOAD_BASE') . $_SESSION['user']['user_id']);
        
        return true;
    } else {
        $error = 'Invalid user.';
        return false;
    }
}

function renderUserForm($userId, $_FORM = false, $errors = false) {
    $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = 'user' AND doc_name_default_prefix IS NOT NULL"), 0);
    if ($userId) {
        $userSubtype = @mysql_result(mysql_query("SELECT doc_subtype FROM sys_docs WHERE doc_type = 'user' AND doc_id = '$userId'"), 0);
    } else {
        $userSubtype = 'user';
    }
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Users</title>
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
        / <a href="<?=href('/?doc=' . constant('DOC'))?>">Users</a>
        / <?=($userId ? 'Id: ' . $userId : 'New')?>
    </div>
    
    <? if ($errors): ?>
    <div class="errors">
        <div class="title">Data Error(s)</div>
        <? foreach ($errors as $error): ?>
        <div class="error"><?=$error?></div>
        <? endforeach; ?>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__users_general">
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
                <td class="col a"><div class="caption">first name</div></td>
                <td class="col b"><input type="text" name="first_name" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['first_name'])?>" style="width: 125px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">last name</div></td>
                <td class="col b"><input type="text" name="last_name" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['last_name'])?>" style="width: 125px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">email</div></td>
                <td class="col b"><input type="text" name="email" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['email'])?>" style="width: 325px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <? if ($userId): ?>
                <td class="col a"><div class="caption">new password</div></td>
                <? else: ?>
                <td class="col a"><div class="caption">password</div></td>
                <? endif; ?>
                <td class="col b">
                    <script type="text/javascript">
                    function generatePassword() {
                        var length = 8;
                        var password = '';
                        
                        for (var i = 0, rndNum; i < length; i++) {
                            rndNum = Math.random();
                            rndNum = parseInt(rndNum * 1000);
                            rndNum = (rndNum % 94) + 33;
                            
                            if ((rndNum >= 45 && rndNum <= 46) || (rndNum >= 48 && rndNum <= 57) || (rndNum >= 64 && rndNum <= 90) || (rndNum >= 97 && rndNum <= 122)) {
                                password = password + String.fromCharCode(rndNum);
                            } else {
                                i--;
                            }
                        }
                        
                        document.getElementById('input__password').value = password;
                        return false;
                    }
                    </script>
                    <input type="text" name="password" id="input__password" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['password'])?>" style="width: 175px;"></td>
                <td class="col c"><div class="annotation">Once set the password cannot be retrieved. Use this to change the existing password. If left empty the existing password will remain in effect.<br><a href="" onclick="return generatePassword()">Generate a random password</a>.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">company</div></td>
                <td class="col b"><input type="text" name="company" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['company'])?>" style="width: 325px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">job title</div></td>
                <td class="col b"><input type="text" name="job_title" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['job_title'])?>" style="width: 325px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">date of birth</div></td>
                <td class="col b"><input type="text" name="date_of_birth" id="input__date_of_birth" maxlength="10" class="text" value="<?=htmlspecialchars($_FORM['date_of_birth'])?>" style="width: 85px;"></td>
                <td class="col c"><div class="annotation">Must be in the form of <a href="" onclick="document.getElementById('input__date_of_birth').value = '<?=date('Y-m-d')?>'; return false;">YYYY-MM-DD</a>.</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">street 1</div></td>
                <td class="col b"><input type="text" name="street_1" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['street_1'])?>" style="width: 325px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">street 2</div></td>
                <td class="col b"><input type="text" name="street_2" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['street_2'])?>" style="width: 325px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">city</div></td>
                <td class="col b"><input type="text" name="city" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['city'])?>" style="width: 175px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">state</div></td>
                <td class="col b"><input type="text" name="state" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['state'])?>" style="width: 175px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">zipcode</div></td>
                <td class="col b"><input type="text" name="zipcode" maxlength="10" class="text" value="<?=htmlspecialchars($_FORM['zipcode'])?>" style="width: 85px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">country</div></td>
                <td class="col b"><input type="text" name="country" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['country'])?>" style="width: 175px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">phone 1</div></td>
                <td class="col b"><input type="text" name="phone_1" maxlength="20" class="text" value="<?=htmlspecialchars($_FORM['phone_1'])?>" style="width: 150px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">phone 2</div></td>
                <td class="col b"><input type="text" name="phone_2" maxlength="20" class="text" value="<?=htmlspecialchars($_FORM['phone_2'])?>" style="width: 150px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">mobile</div></td>
                <td class="col b"><input type="text" name="mobile" maxlength="16" class="text" value="<?=htmlspecialchars($_FORM['mobile'])?>" style="width: 150px;"></td>
                <td class="col c"><div class="annotation">An international number, comprised of 10-15 digits, excluding '+' (e.g. 972541234567).</div></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">credits</div></td>
                <td class="col b"><input type="text" name="credits" maxlength="5" class="text" value="<?=htmlspecialchars($_FORM['credits'])?>" style="width: 85px;"></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">recieve newsletters</div></td>
                <td class="col b"><input type="checkbox" name="send_newsletters" class="checkbox" value="1" <?=($_FORM['send_newsletters'] ? 'checked' : false)?>></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">recieve notifications</div></td>
                <td class="col b"><input type="checkbox" name="send_notifications" class="checkbox" value="1" <?=($_FORM['send_notifications'] ? 'checked' : false)?>></td>
                <td class="col c"><div class="annotation">A global switch for email and SMS notifications that are triggered by various events.</div></td>
            </tr>
            
            <? if ($userId): ?>
            <tr>
                <td class="col a"><div class="caption">registration</div></td>
                <td class="col b"><div class="text"><?=$_FORM['registration']?></div></td>
                <td class="col c"></td>
            </tr>
            
            <tr>
                <td class="col a"><div class="caption">last login</div></td>
                <td class="col b"><div class="text"><?=$_FORM['last_login']?></div></td>
                <td class="col c"></td>
            </tr>
            <? endif; ?>
            
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
    
    <div class="section" id="section__users_seo">
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
    
    <? if ($userSubtype == 'content-admin' && ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin') && $userId != $_SESSION['user']['user_id']): ?>
    <div class="section" id="section__users_admin_access">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">admin access</div>
        </div>
        
        <table class="section">
            <tr>
                <td colspan="3" class="col">
                    <table cellpadding="2" cellspacing="0" style="width: 100%; font-size: 12px; font-variant: small-caps;">
                        <?php
                        $cnt = 0;
                        foreach ($GLOBALS['adminPages'] as $docName => $adminPage) {
                            if (!($cnt % 5)) {
                                echo '<tr>' . "\n";
                                $cnt = 0;
                            }
                            echo '<td><label for="admin_access__' . $docName . '"><input type="checkbox" id="admin_access__' . $docName . '" name="admin_access[' . $docName . ']" class="checkbox" value="1" ' . ($_FORM['admin_access'][$docName] ? 'checked' : false) . '> ' . strtolower($adminPage) . '</label></td>' . "\n";
                            $cnt++;
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <? endif; ?>
    
    <div class="section" id="section__users_files">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">files</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['files']) > 0 ? count($_FORM['files']) : 1); $i++): ?>
        <div class="iteration" arrayname="files[<?=$i?>]">
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
                    <td class="col a"><div class="caption">set</div></td>
                    <td class="col b"><input type="text" name="files[<?=$i?>][set]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['files'][$i]['set'])?>" style="width: 175px;"></td>
                    <td class="col c"><div class="annotation">Optional.</div></td>
                </tr>
                <tr>
                    <td class="col a"><div class="caption">url</div></td>
                    <td class="col b"><input type="text" name="files[<?=$i?>][url]" maxlength="255" class="text browse" value="<?=htmlspecialchars($_FORM['files'][$i]['url'])?>">&nbsp;<button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="gyroFileManager(this.previousSibling.previousSibling.id = Date().replace(/\W/g, ''), this.previousSibling.previousSibling.value)" onfocus="this.blur()" style="border-color: #F6F6F6;"><img src="/repository/admin/images/act_find.gif">&nbsp;&nbsp;Browse</button></td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <div class="section" id="section__users_addresses">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">addresses</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['addresses']) > 0 ? count($_FORM['addresses']) : 1); $i++): ?>
        <div class="iteration" arrayname="addresses[<?=$i?>]">
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
                    <td class="col a"><div class="caption">first name</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][first_name]" maxlength="15" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['first_name'])?>" style="width: 125px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">last name</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][last_name]" maxlength="15" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['last_name'])?>" style="width: 125px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">company</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][company]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['company'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">job title</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][job_title]" maxlength="100" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['job_title'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">street 1</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][street_1]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['street_1'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">street 2</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][street_2]" maxlength="50" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['street_2'])?>" style="width: 325px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">city</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][city]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['city'])?>" style="width: 175px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">state</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][state]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['state'])?>" style="width: 175px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">zipcode</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][zipcode]" maxlength="10" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['zipcode'])?>" style="width: 85px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">country</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][country]" maxlength="25" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['country'])?>" style="width: 175px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">phone 1</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][phone_1]" maxlength="20" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['phone_1'])?>" style="width: 150px;"></td>
                    <td class="col c"></td>
                </tr>
                
                <tr>
                    <td class="col a"><div class="caption">phone 2</div></td>
                    <td class="col b"><input type="text" name="addresses[<?=$i?>][phone_2]" maxlength="20" class="text" value="<?=htmlspecialchars($_FORM['addresses'][$i]['phone_2'])?>" style="width: 150px;"></td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <div class="section" id="section__users_memberzones">
        <div class="section-header" onclick="toggleSectionDisplay(this.parentNode)">
            <div class="icon">[-]</div>
            <div class="caption">memberzones</div>
        </div>
        
        <? for ($i = 0; $i != (count($_FORM['memberzones']) > 0 ? count($_FORM['memberzones']) : 1); $i++): ?>
        <div class="iteration" arrayname="memberzones[<?=$i?>]">
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
                    <td class="col a"><div class="caption">memberzone</div></td>
                    <td class="col b">
                        <select name="memberzones[<?=$i?>]" class="select" style="width: 350px;">
                            <option value="">&nbsp;</option>
                            <?
                            $sql_query = mysql_query("SELECT memberzone_id, title FROM type_memberzone");
                            while ($sql = mysql_fetch_assoc($sql_query)) {
                                $selected = ($sql['memberzone_id'] == $_FORM['memberzones'][$i]) ? 'selected' : false;
                                echo '<option value="' . $sql['memberzone_id'] . '" ' . $selected . '>[ ' . $sql['title'] . ' ]</option>' . "\n";
                            }
                            ?>
                        </select>
                    </td>
                    <td class="col c"></td>
                </tr>
            </table>
        </div>
        <? endfor; ?>
    </div>
    
    <?php
    $param_sections[] = array('section_id' => '', 'title' => 'General');
    $sql_query = mysql_query("SELECT section_id, title, description FROM sys_docs_types_params_sections WHERE doc_type = 'user'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $param_sections[] = $sql;
    }
    
    $params = getDocTypeParameters('user');
    for ($i = 0; $i != count($param_sections); $i++) {
        for ($j = 0; $j != count($params); $j++) {
            if ($param_sections[$i]['section_id'] == $params[$j]['section_id']) {
                $param_sections[$i]['params'][] = $params[$j];
            }
        }
    }
    
    foreach ($param_sections as $param_section) {
        echo renderParameterSection('users', $param_section, $_FORM);
    }
    ?>
    <!--<script>updateDocParametersBySubtype('users', '<?=$userSubtype?>')</script>-->
    
    <div class="action-buttons-bar">
        <input type="image" width="1" height="1">
        
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save</button>
        <? if ($userId): ?>
        <span class="separator">|&nbsp;</span>
        <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="confirmLeave = false; document.getElementById('save_as_new').value = 1;" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Save New</button>
        <span class="separator">|&nbsp;</span>
        <button type="button" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onclick="if (confirm('Are you sure you want to delete this item?')) { confirmLeave = false; window.location = '<?=href('/?doc=' . constant('DOC'))?>&user=<?=$userId?>&act=delete'; }" onfocus="this.blur()"><img src="/repository/admin/images/act_delete.gif">&nbsp;&nbsp;Delete</button>
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

function renderUsersList() {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Users</title>
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
            Array('subtype', 'Subype'),
            Array('name', 'Name'),
            Array('email', 'Email'),
            Array('bounce', 'Bounce'),
            Array('last-login', 'Last Login'),
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
                        +     '<td class="doc-id"><a href="<?=href('/?doc=' . constant('DOC'))?>&user=' + items[i].getAttribute('id') + '&act=edit">' + items[i].getAttribute('id') + '</a></td>'
                        +     '<td style="white-space: nowrap;">' + items[i].getAttribute('doc_subtype') + '</td>'
                        +     '<td style="width: 50%;">' + items[i].getAttribute('name') + '</td>'
                        +     '<td style="width: 50%;"><a href="mailto:' + items[i].getAttribute('email') + '">' + items[i].getAttribute('email') + '</a></td>'
                        +     '<td style="text-align: center;">' + items[i].getAttribute('bounce') + '</td>'
                        +     '<td style="white-space: nowrap;">' + items[i].getAttribute('last_login') + '</td>'
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
                    / <a href="<?=href('/?doc=' . constant('DOC'))?>">Users</a>
                </td>
            </tr>
            
            <tr>
                <td>There are several technical administrators registered with the system. They cannot be altered or removed, and are normally not listed.</td>
            </tr>
            
            <tr>
                <td>
                    <img id="ajax-loader" src="/repository/admin/images/ajax-loader.gif" style="float: right; display: none;">
                    
                    <input type="button" value="Add User" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Actions" class="button" style="width: 84px;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="actions-button-drop-down-menu" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Import XML" class="button" style="width: 100px;" onclick="importXML(); adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Export XML" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=export-xml'; adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Export Sel." class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'export-selected'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <? if ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin'): ?>
                            <input type="button" value="Make Admin" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to mark all selected users as content-admins?')) { document.getElementById('adminList_action').value = 'make-admin'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <input type="button" value="Unmake Admin" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to mark all selected users as regular users?')) { document.getElementById('adminList_action').value = 'unmake-admin'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } adminList_buttonDropDownMenu('actions-button-drop-down-menu'); this.blur();">
                            <? endif; ?>
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
                    <input type="button" value="Add User" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=edit'">
                    <input type="button" value="Delete" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to delete all selected items?')) { document.getElementById('adminList_action').value = 'delete'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } this.blur();">
                    <div style="display: inline; position: relative;">
                        <input type="button" value="Actions" class="button" style="width: 84px;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();"><button type="button" style="width: 16px; height: 22px; border-width: 1px; background-color: #EEEEEE; vertical-align: top;" onclick="adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();"><img src="/repository/admin/images/button-drop-down-menu-arrow.gif"></button>
                        <div id="actions-button-drop-down-menu-2" style="width: 100px; position: absolute; top: 22px; left: 0; display: none;">
                            <input type="button" value="Import XML" class="button" style="width: 100px;" onclick="importXML(); adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Export XML" class="button" style="width: 100px;" onclick="window.location = '<?=href('/?doc=' . constant('DOC'))?>&act=export-xml'; adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Export Sel." class="button" style="width: 100px;" onclick="document.getElementById('adminList_action').value = 'export-selected'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <? if ($_SESSION['user']['type'] == 'tech-admin' || $_SESSION['user']['type'] == 'content-admin'): ?>
                            <input type="button" value="Make Admin" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to mark all selected users as content-admins?')) { document.getElementById('adminList_action').value = 'make-admin'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <input type="button" value="Unmake Admin" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to mark all selected users as regular users?')) { document.getElementById('adminList_action').value = 'unmake-admin'; document.getElementById('adminList_items_inver').value = items_inver; document.getElementById('adminList_items_array').value = items_array.join(','); document.getElementById('adminList').action = uri; document.getElementById('adminList').submit(); } adminList_buttonDropDownMenu('actions-button-drop-down-menu-2'); this.blur();">
                            <? endif; ?>
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
        echo '    ';
        echo '<item';
        echo ' id="' . $item['user_id'] . '"';
        echo ' name="' . xmlentities($item['name']) . '"';
        echo ' email="' . xmlentities($item['email']) . '"';
        echo ' last_login="' . xmlentities($item['last_login']) . '"';
        echo ' doc_subtype="' . xmlentities($item['doc_subtype']) . '"';
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
        $order = 'subtype';
        $sort = 'ASC';
    }
    
    switch ($order) {
        case 'id': $ORDERBY = "ORDER BY doc_id $sort"; break;
        case 'subtype': $ORDERBY = "ORDER BY FIELD(doc_subtype, 'tech-admin', 'content-admin', 'user') $sort"; break;
        case 'name': $ORDERBY = "ORDER BY name $sort"; break;
        case 'email': $ORDERBY = "ORDER BY email $sort"; break;
        case 'last-login': $ORDERBY = "ORDER BY last_login $sort"; break;
        case 'bounce': $ORDERBY = "ORDER BY bounce $sort"; break;
        case 'note': $ORDERBY = "ORDER BY doc_note $sort"; break;
    }
    
    if ($_GET['query']) {
        $searchArray = array('doc_name', 'doc_subtype', 'doc_note', 'user_id', 'name', 'email');
        
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
    
    if ($_SESSION['user']['type'] == 'tech-admin') {
        $sql_query = mysql_query("SELECT sd.doc_subtype, sd.doc_name, sd.doc_note, sd.doc_active, tu.user_id, CONCAT(tu.first_name, ' ', tu.last_name) AS name, tu.email, tu.last_login, (SELECT cnt FROM bounce.log WHERE email = tu.email) AS bounce FROM sys_docs sd LEFT JOIN type_user tu ON doc_id = user_id WHERE doc_type = 'user' $HAVING $ORDERBY");
	} else {
        $sql_query = mysql_query("SELECT sd.doc_subtype, sd.doc_name, sd.doc_note, sd.doc_active, tu.user_id, CONCAT(tu.first_name, ' ', tu.last_name) AS name, tu.email, tu.last_login, (SELECT cnt FROM bounce.log WHERE email = tu.email) AS bounce FROM sys_docs sd LEFT JOIN type_user tu ON doc_id = user_id WHERE doc_type = 'user' AND doc_subtype != 'tech-admin' $HAVING $ORDERBY");
    }
    $techAdmins = array();
    while ($sql = mysql_fetch_assoc($sql_query)) {
        if ($sql['doc_subtype'] == 'tech-admin' && $_SESSION['user']['type'] == 'tech-admin') {
            $techAdmins[$sql['user_id']] = $sql;
        } else {
            $list[] = $sql;
        }
    }
    if (!empty($list)) {
    	$list = array_merge($techAdmins, $list);    
    } else {
        $list = array_merge($techAdmins, array());
    }
    
    return array($list, $order, $sort);
}

?>