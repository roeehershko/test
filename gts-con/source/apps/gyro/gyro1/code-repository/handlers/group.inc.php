<?php

$Group = mysql_fetch_assoc(mysql_query("
    SELECT
        sd.doc_id, sd.doc_name, sd.doc_subtype,
        t.title, t.description, t.docs_per_page, t.seo_title, t.seo_description, t.seo_keywords, t.status
    FROM sys_docs sd JOIN type_group t ON doc_id = group_id
    WHERE
        group_id = '" . constant('DOC_ID') . "'
        AND sd.doc_active = '1'
"));

// Check whether the group is part of a memberzone, and if so, assert whether the user has access to it.
if ($memberzoneId = getGroupMemberzone($Group['doc_id'])) {
    $sql_query = mysql_query("SELECT user_id FROM type_memberzone__users WHERE memberzone_id = '$memberzoneId'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $mz_users[] = $sql['user_id'];
    }
    if (!@in_array($_SESSION['user']['user_id'], $mz_users)) {
        doc_access_denied();
    }
}

$sql_query = mysql_query("SELECT title, content, image_url, image_align FROM type_group__paragraphs WHERE group_id = '$Group[doc_id]' ORDER BY idx ASC");
while ($sql = mysql_fetch_assoc($sql_query)) {
    $Group['paragraphs'][] = $sql;
}

if (is_null($Group['docs_per_page'])) {
    // Get products.
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id JOIN type_product t ON doc_id = product_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'product'
            AND t.status != 'hidden'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['products'][] = $sql['doc_id'];
    }
    
    // Get licenses.
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id JOIN type_license t ON doc_id = license_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'license'
            AND t.status != 'hidden'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['licenses'][] = $sql['doc_id'];
    }
    
    // Get articles.
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'article'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['articles'][] = $sql['doc_id'];
    }
    
    // Get exams.
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id JOIN type_exam t ON doc_id = exam_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'exam'
            AND t.is_active = '1'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['exams'][] = $sql['doc_id'];
    }
    
    // Get auctions.
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'auction'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['auctions'][] = $sql['doc_id'];
    }
} else {
    $limit = $Group['docs_per_page'];
    $total = @mysql_result(mysql_query("SELECT COUNT(*) FROM type_group__associated_docs tg_ad, type_product tp WHERE tg_ad.group_id = '$Group[doc_id]' AND tg_ad.associated_doc_id = tp.product_id AND tp.status = 'normal'"), 0);
    $page = (preg_match('/^\d+$/', $_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= ceil($total / $limit)) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $sql_query = mysql_query("
        SELECT sd.doc_id
        FROM sys_docs sd JOIN type_group__associated_docs tg_ad ON doc_id = associated_doc_id JOIN type_product t ON doc_id = product_id
        WHERE
            tg_ad.group_id = '$Group[doc_id]'
            AND sd.doc_type = 'product'
            AND t.status != 'hidden'
            AND sd.doc_active = '1'
        ORDER BY tg_ad.idx ASC
        LIMIT $limit OFFSET $offset
    ");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $Group['products'][] = $sql['doc_id'];
    }
    
    $Group['prev-page-url'] = ($page > 1) ? href(constant('DOC')) . '/?page=' . ($page - 1) : false;
    $Group['next-page-url'] = ($page < ceil($total / $limit)) ? href(constant('DOC')) . '/?page=' . ($page + 1) : false;
}

##

$T_group['group']['doc'] = $Group['doc_name'] ? $Group['doc_name'] : $Group['doc_id'];

$T_group['group']['id'] = $Group['doc_id'];
$T_group['group']['title'] = $Group['title'];
$T_group['group']['description'] = href_HTMLArea($Group['description']);
$T_group['group']['paragraphs'] = $Group['paragraphs'];
$T_group['group']['status'] = $Group['status'];

$T_group['group']['memberzone'] = $memberzoneId;

$T_group['group']['seo']['title'] = $Group['seo_title'];
$T_group['group']['seo']['description'] = $Group['seo_description'];
$T_group['group']['seo']['keywords'] = $Group['seo_keywords'];

$T_group['group']['products'] = $Group['products'];
$T_group['group']['licenses'] = $Group['licenses'];
$T_group['group']['articles'] = $Group['articles'];
$T_group['group']['exams'] = $Group['exams'];
$T_group['group']['auctions'] = $Group['auctions'];

$T_group['group']['previous_products'] = $_SESSION['previously-viewed-products'];

if (!is_null($Group['docs_per_page'])) {
    $T_group['group']['nav']['page'] = $page;
    $T_group['group']['nav']['limit'] = $limit;
    $T_group['group']['nav']['total'] = $total;
    $T_group['group']['nav']['offset'] = $offset;
    $T_group['group']['nav']['prev-page-url'] = $Group['prev-page-url'];
    $T_group['group']['nav']['next-page-url'] = $Group['next-page-url'];
}

##

$T_global['title'] = $Group['title'];
$T_global['seo'] = $T_group['group']['seo'];
$T_global['content'] = renderTemplate('group.' . $Group['doc_subtype'], $T_group);

##

echo renderTemplate('global/main', $T_global);

?>