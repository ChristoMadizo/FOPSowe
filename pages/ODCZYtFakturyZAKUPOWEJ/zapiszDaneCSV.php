<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$file_path = "/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/faktura.csv";
$csv_file = fopen($file_path, "w");

foreach ($data["zamowienia"] as $zamowienie) {
    fputcsv($csv_file, [$zamowienie["lp"], $zamowienie["opis"], $zamowienie["ilosc"], $zamowienie["jednostka_miary"], $zamowienie["cena"], $zamowienie["stawka_vat"], $zamowienie["wartosc_netto"], $zamowienie["vat"], $zamowienie["wartosc_brutto"]], ";");
}

fclose($csv_file);
echo "Plik CSV zapisany!";



?>