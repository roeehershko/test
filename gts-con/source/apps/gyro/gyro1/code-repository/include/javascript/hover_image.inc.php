<? // This script uses Product Parameters - hover_title, hover_image and hover_text to display them as a "hover window" ?>
<script type="text/javascript">

/***********************************************
* Image w/ description tooltip- By Dynamic Web Coding (www.dyn-web.com)
* Copyright 2002-2007 by Sharon Paine
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

/* IMPORTANT: Put script after tooltip div or
	 put tooltip div just before </BODY>. */

var dom = (document.getElementById) ? true : false;
var ns5 = (!document.all && dom || window.opera) ? true: false;
var ie5 = ((navigator.userAgent.indexOf("MSIE")>-1) && dom) ? true : false;
var ie4 = (document.all && !dom) ? true : false;
var nodyn = (!ns5 && !ie4 && !ie5 && !dom) ? true : false;

var origWidth, origHeight;

// avoid error of passing event object in older browsers
if (nodyn) { event = "nope" }

///////////////////////  CUSTOMIZE HERE   ////////////////////
// settings for tooltip
// Do you want tip to move when mouse moves over link?
var tipFollowMouse= true;
// Be sure to set tipWidth wide enough for widest image
var tipWidth= 240;
var offX= 20;	// how far from mouse to show tip
var offY= 12;
var tipFontFamily= "arial";
var tipFontSize= "11px";
// set default text color and background color for tooltip here
// individual tooltips can have their own (set in messages arrays)
// but don't have to
var tipFontColor= "#FFFFFF";
var tipBgColor= "#16415F";
var tipBorderColor= "#FFFFFF";
var tipBorderWidth= 2;
var tipBorderStyle= "solid";
var tipPadding= 4;

// tooltip content goes here (image, description, optional bgColor, optional textcolor)
var messages = new Array();
// multi-dimensional arrays containing:
// image and text for tooltip
// optional: bgColor and color to be sent to tooltip


<? if ($doc_type == 'group'): ?>
	<? for ($i = 0; $i < count($top10); $i++): ?>
		<? $product = getProduct($top10[$i]['id']); ?>
		<? $images = getProductImages($top10[$i]['id']); ?>
		<?
			if (!$images[0]['url']):
				$images[0]['url'] = 'images/global/spacer.gif';
			endif;
		?>
		    messages[<?=$i?>] = Array('<?=href($images[0]['url'])?>','<?=str_replace("'","\'",$product['description_short'])?>','<?=str_replace("'","\'",$product['title'])?>');
	<? endfor; ?>
<? endif; ?>


////////////////////  END OF CUSTOMIZATION AREA  ///////////////////

// preload images that are to appear in tooltip
// from arrays above
if (document.images) {
	var theImgs = new Array();
	for (var i=0; i<messages.length; i++) {
  	theImgs[i] = new Image();
		theImgs[i].src = messages[i][0];
  }
}

// to layout image and text, 2-row table, image centered in top cell
// these go in var tip in doTooltip function
// startStr goes before image, midStr goes between image and text
var startStr = '<div style="padding: 4px; color: white; direction: rtl;">';
var midStr = '">';
var endStr = '</div>';

////////////////////////////////////////////////////////////
//  initTip	- initialization for tooltip.
//		Global variables for tooltip.
//		Set styles
//		Set up mousemove capture if tipFollowMouse set true.
////////////////////////////////////////////////////////////
var tooltip, tipcss;
function initTip() {
	if (nodyn) return;
	tooltip = (ie4)? document.all['tipDiv']: (ie5||ns5)? document.getElementById('tipDiv'): null;
	tipcss = tooltip.style;
	if (ie4||ie5||ns5) {	// ns4 would lose all this on rewrites
		tipcss.width = tipWidth+"px";
		tipcss.fontFamily = tipFontFamily;
		tipcss.fontSize = tipFontSize;
		tipcss.color = tipFontColor;
		tipcss.backgroundColor = tipBgColor;
		tipcss.borderColor = tipBorderColor;
		tipcss.borderWidth = tipBorderWidth+"px";
		tipcss.padding = tipPadding+"px";
		tipcss.borderStyle = tipBorderStyle;
	}
	if (tooltip&&tipFollowMouse) {
		document.onmousemove = trackMouse;
	}
}

window.onload = initTip;

/////////////////////////////////////////////////
//  doTooltip function
//			Assembles content for tooltip and writes
//			it to tipDiv
/////////////////////////////////////////////////
var t1,t2;	// for setTimeouts
var tipOn = false;	// check if over tooltip link
function doTooltip(evt,num) {
	if (!tooltip) return;
	if (t1) clearTimeout(t1);	if (t2) clearTimeout(t2);
	tipOn = true;
	// set colors if included in messages array
	curBgColor = tipBgColor;
	curFontColor = tipFontColor;
	if (ie4||ie5||ns5) {
		<? // var tip = '<div style="font-size: 14px; font-weight: bold; color: #4972F0;">' + messages[num][2] + '</div>' + startStr + messages[num][0] + midStr + '<div style="font-family:' + tipFontFamily + '; font-size:' + tipFontSize + '; color:' + curFontColor + ';">' + messages[num][1] + '</div>' + endStr; ?>
		var tip = startStr + '<div style="font-weight: bold; color: #FFFFFF;">' + messages[num][2] + '</div>' + '<img src="' + messages[num][0] + '">' + '<div style="color: #FFFFFF;">' + messages[num][1] + '</div>' + endStr;
		tipcss.backgroundColor = curBgColor;
	 	tooltip.innerHTML = tip;
	}
	if (!tipFollowMouse) positionTip(evt);
	else t1=setTimeout("tipcss.visibility='visible'",100);
}

var mouseX, mouseY;
function trackMouse(evt) {
	standardbody=(document.compatMode=="CSS1Compat")? document.documentElement : document.body //create reference to common "body" across doctypes
	mouseX = (ns5)? evt.pageX: window.event.clientX + standardbody.scrollLeft;
	mouseY = (ns5)? evt.pageY: window.event.clientY + standardbody.scrollTop;
	if (tipOn) positionTip(evt);
}

/////////////////////////////////////////////////////////////
//  positionTip function
//		If tipFollowMouse set false, so trackMouse function
//		not being used, get position of mouseover event.
//		Calculations use mouseover event position,
//		offset amounts and tooltip width to position
//		tooltip within window.
/////////////////////////////////////////////////////////////
function positionTip(evt) {
	if (!tipFollowMouse) {
		mouseX = (ns5)? evt.pageX: window.event.clientX + standardbody.scrollLeft;
		mouseY = (ns5)? evt.pageY: window.event.clientY + standardbody.scrollTop;
	}
	// tooltip width and height
	var tpWd = (ie4||ie5)? tooltip.clientWidth: tooltip.offsetWidth;
	var tpHt = (ie4||ie5)? tooltip.clientHeight: tooltip.offsetHeight;
	// document area in view (subtract scrollbar width for ns)
	var winWd = (ns5)? window.innerWidth-20+window.pageXOffset: standardbody.clientWidth+standardbody.scrollLeft;
	var winHt = (ns5)? window.innerHeight-20+window.pageYOffset: standardbody.clientHeight+standardbody.scrollTop;
	// check mouse position against tip and window dimensions
	// and position the tooltip
	if ((mouseX+offX+tpWd)>winWd)
		tipcss.left = mouseX-(tpWd+offX)+"px";
	else tipcss.left = mouseX+offX+"px";
	if ((mouseY+offY+tpHt)>winHt)
		tipcss.top = winHt-(tpHt+offY)+"px";
	else tipcss.top = mouseY+offY+"px";
	if (!tipFollowMouse) t1=setTimeout("tipcss.visibility='visible'",100);
}

function hideTip() {
	if (!tooltip) return;
	t2=setTimeout("tipcss.visibility='hidden'",100);
	tipOn = false;
}

document.write('<div id="tipDiv" style="position:absolute; visibility:hidden; z-index:100"></div>')

</script>