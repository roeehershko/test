
/****************** X Showcase Switch X ***************** 
* Switches on-the-fly between images/SWF/FLV/Youtube
* Make sure to have no redundant spaces in the target_div
* e.g. <target_div><content_item></target_div>
********************************************************/


function showcase(target_div_id, item_type, item, item_width, item_height, flv_player_path, flv_skin_path, autoplay, disable_right_click) {

    // Youtube video path
    youtube_video_path = 'http://www.youtube.com/v/';
    
    // Default width x height
    if (!item_width || !item_height) {
        item_width = 400;
        item_height = 336;
    }
    
     // Add a "px" suffix to numeric proportions and an empty suffix to "auto" proportions 
    if (item_width != 'auto') {var width_suffix = 'px';} else {var width_suffix = '';} 
    if (item_height != 'auto') {var height_suffix = 'px';} else {var height_suffix = '';}
    
    // Default is set to NOT autoplay
    if (!autoplay) {
        autoplay = 0;
    }

    // Retrieve the target_div of the show case
    target_div = document.getElementById(target_div_id);
    
    // Clear the current contents of the target_div
    while (item_type != 'href' && item_type != 'tooltip' && target_div.childNodes.length > 0) {
        target_div.removeChild(target_div.firstChild);
    }
    
    // Generate the new content item
    switch (item_type) {
        
        case 'img':
            var content_item = document.createElement('img');
            content_item.setAttribute('src', item);
            content_item.style.width = item_width +	width_suffix;
            content_item.style.height = item_height + height_suffix;
            if (disable_right_click) {
                content_item.oncontextmenu = function() { return false };
            }
            target_div.appendChild(content_item);
            break;
            
        case 'jpg':
            var content_item = document.createElement('img');
            content_item.setAttribute('src', item);
            content_item.style.width = item_width +	width_suffix;
            content_item.style.height = item_height + height_suffix;
            if (disable_right_click) {
                content_item.oncontextmenu = function() { return false };
            }
            target_div.appendChild(content_item);
            break;
            
        case 'gif':
            var content_item = document.createElement('img');
            content_item.setAttribute('src', item);
            content_item.style.width = item_width +	width_suffix;
            content_item.style.height = item_height + height_suffix;
            if (disable_right_click) {
                content_item.oncontextmenu = function() { return false };
            }
            target_div.appendChild(content_item);
            break;
            
        case 'png':
            var content_item = document.createElement('img');
            content_item.setAttribute('src', item);
            content_item.style.width = item_width +	width_suffix;
            content_item.style.height = item_height + height_suffix;
            if (disable_right_click) {
                content_item.oncontextmenu = function() { return false };
            }
            target_div.appendChild(content_item);
            break;
        
        case 'swf':
            var content_item = new SWFObject(item, "showcase_flash", item_width, item_height, "8", "");
            content_item.addParam("quality", "high");
            content_item.addParam("wmode", "transparent");
            content_item.addParam("menu", "false");
            content_item.write(target_div_id);
            break;
            
        case 'flv':
            var content_item = new SWFObject(flv_player_path+'?contentPath='+item+'&skinPath='+flv_skin_path, "showcase_flash", item_width, item_height, "8", "#FFFFFF");
            content_item.addParam("quality", "high");
            content_item.addParam("wmode", "transparent");
            content_item.addParam("menu", "false");
            content_item.write(target_div_id);
            break;
            
        case 'youtube':
            var content_item = new SWFObject(youtube_video_path+item+"&enablejsapi=1&playerapiid=youtube_"+item+"&autoplay="+autoplay+"&fs=1&showsearch=0&rel=0&fmt=22", "youtube_"+item, item_width, item_height, "9", "");
            content_item.addParam("menu", "false");
            content_item.addParam("allowFullScreen", "true");
             // FIX FOR DEBATE.PNC.CO.IL - 2/3/2009 @ 14:51 - Boaz
             // The Talkback popup was partially covered by the Youtube flash
             // ADDING "wmode" with "opaque" setting, seems to resolve the issue
            content_item.addParam("wmode", "opaque");
            content_item.write(target_div_id);
            break;
            
        case 'media_player':
            
            // IMPORTANT NOTE:
            // Firefox requires that the 'src' and 'FileName' have the FULL URL!
            // so it should be: http://www.domain.com/... and not just the relative path to the movie file
            // Correct example - when using Showcase - '<?=$http_url.$group['parameters']['general']['movie_file']?>'
            
            var content_item = '<object width="'+item_width+'" height="'+item_height+'" CLASSID="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" STANDBY="Loading Windows Media Player components..." type="application/x-oleobject">';
	        
	        content_item = content_item + '<param name="FileName" value="'+item+'">';
            content_item = content_item + '<param name="URL" value="'+item+'">';
            
            if (autoplay == '1') {
                content_item = content_item + '<param name="autoStart" value="True">';
            } else {
                content_item = content_item + '<param name="autoStart" value="False">';
            }
            content_item = content_item + '<param name="PlayCount" value="1">';
            content_item = content_item + '<param name="ShowControls" value="True">';
            content_item = content_item + '<param name="UIMode" value="full">';
            
            content_item = content_item + '<embed name="media_player" type="application/x-mplayer2" src="'+item+'" autostart="'+autoplay+'" showcontrols="1" showstatusbar="1" allowchangedisplaysize="1" width="'+item_width+'" height="'+item_height+'" pluginspage="https://www.microsoft.com/Windows/MediaPlayer/"></embed>';
            content_item = content_item + '</object>';
            target_div.innerHTML = content_item;
            break;
            
        case 'txt':
            var content_item = document.createTextNode(item);
            target_div.appendChild(content_item);
            break;
            
        case 'tooltip':
            target_div.setAttribute('title', item);
            break;
            
        case 'href':
            target_div.href = item;
            break;
            
        case 'html':
            target_div.innerHTML = item;
            break;
			
    }
}
