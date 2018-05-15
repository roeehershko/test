function uploadFile(file_types) {
	
	file_type = new Array();
	file_type = file_types.split(',');
	file_type_verified = 0;
	
	for (i = 0; i < file_type.length; i++) {
		if (eval('/(\.'+file_type[i]+'$)/i').test(document.getElementById('file').value)) {
			file_type_verified = 1;
		}
	}
	if (file_type_verified == 1) {
		
		// Changing the upload button to a 'loader' animated gif.
		document.getElementById('upload_button').style.display = 'none';
		document.getElementById('upload_button_loader').style.display = '';
    	
		document.getElementById('uploadFile_form').submit();
		
	} else {
		alert(file_type + ' ' + 'ניתן להעלות אך ורק קובץ מסוג');
	}
	
}

function uploadFile_callback(success, filename, error) {
    
    /*
    Possible errors:
    - invalid directory
    - error creating directory
    - error uploading file
    - invalid file type
    - invalid file size
    - invalid file name
    - file already exists
    - invalid destination
    */
    
    var href_root_path = document.getElementById('href_root_path').value; // This is the href() prefix of the 'filename'
   	var filename_href = href_root_path + filename;
    var filename_element = document.getElementById('filename_element'); // This is the element that contains the link to the file, simply to show the user what he uploaded.
    
    if (success) {
        
        if (filename.substr(filename.length-13,filename.length) == 'thumbnail.jpg') {  
    		/*
    		var div1 = document.createElement('div');
        	div1.innerHTML  = '<img src="'+href_root_path+'images/global/spacer.gif" style="width: 120px; height: 90px; border: 1px solid #CCCCCC;">';
        	document.getElementById('filename_element').parentNode.insertBefore(div1, document.getElementById('filename_element'));
        	document.getElementById('filename_element').parentNode.removeChild(document.getElementById('filename_element'));
        	div1.setAttribute('id','filename_element');
    		
    		var div2 = document.createElement('div');
    		div2.innerHTML  = '<img src="'+filename_href+'" style="width: 120px; height: 90px; border: 1px solid #CCCCCC;">';
        	document.getElementById('filename_element').parentNode.insertBefore(div2, document.getElementById('filename_element'));
        	document.getElementById('filename_element').parentNode.removeChild(document.getElementById('filename_element'));
        	div2.setAttribute('id','filename_element');
        	*/
        	
        	document.getElementById('param__business_details__thumbnail').value = filename;
        	
        	// Since the browser will NOT reload the latest version of the image
        	// (because it's always the SAME filename)
        	// We need to use the following workaround
        	filename_element.src = filename_href + '?' + Math.floor(Math.random()*100);
    		
    	} else {
    		
    		//filename_element.innerHTML = '<a href="'+filename_href+'" target="_blank" style="font-weight: bold;">למכרז זה מצורף קובץ - לחצו כאן להורדה</a>';
    		
    		// Here we use the 'removeChild' method instead of the line above
    		// Since for some reason, it created a line-break after the file was uploaded.
    		var div = document.createElement('div');
        	div.innerHTML  = '<a href="'+filename_href+'" target="_blank" style="font-weight: bold;">למכרז זה מצורף קובץ - לחצו כאן להורדה</a>';
        	document.getElementById('filename_element').parentNode.insertBefore(div, document.getElementById('filename_element'));
        	document.getElementById('filename_element').parentNode.removeChild(document.getElementById('filename_element'));
        	div.setAttribute('id','filename_element');
    		
    		document.getElementById('param__attachment').value = filename;
    		
    	}  
    	
    	// Display the 'remove attachment' link
    	document.getElementById('filename_remove').style.display = ''; 
       
    } else {
        alert('העלת הקובץ נכשלה' + '\n' + error);
    } 
    
    // Changing the upload button back to "upload"
    document.getElementById('upload_button_loader').style.display = 'none';
    document.getElementById('upload_button').style.display = '';
    
    
}

function removeFile(name_of_attachment_param) {
	document.getElementById(name_of_attachment_param).value='';
	document.getElementById('filename_element').style.display='none';
	document.getElementById('filename_remove').style.display='none';	
}
