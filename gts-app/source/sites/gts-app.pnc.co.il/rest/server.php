<?php
error_reporting(E_ERROR);

require('../config.inc.php');
require('../core.inc.php');
require('../aes.inc.php');

$help = $_GET['help'];
$func = preg_match('/^[_a-z0-9\.]+$/i', $_GET['func']) && file_exists('functions/' . $_GET['func'] . '.inc.php') ? $_GET['func'] : null;
$args = json_decode(file_get_contents("php://input"));

$_SERVER['PHP_AUTH_USER'] && $args->username = $_SERVER['PHP_AUTH_USER'];
$_SERVER['PHP_AUTH_PW'] && $args->password = $_SERVER['PHP_AUTH_PW'];

if ($help) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<style tyle="text/css">* { font-family: Verdana; font-size: 14px; } p { margin-bottom: 25px; } a, div { font-family: Courier New; font-weight: bold; } li { margin: 5px; } span { font-family: Courier New; }</style>' . "\n\n";
    
    if ($func) {
        include 'functions/' . $func . '.inc.php';
        
        echo '<div>[ <a href="/rest/help">GTS REST API</a> ]</div><br>' . "\n\n";
        echo '<div>[ <a href="/rest/help/' . $func . '">' . $func . '</a> ]</div><br>' . "\n\n";
        
        if (${$func . 'Description'}) {
            echo '<p>' . ${$func . 'Description'} . '</p>' . "\n\n";
        }
        
        echo '<div>' . $func . 'Request' . '</div>' . "\n\n";
        echo styleFuncDefinition(${$func . 'Request'}) . "\n\n";
        
        echo '<div>' . $func . 'Response' . '</div>' . "\n\n";
        echo styleFuncDefinition(${$func . 'Response'}) . "\n\n";
    } else {
        echo '<div>[ GTS REST API ]</div>' . "\n\n";
        
        if ($dh = opendir('functions')) {
            $rows = array();
            while (false !== ($file = readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    $func = substr($file, 0, -8);
                    $rows[] = '<li><a href="help/' . $func . '">' . $func . '</a></li>';
                }
            }
            closedir($dh);
            
            sort($rows);
            echo '<ul>' . implode("\n", $rows) . '</ul>' . "\n";
        }
    }
} else {
    if ($func) {
        include 'functions/' . $func . '.inc.php';
        
        $response = call_user_func($func, $args);
    } else {
        $response = array(
            'result' => 'FAIL',
            'error' => err(3)
        );
    }
    
    header('Content-Type: application/json; charset=UTF-8');
    exit(json_encode(strip_nulls($response)));
}

?>