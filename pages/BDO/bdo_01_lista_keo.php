<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Pobranie danych z API
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

// Pobranie kart
$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteRecordCard/v1/Keo/search";

$year = $_GET['year'] ?? date('Y'); // Pobiera rok z parametru lub używa bieżącego roku

$requestData = [
    "Year" => $year,
    "PaginationParameters" => [
        "Order" => [
            "IsAscending" => true,
            "OrderColumn" => "CardNumber"
        ],
        "Page" => [
            "Index" => 0,
            "Size" => 500
        ]
    ]
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

// Pobranie `cardNumber` i `KeoId`
$karty_keo = array_map(fn($item) => [
    "cardNumber" => $item["cardNumber"] ?? null,
    "keoId" => $item["keoId"] ?? null
], $responseData['items'] ?? []);

header('Content-Type: application/json');
echo json_encode($karty_keo, JSON_PRETTY_PRINT);
