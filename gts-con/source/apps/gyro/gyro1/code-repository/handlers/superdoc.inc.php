<?php

// This handler can handle both 'internal' and 'external' superdocs. Internal superdocs are executed within
// the limitations of the layout layer. External superdocs are executed outside the layour layer, as
// stand-alone scripts. An external superscript can mimic an internal script, by buffering its output and
// than passing it to the layout layer (this is useful in scripts such as a printable version of a page).

$Superdoc = @mysql_fetch_assoc(mysql_query("SELECT superdoc_id, file_path, is_external FROM type_superdoc WHERE superdoc_id = '" . constant('DOC_ID') . "'"));
$filename = 'handlers/superdocs/' . $Superdoc['file_path'];

if ($Superdoc['is_external']) {
    if (($fp = @fopen($filename, 'r', 1)) && fclose($fp)) {
        include($filename);
    } else {
        abort('Could not find superdoc.', __FUNCTION__, __FILE__, __LINE__);
    }
} else {
    ob_start();
    
    if (($fp = @fopen($filename, 'r', 1)) && fclose($fp)) {
        include($filename);
    } else {
        abort('Could not find superdoc.', __FUNCTION__, __FILE__, __LINE__);
    }
    
    $T_global['content'] = ob_get_contents();
    ob_end_clean();
    
    echo renderTemplate('global/main', $T_global);
}

?>