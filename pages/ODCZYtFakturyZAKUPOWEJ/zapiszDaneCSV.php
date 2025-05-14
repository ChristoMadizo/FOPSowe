<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    file_put_contents('/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/debug.log', print_r($_POST, true));
    
    if (!empty($_POST["csv_data"])) {
        $csv_data = $_POST["csv_data"];
        $file_path = "/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/faktura.csv";

        if (file_put_contents($file_path, $csv_data)) {
            echo "Plik CSV zapisany pomyślnie!";
        } else {
            echo "❌ Błąd zapisu pliku!";
        }
    } else {
        echo "❌ Brak danych do zapisania!";
    }
} else {
    echo "❌ Żądanie nie jest typu POST!";
}
?>
