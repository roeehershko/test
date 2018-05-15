<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

if ($_SESSION['user']['type'] == 'content-admin' && !@mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = 'admin/newsletters-log'"), 0)) {
    error_forbidden();
}

if (!$newsletterId = validateNewsletterId($_GET['newsletter'])) {
    abort($saveNewsletter_error, __FUNCTION__, __FILE__, __LINE__);
}

if ($_GET['link']) {
    $sql_query = mysql_query("SELECT name, email, opened FROM type_newsletter__recipients LEFT JOIN type_newsletter__links_recipients USING (newsletter_id, recipient_id) WHERE newsletter_id = '$newsletterId' AND link_id = '" . mysql_real_escape_string($_GET['link']) . "' ORDER BY recipient_id ASC");
} else {
    $sql_query = mysql_query("SELECT name, email, opened FROM type_newsletter__recipients WHERE newsletter_id = '$newsletterId' ORDER BY recipient_id ASC");
}
while ($sql = mysql_fetch_assoc($sql_query)) {
    $phones = getNonEmptyIterations(mysql_fetch_row(mysql_query("SELECT phone_1, phone_2, mobile FROM type_user WHERE email = '" . mysql_real_escape_string($sql['email']) . "'")));
    $sql['phone'] = $phones ? @implode(', ', $phones) : null;
    
    $list[] = $sql;
}

if ($_GET['export']) {
    XMLExport('newsletter-log', array(
        array(
            'db' => 'name',
            'caption' => 'Name',
            'type' => 'variable'
        ),
        array(
            'db' => 'email',
            'caption' => 'Email',
            'type' => 'variable'
        ),
        array(
            'db' => 'phone',
            'caption' => 'Phone',
            'type' => 'variable'
        ),
        array(
            'db' => 'opened',
            'caption' => 'Opened',
            'type' => 'variable'
        )
    ), $list);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Admin. / Newsletters / Recipients</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <link href="/repository/admin/admin.css?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.css')?>" type="text/css" rel="stylesheet">
    <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
    <script src="/repository/admin/admin-list.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin-list.js')?>" type="text/javascript"></script>
    <script src="/repository/scriptaculous/prototype.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/scriptaculous/prototype.js')?>" type="text/javascript"></script>
    <script src="/repository/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop,controls,extensions" type="text/javascript"></script>
</head>

<body>

<div align="center">

<form method="POST" id="adminList" class="dialog">
<input type="hidden" name="referer" value="<?=$_FORM['referer']?>">
<table>
    <thead>
        <tr>
            <td>
                <select style="margin-bottom: 10px; width: 100%; font-family: Courier New;" onchange="location = '<?=href(constant('DOC') . '/?newsletter=' . $newsletterId)?>&link=' + this.value">
                    <option value="">&nbsp;</option>
                    <?
                    $sql_query = mysql_query("SELECT link_id, url, (SELECT COUNT(*) FROM type_newsletter__links_recipients WHERE newsletter_id = '$newsletterId' AND link_id = type_newsletter__links.link_id) AS cnt FROM type_newsletter__links WHERE newsletter_id = '$newsletterId' ORDER BY link_id ASC");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $selected = ($_GET['link'] == $sql['link_id']) ? 'selected' : false;
                        echo '<option value="' . $sql['link_id'] . '" ' . $selected . '>[' . $sql['cnt'] . '] ' . $sql['url'] . '</option>' . "\n";
                    }
                    ?>
                </select>
                
                <input type="button" value="Export XML" style="float: right;" onclick="window.location = window.location.href + '&export=1'">
            </td>
        </tr>
    </thead>
    
    <tbody>
        <tr>
            <td>
                <table>
                    <tr>
                        <th style="width: 25%;">Name</th>
                        <th style="width: 50%;">Email</th>
                        <th style="width: 25%;">Phone</th>
                        <th>Opened</th>
                    </tr>
                    
                    <?
                    for ($i = 0; $item = $list[$i]; $i++):
                        $firstFlag = !$i ? 'first' : false;
                    ?>
                        <tr class="list <?=$firstFlag?>">
                            <td class="first" style="white-space: nowrap;"><?=($item['name'] ? $item['name'] : '<div style="text-align: center;">-</div>')?></td>
                            <td><?=$item['email']?></td>
                            <td style="white-space: nowrap;"><?=($item['phone'] ? $item['phone'] : '<div style="text-align: center;">-</div>')?></td>
                            <td style="white-space: nowrap; text-align: center;"><?=($item['opened'] ? $item['opened'] : '-')?></td>
                        </tr>
                    <? endfor; ?>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</form>

</div>

</body>
</html>

<?

// Functions.

function validateNewsletterId($newsletterId) {
    return @mysql_result(mysql_query("SELECT newsletter_id FROM type_newsletter WHERE newsletter_id = '$newsletterId'"), 0);
}

?>