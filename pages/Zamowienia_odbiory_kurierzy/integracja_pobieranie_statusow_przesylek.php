<?php
//szuka przesyłek w bazie KM, które mają status inny niż dostarczona i aktualizuje ich statusy na podstawie danych dla różnych kurierów.
//Dane bierze z różnych API kurierów

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
require '/home/kmadzia/www/pages/Zamowienia_odbiory_kurierzy/integracja_funkcje.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$connectionKM = db_connect_mysqli_KM_VM();

//-------------------------------sekcja dla GLS-----------------------------------
//pobiera listę przesyłek GLS, które są w trakcie dostarczenia i nie zostały jeszcze dostarczone
$kurier='GLS';
$lista_przesylek_w_doreczeniu = fetchPendingParcels($connectionKM,$kurier);
//wywołanie funkcji 
$results=$lista_przesylek_w_doreczeniu = fetchFullGlsDataUpdateStatus($lista_przesylek_w_doreczeniu[1]);

$flatArray = buildFlatTrackingArray($results);  //tablica zawiera dane o przesyłkach w formie płaskiej, ale dla wszystkich etapów przesyłki (events)

//wynikowa tablica zawiera tylko ostatnie zdarzenia dla każdego numeru listu przewozowego
$flatArray_last_events=extractLatestEvents($flatArray);

//aktualizacja statusów przesyłek w bazie KM
updateShipmentStatuses($connectionKM,$flatArray_last_events);


//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^sekcja dla GLS^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^










if (!empty($flatArray_last_events)) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';

    // Nagłówki tabeli generowane dynamicznie
    echo '<tr>';
    foreach (array_keys($flatArray_last_events[0]) as $colName) {
        echo '<th>' . htmlspecialchars($colName) . '</th>';
    }
    echo '</tr>';

    // Dane
    foreach ($flatArray_last_events as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars((string)$value) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
} else {
    echo 'Brak danych do wyświetlenia.';
}


