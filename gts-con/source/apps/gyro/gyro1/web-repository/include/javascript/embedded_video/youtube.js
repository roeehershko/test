function youtube(width, height, source) {
	document.write('<object width="' + width + '" height="' + height + '">');
	document.write('<param name="movie" value="' + source + '&fs=1"></param>');
	document.write('<param name="menu" value="false"></param>');
	document.write('<param name="allowFullScreen" value="true"></param>');
	document.write('<embed src="' + source + '&fs=1" type="application/x-shockwave-flash" width="' + width + '" height="' + height + '"></embed>');
	document.write('</object>');
}
