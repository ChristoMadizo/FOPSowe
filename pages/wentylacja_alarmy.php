<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection = db_connect_mysqli_KM_VM();

// Sprawdzanie ostatniej zmiany (on lub off) w wentylacji
$sql = "SELECT date_time, event_name FROM km_base.cc01_wentylacja_events WHERE event_name IN ('switch_on','switch_off') ORDER BY date_time DESC LIMIT 1;";
$fetch_result = fetch_data($connection, $sql);
$last_switch_modification_date = escapeshellarg($fetch_result[1][0]['date_time']);
$last_switch_modification_event = escapeshellarg($fetch_result[1][0]['event_name']);

// Sprawdzanie ostatniego włączenia wentylacji
$sql2 = "SELECT date_time, event_name FROM cc01_wentylacja_events WHERE event_name LIKE 'switch_on' ORDER BY date_time DESC LIMIT 1;";
$fetch_result2 = fetch_data($connection, $sql2);
$last_switch_on_date = escapeshellarg(isset($fetch_result2[1][0]['date_time']) ? $fetch_result2[1][0]['date_time'] : '');

$python_path = '/home/kmadzia/myenv/bin/python';

//pobranie danych z niezależnego czujnika temperatury wewnętrznej (czarny)
$command_temp_czarny_czujnik=$python_path . ' ' . '/home/kmadzia/www/scripts/pobranie_temperatury_czujnik_czarny.py';
$temp_wewnetrzna_czarny_czujnik= shell_exec("$command_temp_czarny_czujnik");
$temp_wewnetrzna_czarny_czujnik = trim($temp_wewnetrzna_czarny_czujnik);  // Usuwanie spacji
$temp_wewnetrzna_czarny_czujnik = str_replace(",", ".", $temp_wewnetrzna_czarny_czujnik);  // Zamiana przecinka na kropkę
$temp_wewnetrzna_czarny_czujnik = floatval($temp_wewnetrzna_czarny_czujnik);  // Konwersja na float

$script_path = '/home/kmadzia/www/scripts/monitorowanie_wentylacji.py'; //skrypt pobierający dane ze strony wentylacji

//pobranie danych ze strony wentylacji
$command = $python_path . ' ' . $script_path . ' ' . $last_switch_modification_date . ' ' . $last_switch_on_date . ' ' . $temp_wewnetrzna_czarny_czujnik;
$json_result = shell_exec("$command");
$data = json_decode($json_result, true);  


$update_queries = [];

$date_time = $data['date_time'];
$akcja = $data['akcja'];
$decision = $data['decision'];
$wentylacja_state = ($data['wentylacja_state'] === 'ON') ? 1 : 0;




// Iteracja przez dane JSON i przygotowanie INSERT dla temperatur
foreach ($data as $key => $value) {
    if (strpos($key, 'id_') === 0 || $key==='temperatura_czujnik_czarny') {
        $sensor_id = $key;
        $temperature = (float)$value;

        $query = "INSERT INTO cc01_wentylacja_events (sensor_id, date_time, event_name, remarks, temperature) VALUES ('$sensor_id', '$date_time', 'temperature_check', NULL, $temperature);";
        $update_queries[] = $query;
    }
}

// Dodanie `wentylacja_state` jako osobnego sensora
$query = "INSERT INTO cc01_wentylacja_events (sensor_id, date_time, event_name, remarks, temperature) VALUES ('wentylacja_state', '$date_time', 'temperature_check', NULL, $wentylacja_state);";
$update_queries[] = $query;

// Dla 'akcja' generujemy jedno zapytanie
$query = "INSERT INTO cc01_wentylacja_events (sensor_id, date_time, event_name, remarks, temperature) VALUES (NULL, '$date_time', '$akcja', NULL, NULL);";
$update_queries[] = $query;

// Wykonanie zapytań SQL na bazie danych
foreach ($update_queries as $query) {
    fetch_data($connection, $query);
}

// Wysyłka maila
$email_content = '';
foreach ($data as $key => $value) {
    $email_content .= "$key: $value\n";
}

sendEmail('k.madzia@fops.pl', $decision . ' ' . $akcja, $email_content);

echo "<div class='content'>$decision . ' ' . $akcja</div>";

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wykres temperatury</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<canvas id="lineChart"></canvas>

<script>
// const dataFromPHP = <?php echo json_encode($sensorData); ?>;

// Sprawdzenie poprawności danych w konsoli
console.log("Dane z PHP:", dataFromPHP);

const ctx = document.getElementById('lineChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dataFromPHP.wentylacja_state.dates.map(date => new Date(date).toLocaleString()), // Użycie wspólnej listy dat
        datasets: [
            {
                label: "Temperatura na zewnątrz",
                data: dataFromPHP.id_41_input_label.values,
                borderColor: "blue",
                fill: false
            },
            {
                label: "Temperatura w biurze (schody)",
                data: dataFromPHP.id_27_input_label.values,
                borderColor: "red",
                fill: false
            },
            {
                label: "Temperatura w biurze (kanał)",
                data: dataFromPHP.id_143_input_label.values,
                borderColor: "green",
                fill: false
            },
            {
                label: "Temperatura zadana",
                data: dataFromPHP.id_197_input_label.values,
                borderColor: "orange",
                fill: false
            },
            {
                label: "Stan wentylacji",
                data: dataFromPHP.wentylacja_state.values, // Nowy sensor traktowany jak inne czujniki
                borderColor: "black",
                fill: false,
                stepped: true // Lepsza wizualizacja skokowych zmian wentylacji
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,  
                position: 'right'
            }
        },
        scales: {
            x: { title: { display: true, text: 'Czas' } },
            y: { title: { display: true, text: 'Temperatura (°C)' } }
        }
    }
});
</script>

</body>
</html>
