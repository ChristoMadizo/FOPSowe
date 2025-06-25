<?php


function fetchPendingParcels(mysqli $connectionKM, string $firma): array
{
    $firma = $connectionKM->real_escape_string($firma);

    $query = "
        SELECT nr_listu_przewozowego 
        FROM km_base.gg_01_zamowienia_listy_przewozowe 
        WHERE firma_kurierska = '{$firma}' 
        AND (status_przesylki NOT IN('DELIVERED','FINAL','CANCELED') OR status_przesylki IS NULL) 
        AND data_dodania >= DATE_SUB(NOW(), INTERVAL 100 DAY)
    ";

    return fetch_data($connectionKM, $query);
}


function fetchFullGlsDataUpdateStatus(array $trackingNumbers): array
{
    $connectionKM = db_connect_mysqli_KM_VM(); // jeśli nieużywane, możesz usunąć
    $username = '6160106211';
    $password = 'GLShaslo33';

    $results = [];

    foreach ($trackingNumbers as $number) {
        $number = $number['nr_listu_przewozowego']; // wyciągamy numer listu
        $url = "https://api.gls-group.eu/public/v1/tracking/references/" . urlencode($number);
        $auth = base64_encode("$username:$password");

        $headers = [
            "Authorization: Basic $auth",
            "Accept: application/json"
        ];

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => implode("\r\n", $headers),
                "timeout" => 10
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            // nawet jeśli błąd — dodajemy nazwę kuriera
            $results[$number] = [
                'error' => "Błąd pobierania przesyłki",
                'kurier' => 'GLS'
            ];
            continue;
        }

        $data = json_decode($response, true);

        // dodajemy kuriera do zwróconej paczki
        $data['kurier'] = 'GLS';

        $results[$number] = $data;
    }

    return $results;
}

function buildFlatTrackingArray(array $results): array
{
    $result = [];

    foreach ($results as $numer_listu => $info) {
        if (!isset($info['parcels'][0])) {
            continue;
        }

        $parcel = $info['parcels'][0];
        $events = $parcel['events'] ?? [];
        $kurier = $info['kurier'] ?? 'nieznany'; // jeśli nie ma pola kurier, ustaw domyślne

        foreach ($events as $ev) {
            $result[] = [
                'kurier' => $kurier,
                'numer_listu_przewozowego' => $numer_listu,
                'timestamp' => $parcel['timestamp'] ?? null,
                'status' => $parcel['status'] ?? null,
                'event_timestamp' => $ev['timestamp'] ?? null,
                'event_description' => $ev['description'] ?? null,
                'event_location' => $ev['location'] ?? null,
                'event_country' => $ev['country'] ?? null,
                'event_code' => $ev['code'] ?? null
            ];
        }
    }

    return $result;
}

function extractLatestEvents(array $flatArray): array
{
    $flatArray_last_events = [];

    if (!empty($flatArray)) {
        foreach ($flatArray as $row) {
            $nr = $row['numer_listu_przewozowego'];
            $ts = strtotime($row['event_timestamp']);

            if (
                !isset($flatArray_last_events[$nr]) ||
                $ts > strtotime($flatArray_last_events[$nr]['event_timestamp'])
            ) {
                $flatArray_last_events[$nr] = $row;
            }
        }

        // Przekształcamy do tablicy indeksowanej liczbowo
        $flatArray_last_events = array_values($flatArray_last_events);
    }

    return $flatArray_last_events;
}







function updateShipmentStatuses(mysqli $connectionKM, array $flatArray_last_events): void
{
    $stmt = $connectionKM->prepare("
        UPDATE gg_01_zamowienia_listy_przewozowe
        SET 
            status_przesylki = ?,
            status_datestamp = ?,
            last_event_desc = ?,
            last_event_timestamp = ?
        WHERE 
            firma_kurierska = ? AND
            nr_listu_przewozowego = ?
    ");

    if (!$stmt) {
        die('Błąd przygotowania zapytania: ' . $connectionKM->error);
    }

    foreach ($flatArray_last_events as $row) {
        $status = $row['status'] ?? null;
        $statusDate = $row['timestamp'] ?? null;
        $eventDesc = $row['event_description'] ?? null;
        $eventTime = $row['event_timestamp'] ?? null;
        $kurier = $row['kurier'] ?? null;
        $numerListu = $row['numer_listu_przewozowego'] ?? null;

        $stmt->bind_param(
            "ssssss",
            $status,
            $statusDate,
            $eventDesc,
            $eventTime,
            $kurier,
            $numerListu
        );

        $stmt->execute();
    }

    $stmt->close();
}




