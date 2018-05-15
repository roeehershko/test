<?php

$template = renderTemplate('user', array(
    'user_info' => array(
        'doc' => $docObj->doc,
        'id' => $docObj->doc_id,
        'type' => $docObj->doc_subtype,
        'email' => $docObj->email,
        'first_name' => $docObj->first_name,
        'last_name' => $docObj->last_name,
        'company' => $docObj->company,
        'job_title' => $docObj->job_title,
        'street_1' => $docObj->street_1,
        'street_2' => $docObj->street_2,
        'city' => $docObj->city,
        'state' => $docObj->state,
        'zipcode' => $docObj->zipcode,
        'country' => $docObj->country,
        'phone_1' => $docObj->phone_1,
        'phone_2' => $docObj->phone_2,
        'mobile' => $docObj->mobile,
        'seo' => array(
            'title' => $docObj->seo_title,
            'description' => $docObj->seo_description,
            'keywords' => $docObj->seo_keywords
        ),
        'credits' => $docObj->credits,
        'date_of_birth' => $docObj->date_of_birth,
        'send_newsletters' => $docObj->send_newsletters,
        'send_notifications' => $docObj->send_notifications,
        'registration' => $docObj->registration,
        'last_login' => $docObj->last_login
    )
));

echo renderTemplate('global/main', array(
    'title' => $docObj->title,
    'seo' => array(
        'title' => $docObj->seo_title,
        'description' => $docObj->seo_description,
        'keywords' => $docObj->seo_keywords
    ),
    'content' => $template
));

?>
