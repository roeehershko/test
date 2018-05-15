<?php

	$group_object = new Group($doc_id);
	
	if ($doc == 'order-form') {

		include 'order-form.inc.php';

	} else if ($doc_id == '161894' || $doc_id == '162318') {
		?>
		<div class="box showcase">
			<a class="paragon_logo"><img src="<?=href('images/global/logo.png')?>" alt="<?=htmlspecialchars(constant('SITE_TITLE'))?>"></a>
			<div class="title"></div>
			<div class="sub_title"></div>
			<div class="overlay">
				<div class="short_description"></div>
			</div>
			<img class="image" src="<?=href('images/global/spacer.gif')?>">
			<div class="navigator">
				<?
				for ($i = 0; $i != count($group_object->showcase); $i++) {
				 ?>
				 <span onclick="showcase_clicked = 1; toggle_showcase(showcase_items_data[<?=$i?>], 'showcase', <?=$i?>)"></span>
				 <?
				}
				?>
			</div>
		</div>
		
		<script type="text/javascript">
				
			var jcarousel_clicked = 0;
			var showcase_clicked = 0;
			
			function toggle_showcase(data, type, index) {
				
				if (type == 'showcase') {
					next_showcase_index = index + 1;
					if (next_showcase_index > (showcase_count-1)) {
						next_showcase_index = 0;
					}
					prev_showcase_index = index - 1;
					if (prev_showcase_index < 0) {
						prev_showcase_index = showcase_count - 1;
					}
				}
				
				if (type == 'jcarousel') {
					jcarousel_clicked = 1;
					$('.box.showcase .paragon_logo').fadeOut(400);
				} else {
					$('.box.showcase .paragon_logo').fadeIn(400);
				}
				$('.box.showcase .title').hide('slide', {direction:'right'}, 400);
				$('.box.showcase .sub_title').hide('slide', {direction:'left'}, 400);
				$('.box.showcase .short_description').hide('slide', {direction:'down'}, 400, function () {
					if (type == 'showcase') {
						$('.box.showcase .overlay').fadeOut(400);
					}
				});
				$('.box.showcase .image').fadeOut(400, function() {
					if (data.large_image) {
						$('.box.showcase .image').attr('src', href_url+data.large_image).fadeIn(800);
					}
					if (data.description && type == 'jcarousel') {
						$('.box.showcase .overlay').fadeIn(800, function () {
							$('.box.showcase .title').addClass('small').text(data.title).show('slide', {direction:'right'}, 800);
							$('.box.showcase .sub_title').addClass('small').text(data.subtitle).show('slide', {direction:'left'}, 800);
							$('.box.showcase .short_description').html(data.description).show('slide', {direction:'down'}, 800);
						});
					} else {
						$('.box.showcase .title').removeClass('small').text(data.title).show('slide', {direction:'right'}, 800);
						$('.box.showcase .sub_title').removeClass('small').text(data.subtitle).show('slide', {direction:'left'}, 800);
					}
				});
				if (data.link) {
					$('.box.showcase').css({cursor:'pointer'});
					$('.box.showcase').bind('click', function () {
						window.location = data.link;
					});
				} else {
					$('.box.showcase').css({cursor:'default'});
					$('.box.showcase').unbind();
				}
				
				$('.box.showcase .navigator span').html(' &#9679; ');
				if (type == 'showcase') {
					$('.box.showcase .navigator span:nth-child('+(index+1)+')').html(' &#9675; ');
				}
			}
			
			var showcase_items_data = [];
			<?
			$count = 0;
			if (!empty($group_object->showcase)) {
				$items = $group_object->showcase;
				foreach ($items as $item) {
					?>
					showcase_items_data[<?=$count?>] = <?=json_encode($item)?>;
					<?
					if ($item['large_image']) {
						// Preloading the images
						?>
						(new Image()).src = '<?=href($item['large_image'])?>';
						<?
					}
					$count++;
				}
			}
			?>
			var showcase_index = 0;
			var showcase_count = <?=$group_object->showcase ? count($group_object->showcase) : '0' ?>;
			var next_showcase_index = 1;
			var prev_showcase_index = showcase_count-1;
			
			var jcarousel_items = [];
			var jcarousel_items_data = [];
			<?
			$count = 0;
			if (!empty($group_object->slider)) {
				$items = $group_object->slider;
				//shuffle($items);
				foreach ($items as $item) {
					if ($item['image'] && $item['large_image']) {
						$item['description'] = addslashes(str_replace(array("\n", "\r"), array("", ""), nl2br(htmlspecialchars($item['description']))));
						$media = '';
						//$media .= '<div title="'.htmlspecialchars($item['title']).'" onclick="window.location=\''.href($item['link']).'\'">';
						$media .= '<div title="'.htmlspecialchars($item['title']).'" onclick="toggle_showcase(jcarousel_items_data['.$count.'], \'jcarousel\', '.$count.')">';
						$media .=	'<h2>'.$item['title'].'</h2>';
						$media .=	'<h3>'.$item['subtitle'].'</h3>';
						$media .= 	'<img src="'.href($item['image']).'">';
						$media .= '</div>';
						?>
						jcarousel_items[<?=$count?>] = '<?=addslashes(str_replace(array("\n", "\r"), array("", ""), $media))?>';
						jcarousel_items_data[<?=$count?>] = <?=json_encode($item)?>;
						<?
						if ($item['large_image']) {
							// Preloading the images
							?>
							(new Image()).src = '<?=href($item['large_image'])?>';
							<?
						}
						$count++;
					}
				}
			}
			?>
			$(document).ready(function() {
				
				setInterval(function() {
					if (showcase_clicked != 1 && jcarousel_clicked != 1) {
						toggle_showcase(showcase_items_data[showcase_index], 'showcase', showcase_index);
						if (showcase_index >= showcase_count - 1) {
							showcase_index = 0;
						} else {
							showcase_index++;
						}
					}
				}, 4000);
				
				setTimeout(function() {
					// We need this timeout since we must wait for the fadeIn element to finish and the .content to appear.
					<?
					if (constant('MOBILE') || $_GET['iscroll']) {
						?>
						//$('.jcarousel-prev.jcarousel-prev-horizontal').hide();
						//$('.jcarousel-next.jcarousel-next-horizontal').hide();
						
						new iScroll('iscroll', {hScrollbar:false, hScroll:true, vScrollbar:false, vScroll:false});
						
						$('.showcase').wipetouch({
							wipeLeft: function() {
								showcase_clicked = 1;
								toggle_showcase(showcase_items_data[next_showcase_index], 'showcase', next_showcase_index);
							},
							wipeRight: function() {
								showcase_clicked = 1;
								toggle_showcase(showcase_items_data[prev_showcase_index], 'showcase', prev_showcase_index);
							},
						});
						<?
					} else {
						?>
						$('#jcarousel').jcarousel({scroll:4, wrap:'circular', animation:1800, easing:'swing', rtl:false,
							/*initCallback:function(carousel) {
								$('.slider').wipetouch({
									wipeLeft: function() {
										carousel.next();
									},
									wipeRight: function() {
									 	carousel.prev();
									},
								});
							},*/
							itemLoadCallback:{onBeforeAnimation:function (carousel, state) {
								//console.log(carousel);
								var count = 0;
								for (var i = carousel.first; i <= carousel.last; i++) {
									if (Math.abs(i-1) >= jcarousel_items.length) {
										count = Math.abs(i-1) % (jcarousel_items.length > carousel.options.scroll ? jcarousel_items.length : carousel.options.scroll);
									} else {
										count = Math.abs(i-1);
									}
									// Check if the item already exists
									if (!carousel.has(i) && jcarousel_items[count]) {
										// Add the item
										carousel.add(i, jcarousel_items[count]);
									}
								}
							}}});
							$('.jcarousel-prev.jcarousel-prev-horizontal').html('&lsaquo;');
							$('.jcarousel-next.jcarousel-next-horizontal').html('&rsaquo;');
						<?
					}
					?>
					
				}, 3500);									
			});
		</script>
		<div class="slider">
			<?
			if (constant('MOBILE') || $_GET['iscroll']) {
				?>
				<div class="jcarousel-container jcarousel-container-horizontal" style="position: relative; display: block; ">
					<div id="iscroll" class="jcarousel-clip jcarousel-clip-horizontal" style="overflow-x:hidden;overflow-y:hidden;position:relative">
						<ul id="jcarousel" class="jcarousel-list jcarousel-list-horizontal">
							<?
							$count = 0;
							if (!empty($group_object->slider)) {
								$items = $group_object->slider;
								//shuffle($items);
								foreach ($items as $item) {
									if ($item['image'] && $item['large_image']) {
										$item['description'] = addslashes(str_replace(array("\n", "\r"), array("", ""), nl2br(htmlspecialchars($item['description']))));
										$media = '';
										//$media .= '<div title="'.htmlspecialchars($item['title']).'" onclick="window.location=\''.href($item['link']).'\'">';
										$media .= '<li class="jcarousel-item jcarousel-item-horizontal" style="float:left"><div title="'.htmlspecialchars($item['title']).'" onclick="toggle_showcase(jcarousel_items_data['.$count.'], \'jcarousel\', '.$count.')">';
										$media .=	'<h2>'.$item['title'].'</h2>';
										$media .=	'<h3>'.$item['subtitle'].'</h3>';
										$media .= 	'<img src="'.href($item['image']).'">';
										$media .= '</div></li>';
										echo $media;
										$count++;
									}
								}
							}
							?>
						</ul>
					</div>
					<div class="jcarousel-prev jcarousel-prev-horizontal">&lsaquo;</div>
					<div class="jcarousel-next jcarousel-next-horizontal">&rsaquo;</div>
				</div>
				<script type="text/javascript">
					$(document).ready(function() {
						$('#jcarousel').css({width:'<?=$count*245?>px'});
					});
				</script>
				<?
			} else {
				?>
				<ul id="jcarousel"></ul>
				<?
			}
			?>
		</div>
		<div class="box flex">
			<h1><?=$group_object->title?></h1>
			<div class="description"><?=$group_object->description?></div>
		</div>
		<?
	
	 // Support statistics
    } elseif ($group_object->is_stats_group){
		$parentPath = 'templates/'.$group_object->doc_name.'.inc.php';
		$childPath = 'templates/support-stats.'.$group_object->stats_group_type.'.inc.php';
        $stats_template_file = ($group_object->stats_group_type ? $childPath : $parentPath );
        include($stats_template_file);
            
	} else {
		if ($group_object->header_image) {
			list($image_width, $image_height, $image_type, $image_attr) = getimagesize($group_object->header_image);
			?>
			<div class="box high" style="height:<?=$image_height?>px">
				<version><?=constant('VERSION')?></version>
				<img class="image" style="height:<?=$image_height?>px" src="<?=href($group_object->header_image)?>">
			</div>
			<?	
		} else {
			?>
			<div class="box high">
				<version><?=constant('VERSION')?></version>
				<a class="paragon_logo"><img src="<?=href('images/global/logo.png')?>" alt="<?=htmlspecialchars(constant('SITE_TITLE'))?>"></a>
				<img class="image" src="<?=href('images/global/wave_205.jpg')?>">
			</div>
			<?
		}
		?>
		<div class="box flex<?=$group_object->layout ? ' '.$group_object->layout : false?>">
			<h1><?=$group_object->title?></h1>
			<div class="description"><?=$group_object->description?></div>
			<?
			if (!empty($group_object->items)) {
				?>
				<div class="items">
					<?
					$count = 0;
					foreach ($group_object->items as $item) {
						?>
						<div id="item_<?=$count?>" class="item">
							<?
							if ($item['title']) {
								?>
								<h2><?=$item['title']?></h2>
								<?
							}
							if ($item['sub_title']) {
								?>
								<h3><?=$item['sub_title']?></h3>
								<?
							}
							//echo $_SERVER['HTTP_USER_AGENT'];
							if (($item['image'] && !$item['iframe']) || (preg_match('/MSIE [2-9]{1}\./i', $_SERVER['HTTP_USER_AGENT']) && $item['image'])) {
								?>
								<div class="media image"><img src="<?=href($item['image'])?>"></div>
								<?
							} else if ($item['iframe']) {
								$iframe_size = false;
								if ($item['iframe_size']) {
									$iframe_size = explode('x', $item['iframe_size']);
									if ($iframe_size[0] && $iframe_size[1]) {
										$iframe_size = ' style="width:'.$iframe_size[0].'px;height:'.$iframe_size[1].'px"';
									}
								}
								?>
								<script>
									$(document).ready(function() {
										setTimeout(function() {
											$('#item_<?=$count?> .media.iframe').html('<iframe type="text/html"<?=$iframe_size?> src="<?=$item['iframe']?>" seamless allowfullscreen frameborder="0"></iframe>');
										}, 1000);
									});
								</script>
								<div class="media iframe"></div>
								<?
							}
							if ($item['youtube_code']) {
								?>
								<div class="media youtube"><iframe type="text/html" src="http://www.youtube.com/embed/<?=$item['youtube_code']?>" seamless allowfullscreen frameborder="0"></iframe></div>
								<?
							}
							if ($item['description'] && !$_POST) {
								?>
								<div class="description">
									<?=$item['description']?>
								</div>
								<?
							}
							?>
						</div>
						<?
						$count++;
					}
					?>
				</div>
				<?
			}
			?>
		</div>
		<?
	}
	
?>
