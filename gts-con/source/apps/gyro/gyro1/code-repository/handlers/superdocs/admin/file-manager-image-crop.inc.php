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

 if (!file_exists(constant('FILES_BASE') . $_GET['filename']) || !is_file(constant('FILES_BASE') . $_GET['filename']) || strpos($_GET['filename'], '..') !== false) {
    abort('Invalid filename.');
}

if ($_GET['filename_new']) {
    if (!preg_match('/^[a-z0-9][a-z0-9\s\_\.\-]{0,99}$/i', basename($_GET['filename_new']) || strpos($_GET['filename_new'], '..') !== false)) {
        abort('Invalid new filename: contains invalid characters.');
    }
    if (!is_dir(constant('FILES_BASE') . dirname($_GET['filename_new']))) {
        abort('Invalid new filename: specified directory does not exist.');
    }
    if (file_exists(constant('FILES_BASE') . $_GET['filename_new'])) {
        abort('Invalid new filename: already exists.');
    }
}

##

// Verify that the file is an image.
if (list($width, $height, $type) = @getimagesize(constant('FILES_BASE') . $_GET['filename'])) {
    if ($type != '1' && $type != '2' && $type != '3') {
        $errors[] = 1;
    }
} else {
    $errors[] = 1;
}

if ($_GET['action'] == 'crop' && !$errors) {
    if (!preg_match('/^\d+$/', $_GET['x'])) {
        $errors[] = 1;
    }
    if (!preg_match('/^\d+$/', $_GET['y'])) {
        $errors[] = 1;
    }
    if (!preg_match('/^\d+$/', $_GET['w'])) {
        $errors[] = 1;
    }
    if (!preg_match('/^\d+$/', $_GET['h'])) {
        $errors[] = 1;
    }
    
    if (!$errors) {
        exec('convert -crop ' . $_GET['w'] . 'x' . $_GET['h'] . '+' . $_GET['x'] . '+' . $_GET['y'] . ' -quality 100 ' . escapeshellarg(constant('FILES_BASE') . $_GET['filename']) . ' ' . escapeshellarg(constant('FILES_BASE') . ($_GET['filename_new'] ? $_GET['filename_new'] : $_GET['filename'])));
        
        echo '<script type="text/javascript">' . "\n";
        echo '    window.opener.cropImage_return(\'' . str_replace("'", "\'", ($_GET['filename_new'] ? $_GET['filename_new'] : $_GET['filename'])) . '\');' . "\n";
        echo '    window.close();' . "\n";
        echo '</script>' . "\n";
        exit;
    }
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Image Crop</title>
    <script type="text/javascript">
    window.onload = function () {
        image_crop_init('image');
        window.focus();
    }

    var crop_w_min = 25;
    var crop_h_min = 25;

    /* ## */

    var image;

    var selecting = 0; // Indicates that a selecting operation is in process.
    var selecting_x, selecting_y;

    var moving = 0; // Indicates that a moving operation is in process.
    var moving_x, moving_y;

    var previewing = 0; // Indicates that a preview operation is in process.

    // The frame inside which the work area resides.
    var frame;

    // The complete image area.
    var area;
    var area_w, area_h;

    // The crop area.
    var crop;
    var crop_w, crop_h, crop_x, crop_y;

    // The foreground areas.
    var fg_t, fg_l, fg_r, fg_b;

    // The outline area (used during the selection operation).
    var outline;
    var outline_w, outline_h, outline_x, outline_y;
    var outline_size;

    // Used for showing the cropped preview.
    var preview;

    // Position of the mouse pointer (relative to the _document_).
    var mouse_x, mouse_y;

    // Used primarily for allowing fixed-ratio selection.
    var shift_pressed = 0;

    function image_crop_init(imageId) {
        image = document.getElementById(imageId);
        
        // Create the frame around the work area.
        frame = document.createElement('div');
        frame.style.width = image.width + 2;
        frame.style.height = image.height + 2;
        frame.style.border = '1px solid #000000';
        frame.style.MozBoxSizing = 'border-box';
        
        // Create the work area.
        area = document.createElement('div');
        area.style.position = 'relative';
        area.style.width = image.width;
        area.style.height = image.height;
        area.style.backgroundImage = 'url(' + image.src + '?' + Math.random() + ')';
        area.style.cursor = 'crosshair';
        area.onmousedown = function () {
            if (!selecting && !moving && !previewing) {
                selecting = 1;
                selecting_x = mouse_x - area.offsetLeft;
                selecting_y = mouse_y - area.offsetTop;
            }
        }
        frame.appendChild(area);
        
        // Replace the original image with the work area.
        image.parentNode.replaceChild(frame, image);
        
        // Create the instructions note above the works area frame.
        var instructions = document.createElement('div');
        instructions.style.marginBottom = '15px';
        instructions.style.lineHeight = '18px';
        instructions.style.fontFamily = 'Verdana';
        instructions.style.fontSize = '10px';
        instructions.innerHTML = 'Hold <strong>Ctrl</strong> to preview the selected area.'
                               + '<br>Hold <strong>Shift</strong> while selecting for a fixed-ratio.';
        frame.parentNode.insertBefore(instructions, frame);
        
        var submitButton = document.createElement('div');
        submitButton.style.marginTop = '8px';
        submitButton.innerHTML = '<button type="button"'
                               + '   onmouseover="this.setAttribute(\'defaultBorderColor\', this.style.borderColor); this.setAttribute(\'defaultColor\', this.style.color); this.style.borderColor = \'#888888\'; this.style.color = \'#666666\';"'
                               + '   onmouseout="this.style.borderColor = this.getAttribute(\'defaultBorderColor\'); this.style.color = this.getAttribute(\'defaultColor\');"'
                               + '   onclick="image_crop_return(0)"'
                               + '   onfocus="this.blur()"'
                               + '   style="_width: 1px; height: 23px; border: 1px solid window; background-color: transparent; padding: 1px 2px 2px 0; _padding: 2px 3px 1px 2px; overflow: visible; background-position: 3px; background-repeat: no-repeat; text-align: center; font-family: Verdana; font-size: 11px; cursor: pointer;">'
                               + '   <img src="/repository/admin/images/act_crop.gif" style="vertical-align: -3px;">&nbsp;&nbsp;Crop and Save (overwrite)'
                               + '</button>'
                               + '<span style="margin: 0 2px 0 2px; color: #999999; font-family: Verdana; font-size: 15px;">|</span>'
                               + '<button type="button"'
                               + '   onmouseover="this.setAttribute(\'defaultBorderColor\', this.style.borderColor); this.setAttribute(\'defaultColor\', this.style.color); this.style.borderColor = \'#888888\'; this.style.color = \'#666666\';"'
                               + '   onmouseout="this.style.borderColor = this.getAttribute(\'defaultBorderColor\'); this.style.color = this.getAttribute(\'defaultColor\');"'
                               + '   onclick="image_crop_return(1)"'
                               + '   onfocus="this.blur()"'
                               + '   style="_width: 1px; height: 23px; border: 1px solid window; background-color: transparent; padding: 1px 2px 2px 0; _padding: 2px 3px 1px 2px; overflow: visible; background-position: 3px; background-repeat: no-repeat; text-align: center; font-family: Verdana; font-size: 11px; cursor: pointer;">'
                               + '   <img src="/repository/admin/images/act_crop.gif" style="vertical-align: -3px;">&nbsp;&nbsp;Crop and Save As New'
                               + '</button>';
        frame.parentNode.appendChild(submitButton);
        
        // Work area size.
        area_w = image.width;
        area_h = image.height;
        
        // Create the crop area.
        crop = document.createElement('div');
        crop.style.position = 'absolute';
        crop.style.zIndex = '2';
        crop.style.border = '1px dashed #000000';
        crop.style.MozBoxSizing = 'border-box';
        crop.style.cursor = 'move';
        crop.onmousedown = function (e) {
            if (!selecting && !previewing) {
                moving = 1;
                
                moving_x = mouse_x - crop.offsetLeft;
                moving_y = mouse_y - crop.offsetTop;
            }
        }
        area.appendChild(crop);
        
        // IE insists that an empty DIV has a minimum height that cannot be zero,
        // unless there is a comment inside it.
        var emtpyDivTagContent = '<div><!-- --></div>';
        
        // Create the four "select crop area" squares.
        // On a click event they mark that a select operation is in process, 
        // and define the starting point of the select area (opposing square).
            var sq_tl = document.createElement('div');
            sq_tl.style.position = 'absolute';
            sq_tl.style.zIndex = '1';
            sq_tl.style.width = '6px';
            sq_tl.style.height = '6px';
            sq_tl.style.border = '1px solid #000000';
            sq_tl.style.background = '#FFFFFF';
            sq_tl.style.MozBoxSizing = 'border-box';
            sq_tl.style.top = '-3px';
            sq_tl.style.left = '-3px';
            sq_tl.style.cursor = 'nw-resize';
            sq_tl.innerHTML = emtpyDivTagContent;
            sq_tl.onmousedown = function () {
                if (!selecting && !moving && !previewing) {
                    selecting = 1;
                    selecting_x = crop_x + crop_w;
                    selecting_y = crop_y + crop_h;
                }
            }
            crop.appendChild(sq_tl);
            
            var sq_tr = document.createElement('div');
            sq_tr.style.position = 'absolute';
            sq_tr.style.zIndex = '1';
            sq_tr.style.width = '6px';
            sq_tr.style.height = '6px';
            sq_tr.style.border = '1px solid #000000';
            sq_tr.style.background = '#FFFFFF';
            sq_tr.style.MozBoxSizing = 'border-box';
            sq_tr.style.top = '-3px';
            sq_tr.style.right = '-3px';
            sq_tr.style.cursor = 'ne-resize';
            sq_tr.innerHTML = emtpyDivTagContent;
            sq_tr.onmousedown = function () {
                if (!selecting && !moving && !previewing) {
                    selecting = 1;
                    selecting_x = crop_x;
                    selecting_y = crop_y + crop_h;
                }
            }
            crop.appendChild(sq_tr);
            
            var sq_bl = document.createElement('div');
            sq_bl.style.position = 'absolute';
            sq_bl.style.zIndex = '1';
            sq_bl.style.width = '6px';
            sq_bl.style.height = '6px';
            sq_bl.style.border = '1px solid #000000';
            sq_bl.style.background = '#FFFFFF';
            sq_bl.style.MozBoxSizing = 'border-box';
            sq_bl.style.bottom = '-3px';
            sq_bl.style.left = '-3px';
            sq_bl.style.cursor = 'sw-resize';
            sq_bl.innerHTML = emtpyDivTagContent;
            sq_bl.onmousedown = function () {
                if (!selecting && !moving && !previewing) {
                    selecting = 1;
                    selecting_x = crop_x + crop_w;
                    selecting_y = crop_y;
                }
            }
            crop.appendChild(sq_bl);
            
            var sq_br = document.createElement('div');
            sq_br.style.position = 'absolute';
            sq_br.style.zIndex = '1';
            sq_br.style.width = '6px';
            sq_br.style.height = '6px';
            sq_br.style.border = '1px solid #000000';
            sq_br.style.background = '#FFFFFF';
            sq_br.style.MozBoxSizing = 'border-box';
            sq_br.style.bottom = '-3px';
            sq_br.style.right = '-3px';
            sq_br.style.cursor = 'se-resize';
            sq_br.innerHTML = emtpyDivTagContent;
            sq_br.onmousedown = function () {
                if (!selecting && !moving && !previewing) {
                    selecting = 1;
                    selecting_x = crop_x;
                    selecting_y = crop_y;
                }
            }
            crop.appendChild(sq_br);
        
        // Crop area size and position.
        crop_w = Math.round(area_w * 0.75);
        crop_h = Math.round(area_h * 0.75);
        crop_x = Math.round(area_w * 0.125);
        crop_y = Math.round(area_h * 0.125);
        
        // Create the four foreground blocks, which cover the whole work area,
        // with the exception on the crop area.
            fg_t = document.createElement('div');
            fg_t.style.position = 'absolute';
            fg_t.style.zIndex = '1';
            fg_t.style.background = '#FFFFFF';
            fg_t.style.filter = 'alpha(opacity=70)';
            fg_t.style.MozOpacity = '0.7';
            fg_t.innerHTML = emtpyDivTagContent;
            area.appendChild(fg_t);
            
            fg_l = document.createElement('div');
            fg_l.style.position = 'absolute';
            fg_l.style.zIndex = '1';
            fg_l.style.background = '#FFFFFF';
            fg_l.style.filter = 'alpha(opacity=70)';
            fg_l.style.MozOpacity = '0.7';
            fg_l.innerHTML = emtpyDivTagContent;
            area.appendChild(fg_l);
            
            fg_r = document.createElement('div');
            fg_r.style.position = 'absolute';
            fg_r.style.zIndex = '1';
            fg_r.style.background = '#FFFFFF';
            fg_r.style.filter = 'alpha(opacity=70)';
            fg_r.style.MozOpacity = '0.7';
            fg_r.innerHTML = emtpyDivTagContent;
            area.appendChild(fg_r);
            
            fg_b = document.createElement('div');
            fg_b.style.position = 'absolute';
            fg_b.style.zIndex = '1';
            fg_b.style.background = '#FFFFFF';
            fg_b.style.filter = 'alpha(opacity=70)';
            fg_b.style.MozOpacity = '0.7';
            fg_b.innerHTML = emtpyDivTagContent;
            area.appendChild(fg_b);
        
        // Create the outline square that is used when selecting the crop area.
        outline = document.createElement('div');
        outline.style.position = 'absolute';
        outline.style.zIndex = '2';
        outline.style.border = '1px dashed #FF0000';
        outline.style.MozBoxSizing = 'border-box';
        outline.style.display = 'none';
        outline.style.lineHeight = '0';
        area.appendChild(outline);
        
        // Create the preview area, which is used to preview the cropped image.
        preview = document.createElement('div');
        preview.style.backgroundImage = 'url(' + image.src + '?' + Math.random() + ')';
        preview.style.display = 'none';
        frame.appendChild(preview);
        
        // Create the outline size indicator.
        outline_size = document.createElement('div');
        outline_size.style.marginTop = '2px';
        outline_size.style.width = area_w;
        outline_size.style.textAlign = 'right';
        outline_size.innerHTML = '<input type="text" id="outline_size_w" maxlength="4" value="' + area_w + '" onchange="image_crop_update_area()" style="width: 35px; border: 0; font-family: Courier New; font-size: 11px; color: #888888; text-align: right;">'
                               + '<span style="margin: 0 1px 0 1px; font-family: Courier New; font-size: 11px; color: #888888;">x</span>'
                               + '<input type="text" id="outline_size_h" maxlength="4" value="' + area_h + '" onchange="image_crop_update_area()" style="width: 35px; border: 0; font-family: Courier New; font-size: 11px; color: #888888;">';
        frame.parentNode.insertBefore(outline_size, submitButton);
        
        document.onmousemove = function (e) {
            // Get the coordinates of the mouse pointer.
            if (e) {
                mouse_x = e.pageX;
                mouse_y = e.pageY;
            } else {
                e = window.event;
                mouse_x = e.clientX + document.body.scrollLeft;
                mouse_y = e.clientY + document.body.scrollTop;
            }
            
            if (selecting) {
                image_crop_select_update();
            } else if (moving) {
                crop_x = mouse_x - moving_x;
                crop_y = mouse_y - moving_y;
                
                image_crop_render();
            }
        }
        
        document.onmouseup = function () {
            if (selecting) {
                selecting = 0;
                
                image_crop_select_stop();
            } else if (moving) {
                moving = 0;
            }
        }
        
        document.onkeydown = function (e) {
            if ((window.event && window.event.keyCode == 16) || (e && e.which == 16)) {
                shift_pressed = 1;
            }
            
            // On pressing 'Ctrl', preview the cropped image.
            if (((window.event && window.event.keyCode == 17) || (e && e.which == 17)) && !selecting && !moving && !previewing) {
                previewing = 1;
                
                preview.style.width = crop_w;
                preview.style.height = crop_h;
                preview.style.backgroundPosition = '-' + crop_x + 'px -' + crop_y + 'px';
                preview.style.display = '';
                
                frame.style.width = crop_w + 2;
                frame.style.height = crop_h + 2;
                
                area.style.display = 'none';
            }
        }
        
        document.onkeyup = function (e) {
            if ((window.event && window.event.keyCode == 16) || (e && e.which == 16)) {
                shift_pressed = 0;
            }
            
            if (((window.event && window.event.keyCode == 17) || (e && e.which == 17)) && previewing) {
                previewing = 0;
                
                frame.style.width = area_w + 2;
                frame.style.height = area_h + 2;
                area.style.display = '';
                
                preview.style.display = 'none';
            }
        }
        
        image_crop_render();
    }

    // Render the crop area and the foreground blocks around it.
    function image_crop_render() {
        // Force the crop area to defined dimensions.
        if (crop_w < crop_w_min) {
            crop_w = crop_w_min;
        }
        if (crop_w > area_w) {
            crop_w = area_w;
        }
        if (crop_h < crop_h_min) {
            crop_h = crop_h_min;
        }
        if (crop_h > area_h) {
            crop_h = area_h;
        }
        
        // Force the crop area inside the boundaries.
        if (crop_x < 0) {
            crop_x = 0;
        }
        if (crop_w + crop_x > area_w) {
            crop_x = area_w - crop_w;
        }
        if (crop_y < 0) {
            crop_y = 0;
        }
        if (crop_h + crop_y > area_h) {
            crop_y = area_h - crop_h;
        }
        
        // Position the crop area.
        crop.style.width  = crop_w;
        crop.style.height = crop_h;
        crop.style.left   = crop_x;
        crop.style.top    = crop_y;
        
        // Position the foreground areas.
        fg_t.style.width  = area_w;
        fg_t.style.height = crop_y;
        fg_t.style.left   = 0;
        fg_t.style.top    = 0;
                
        fg_l.style.width  = crop_x;
        fg_l.style.height = crop_h;
        fg_l.style.left   = 0;
        fg_l.style.top    = crop_y;
        
        fg_r.style.width  = (area_w - crop_x - crop_w);
        fg_r.style.height = crop_h;
        fg_r.style.left   = (crop_x + crop_w);
        fg_r.style.top    = crop_y;
        
        fg_b.style.width  = area_w;
        fg_b.style.height = (area_h - crop_y - crop_h);
        fg_b.style.left   = 0;
        fg_b.style.top    = (crop_y + crop_h);
        
        // Update the outline area size indicator.
        document.getElementById('outline_size_w').value = crop_w;
        document.getElementById('outline_size_h').value = crop_h;
    }

    // Calculate and draw the outline square when selecting the crop area.
    function image_crop_select_update() {
        mouse_x -= area.offsetLeft;
        mouse_y -= area.offsetTop;
        
        // Place the "outline" DIV releative to the starting coordinates and the current mouse position.
        // There are four possible modes:
        //     1: mouse is above and to the left.
        //     2: mouse is above and to the right.
        //     3: mouse is below and to the left.
        //     4: mouse is below and to the right.
        if (mouse_x < selecting_x && mouse_y < selecting_y) {
            var mode = 1;
            
            outline_w = selecting_x - mouse_x;
            outline_h = selecting_y - mouse_y;
            outline_x = mouse_x;
            outline_y = mouse_y;
        } else if (mouse_x > selecting_x && mouse_y < selecting_y) {
            var mode = 2;
            
            outline_w = mouse_x - selecting_x;
            outline_h = selecting_y - mouse_y;
            outline_x = selecting_x;
            outline_y = mouse_y;
        } else if (mouse_x < selecting_x && mouse_y > selecting_y) {
            var mode = 3;
            
            outline_w = selecting_x - mouse_x;
            outline_h = mouse_y - selecting_y;
            outline_x = mouse_x;
            outline_y = selecting_y;
        } else if (mouse_x > selecting_x && mouse_y > selecting_y) {
            var mode = 4;
            
            outline_w = mouse_x - selecting_x;
            outline_h = mouse_y - selecting_y;
            outline_x = selecting_x;
            outline_y = selecting_y;
        } else {
            return;
        }
        
        // Force the outline area to defined boundaries.
        if (outline_y < 0) {
            outline_y = 0;
            outline_h = outline_h + mouse_y;
        }
        if (outline_x < 0) {
            outline_x = 0;
            outline_w = outline_w + mouse_x;
        }
        if (outline_y + outline_h > area_h) {
            outline_h = area_h - outline_y;
        }
        if (outline_x + outline_w > area_w) {
            outline_w = area_w - outline_x;
        }
        
        // If the 'Shift' key is pressed, force a fixed-ratio seletion.
        if (shift_pressed) {
            if (outline_w > outline_h) {
                if (mode == 1 || mode == 3) {
                    outline_x += outline_w - outline_h;
                }
                outline_w = outline_h;
            } else {
                if (mode == 1 || mode == 2) {
                    outline_y += outline_h - outline_w;
                }
                outline_h = outline_w;
            }
        }
        
        outline.style.display = '';
        outline.style.width   = outline_w;
        outline.style.height  = outline_h;
        outline.style.left    = outline_x;
        outline.style.top     = outline_y;
        
        // Update the outline area size indicator.
        document.getElementById('outline_size_w').value = outline_w;
        document.getElementById('outline_size_h').value = outline_h;
    }

    // Change the crop area to the outline square's size and position.
    function image_crop_select_stop() {
        if (outline_x >= 0 && outline_y >= 0) {
            crop_w = outline_w;
            crop_h = outline_h;
            crop_x = outline_x;
            crop_y = outline_y;
        }
        
        outline.style.display = 'none';
        
        image_crop_render();
    }

    function image_crop_update_area() {
        crop_w = parseInt(document.getElementById('outline_size_w').value);
        crop_h = parseInt(document.getElementById('outline_size_h').value);
        crop_x = 0;
        crop_y = 0;
        
        image_crop_render();
    }

    function image_crop_return(save_as_new) {
        var filename = image.getAttribute('src', 2).substring(<?=strlen(href(constant('FILES_BASE_VIRTUAL')))?>);
        var filename_new = '';
        
        if (save_as_new) {
            var filename_new = prompt('Rename File', filename.replace(/(\.[^\.]+)$/, '_new$1'));
            if (filename_new == null) {
                return;
            }
        }
        
        window.location = '<?=href('/?doc=admin/file-manager-image-crop')?>&action=crop&filename=' + filename + '&x=' + crop_x + '&y=' + crop_y + '&w=' + crop_w + '&h=' + crop_h + '&filename_new=' + filename_new;
    }
    </script>
</head>

<body>

<table style="width: 100%; height: 100%;">
    <tr>
        <td align="center">
            <img id="image" src="<?=href(constant('FILES_BASE_VIRTUAL') . $_GET['filename'])?>" width="<?=$width?>" height="<?=$height?>">
        </td>
    </tr>
</table>

</body>
</html>
