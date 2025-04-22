<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';


?>

<?php
// Wprowadź swoje dane autoryzacyjne
$clientId = '0464f85d-1703-45b6-9b7a-22b624a1e2a3';  // Twój Client ID
$clientSecret = '8ce4bcb8e5134f7ba72141caa9b334c71658e5983ca94405b323013699a2f964';  // Twój Client Secret
$tokenUrl = 'https://rejestr-bdo.mos.gov.pl/oauth/token';  // Standardowy endpoint tokenu


// Przygotowanie danych do zapytania
$data = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'client_credentials'  // Typ grant dla OAuth 2.0
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data),
    ]
];

// Wykonanie zapytania
$context = stream_context_create($options);


$response = file_get_contents($tokenUrl, false, $context);

if ($response === FALSE) {
    die('Error occurred while generating access token');
}

// Dekodowanie odpowiedzi
$response_data = json_decode($response, true);

// Otrzymasz token w odpowiedzi
if (isset($response_data['access_token'])) {
    $accessToken = $response_data['access_token'];
    echo "Access Token: " . $accessToken;
} else {
    echo "Nie udało się uzyskać tokenu dostępu. Odpowiedź: " . print_r($response_data, true);
}
?>
