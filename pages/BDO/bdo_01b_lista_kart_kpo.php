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
    die(json_encode(["error" => "Błąd generowania tokena!"]));
}

// 2️⃣ Wysyłanie zapytania do wyszukiwarki kart przekazania odpadów
$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteTransferCard/v1/Kpo/sender/search";


$allResults = [];
$pageIndex = 0;

do {
    $requestData = [
        "PaginationParameters" => [
            "Order" => [
                "IsAscending" => false,
                "OrderColumn" => "cardRejectionTime"
            ],
            "Page" => [
                "Index" => $pageIndex,  // Dynamicznie zmienia indeks strony
                "Size" => 500
            ]
        ],
        "SearchInCarriers" => true,
        "SearchInReceivers" => true,
        "TransportDateRange" => true,
        "TransportDateFrom" => "2025-01-01T00:00:00",    //czyli pobieramy karty od 2025 roku (po uzgodnienieu z Jankiem)
        "ReceiveConfirmationDateRange" => true
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
    
    // Pobieranie danych i dodanie do listy wyników
    $items = array_map(fn($item) => [
        "cardNumber" => $item["cardNumber"] ?? null,
        "KpoId" => $item["kpoId"] ?? null,
        "cardStatus" => $item["cardStatus"] ?? null,
        "senderFirstNameAndLastName" => $item["senderFirstNameAndLastName"] ?? null,
        "cardRejectionTime" => $item["cardRejectionTime"] ?? null,
        "realTransportTime" => $item["realTransportTime"] ?? null,
        "wasteCode" => $item["wasteCode"] ?? null,
        "wasteCodeDescription" => $item["wasteCodeDescription"] ?? null,
        "kpoLastModifiedAt" => $item["kpoLastModifiedAt"] ?? null,
    ], $responseData['items'] ?? []);
    
    $allResults = array_merge($allResults, $items);
    
    // Sprawdzanie, czy jest kolejna strona
    $hasNextPage = $responseData['hasNextPage'] ?? false;
    $pageIndex++;

} while ($hasNextPage); // Pobieranie do momentu, aż nie ma kolejnych stron


// Usuwanie DUPLIKATÓW na podstawie tylko 'KpoId'
$uniqueResults = [];
foreach ($allResults as $item) {
    $uniqueResults[$item['KpoId']] = $item; // Przechowujemy unikalne KpoId
}

// Konwersja z powrotem do indeksowanej tablicy
$allResults = array_values($uniqueResults);


header('Content-Type: application/json');
echo json_encode($allResults);
