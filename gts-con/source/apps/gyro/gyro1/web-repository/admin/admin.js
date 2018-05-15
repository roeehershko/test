function mouseOvrBtn(button) {
    button.setAttribute('defaultBorderColor', button.style.borderColor);
    button.setAttribute('defaultColor', button.style.color);
    
    button.style.borderColor = '#888888';
    button.style.color = '#666666';
}

function mouseOutBtn(button) {
    button.style.borderColor = button.getAttribute('defaultBorderColor');
    button.style.color = button.getAttribute('defaultColor');
}

/* ## */

function inArray(needle, haystack) {
    for (var i = 0; i < haystack.length; i++) {
        if (haystack[i] === needle) {
            return true;
        }
    }
    
    return false;
}

function setCookie(cookieName, cookieValue, cookieExpire, cookiePath) {
    var today = new Date();
    var expire = new Date();
    cookieExpire *= 1000; // Adjust the JavaScript's standards.
    expire.setTime(today.getTime() + cookieExpire);
    document.cookie = cookieName + '=' + escape(cookieValue) + '; expires=' + expire.toGMTString() + '; path=' + cookiePath;
}

function getCookie(name) {
    var dc = document.cookie;
    var prefix = name + '=';
    var begin = dc.indexOf('; ' + prefix);
    if (begin == -1) {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    } else {
        begin += 2;
    }
    var end = document.cookie.indexOf(';', begin);
    if (end == -1) end = dc.length;
    return unescape(dc.substring(begin + prefix.length, end));
}

// Automatically extend the session authentication when in the Admin so it never expires.
function admin_updateAuthExpirationTime() {
    var req = false;
    
    if (window.XMLHttpRequest && !(window.ActiveXObject)) {
        try { req = new XMLHttpRequest() } catch(e) { }
    } else if (window.ActiveXObject) {
        try { req = new ActiveXObject("Msxml2.XMLHTTP") } catch(e) { try { req = new ActiveXObject("Microsoft.XMLHTTP") } catch(e) { } }
    }
    
    if (req) {
        req.open('GET', '?doc=void', true);
        req.send('');
    }
}
window.setInterval(admin_updateAuthExpirationTime, 300000);

function addListener(element, type, expression, bubbling) {
    if (window.addEventListener) {
        element.addEventListener(type, expression, bubbling || false);
        return true;
    } else if (window.attachEvent) {
        element.attachEvent('on' + type, expression);
        return true;
    } else {
        return false;
    }
}

/*
addListener(oElement, 'focus', function () { ... });
*/

/* ## */

function gyroFileManager(field_name, url, type, win) {
    base = window.location.pathname.replace(/^(\/[^\/]*\/).*$/, '$1');
    if (!tinyMCE.activeEditor) {
        var element = document.createElement('textarea');
        element.className = 'htmlarea';
        element.style.display = 'none';
        document.body.appendChild(element);
        tinyMCE.init(tinyMCE_def);
        setTimeout(function () { gyroFileManager(field_name, url, type, win) }, 250);
        return;
    }
    tinyMCE.activeEditor.windowManager.open({
        file: base + '?doc=admin/file-manager&url=' + url + '&type=' + (type == 'image' ? 'images' : ''),
        title: 'Gyro File Manager',
        width: 910,
        height: 525,
        resizable: 'yes',
        inline: false,
        close_previous: 'no',
        popup_css: false
    }, {
        window: win ? win : window, // Either the tinyMCE dialog or the main window if accessed directly.
        input: field_name
    });
    
    return false;
}

/* ## */

function iframeDialog(url, width, height) {
    // Set default dialog size.
    if (!width) width = 810;
    if (!height) height = 600;
    
    // Adjust the dialog size if it exceeds the viewport size.
    if (typeof window.innerWidth != 'undefined') {
        if (window.innerWidth - 100 < width) {
            width = window.innerWidth - 100;
        }
        if (window.innerHeight - 100 < height) {
            height = window.innerHeight - 100;
        }
    }
    
    // If no dialog has been created, create it; otherwise, only adjust the location (url) and size.
    if (!$('Dialog_iframe')) {
        var dialog = document.createElement('div');
        dialog.id = 'Dialog_iframe';
        dialog.style.display = 'none';
        dialog.innerHTML = '<iframe name="Dialog_iframe_iframe" id="Dialog_iframe_iframe" src="' + url + '" frameborder="0" style="width: ' + width + 'px; height: ' + height + 'px;"></iframe>';
        
        document.body.insertBefore(dialog, document.body.childNodes[0]);
        
        new Dialog.Box('Dialog_iframe');
        
        // Inject code into the iframe to close dialog when `Esc` is pressed.
        document.getElementById('Dialog_iframe_iframe').onload = function () {
            document.getElementById('Dialog_iframe_iframe').contentDocument.onkeydown = function (e) {
                if ((window.event && window.event.keyCode == 27) || (e && e.which == 27)) {
                     $('Dialog_iframe').close();
                }
            }
            
            window.focus();
        }
    } else {
        document.getElementById('Dialog_iframe_iframe').setAttribute('src', url);
        document.getElementById('Dialog_iframe_iframe').style.width = width;
        document.getElementById('Dialog_iframe_iframe').style.height = height;
    }
    
    $('Dialog_iframe').open();
    
    // Close dialog when `Esc` is pressed.
    document.onkeydown = function (e) {
        if ((window.event && window.event.keyCode == 27) || (e && e.which == 27)) {
            $('Dialog_iframe').close();
        }
    }
}
