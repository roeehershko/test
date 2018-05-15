var Dialog = {};
Dialog.Box = Class.create();
Object.extend(Dialog.Box.prototype, {
    initialize: function (id) {
        this.createDialogBG();
        this.createDialogFG(id);
        
        new Draggable(id, { revert:true, handle:'dialog-draggable-handle', revert:false, starteffect:false, endeffect:false });
        window.onresize = this.position.bind(this);
        
        this.parent_element = this.dialog_fg.parentNode;
    },
    
    createDialogBG: function () {
        if ($('dialog_bg')) {
            this.dialog_bg = $('dialog_bg'); // only one 'dialog_bg' is required (for all dialogs).
        } else {
            this.dialog_bg = document.createElement('div');
            this.dialog_bg.id = 'dialog_bg';
            Object.extend(this.dialog_bg.style, {
                position: 'absolute',
                top: 0,
                left: 0,
                zIndex: 90,
                width: '100%',
                backgroundColor: '#E4E6EE',
                display: 'none',
        		filter: 'alpha(opacity=85)',
        		MozOpacity: '0.85',
        		Opacity: '0.85'
            });
            document.body.insertBefore(this.dialog_bg, document.body.childNodes[0]);
        }
    },
    
    createDialogFG: function (id) {
        this.dialog_fg = $(id);
        this.dialog_fg.open = this.open.bind(this);
        this.dialog_fg.close = this.close.bind(this);
        this.dialog_fg.resize = this.resize.bind(this);
        this.dialog_fg.hideLoader = this.hideLoader.bind(this);
        this.dialog_fg.showLoader = this.showLoader.bind(this);
        
        var fixPNG = (parseFloat(navigator.appVersion.split('MSIE')[1]) <= 6) ? 1 : 0;
        this.dialog_fg.innerHTML =
              '<map name="' + this.dialog_fg.id + '_close_button_map"><area shape="circle" coords="26,26,16" href="" onclick="$(\'' + this.dialog_fg.id + '\').close(); return false;"></map>'
            + '<table cellspacing="0" cellpadding="0">'
            + '    <tr id="dialog-draggable-handle">'
            + '        <td style="width: 30px; height: 30px;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-tl.png" style="width: 30px; height: 30px;">' : '<span style="width: 30px; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-tl.png\');"></span>') + '</td>'
            + '        <td>' + (!fixPNG ? '<img src="/repository/admin/images/dialog-t.png" style="width: 100%; height: 30px;">' : '<span style="width: 100%; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-t.png\', sizingMethod=\'scale\');"></span>') + '</td>'
            + '        <td style="width: 30px; height: 30px;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-tr.png" style="width: 30px; height: 30px;">' : '<span style="width: 30px; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-tr.png\');"></span>') + '</td>'
            + '        <td style="position: relative;">'
            +              '<img src="/repository/admin/images/' + (!fixPNG ? 'dialog-x.png' : 'void.gif') + '" usemap="#' + this.dialog_fg.id + '_close_button_map" style="position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-x.png\'); border: 0;">'
            +              '<img id="' + this.dialog_fg.id + '_ajax_loader" src="/repository/admin/images/dialog-ajax-loader.gif" style="position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; display: none;">'
            +          '</td>'
            + '    </tr>'
            + '    <tr>'
            + '        <td style="width: 30px; height: 100%;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-l.png" style="width: 30px; height: 100%;">' : '<span style="width: 30px; height: 100%; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-l.png\', sizingMethod=\'scale\');"></span>') + '</td>'
            + '        <td style="padding: 10px; background-color: #FFFFFF;">' + this.dialog_fg.innerHTML + '</td>'
            + '        <td style="width: 30px; height: 100%;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-r.png" style="width: 30px; height: 100%;">' : '<span style="width: 30px; height: 100%; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-r.png\', sizingMethod=\'scale\');"></span>') + '</td>'
            + '    </tr>'
            + '    <tr>'
            + '        <td style="width: 30px; height: 30px;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-bl.png" style="width: 30px; height: 30px;">' : '<span style="width: 30px; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-bl.png\');"></span>') + '</td>'
            + '        <td>' + (!fixPNG ? '<img src="/repository/admin/images/dialog-b.png" style="width: 100%; height: 30px;">' : '<span style="width: 100%; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-b.png\', sizingMethod=\'scale\');"></span>') + '</td>'
            + '        <td style="width: 30px; height: 30px;">' + (!fixPNG ? '<img src="/repository/admin/images/dialog-br.png" style="width: 30px; height: 30px;" onmousedown="$(\'' + this.dialog_fg.id + '\').resize()">' : '<span style="width: 30px; height: 30px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'/repository/admin/images/dialog-br.png\');"></span>') + '</td>'
            + '    </tr>'
            + '</table>';
        
        this.dialog_fg.style.position = 'absolute';
        this.dialog_fg.style.zIndex = this.dialog_bg.style.zIndex + 1;
    },
    
    showLoader: function () {
        eval(this.dialog_fg.id + '_ajax_loader').style.display = '';
    },
    
    hideLoader: function () {
        eval(this.dialog_fg.id + '_ajax_loader').style.display = 'none';
    },
    
    moveDialogBox: function (where) {
        Element.remove(this.dialog_fg);
        if (where == 'back')
            this.dialog_fg = this.parent_element.appendChild(this.dialog_fg);
        else
            this.dialog_fg = this.dialog_bg.parentNode.insertBefore(this.dialog_fg, this.dialog_bg);
    },
    
    open: function () {
        document.body.scrollTop = 0;
        
        this.position();
        this.moveDialogBox('out');
        this.selectBoxes('hide');
        //this.dialog_bg.show();
        //this.dialog_fg.show();
        new Effect.Appear(this.dialog_bg, {duration: 0.1, from: 0, to: 0.85});
        new Effect.Appear(this.dialog_fg, {duration: 0.1});
    },
    
    close: function () {
        this.selectBoxes('show');
        //this.dialog_bg.hide();
        //this.dialog_fg.hide();
        new Effect.Fade(this.dialog_bg, {duration: 0.1});
        new Effect.Fade(this.dialog_fg, {duration: 0.1});
        this.moveDialogBox('back');
        /* Clears all input fields in the dialog box.
        $A(this.dialog_fg.getElementsByTagName('input')).each(function (e) {
            if (e.type != 'submit') e.value = ''
        });
        */
    },
    
    position: function () {
        var dataH = document.getElementsByTagName('body')[0].clientHeight;
        var portH = document.getElementsByTagName('body')[0].scrollHeight;
        this.dialog_fg.style.left   = (Element.getWidth(this.dialog_bg) / 2 - Element.getWidth(this.dialog_fg) / 2) + 'px';
        this.dialog_fg.style.top    = (dataH / 2 - Element.getHeight(this.dialog_fg) / 2) + 'px';
        this.dialog_bg.style.height = (dataH > portH ? dataH : portH) + 'px';
    },
    
    resize: function () {
        /*
        var dim = Element.getDimensions(this.dialog_fr);
        //this.dialog_fr.style.width  = (dim.width  + 25) + 'px';
        //this.dialog_fr.style.height = (dim.height + 25) + 'px';
        
        var mouse_x_s, mouse_y_s;
        var resizing = 1;
        document.onmouseup = function (e) {
            if (resizing) {
                var mouse_x_e, mouse_y_e;
                
                alert('done: ' + mouse_x_s + ',' + mouse_y_s + ' -> ' + mouse_x_s + ',' + mouse_y_s)
                resizing = 0;
            }
        }
        */
    },
    
    selectBoxes: function(what) {
        $A(document.getElementsByTagName('select')).each(function (select) {
            Element[what](select);
        });
        
        if (what == 'hide')
            $A(this.dialog_fg.getElementsByTagName('select')).each(function (select) {
                Element.show(select)
            });
        }
    }
);