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

if ($_POST['action'] == 'save') {
    recursive('trim', $_POST);
    recursive('stripslashes', $_POST);
    
    if ($_POST['products']) {
        foreach ($_POST['products'] as $productId => $priceZone) {
            if (preg_match('/^\d{1,6}(\.\d{1,2})?$/', $priceZone)) {
                mysql_query("REPLACE INTO type_memberzone__products (memberzone_id, product_id, price_zone) VALUES ('$memberzoneId', '$productId', '$priceZone')");
            } else {
                mysql_query("DELETE FROM type_memberzone__products WHERE memberzone_id = '$memberzoneId' AND product_id = '$productId'");
            }
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : href('/?doc=' . constant('DOC') . '&memberzone=' . $memberzoneId)));
    exit;
}

echo renderMemberzoneProductsList($memberzoneId, $errors);


// Functions.

function validateMemberzoneId($memberzoneId) {
    return @mysql_result(mysql_query("SELECT memberzone_id FROM type_memberzone WHERE memberzone_id = '$memberzoneId'"), 0);
}

function renderMemberzoneProductsList($memberzoneId, $errors = false) {
    ob_start();
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    
    <html>
    <head>
        <title>Admin. / Memberzones / Products</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
        <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
        <script src="/repository/admin/boxover.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/boxover.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
        <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
        
        <script type="text/javascript">
        function validateZonePrice(element) {
            if (element.value.length > 0) {
                if (!/^\d{1,6}(\.\d{1,2})?$/.test(element.value)) {
                    alert('Invalid Zone Price');
                    element.focus();
                    element.select();
                }
            }
        }
        </script>
    </head>
    
    <body>
    
    <div align="center">
    
    <form method="POST" id="adminList" class="dialog">
    <input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
    <input type="hidden" name="action" value="save">
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
                    <input type="submit" value="Save" accesskey="s" class="button" style="width: 100px;">
                </td>
            </tr>
        </thead>
        
        <tbody>
            <tr>
                <td>
                    <table>
                        <tr>
                            <td colspan="6" style="font-style: italic;">
                                If set, the 'zone price' will replace the 'actual price' of the product (much like the 'actual price' replaces the 'retail price').
                                <br>
                                Products that belong to several groups are shown only under the first group to appear on the list.
                                <br>
                                The final product price factors-in selected attributes, matching-product discounts, etc.
                            </td>
                        </tr>
                        
                        <?
                        if ($groupId = @mysql_result(mysql_query("SELECT group_id FROM type_memberzone WHERE memberzone_id = '$memberzoneId'"), 0)) {
                            if ($groups_tmp = getGroups_byParent($groupId)) {
                                foreach ($groups_tmp as $group_tmp) {
                                    $groups[] = $group_tmp['group_id'];
                                }
                            }
                            $groups[] = $groupId;
                        }
                        
                        $flag = 1;
                        for ($i = 0; $i != count($groups); $i++) {
                            $groupTitle = @mysql_result(mysql_query("SELECT title FROM type_group WHERE group_id = '" . $groups[$i] . "'"), 0);
                            
                            $style = ($flag++) ? 'padding-top: 15px;' : false;
                            
                            echo '<tr>' . "\n";
                            echo '    <td colspan="6" style="padding-left: 10px; font-weight: bold; ' . $style . '">' . $groupTitle . '</td>' . "\n";
                            echo '</tr>' . "\n";
                            
                            ?>
                            <tr>
                                <th>Id</th>
                                <th>Title</th>
                                <th title="Catalog Number">Catalog No.</th>
                                <th title="Retail Price">Retail</th>
                                <th title="Actual Price">Actual</th>
                                <th title="Zone Price">&nbsp;Zone Price&nbsp;</th>
                            </tr>
                            <?
                            
                            $cnt = 0;
                            $sql_query = mysql_query("SELECT tp.product_id, tp.title, tp.catalog_number, tp.price_retail, tp.price_actual, tg_ad.group_id FROM type_group__associated_docs tg_ad, type_product tp WHERE tg_ad.group_id = '" . $groups[$i] . "' AND tg_ad.associated_doc_id = tp.product_id ORDER BY product_id ASC");
                            while ($sql = mysql_fetch_assoc($sql_query)) {
                                if (@in_array($sql['product_id'], $products)) {
                                    continue;
                                } else {
                                    $products[] = $sql['product_id'];
                                }
                                
                                $firstFlag = !($cnt++) ? 'first' : false;
                                
                                $priceZone = @mysql_result(mysql_query("SELECT price_zone FROM type_memberzone__products WHERE memberzone_id = '$memberzoneId' AND product_id = '$sql[product_id]'"), 0);
                                ?>
                                <tr class="list <?=$firstFlag?>">
                                    <td class="first doc-id"><?=$sql['product_id']?></td>
                                    <td style="width: 100%;"><?=$sql['title']?></td>
                                    <td><?=$sql['catalog_number']?></td>
                                    <td style="text-align: center;"><?=coin($sql['price_retail'])?></td>
                                    <td style="text-align: center;"><?=coin($sql['price_actual'])?></td>
                                    <td><input type="text" name="products[<?=$sql['product_id']?>]" maxlength="10" value="<?=$priceZone?>" class="text" style="width: 75px; border: 0; text-align: center; color: #FF0000;" onblur="validateZonePrice(this)"></td>
                                </tr>
                                <?
                            }
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </tbody>
        
        <tfoot>
            <tr>
                <td>
                    <input type="submit" value="Save" accesskey="s" class="button" style="width: 100px;">
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