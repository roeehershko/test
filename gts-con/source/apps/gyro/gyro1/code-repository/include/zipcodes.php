<?php

// Note: at present this is has not been transitioned to UTF8.

mysql_connect('localhost');
mysql_select_db('zipcodes');

mysql_query('SET NAMES utf8');

if ($_GET['city']) {
    header('Content-Type: text/xml; charset=UTF-8');
    
    if ($zipcode = @mysql_result(mysql_query("SELECT zipcode FROM cities WHERE name = '" . rawurldecode($_GET['city']) . "' AND zipcode IS NOT NULL"), 0)) {
        echo '<XMLResult multiple="0">' . "\n";
        echo '    <result zipcode="' . $zipcode . '"/>' . "\n";
        echo '</XMLResult>' . "\n";
    } else {
        echo '<XMLResult multiple="1">' . "\n";
        $sql_query = mysql_query("SELECT streets.name, streets.zipcode FROM cities, streets WHERE cities.name = '" . rawurldecode($_GET['city']) . "' AND cities.id = streets.city_id ORDER BY streets.name ASC");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            echo '    <result street="' . $sql['name'] . '" zipcode="' . $sql['zipcode'] . '"/>' . "\n";
        }
        echo '</XMLResult>' . "\n";
    }
    
    exit;
}

?>