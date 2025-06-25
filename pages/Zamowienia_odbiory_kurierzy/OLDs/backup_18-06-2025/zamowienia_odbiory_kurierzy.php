<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$python_path = '/home/kmadzia/myenv/bin/python'; // Ścieżka do Pythona

$connection = db_connect_mysqli();
$connectionKM = db_connect_mysqli_KM_VM();



$data = json_decode($argv[1], true); //odbiera tablicę argumentów przekazaną ze skryptu petla_zczytywanie_danych.php

$zamowienie = $data['serial'];
$data_od = $data['data_od'];
$data_do = $data['data_do'];


//sprawdzamy czy numer listu jest w bazie PROSTO (bywa dla przesyłek, gdzie jest jeden list przewozowy)
$sql_listy_przewozowe_zapisane_w_bazie = "SELECT `Collections`.`post_number`,`Couriers`.`name` from  
    (((`prosto`.`orders` `Orders`
join `prosto`.`collections_orders` `CollectionOrders` on
    (`Orders`.`id` = `CollectionOrders`.`order_id`))
join `prosto`.`collections` `Collections` on
    (`CollectionOrders`.`collection_id` = `Collections`.`id`))
join `prosto`.`couriers` `Couriers` on
    (`Collections`.`courier_id` = `Couriers`.`id`))
where
    `Collections`.`serial` like 'OT%' and `Collections`.`post_number`  IS NOT NULL and Orders.serial= '$zamowienie'";

//echo 'SQL do pobrania listów przewozowych: ' . $sql_listy_przewozowe_zapisane_w_bazie . '<br>';

//echo 'To jest test: ' . $sql_listy_przewozowe_zapisane_w_bazie . '<br>'; 

$output = fetch_data($connection, $sql_listy_przewozowe_zapisane_w_bazie);



$listy_przewozowe = [];

foreach ($output[1] as $row) {  // Iteracja przez tablicę wyników
    $listy_przewozowe[] = [
        'firma_kurierska' => $row['name'] ?? null,
        'nr_listu_przewozowego' => $row['post_number'] ?? null
    ];
}


foreach ($listy_przewozowe as $list) { //sprawdza czy pobrane z bazy PROSTO numery listów przewozowych są w bazieKM - jeśli nie, to dodaje
    $nr_listu_przewozowego = $list['nr_listu_przewozowego'];
    $firma_kurierska = $list['firma_kurierska'];

    // Sprawdź, czy list już jest w bazie
    $sql_czy_list_jest_juz_w_bazieKM = "SELECT count(*) as count FROM km_base.gg_zamowienia_listy_przewozowe WHERE nr_listu_przewozowego='" . $nr_listu_przewozowego . "'";
    $result = fetch_data($connectionKM, $sql_czy_list_jest_juz_w_bazieKM);

    if ($result[1][0]['count'] == 0) {
        $sql_insert_do_bazyKM = $sql_insert_do_bazyKM = "INSERT INTO km_base.gg_zamowienia_listy_przewozowe 
        (zamowienie, nr_listu_przewozowego, file_id, firma_kurierska) 
        VALUES ('$zamowienie', '$nr_listu_przewozowego', 'z bazy PROSTO', '$firma_kurierska')";

        fetch_data($connectionKM, $sql_insert_do_bazyKM);
        echo "Dodano list z bazy PROSTO do bazyKM: $nr_listu_przewozowego<br>";
    } else {
        echo "List $nr_listu_przewozowego już istnieje w bazie.<br>";
    }
}
// Teraz $kurierzy zawiera tablicę z dwoma kluczami dla każdego wiersza z bazy
//print_r($kurierzy);

//$listy_przewozowe_zapisane_w_bazie = fetch_data($connection, $sql_listy_przewozowe_zapisane_w_bazie);


//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

// SQL do pobrania ID plików dla danego zlecenia
$sql = "SELECT DISTINCT files.id FROM prosto.orders orders
        JOIN prosto.files_orders files_orders ON orders.id = files_orders.order_id
        JOIN prosto.files files ON files_orders.file_id = files.id
        JOIN prosto.files_tokens files_tokens ON files.id = files_tokens.file_id
        WHERE orders.serial = '$zamowienie'";

//echo 'To jest SQL do pobrania ID plików: ' . $sql . '<br>';

$file_ids = fetch_data($connection, $sql);

$url_poczatek = 'https://prosto.fops.pl/index.php/files/download/';
$url_part2 = '?disposition=inline';

$lista_zlecenie_listy_przewozowe = []; // array of arrays: ['zlecenie' => ..., 'nr_listu_przewozowego' => ...]

foreach ($file_ids[1] as $file) {

    //sprawdzamy czy plik o takim file_id był już zczytywany - jeśli tak, to przeskakujemy do następnego file_id
    //$sql_file_id_check = 'SELECT COUNT(*) AS count FROM km_base.gg_zamowienia_listy_przewozowe WHERE file_id = ' . $file['id'];
    $sql_file_id_check = 'SELECT COUNT(*) AS count FROM km_base.gg_02_sprawdzone_pliki WHERE file_id = ' . $file['id'];
    $czy_file_id_byl_juz_zczytywany = fetch_data($connectionKM, $sql_file_id_check);
    if ($czy_file_id_byl_juz_zczytywany[1][0]['count'] > 0) {
        echo '<div class="content">Zapis już jest w bazie<br></div>';
        continue;    //jeśli już był zczytywany, to następny foreach
    }

    $file_id = $file['id'];

    // SQL do pobrania tokena pliku
    $sql = 'SELECT token FROM prosto.files_tokens WHERE file_id = ' . $file_id . ' ORDER BY created DESC LIMIT 1';
    $file_token = fetch_data($connection, $sql);


    $url = $url_poczatek . $file_id . '/' . $file_token[1][0]['token'] . $url_part2;

    // Zczytuje dane z PDFów - z załączników do zamówień
    $command = '/home/kmadzia/myenv/bin/python /home/kmadzia/www/pages/Zamowienia_odbiory_kurierzy/zamowienia_odbiory_kurierzy.py ' . escapeshellarg($url);
    $output = shell_exec($command);

    //echo 'To jest output: ' . $output . '<br>';

    $output = trim($output ?? ''); // Usunięcie zbędnych znaków nowej linii/spacji
    $lines = explode("\n", $output); // Podział na pojedyncze linie
    $listy_przewozowe = [];
    foreach ($lines as $line) {
        $parts = explode("|", $line); // Podział każdej linii na firmę + numer listu
        if (count($parts) == 2) {  // Upewnienie się, że mamy 2 elementy
            $listy_przewozowe[] = [
                'firma_kurierska' => $parts[0],
                'numer_listu_przewozowego' => $parts[1]
            ];
        }
    }

    foreach ($listy_przewozowe as $list) {    //drukujemy wynik na ekran i dodajemy rekordy do bazy
        $firma_kurierska = trim($list['firma_kurierska']);
        $numer_listu_przewozowego = trim($list['numer_listu_przewozowego']);

        echo empty($numer_listu_przewozowego) ? "To nie list przewozowy <br>" : "Numer listu przewozowego: $numer_listu_przewozowego<br>";

        // Dodajemy zapis do bazy tylko, jeśli numer listu przewozowego nie jest pusty
        if (!empty($numer_listu_przewozowego)) {
            $sql_insert = "INSERT INTO km_base.gg_zamowienia_listy_przewozowe (zamowienie, nr_listu_przewozowego, file_id, firma_kurierska) 
                       VALUES ('$zamowienie', '$numer_listu_przewozowego', $file_id, '$firma_kurierska')";

            fetch_data($connectionKM, $sql_insert);
            echo "Dodano list do bazy: $numer_listu_przewozowego<br>";
        }
    }
    //dodaje info do bazy, że plik został zczytany
    $sql_add_file_id_to_checked = "INSERT INTO km_base.gg_02_sprawdzone_pliki (file_id, serial, check_date) VALUES ('$file[id]', '$zamowienie', NOW())";
    fetch_data($connectionKM, $sql_add_file_id_to_checked);

    echo json_encode($listy_przewozowe);

}

?>