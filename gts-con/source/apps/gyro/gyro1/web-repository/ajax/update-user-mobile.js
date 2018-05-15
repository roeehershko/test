var req;
var updateUserMobile_send__callback;
var updateUserMobile_verify__callback;

function updateUserMobile_send(mobile, callback) {
    req = false;
    updateUserMobile_send__callback = callback;
    
    if (window.XMLHttpRequest && !(window.ActiveXObject)) {
        try {
            req = new XMLHttpRequest();
        } catch(e) {
            req = false;
        }
    } else if (window.ActiveXObject) {
           try {
            req = new ActiveXObject("Msxml2.XMLHTTP");
          } catch(e) {
            try {
                  req = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                  req = false;
            }
        }
    }
    
    if (req) {
        req.onreadystatechange = updateUserMobile_send__statechange;
        req.open("GET", "?doc=ajax/update-user-mobile&action=send-code&mobile=" + mobile, true);
        req.send("");
    }
}

function updateUserMobile_send__statechange() {
    if (req.readyState == 4) {
        if (req.status == 200) {
            var XMLDocument = req.responseXML.documentElement;
            
            for (var i = 0; XMLDocument.childNodes[i]; i++) {
                if (XMLDocument.childNodes[i].nodeType == 1) {
                    if (XMLDocument.childNodes[i].getAttribute('success') == '1') {
                        eval(updateUserMobile_send__callback + '(true)');
                    } else {
                        eval(updateUserMobile_send__callback + '(false, "' + XMLDocument.childNodes[i].getAttribute('error') + '")');
                    }
                }
            }
        }
    }
}

function updateUserMobile_verify(mobile, code, callback) {
    req = false;
    updateUserMobile_verify__callback = callback;
    
    if (window.XMLHttpRequest && !(window.ActiveXObject)) {
        try {
            req = new XMLHttpRequest();
        } catch(e) {
            req = false;
        }
    } else if (window.ActiveXObject) {
           try {
            req = new ActiveXObject("Msxml2.XMLHTTP");
          } catch(e) {
            try {
                  req = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                  req = false;
            }
        }
    }
    
    if (req) {
        req.onreadystatechange = updateUserMobile_verify__statechange;
        req.open("GET", "?doc=ajax/update-user-mobile&action=verify-code&mobile=" + mobile + "&code=" + code, true);
        req.send("");
    }
}

function updateUserMobile_verify__statechange() {
    if (req.readyState == 4) {
        if (req.status == 200) {
            var XMLDocument = req.responseXML.documentElement;
            
            for (var i = 0; XMLDocument.childNodes[i]; i++) {
                if (XMLDocument.childNodes[i].nodeType == 1) {
                    if (XMLDocument.childNodes[i].getAttribute('success') == '1') {
                        eval(updateUserMobile_verify__callback + '(true)');
                    } else {
                        eval(updateUserMobile_verify__callback + '(false, "' + XMLDocument.childNodes[i].getAttribute('error') + '")');
                    }
                }
            }
        }
    }
}