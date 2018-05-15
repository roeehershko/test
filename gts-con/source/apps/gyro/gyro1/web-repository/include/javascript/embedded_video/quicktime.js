function quicktime(movie_src_url, width, height, bgcolor) {
	/*
	
	* For some reason the use of <object> doesn't work, and ONLY when using only 'embed' it works.
	
	document.write('<object CLASSID="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" CODEBASE="https://www.apple.com/qtactivex/qtplugin.cab" width="'+width+'" height="'+height+'">\n');
	
	document.write('	<param name="src" value="'+movie_src_url+'" />\n');
	document.write('	<param name="type" value="video/quicktime" />\n');
	document.write('	<param name="autoplay" value="false" />\n');
	document.write('	<param name="loop" value="false" />\n');
	document.write('	<param name="bgcolor" value="'+bgcolor'+" />\n');
	document.write('	<param name="controller" value="true" />\n');
	*/
	document.write('	<embed type="video/quicktime" src="'+movie_src_url+'" autoplay="false" loop="false" controller="true" bgcolor="'+bgcolor+'" width="'+width+'" height="'+height+'" pluginspage="https://www.apple.com/quicktime/download/"></embed>\n');
	
	//document.write('</object>\n');
	
}
