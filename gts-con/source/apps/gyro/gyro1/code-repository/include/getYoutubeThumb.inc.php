<?php

 // Youtube Thumb retrieval
function getYoutubeThumb($youtube_id) {
    return 'http://i.ytimg.com/vi/' . $youtube_id . '/default.jpg';
}

/*****************************************************************************************************************
 * USING THE YOUTUBE API INSTEAD
 *
 * In the future we may want to do this more elegantly, or this may break altogether :)
 * So we can instead use the Youtube API directly .
 *  
 * Given a dev_id and a video_id we get an XML feed to parse, with all the movie's details:
        http://www.youtube.com/api2_rest?method=youtube.videos.get_details&dev_id=dev_id&video_id=video_id
 * For example with my dev_id :
        http://www.youtube.com/api2_rest?method=youtube.videos.get_details&dev_id=r7kuyA1NuFQ&video_id=cfKimrZStA8   
 * In this case we're looking for <thumbnail_url>...</thumbnail_url>
*****************************************************************************************************************/
 
?>
