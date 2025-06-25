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
//$searchUrl = "https://rejestr-bdo.mos.gov.pl/api/​WasteRegister​/ElectronicWasteRecordCard​/v1​/Kezs​/Kezs​/KezsStockWasteMassGroup​/card";
$searchUrl="https://rejestr-bdo.mos.gov.pl/api/WasteRegister/WasteRecordCard/v1/Keo/KeoForwarded/search";

$requestData = [
   // "CountryWasteMass" => 0,
  //  "AbroadWasteMass" => 0,
  /*  "KeoDto" => [
        "WasteMass" => 0,
        "KeoId" => "8e777cb8-dffb-4278-92cd-6654458891b3",
        "WasteCodeId" => 0,
      //  "CardNumber" => "string",
      //  "CreatedByUserId" => "3fa85f64-5717-4562-b3fc-2c963f66afa6",
        "IsWasteGenerating" => true,
        "IsWasteCollecting" => true,
        "IsSalvage" => true,
        "IsNeutralization" => true,
        "Year" => 2025,
        "CanBeDeleted" => true,
        "WasteCodeExtended" => true,
      //  "WasteCodeExtendedDescription" => "string",
        "HazardousWasteReclassification" => true,
      //  "HazardousWasteReclassificationDescription" => "string"
    ],*/
    "KeoId" => "504a75bc-b4d4-4dfc-9a58-79247b91900e",
    

   
 //   "ForwardedForwardedKeos" => [
     /*   "Items" => [
            [
             //   "WasteMass" => 0,
              //  "EmergencyModeCardNumber" => "string",
              //  "KeoForwardedId" => "8e777cb8-dffb-4278-92cd-6654458891b3",
                "WasteCollectionDate" => "2025-01-01T06:43:42.733Z",
               // "CardNumberKpo" => "string",
               // "CardNumberKpok" => "string",
               // "TransferWay" => "string",
                "TransportDate" => "2025-01-01T06:43:42.733Z",
               // "CreatedByUser" => "string"
            ]
        ],*/
  //      "PageSize" => 100,
        //"PageNumber" => 0,
  //      "TotalPagesNumber" => 10,
  //      "TotalResultNumber" => 10,
  //      "HasPreviousPage" => true,
  //      "HasNextPage" => true
  //  ],
    
    "PaginationParameters" => [
        "Order" => [
            "IsAscending" => true,
         //   "OrderColumn" => "year"
        ],
        "Page" => [
            "Index" => 0,
            "Size" => 100
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


if ($response === false) {
    $error = curl_error($ch);
    echo "<div class='content'>Błąd cURL: " . htmlspecialchars($error) . "</div>";
} else {
    echo "<div class='content'>{$response}</div>";
}

//echo "<div class='content'>{$response}</div>";

curl_close($ch);

$responseData = json_decode($response, true);

//echo "<div class='content'>{$responseData}</div>";


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
