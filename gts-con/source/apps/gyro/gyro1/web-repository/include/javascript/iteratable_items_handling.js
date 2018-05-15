
function update_fields_index(node, index_from, index_to, original_node, mode, hide_node_with_suffix, show_node_with_suffix, debug) {
    
    var tmp;
    var browserName = navigator.appName;
    
    if (node.hasChildNodes()) { 
        // NOTE: 'var' must exist before the 'i' here... otherwise - this recursive function will enter an infinite loop.
        for (var i = 0; i < node.childNodes.length; i++) {
            if (node.childNodes[i].nodeType == 1) {
                
                if (node.childNodes[i].getAttribute('id')) {
                    tmp = node.childNodes[i].getAttribute('id');
                    tmp = tmp.replace(eval('/__'+index_from+'/g'),'__'+index_to);
                    node.childNodes[i].setAttribute('id',tmp);
                    
                    if (hide_node_with_suffix && eval('/'+hide_node_with_suffix+'/.test(tmp)')) {
                        node.childNodes[i].style.display = 'none';
                    }
                    
                    if (show_node_with_suffix && eval('/'+show_node_with_suffix+'/.test(tmp)')) {
                        node.childNodes[i].style.display = '';
                    }
                    
                }
                
                if (node.childNodes[i].getAttribute('name')) {
                    tmp = node.childNodes[i].getAttribute('name');
                    tmp = tmp.replace(eval('/__'+index_from+'/g'),'__'+index_to);
                    /*
                    The following does NOT work in IE:
                        node.childNodes[i].setAttribute('name',tmp);
                    Therefore we use the following workaround:
                    */
                    
                    if (browserName == "Microsoft Internet Explorer") {
                        node.childNodes[i].outerHTML = node.childNodes[i].outerHTML.replace(eval('/__'+index_from+'/g'),'__'+index_to);
                    } else {
                        node.childNodes[i].setAttribute('name',tmp);
                    }
                }
                
                if (node.childNodes[i].getAttribute('href') && node.childNodes[i].getAttribute('href').substr(0,10) == 'javascript') {
                    tmp = node.childNodes[i].getAttribute('href');
                    tmp = tmp.replace(eval('/__'+index_from+'/g'),'__'+index_to);
                    node.childNodes[i].setAttribute('href',tmp);
                }
                
                if (node.childNodes[i].onchange) {
                    
                    tmp = node.childNodes[i].onchange;
                    tmp = tmp.toString();
                    tmp = tmp.replace(/(\d)/g,index_to);
                    tmp = tmp.replace(/function(.+)/,'');
                    tmp = tmp.replace(/[\{\};\r\n]/g,'');
                    tmp = tmp.replace(/[']/g,'\\\'');
                    
                    eval('node.childNodes[i].setAttribute(\'onchange\', \''+tmp+'\')');
                    
                }
				
				if (node.childNodes[i].onkeyup) {
                    
                    tmp = node.childNodes[i].onkeyup;
                    tmp = tmp.toString();
                    tmp = tmp.replace(/(\d)/g,index_to);
                    tmp = tmp.replace(/function(.+)/,'');
                    tmp = tmp.replace(/[\{\};\r\n]/g,'');
                    tmp = tmp.replace(/[']/g,'\\\'');
                    
                    eval('node.childNodes[i].setAttribute(\'onkeyup\', \''+tmp+'\')');
                    
                }
                
                // If a field's ID starts with "sub_" then we hide it.
                // Useful in places that have a sub-cateogry pull-down that is chosen according to the 'category' pull-down
                // The "original_node" is our indictor that we'in in the 'add' action
                // (like we did in WANTED.CO.IL
                if (original_node && node.childNodes[i].getAttribute('id') && node.childNodes[i].getAttribute('id').substr(0,4) == 'sub_') {
                    node.childNodes[i].style.display = 'none';
                }
                
                // If we're in "CLEAN" mode, we need to remove the value of the cloned item.
                if (mode == 'clean') {
                    
                    // In WANTED.CO.IL we used special, custom-made pull-down-menus. Therefore, once cloned, we had to 'clean' the innerHTML of hte "current_option"
                    /*
                    if (node.childNodes[i].className == 'current_option') {
                        if (/year__/.test(node.childNodes[i].getAttribute('id'))) {
                            node.childNodes[i].innerHTML = 'שנה';
                        } else if (/month__/.test(node.childNodes[i].getAttribute('id'))) {
                            node.childNodes[i].innerHTML = 'חודש';
                        } else if (/day__/.test(node.childNodes[i].getAttribute('id'))) {
                            node.childNodes[i].innerHTML = 'יום';
                        } else {
                            node.childNodes[i].innerHTML = '';
                        }
                    }
                    
                    if (node.childNodes[i].className == 'selected') {
                        node.childNodes[i].className = '';
                    }
                    */
                    
                    // Clearing the field's value
                    if (node.childNodes[i].tagName.toLowerCase() == 'input') {
                        node.childNodes[i].value = '';
                    }
                    if (node.childNodes[i].tagName.toLowerCase() == 'option') {
                        node.childNodes[i].selected = false;
                    }
                    if (node.childNodes[i].tagName.toLowerCase() == 'textarea') {
                        node.childNodes[i].innerHTML = '';
                    }
                    
                    /*
                    if (node.childNodes[i].tagName.toLowerCase() == 'input' && !/__option/.test(node.childNodes[i].getAttribute('id'))) {
                        node.childNodes[i].value = '';
                        //alert(node.childNodes[i].parentNode.innerHTML);
                    }
                    */
                    
                } else if (original_node && original_node.childNodes[i]) {
                    
                    // The following works also for TEXTAREA (for both IE & FF)
                    node.childNodes[i].value = original_node.childNodes[i].value;
                
                }
               
                //DEBUG
                //document.getElementById('debug').innerHTML = document.getElementById('debug').innerHTML + node.childNodes[i].tagName + ' | ';
                
                if (original_node) {
                    update_fields_index(node.childNodes[i], index_from, index_to, original_node.childNodes[i], mode, debug);
                } else {
                    update_fields_index(node.childNodes[i], index_from, index_to, '', mode, debug);
                }
            }
        }
    }
    
    return true;
}


function iteratable_items_handling(action, section, index_string, direction, mode, start_index, loader, hide_node_with_suffix, show_node_with_suffix, debug) {
    
    /*** The form MUST have autocomplete="off" since otherwise Firefox will automatically complete the HIDDEN fields too and the indexes will change ***/
    last_item_index = parseInt(document.getElementById('last_item_index__'+section).value);
    index = parseInt(index_string.substr(2,index_string.length-2));
    
    /*** The 'show_node_with_suffix' is used if we want to display an "inside" element of the new item, which by default is set to hidden ***/
    /*** It is used, for example, in hoopa.co.il/my-account/?step=wed-tasks ***/
    /*** Same goes for 'hide_node_with_suffix' ***/
    
    if (!start_index) {
        start_index = 1;
    }
    
    var browserName=navigator.appName;
    
    switch (action) {
        case 'add':
			/*
			if (debug) {
			   alert('container__'+section+'__'+last_item_index);
			   alert(document.getElementById('container__'+section+'__'+last_item_index));
			}
			*/
            if (document.getElementById('container__'+section+'__'+last_item_index)) {
                
                /*
                if (loader) {
                    visible(loader);
                }
                */
                
                container__section = document.getElementById('container__'+section+'__'+last_item_index);
                div = container__section.cloneNode(true);
                div.setAttribute('id','container__'+section+'__'+(last_item_index+1));
                
                /*** When using innerHTML - in FF it resets the field VALUE of newly created items, so we must use the function "update_fields_index"... ***/
                /*** This causes the "move" action (of items that were just added) to be screwed up, however we could still use the "innerHTML" method to solve the "add new item" action ***/
                //div.innerHTML = div.innerHTML.replace(eval('/__'+last_item_index+'/g'),'__'+(last_item_index+1));
                
                /*
                if (debug) {
				   alert(container__section);
                }
                */
                
                update_fields_index(div, last_item_index, (last_item_index+1), container__section, mode, hide_node_with_suffix, show_node_with_suffix, debug);
                
                document.getElementById(section).appendChild(div);
                last_item_index++;
                
                // IE doesn't render event via DOM - therefore we use the following workaround.
                if (browserName == "Microsoft Internet Explorer") {
                	document.getElementById('container__'+section+'__'+last_item_index).innerHTML = document.getElementById('container__'+section+'__'+last_item_index).innerHTML;
				}
                //alert(document.getElementById('container__'+section+'__'+last_item_index).innerHTML);
                
                /*
                if (loader) {
                    visible(loader);
                }
                */
                
            }
            break;
        case 'remove':
            total_number_of_items = last_item_index+1;
            if (total_number_of_items > start_index && document.getElementById('container__'+section+'__'+index)) {
                for (i = 0; i < total_number_of_items; i++) {
                    div = document.getElementById('container__'+section+'__'+i);
                    if (i == index) {
                        div.parentNode.removeChild(div);
                        last_item_index--;
                    } else if (i > index && last_item_index < (total_number_of_items-1) ) {
                        // If a section has been removed - we need to re-index the rest of the sections.
                        
                        //div.innerHTML = div.innerHTML.replace(eval('/__'+i+'/g'),'__'+(i-1));
                        update_fields_index(div, i, (i-1), '', mode);
                        
                        div.setAttribute('id','container__'+section+'__'+(i-1));
                    }
                }
            }
            break;
        case 'move':
            if (document.getElementById('container__'+section+'__'+index)) {
                current = document.getElementById('container__'+section+'__'+index);
                if (direction == 'up' && index > 0) {
                    if (document.getElementById('container__'+section+'__'+(index-1))) {
                        prev = document.getElementById('container__'+section+'__'+(index-1));
                        
                        //prev.innerHTML = prev.innerHTML.replace(eval('/__'+(index-1)+'/g'),'__'+index);
                        update_fields_index(prev, (index-1), index, '', mode);
                        
                        prev.setAttribute('id','container__'+section+'__'+index);
                        
                        //current.innerHTML = current.innerHTML.replace(eval('/__'+index+'/g'),'__'+(index-1));
                        update_fields_index(current, index, (index-1), '', mode);
                        
                        current.setAttribute('id','container__'+section+'__'+(index-1));
                        current.parentNode.insertBefore(current,prev);
                        
                        // IE doesn't render event via DOM - therefore we use the following workaround.
                        if (browserName == "Microsoft Internet Explorer") {
                            prev.innerHTML = prev.innerHTML;
                            current.innerHTML = current.innerHTML;
                        }
                
                    }
                }
                if (direction == 'down' && index < last_item_index) {
                    if (document.getElementById('container__'+section+'__'+(index+1))) {
                        next = document.getElementById('container__'+section+'__'+(index+1));
                        
                        //next.innerHTML = next.innerHTML.replace(eval('/__'+(index+1)+'/g'),'__'+index);
                        update_fields_index(next, (index+1), index, '', mode);
                        
                        next.setAttribute('id','container__'+section+'__'+index);
                        
                        //current.innerHTML = current.innerHTML.replace(eval('/__'+index+'/g'),'__'+(index+1));
                        update_fields_index(current, index, (index+1), '', mode);
                        
                        current.setAttribute('id','container__'+section+'__'+(index+1));
                        next.parentNode.insertBefore(next,current);
                        
                        // IE doesn't render event via DOM - therefore we use the following workaround.
                        if (browserName == "Microsoft Internet Explorer") {
                            current.innerHTML = current.innerHTML;
                            next.innerHTML = next.innerHTML;
                        }
                        
                    }
                }
            }
            break;
    }
    
    document.getElementById('last_item_index__'+section).value = last_item_index;
} 
