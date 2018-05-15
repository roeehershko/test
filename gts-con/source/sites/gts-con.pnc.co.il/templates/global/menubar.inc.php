<?php 
// Menubar - Groups of Level 0 only.
if ($doc_type == 'product'):
	$product_groups = getProductGroups($doc_id);
elseif ($doc_type == 'article'):
	$product_groups = getArticleGroups($doc_id);
endif;
?>

                            <div class="categories">
                            	<table style="width: 100%; height: 100%;" cellpadding="0" cellspacing="0">
                                    <tr>
<?
			$group_type = array('category');
			
			foreach($group_type as $mother_group) {
				if ($product_groups) {
					$product_parent = $product_groups[$mother_group][0]['id'];
				}
				
				$doc_parent = $groups[$mother_group]['groups_assoc'][$doc_id]['parent_group_id'];
				$doc_grandpa = $groups[$mother_group]['groups_assoc'][$doc_parent]['parent_group_id'];
						
				$product_grandpa = $groups[$mother_group]['groups_assoc'][$product_parent]['parent_group_id'];
				$product_grand_grandpa = $groups[$mother_group]['groups_assoc'][$product_grandpa]['parent_group_id'];
					
				for ($i = 0; $i != count($groups[$mother_group]['groups']); $i++):
					if ($groups[$mother_group]['groups'][$i]['status'] == 'normal' || (($user['type'] == 'content-admin' || $user['type'] == 'tech-admin') && $groups[$mother_group]['groups'][$i]['status'] == 'unavailable')):
						
						if ( $groups[$mother_group]['groups'][$i]['level'] == '0' ):
							
							// parameters:
							$current_group = false;
							$current_group['id'] = $groups[$mother_group]['groups'][$i]['id'];
							$current_group['doc'] = $groups[$mother_group]['groups'][$i]['doc'];
							$current_group['parent'] = $groups[$mother_group]['groups'][$i]['parent_group_id'];
							$current_group['grandpa'] = $groups[$mother_group]['groups_assoc'][$current_group['parent']]['parent_group_id'];
							
							$current_group['parameters'] = getDocParameters($current_group['id']);									
							
							// Checking if a Button Title parameter exists
							if ($current_group['parameters']['button_title']) {
								$button_title = $current_group['parameters']['button_title'];
							} else {
								$button_title = $groups[$mother_group]['groups'][$i]['title'];						
							}
							
							// Checking if a Alternative Link parameter exists
							if ($current_group['parameters']['alt_link']) {
								$button_link = href($current_group['parameters']['alt_link']);
								$current_group['doc'] = $current_group['parameters']['alt_link'];
							} else {
								$button_link = href($current_group['doc']);
							}
							
							if 	(
								   ( $current_group['doc'] == $doc )
								|| ( $current_group['id'] == $doc_id )
								|| ( $current_group['id'] == $doc_parent )
								|| ( $current_group['id'] == $doc_grandpa )
								
								|| ( $current_group['id'] == $product_parent )
								|| ( $current_group['id'] == $product_grandpa )
								|| ( $current_group['id'] == $product_grand_grandpa )
							):
								
								// Selected Group
								
								echo "\t\t\t\t\t" . '<td>';
								echo '<a href="'.$button_link.'" class="selected">'.$button_title.'</a>';
								echo '</td>' . "\n";
								
							else:
								
								// Non Selected Group
								$mouseover = $current_group['id'] != $last_menubar_category ? '' : '$(\'#plain_corner\').toggle(); $(\'#grad_corner\').toggle();';
								$mouseout  = $current_group['id'] != $last_menubar_category ? '' : '$(\'#plain_corner\').toggle(); $(\'#grad_corner\').toggle();';
							
                            	echo "\t\t\t\t\t" . '<td>';
								echo '<a href="'.$button_link.'" onmouseover="' . $mouseover . '" onmouseout="' . $mouseout . '">'.$button_title.'</a>';								
								echo '</td>' . "\n";	
							
							endif;
						
						endif;
					endif;
				endfor;	
			}
?>
                            	   </tr> 
                            	</table>	
                            </div>
