function adminList_selectItems(action) {
    var checkboxes = document.getElementsByName('items[]');
    
    if (checkboxes.length == 0) {
        return;
    }
    
    // The default action is to select-all unless all items are already selected, in which case select-none.
    if (action == 'default') {
        for (var i = 0; i != checkboxes.length; i++) {
            if (checkboxes[i].checked == false) {
                action = 'all';
                break;
            }
        }
        if (!action) {
            action = 'none';
        }
    }
    
    for (var i = 0; i != checkboxes.length; i++) {
        checkboxes[i].checked = (action == 'inverse') ? !checkboxes[i].checked : (action == 'all');
    }
    
    // Update the 'items_inver' state and, if required, clear 'items_array'.
    if (action == 'inverse') {
        items_inver = 1 - items_inver;
    } else {
        items_inver = (action == 'all') ? 1 : 0;
        items_array.splice(0, items_array.length);
    }
    
    adminList_renderBarSelections();
}

function adminList_selectItem(oItem) {
    if ((!items_inver && oItem.checked) || (items_inver && !oItem.checked)) {
        items_array[items_array.length] = oItem.value;
    } else if ((!items_inver && !oItem.checked) || (items_inver && oItem.checked)) {
        for (var i = 0; i != items_array.length; i++) {
            if (items_array[i] == oItem.value) {
                items_array.splice(i, 1);
                break;
            }
        }
    }
    
    adminList_renderBarSelections();
}

/* ## */

// Used to keep track of the location hash. Once it no longer matches the current hash, the page is updated to the current hash, thereby fixing the back button for AJAX actions.
var uri_hash = window.location.hash;

function locationHashEval(base) {
    // Fix the back button for AJAX actions.
    window.setInterval(function () {
        if (uri_hash != window.location.hash) {
            uri_hash = window.location.hash;
            uri = base + window.location.hash.replace(/^#/, '&');
            
            adminList_updateList(adminList_updateList__initial);
        }
    }, 1000);
}

/* ## */

var items_total;      // The total number of items.
var items_limit;      // The number of items shown per page.
var items_cpage;      // The current page number (starts from 1).
var items_order;      // The variable according to each the items are sorted.
var items_sorto;      // The sorting order ('asc' or 'desc').
var items_query;      // The items search query.

var items_thead = ''; // A string comprising the items' captions row.
var items_inver = 0;  // Indicates whether 'items_array' contains selected ('0') or unselected ('1') items.
var items_array = new Array();

/* ## */

var adminList_updateList__req;
var adminList_updateList__callback;

function adminList_updateList(callback) {
    adminList_updateList__callback = callback;
    adminList_updateList__req = false;
    
    document.getElementById('ajax-loader').style.display = '';
    
    if (window.XMLHttpRequest && !(window.ActiveXObject)) {
        try { adminList_updateList__req = new XMLHttpRequest() } catch(e) { }
    } else if (window.ActiveXObject) {
        try { adminList_updateList__req = new ActiveXObject("Msxml2.XMLHTTP") } catch(e) { try { adminList_updateList__req = new ActiveXObject("Microsoft.XMLHTTP") } catch(e) { } }
    }
    
    if (adminList_updateList__req) {
        adminList_updateList__req.onreadystatechange = function () {
            if (adminList_updateList__req.readyState == 4) {
                if (adminList_updateList__req.status == 200) {
                    adminList_updateList__callback.call(this, adminList_updateList__req.responseXML.documentElement);
                    
                    document.getElementById('ajax-loader').style.display = 'none';
                }
            }
        };
        adminList_updateList__req.open('GET', uri + '&ajax=1', true);
        adminList_updateList__req.send('');
    }
}

function adminList_updateList__initial(XMLDocument) {
    items_total = parseInt(XMLDocument.getElementsByTagName('legend')[0].getAttribute('total'));
    items_limit = parseInt(XMLDocument.getElementsByTagName('legend')[0].getAttribute('limit'));
    items_cpage = parseInt(XMLDocument.getElementsByTagName('legend')[0].getAttribute('page'));
    items_order = XMLDocument.getElementsByTagName('legend')[0].getAttribute('order');
    items_sorto = XMLDocument.getElementsByTagName('legend')[0].getAttribute('sort');
    items_query = XMLDocument.getElementsByTagName('legend')[0].getAttribute('query');
    
    adminList_renderBarPages(items_cpage, items_total, items_limit, 15);
    adminList_renderBarLimits(items_limit);
    adminList_renderBarCaptions();
    adminList_renderBarSelections();
    adminList_renderBarSearchQuery();
    
    adminList_updateList__central(XMLDocument);
    adminList_updateList__closing(XMLDocument);
}

function adminList_updateList__closing(XMLDocument) {
    // Update the items counter to reflect the number of returned items (may have been updated since the page loaded or as a result of using a search query).
    items_total = parseInt(XMLDocument.getElementsByTagName('legend')[0].getAttribute('total'));
    adminList_renderBarSelections();
    
    // Force empty cells to appear with a border. Necessary because IE doesn't support "empty-cells: show;".
    var items = document.getElementById('adminList').getElementsByTagName('td');
    for (var i = 0; i != items.length; i++) {
        if (/^[\n\r\s]*$/.test(items[i].innerHTML)) {
            items[i].innerHTML = '<div align="center" style="color: #AAAAAA;">-</div>';
        }
    }
    
    try {
        scanDOM(document.body.firstChild); // Run BoxOver again.
    } catch (e) { }
}

/* ## */

// Only used to update page-specific filters (e.g. recipient-type), as it updates the hash instantly, before the page is regenerated.
function adminList_updateURI(vars, vals) {
    var re = new RegExp('&?(' + vars.join('|') + ')=[^&]+', 'g');
    var vs = '';
    
    for (var i = 0; i != vars.length; i++) {
        if (vals[i]) {
            vs = vs + '&' + vars[i] + '=' + vals[i];
        }
    }
    
    uri = uri.replace(re, '') + vs;
    window.location.hash = uri_hash = (window.location.hash.replace(re, '') + vs).replace(/^#?&/, '#');
}

/* ## */

function adminList_updatePage(page) {
    items_cpage = page;
    
    uri = uri.replace(/&?page=[^&]+/, '') + (items_cpage ? '&page=' + items_cpage : '');
    
    adminList_updateList(function (XMLDocument) {
        adminList_updateList__central(XMLDocument);
        adminList_updateList__closing(XMLDocument);
        
        adminList_renderBarPages(items_cpage, items_total, items_limit, 15);
        
        window.location.hash = uri_hash = (window.location.hash.replace(/&?page=[^&]+/, '') + (items_cpage ? '&page=' + items_cpage : '')).replace(/^#?&/, '#');
    });
}

function adminList_renderBarPages(page, itemsNum, itemsPerPage, neighborsNum) {
    if (!itemsPerPage || itemsNum <= itemsPerPage) {
        document.getElementById('bar-pages').innerHTML = '';
        document.getElementById('bar-pages-row').style.display = 'none';
        return;
    } else {
        document.getElementById('bar-pages-row').style.display = '';
    }
    
    var pagesNum = Math.ceil(itemsNum / itemsPerPage);
    
    if (page > 1) {
        str_prevPage = '<a href="" onmouseover="this.href = uri.replace(/&page=[^&]+/, \'\') + \'&page=' + (page - 1) + '\'" onclick="adminList_updatePage(' + (page - 1) + '); return false;" class="prev">« prev.</a>';
    } else {
        str_prevPage = '« prev.'
    }
    if (page != pagesNum) {
        str_nextPage = '<a href="" onmouseover="this.href = uri.replace(/&page=[^&]+/, \'\') + \'&page=' + (page + 1) + '\'" onclick="adminList_updatePage(' + (page + 1) + '); return false;" class="next">next »</a>';
    } else {
        str_nextPage = 'next »';
    }
    
    var startP = page - Math.floor(neighborsNum / 2);
    if (startP > pagesNum - neighborsNum + 1) {
        startP = pagesNum - neighborsNum + 1;
    }
    
    var pages = new Array();
    
    if (startP > 1) {
        pages[pages.length] = '<a href="' + uri.replace(/&page=[^&]+/, '') + '&page=1" onclick="adminList_updatePage(1); return false;" class="number">1</a>';
        pages[pages.length] = '...';
    }
    for (var i = 0, cnt = startP; i != neighborsNum && cnt <= pagesNum; i++, cnt++) {
        if (cnt <= 0) {
            i--;
        } else if (cnt == page) {
            pages[pages.length] = '<span class="number">' + cnt + '</span>';
        } else {
            pages[pages.length] = '<a href="" onmouseover="this.href = uri.replace(/&page=[^&]+/, \'\') + \'&page=' + (cnt) + '\'" onclick="adminList_updatePage(' + cnt + '); return false;" class="number">' + cnt + '</a>';
        }
        last = cnt;
    }
    if (last < pagesNum) {
        pages[pages.length] = '...';
        pages[pages.length] = '<a href="' + uri.replace(/&page=[^&]+/, '') + '&page=' + pagesNum + '" onclick="adminList_updatePage(' + pagesNum + '); return false;" class="number">' + pagesNum + '</a>';
    }
    
    document.getElementById('bar-pages').innerHTML = '<div style="float: left;">' + str_prevPage + '</div>' + '<div style="float: right;">' + str_nextPage + '</div>' + '<div>' + pages.join('') + '</div>';
}

/* ## */

function adminList_updateLimit(limit) {
    items_limit = limit;
    items_cpage = 1;
    
    uri = uri.replace(/&?(page|limit)=[^&]+/, '') + ((items_limit || items_limit == '0') ? '&limit=' + items_limit : '');
    
    adminList_updateList(function (XMLDocument) {
        adminList_updateList__central(XMLDocument);
        adminList_updateList__closing(XMLDocument);
        
        adminList_renderBarPages(items_cpage, items_total, items_limit, 15);
        adminList_renderBarLimits(items_limit);
        
        window.location.hash = uri_hash = (window.location.hash.replace(/&?(page|limit)=[^&]+/g, '') + ((items_limit || items_limit == '0') ? '&limit=' + items_limit : '')).replace(/^#?&/, '#');
    });
}

function adminList_renderBarLimits(limit) {
    var limits = new Array(25, 50, 100, 500, 0);
    var values = new Array();
    
    for (var i = 0; i != limits.length; i++) {
        if (limit == limits[i]) {
            values[values.length] = limits[i] ? '<span style="font-size: 10px;">' + limits[i] + '</span>' : '<span>all</span>';
        } else {
            values[values.length] = '<a href="" onmouseover="this.href = uri.replace(/&(limit|page)=[^&]+/g, \'\') + \'&limit=' + limits[i] + '\'" onclick="adminList_updateLimit(' + limits[i] + '); return false;">' + (limits[i] ? '<span style="font-size: 10px;">' + limits[i] + '</span>' : 'all') + '</a>';   
        }
    }
    
    document.getElementById('bar-limits').innerHTML = '[ per page: ' + values.join(' | ') + ' ]';
}

/* ## */

function adminList_updateSearchQuery(query) {
    query = query.replace(/^\s+/, '').replace(/\s+$/, '');
    
    if (query == items_query) {
        return;
    }
    
    items_query = query;
    items_cpage = 1;
    
    uri = uri.replace(/&?(page|query)=[^&]+/, '') + (items_query ? '&query=' + encodeURIComponent(items_query) : '');
    
    adminList_updateList(function (XMLDocument) {
        adminList_updateList__central(XMLDocument);
        adminList_updateList__closing(XMLDocument);
        
        adminList_renderBarPages(items_cpage, items_total, items_limit, 15);
        
        window.location.hash = uri_hash = (window.location.hash.replace(/&?(page|query)=[^&]+/g, '') + (items_query ? '&query=' + encodeURIComponent(items_query) : '')).replace(/^#?&/, '#');
    });
}

function adminList_renderBarSearchQuery() {
    document.getElementById('bar-search-query').innerHTML = '<label for="bar-search-query-input" style="cursor: text;">[ search: <input type="text" id="bar-search-query-input" onkeyup="adminList_updateSearchQuery(this.value)" value="' + items_query + '"> ]</label>';
}

/* ## */

function adminList_updateOrder(orderby, sortby) {
    items_order = orderby;
    items_sorto = sortby;
    items_cpage = 1;
    
    uri = uri.replace(/&?(page|orderby|sort)=[^&]+/, '') + (items_order ? '&orderby=' + items_order : '') + (items_sorto ? '&sort=' + items_sorto : '');
    
    adminList_renderBarCaptions();
    adminList_updateList(function (XMLDocument) {
        adminList_updateList__central(XMLDocument);
        adminList_updateList__closing(XMLDocument);
        
        adminList_renderBarPages(items_cpage, items_total, items_limit, 15);
        
        window.location.hash = uri_hash = (window.location.hash.replace(/&?(page|orderby|sort)=[^&]+/g, '') + (items_order ? '&orderby=' + items_order : '') + (items_sorto ? '&sort=' + items_sorto : '')).replace(/^#?&/, '#');
    });
}

function adminList_renderBarCaptions() {
    var columns = new Array();
    for (var i = 0, sorto, mark; i != items_metad.length; i++) {
        sort = (items_order == items_metad[i][0] && items_sorto == 'asc') ? 'desc' : 'asc';
        
        columns[columns.length] = '<th ' + (items_metad[i][2] ? 'title="' + items_metad[i][2] + '"' : '') + '>'
                                + ((items_order == items_metad[i][0]) ? ((sort == 'asc') ? '&darr;' : '&uarr;') : '')
                                + (items_metad[i][0] ? ' <a href="" onmouseover="this.href = uri.replace(/&(orderby|sort|page)=[^&]+/g, \'\') + \'&orderby=' + items_metad[i][0] + '&sort=' + sort + '\'" onclick="adminList_updateOrder(\'' + items_metad[i][0] + '\', \'' + sort + '\'); return false;">' + items_metad[i][1] + '</a> ' : '<span class="a">' + items_metad[i][1] + '</span>')
                                + ((items_order == items_metad[i][0]) ? ((sort == 'asc') ? '&darr;' : '&uarr;') : '')
                                + '</th>';
    }
    
    items_thead = '<tr>' + columns.join("\n") + '</tr>';
}

/* ## */

function adminList_renderBarSelections() {
    if (!items_inver) {
        document.getElementById('bar-selections').innerHTML = '[ selected: ' + (items_array.length == 0 ? 'none' : '<span style="font-size: 10px;">' + items_array.length + '</span>') + ' <span style="font-size: 10px;">/</span> ' + (items_total == 0 ? 'none' : '<span style="font-size: 10px;">' + items_total + '</span>') + ' ]';
    } else {
        document.getElementById('bar-selections').innerHTML = '[ selected: ' + (items_array.length == 0 ? 'all' : '<span style="font-size: 10px;">' + (items_total - items_array.length) + '</span>') + ' <span style="font-size: 10px;">/</span> ' + (items_total == 0 ? 'none' : '<span style="font-size: 10px;">' + items_total + '</span>') + ' ]';
    }
}

/* ## */

function importXML(ignoreErrors) {
    if (!$('Dialog_ImportXML')) {
        var dialog = document.createElement('div');
        dialog.id = 'Dialog_ImportXML';
        dialog.style.display = 'none';
        dialog.innerHTML =
              '<form method="POST" enctype="multipart/form-data" style="margin: 0;">'
            + '<div style="font-family: Arial; font-size: 12px; font-weight: bold;">Upload & Import XML File</div>'
            + '<table cellspacing="0" cellpadding="3" style="margin-top: 10px; margin-bottom: 10px; font-family: Arial; font-size: 12px;">'
            + '    <tr>'
            + '        <td>File:</td>'
            + '        <td><input type="file" name="import-xml" size="30" style="font-size: 11px;"></td>'
            + '    </tr>'
            + (ignoreErrors
            ? '    <tr>'
            + '        <td></td>'
            + '        <td><label for="ignore-errors"><input type="checkbox" id="ignore-errors" name="ignore-errors" value="1" style="margin: 0; padding: 0;"> &nbsp; ignore errors</label></td>'
            + '    </tr>'
            : '')
            + '</table>'
            + '<div style="text-align: center;">'
            + '    <button type="submit" class="button" onmouseover="mouseOvrBtn(this)" onmouseout="mouseOutBtn(this)" onfocus="this.blur()"><img src="/repository/admin/images/act_save.gif">&nbsp;&nbsp;Upload File</button>'
            + '</div>'
            + '</form>';
        
        document.body.insertBefore(dialog, document.body.childNodes[0]);
        
        new Dialog.Box('Dialog_ImportXML');
    }
    
    $('Dialog_ImportXML').open();
    
    document.onkeydown = function (e) {
        if ((window.event && window.event.keyCode == 27) || (e && e.which == 27)) {
            $('Dialog_ImportXML').close();
        }
    }
}

/* ## */

function adminList_buttonDropDownMenu(iMenu) {
    document.getElementById(iMenu).style.display = (document.getElementById(iMenu).style.display == '') ? 'none' : '';
}

/* ## */

function updateDocNote(docId) {
    var value = prompt('Note', docNotes[docId] ? docNotes[docId] : '');
    if (value != null && value != docNotes[docId]) {
        var url = window.location.href;
        uri = uri + '&doc_id=' + docId + '&act=update-note&note=' + encodeURI(value);
        
        adminList_updateList(function (XMLDocument) {
            adminList_updateList__central(XMLDocument);
            adminList_updateList__closing(XMLDocument);
        });
    }
}
