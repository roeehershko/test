function mediaplayer(movie_src_url, width, height) {
	
	document.write('<object width="'+width+'" height="'+height+'" CLASSID="CLSID:22D6f312-B0F6-11D0-94AB-0080C74C7E95" STANDBY="Loading Windows Media Player components..." type="application/x-oleobject">\n');
	
	document.write('	<param name="FileName" value="'+movie_src_url+'" />\n');
	document.write('	<param name="AutoStart" value="True" />\n');
	document.write('	<param name="PlayCount" value="1" />\n');
	document.write('	<param name="ShowControls" value="True" />\n');
	document.write('	<param name="ShowStatusBar" value="True" />\n');
	//document.write('	<param name="uimode" value="full" />\n');
	
	document.write('	<embed type="application/x-mplayer2" src="'+movie_src_url+'" autostart=1 showcontrols=1 width="'+width+'" height="'+height+'" pluginspage="https://www.microsoft.com/Windows/MediaPlayer/"></embed>\n');
	
	document.write('</object>\n');
	
}
