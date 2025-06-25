<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection = db_connect_mysqli();
$connectionKM = db_connect_mysqli_KM_VM();


//-----------------------dodanie do bazy KM listów przewozowych z bazy PROSTO ------------------------



    // Pobranie zamówień z bazy
    $lista_zamowien_sql = "SELECT serial FROM prosto.orders WHERE created BETWEEN '$data_od 00:00:00' AND '$data_do 23:59:59'"; //serial ='Z-8664-JK-0525'"; 
    $lista_zamowien_output = fetch_data($connection, $lista_zamowien_sql);


    
    //^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

    //przekształacam $lista_zamowien_output w stringi do użycia w SQL
    foreach ($lista_zamowien_output[1] as $zamowienie) {
        if (isset($zamowienie['serial'])) {
            $serials[] = "'" . $zamowienie['serial'] . "'"; // Apostrofy dla SQL
        }
    }
    $zamowienia_lista_do_in = implode(", ", $serials);

    $lista_plikow_w_zamowieniach_sql = "SELECT DISTINCT files.id FROM prosto.orders orders
        JOIN prosto.files_orders files_orders ON orders.id = files_orders.order_id
        JOIN prosto.files files ON files_orders.file_id = files.id
        JOIN prosto.files_tokens files_tokens ON files.id = files_tokens.file_id
        WHERE orders.serial IN ($zamowienia_lista_do_in)";

    $lista_plikow_w_zamowieniach_output = fetch_data($connection, $lista_plikow_w_zamowieniach_sql);

    echo "<h3>🔄 Rozpoczęto przetwarzanie zamówień:</h3><br>";
    flush();  // Wysyłanie pierwszej części wyników


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