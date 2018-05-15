<?php

$Newsletter = mysql_fetch_assoc(mysql_query("
    SELECT
        sd.doc_id, sd.doc_name,
        t.newsletter_id, t.title, t.content, t.subject,
        t.seo_title, t.seo_description, t.seo_keywords
    FROM sys_docs sd JOIN type_newsletter t ON doc_id = newsletter_id
    WHERE
        newsletter_id = '" . constant('DOC_ID') . "'
        AND sd.doc_active = '1'
"));

if ($_GET['link']) {
    $tmp = explode('-', $_GET['link']);
    
    if (count($tmp) == 3 && $tmp[2] == strtoupper(substr(md5(constant('SECRET_PHRASE') . $Newsletter['newsletter_id'] . $tmp[0] . $tmp[1]), 16, 8))) {
        $recipient_id = $tmp[0];
        $link_id = $tmp[1];
    } else {
        header('Location: ' . href('/'));
        exit;
    }
    
    mysql_query("UPDATE type_newsletter__recipients SET opened = NOW() WHERE newsletter_id = '" . mysql_real_escape_string($Newsletter['newsletter_id']) . "' AND recipient_id = '" . mysql_real_escape_string($recipient_id) . "' AND opened IS NULL");
    
    list($name, $email) = mysql_fetch_row(mysql_query("SELECT name, email FROM type_newsletter__recipients WHERE newsletter_id = '" . mysql_real_escape_string($Newsletter['newsletter_id']) . "' AND recipient_id = '" . mysql_real_escape_string($recipient_id) . "'"));
    
    if ($link_id == 'O') {
        // Image source link.
        
        header('Content-type: image/gif');
        echo base64_decode('R0lGODlhAQABAJEAAAAAAP///////wAAACH5BAEAAAIALAAAAAABAAEAAAICVAEAOw==');
        exit;
    } elseif ($link_id == 'X') {
        // Unsubscribe link.
        
        mysql_query("UPDATE type_newsletter__recipients SET unsubscribed = NOW() WHERE newsletter_id = '" . mysql_real_escape_string($Newsletter['newsletter_id']) . "' AND recipient_id = '" . mysql_real_escape_string($recipient_id) . "' AND unsubscribed IS NULL");
        mysql_query("UPDATE type_user SET send_newsletters = '0' WHERE email = '" . mysql_real_escape_string($email) . "'");
        if (mysql_affected_rows() > 0) {
            $operation_successful = true;
        } elseif ($subscriber_id = @mysql_result(mysql_query("SELECT subscriber_id FROM type_subscriber WHERE email = '" . mysql_real_escape_string($email) . "'"), 0)) {
            mysql_query("DELETE FROM sys_docs WHERE doc_id = '$subscriber_id'");
            mysql_query("DELETE FROM type_subscriber WHERE subscriber_id = '$subscriber_id'");
            #
            mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$subscriber_id'");
            
            $operation_successful = true;
        } else {
            $operation_successful = false;
        }
        
        echo renderTemplate('global/main', array(
            'content' => renderTemplate('superdocs/unsubscribe', array(
                'operation_successful' => $operation_successful
            ))
        ));
        exit;
    } elseif ($link_id > 0) {
        // Proxied link.
        
        if ($url = @mysql_result(mysql_query("SELECT url FROM type_newsletter__links WHERE newsletter_id = '" . mysql_real_escape_string($Newsletter['newsletter_id']) . "' AND link_id = '" . mysql_real_escape_string($link_id) . "'"), 0)) {
            mysql_query("INSERT INTO type_newsletter__links_recipients (newsletter_id, link_id, recipient_id) VALUES ('" . mysql_real_escape_string($Newsletter['newsletter_id']) . "', '" . mysql_real_escape_string($link_id) . "', '" . mysql_real_escape_string($recipient_id) . "')");
        } else {
            $url = href('/');
        }
        
        header('Location: ' . $url);
        exit;
    }
}

if (!$name && !$email && constant('NEWSLETTER_DEFAULT_RECIPIENT_NAME') && constant('NEWSLETTER_DEFAULT_RECIPIENT_EMAIL')) {
    $name = constant('NEWSLETTER_DEFAULT_RECIPIENT_NAME');
    $email = constant('NEWSLETTER_DEFAULT_RECIPIENT_EMAIL');
}

$output = renderTemplate('newsletters/newsletter', array(
    'standalone' => false,
    'recipient' => array(
        'name' => $name,
        'email' => $email
    ),
    'title' => $Newsletter['title'],
    'content' => href_HTMLArea($Newsletter['content']),
    'parameters' => getDocParameters(constant('DOC_ID')),
    'direct_url' => constant('HTTP_URL') . $Newsletter['newsletter_id'] . '?link=' . $recipient_id . '-' . '0' . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $Newsletter['newsletter_id'] . $recipient_id . '0'), 16, 8)),
    'unsubscribe_url' => constant('HTTP_URL') . $Newsletter['newsletter_id'] . '?link=' . $recipient_id . '-' . 'X' . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $Newsletter['newsletter_id'] . $recipient_id . 'X'), 16, 8)),
    'seo' => array(
        'title' => $Article['seo_title'],
        'description' => $Article['seo_description'],
        'keywords' => $Article['seo_keywords']
    )
));

// If $recipient_id is set (token validated), tag all links.
if ($recipient_id) {
    $sql_query = mysql_query("SELECT link_id, url FROM type_newsletter__links WHERE newsletter_id = '$Newsletter[newsletter_id]'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $link = $recipient_id . '-' . $sql['link_id'] . '-' . strtoupper(substr(md5(constant('SECRET_PHRASE') . $Newsletter['newsletter_id'] . $recipient_id . $sql['link_id']), 16, 8));
        $output = preg_replace('/([\"\'])' . preg_quote($sql['url'], '/') . '\\1/i', constant('HTTP_URL') . $Newsletter['newsletter_id'] . '?link=' . $link, $output);
    }
}

echo $output;

?>
