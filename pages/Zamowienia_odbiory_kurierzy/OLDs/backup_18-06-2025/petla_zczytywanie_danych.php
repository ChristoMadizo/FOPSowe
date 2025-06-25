<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection = db_connect_mysqli();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Kod uruchamia się tylko po kliknięciu buttona

    // Pobranie dat z formularza lub ustawienie domyślnych wartości
    $data_od = $_POST['data_od'] ?? date('Y-m-d', strtotime('-5 days'));
    $data_do = $_POST['data_do'] ?? date('Y-m-d');

    // Pobranie zamówień z bazy
    $lista_zamowien_sql = "SELECT serial FROM prosto.orders WHERE created BETWEEN '$data_od 00:00:00' AND '$data_do 23:59:59'"; //serial ='Z-8664-JK-0525'"; 
    $lista_zamowien_output = fetch_data($connection, $lista_zamowien_sql);



    echo "<h3>🔄 Rozpoczęto przetwarzanie zamówień:</h3><br>";
    flush();  // Wysyłanie pierwszej części wyników

    foreach ($lista_zamowien_output[1] as $zamowienie) {
        $orderSerial = $zamowienie['serial'];

        //echo "⏳ Szukam numerów listów przewozowych dla zlecenia: <b>$orderSerial</b><br>";
        //flush();  // Natychmiastowe wyświetlenie informacji

        // Przekazanie tablicy argumentów do wewnętrznego skryptu w formie JSON
        $args = escapeshellarg(json_encode(['serial' => $orderSerial, 'data_od' => $data_od, 'data_do' => $data_do]));
        $output = shell_exec("php /home/kmadzia/www/pages/Zamowienia_odbiory_kurierzy/zamowienia_odbiory_kurierzy.php $args");

        // Parsowanie JSON zwróconego przez wewnętrzny skrypt
        //$listy_przewozowe = json_decode($output, true);

        /*echo "✅ Numery listów znalezione dla zlecenia <b>$orderSerial</b>:<br>";
        if (isset($listy_przewozowe) && is_array($listy_przewozowe)) {
            foreach ($listy_przewozowe as $list) {
            echo "- <b>{$list['firma_kurierska']}</b>: {$list['numer_listu_przewozowego']}<br>";
            }
        } else {
            echo "Brak (nowych) numerów listów przewozowych.<br>";
        }
        echo "<hr>";
        flush();  // Wyświetlanie wyników na bieżąco
    }

    echo "<h3>✅ Wszystkie zamówienia przetworzone!</h3>";
    flush();*/
    }
}

?>

<body>
    <h1>Zamówienia i odbiory kurierów</h1>
    <form method="post">
        <label for="data_od">Data od:</label>
        <input type="date" name="data_od" value="<?= date('Y-m-d', strtotime('-5 days')) ?>">
        <br>
        <label for="data_do">Data do:</label>
        <input type="date" name="data_do" value="<?= date('Y-m-d') ?>">
        <br>
        <button type="submit">Pobierz dane</button>
    </form>
</body>