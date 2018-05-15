<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = 'admin/memberzones'"), 0)) {
    error_forbidden();
}

if (!$memberzoneId = validateMemberzoneId($_GET['memberzone'])) {
    abort($saveMemberzone_error, __FUNCTION__, __FILE__, __LINE__);
}

if ($_GET['action'] == 'unassociate') {
    if ($_GET['users'] && ($users = @explode(',', $_GET['users']))) {
        foreach ($users as $usersId) {
            mysql_query("DELETE FROM type_memberzone__users WHERE memberzone_id = '$memberzoneId' AND user_id = '$usersId'");
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('?doc=' . constant('DOC') . '&memberzone=' . $memberzoneId)));
    exit;
}

echo renderMemberzoneUsersList($memberzoneId, $errors);


// Functions.

function validateMemberzoneId($memberzoneId) {
    return @mysql_result(mysql_query("SELECT memberzone_id FROM type_memberzone WHERE memberzone_id = '$memberzoneId'"), 0);
}

function renderMemberzoneUsersList($memberzoneId, $errors = false) {
    $sql_query = mysql_query("SELECT tu.user_id, TRIM(CONCAT(tu.first_name, ' ', tu.last_name)) AS name, tu.email FROM type_memberzone__users tm_u, type_user tu WHERE tm_u.memberzone_id = '$memberzoneId' AND tm_u.user_id = tu.user_id ORDER BY user_id DESC");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $list[] = $sql;
    }
    
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Memberzones / Users</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/boxover.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/boxover.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        function getMarkedItems(arrayName) {
            var items = document.getElementsByName(arrayName);
            var items_array = new Array();
            for (var i = 0, j = 0; i != items.length; i++) {
                if (items[i].checked) {
                    items_array[j] = items[i].value;
                    j++;
                }
            }
            
            return items_array;
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    
    <form method="POST" id="adminList" class="dialog">
    <input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
    <table>
        <thead>
            <? if ($errors): ?>
            <tr>
                <td>
                    <div style="font-weight: bold; color: #FF0000;">Errors:</div>
                    <? foreach ($errors as $error): ?>
                    <div style="margin-top: 8px; margin-left: 15px;"><?=$error?></div>
                    <? endforeach; ?>
                </td>
            </tr>
            <? endif; ?>
            
            <tr>
                <td>
                    <input type="button" value="Unasso. Marked" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to unassociate all selected items from the memberzone?')) window.location = '<?=href('/?doc=' . constant('DOC'))?>&memberzone=<?=$memberzoneId?>&users=' + getMarkedItems('items[]') + '&action=unassociate'">
                </td>
            </tr>
        </thead>
        
        <tbody>
            <tr>
                <td>
                    <table>
                        <tr>
                            <th>&nbsp;</th>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                        
                        <?
                        for ($i = 0; $item = $list[$i]; $i++):
                            $firstFlag = !$i ? 'first' : false;
                        ?>
                            <tr class="list <?=$firstFlag?>">
                                <td class="first check"><input type="checkbox" name="items[]" value="<?=$item['user_id']?>" style="margin: 0;"></td>
                                <td class="doc-id"><?=$item['user_id']?></td>
                                <td style="width: 50%"><?=$item['name']?></td>
                                <td style="width: 50%"><?=$item['email']?></td>
                            </tr>
                        <? endfor; ?>
                    </table>
                </td>
            </tr>
        </tbody>
        
        <tfoot>
            <tr>
                <td>
                    <input type="button" value="Unasso. Marked" class="button" style="width: 100px;" onclick="if (confirm('Are you sure you want to unassociate all selected items from the memberzone?')) window.location = '<?=href('/?doc=' . constant('DOC'))?>&memberzone=<?=$memberzoneId?>&users=' + getMarkedItems('items[]') + '&action=unassociate'">
                </td>
            </tr>
        </tfoot>
    </table>
    </form>
    
    </div>
    
    </body>
    </html>
    <?
    $stdout = ob_get_contents();
    ob_end_clean();
    
    return $stdout;
}

?>