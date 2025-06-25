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

/*
// 3️⃣ Filtrowanie tylko kart ze statusem "Potwierdzenie transportu"
$cards = array_values(array_filter($items, function($item) {
    return $item["cardStatus"] === "Potwierdzenie transportu";
}));
*/

$cards=$items;

foreach ($cards as &$item) {
    if (in_array($item["cardStatus"], ["Potwierdzenie transportu", "Potwierdzenie przejęcia"])) {
        $item["status_final"] = "dodac_do_ewidencji";
    } else {
        $item["status_final"] = "else";
    }
}
unset($item);

/*
$lastPotwierdzonyTransport_old = file_get_contents('/home/kmadzia/www/pages/BDO/ostatnia_potwierdzony_transport.txt');
$lastPotwierdzonyTransport_new=$confirmedTransportCards[1]['cardNumber']; //sprawdza czy jest nowa karta odrzucona
$PotwierdzonyTransportpoId_new = $confirmedTransportCards[1]['kpoId']; // Pobiera KpoId
$confirmedTransportCards = $items;

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
}*/


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
            <th>status_final</th>
            <th>Numer karty</th>
            <th>Status karty</th>
            <th>Data faktycznego transportu</th>
            <th>Data planowanego transportu</th>
            <th>Kod odpadu</th>
            <th>Opis odpadu</th>
            <th>Numer rejestracyjny pojazdu</th>
            <th>Nazwa statusu karty</th>
            <th>Nazwa nadawcy</th>
            <th>KpoId</th>
            <th>Data ostatniej modyfikacji KpoId</th>
            <th>Imię i nazwisko nadawcy</th>
            <th>Imię i nazwisko odbiorcy</th>
            <th>Numer rewizji</th>
            <th>Data odrzucenia karty</th>
            <th>Rozszerzony kod odpadu</th>
            <th>Reklasyfikacja odpadu niebezpiecznego</th>
        </tr>

        <?php foreach ($cards as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item["status_final"]) ?></td>
                <td><?= htmlspecialchars($item["cardNumber"]) ?></td>
                <td><?= htmlspecialchars($item["cardStatus"]) ?></td>
                <td><?= htmlspecialchars($item["plannedTransportTime"]) ?></td>
                <td><?= htmlspecialchars($item["realTransportTime"]) ?></td>
                <td><?= htmlspecialchars($item["wasteCode"]) ?></td>
                <td><?= htmlspecialchars($item["wasteCodeDescription"]) ?></td>
                <td><?= htmlspecialchars($item["vehicleRegNumber"]) ?></td>
                <td><?= htmlspecialchars($item["cardStatusCodeName"]) ?></td>
                <td><?= htmlspecialchars($item["senderName"]) ?></td>
                <td><?= htmlspecialchars($item["kpoId"]) ?></td>
                <td><?= htmlspecialchars($item["kpoLastModifiedAt"]) ?></td>
                <td><?= htmlspecialchars($item["senderFirstNameAndLastName"]) ?></td>
                <td><?= isset($item["receiverFirstAndLastName"]) && $item["receiverFirstAndLastName"] !== null ? htmlspecialchars($item["receiverFirstAndLastName"]) : '' ?></td>
                <td><?= htmlspecialchars($item["revisionNumber"]) ?></td>
                <td><?= isset($item["cardRejectionTime"]) && $item["cardRejectionTime"] !== null ? htmlspecialchars($item["cardRejectionTime"]) : '' ?></td>
                <td><?= htmlspecialchars($item["wasteCodeExtended"]) ?></td>
                <td><?= htmlspecialchars($item["hazardousWasteReclassification"]) ?></td>


            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>
