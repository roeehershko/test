document.write('<script src="/repository/tinymce/jscripts/tiny_mce/tiny_mce.js" type="text/javascript"></script>');

tinyMCE_def = {
    mode: 'specific_textareas',
    editor_selector: 'htmlarea',
    skin: 'o2k7',
    skin_variant: 'silver',
    
    theme: 'advanced',
    theme_advanced_layout_manager: 'SimpleLayout',
    theme_advanced_toolbar_location: 'top',
    theme_advanced_toolbar_align: 'left',
    theme_advanced_statusbar_location: 'bottom',
    theme_advanced_resizing: true,
    theme_advanced_path: true,
    theme_advanced_resize_horizontal: false,
    theme_advanced_buttons1: 'fullscreen,|,code,|,pasteword,|,undo,redo,|,justifyleft,justifycenter,justifyright,justifyfull,|,numlist,bullist,indent,outdent,ltr,rtl,|,newdocument,cleanup,removeformat',
    theme_advanced_buttons2: 'link,anchor,charmap,|,image,media,|,bold,italic,underline,strikethrough,|,sub,sup,|,styleprops,|,nonbreaking,attribs,styleselect',
    theme_advanced_buttons3: 'tablecontrols,visualaid,|,search,replace,formatselect',
    
    width:'580',
    
    preformatted: true,
    button_tile_map: true,
    gecko_spellcheck: true,
    content_css: 'include/css/htmlarea.css',
    plugins: 'fullscreen,directionality,table,style,advimage,media,inlinepopups,safari,paste,searchreplace,nonbreaking,lists,xhtmlxtras,advlink',
    
    convert_urls: false, // We do not want TinyMCE to convert our links to anything (by default it removes the domain prefix if it is same as the current domain)

    file_browser_callback: 'gyroFileManager'
};

addListener(window, 'load', function () {
    tinyMCE.init(tinyMCE_def);
});

/* ## */

function iteration_getIterationObject(element) {
    do {
        if (element.nodeType == 1 && element.className == 'iteration') {
            return element;
        }
    } while (element = element.parentNode);
    
    return null;
}

function iteration_getPrevious(oIteration) {
    var oIteration_prev = oIteration.previousSibling;
    do {
        if (oIteration_prev && oIteration_prev.nodeType == 1 && oIteration_prev.className == 'iteration') {
            return oIteration_prev;
        }
    } while (oIteration_prev = oIteration_prev.previousSibling);
    return null;
}

function iteration_getNext(oIteration) {
    var oIteration_next = oIteration.nextSibling;
    do {
        if (oIteration_next && oIteration_next.nodeType == 1 && oIteration_next.className == 'iteration') {
            return oIteration_next;
        }
    } while (oIteration_next = oIteration_next.nextSibling);
    return null;
}

function iteration_clearValues(oIteration, keepImagePath, inColumnB) {
    for (var i = 0; i != oIteration.childNodes.length; i++) {
        if (/error/.test(oIteration.childNodes[i].className)) {
            oIteration.childNodes[i].className = oIteration.childNodes[i].className.replace(/\s?error\s?/, '');
        }
        
        if (inColumnB) {
            if (oIteration.childNodes[i].tagName == 'INPUT') {
                if (keepImagePath && oIteration.childNodes[i].type == 'text' && /browse/.test(oIteration.childNodes[i].className)) {
                    // Do nothing.
                } else if (oIteration.childNodes[i].type == 'text' || oIteration.childNodes[i].type == 'password' || oIteration.childNodes[i].type == 'file' || oIteration.childNodes[i].type == 'hidden') {
                    oIteration.childNodes[i].value = '';
                } else if (oIteration.childNodes[i].type == 'radio' || oIteration.childNodes[i].type == 'checkbox') {
                    oIteration.childNodes[i].checked = false;
                }
            } else if (oIteration.childNodes[i].tagName == 'TEXTAREA') {
                oIteration.childNodes[i].value = '';
            } else if (oIteration.childNodes[i].tagName == 'SELECT') {
                oIteration.childNodes[i].selectedIndex = false;
            }
        }
        
        if (oIteration.childNodes[i].nodeType == 1 && /col b/.test(oIteration.childNodes[i].className)) {
            inColumnB = 1;
        }
        
        if (oIteration.childNodes[i].childNodes.length > 0) {
            iteration_clearValues(oIteration.childNodes[i], keepImagePath, inColumnB);
        }
    }
    
    return oIteration;
}

function iteration_findIndex(oIteration) {
    for (var i = 0; i != oIteration.childNodes.length; i++) {
        if (oIteration.childNodes[i].nodeType == 1 && oIteration.childNodes[i].className == 'index') {
            return oIteration.childNodes[i];
        }  else if (oIteration.childNodes[i].childNodes.length > 0) {
            return iteration_findIndex(oIteration.childNodes[i]);
        }
    }
}

function iteration_updateIndices(iterations) {
    var oIndex;
    
    for (var i = 0, cnt = 1; i != iterations.childNodes.length; i++) {
        if (iterations.childNodes[i].nodeType == 1 && iterations.childNodes[i].className == 'iteration') {
            if (oIndex = iteration_findIndex(iterations.childNodes[i])) {
                oIndex.innerHTML = '#' + (cnt < 10 ? '0' + cnt : cnt)
                
                var arrayName = iterations.childNodes[i].getAttribute('arrayname');
                if (arrayName) {
                    var arrayName_new = arrayName.replace(/\[\d+\]$/, '[' + (cnt - 1) + ']');
                    
                    iteration_updateArrayNames(iterations.childNodes[i], arrayName, arrayName_new, 0);
                    
                    iterations.childNodes[i].setAttribute('arrayname', arrayName_new);
                }
                
                cnt++;
            }
        }
    }
}

function iteration_updateArrayNames(oIteration, arrayName, arrayName_new) {
    for (var i = 0; i != oIteration.childNodes.length; i++) {
        if (oIteration.childNodes[i].nodeType == 1) {
            if (oIteration.childNodes[i].getAttribute('arrayname')) {
                oIteration.childNodes[i].setAttribute('arrayname', arrayName_new + oIteration.childNodes[i].getAttribute('arrayname').substr(arrayName.length));
            }
            
            if (oIteration.childNodes[i].name && oIteration.childNodes[i].name.substr(0, arrayName.length) == arrayName) {
                oIteration.childNodes[i].name = arrayName_new + oIteration.childNodes[i].name.substr(arrayName.length);
            }
        }
        
        if (oIteration.childNodes[i].childNodes.length > 0) {
            iteration_updateArrayNames(oIteration.childNodes[i], arrayName, arrayName_new);
        }
    }
}

/* ## */

function iteration_prepareHtmlAreas(iteration,preserve_content) {
     // Textareas in the iteration
    var iteration_textareas = iteration.getElementsByTagName('textarea');
    for (var i = 0; i != iteration_textareas.length; i++) {
        var iteration_textarea = iteration_textareas[i];

         // Iteration textarea is marked as an HTMLarea
        if (iteration_textarea.className.search('htmlarea') != -1) {
            var iteration_textarea_tinymce =  iteration_textarea.nextSibling;
             // Iteration textarea has a matching tinyMCE instance - prepare it for a fresh instance
            if (typeof iteration_textarea_tinymce != 'undefined' && iteration_textarea_tinymce.className.search('mceEditor') != -1) {
                 // Mark the iteration
                iteration.setAttribute('has_htmlarea','1');
                 // Mark the textarea
                iteration_textarea.className = 'htmlarea htmlarea_waiting_for_init';

                 // Preserve the tinyMCE content - the iteration is being moved not duplicated
                if (preserve_content) {
                    iteration_textarea_tinyMCE_editor = tinyMCE.getInstanceById(iteration_textarea.id);
                    iteration_textarea.value          = iteration_textarea_tinyMCE_editor.getContent();
                }

                 // Remove the current tinyMCE instance
                iteration_textarea_tinymce.parentNode.removeChild(iteration_textarea_tinymce);
                 // Prepare the textarea for a fresh tinyMCE instance
                iteration_textarea.id = '';
                iteration_textarea.removeAttribute('style');
                iteration_textarea.removeAttribute('aria-hidden');


            } // Of preparing the textarea of a fresh tinyMCE instance

        } // Of iteration textarea marked as an HTMLarea

    } // Of iteration textareas

    return iteration;
}

function iteration_initHtmlAreas() {
    tinyMCE_init_in_progress = true;
    tinyMCE_settings                 = tinyMCE_def;
    tinyMCE_settings.editor_selector = 'htmlarea_waiting_for_init';
    tinyMCE_settings.setup           = function(editor) { editor.onInit.add(function(editor) { var iteration_textarea = document.getElementById(editor.id); iteration_textarea.className = 'htmlarea';  }); };
    tinyMCE.init(tinyMCE_settings);
    tinyMCE_init_timer       = setTimeout('tinyMCE_init_in_progress=false;',1000);
}

/* ## */

var tinyMCE_init_in_progress = false;
var tinyMCE_init_timer;

function iteration_addAfter(element) {
    if (tinyMCE_init_in_progress) { return false; }
    
    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        var oIteration_new = oIteration.cloneNode(true);
        oIteration_new = iteration_clearValues(oIteration_new, 1, 0);
        oIteration_new = iteration_prepareHtmlAreas(oIteration_new);

        oIteration.parentNode.insertBefore(oIteration_new, oIteration.nextSibling);        
        iteration_updateIndices(oIteration.parentNode);
         
        if (oIteration_new.getAttribute('has_htmlarea')) {
            iteration_initHtmlAreas();
        }
    }
}

function iteration_addBefore(element) {
    if (tinyMCE_init_in_progress) { return false; }
    
    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        var oIteration_new = oIteration.cloneNode(true);
        oIteration_new = iteration_clearValues(oIteration_new, 1, 0);
        oIteration_new = iteration_prepareHtmlAreas(oIteration_new);
        
        oIteration.parentNode.insertBefore(oIteration_new, oIteration);    
        iteration_updateIndices(oIteration.parentNode);
        
        if (oIteration_new.getAttribute('has_htmlarea')) {
            iteration_initHtmlAreas();
        }
    }
}

function iteration_moveUp(element) {
    if (tinyMCE_init_in_progress) { return false; }

    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        var oIteration_prev = iteration_getPrevious(oIteration);
        if (oIteration_prev) {
            oIteration = iteration_prepareHtmlAreas(oIteration,1);
            
            oIteration.parentNode.insertBefore(oIteration, oIteration_prev);
            iteration_updateIndices(oIteration.parentNode);
            
            if (oIteration.getAttribute('has_htmlarea')) {
                iteration_initHtmlAreas();
            }
        }
    }
}

function iteration_moveDown(element) {
    if (tinyMCE_init_in_progress) { return false; }

    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        var oIteration_next = iteration_getNext(oIteration);
        if (oIteration_next) {
            oIteration_next = iteration_prepareHtmlAreas(oIteration_next,1);
        
            oIteration.parentNode.insertBefore(oIteration_next, oIteration);        
            iteration_updateIndices(oIteration.parentNode);
            
            if (oIteration_next.getAttribute('has_htmlarea')) {
                iteration_initHtmlAreas();
            }
        }
    }
}

function iteration_moveByIdx(element) {
    var idx = prompt('Move after Index #' + "\n" + '(use "0" to move to the top of the list)', '');
    
    if (idx != null && idx != '') {
        if (!/^\d+$/.test(idx)) {
            alert('Invalid Index');
        }
    } else {
        return;
    }
    
    idx = parseInt(idx);
    
    var oIteration = iteration_getIterationObject(element);
    var iterations = oIteration.parentNode;
    var oIteration_ancor;
    
    for (var i = 0, cnt = 1; i != iterations.childNodes.length; i++) {
        if (iterations.childNodes[i].nodeType == 1 && iterations.childNodes[i].className == 'iteration') {
            oIteration_anchor = iterations.childNodes[i];
            if (idx + 1 == cnt) {
                iterations.insertBefore(oIteration, oIteration_anchor);
                iteration_updateIndices(iterations);
                return;
            }
            cnt++;
        }
    }
    
    iterations.insertBefore(oIteration, oIteration_anchor.nextSibling);
    iteration_updateIndices(iterations);
}

function iteration_clear(element) {
    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        oIteration = iteration_clearValues(oIteration, 0, 0);
    }
}

function iteration_delete(element) {
    var oIteration = iteration_getIterationObject(element);
    
    if (oIteration) {
        var temp = oIteration.parentNode;
        if (iteration_getPrevious(oIteration) || iteration_getNext(oIteration)) {
            oIteration.parentNode.removeChild(oIteration);
        }
        iteration_updateIndices(temp);
    }
}

/* ## */

var collapsed_sections_ids = new Array();

function toggleSectionDisplay(oSection) {
    oSection.collapsed = 1 - Boolean(oSection.collapsed);
    
    for (var i = 0, child; child = oSection.childNodes[i]; i++) {
        if (child.nodeType == 1) {
            if (child.className == 'section-header') {
                for (var j = 0; j != child.childNodes.length; j++) {
                    if (child.childNodes[j].nodeType == 1) {
                        if (child.childNodes[j].className == 'icon') {
                            child.childNodes[j].innerHTML = oSection.collapsed ? '[+]' : '[-]';
                        }
                    }
                }
            } else {
                child.style.display = oSection.collapsed ? 'none' : '';
            }
        }
    }
    
    if (oSection.collapsed != 0) {
        collapsed_sections_ids[collapsed_sections_ids.length] = oSection.id;
    } else {
        var collapsed_sections_ids_tmp = new Array();
        for (var i = 0; i != collapsed_sections_ids.length; i++) {
            if (collapsed_sections_ids[i] != oSection.id) {
                collapsed_sections_ids_tmp[collapsed_sections_ids_tmp.length] = collapsed_sections_ids[i];
            }
        }
        collapsed_sections_ids = collapsed_sections_ids_tmp;
    }
    
    setCookie('collapsed_sections_ids', collapsed_sections_ids, 8640000, '/');
}

function toggleSectionDisplay_all() {
    var elements = document.getElementsByTagName('div');
    var collapsed = 1;
    for (var i = 0; i != elements.length; i++) {
        if (/^section__/.test(elements[i].getAttribute('id'))) {
            if (typeof(elements[i].collapsed) == 'undefined' || elements[i].collapsed == '0') {
                collapsed = 0;
                break;
            }
        }
    }
    for (var i = 0; i != elements.length; i++) {
        if (/^section__/.test(elements[i].getAttribute('id'))) {
            elements[i].collapsed = collapsed;
            toggleSectionDisplay(elements[i]);
        }
    }
    
    document.getElementById('collapse-all-icon').innerHTML = '[' + (collapsed ? '-' : '+') + ']';
}

/* ## */

function updateDocParametersByDocSubtype(section_tag, doc_subtype) {
    var re = new RegExp(' ' + (doc_subtype ? doc_subtype : '') + ' ');
    var start = document.getElementById('section__' + section_tag + '_param_section_0') ? 0 : 1;
    for (var i = start, section; section = document.getElementById('section__' + section_tag + '_param_section_' + i); i++) {
        var params = section.getElementsByTagName('tr');
        var hidden = 0;
        for (var j = 0; j != params.length; j++) {
            if (params[j].getAttribute('subtypes') && (!doc_subtype || !re.test(params[j].getAttribute('subtypes')))) {
                params[j].style.display = 'none';
                hidden++;
            } else {
                params[j].style.display = '';
            }
        }
        if (hidden == params.length) {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    }
}

/* ## */

function highlightErrors(errors) {
    for (var i = 0; i != errors.length; i++) {
        var elements = document.getElementsByName(errors[i]);
        
        for (var j = 0, element; element = elements[j]; j++) {
            while (element = element.parentNode) {
                if (element.tagName == 'TR') {
                    element.className = element.className + ' error';
                }
            }
        }
    }
}

/* ## */

window.onload = function () {
    var collapsed_sections_ids_cookie_tmp = getCookie('collapsed_sections_ids');
    if (collapsed_sections_ids_cookie_tmp) {
        var collapsed_sections_ids_cookie = new Array();
        collapsed_sections_ids_cookie = collapsed_sections_ids_cookie_tmp.split(',');
        for (var i = 0; i != collapsed_sections_ids_cookie.length; i++) {
            if (document.getElementById(collapsed_sections_ids_cookie[i])) {
                toggleSectionDisplay(document.getElementById(collapsed_sections_ids_cookie[i]));
            }
        }
    }
    
    // Force empty cells to appear with a border. Necessary because IE doesn't support "empty-cells: show;".
    try {
        if (document.getElementById('adminForm')) {
            var items = document.getElementById('adminForm').getElementsByTagName('td');
            for (var i = 0; i != items.length; i++) {
                // Fill up only cells with the class name 'col' (do not interfere stuff like HTMLArea).
                if (/col/.test(items[i].className) && /^[\n\r\s]*$/.test(items[i].innerHTML)) {
                    items[i].innerHTML = '&nbsp;';
                }
            }
        }
    } catch (e) { }
}

var confirmLeave = true;
window.onbeforeunload = function () {
    if (confirmLeave) {
        return '';
    }
}