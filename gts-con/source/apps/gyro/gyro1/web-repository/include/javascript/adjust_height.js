function adjust_height(source, target, delta) {
	
	//var before__source_height = document.getElementById(source).offsetHeight;
	//var before__target_height = document.getElementById(target).offsetHeight;
	
	if (!delta) {
		delta = 0;
	}
	
	if (document.getElementById(source) && document.getElementById(target)) {
        if (document.getElementById(source).offsetHeight > document.getElementById(target).offsetHeight) {
            if (document.getElementById(target).offsetHeight != (document.getElementById(source).offsetHeight + delta)) {
                document.getElementById(target).style.height = document.getElementById(source).offsetHeight + delta + 'px';
            }
        } else {
            if (document.getElementById(source).offsetHeight != (document.getElementById(target).offsetHeight - delta)) {
                if (document.getElementById(target).offsetHeight - delta > 0) {
                    document.getElementById(source).style.height = document.getElementById(target).offsetHeight - delta + 'px';
                }
            }
        }
	}
	
	//var after__source_height = document.getElementById(source).offsetHeight;
	//var after__target_height = document.getElementById(target).offsetHeight;
	
	//alert('BEFORE: source: ' + before__source_height + 'px' + '\n' + 'target: ' + before__target_height +'px' + '\n' + 'AFTER: source: ' + after__source_height + 'px' + '\n' + 'target: ' + after__target_height +'px');
}
