<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';


//pobieranie listy kart keo - cardNumber i keoId ze wszystkich lat
$years = [2024, 2025, 2026, 2027, 2028, 2029, 2030]; // Lista lat do pobrania
$all_keo_CardNumber_keoId = [];
foreach ($years as $year) {
    $apiUrl = "http://192.168.101.203/pages/BDO/bdo_01_lista_keo.php?year=$year";
    $response = file_get_contents($apiUrl);

    if ($response === false) {
        echo "Błąd pobierania danych dla roku $year!<br>";
        continue;
    }

    $cards = json_decode($response, true);

    if (!is_array($cards)) {
        echo "Niepoprawne dane dla roku $year!<br>";
        continue;
    }

    // Pobranie tylko wartości `CardNamex` i `keoId`
    $filteredCards = array_map(fn($item) => [
        "cardNumber" => $item["cardNumber"] ?? null,
        "keoId" => $item["keoId"] ?? null
    ], $cards);

    $all_keo_CardNumber_keoId[$year] = $filteredCards;
}

$keoIdList = [];  //wyciąga z tablicy wszystkie keoId

foreach ($all_keo_CardNumber_keoId as $year => $cards) { // Iteracja przez lata
    foreach ($cards as $card) { // Iteracja przez każdą kartę w danym roku
        if (isset($card['keoId'])) { // Sprawdzenie, czy pole `keoId` istnieje
            $keoIdList[] = $card['keoId']; // Dodanie do listy
        }
    }
}


//header('Content-Type: application/json');
//echo json_encode($all_keo_CardNumber_keoId, JSON_PRETTY_PRINT);



//pobieranie listy kart kpo dla wszystkich kart keo
$all_kpo_cards_from_keo_cards = [];

foreach ($keoIdList as $keoCard) {
    $keoId = $keoCard ?? null; // Pobranie pierwszego elementu jako KeoId, jeśli nie jest klucz 'keoId'
    if ($keoId) {
        $apiUrl = "http://192.168.101.203/pages/BDO/bdo_02_keo_items_kpo.php?keoId=$keoId";
        $response = file_get_contents($apiUrl);

        if ($response !== false) {
            $keo_cards_kpo_items = json_decode($response, true);

            if (is_array($keo_cards_kpo_items)) {
                $all_kpo_cards_from_keo_cards[$keoId] = $keo_cards_kpo_items; // Przypisanie wyników do KeoId
            }
        } else {
            echo "Błąd pobierania danych dla KeoId: $keoId<br>";
        }
    }
}

$all_kpo_cards_from_keo_cards = array_merge(...array_values($all_kpo_cards_from_keo_cards));


/*
echo "<pre>";
//print_r($all_kpo_cards_from_keo_cards);
echo "</pre>";
*/


//pobiera dane o przejsciu z CardName na kpoid (slownik)   -  pobieramy karty od 2025 roku (po uzgodnieniu z Jankiem)
$apiUrl = "http://192.168.101.203/pages/BDO/bdo_01b_lista_kart_kpo.php";
$response = file_get_contents($apiUrl);
$karty_kpo_slownik = json_decode($response, true);

/*
echo '<pre>';
//print_r($karty_kpo_slownik);
echo '</pre>';
*/


//dodaje do karty kpo id na podstawie cardNumber - teraz mamy już w zmiennej CardNumber i KpoId - to lista kart kpo, które zostały użyte w kartach keo
foreach ($all_kpo_cards_from_keo_cards as $index => $cardNumber) {
    foreach ($karty_kpo_slownik as $entry) {
        if ($cardNumber === $entry['cardNumber']) {
            $all_kpo_cards_from_keo_cards[$index] = [
                'cardNumber' => $cardNumber,
                'KpoId' => $entry['KpoId']
            ];
            break;
        }
    }
}
unset($card); // Usuwamy referencję, aby uniknąć niechcianych zmian

$kpo_cards_used_in_keo = $all_kpo_cards_from_keo_cards;  //to tylko zmiana nazwy zmiennej

/*
echo "<pre>";
print_r($kpo_cards_used_in_keo);
echo "</pre>";
*/

//poniżej sprawdzamy które karty kpo (ze zmiennej $karty_kpo_slownik) nie zostały użyte w kartach keo

/*echo "<pre>";
print_r($karty_kpo_slownik);
echo "</pre>";*/


foreach ($karty_kpo_slownik as &$entry) {
    $entry['used_at_keo'] = in_array($entry['KpoId'], array_column($kpo_cards_used_in_keo, 'KpoId'))
        ? 'used_at_keo'
        : 'not_used_at_keo';
}
unset($entry); // Usuwamy referencję dla bezpieczeństwa

/*
echo "<pre>";
print_r($karty_kpo_slownik);
echo "</pre>";*/

//bierzemy tylko karty kpo, które nie były używane w kartach keo
$karty_kpo_slownik = array_filter($karty_kpo_slownik, fn($entry) => $entry['used_at_keo'] === 'not_used_at_keo');

//odrzucamy karty kpo, które są wycofane, albo ze statusem 'Potwierdzenie wygenerowane'
$karty_kpo_slownik = array_filter(
    $karty_kpo_slownik,
    fn($entry) =>
    $entry['cardStatus'] !== 'Wycofana' &&
    $entry['cardStatus'] !== 'Potwierdzenie wygenerowane'
);



$wasteCodeMap = [
    '15 01 02' => 'b352696e-6acc-4585-a69e-b28f98fc2ca7',
    '16 02 14' => '31437c77-ff07-49ab-8d27-a28ceb94e133',
    '08 03 12' => '870193b6-13e5-40ee-8ee3-da8d2c56d22f',
    '15 01 03' => '944dc02a-15cf-40fd-91c7-bf86ffe137a7',
    '15 01 10' => '8e777cb8-dffb-4278-92cd-6654458891b3',
    '15 01 01' => '82a4bf60-26e0-4eb8-86c9-0297722a694a',
    '07 02 13' => '6b9a7c57-83b8-4a03-b39a-6ae3190ec096',
];

foreach ($karty_kpo_slownik as &$entry) {
    $wasteCode = $entry['wasteCode'] ?? null;
    $uuid = $wasteCode && isset($wasteCodeMap[$wasteCode]) ? $wasteCodeMap[$wasteCode] : ($entry['KpoId'] ?? '');

    $baseUrl = match ($entry['cardStatus'] ?? '') {
        'Odrzucona' => 'https://rejestr-bdo.mos.gov.pl/WasteRegister/WasteTransferForwardedCard/EditRejected/' . $uuid,
        'Wycofana' => 'https://rejestr-bdo.mos.gov.pl/WasteRegister/WasteTransferForwardedCard/ShowWithdrawn/' . $uuid,
        'Potwierdzenie przejęcia', 'Potwierdzenie transportu' => 'https://rejestr-bdo.mos.gov.pl/WasteRegister/WasteRecordCard/Create/TransferedWaste/' . $uuid,
        default => ''
    };

    // Sprawdzenie, czy `wasteCode` istnieje i dodanie go do URL tylko raz
    if (!empty($wasteCode)) {
        $baseUrl .= '?wasteCodeName=' . urlencode($wasteCode);
    }

    $entry['URL'] = $baseUrl;
}


unset($entry); // Usuwamy referencję dla bezpieczeństwa

//jeśli nie ma kart kpo do wysłania to kończymy
if (empty($karty_kpo_slownik)) {
    echo "<p>Brak kart do wyświetlenia i wysłania.</p>";
    exit;
}

$emailBody = '<html><head><meta charset="UTF-8"><title>Karty KPO wymagające czynności</title></head><body>';
$emailBody .= '<h2>Lista Kart KPO wymagających czynności</h2>';
$emailBody .= '<table border="1" cellpadding="5" cellspacing="0">';
$emailBody .= '<tr>
    <th>Card Status</th>
    <th>Card Number</th>
    <th>URL</th>
    <th>Kpo ID</th>
    <th>Last Modified</th>
    <th>Sender Name</th>
    <th>Waste Code</th>
</tr>';

foreach ($karty_kpo_slownik as $entry) {
    $rowColor = ($entry['cardStatus'] ?? '') === 'Odrzucona' ? 'background-color:#ffcccc;' : 'background-color:#e6f2ff;';
    $emailBody .= '<tr style="' . $rowColor . '">
        <td>' . htmlspecialchars($entry['cardStatus'] ?? '') . '</td>
        <td>' . htmlspecialchars($entry['cardNumber'] ?? '') . '</td>
        <td><a href="' . htmlspecialchars($entry['URL'] ?? '') . '" target="_blank">Link</a></td>
        <td>' . htmlspecialchars($entry['KpoId'] ?? '') . '</td>
        <td>' . htmlspecialchars($entry['kpoLastModifiedAt'] ?? '') . '</td>
        <td>' . htmlspecialchars($entry['senderFirstNameAndLastName'] ?? '') . '</td>
        <td>' . htmlspecialchars($entry['wasteCode'] ?? '') . '</td>
    </tr>';
}

$emailBody .= '</table></body></html>';

echo $emailBody; // Wyświetlenie tabeli na stronie

$to = 'k.madzia@fops.pl;j.boruta@fops.pl'; // Zmień na właściwy adres odbiorcy
$subject = "BDO - Lista Kart KPO wymagających czynności";

sendEmail($to, $subject, $emailBody, null, true);



?>