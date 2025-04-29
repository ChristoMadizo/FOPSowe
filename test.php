<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


$connection = ibase_connect('192.168.101.79/3050:C:\fakt95\0002\0002BAZA.FDB', 'krzysiek', 'Bielawa55');
if ($connection) {
    echo "Połączono pomyślnie!";
} else {
    echo "Błąd: " . ibase_errmsg();
}
?>
