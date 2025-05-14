<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


$connection=db_connect_mysqli_KM_VM();

//sprawdzanie ostatniej zmiany (on lub off) w wentylacji
$sql="SELECT date_time,event_name FROM km_base.cc01_wentylacja_events WHERE event_name in ('switch_on','switch_off') ORDER BY date_time DESC LIMIT 1;";
$fetch_result=fetch_data($connection, $sql);
$last_switch_modification_date=escapeshellarg($fetch_result[1][0]['date_time']);
$last_switch_modification_event=escapeshellarg($fetch_result[1][0]['event_name']);

//sprawdzanie ostatniego włączenia wentylacji (czyli uruchomienie ostatniego wietrzenia)
$sql2="SELECT date_time,event_name FROM cc01_wentylacja_events WHERE event_name LIKE 'switch_on' ORDER BY date_time DESC LIMIT 1;";
$fetch_result2=fetch_data($connection, $sql2);
$last_switch_on_date = escapeshellarg(isset($fetch_result2[1][0]['date_time']) ? $fetch_result2[1][0]['date_time'] : '');


$python_path = '/home/kmadzia/myenv/bin/python'; // Ścieżka do Pythona w myenv
$script_path = '/home/kmadzia/www/scripts/monitorowanie_wentylacji.py'; // Ścieżka do skryptu Pythona

$command=$python_path  . ' ' .  $script_path  . ' ' .  $last_switch_modification_date  . ' ' . $last_switch_on_date;

$json_result = shell_exec("$command");  //do zmiennej trafi to co jest ostatnim wyprintowaną zawartością w skrypcie pythona
$data = json_decode($json_result, true);

//echo "<div class='content'>$data</div>";

$update_queries = [];  // Tablica do przechowywania zapytań SQL

$date_time = $data['date_time'];
$akcja = $data['akcja'];
$decision = $data['decision'];
$wentylacja_state = ($data['wentylacja_state'] === 'ON') ? 1 : 0;

// Iteracja przez dane JSON i przygotowanie INSERT dla temperatur
foreach ($data as $key => $value) {
    // Jeśli klucz zaczyna się od 'id_'
    if (strpos($key, 'id_') === 0) {
        // Przygotowanie wartości do zapytania SQL
        $sensor_id = $key;
        $temperature = (float)$value;  // Konwersja na decimal

        // Przygotowanie zapytania UPDATE
        $query = "INSERT INTO cc01_wentylacja_events (sensor_id, date_time, event_name, remarks, temperature,wentylacja_state) VALUES ('$sensor_id', '$date_time', 'temperature_check', NULL, $temperature,$wentylacja_state);";
        $update_queries[] = $query;
    }
}

// Dla 'akcja' generujemy jedno zapytanie
$query = "INSERT INTO cc01_wentylacja_events (sensor_id, date_time, event_name, remarks, temperature,wentylacja_state) VALUES (NULL, '$date_time', '$akcja', NULL, NULL,$wentylacja_state);";
$update_queries[] = $query;

// Teraz możemy wykonać zapytania SQL na bazie danych (tutaj tylko wypisuję zapytania)
foreach ($update_queries as $query) {
    fetch_data($connection, $query);  // Tutaj można wykonać zapytanie do bazy danych
}


$email_content = '';

foreach ($data as $key => $value) {
    $email_content .= "$key: $value\n";
}

sendEmail('k.madzia@fops.pl',$decision . ' ' . $akcja,$email_content);

echo "<div class='content'>$decision . ' ' . $akcja</div>";

?>