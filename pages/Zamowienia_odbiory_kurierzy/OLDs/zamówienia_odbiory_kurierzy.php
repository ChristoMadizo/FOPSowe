<?php


require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
use Smalot\PdfParser\Parser;
ini_set('display_errors', 1);
error_reporting(E_ALL);



$connection = db_connect_mysqli();

//SQL do pobrania id plików dla danego zlecenia
$sql = 'SELECT 
DISTINCT files.id
FROM prosto.orders orders
JOIN prosto.files_orders files_orders on orders.id=files_orders.order_id
join prosto.files files on files_orders.file_id =files.id
join prosto.files_tokens files_tokens on files.id=files_tokens.file_id
WHERE orders.serial="Z-9552-MDZ-0525"';

//pobiera id plików podpiętych pod dane zlecenie
$file_ids = fetch_data($connection, $sql);

$url_poczatek = 'https://prosto.fops.pl/index.php/files/download/';
$url_part2 = '?disposition=inline';
// URL pliku PDF do pobrania

$lista_zlecenie_listy_przewozowe = [];

foreach ($file_ids[1] as $file) {
    // Pobranie ID pliku
    $file_id = $file['id'];

    // SQL do pobrania tokena pliku
    $sql = 'SELECT token FROM prosto.files_tokens WHERE file_id = ' . $file_id . ' ORDER BY created DESC LIMIT 1';

    $file_token = fetch_data($connection, $sql);

    foreach ($file_token[1] as $token) {
        $url = $url_poczatek . $file_id . '/' . $token['token'] . $url_part2;
        // reszta kodu pobierającego i przetwarzającego PDF
        //   echo "URL dla pliku $file_id: $url\n";

        $text = parse_pdf($url);
        //echo $text;

        $reg_text = '';


        switch (true) {
            case preg_match('/\bGLS\b/i', $text):
                $firma = "GLS";
                $reg_text = '/Your GLS Track ID:\s*\n[A-Z0-9]+\s*\n\d+\s*\n(\d{12})/';
                $numer_listu_przewozowego = regexGetMatchingText($text, $reg_text);
                echo 'To jest ten numer' . $numer_listu_przewozowego;
                break;
            case preg_match('/DHL/', $text):
                $firma = "DHL";
                break;
            case preg_match('/UPS/', $text):
                $firma = "UPS";
                break;
            case preg_match('/FedEx/', $text):
                $firma = "FedEx";
                break;
            default:
                $firma = "Nie znaleziono znanej firmy";
        }

        //echo $firma;

        echo '<div class="content">Koniec tego</div>';

    }

}


// Pobieranie pliku PDF
function parse_pdf($url)
{
    $parser = new Smalot\PdfParser\Parser();
    
    // Pobieranie zawartości PDF
    $fileContent = file_get_contents($url);
    if ($fileContent === false) {
        die("Błąd pobierania pliku PDF.\n");
    }

    // Tworzenie tymczasowego pliku
    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
    file_put_contents($tempFile, $fileContent);

    // Parsowanie pliku
    try {
        $pdf = $parser->parseFile($tempFile);
        $pages = $pdf->getPages();
        
        if (empty($pages)) {
            return "Błąd: Plik PDF nie zawiera stron.";
        }

        $text = "";

        // Pobranie tekstu ze wszystkich stron
        foreach ($pages as $page) {
            $text .= $page->getText() . "\n\n"; // Dodanie odstępu między stronami
        }

        // Normalizacja znaków białych i struktury
        $text = preg_replace('/\s+/', ' ', $text); // Zamiana wielokrotnych spacji na pojedyncze
        $text = preg_replace('/(\w)-\s(\w)/', '$1$2', $text); // Łączenie przeniesionych wyrazów
        $text = trim($text);

        // Usunięcie tymczasowego pliku
        unlink($tempFile);

        return $text;
    } catch (Exception $e) {
        unlink($tempFile); // Usuwanie tymczasowego pliku w przypadku błędu
        return "Błąd parsowania PDF: " . $e->getMessage();
    }
}









