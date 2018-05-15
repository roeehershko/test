<?php

//echo '<pre>', $docObj, '</pre>';

$template = renderTemplate('element', array(
    'title' => $docObj->title
));

echo renderTemplate('global/main', array(
    'title' => $docObj->title,
    'content' => $template
));

?>