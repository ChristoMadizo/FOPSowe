<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

// http://192.168.101.203/pages/BDO/bdo_01_lista_keo.php

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
//$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/​WasteRegister​/ElectronicWasteRecordCard​/v1​/Kezs​/Kezs​/KezsStockWasteMassGroup​/card";
$searchUrl="https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteRecordCard/v1/Keo/search";

$requestData = [
  "Year"=> 2025,
  "PaginationParameters" => [
    "Order"=> [
      "IsAscending"=> true,
      "OrderColumn"=> "CardNumber"
    ],
    "Page"=> [
      "Index"=> 0,
      "Size"=> 500
    ]
],
  //"WasteCode": "string",
  //"CardNumber"=> "string"
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
        <?php
        if (!empty($items)) {
            // Use the first item to get all keys for headers
            foreach (array_keys($items[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
        }
        ?>
        </tr>

        <?php foreach ($cards as $item): ?>
            <tr>
                <?php
                foreach ($item as $value) {
                    echo "<td>" . htmlspecialchars(is_null($value) ? '' : (is_array($value) ? json_encode($value) : $value)) . "</td>";
                }
                ?>


            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>

