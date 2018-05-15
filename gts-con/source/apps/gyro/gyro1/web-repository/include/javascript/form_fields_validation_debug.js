function create_fields_array(node) {
    var fields = [];
    if (node.hasChildNodes()) {    
        // NOTE: 'var' must exist before the 'i' here... otherwise - this recursive function will enter an infinite loop.
        for (var i = 0; i < node.childNodes.length; i++) {
            if (node.childNodes[i].nodeType == 1) {
                if (
                    (
                        (node.childNodes[i].tagName.toLowerCase() == 'input' && (node.childNodes[i].getAttribute('type') == 'text' || node.childNodes[i].getAttribute('type') == 'checkbox' || node.childNodes[i].getAttribute('type') == 'hidden' || node.childNodes[i].getAttribute('type') == 'file')) ||
                        (node.childNodes[i].tagName.toLowerCase() == 'select') ||
                        (node.childNodes[i].tagName.toLowerCase() == 'textarea')
                    ) &&
                    (node.childNodes[i].getAttribute('id')) &&
                    (document.getElementById('label_of_'+node.childNodes[i].getAttribute('id')))
                ) {
                    // we "bumped" into a relevant field, so we add it to the array.
                    fields.push(node.childNodes[i]);
                    
                    //DEBUG
                    //document.getElementById('debug').innerHTML = document.getElementById('debug').innerHTML + node.childNodes[i].getAttribute('id') + ' - ' + node.childNodes[i].tagName + ' - ' + node.childNodes[i].getAttribute('type') + '<br>';
                    
                } else {
                    
                    //DEBUG
                    //document.getElementById('debug').innerHTML = document.getElementById('debug').innerHTML + node.childNodes[i].tagName + ' | ';
                    
                    // we go on into the childs of this node
                    // and we concatenate the returned array with the current fields array
                    fields = fields.concat(create_fields_array(node.childNodes[i]));
                    
                }
            
            }
        }
    } 
    return fields; 
}

function form_fields_validation(node) {
    
    var field_error = 0;	
    var fields = [];
    
    // Preparing the fields array (with only fields that have a label, and that are of type 'text')
    fields = create_fields_array(node);
    
    for (var i = 0; i != fields.length; i++) {
        
        failed = false;
        
        // Validation according to the NAME of the field
        if (fields[i].getAttribute('type') != 'checkbox' && /(phone|fax|mobile|cellular|birth)/.test(fields[i].getAttribute('name'))) {
            fields[i].value = fields[i].value.replace(/([^\d])/g,'');
        }
        if (fields[i].getAttribute('type') != 'checkbox' && /(name)/.test(fields[i].getAttribute('name'))) {
            fields[i].value = fields[i].value.replace(/(\d)/g,'');
        }
        if (fields[i].getAttribute('type') != 'checkbox' && /(email|e-mail)/.test(fields[i].getAttribute('name'))) {
            if (fields[i].value && !/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(fields[i].value)) {
                failed = true;
            }
        }
        if (fields[i].getAttribute('type') != 'checkbox' && /(price)/.test(fields[i].getAttribute('name'))) {
            fields[i].value = fields[i].value.replace(/(\..+)/g,'');
            fields[i].value = fields[i].value.replace(/\D/g,'');
        }
         
        // Validation according to the LABEL of the field
        if (fields[i].getAttribute('type') != 'checkbox' && /(phone|fax|mobile|cellular|birth)/.test(document.getElementById('label_of_'+fields[i].getAttribute('id')).innerHTML.toLowerCase())) {
            fields[i].value = fields[i].value.replace(/([^\d])/g,'');
        }
        if (fields[i].getAttribute('type') != 'checkbox' && /(name)/.test(document.getElementById('label_of_'+fields[i].getAttribute('id')).innerHTML.toLowerCase())) {
            fields[i].value = fields[i].value.replace(/(\d)/g,'');
        }
        if (fields[i].getAttribute('type') != 'checkbox' && /(email|e-mail)/.test(document.getElementById('label_of_'+fields[i].getAttribute('id')).innerHTML.toLowerCase())) {
            if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(fields[i].value)) {
                failed = true;
            }
        }
        if (/(price)/.test(document.getElementById('label_of_'+fields[i].getAttribute('id')).innerHTML.toLowerCase())) {
            fields[i].value = fields[i].value.replace(/(\..+)/g,'');
            fields[i].value = fields[i].value.replace(/\D/g,'');
        }
        
        // If the field is mandatory - we check that it has a value.
        // If it's a checkbox - we check for 'checked'.
        // In any case - we only do the check if the element is NOT	hidden.
        if (!fields[i].disabled && fields[i].style.display != 'none' && /(\*)/.test(document.getElementById('label_of_'+fields[i].getAttribute('id')).innerHTML.toLowerCase())) {
            // Checkbox
            if (fields[i].getAttribute('type') == 'checkbox') {
                if (fields[i].checked != true) {
                    failed = true;
                }
            // Input fields or Textarea
            } else if (!fields[i].value) {
                failed = true;
            }
        }
        
        
        
        // DEBUG
        //document.getElementById('debug').innerHTML = document.getElementById('debug').innerHTML + failed + ': ' + document.getElementById('label_of_'+fields[i].getAttribute('id')).className + '<br>';
        
        if (failed) {
        
            // Marking the relevant field sub-title
            document.getElementById('label_of_'+fields[i].getAttribute('id')).className = 'error ' + document.getElementById('label_of_'+fields[i].getAttribute('id')).className.replace(/(error ?)/,'');
            
            // Marking the relevant field
            fields[i].className = 'error ' + fields[i].className.replace(/(error ?)/,'');
            
            field_error = 1;
        
        } else {
           
            // If a value exists, we remove the error mark from the label and from the input field
            document.getElementById('label_of_'+fields[i].getAttribute('id')).className = document.getElementById('label_of_'+fields[i].getAttribute('id')).className.replace(/(error ?)/,'');
            fields[i].className = fields[i].className.replace(/(error ?)/,'');
            
        }
            
    }
    
    return field_error;
}
