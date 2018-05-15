<? 
// Footerbar - of Level 0 only.
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
			$group_type = array('document','category','license','member-zone','article','exam','special-category');
			
			foreach($group_type as $mother_group) {
				if ($product_groups) {
					$product_parent = $product_groups[$mother_group][0]['id'];
				}
				
				$doc_parent = $groups[$mother_group]['groups_assoc'][$doc_id]['parent_group_id'];
				$doc_grandpa = $groups[$mother_group]['groups_assoc'][$doc_parent]['parent_group_id'];
						
				$product_grandpa = $groups[$mother_group]['groups_assoc'][$product_parent]['parent_group_id'];
				$product_grand_grandpa = $groups[$mother_group]['groups_assoc'][$product_grandpa]['parent_group_id'];
					
				for ($i = 0; $i != count($groups[$mother_group]['groups']); $i++):
					if (($groups[$mother_group]['groups'][$i]['status'] != 'unavailable') 
						&& ($groups[$mother_group]['groups'][$i]['status'] != 'hidden')
						):
						
						if( $groups[$mother_group]['groups'][$i]['level'] == '0' ):
							
							// parameters:
							$current_doc = $groups[$mother_group]['groups'][$i]['doc'];
							$current_group = $groups[$mother_group]['groups'][$i]['group_id'];
							$current_parent = $groups[$mother_group]['groups'][$i]['parent_group_id'];
							$current_granpa = $groups[$mother_group]['groups_assoc'][$current_parent]['parent_group_id'];
																
							// Checking if a Button Title parameter exists
							$parameters = getDocParameters($current_group);
							if ($parameters['button_title']) {
								$button_title =	$parameters['button_title'];
							} else {
								$button_title =	$groups[$mother_group]['groups'][$i]['title'];						
							}
							if ($parameters['alt_link']) {
								$button_link =	$parameters['alt_link'];
							} else {
								$button_link =	$current_doc;						
							}
							
							if 	(
								   ( $current_group == $doc_id )
								|| ( $current_group == $doc_parent )
								|| ( $current_group == $doc_grandpa )
								
								|| ( $current_group == $product_parent )
								|| ( $current_group == $product_grandpa )
								|| ( $current_group == $product_grand_grandpa )
							):
								
							echo('<td><a href="'.href($button_link).'" class="selected"><span>'.$button_title.'</span></a></td>');
								
							else:
								
							echo('<td><a href="'.href($button_link).'"><span>'.$button_title.'</span></a></td>');
								
							endif;
						
						endif;
					endif;
				endfor;	
			}
			?>
		</tr>
	</table>	
</div>
