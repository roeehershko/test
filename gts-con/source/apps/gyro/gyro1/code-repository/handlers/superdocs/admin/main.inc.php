<?php

if (!$_SERVER['HTTPS']) {
    echo 'HTTP connections disallowed.';
    exit;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Administration</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
    <script src="/repository/admin/admin.js?<?=filemtime(constant('GYRO_LOCATION') . 'web-repository/admin/admin.js')?>" type="text/javascript"></script>
    
    <style type="text/css">
    html, body {
        padding: 0;
        margin: 0;
        background-color: #FFFFFF;
    }
    
    a {
        font-family: Verdana;
        font-size: 11px;
        color: #333333;
        text-decoration: none;
    }
    a:hover {
        color: #6699CC;
        text-decoration: underline;
    }
    
    .admin-header-data {
        width: 100%;
        padding: 16px 15px 0 15px;
        background-image: url(/repository/admin/images/admin-header-bg.gif);
        background-repeat: repeat-x;
        text-align: right;
        vertical-align: top;
        
        font-family: Verdana;
        font-size: 11px;
        color: #333333;
    }
    .admin-header-data .separator {
        margin: 0 3px 0 3px;
        font-size: 13px;
        color: #999999;
    }
    .admin-header-data a:hover {
        color: #6699CC;
        text-decoration: underline;
    }
    
    .admin-box {
        width: 160px;
        height: 184px;
        background-image: url(/repository/admin/images/admin-box.png);
        position: relative;
        text-align: left;
    }
    .admin-box-data {
        position: absolute;
        bottom: 40px;
    }
    .admin-box-data div {
        margin: 8px 15px 0 15px;
    }
    .admin-box-data img.arrow {
        position: relative;
        top: -2px;
        margin-right: 5px;
    }
    </style>
     
</head>

<body>

<?
if ($_SESSION['user']['type'] == 'tech-admin') {
	include 'main.dock.inc.php';	
}
?>

<div align="center" style="padding: 10px;">

<table cellspacing="0" cellpadding="25" style="width: 700px;">
    <tr>
        <td colspan="3">
            <table cellspacing="0" cellpadding="0" style="width: 100%;">
                <tr>
                    <td><img src="/repository/admin/images/admin-header-left.gif"></td>
                    <td class="admin-header-data">
                        <?=$_SESSION['user']['first_name']?> <?=$_SESSION['user']['last_name']?>
                        <span class="separator">|</span>
                        <a href="<?=href('/')?>"><?=constant('SITE_NAME')?></a>
                        <span class="separator">|</span>
                        <a href="<?=href('/?doc=logout')?>">Logout</a>
                    </td>
                    <td><img src="/repository/admin/images/admin-header-right.gif"></td>
                </tr>
            </table>
        </td>
    </tr>
    
    <tr>
        <td align="left">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-groups.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/groups')?>">Groups</a></div>
                    <div><a href="<?=href('/?doc=admin/groups&subtype=blog')?>">Blogs</a></div>
                    <div><a href="<?=href('/?doc=admin/documents')?>">Documents</a></div>
                    <div><a href="<?=href('/?doc=admin/objects')?>">Objects</a></div>
                </div>
            </div>
        </td>
        
        <td align="center">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-products.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/products')?>">Products</a></div>
                    <div><a href="<?=href('/?doc=admin/articles')?>">Articles</a></div>
                    <div><a href="<?=href('/?doc=admin/auctions')?>">Auctions</a></div>
                    <div><a href="<?=href('/?doc=admin/posts')?>">Posts</a></div>
                    <div><a href="<?=href('/?doc=admin/licenses')?>">Licenses</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/activkeys')?>">Activekeys</a></div>
                </div>
            </div>
        </td>
        
        <td align="right">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-polls.gif" style="padding: 10px; position: absolute; top: -35px; right: -45px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/polls')?>">Polls</a></div>
                    <div><a href="<?=href('/?doc=admin/quizzes')?>">Quizzes</a></div>
                    <div><a href="<?=href('/?doc=admin/exams')?>">Exams</a></div>
                </div>
            </div>
        </td>
    </tr>
    
    <tr>
        <td align="left">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-users.gif" style="padding: 10px; position: absolute; top: -35px; right: -45px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/users')?>">Users</a></div>
                    <div><a href="<?=href('/?doc=admin/subscribers')?>">Subscribers</a></div>
                    <div><a href="<?=href('/?doc=admin/memberzones')?>">Memberzones</a></div>
                </div>
            </div>
        </td>
        
        <td align="center">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-newsletters.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/newsletters')?>">Newsletters</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/send-newsletter')?>">Send Newsletter</a></div>
                    <div><a href="<?=href('/?doc=admin/send-sms')?>">Send SMS</a></div>
                </div>
            </div>
        </td>
        
        <td align="right">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-talkbacks.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/talkbacks')?>">Talkbacks</a></div>
                    <div><a href="<?=href('/?doc=admin/reviews')?>">Reviews</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/reviews/feature-sets')?>">Feature Sets</a></div>
                </div>
            </div>
        </td>
    </tr>
    
    <tr>
        <td align="left">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-shipping.gif" style="padding: 10px; position: absolute; top: -35px; right: -45px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/ads')?>">Ads</a></div>
                    <div><a href="<?=href('/?doc=admin/coupons')?>">Coupons</a></div>
                    <div><a href="<?=href('/?doc=admin/shipping-methods')?>">Shipping Methods</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/shipping-methods/zones')?>">Zones</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/shipping-methods/zones-delivery-factors')?>">Delivery Factors</a></div>
                </div>
            </div>
        </td>
        
        <td align="center">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-orders.gif" style="padding: 10px; position: absolute; top: -35px; right: -45px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/orders')?>">Orders</a></div>
                    <div><a href="<?=href('/?doc=admin/credits')?>">Credits Trans.</a></div>
                    <div><a href="<?=href('/?doc=admin/affiliates')?>">Affiliates</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/affiliates/programs')?>">Programs</a></div>
                </div>
            </div>
        </td>
        
        <td align="right">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-statistics.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="http://www.google.com/analytics/" target="_blank">Google Analytics</a></div>
                    <div><a href="http://www.google.com/a/" target="_blank">Google Apps</a></div>
                    <div><a href="http://www.google.com/webmasters/" target="_blank">Google Webmasters</a></div>
                    <div><a href="http://www.google.com/adwords/" target="_blank">Google Adwords</a></div>
                    <div><a href="http://www.google.com/adsense/" target="_blank">Google AdSense</a></div>
                </div>
            </div>
        </td>
    </tr>
    
    <tr>
        <td align="left">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-tickets.gif" style="padding: 10px; position: absolute; top: -35px; right: -40px; z-index: 1;">
                <div class="admin-box-data">
                    <div><a href="<?=href('/?doc=admin/verifone-locations')?>">VeriFone Locations</a></div>
                    <div><a href="<?=href('/?doc=admin/verifone-tickets')?>">VeriFone Tickets</a></div>
                    <div><a href="<?=href('/?doc=admin/verifone-transactions')?>">VeriFone Trans.</a></div>
                    <div><a href="<?=href('/?doc=admin/verifone-isracard-users')?>">Isracard Users</a></div>
                </div>
            </div>
        </td>
        
        <td align="center">
            <div class="admin-box">
                <img src="/repository/admin/images/admin-box-icon-config.gif" style="padding: 10px; position: absolute; top: -35px; right: -40px; z-index: 1;">
                <div class="admin-box-data">
                    <? if ($_SESSION['user']['type'] == 'tech-admin'): ?>
                    <div><a href="<?=href('/?doc=admin/export-static-website')?>">Export to Static</a></div>
                    <div><a href="<?=href('/?doc=admin/doc-types')?>">Doc Types</a></div>
                    <div><img class="arrow" src="/repository/admin/images/admin-nesting-arrow.gif"><a href="<?=href('/?doc=admin/doc-types/parameters')?>">Parameters</a></div>
                    <? endif; ?>
                    <div><a href="<?=href('/?doc=admin/file-manager')?>&noreturn=1" onclick="window.open(this.href, 'gyroFileManager', 'width=910,height=525,resizable=1,left=' + (screen.width ? (screen.width - 910) / 2 : 0) + ',top=' + (screen.height ? (screen.height - 525) / 2.5 : 0)); return false;">Gyro File Manager</a></div>
                    <div><a href="https://gts.pnc.co.il/console/" onclick="window.open(this.href, 'gyroTransactionConsole', 'width=950,height=525,resizable=1,left=' + (screen.width ? (screen.width - 950) / 2 : 0) + ',top=' + (screen.height ? (screen.height - 525) / 2.5 : 0)); return false;">Gyro Trans. Console</a></div>
                </div>
            </div>
        </td>
        
        <?php
        if ($_SESSION['user']['type'] == 'tech-admin' || ($_SESSION['user']['type'] == 'content-admin' && @mysql_result(mysql_query("SELECT 1 FROM type_user__admin_access WHERE user_id = '" . $_SESSION['user']['user_id'] . "' AND doc_name = 'admin/elements'"), 0))):
            $sql_query = mysql_query("SELECT title, doc_subtype FROM sys_docs_subtypes WHERE doc_type = 'element' ORDER BY idx ASC");
            if (mysql_num_rows($sql_query) > 0):
            ?>
            <td align="right">
                <div class="admin-box">
                    <img src="/repository/admin/images/admin-box-icon-groups.gif" style="padding: 10px; position: absolute; top: -45px; right: -55px; z-index: 1;">
                    <div class="admin-box-data">
                        <? while ($sql = mysql_fetch_assoc($sql_query)): ?>
                        <div><a href="<?=href('/?doc=admin/elements&subtype=' . $sql['doc_subtype'])?>"><?=$sql['title']?></a></div>
                        <? endwhile; ?>
                    </div>
                </div>
            </td>
            <? endif; ?>
        <? endif; ?>
    </tr>
</table>

<div style="margin-top: 25px;"><img src="/repository/admin/images/paragon-note.gif"></div>

</div>

</body>
</html>
