<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

//echo '<div class="content">';

// 1️⃣ Generowanie nowego Access Token
$generateTokenUrl = "https://rejestr-bdo.mos.gov.pl/api/WasteRegister/v1/Auth/generateEupAccessToken";
$tokenRequestData = [
    "ClientId" => "0464f85d-1703-45b6-9b7a-22b624a1e2a3",
    "ClientSecret" => "8ce4bcb8e5134f7ba72141caa9b334c71658e5983ca94405b323013699a2f964",
    "EupId" => "6cb7c7e2-ae67-4d8e-aaf0-b692c2a07ab3"
];

$ch = curl_init($generateTokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tokenRequestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Content-Type: application/json"
]);

$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($tokenResponse, true);
$accessToken = $tokenData["AccessToken"] ?? null;

if (!$accessToken) {
    die("<div class='content'>Błąd generowania tokena!</div>");
}

// 2️⃣ Wysyłanie zapytania do wyszukiwarki kart przekazania odpadów
$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteTransferCard/v1/Kpo/sender/search";
$requestData = [
    "PaginationParameters" => [
        "Order" => [
            "IsAscending" => false,
            "OrderColumn" => "cardRejectionTime"   //sortowanie po cardRejectionTime (najnowsze u góry)
        ],
        "Page" => [
            "Index" => 0,
            "Size" => 500
        ]
    ],
   // "Year" => 2024,
    "SearchInCarriers" => true,
    "SearchInReceivers" => true,
    //"CardRejectionTime" => "2025-01-09T13:56:48.406Z", // Filtrujemy karty, które mają wartość w "cardRejectionTime"
    //"CardStatusCodeNames" => ["REJECTED"],
  //  "TransportTime" => "2025-01-09T13:56:48.406Z",
  //  "ReceiveConfirmationTime" => "2025-01-09T13:56:48.406Z",
    "TransportDateRange" => true,  //// Aktywuj filtrowanie zakresu dat
    "TransportDateFrom" => "2025-01-01T00:00:00", // Start od początku 2024 roku
  //  "TransportDateFrom" => "2025-01-09T13:56:48.406Z",
  //  "TransportDateTo" => "2025-01-09T13:56:48.406Z",
    "ReceiveConfirmationDateRange" => true,    //usunięcie tego zeruje tabelę
  //  "ReceiveConfirmationDateFrom" => "2025-01-09T13:56:48.406Z",
  //  "ReceiveConfirmationDateTo" => "2025-01-09T13:56:48.406Z"
];

$ch = curl_init($searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

//echo "Kod odpowiedzi HTTP: " . $httpCode . "<br>";
//echo $response;


$responseData = json_decode($response, true);

// Filtrowanie tylko kart, które mają "cardRejectionTime" różne od null
$filteredItems = array_filter($responseData["items"], function($item) {
    return !empty($item["cardRejectionTime"]);     //cardRejectionTime    //cardNumber
});

$lastRejectedCardNumber_old = file_get_contents('/home/kmadzia/www/pages/BDO/ostatnia_odrzucona.txt');
$lastRejectedCardNumber_new=$filteredItems[1]['cardNumber']; //sprawdza czy jest nowa karta odrzucona

if (!empty($filteredItems)) {  //powiadominie o nowej odrzuconej karcie
    if ($lastRejectedCardNumber_new) {
        $filePath = '/home/kmadzia/www/pages/BDO/ostatnia_odrzucona.txt';
        file_put_contents($filePath, $lastRejectedCardNumber_new);
        if ($lastRejectedCardNumber_new !== $lastRejectedCardNumber_old) {
            //SendSMSNokia(503100955, "Nowa karta odrzucona: $lastRejectedCardNumber_new");
            foreach (['k.madzia@fops.pl','j.boruta@fops.pl'] as $email) {  //wysyła maila do dwóch osób
                sendEmail($email,'BDO: nowa odrzucona karta: ' . $lastRejectedCardNumber_new . '( ' . $filteredItems[1]["cardRejectionTime"] . ' )',
                'Nowa odrzucona karta: ' . $lastRejectedCardNumber_new,$attachment_path = null,$is_html = false);
            }

            
            echo "<div class='content'>Poszło info o odrzuceniu.</div>";
        } else {
            echo "<div class='content'>Brak nowych odrzuconych kart.</div>";
        }
    }
}

//$most_recent_item

//var_dump($filteredItems);

// Wyświetlenie wyników po filtracji

//echo '<div>' . json_encode($filteredItems, JSON_PRETTY_PRINT) . '</div>';

//echo '</div>';

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Kart Przekazania Odpadów</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>

    <h2>Lista Kart Przekazania Odpadów</h2>
    <table>
        <tr>
            <th>Numer karty</th>
            <th>Status</th>
            <th>Data odrzucenia</th>
            <th>Planowany transport</th>
            <th>Rzeczywisty transport</th>
            <th>Kod odpadu</th>
            <th>Opis odpadu</th>
            <th>Nr rejestracyjny pojazdu</th>
            <th>Nadawca</th>
            <th>Odbiorca</th>
            <th>Ostatnia modyfikacja</th>
            <th>Rewizja</th>
            <th>Osoba wysyłająca</th>
            <th>Osoba odbierająca</th>
        </tr>

        <?php
        // Pobranie wcześniej wygenerowanych danych JSON ze skryptu PHP
        $jsonData = json_encode($filteredItems); // Użycie $filteredItems bezpośrednio
        $filteredItems = json_decode($jsonData, true);

        foreach ($filteredItems as $item) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($item["cardNumber"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["cardStatus"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["cardRejectionTime"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["plannedTransportTime"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["realTransportTime"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["wasteCode"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["wasteCodeDescription"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["vehicleRegNumber"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["senderName"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["receiverName"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["kpoLastModifiedAt"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["revisionNumber"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["senderFirstNameAndLastName"]) . '</td>';
            echo '<td>' . htmlspecialchars($item["receiverFirstAndLastName"] ?? '') . '</td>';
            echo '</tr>';
        }
        ?>
    </table>

    <h3>Wyniki wyszukiwania</h3>
</body>
</html>
