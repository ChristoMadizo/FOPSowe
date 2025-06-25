<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Połączenie z bazą danych
$connection = db_connect_mysqli_KM_VM();
// Pobranie wartości z formularza - jeśli nie ma, ustawiamy domyślne 
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d H:i:s', strtotime('-48 hours'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d H:i:s');


// Sprawdzenie, czy wartości są dostępne
$whereCondition = "WHERE event_name='temperature_check'";
if ($date_from && $date_to) {
    $whereCondition .= " AND date_time BETWEEN '$date_from' AND '$date_to'";
}

$sql = "SELECT sensor_id, DATE_FORMAT(date_time, '%Y-%m-%d %H:%i:%s') AS date_time, temperature 
        FROM km_base.cc01_wentylacja_events 
        $whereCondition
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
<body style="margin-top: 80px;">

<form method="get" action="" style="margin-bottom: 20px;">
    <input type="hidden" name="page" value="wykres_temperatury">   <!--bez tego adres URL nie będzie poprawny-->

    <label for="date_from">Data od:</label>
    <input type="datetime-local" id="date_from" name="date_from" value="<?php 
        echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : date('Y-m-d\TH:i', strtotime('-48 hours')); 
    ?>">

    <label for="date_to">Data do:</label>
    <input type="datetime-local" id="date_to" name="date_to" value="<?php 
        echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : date('Y-m-d\TH:i'); 
    ?>">

    <button type="submit">Generuj wykres</button>
</form>




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
