<?
$object = getObject('Rolling News');

if ($object['content']) {
?>

	<script type="text/javascript" language="JavaScript1.2">
	<!--
	var news = new Array();
	
	<? 
	$news = explode('---', $object['content']);
	
	for ( $i = 0; $i != count($news); $i++ ):
		echo 'news[' . $i . '] = \'' . str_replace("'", "\'", preg_replace("/[\n\r]/", '', trim($news[$i]))) . '\'' . "\n";
	endfor;

	?>
	
	/* the newsticker will automatically adjusts to the
	   height setting in the style of #newstickerFrame. */
	var newstickerFrame;
	var newsticker;
	var pauseTime = 4800; // in milliseconds.
	var isPaused = 0;
	var index = 0;
	
	function newstickerPause() { isPaused = 1; }
	function newstickerResume() { isPaused = 0; }
	
	function newstickerInit() {
		if (news.length > 0) {
			newstickerFrame = document.getElementById('newstickerFrame');
			newsticker = document.getElementById('newsticker');
			setInterval('scrollNewsticker()', 1);
		}
	}
	
	function scrollNewsticker() {
		if (isPaused) return;
		if (!newsticker.innerHTML | parseInt(newsticker.offsetHeight) == -parseInt(newsticker.style.top)) {
			newsticker.innerHTML = news[index];
			index = news[index + 1] ? index + 1 : 0;
			newsticker.style.top = parseInt(newstickerFrame.style.height);
		}
		newsticker.style.top = parseInt(newsticker.style.top) - 1 + 'px';
		if (parseInt(newsticker.style.top) % parseInt(newstickerFrame.style.height) == 0) {
			newstickerPause();
			setTimeout('newstickerResume()', pauseTime);
		}
	}
	
	//-->
	</script>
	
	<? if (count($news) >= 1): ?>
	<? $news_exist = true; ?>
	<? /*<div class="s_title"style="margin-bottom: 0;">News & Updates</div>*/ ?>
	<div class="box">
		<div class="rolling_news">
			<div style="margin: 0 10px 0 10px; padding: 8px 0 0 0;">
				<div id="newstickerFrame" style="height: 110px; position: relative; overflow: hidden;" onmouseover="newstickerPause()" onmouseout="newstickerResume()">
					<div id="newsticker" style="position: absolute;"></div>
				</div>
			</div>
		</div>
	</div>
	
	<script type="text/javascript">
	<!--
	newstickerInit();
	//-->
	</script>
	
	<? endif; ?>
	
<? } // of if ?>
