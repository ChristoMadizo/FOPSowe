<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// 2️⃣ Pobranie kart przekazania odpadów
$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteTransferCard/v1/Kpo/sender/search";
$requestData = [
    "PaginationParameters" => [
        "Order" => [
            "IsAscending" => false,
            "OrderColumn" => "ReceiveConfirmationTime"
        ],
        "Page" => [
            "Index" => 0,
            "Size" => 500
        ]
        ],
    "SearchInCarriers" => true,
    "SearchInReceivers" => true,
    "TransportDateRange" => true,  //// Aktywuj filtrowanie zakresu dat
    "TransportDateFrom" => "2025-01-01T00:00:00", // Start od początku 2025 roku
    "ReceiveConfirmationDateRange" => true,    //usunięcie tego zeruje tabelę

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
curl_close($ch);

$responseData = json_decode($response, true);

$items = $responseData['items'];

// 3️⃣ Filtrowanie tylko kart ze statusem "Potwierdzenie transportu"
$confirmedTransportCards = array_values(array_filter($items, function($item) {
    return $item["cardStatus"] === "Potwierdzenie transportu";
}));


$lastPotwierdzonyTransport_old = file_get_contents('/home/kmadzia/www/pages/BDO/ostatnia_potwierdzony_transport.txt');
$lastPotwierdzonyTransport_new=$confirmedTransportCards[1]['cardNumber']; //sprawdza czy jest nowa karta odrzucona
$PotwierdzonyTransportpoId_new = $confirmedTransportCards[1]['kpoId']; // Pobiera KpoId
//$confirmedTransportCards = $items;



if ($lastPotwierdzonyTransport_new) {
    $filePath = '/home/kmadzia/www/pages/BDO/ostatnia_potwierdzony_transport.txt';
    file_put_contents($filePath, "$lastPotwierdzonyTransport_new | KpoId: $PotwierdzonyTransportpoId_new");

    if ($lastPotwierdzonyTransport_new !== explode(' ', $lastPotwierdzonyTransport_old)[0]) {
        foreach (['k.madzia@fops.pl'] as $email) {  // Wysyła maila do dwóch osób
            sendEmail($email,
                'BDO: nowa karta - Potwierdzenie transoportu: ' . $lastPotwierdzonyTransport_new,
                'Nowa karta - Potwierdzenie transoportu: ' . $lastPotwierdzonyTransport_new . 
                "\nKpoId: " . $PotwierdzonyTransportpoId_new . 
                "\nUrl: " . "https://rejestr-bdo.mos.gov.pl/WasteRegister/WasteTransferForwardedCard/EditRejected/" . $PotwierdzonyTransportpoId_new,
                $attachment_path = null,
                $is_html = false
            );
        }
        echo "<div class='content'>Poszło info o nowym potwierdzonym transporcie.</div>";
    } else {
        echo "<div class='content'>Brak nowych potwierdzonych transportów.</div>";
    }
}




?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Kart Potwierdzonego Transportu</title>
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

    <h2>Lista Kart Potwierdzonego Transportu</h2>
    <table>
        <tr>
            <th>Numer karty</th>
            <th>Status karty</th>
            <th>Data faktycznego transportu</th>
        </tr>

        <?php foreach ($confirmedTransportCards as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item["cardNumber"]) ?></td>
                <td><?= htmlspecialchars($item["cardStatus"]) ?></td>
                <td><?= htmlspecialchars($item["realTransportTime"]) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>
