<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Połączenie z bazą danych
$connection = db_connect_mysqli_KM_VM();
$sql = "SELECT sensor_id, DATE_FORMAT(date_time, '%Y-%m-%d %H:%i:%s') AS date_time, temperature 
        FROM km_base.cc01_wentylacja_events 
        WHERE event_name='temperature_check' and  date_time > NOW() - INTERVAL 17 HOUR
        ORDER BY date_time ASC";

$dane = fetch_data($connection, $sql);

// Inicjalizujemy tablice dla 4 sensorów i stanu wentylacji
$sensorData = [
    'id_27_input_label' => ['dates' => [], 'values' => []],  //temp. w biurze (schody)  
    'id_41_input_label' => ['dates' => [], 'values' => []],  //temp. na zewnątrz
    'id_143_input_label' => ['dates' => [], 'values' => []], //temp. w biurze (kanał)
    'id_197_input_label' => ['dates' => [], 'values' => []], //temp. zadana
    'wentylacja_state' => ['dates' => [], 'values' => []],   //stan wentylacji (0/1)
    'temperatura_czujnik_czarny' => ['dates' => [], 'values' => []],   //temp_wewnętrzna czarny czujnik
];


// Podział danych na oddzielne serie
foreach ($dane[1] as $row) {
    $sensorData[$row['sensor_id']]['dates'][] = $row['date_time']; // Format daty
    $sensorData[$row['sensor_id']]['values'][] = floatval($row['temperature']); // Konwersja na liczbę
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wykres temperatury</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

var_dump($sensorData); // Debugging - wyświetlenie danych
<canvas id="lineChart"></canvas>

<script>
// Pobranie danych z PHP i przekazanie do JavaScript
const dataFromPHP = <?php echo json_encode($sensorData); ?>;



const ctx = document.getElementById('lineChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dataFromPHP.id_41_input_label.dates.map(date => new Date(date).toLocaleString()), // Formatowanie dat
        datasets: [
            {
                label: "Temperatura na zewnątrz", // Ręcznie ustawiona legenda
                data: dataFromPHP.id_41_input_label.values,
                borderColor: "blue",
                fill: false
            },
            {
                label: "Temperatura w biurze (schody)", // Ręcznie ustawiona legenda
                data: dataFromPHP.id_27_input_label.values,
                borderColor: "red",
                fill: false
            },
            {
                label: "Temperatura w biurze (kanał)", // Ręcznie ustawiona legenda
                data: dataFromPHP.id_143_input_label.values,
                borderColor: "orange",
                fill: false
            },
            {
                label: "Temperatura zadana", // Ręcznie ustawiona legenda
                data: dataFromPHP.id_197_input_label.values,
                borderColor: "green",
                fill: false
            },
            {
                label: "Stan wentylacji", // Ręcznie ustawiona legenda
                data: dataFromPHP.wentylacja_state.values,
                borderColor: "light blue",
                fill: false
            },
            {
                label: "Temperatura wewn (czarny czujnik)", // Ręcznie ustawiona legenda
                data: dataFromPHP.temperatura_czujnik_czarny.values,
                borderColor: "brown",
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,  
                position: 'right' // Możesz zmienić na 'bottom', 'left', 'right'
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
