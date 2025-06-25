
<?php  //kasuje pliki z paskami po wykryciu braku obecnosci na stronie z paskami

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Warsaw'); // Ustawienie strefy czasowej na Warszawę

// Ścieżki do plików i katalogów
$dataFile = "/home/kmadzia/www/pages/PASKI_WYPLATY/sprawdzacz_obecnosci_na_stronie.txt";
$pdfDir = "/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu/";
$testDir = "/home/kmadzia/www/pages/PASKI_WYPLATY/TestySPLIT/";

// Sprawdzenie, czy plik istnieje
if (!file_exists($dataFile)) {
    die("Błąd: Plik z datą nie istnieje.");
}

// Pobranie daty i godziny z pliku
$lastActiveTime = trim(file_get_contents($dataFile));
$lastActiveDateTime = new DateTime($lastActiveTime);
$now = new DateTime();

// Obliczenie różnicy czasu w minutach
$interval = $now->diff($lastActiveDateTime);
$elapsedMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

if ($elapsedMinutes >= 1) {
    // Funkcja do kasowania plików w katalogu
    function deleteFilesInDirectory($directory) {
        if (!is_dir($directory)) {
            echo "Błąd: Katalog nie istnieje - $directory\n";
            return;
        }

        $files = glob($directory . "*"); // Pobranie listy plików
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file); // Usunięcie pliku
                echo "Usunięto: $file\n";
            }
        }
    }

    // Kasowanie plików w obu katalogach
    deleteFilesInDirectory($pdfDir);
    deleteFilesInDirectory($testDir);
} else {
    echo "Nie minęło jeszcze 3 minuty. Pliki pozostają.\n";
}
?>
