<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if (!defined('FILES_BASE') || !file_exists(constant('FILES_BASE')) || !is_dir(constant('FILES_BASE')) || substr(constant('FILES_BASE'), -1) != '/') {
    abort('The FILES_BASE system variable is not properly defined.');
}

if (!defined('FILES_BASE_VIRTUAL') || substr(constant('FILES_BASE_VIRTUAL'), -1) != '/') {
    abort('The FILES_BASE_VIRTUAL system variable is not properly defined.');
}

##

// ACP Upload Progress.
if (isset($_POST['uid'])) {
    header('Expires: Tue, 08 Oct 1991 00:00:00 GMT');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo json_encode(apc_fetch('upload_' . $_POST['uid']));
    exit;
}

##

set_time_limit(60 * 5);

define('OBJECTS_COUNT', 100);
define('MAX_FILE_SIZE', 8388608); // See additional settings in '.htaccess'.

$base = constant('FILES_BASE');
$baseVirtual = constant('FILES_BASE_VIRTUAL');

$noReturn = $_GET['noreturn'] ? 1 : 0; // If set, the application will not show the 'Select' button.

// Known file types.
// Groups are used for filtering.
// Extensions are used for client-side and server-side file uploading restrictions.
// Mime content types are used for server-side file uploading restrictions.
$fileTypes = array(
    'images' => array(
        array('.gif',   'image/gif'),
        array('.jpeg',  'image/jpeg'),
        array('.jpg',   'image/jpeg'),
        array('.jpeg',  'image/pjpeg'),
        array('.jpg',   'image/pjpeg'),
        array('.png',   'image/png'),
        array('.png',   'image/x-png'),
        array('.tif',   'image/tiff'),
        array('.psd',   'image/vnd.adobe.photoshop'),
        array('.tiff',  'image/tiff')
    ),
    
    'flash' => array(
        array('.swf',   'application/x-shockwave-flash'),
        array('.flv',   'video/x-flv')
    ),
    
    'media' => array(
        array('.avi',   'video/x-msvideo'),
        array('.mov',   'video/quicktime'),
        array('.mpeg',  'video/mpeg'),
        array('.mpg',   'video/mpeg'),
        array('.mp4',   'video/mp4'),
        array('.wmv',   'video/x-ms-wmv'),
        array('.rm',    'audio/x-pn-realaudio'),
        array('.wav',   'audio/x-wav'),
        array('.mp3',   'audio/mpeg'),
        array('.mp3',   'audio/mp3'),
        array('.aif',   'audio/x-aiff'),
        array('.mid',   'audio/midi'),
        array('.midi',  'audio/midi'),
        array('.ra',    'audio/x-realaudio'),
        array('.ram',   'audio/x-pn-realaudio')
    ),
    
    'documents' => array(
        array('.pdf',   'application/pdf'),
        array('.doc',   'application/msword'),
        array('.docx',  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        array('.xls',   'application/vnd.ms-excel'),
        array('.pps',   'application/mspowerpoint'),
        array('.ppt',   'application/mspowerpoint'),
        array('.pptx',  'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
        array('.xlsx',  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        array('.ppt',   'application/vnd.ms-powerpoint'),
        array('.txt',   'text/plain'),
        array('.css',   'text/css'),
        array('.vcf',   'text/x-vcard'),
        array('.xml',   'text/xml'),
        array('.csv',   'text/csv'),
        array('.html',  'text/html'),
        array('.htm',   'text/html'),
        array('.vcf',   'text/vcard'),
        array('.dwg',   'application/octet-stream')
    ),
    
    'archives' => array(
    	array('.zip',   'application/octet-stream'),
        array('.zip',   'application/zip'),
        array('.zip',   'application/x-zip-compressed'),
        array('.rar',   'application/x-rar-compessed'),
        array('.gz',    'application/x-gzip'),
        array('.msi',   'application/x-msi'),
        array('.msi',   'application/x-ole-storage'),
        array('.msi',   'application/msword'),
        array('.msi',   'application/octet-stream')
    )
);

// Process the list of known file types; also, extract the allowed mime content types (only defined mime content types are allowed).
$fileTypesExtensions = $fileTypesExtensions_groups = $allowedMimeTypes = false;
foreach ($fileTypes as $group => $extensions) {
    foreach ($extensions as $extension) {
        $fileTypesExtensions_groups[$group][] = $extension[0];
        $fileTypesExtensions[] = $extension[0];
        
        if ($extension[1]) {
            $allowedMimeTypes[] = $extension[1];
        }
    }
}

##

if ($_POST) {
    recursive('stripslashes', $_POST);
    recursive('trim', $_POST);
    
    if ($_POST['action'] == 'get-objects') {
        $timer = new Timer();
        
        $path = strpos($_POST['path'], '..') === false ? $base . $_POST['path'] : $base;
        $highlight = (strpos($_POST['highlight'], '..') === false && strpos($_POST['highlight'], '/') === false && file_exists($base . $_POST['path'] . $_POST['highlight'])) ? $_POST['highlight'] : false;
        
        if ($fileTypes[$_POST['type']]) {
            $typeFilter = $_POST['type'];
            
            $validFileTypesExtensions = false;
            foreach ($fileTypes[$typeFilter] as $extension) {
                $validFileTypesExtensions[] = $extension[0];
            }
        } else {
            $typeFilter = false;
        }
        
        if ($_POST['query']) {
            $queryFilter = $_POST['query'];
        } else {
            $queryFilter = false;
        }
        
        // If a filter is in effect, ignore the highlighted file.
        if ($typeFilter || $queryFilter) {
            $highlight = false;
        }
        
        $dirs = $files = array();
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . $file)) {
                        if (!$queryFilter || stripos($file, $queryFilter) !== false || $children > 0) {
                            $dirs[$file] = array(
                                'dir' => 1,
                                'name' => $file
                            );
                        }
                    } elseif ((!$typeFilter || @in_array(fileExtension($path . $file), $validFileTypesExtensions)) && (!$queryFilter || stripos($file, $queryFilter) !== false)) {
                        $files[$file] = array(
                            'dir' => 0,
                            'name' => $file
                        );
                    }
                }
            }
            closedir($handle);
            
            if ($dirs) {
                uksort($dirs, 'strnatcasecmp');
                $dirs = array_values($dirs);
            }
            if ($files) {
                uksort($files, 'strnatcasecmp');
                $files = array_values($files);
            }
            
            $objects = array_merge($dirs, $files);
        }
        
        $timer->stop();
        
        $total = count($objects);
        $skip = preg_match('/^[1-9]\d*$/', $_POST['skip']) && $_POST['skip'] < $total ? $_POST['skip'] : 0;
        $count = preg_match('/^[1-9]\d*$/', $_POST['count']) ? $_POST['count'] : $total - $skip;
        
        // If the highlighted file is not listed in the shown files, update the cutoff point (skip + count) so that it does get shown.
        if ($highlight) {
            for ($i = $skip; $i < count($objects); $i++) {
                if (!$objects[$i]['dir'] && $objects[$i]['name'] == $highlight) {
                    if ($i > $skip + $count) {
                        $count = $i - $skip + 1;
                    }
                    break;
                }
            }
        }
        
        $objects = array_slice($objects, $skip, $count);
        
        // Get additional information only for objects currently displayed.
        for ($i = 0; $i != count($objects); $i++) {
            if ($objects[$i]['dir'] == '1') {
                $objects[$i]['children'] = count_files_recursive($path . $objects[$i]['name'] . '/', $validFileTypesExtensions, $queryFilter);
            } else {
                list($objects[$i]['width'], $objects[$i]['height']) = @getimagesize($path . $objects[$i]['name']);
                $objects[$i]['size'] = filesize_human(filesize($path . $objects[$i]['name']));
            }
        }
        
        header('Content-Type: text/xml; charset=UTF-8');
        echo "<?xml version='1.0' encoding='UTF-8'?>" . "\n\n";
        echo '<XMLResult t="' . $timer->time() . '" cnt="' . $total . '" skip="' . $skip . '" count="' . $count . '" highlight="' . $highlight . '">' . "\n";
        for ($i = 0; $i < count($objects); $i++) {
            if ($objects[$i]['dir']) {
                echo '    <i d="1" n="' . xmlentities($objects[$i]['name']) . '" c="' . $objects[$i]['children'] . '"/>' . "\n";
            } else {
                
                echo '    <i d="0" n="' . xmlentities($objects[$i]['name']) . '" s="' . $objects[$i]['size'] . '" w="' . $objects[$i]['width'] . '" h="' . $objects[$i]['height'] . '"/>' . "\n";
            }
        }
        echo '</XMLResult>' . "\n";
    } elseif ($_POST['action'] == 'mv-file') {
        if (isset($_POST['filename']) && isValidObjectName($_POST['filename_new']) && fileExtension($_POST['filename']) == fileExtension($_POST['filename_new']) && strpos($_POST['path'] . $_POST['filename'] . $_POST['filename_new'], '..') === false) {
            if ($_POST['keep_original']) {
                @copy($base . $_POST['path'] . $_POST['filename'], $base . $_POST['path'] . $_POST['filename_new']);
            } else {
                @rename($base . $_POST['path'] . $_POST['filename'], $base . $_POST['path'] . $_POST['filename_new']);
            }
        }
    } elseif ($_POST['action'] == 'rm-file') {
        if (isset($_POST['filename']) && file_exists($base . $_POST['path'] . $_POST['filename']) && strpos($_POST['path'] . $_POST['filename'], '..') === false) {
            @unlink($base . $_POST['path'] . $_POST['filename']);
        }
    } elseif ($_POST['action'] == 'mk-dir') {
        if (isValidObjectName($_POST['directory']) && strpos($_POST['path'] . $_POST['directory'], '..') === false) {
            @mkdir($base . $_POST['path'] . $_POST['directory']);
        }
    } elseif ($_POST['action'] == 'mv-dir') {
        if (isset($_POST['directory']) && isValidObjectName($_POST['directory_new']) && strpos($_POST['path'] . $_POST['directory'] . $_POST['directory_new'], '..') === false) {
            @rename($base . $_POST['path'] . $_POST['directory'], $base . $_POST['path'] . $_POST['directory_new']);
        }
    } elseif ($_POST['action'] == 'rm-dir') {
        if (isset($_POST['directory']) && file_exists($base . $_POST['path'] . $_POST['directory']) && strpos($_POST['path'] . $_POST['directory'], '..') === false) {
            rmdir_recursive($base . $_POST['path'] . $_POST['directory']);
        }
    } elseif ($_POST['action'] == 'resize') {
        $filename = $base . $_POST['path'] . $_POST['filename'];
        if ($_POST['filename'] && file_exists($filename) && strpos($_POST['path'] . $_POST['filename'], '..') === false) {
            if (preg_match('/^\d+$/', $_POST['width']) && $_POST['width'] >= 10 && preg_match('/^\d+$/', $_POST['height']) && $_POST['height'] >= 10) {
                exec('convert -resize ' . $_POST['width'] . 'x' . $_POST['height'] . '! ' . escapeshellarg($filename) . ' ' . escapeshellarg($filename));
            }
        }
    } elseif ($_POST['action'] == 'rotate-cw' || $_POST['action'] == 'rotate-ccw' ) {
        $filename = $base . $_POST['path'] . $_POST['filename'];
        if ($_POST['filename'] && file_exists($filename) && strpos($_POST['path'] . $_POST['filename'], '..') === false) {
            exec('convert -rotate ' . ($_POST['action'] == 'rotate-cw' ? '90' : '-90') . ' ' . escapeshellarg($filename) . ' ' . escapeshellarg($filename));
        }
    } elseif ($_POST['action'] == 'frame') {
        $filename = $base . $_POST['path'] . $_POST['filename'];
        if ($_POST['filename'] && file_exists($filename) && strpos($_POST['path'] . $_POST['filename'], '..') === false) {
            if (preg_match('/^\d+$/', $_POST['width']) && $_POST['width'] >= 1 && preg_match('/^\d+$/', $_POST['height']) && $_POST['height'] >= 1 && preg_match('/^#[0-9A-F]{6}$/i', $_POST['color'])) {
                exec('convert -bordercolor "' . $_POST['color'] . '" -border ' . $_POST['width'] . 'x' . $_POST['height'] . ' ' . escapeshellarg($filename) . ' ' . escapeshellarg($filename));
            }
        }
    }
    
    if ($_FILES['GFM_file']) {
        $_FILES['GFM_file']['name'] = strtolower(trim(str_replace(' ', '_', $_FILES['GFM_file']['name']))); // Also done on the client-side.
        if (!@in_array($_FILES['GFM_file']['type'], $allowedMimeTypes)) {
            $error = 'file type not allowed: ' . $_FILES['GFM_file']['type'];
        } elseif ($_FILES['GFM_file']['error'] != UPLOAD_ERR_OK) {
            $error = $_FILES['GFM_file']['error'];
        } elseif ($_FILES['GFM_file']['size'] > constant('MAX_FILE_SIZE')) {
            $error = 'file size (' . $_FILES['GFM_file']['size'] . ') larger than allowed (' . constant('MAX_FILE_SIZE') . ')';
        } elseif (!isValidObjectName($_FILES['GFM_file']['name'])) {
            $error = 'invalid file name: ' . $_FILES['GFM_file']['name'];
        } elseif (strpos($_POST['path'] . $_POST['filename'], '..') !== false) {
            $error = 'invalid file name; contains ".."';
        } elseif (!@move_uploaded_file($_FILES['GFM_file']['tmp_name'], $base . $_POST['path'] . $_FILES['GFM_file']['name'])) {
            $error = 'could not move uploaded file';
        }
        
        echo ($error ? 'ERROR: ' . $error : 'OKAY');
    }
    
    exit;
}
    
##

## TMP (until "path" and "file" are replaced with a single "url"):
if ($_GET['url']) {
    $_GET['path'] = preg_replace('/^(.+\/)[^\/]*$/', '$1', $_GET['url']);
    $_GET['file'] = preg_replace('/^.+\/([^\/]*)$/', '$1', $_GET['url']);
    
    if (substr($_GET['path'], 0, strlen($baseVirtual)) == $baseVirtual) {
        $_GET['path'] = substr($_GET['path'], strlen($baseVirtual));
    }
    
    //path = path.replace(/\//g, '%2F').replace(/\?/g, '%3F').replace(/=/g, '%3D').replace(/&/g, '%26').replace(/@/g, '%40');
    //file = file.replace(/\//g, '%2F').replace(/\?/g, '%3F').replace(/=/g, '%3D').replace(/&/g, '%26').replace(/@/g, '%40');
}
## END;

// Default path (to be opened) and filename (to be selected and highlighted).
if ($_GET['path'] && file_exists($base . $_GET['path'])) {
    $path = (substr($_GET['path'], -1) == '/') ? $_GET['path'] : $_GET['path'] + '/';
}
if ($_GET['file'] && file_exists($base . $_GET['path'] . $_GET['file'])) {
    $file = $_GET['file'];
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Gyro File Manager</title>
    
    <? if ($noReturn): ?>
    <script type="text/javascript">
    window.onload = function() {
        GFM_init();
    };
    </script>
    <? else : ?>
    <script type="text/javascript" src="/repository/tinymce/jscripts/tiny_mce/tiny_mce_popup.js"></script>
    <script type="text/javascript">
    var FileBrowserDialogue = {
        init: function () {
            GFM_init();
        },
        mySubmit: function () {
            var win = tinyMCEPopup.getWindowArg('window');
            win.document.getElementById(tinyMCEPopup.getWindowArg('input')).value = baseVirtual + currentPath + currentFile;
            tinyMCEPopup.close(); // Close popup window.
        }
    }
    
    tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);
    </script>
    <? endif; ?>
    
    <script type="text/javascript">
    var baseVirtual = '<?=jsQuote($baseVirtual)?>';
    var baseHref    = '<?=jsQuote(href('/'))?>'; // The current absolute path (used to link to files using baseVirtual, both when there is a dedicated SSL certificate and a shared certificate).
    
    var currentPath = '<?=jsQuote($path)?>';
    var currentFile = '<?=jsQuote($file)?>'; // The name of the currently selected file.
    var highlighted = currentFile; // The name of the currently highlighted object (directory, file, or '..').
    var randomseed  = <?=time()?>; // Used to force images to reload after manipulation.
    
    var previewNote = 'Preview';
    var previewUnavailableNote = 'Preview<br>Not Available';
    
    var allowedFileTypes = new Array('<?=implode("', '", $fileTypesExtensions)?>');
    var allowedFileTypes_images = new Array('<?=implode("', '", $fileTypesExtensions_groups['images'])?>'); // Used to determine what files can be previewed using <img>.
    
    var typeFilter = '<?=($_GET['type'] == 'images' ? 'images' : false)?>';
    var queryFilter = '';
    
    var objects = []; // An array of objects (dirs and files) in the current directory.
    
    var noReturn = <?=$noReturn?>;
    
    var uploadFiles_slots = 8; // The number of file upload slots.
    
    /* ## */
    
    function GFM_init() {
        GFM_veil_ajax = document.getElementById('veil-ajax');
        GFM_veil_back = document.getElementById('veil-back');
        
        if (!noReturn) {
            document.getElementById('GFM_selectFile_btn').appendChild(GFM_renderButton('<strong>Select</strong>', '/repository/admin/images/act_check.gif', '', function () { GFM_returnFile() }));
        }
        
        document.getElementById('GFM_uploadFiles_btn').appendChild(GFM_renderButton('Upload Files', '/repository/admin/images/act_save.gif', '', function () { GFM_uploadFiles() }));
        document.getElementById('GFM_NewDirectory_btn').appendChild(GFM_renderButton('New Directory', '/repository/admin/images/act_folder-new.gif', '', function () { GFM_createDir() }));
        document.getElementById('GFM_resizeImage_btn').appendChild(GFM_renderButton('Resize', '/repository/admin/images/act_resize.gif', '', function () { GFM_resizeImage() }));
        document.getElementById('GFM_cropImage_btn').appendChild(GFM_renderButton('Crop', '/repository/admin/images/act_crop.gif', '', function () { GFM_cropImage() }));
        document.getElementById('GFM_frameImage_btn').appendChild(GFM_renderButton('Frame', '/repository/admin/images/act_frame.gif', '', function () { GFM_frameImage() }));
        
        document.getElementById('GFM_rotateCCWImage_btn').appendChild(GFM_renderButton('', '/repository/admin/images/act_rotate_ccw.gif', 'Rotate Counterclockwise', function () {
            GFM_HTTP('action=rotate-ccw&path=' + currentPath + '&filename=' + currentFile, function (doc) {
                GFM_getObjects(currentPath, 0, objects.length, function (cnt) {
                    GFM_list_render(cnt, function () {
                        randomseed++; // This will force a reloading of the image.
                        GFM_selectFile(currentFile);
                    });
                });
                GFM_POPUP_close();
            });
        }));
        document.getElementById('GFM_rotateCWImage_btn').appendChild(GFM_renderButton('', '/repository/admin/images/act_rotate_cw.gif', 'Rotate Clockwise', function () {
            GFM_HTTP('action=rotate-cw&path=' + currentPath + '&filename=' + currentFile, function (doc) {
                GFM_getObjects(currentPath, 0, objects.length, function (cnt) {
                    GFM_list_render(cnt, function () {
                        randomseed++; // This will force a reloading of the image.
                        GFM_selectFile(currentFile);
                    });
                });
                GFM_POPUP_close();
            });
        }));
        
        document.getElementById('GFM_preview').innerHTML = previewNote;
        document.getElementById('GFM_filename').value = baseVirtual + currentPath + currentFile;
        
        // Render the list of objects under the current (default) directory.
        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
            GFM_list_render(cnt);
        });
        
        window.focus();
    }

    /* ## */
    
    var GFM_HTTP_req;
    var GFM_HTTP_active = false;

    function GFM_HTTP(request, callback, debug) {
        if (GFM_HTTP_active) {
            return;
        }
        
        GFM_HTTP_req = false;
        GFM_HTTP_active = true;
        GFM_veil_ajax.style.display = '';
        
        if (window.XMLHttpRequest && !(window.ActiveXObject)) {
            try { GFM_HTTP_req = new XMLHttpRequest() } catch(e) { }
        } else if (window.ActiveXObject) {
            try { GFM_HTTP_req = new ActiveXObject("Msxml2.XMLHTTP") } catch(e) { try { GFM_HTTP_req = new ActiveXObject("Microsoft.XMLHTTP") } catch(e) { } }
        }
        
        if (GFM_HTTP_req) {
            GFM_HTTP_req.onreadystatechange = function () {
                if (GFM_HTTP_req.readyState == 4) {
                    if (GFM_HTTP_req.status == 200) {
                        GFM_HTTP_active = false;
                        GFM_veil_ajax.style.display = 'none';
                        
                        if (debug) {
                            alert(GFM_HTTP_req.responseText);
                        }
                        
                        callback(GFM_HTTP_req);
                    }
                }
            };
            GFM_HTTP_req.open('POST', 'admin/file-manager', true);
            GFM_HTTP_req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            GFM_HTTP_req.setRequestHeader('Content-length', request.length);
            GFM_HTTP_req.setRequestHeader('Connection', 'close');
            GFM_HTTP_req.send(request);
        }
    }
    
    /* ## */
    
    var GFM_veil_ajax;
    var GFM_veil_back;
    
    var GFM_POPUP_active = false;
    var GFM_POPUP_defact = false;
    var GFM_POPUP_unload = false;
    
    function GFM_POPUP(title, content, buttons, onload, onunload) {
        if (GFM_POPUP_active) {
            return false;
        }
        
        GFM_POPUP_active = true;
        
        window.onresize = function () {
            if (GFM_POPUP_active) {
                var dialog = document.getElementById('GFM_POPUP_dialog');
                dialog.style.top = GFM_veil_back.offsetHeight / 2 - parseInt(dialog.style.height) / 2;
                dialog.style.left = GFM_veil_back.offsetWidth / 2 - parseInt(dialog.style.width) / 2;
            }
        }
        
        GFM_POPUP_defact = buttons ? buttons[0][4] : false; // Set the default button action. Triggered by 'enter'.
        
        var buttons_span = new Array();
        for (var i = 0; i != buttons.length; i++) {
            buttons_span[i] = '<span id="' + buttons[i][0] + '"></span>';
        }
        
        GFM_veil_back.style.display = '';
        
        var dialog = document.createElement('div');
        dialog.id = 'GFM_POPUP_dialog';
        dialog.innerHTML = '<table cellspacing="0" cellpadding="0">'
            + '    <tr id="dialog-draggable-handle">'
            + '        <td style="width: 30px; height: 30px;"><img src="/repository/admin/images/dialog-tl.png" style="width: 30px; height: 30px;"></td>'
            + '        <td><img src="/repository/admin/images/dialog-t.png" style="width: 100%; height: 30px;"></td>'
            + '        <td style="width: 30px; height: 30px;"><img src="/repository/admin/images/dialog-tr.png" style="width: 30px; height: 30px;"></td>'
            + '        <td style="position: relative;"><map name="GFM_POPUP_dialog_close_button_map"><area shape="circle" coords="26,26,16" href="" onclick="GFM_POPUP_close(); return false;"></map><img src="/repository/admin/images/dialog-x.png" usemap="#GFM_POPUP_dialog_close_button_map" style="position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; border: 0;"></td>'
            + '    </tr>'
            + '    <tr>'
            + '        <td style="width: 30px; height: 100%;"><img src="/repository/admin/images/dialog-l.png" style="width: 30px; height: 100%;"></td>'
            + '        <td style="background-color: #FFFFFF;">'
            + '            <div class="title">' + title + '</div>'
            + '            <div class="content">' + content + '</div>'
            + '            <div class="buttons">' + buttons_span.join('&nbsp;') + '</div>'
            + '        </td>'
            + '        <td style="width: 30px; height: 100%;"><img src="/repository/admin/images/dialog-r.png" style="width: 30px; height: 100%;"></td>'
            + '    </tr>'
            + '    <tr>'
            + '        <td style="width: 30px; height: 30px;"><img src="/repository/admin/images/dialog-bl.png" style="width: 30px; height: 30px;"></td>'
            + '        <td><img src="/repository/admin/images/dialog-b.png" style="width: 100%; height: 30px;"></td>'
            + '        <td style="width: 30px; height: 30px;"><img src="/repository/admin/images/dialog-br.png" style="width: 30px; height: 30px;"></td>'
            + '    </tr>'
            + '</table>'
            + '<div class="buttons">' + buttons_span.join('&nbsp;') + '</div>';
        
        document.body.insertBefore(dialog, document.body.firstChild);
        
        dialog.style.width = dialog.offsetWidth;
        dialog.style.height = dialog.offsetHeight + 7;
        dialog.style.top = GFM_veil_back.offsetHeight / 2 - parseInt(dialog.style.height) / 2;
        dialog.style.left = GFM_veil_back.offsetWidth / 2 - parseInt(dialog.style.width) / 2;
        
        for (var i = 0; i != buttons.length; i++) {
            document.getElementById(buttons[i][0]).appendChild(GFM_renderButton(buttons[i][1], buttons[i][2], buttons[i][3], buttons[i][4]));
        }
        
        if (onload) {
            onload();
        }
        
        GFM_POPUP_unload = onunload;
    }

    function GFM_POPUP_close() {
        GFM_veil_back.style.display = 'none';
        document.getElementById('GFM_POPUP_dialog').parentNode.removeChild(document.getElementById('GFM_POPUP_dialog'));
        
        if (GFM_POPUP_unload) {
            GFM_POPUP_unload();
        }
        
        GFM_POPUP_active = false;
    }
    
    /* ## */

    // Return all objects under 'path' (if 'path' is empty, returns all objects under root).
    function GFM_getObjects(path, skip, count, callback) {
        GFM_HTTP('action=get-objects&path=' + path + '&highlight=' + highlighted + '&skip=' + skip + '&count=' + count + '&type=' + typeFilter + '&query=' + queryFilter, function (doc) {
            if (!skip) {
                objects = [];
            }
            
            if (doc.responseXML.documentElement.getElementsByTagName('i')[0]) {
                for (var i = 0, object; i != doc.responseXML.documentElement.getElementsByTagName('i').length; i++) {
                    object = doc.responseXML.documentElement.getElementsByTagName('i')[i];
                    
                    if (object.getAttribute('d') == 1) {
                        objects[objects.length] = {'d': 1, 'n': object.getAttribute('n'), 'c': object.getAttribute('c')};
                    } else {
                        objects[objects.length] = {'d': 0, 'n': object.getAttribute('n'), 's': object.getAttribute('s'), 'w': object.getAttribute('w'), 'h': object.getAttribute('h')};
                    }
                }
            }
            
            callback(doc.responseXML.documentElement.getAttribute('cnt'));
        });
        
        return;
    }
    
    // Get an object, along with all its attributes (including children), by its full path (path and name).
    function GFM_getObject(objPath) {
        var len = objPath.lastIndexOf('/');
        
        if (len != -1) {
            //var objects_tmp = GFM_getObjects(objPath.substr(0, len));
            var objName = objPath.substr(len + 1, objPath.length);
        } else {
            //var objects_tmp = objects;
            var objName = objPath;
        }
        
        for (var i = 0; i != objects.length; i++) {
            if (objects[i].n == objName) {
                return objects[i];
            }
        }
        
        return false;
    }

    function GFM_isValidObjectName(obj) {
        return /^[a-z0-9][a-z0-9\s\_\.\-]{0,99}$/i.test(obj);
    }

    function GFM_getFileExt(filename) {
        var pos = filename.lastIndexOf('.');
        return (pos != -1) ? filename.substring(pos).toLowerCase() : '';
    }

    function GFM_htmlDecode(str) {
        return str.replace(/\&lt\;/g, '<').replace(/\&gt\;/g, '>').replace(/\&quot\;/g, '"').replace(/\&amp\;/g, '&');
    }

    /* ## */

    // Change the current directoy.
    function GFM_selectDir(directory) {
        // Do not allow skipping directories (a directory cannot contain a slash).
        if (directory.indexOf('/') != -1) {
            return;
        }
        
        // Step 1: Remove an existing trailing slash from the 'currentPath'.
        if (currentPath.indexOf('/') != -1) {
            currentPath = currentPath.substr(0, currentPath.length - 1);
        }
        // Step 2: Go to the requested directory (including the parent directory).
        if (directory == '..') {
            if (currentPath.indexOf('/') != -1) {
                highlighted = currentPath.substr(currentPath.lastIndexOf('/') + 1, currentPath.length);
                currentPath = currentPath.substr(0, currentPath.lastIndexOf('/'));
            } else {
                highlighted = currentPath;
                currentPath = '';
            }
            directory = '';
        } else if (directory) {
            directory += '/';
        }
        // Step 3: If not in parent directory, append a trailing slash.
        if (currentPath) {
            currentPath += '/';
        }
        // Set the new 'currentPath'. If not empty (not root), it must have a trailing slash.
        currentPath += directory;
        
        document.getElementById('GFM_filename').value = baseVirtual + currentPath; // Update the 'address' field.
        
        GFM_unselectFile(); // Unselect the currently selected file (if any).
        
        // Rended the list of objects under the new directory.
        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
            GFM_list_render(cnt, function () {
                // When moving up to the parent directory, highlight the previous directory.
                // When moving down to a sub-directory, highlight the '..' entry.
                if (highlighted) {
                    GFM_list_objectHighlight(highlighted);
                } else if (currentPath) {
                    GFM_list_objectHighlight('..');
                }
            })
        });
    }

    // Remove all traces of the selected file (preview, action buttons, 'address' field, etc.).
    function GFM_unselectFile() {
        GFM_list_objectHighlight(); // Unhighlight the object.
        
        currentFile = false;
        
        document.getElementById('GFM_filename').value = baseVirtual + currentPath;
        document.getElementById('GFM_preview').innerHTML = previewNote;
        document.getElementById('GFM_previewBand').style.display = 'none';
        document.getElementById('GFM_selectFile_btn').style.display = 'none';
        document.getElementById('GFM_imageActions').style.display = 'none';
        
        GFM_list_adjustScroll();
    }

    // Select a file by name.
    function GFM_selectFile(filename) {
        GFM_unselectFile(); // Unselect the currently selected file (if any).
        
        // Get the corresponding object with all its attributes.
        var file = GFM_getObject(currentPath + filename);
        if (file) {
            currentFile = filename;
            
            GFM_list_objectHighlight(filename); // Highlight the file.
            
            var extension = GFM_getFileExt(filename);
            
            // Determine whether the file is an image (based on its extension).
            var isImage = false;
            for (var i = 0; i != allowedFileTypes_images.length; i++) {
                if ((allowedFileTypes_images[i]) == extension) {
                    isImage = true;
                    break;
                }
            }
            
            // If dimensions are specified for the file, calculate new dimensions that will fit the preview pane, keeping the aspect-ratio.
            if (file.w || file.h) {
                var width = parseInt(file.w), height = parseInt(file.h);
                var nWidth = width, nHeight = height;
                
                if (width > height && width > 290) {
                    nWidth = 290;
                    nHeight = Math.round(height / (width / 290));
                } else if (height > 290) {
                    nWidth = Math.round(width / (height / 290));
                    nHeight = 290;
                }
                
                // If the new dimensions are smaller than the original, show the 'scaled' band on the preview pane.
                if (width != nWidth || height != nHeight) {
                    document.getElementById('GFM_previewBand_link').href = baseHref + baseVirtual + currentPath + filename;
                    document.getElementById('GFM_previewBand').style.display = '';
                }
            }
            
            var preview = document.getElementById('GFM_preview');
            
            // Preview the file, according to its type.
            if (isImage) {
                preview.innerHTML = '<img src="' + baseHref + baseVirtual + currentPath + filename + '?' + randomseed + '" width="' + nWidth + '" height="' + nHeight + '">';
                
                document.getElementById('GFM_imageActions').style.display = ''; // Show the actions available for image manipulations.
            } else if (extension == '.swf') {
                preview.innerHTML = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="' + nWidth + '" height="' + nHeight + '">'
                     + '<param name="movie" value="' + baseHref + baseVirtual + currentPath + filename + '?' + randomseed + '">'
                     + '<param name="quality" value="high">'
                     + '<embed src="' + baseHref + baseVirtual + currentPath + filename + '?' + randomseed + '" quality="high" pluginspage="https://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="' + nWidth + '" height="' + nHeight + '"></embed>'
                     + '</object>';
            } else {
                preview.innerHTML = previewUnavailableNote + '<div style="margin-top: 25px; color: #CCCCCC;">[ <a href="' + baseHref + baseVirtual + currentPath + filename + '" target="_blank" style="color: #CCCCCC; text-decoration: none;">download file</a> ]</div>';
            }
            
            document.getElementById('GFM_filename').value = baseVirtual + currentPath + filename;
            document.getElementById('GFM_selectFile_btn').style.display = ''; // Show the 'Select' button.
            
            GFM_list_adjustScroll();
        }
    }

    /* ## */

    // Render the list of objects under the current directory, and select the first entity.
    function GFM_list_render(cnt, callback) {
        var GFM_list_container = document.getElementById('GFM_list_container');
        
        // Clear the list.
        while (GFM_list_container.childNodes.length > 0) {
            GFM_list_container.removeChild(GFM_list_container.firstChild);
        }
        
        if (objects) {
            var bg = 0; // Used to alternate the background color.
            
            // If the current directory is not root, show the '..' entry.
            if (currentPath) {
                GFM_list_container.appendChild(GFM_list_renderDirUp());
                ++bg;
            }
            
            if (objects.length > 0) {
                for (var i = 0; i != objects.length; i++) {
                    if (objects[i].d == 1) {
                        GFM_list_container.appendChild(GFM_list_renderDir(objects[i], ((++bg % 2) ? '#EEEEEE' : '#FFFFFF')));
                    } else if (objects[i].d == 0) {
                        GFM_list_container.appendChild(GFM_list_renderFile(objects[i], ((++bg % 2) ? '#EEEEEE' : '#FFFFFF')));
                    }
                }
                
                if (cnt > objects.length) {
                    GFM_list_container.appendChild(GFM_list_renderMoreFiles(cnt, (++bg % 2) ? '#EEEEEE' : '#FFFFFF'));
                }
            } else {
                GFM_list_container.appendChild(GFM_list_renderNoFiles());
            }
        }
        
        // Highlight the first available: either the current (default) file, or the '..' entry, or the first object (directory or file) under the current directory.
        if (currentFile) {
            GFM_selectFile(currentFile);
        } else if (currentPath) {
            GFM_list_objectHighlight('..');
        } else {
            if (objects.length > 0) {
                if (objects[0].d == 1) {
                    GFM_list_objectHighlight(objects[0].n);
                } else {
                    GFM_selectFile(objects[0].n);
                }
            }
        }
        
        if (callback) {
            callback();
        }
    }

    function GFM_list_renderNoFiles() {
        var tr, td;
        
        tr = document.createElement('tr');
        
        td = document.createElement('td');
        td.colSpan = '4';
        td.style.height = '230px';
        td.style.textAlign = 'center';
        td.style.verticalAlign = 'middle';
        td.innerHTML = 'No files.';
        tr.appendChild(td);
        
        return tr;
    }
    
    function GFM_list_renderDirUp() {
        var tr, td;
        
        tr = document.createElement('tr');
        tr.style.backgroundColor = '#EEEEEE';
        
        td = document.createElement('td');
        td.colSpan = '4';
        td.style.height = '28px';
        td.style.backgroundRepeat = 'no-repeat';
        td.style.backgroundPosition = '3px';
        td.style.paddingLeft = '25px';
        td.style.color = '#000000';
        td.style.fontWeight = 'bold';
        td.style.textDecoration = 'underline';
        td.style.cursor = 'pointer';
        td.className = 'icon_folder-up'; // Made necessary due to a bug in IE (http://support.microsoft.com/kb/925014).
        td.onmouseover = function () {
            this.style.color = '#666666';
        }
        td.onmouseout = function () {
            this.style.color = '#000000';
        }
        td.onclick = function () {
            GFM_selectDir('..');
        }
        td.onfocus = function () {
            this.blur();
        }
        td.innerHTML = '..';
        tr.appendChild(td);
        
        return tr;
    }

    function GFM_list_renderDir(dir, bg) {
        var tr, td;
        
        tr = document.createElement('tr');
        tr.style.backgroundColor = bg;
        
        td = document.createElement('td');
        td.style.width = '100%';
        td.style.height = '28px';
        td.style.backgroundRepeat = 'no-repeat';
        td.style.backgroundPosition = '3px';
        td.style.paddingLeft = '25px';
        td.style.color = '#000000';
        td.style.fontWeight = 'bold';
        td.style.textDecoration = 'underline';
        td.style.cursor = 'pointer';
        td.className = 'icon_dir'; // Made necessary due to a bug in IE (http://support.microsoft.com/kb/925014).
        td.onmouseover = function () {
            this.style.color = '#666666';
        }
        td.onmouseout = function () {
            this.style.color = '#000000';
        }
        td.onclick = function () {
            GFM_selectDir(dir.n);
        }
        td.onfocus = function () {
            this.blur();
        }
        td.innerHTML = dir.n;
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.className = 'attributes';
        td.innerHTML = '[ ' + ((dir.c > 0) ? dir.c + ' files' : 'empty') + ' ]';
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.appendChild(GFM_renderButton('', '/repository/admin/images/act_rename.gif', 'Rename (F2)', function () { GFM_renameDir(dir.n) }, bg));
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.appendChild(GFM_renderButton('', '/repository/admin/images/act_delete.gif', 'Delete (Del)', function () { GFM_deleteDir(dir.n) }, bg));
        tr.appendChild(td);
        
        return tr;
    }

    function GFM_list_renderFile(file, bg) {
        var tr, td;
        
        var icon;
        switch (GFM_getFileExt(file.n)) {
            case '.gif':  icon = 'gif'; break;
            case '.jpg':  
            case '.jpeg': icon = 'jpg'; break;
            case '.png':  icon = 'png'; break;
            
            case '.swf':  icon = 'swf'; break;
            
            case '.avi':  
            case '.mov':  
            case '.rm':   
            case '.mpg':  
            case '.mpeg': icon = 'mov'; break;
            case '.mp3':  icon = 'mp3'; break;
            case '.aif':  
            case '.mid':  
            case '.midi': 
            case '.mov':  
            case '.ra':   
            case '.ram':  
            case '.wav':  icon = 'sound'; break;
            
            case '.txt':  icon = 'txt'; break;
            case '.pdf':  icon = 'pdf'; break;
            case '.doc':  icon = 'doc'; break;
            case '.ppt':  
            case '.pps':  icon = 'ppt'; break;
            case '.xls':  icon = 'xls'; break;
            
            case '.zip':  icon = 'zip'; break;
            
            default:      icon = 'unknown';
        }
        
        tr = document.createElement('tr');
        tr.style.backgroundColor = bg;
        
        td = document.createElement('td');
        td.style.width = '100%';
        td.style.height = '28px';
        td.className = 'icon_' + icon; // Made necessary due to a bug in IE (http://support.microsoft.com/kb/925014).
        td.style.backgroundRepeat = 'no-repeat';
        td.style.backgroundPosition = '3px';
        td.style.paddingLeft = '25px';
        td.style.color = '#000000';
        td.style.textDecoration = 'underline';
        td.style.cursor = 'pointer';
        td.onmouseover = function () {
            this.style.color = '#666666';
        }
        td.onmouseout = function () {
            this.style.color = '#000000';
        }
        td.onclick = function () {
            GFM_selectFile(file.n);
        }
        td.onfocus = function () {
            this.blur();
        }
        td.innerHTML = file.n;
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.className = 'attributes';
        td.innerHTML = file.s;
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.appendChild(GFM_renderButton('', '/repository/admin/images/act_rename.gif', 'Rename (F2)', function () { GFM_renameFile(file.n) }, bg));
        tr.appendChild(td);
        
        td = document.createElement('td');
        td.appendChild(GFM_renderButton('', '/repository/admin/images/act_delete.gif', 'Delete (Del)', function () { GFM_deleteFile(file.n) }, bg));
        tr.appendChild(td);
        
        return tr;
    }
    
    function GFM_list_renderMoreFiles(cnt, bg) {
        var tr, td, span;
        
        tr = document.createElement('tr');
        tr.style.backgroundColor = bg;
        
        td = document.createElement('td');
        td.colSpan = '4';
        td.style.height = '28px';
        td.style.paddingLeft = '25px';
        td.style.color = '#000000';
        td.style.fontWeight = 'bold';
        td.style.fontVariant = 'small-caps';
        td.style.textAlign = 'center';
        td.style.cursor = 'pointer';
        
        td.appendChild(document.createTextNode('[ '));
        
        span = document.createElement('span');
        span.onmouseover = function () {
            this.style.color = '#666666';
        }
        span.onmouseout = function () {
            this.style.color = '#000000';
        }
        span.onclick = function () {
            GFM_getObjects(currentPath, objects.length, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                GFM_list_render(cnt);
            });
        }
        span.onfocus = function () {
            this.blur();
        }
        span.innerHTML = 'get the ' + (cnt - objects.length > <?=constant('OBJECTS_COUNT')?> ? 'next <?=constant('OBJECTS_COUNT')?>' : 'last ' + (cnt - objects.length)) + ' rows';
        td.appendChild(span);
        
        td.appendChild(document.createTextNode(' | '));
        
        span = document.createElement('span');
        span.onmouseover = function () {
            this.style.color = '#666666';
        }
        span.onmouseout = function () {
            this.style.color = '#000000';
        }
        span.onclick = function () {
            GFM_getObjects(currentPath, objects.length, 0, function (cnt) {
                GFM_list_render(cnt);
            });
        }
        span.onfocus = function () {
            this.blur();
        }
        span.innerHTML = 'get all remaining rows';
        td.appendChild(span);
        
        td.appendChild(document.createTextNode(' ]'));
        
        tr.appendChild(td);
        
        return tr;
    }
    
    function GFM_renderButton(caption, image, alt, onclick, bg) {
        var btn = document.createElement('button');
        
        btn.setAttribute('type', 'button');
        
        if (bg) btn.style.borderColor = bg;
        if (alt) btn.title = alt;
        if (image) btn.innerHTML += '<img src="' + image + '">';
        if (image && caption) btn.innerHTML += '&nbsp;&nbsp;';
        if (caption) btn.innerHTML += caption;
        
        btn.onmouseover = function () {
            this.setAttribute('defaultBorderColor', this.style.borderColor);
            this.setAttribute('defaultColor', this.style.color);
            
            this.style.borderColor = '#888888';
            this.style.color = '#666666';
        }
        
        btn.onmouseout = function () {
            this.style.borderColor = this.getAttribute('defaultBorderColor');
            this.style.color = this.getAttribute('defaultColor');
        }
        
        btn.onclick = function () {
            onclick();
            this.blur();
        };
        
        return btn;
    }
    
    // Highlight an object on the list (directory or file), or the '..' entry.
    function GFM_list_objectHighlight(objName) {
        var GFM_list_container = document.getElementById('GFM_list_container');
        
        // If there's an already highlighted object, remove its highlighting.
        if (highlighted) {
            for (var i = 0, cellFilename; i != GFM_list_container.childNodes.length; i++) {
                cellFilename = GFM_list_container.childNodes[i].childNodes[0];
                if (GFM_htmlDecode(cellFilename.innerHTML) == highlighted) {
                    highlighted = false;
                    
                    cellFilename.parentNode.style.backgroundColor = cellFilename.parentNode.getAttribute('defaultBackgroundColor');
                }
            }
        }
        
        // Hightlight the requested object.
        if (objName) {
            for (var i = 0, cellFilename; i != GFM_list_container.childNodes.length; i++) {
                cellFilename = GFM_list_container.childNodes[i].childNodes[0];
                if (GFM_htmlDecode(cellFilename.innerHTML) == objName) {
                    highlighted = objName;
                    
                    cellFilename.parentNode.setAttribute('defaultBackgroundColor', cellFilename.parentNode.style.backgroundColor);
                    cellFilename.parentNode.style.backgroundColor = '#FFFFCC';
                }
            }
            
            GFM_list_adjustScroll();
        }
    }

    // Adjust the scrolling of the list so as to position the highlighted object in view.
    function GFM_list_adjustScroll() {
        if (highlighted) {
            var GFM_list = document.getElementById('GFM_list');
            
            if (highlighted == '..') {
                GFM_list.scrollTop = 0;
            } else {
                var GFM_list_container = document.getElementById('GFM_list_container');
                for (var i = 0, cellFilename; i != GFM_list_container.childNodes.length; i++) {
                    cellFilename = GFM_list_container.childNodes[i].childNodes[0];
                    if (GFM_htmlDecode(cellFilename.innerHTML) == highlighted) {
                        GFM_list.scrollTop = cellFilename.offsetTop - 130;
                    }
                }
            }
        }
    }

    /* ## */

    function GFM_updateTypeFilter(type) {
        typeFilter = type;
        
        // Render the list of objects under the current (default) directory.
        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
            GFM_list_render(cnt);
        });
    }

    /* ## */
    
    function GFM_updateQueryFilter(query) {
        if (query != queryFilter) {
            queryFilter = query;
            
            // Render the list of objects under the current (default) directory.
            GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                GFM_list_render(cnt);
            });
        }
    }

    /* ## */

    function GFM_createDir() {
        GFM_POPUP(
            'Create Directory',
            '<input type="text" id="GFM_createDir_directory" style="width: 100%;">',
            Array(
                Array('GFM_createDir_create_btn', 'Create', '/repository/admin/images/act_folder-new.gif', '', function () {
                    var directory = document.getElementById('GFM_createDir_directory').value;
                    
                    if (directory && directory != null) {
                        if (GFM_isValidObjectName(directory)) {
                            var isExistingName = false;
                            if (objects.length > 0) {
                                for (var i = 0; i != objects.length; i++) {
                                    if (objects[i].n == directory) {
                                        isExistingName = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!isExistingName || confirm('A file or directory with this name already exists. Would you like to overwrite it?')) {
                                GFM_HTTP('action=mk-dir&path=' + currentPath + '&directory=' + directory, function (doc) {
                                    GFM_selectDir(directory);
                                    GFM_POPUP_close();
                                });
                            }
                        } else {
                            alert('Invalid directory.');
                        }
                    }
                })
            ),
            function () {
                document.getElementById('GFM_createDir_directory').focus();
            }
        );
    }

    function GFM_renameDir(directory) {
        GFM_POPUP(
            'Rename Directory',
            '<input type="text" id="GFM_renameDir_directory_new" value="' + directory + '" style="width: 100%;">',
            Array(
                Array('GFM_renameDir_rename_btn', 'Rename', '/repository/admin/images/act_rename.gif', '', function () {
                    var directory_new = document.getElementById('GFM_renameDir_directory_new').value;
                    
                    if (directory_new && directory_new != null && directory_new != directory) {
                        if (GFM_isValidObjectName(directory_new)) {
                            var isExistingName = false;
                            if (objects.length > 0) {
                                for (var i = 0; i != objects.length; i++) {
                                    if (objects[i].n == directory_new) {
                                        isExistingName = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!isExistingName || confirm('A file or directory with this name already exists. Would you like to overwrite it?')) {
                                GFM_HTTP('action=mv-dir&path=' + currentPath + '&directory=' + directory + '&directory_new=' + directory_new, function (doc) {
                                    GFM_selectDir(directory_new);
                                    GFM_POPUP_close();
                                });
                            }
                        } else {
                            alert('Invalid directory.');
                        }
                    }
                })
            ),
            function () {
                document.getElementById('GFM_renameDir_directory_new').select();
            }
        );
    }

    function GFM_deleteDir(directory) {
        var contents = GFM_getObject(currentPath + directory).c;
        GFM_POPUP(
            'Delete Directory',
            'Directory: ' + directory + '\
             <p>Are you sure you want to delete this directory' + (contents > 0 ? ' <em>and all its contents</em>' : '') + '?',
            Array(
                Array('GFM_deleteDir_delete_btn', 'Delete', '/repository/admin/images/act_delete.gif', '', function () {
                    GFM_HTTP('action=rm-dir&path=' + currentPath + '&directory=' + directory, function (doc) {
                        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                            GFM_list_render(cnt);
                        });
                        GFM_POPUP_close();
                    });
                })
            )
        );
    }

    /* ## */
    
    var GFM_uploadFiles_intervalId = false; // Used to query the ACP Upload Progress.
    var GFM_uploadFiles_fileInput = document.createElement('input');
        GFM_uploadFiles_fileInput.setAttribute('type', 'file');
        GFM_uploadFiles_fileInput.setAttribute('name', 'GFM_file');
        GFM_uploadFiles_fileInput.setAttribute('size', '37');
        GFM_uploadFiles_fileInput.setAttribute('onchange', 'GFM_uploadFiles_validateFile(this)');
        GFM_uploadFiles_fileInput.style.width = '275px';
    
    function GFM_uploadFiles() {
        // Prepare the HTML code for the file upload inputs.
        fileInputHTML = '';
        for (var i = 0; i != uploadFiles_slots; i++) {
            fileInputHTML += '<form method="POST" enctype="multipart/form-data" target="GFM_uploadFiles_frame" id="GFM_uploadFiles_form_' + i + '">\
                              <input type="hidden" name="APC_UPLOAD_PROGRESS" value="<?=md5(uniqid(rand()))?>">\
                              <input type="hidden" name="MAX_FILE_SIZE" value="<?=constant('MAX_FILE_SIZE')?>">\
                              <input type="hidden" name="path" value="' + currentPath + '">\
                              <input type="file" name="GFM_file" id="GFM_uploadFiles_file_' + i + '" size="37" onchange="GFM_uploadFiles_validateFile(this)" style="width: 275px;">\
                              </form>';
        }
        
        GFM_POPUP(
            'Upload Files',
            fileInputHTML + '\
            <iframe id="GFM_uploadFiles_frame" name="GFM_uploadFiles_frame" onload="GFM_uploadFiles_frame__onload(this)" style="display: none"></iframe>\
            <div>Status: <span id="GFM_uploadFiles_status">select files to upload.</span></div>',
            Array(
                Array('GFM_uploadFiles_upload_btn', 'Upload', '/repository/admin/images/act_save.gif', '', function () {
                    var uploadFiles = new Array();
                    
                    // Prepare an array (uploadFiles) with the indexes of valid file inputs (and their corresponding forms).
                    for (var i = 0; i <= uploadFiles_slots; i++) {
                        if (document.getElementById('GFM_uploadFiles_file_' + i) && document.getElementById('GFM_uploadFiles_file_' + i).value) {
                            uploadFiles[uploadFiles.length] = i;
                        }
                    }
                    
                    // Start uploading the first file.
                    if (uploadFiles.length > 0) {
                        GFM_uploadFiles_uploadFile(uploadFiles, 0);
                    }
                })
            )
        );
    }
    
    // Dummy function that is set dynamically later on (required to work in IE).
    function GFM_uploadFiles_frame__onload(element) {
    }
    
    // Validate a file input; if successful, store the processed filename in the input (as an attribute); if failed, replace the file input element with a new one, thereby resetting its value.
    function GFM_uploadFiles_validateFile(element) {
        var valid = false;
        
        var filename = element.value;
        filename = filename.toLowerCase().replace(/^\s+|\s+$/g, '').replace(/\s/g, '_'); // Also done on the server-side.
        
        // Allow for Linux filenames (using slashes instead of forward slashes).
        if (filename.lastIndexOf('\\') == -1 && filename.lastIndexOf('/') > 0) {
            filename = filename.substring(filename.lastIndexOf('/') + 1);
        } else {
            filename = filename.substring(filename.lastIndexOf('\\') + 1);
        }
        
        var extension = GFM_getFileExt(filename);
        
        var isExistingName = false;
        if (objects.length > 0) {
            for (var i = 0; i != objects.length; i++) {
                if (objects[i].n == filename) {
                    isExistingName = true;
                    break;
                }
            }
        }
        
        var isValidExtension = false;
        for (var i = 0; i != allowedFileTypes.length; i++) {
            if ((allowedFileTypes[i]) == extension) {
                isValidExtension = true;
                break;
            }
        }
        
        if (filename) {
            if (GFM_isValidObjectName(filename)) {
                if (isValidExtension) {
                    if (!isExistingName || confirm('A file or directory with this name already exists. Would you like to overwrite it?')) {
                        valid = true;
                    }
                } else {
                    alert('Invalid file type.');
                }
            } else {
                alert('Invalid filename.');
            }
        }
        
        if (valid) {
            element.setAttribute('filename', filename);
        } else {
            var fileInput_new = GFM_uploadFiles_fileInput.cloneNode(false);
            fileInput_new.setAttribute('id', element.getAttribute('id'));
            element.parentNode.replaceChild(fileInput_new, element);
        }
    }
    
    // Upload the file corresponding to uploadFiles[idx]; uploadFiles is an array of valid file inputs.
    // Once done, continue to the next value of uploadFiles, until exhausted, then select the last file and exit.
    function GFM_uploadFiles_uploadFile(uploadFiles, idx) {
        clearInterval(GFM_uploadFiles_intervalId);
        document.getElementById('GFM_uploadFiles_status').innerHTML = 'upload #' + (idx + 1) + ' in progress...';
        
        GFM_uploadFiles_frame__onload = function (element) {
            if (element.contentWindow.document.body.innerHTML) {
                if (element.contentWindow.document.body.innerHTML != 'OKAY') {
                    alert(element.contentWindow.document.body.innerHTML + ' (#' + (idx + 1) + ')');
                }
                
                // Unless element was the last file, start uploading the next file.
                if (idx < uploadFiles.length - 1) {
                    GFM_uploadFiles_uploadFile(uploadFiles, idx + 1);
                } else {
                    GFM_HTTP_active = false; // Required so that the following code is not skipped because the ACP Upload Progress is still using up GFM_HTTP().
                    GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                        GFM_list_render(cnt, function () {
                            GFM_selectFile(document.getElementById('GFM_uploadFiles_file_' + uploadFiles[idx]).getAttribute('filename'));
                        });
                        GFM_POPUP_close();
                    });
                }
            }
        }
        
        document.getElementById('GFM_uploadFiles_form_' + uploadFiles[idx]).submit();
        
        // ACP Upload Progress.
        GFM_uploadFiles_intervalId = setInterval(function() {
            if (document.getElementsByName('APC_UPLOAD_PROGRESS')[uploadFiles[idx]]) {
                GFM_HTTP('uid=' + document.getElementsByName('APC_UPLOAD_PROGRESS')[uploadFiles[idx]].value, function (doc) {
                    var progress = eval("(" + doc.responseText + ")");
                    
                    if (progress.done == '0') {
                        document.getElementById('GFM_uploadFiles_status').innerHTML = 'upload #' + (idx + 1) + ' in progress... ' + parseInt(progress.current / progress.total * 100) + '% complete.';
                    }
                });
            }
        }, 1000);
    }
    
    /* ## */
    
    function GFM_renameFile(filename) {
        GFM_POPUP(
            'Rename File',
            '<input type="text" id="GFM_renameFile_filename_new" value="' + filename + '" style="width: 100%;">\
             <p><label for="GFM_renameFile_keep_original"><input type="checkbox" id="GFM_renameFile_keep_original"> Keep original file</label>',
            Array(
                Array('GFM_renameFile_rename_btn', 'Rename', '/repository/admin/images/act_rename.gif', '', function () {
                    var filename_new = document.getElementById('GFM_renameFile_filename_new').value;
                    
                    if (filename_new && filename_new != null && filename_new != filename) {
                        if (GFM_isValidObjectName(filename_new)) {
                            if (GFM_getFileExt(filename) == GFM_getFileExt(filename_new)) {
                                var isExistingName = false;
                                if (objects.length > 0) {
                                    for (var i = 0; i != objects.length; i++) {
                                        if (objects[i].n == filename_new) {
                                            isExistingName = true;
                                            break;
                                        }
                                    }
                                }
                                
                                if (!isExistingName || confirm('A file or directory with this name already exists. Would you like to overwrite it?')) {
                                    GFM_HTTP('action=mv-file&path=' + currentPath + '&filename=' + filename + '&filename_new=' + filename_new + '&keep_original=' + (document.getElementById('GFM_renameFile_keep_original').checked ? '1' : '0'), function (doc) {
                                        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                                            GFM_list_render(cnt, function () {
                                                GFM_selectFile(filename_new);
                                            });
                                        });
                                        GFM_POPUP_close();
                                    });
                                }
                            } else {
                                alert('Cannot change file extension.');
                            }
                        } else {
                            alert('Invalid filename.');
                        }
                    }
                })
            ),
            function () {
                document.getElementById('GFM_renameFile_filename_new').select();
            }
        );
    }

    function GFM_deleteFile(filename) {
        GFM_POPUP(
            'Delete File',
            'File: ' + filename + '\
             <p>Are you sure you want to delete this file?',
            Array(
                Array('GFM_deleteFile_delete_btn', 'Delete', '/repository/admin/images/act_delete.gif', '', function () {
                    GFM_HTTP('action=rm-file&path=' + currentPath + '&filename=' + filename, function (doc) {
                        GFM_getObjects(currentPath, 0, <?=constant('OBJECTS_COUNT')?>, function (cnt) {
                            GFM_unselectFile();
                            GFM_list_render(cnt);
                        });
                        GFM_POPUP_close();
                    });
                })
            )
        );
    }

    /* ## */

    function GFM_resizeImage() {
        var file = GFM_getObject(currentPath + currentFile);
        GFM_POPUP(
            'Resize Image',
            '<input type="hidden" id="GFM_imageResize_aspectRatio" value="' + (file.w / file.h) + '">\
             <table cellspacing="0" cellpadding="3">\
                 <tr>\
                     <td>Width:</td>\
                     <td><input type="text" id="GFM_imageResize_width" value="' + file.w + '" maxlength="4" tabindex="1" style="width: 40px;" onkeyup="GFM_resizeImage_chkAR(this)" onfocus="GFM_resizeImage_chkAR(this)" onblur="GFM_resizeImage_chkAR(this)"></td>\
                     <td rowspan="2"><img src="/repository/admin/images/file-manager-brace.gif"></td>\
                     <td rowspan="2"><label for="GFM_imageResize_MaintainAspectRatio"><input type="checkbox" id="GFM_imageResize_MaintainAspectRatio" tabindex="3" checked onclick="GFM_resizeImage_chkAR()" style="position: relative; top: 3px; _top: 1px;"> Maintain Aspect Ratio</label></td>\
                 </tr>\
                 <tr>\
                     <td>Height:</td>\
                     <td><input type="text" id="GFM_imageResize_height" value="' + file.h + '" maxlength="4" tabindex="2" style="width: 40px;" onkeyup="GFM_resizeImage_chkAR(this)" onfocus="GFM_resizeImage_chkAR(this)" onblur="GFM_resizeImage_chkAR(this)"></td>\
                 </tr>\
             </table>',
            Array(
                Array('GFM_resizeImage_resize_btn', 'Resize', '/repository/admin/images/act_resize.gif', '', function () {
                    GFM_HTTP('action=resize&width=' + document.getElementById('GFM_imageResize_width').value + '&height=' + document.getElementById('GFM_imageResize_height').value + '&path=' + currentPath + '&filename=' + currentFile, function (doc) {
                        GFM_getObjects(currentPath, 0, objects.length, function (cnt) {
                            GFM_list_render(cnt, function () {
                                randomseed++; // This will force a reloading of the image.
                                GFM_selectFile(currentFile);
                            });
                        });
                        GFM_POPUP_close();
                    });
                })
            ),
            function () {
                document.getElementById('GFM_imageResize_width').select();
            }
        );
    }

    function GFM_resizeImage_chkAR(element) {
        if (document.getElementById('GFM_imageResize_MaintainAspectRatio').checked) {
            if (!element || element.id == 'GFM_imageResize_width') {
                document.getElementById('GFM_imageResize_height').value = Math.round(document.getElementById('GFM_imageResize_width').value / document.getElementById('GFM_imageResize_aspectRatio').value);
            } else {
                document.getElementById('GFM_imageResize_width').value = Math.round(document.getElementById('GFM_imageResize_height').value * document.getElementById('GFM_imageResize_aspectRatio').value);
            }
        }
    }

    /* ## */

    function GFM_cropImage() {
        var file = GFM_getObject(currentPath + currentFile);
        if (file) {
            if (screen.width && screen.height) {
                var width  = screen.width - 100;
                var height = screen.height - 100;
            } else {
                var width  = 924;
                var height = 668;
            }
            window.open(baseHref + '?doc=admin/file-manager-image-crop&filename=' + currentPath + currentFile, 'gyroFileManager_image_crop', 'width=' + width + ',height=' + height + ',resizable=1,scrollbars=1,left=' + (screen.width ? (screen.width - width) / 2 : 0) + ',top=' + (screen.height ? (screen.height - height) / 2.5 : 0));
        }
    }

    function cropImage_return(filename) {
        var path = filename.replace(/\\/g, '/').replace(/\/[^\/]*$/, '');
        var file = filename.replace(/\\/g, '/').replace(/.*\//, '');
        
        // if no path was set in the passed `filename`, the result will be the name of the file.
        if (path == file) {
            path = '';
        } else {
            path += '/';
        }
        
        window.location = baseHref + '?doc=admin/file-manager&path=' + path + '&file=' + file + (noReturn ? '&noreturn=1' : '');
    }

    /* ## */
    
    function GFM_frameImage() {
        GFM_POPUP(
            'Frame Image',
            '<table cellspacing="0" cellpadding="3">\
                 <tr>\
                     <td>Width:</td>\
                     <td><input type="text" id="GFM_frameImage_width" value="10" maxlength="4" tabindex="1" style="width: 40px;"></td>\
                     <td rowspan="2" style="width: 10px;"><br></td>\
                     <td>Color:</td>\
                     <td><input type="text" id="GFM_frameImage_color" value="#FFFFFF" maxlength="7" tabindex="3" style="width: 60px;" onblur="if (/^#[0-9A-F]{6}$/i.test(this.value)) { document.getElementById(\'GFM_frameImage_color_preview\').style.display = \'\'; document.getElementById(\'GFM_frameImage_color_preview\').style.backgroundColor = this.value; } else { document.getElementById(\'GFM_frameImage_color_preview\').style.display = \'none\'; }" onkeyup="this.onblur()" onfocus="this.onblur()"></td>\
                     <td>→</td>\
                     <td><img src="/repository/admin/images/void.gif" id="GFM_frameImage_color_preview" style="width: 32px; height: 16px; border: 1px solid #000000; background-color: #FFFFFF;"></td>\
                 </tr>\
                 <tr>\
                     <td>Height:</td>\
                     <td><input type="text" id="GFM_frameImage_height" value="10" maxlength="4" tabindex="2" style="width: 40px;"></td>\
                     <td><br></td>\
                     <td colspan="3">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#FFFFFF\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #FFFFFF;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#000000\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #000000;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#FF0000\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #FF0000;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#FFFF00\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #FFFF00;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#00FF00\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #00FF00;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#00FFFF\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #00FFFF;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#0000FF\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #0000FF;">\
                        <img src="/repository/admin/images/void.gif" onclick="document.getElementById(\'GFM_frameImage_color\').value = \'#FF00FF\'; document.getElementById(\'GFM_frameImage_color\').onblur();" style="width: 9px; height: 9px; border: 1px solid #000000; cursor: pointer; background-color: #FF00FF;">\
                     </td>\
                 </tr>\
             </table>',
            Array(
                Array('GFM_frameImage_frame_btn', 'Frame', '/repository/admin/images/act_frame.gif', '', function () {
                    GFM_HTTP('action=frame&width=' + document.getElementById('GFM_frameImage_width').value + '&height=' + document.getElementById('GFM_frameImage_height').value + '&color=' + document.getElementById('GFM_frameImage_color').value + '&path=' + currentPath + '&filename=' + currentFile, function (doc) {
                        GFM_getObjects(currentPath, 0, objects.length, function (cnt) {
                            GFM_list_render(cnt, function () {
                                randomseed++; // This will force a reloading of the image.
                                GFM_selectFile(currentFile);
                            });
                        });
                        GFM_POPUP_close();
                    });
                })
            ),
            function () {
                document.getElementById('GFM_frameImage_width').select();
            }
        );
    }
    
    /* ## */

    // Set various actions to be controlled by the keyboard.
    document.onkeydown = function (e) {
        if (GFM_POPUP_active && ((window.event && window.event.keyCode == 27) || (e && e.which == 27))) {
            // 'Esc' key: close the popup window.
            
            GFM_POPUP_close();
        } else if (GFM_POPUP_active && GFM_POPUP_defact && ((window.event && (window.event.keyCode == 13 || window.event.keyCode == 32)) || (e && (e.which == 13 || e.which == 32)))) {
            // 'Enter' key: execute the default popup button action.
            
            GFM_POPUP_defact();
        } else if (!GFM_POPUP_active && document.activeElement.getAttribute('id') != 'query' && document.activeElement.getAttribute('id') != 'type') {
            if ((window.event && window.event.keyCode == 38) || (e && e.which == 38)) {
                // 'Up Arrow' key: move to the previous entry on the list.
                
                // If not in the root directory, and the first entry is current highlighted, highlight the '..' entry.
                // If in the root directory, find the highlighted entry and highlight the one before it.
                if (objects.length > 0) {
                    if (currentPath && objects[0].n == highlighted) {
                        GFM_unselectFile();
                        GFM_list_objectHighlight('..');
                    } else {
                        for (var i = 1; i != objects.length; i++) {
                            if (objects[i].n == highlighted) {
                                if (objects[i - 1].d == 1) {
                                    GFM_unselectFile();
                                    GFM_list_objectHighlight(objects[i - 1].n);
                                } else {
                                    GFM_selectFile(objects[i - 1].n);
                                }
                                break;
                            }
                        }
                    }
                }
                
                return false; // Disable the default key action.
            } else if ((window.event && window.event.keyCode == 40) || (e && e.which == 40)) {
                // 'Down Arrow' key: move to the next entry on the list.
                
                // Assuming there are objects under the current directory:
                // If the '..' entry is highlighted, highlight the first entry on the list.
                // Otherwise, find the highlighted entry and highlight the one after it.
                if (objects.length > 0) {
                    if (highlighted == '..') {
                        if (objects[0].d == 1) {
                            GFM_unselectFile();
                            GFM_list_objectHighlight(objects[0].n);
                        } else {
                            GFM_selectFile(objects[0].n);
                        }
                    } else {
                        for (var i = 0; i != objects.length; i++) {
                            if (objects[i].n == highlighted) {
                                if (i + 1 < objects.length) {
                                    if (objects[i + 1].d == 1) {
                                        GFM_unselectFile();
                                        GFM_list_objectHighlight(objects[i + 1].n);
                                    } else {
                                        GFM_selectFile(objects[i + 1].n);
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 36) || (e && e.which == 36)) {
                // 'Home' key: move to the top entry on the list.
                
                // If not root directory, highlight the '..' entry.
                // Otherwise, highlight the first entry on the list.
                if (currentPath) {
                    GFM_unselectFile();
                    GFM_list_objectHighlight('..');
                } else {
                    if (objects.length > 0) {
                        if (objects[0].d == 1) {
                            GFM_unselectFile();
                            GFM_list_objectHighlight(objects[0].n);
                        } else {
                            GFM_selectFile(objects[0].n);
                        }
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 35) || (e && e.which == 35)) {
                // 'End' key: move to the bottom entry on the list.
                
                // Highlight the last entry on the list.
                if (objects.length > 0) {
                    if (objects[objects.length - 1].d == 1) {
                        GFM_unselectFile();
                        GFM_list_objectHighlight(objects[objects.length - 1].n);
                    } else {
                        GFM_selectFile(objects[objects.length - 1].n);
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 33) || (e && e.which == 33)) {
                // 'Page Up' key: move 10 entries up the list.
                
                // Assuming there are objects under the current directory:
                // If the '..' entry is highlighted, do nothing.
                // Otherwise, find the highlighted entry, skip the previous 9 entries and highlight the one before it.
                if (objects.length > 0 && highlighted != '..') {
                    for (var i = 0; i != objects.length; i++) {
                        if (objects[i].n == highlighted) {
                            var cnt = i - 10 + 1;
                            break;
                        }
                    }
                    
                    // If in the top-level directory, go to the first object instead of the '..'.
                    if (cnt < 0 && currentPath.length == 0) {
                        cnt = 0;
                    }
                    
                    if (cnt < 0) {
                        GFM_unselectFile();
                        GFM_list_objectHighlight('..');
                    } else if (objects[cnt].d == 1) {
                        GFM_unselectFile();
                        GFM_list_objectHighlight(objects[cnt].n);
                    } else {
                        GFM_selectFile(objects[cnt].n);
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 34) || (e && e.which == 34)) {
                // 'Page Down' key: move 10 entries down the list.
                
                // Assuming there are objects under the current directory:
                // If the '..' entry is highlighted, skip the next 9 entries and highlight the next entry on the list.
                // Otherwise, find the highlighted entry, skip the next 9 entries and highlight the one after it.
                if (objects.length > 0) {
                    if (highlighted == '..') {
                        var cnt = objects.length >= 10 - 1 ? 10 - 1 : objects.length - 1;
                    } else {
                        for (var i = 0; i != objects.length; i++) {
                            if (objects[i].n == highlighted) {
                                var cnt = objects.length >= i + 10 - 1 ? i + 10 - 1 : objects.length - 1;
                                break;
                            }
                        }
                    }
                    
                    if (objects[cnt].d == 1) {
                        GFM_unselectFile();
                        GFM_list_objectHighlight(objects[cnt].n);
                    } else {
                        GFM_selectFile(objects[cnt].n);
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 8) || (e && e.which == 8)) {
                // 'Backspace' key: move to the previous directory.
                
                // Unless in the top-level directory, move to the previous directory.
                if (currentPath.length != 0) {
                    GFM_list_objectHighlight('..');
                    GFM_list_selectObject();
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 113) || (e && e.which == 113)) {
                // 'F2' key.
                if (highlighted) {
                    var obj = GFM_getObject(highlighted);
                    if (obj) {
                        if (obj.d == 1) {
                            GFM_renameDir(obj.n);
                        } else {
                            GFM_renameFile(obj.n);
                        }
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 46) || (e && e.which == 46)) {
                // 'Del' key.
                if (highlighted) {
                    var obj = GFM_getObject(highlighted);
                    if (obj) {
                        if (obj.d == 1) {
                            GFM_deleteDir(obj.n);
                        } else {
                            GFM_deleteFile(obj.n);
                        }
                    }
                }
                
                return false;
            } else if ((window.event && window.event.keyCode == 13) || (e && e.which == 13)) {
                // 'Enter' key.
                GFM_list_selectObject();
                
                return false;
            }
        }
    }

    // When pressing 'Enter', either move to a directory, or return the selected file.
    function GFM_list_selectObject() {
        if (highlighted) {
            if (highlighted == '..') {
                GFM_selectDir('..');
            } else {
                var obj = GFM_getObject(currentPath + highlighted);
                if (obj) {
                    if (obj.d == 1) {
                        GFM_selectDir(obj.n);
                    } else if (!noReturn) {
                        GFM_returnFile();
                    }
                }
            }
        }
    }

    /* ## */

    // Return the selected file to the parent application.
    function GFM_returnFile() {
        if (currentFile) {
            FileBrowserDialogue.mySubmit();
        }
    }
    </script>
    
    <style type="text/css">
    * {
        font-family: Verdana;
        font-size: 11px;
    }
    
    body {
        padding: 0;
        margin: 0;
        
    }
    
    #veil-ajax {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 3;
        cursor: progress;
    }
    
    #veil-back {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background-color: #E4E6EE;
        filter: alpha(opacity=85);
        filter: "alpha(opacity=85)";
        opacity: 0.85;
        z-index: 1;
    }
    
    #GFM_list table {
        width: 100%;
        border: 5px solid #FFFFFF;
    }
    
    #GFM_list table td {
        padding: 2px 3px 2px 3px;
    }
    
    #GFM_list .attributes {
        white-space: nowrap;
        text-align: right;
        font-size: 10px;
        color: #666666;
    }
    
    #GFM_POPUP_dialog {
        position: absolute;
        z-index: 2;
    }
    #GFM_POPUP_dialog .title {
        margin-bottom: 15px;
        font-weight: bold;
    }
    #GFM_POPUP_dialog .content {
        width: 275px;
    }
    #GFM_POPUP_dialog .buttons {
        margin-top: 10px;
        text-align: center;
    }
    
    form {
        margin: 0 0 10px 0;
    }
    
    button {
        _width: 1px; /* IE only */
        height: 23px;
        border: 1px solid window;
        background-color: transparent;
        padding: 1px 2px 2px 0;
        _padding: 2px 3px 1px 2px; /* IE only */
        overflow: visible;
        
        background-position: 3px;
        background-repeat: no-repeat;
        
        text-align: center;
        font-family: Verdana;
        font-size: 11px;
        
        cursor: pointer;
    }
    button img {
        vertical-align: -3px;
    }
    
    span.separator {
        margin: 0 2px 0 2px;
        color: #999999;
        font-family: Verdana;
        font-size: 15px;
    }
    
    /* Made necessary due to a bug in IE (http://support.microsoft.com/kb/925014). */
    .icon_dir       { background-image: url(/repository/admin/images/ico_dir.gif); }
    .icon_folder-up { background-image: url(/repository/admin/images/act_folder-up.gif); }
    .icon_gif       { background-image: url(/repository/admin/images/ico_gif.gif); }
    .icon_jpg       { background-image: url(/repository/admin/images/ico_jpg.gif); }
    .icon_png       { background-image: url(/repository/admin/images/ico_png.gif); }
    .icon_swf       { background-image: url(/repository/admin/images/ico_swf.gif); }
    .icon_mov       { background-image: url(/repository/admin/images/ico_mov.gif); }
    .icon_mp3       { background-image: url(/repository/admin/images/ico_mp3.gif); }
    .icon_sound     { background-image: url(/repository/admin/images/ico_sound.gif); }
    .icon_txt       { background-image: url(/repository/admin/images/ico_txt.gif); }
    .icon_pdf       { background-image: url(/repository/admin/images/ico_pdf.gif); }
    .icon_doc       { background-image: url(/repository/admin/images/ico_doc.gif); }
    .icon_ppt       { background-image: url(/repository/admin/images/ico_ppt.gif); }
    .icon_xls       { background-image: url(/repository/admin/images/ico_xls.gif); }
    .icon_zip       { background-image: url(/repository/admin/images/ico_zip.gif); }
    .icon_unknown   { background-image: url(/repository/admin/images/ico_unknown.gif); }
    </style>
</head>

<body>

<div id="veil-back" style="display: none;"></div>
<div id="veil-ajax" style="display: none;"></div>

<table style="width: 100%; height: 100%;">
    <tr>
        <td align="center">

<table cellspacing="15" cellpadding="0">
    <tr style="vertical-align: bottom;">
        <td style="padding: 0; text-align: center;"><img src="/repository/admin/images/file-manager-logo.png" title="Gyro File Manager"><span title="Keyboard Navigation: Up, Down, Home, End, Page Up, Page Down, Enter, Backspace (Parent Directory), F2 (Rename), Del (Delete)" style="font-family: Times New Roman; font-size: 14pt; font-weight: bold; font-style: italic; color: #669ACC; cursor: help;">v.4</span></td>
        
        <td>
            <div style="margin-top: 10px; white-space: nowrap; text-align: right;">
                Type&nbsp;
                <select id="type" onchange="GFM_updateTypeFilter(this.value)">
                    <option value="">all files</option>
                    <option value="images" <?=($_GET['type'] == 'images' ? 'selected' : false)?>>images</option>
                    <option value="flash" >flash</option>
                    <option value="media">media</option>
                    <option value="documents">documents</option>
                    <option value="archives">archives</option>
                </select>
                &nbsp;
                
                Search&nbsp;
                <input type="text" id="query" maxlength="25" style="width: 80px;">
                <button style="font-family: Verdana; font-size: 13px; cursor: pointer; border: 0;" onclick="GFM_updateQueryFilter(document.getElementById('query').value); this.blur(); return false;">»</button>
            </div>
            
            <div style="margin-top: 10px;">
                <table cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        <td>Address</td>
                        <td>&nbsp;&nbsp;</td>
                        <td style="width: 100%;"><input type="text" id="GFM_filename" readonly style="width: 100%; padding: 1px 3px 1px 3px;"></td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    
    <tr style="vertical-align: top;">
        <td style="border: 2px solid #AAAAAA;">
            <div style="position: relative;">
                <table cellspacing="0" cellpadding="0" style="width: 300px; height: 300px; text-align: center; vertical-align: middle;">
                    <tr>
                        <td id="GFM_preview" style="font-family: Century Gothic; font-size: 26pt; font-weight: bold; font-style: italic; color: #EEEEEE;"></td>
                    </tr>
                </table>
                <map name="scaled-band"><area id="GFM_previewBand_link" alt="Click for Full Size" shape="POLY" coords="1,62,63,0,63,18,19,62" onclick="var width = 760, height = 500; window.open(this.href, '_blank', 'width=' + width + ',height=' + height + ',resizable=1,scrollbars=1,status=1,titlebar=1,toolbar=1,location=1,menubar=1,directories=1,left=' + (screen.width ? (screen.width - width) / 2 : 0) + ',top=' + (screen.height ? (screen.height - height) / 2.75 : 0)); return false;" onfocus="this.blur()"></map>
                <img id="GFM_previewBand" src="/repository/admin/images/file-manager-scaled.gif" usemap="#scaled-band" style="position: absolute; bottom: 0; right: 0; border: 0; display: none;">
            </div>
        </td>
        
        <td style="border: 2px solid #AAAAAA;">
            <div id="GFM_list" style="width: 500px; height: 300px; overflow: auto;">
                <table cellspacing="0" cellpadding="0">
                    <tbody id="GFM_list_container"></tbody>
                </table>
            </div>
        </td>
    </tr>
    
    <tr style="height: 25px; vertical-align: top;">
        <td align="center">
            <div id="GFM_imageActions" style="float: center; display: none;">
                <span id="GFM_resizeImage_btn"></span>
                <span class="separator">|</span>
                <span id="GFM_cropImage_btn"></span>
                <span class="separator">|</span>
                <span id="GFM_rotateCCWImage_btn"></span>
                <span id="GFM_rotateCWImage_btn"></span>
                <span class="separator">|</span>
                <span id="GFM_frameImage_btn"></span>
            </div>
        </td>
        
        <td>
            <div id="GFM_selectFile_btn" style="float: left; display: none;"></div>
            <div style="float: right;">
                <span id="GFM_uploadFiles_btn"></span>
                <span class="separator">|</span>
                <span id="GFM_NewDirectory_btn"></span>
            </div>
        </td>
    </tr>
</table>

        </td>
    </tr>
</table>

</body>
</html>

<?php

// Functions.

function jsQuote($string) {
    return str_replace("'", "\'", $string);
}

function isValidObjectName($obj) {
    return preg_match('/^[a-z0-9][a-z0-9\s\_\.\-]{0,99}$/i', $obj);
}

function fileExtension($filename) {
    $pos = strrpos($filename, '.');
    return ($pos !== false) ? strtolower(substr($filename, $pos)) : '';
}

function filesize_human($size) {
    if ($size) {
        $filesizeName = array('bytes', 'kb', 'mb', 'gb');
        return round($size / pow(1024, ($cnt = floor(log($size, 1024)))), 2) . ' ' . $filesizeName[$cnt];
    } else {
        return '0 bytes';
    }
}

function count_files_recursive($path, $validFileTypesExtensions, $query) {
    $files = 0;
    if ($dir = opendir($path)) {
        while (($file = readdir($dir)) !== false) {
            if ($file[0] != '.') {
                if ((!$validFileTypesExtensions || @in_array(fileExtension($path . $file), $validFileTypesExtensions)) && (!$query || stripos($file, $query) !== false)) {
                    $files++;
                }
                
                if (is_dir($path . $file)){  
                    $files += count_files_recursive($path . $file . DIRECTORY_SEPARATOR, $validFileTypesExtensions, $query);
                }
            }
        }    
        closedir($dir);
    }
 
    return $files;
}

function rmdir_recursive($path, $followLinks = false) {
    $dir = opendir($path);
    while ($entry = readdir($dir)) {
        if (is_file($path . '/' . $entry) || (!$followLinks && is_link($path . '/' . $entry))) {
            @unlink($path . '/' . $entry);
        } elseif (is_dir($path . '/' . $entry) && $entry != '.' && $entry != '..') {
            rmdir_recursive($path . '/' . $entry, $followLinks);
        }
    }
    closedir($dir) ;
    
    rmdir($path);
}

?>
