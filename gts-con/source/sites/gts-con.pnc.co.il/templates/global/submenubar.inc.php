<?php
// Submenubar
$categories_exist = 'false';
?>
                    <div class="categories">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
<?
        $group_type = array('category');
                    
        foreach($group_type as $mother_group) {

            // General Parameters for the IFs...
            if ($doc_type == 'product') {
                $tmp = getProductGroups($doc_id);
                for ($i = 0; $i != count($tmp[$mother_group]); $i++) {
                    $product_parents[] = $tmp[$mother_group][$i]['id'];
                }
                $product_parent = $tmp[$mother_group][0]['id'];
            } elseif ($doc_type == 'article') {
                $tmp = getArticleGroups($doc_id);
                for ($i = 0; $i != count($tmp[$mother_group]); $i++) {
                    $product_parents[] = $tmp[$mother_group][$i]['id'];
                }
                $product_parent = $tmp[$mother_group][0]['id'];
            }

            if ($user['id']) {
                $user_member_zones = getUserMemberzones($user['id']);
                /* if ($user['id'] == '150091') {
                    echo '<pre dir="ltr" style="text-align: left; direction: ltr;">'; print_r($user_member_zones); echo '</pre><br><br>';
                    echo '<pre dir="ltr" style="text-align: left; direction: ltr;">'; print_r($groups[$mother_group]); echo '</pre>';
                */
            }

            // Display levels 1,2,... of Group['---'] according to the current parent GROUP
            
            $doc_parent = $groups[$mother_group]['groups_assoc'][$doc_id]['parent_group_id'];
            $doc_grandpa = $groups[$mother_group]['groups_assoc'][$doc_parent]['parent_group_id'];
            
            $product_grandpa = $groups[$mother_group]['groups_assoc'][$product_parent]['parent_group_id'];
            $product_grand_grandpa = $groups[$mother_group]['groups_assoc'][$product_grandpa]['parent_group_id'];
            
            if ($groups[$mother_group]['groups']):
                for ($i = 0; $i != count($groups[$mother_group]['groups']); $i++):
                    if ($groups[$mother_group]['groups'][$i]['status'] == 'normal'):
                        
                        // parameters:
                        $current_group = $groups[$mother_group]['groups'][$i]['id'];
                        $current_group_doc = $groups[$mother_group]['groups'][$i]['doc'];
                        
                        if ($user['id']) {
                            $current_member_zone = $groups[$mother_group]['groups'][$i]['memberzone_id'];
                        }
                        $current_parent = $groups[$mother_group]['groups'][$i]['parent_group_id'];
                        // $current_granpa = $groups[$mother_group]['groups_assoc'][$current_parent]['parent_group_id'];
                                        
                        // Checking if a Button Title parameter exists
                        $parameters = getDocParameters($current_group);
                        
                        if ($parameters['button_title']) {
                            $button_title = $parameters['button_title'];
                        } else {
                            $button_title = $groups[$mother_group]['groups'][$i]['title'];						
                        }
                        if ($parameters['alt_link']) {
                            $button_link = href($parameters['alt_link']);
                            $current_group = $parameters['alt_link'];
                        } else {
                            $button_link = href($current_group_doc);						
                        }
                        
                        $button_style = $parameters['button_style'];
                        
                        // If the user is NOT logged-in, then we display ALL the groups, even if they require Log-in once he clicks them.
                        if (
                            ($user['id'] && $current_member_zone && !empty($user_member_zones) && in_array($current_member_zone, $user_member_zones)) ||
                            (!$user['id']) || (!$user_member_zones)
                        ):
                        
                            // Checking whether the current group belongs to the current doc-group-tree.
                            if (
                                   ( $current_group == $doc )
                                || ( $current_group == $doc_id )
                                || ( $current_group == $doc_parent )
                                || (is_array($product_parents) && in_array($current_group, $product_parents))
                                || ( $current_group == $product_grandpa )
                                                        
                                || ( $current_parent == $doc_id )
                                || ( $current_parent == $doc_parent )
                                || ( $current_parent == $doc_grandpa )
                                || (is_array($product_parents) && in_array($current_parent, $product_parents))
                                || ( $current_parent == $product_grandpa )
                                || ( $current_parent == $product_grand_grandpa )
                            ):
                                
                                if ($groups[$mother_group]['groups'][$i]['level'] == '1'):
                                    
                                    $sub_categories_exist = false;
                                    if ($groups[$mother_group]['groups'][$i+1]['id'] && $groups[$mother_group]['groups'][$i+1]['level'] == 2 && $groups[$mother_group]['groups'][$i+1]['parent_group_id'] == $current_group) {
                                        $sub_categories_exist = true;
                                    }
                                    
                                    if 	( 
                                           ($current_group == $doc)
                                        || ($current_group == $doc_id)
                                        || ($current_group == $doc_parent) 
                                        || (is_array($product_parents) && in_array($current_group, $product_parents)) 
                                    ):
                                        echo "\t\t\t\t" . '<td><a href="'.$button_link.'" class="selected '.$button_style.'">'.$button_title.'</a></td>' . "\n";
                                        $categories_exist = 'true';
                                    else:
                                        echo "\t\t\t\t" . '<td><a href="'.$button_link.'" class="'.$button_style.'">'.$button_title.'</a></td>' . "\n";
                                        $categories_exist = 'true';
                                    endif;
                                endif;
                            endif;
                        endif;
                    endif;
                endfor;	
            endif;
        }
?>
                            </tr>
                        </table>
                    </div>
