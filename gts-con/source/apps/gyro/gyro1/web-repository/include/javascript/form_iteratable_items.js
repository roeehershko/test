function iteratable_items_handling(action, section, index, direction) {
    index = index.replace(/__/, '');
    // Finding the largest index
    // We cannot use the number of children of the section - since if the user REMOVED a specific item - then we won't know the last index
    var last_index = $('#'+section).children('.container__'+section).length - 1;
    var current_index;
    for (i = 1; i <= $('#'+section).children('.container__'+section).length; i++) {
    	current_index = parseInt($('#'+section+' .container__'+section+':nth-child('+i+')').attr('id').replace(eval('/container__'+section+'__/'), ''));
    	//console.log('Current Index: '+current_index);
    	if (current_index > last_index) {
    		last_index = current_index;
    	}
    }
   	//console.log('last_index: '+last_index);
    switch (action) {
        case 'add':
			$('.container__'+section+':last').clone().appendTo('#'+section);
			$('.container__'+section+':last').attr('id', $('.container__'+section+':last').attr('id').replace(eval('/__'+last_index+'/g'), '__'+(last_index+1)));
			$('.container__'+section+':last').html($('.container__'+section+':last').html().replace(eval('/__'+last_index+'/g'), '__'+(last_index+1)));
			/*
			$('.container__'+section+':last .autocomplete__post_id').autocomplete(asset_data,{width:350, minChars:1, matchContains:1, matchSubset:1, cacheLength:1, max:100, delay:250, autoFill:false, mustMatch:true, 
				formatItem: function (row, i, max, term) {
					//eval('var data = '+row);
					//return data.title;
					return row.title;
				},
				formatResult: function (row, i, max) {
					//eval('var data = '+row);
					//return data.id;
					return row.id;
				}
			});
			*/
			break;
        case 'remove':
            //console.log($('#'+section).children('.container__'+section).length);
            if ($('#'+section).children('.container__'+section).length > 1) {
            	$('#container__'+section+'__'+index).remove();
           	}
            break;
        case 'move':
            if (direction == 'up') {
            	var item = $('#container__'+section+'__'+index).clone();
            	var prev = $('#container__'+section+'__'+index).prev();
            	if (prev.length > 0) {
            		$('#container__'+section+'__'+index).remove();
            		prev.before(item);
            	}
            } else if (direction == 'down') {
            	var item = $('#container__'+section+'__'+index).clone();
            	var next = $('#container__'+section+'__'+index).next();
            	if (next.length > 0) {
            		$('#container__'+section+'__'+index).remove();
            		next.after(item);
            	}
            }
            break;
    }
} 
