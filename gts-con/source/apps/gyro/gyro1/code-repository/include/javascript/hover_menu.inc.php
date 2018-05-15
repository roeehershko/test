<script type="text/javascript">
    function hover_menu(element,action) {
        hover_group = new Array();
        <?
        $group_type = array('document','category','license','member-zone','article','exam','special-category','menubar');
        
        $j = 0;
        foreach($group_type as $mother_group) {
            for ($i = 0 ; $i != count($groups[$mother_group]['groups']); ++$i) {
                $current_group = $groups[$mother_group]['groups'][$i]['group_id'];
                if ( ($groups[$mother_group]['groups'][$i]['level'] == 0) && ($groups[$mother_group]['groups'][$i]['status'] == 'normal') ) {
                    echo 'hover_group['.$j.'] = \''.$current_group.'\';'."\n";
                    $j++;
                }
            }
        }
        ?>
        if (action == 'show') {
            for (var i = 0; i != hover_group.length; i++) {
                if (hover_group[i] == element) {
                    document.getElementById('hover_group_'+hover_group[i]).style.display='';
                } else {
                    if ( document.getElementById('hover_group_'+hover_group[i]) ) {
                        document.getElementById('hover_group_'+hover_group[i]).style.display='none';
                    }
                }
            }
        } else {
            document.getElementById('hover_group_'+element).style.display='none';
        }
    }
</script>
