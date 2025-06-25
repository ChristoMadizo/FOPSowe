<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$connectionPROSTO = db_connect_mysqli();
$connectionKM = db_connect_mysqli_KM_VM();

//poniższe, żeby wyświetlał na bieżąco zawartość
while (@ob_end_flush())
    ; // Czyści wszystkie poziomy buforowania
ob_implicit_flush(true); // Włącza wypychanie danych natychmiast




//-----------------------dodanie do bazy KM listów przewozowych z bazy PROSTO  - bierzemy zamówienia z ostatnich 100 dni------------------------

//pobieramy listy przewozowe z bazy PROSTO, które zostały utworzone w ciągu ostatnich 100 dni
// Pobranie listów z bazy PROSTO
$listy_przewozowe_zapisane_w_baziePROSTO_query = "
    SELECT Collections.created, Collections.post_number, Couriers.name, Orders.serial
    FROM prosto.orders AS Orders
    JOIN prosto.collections_orders AS CollectionOrders ON Orders.id = CollectionOrders.order_id
    JOIN prosto.collections AS Collections ON CollectionOrders.collection_id = Collections.id
    JOIN prosto.couriers AS Couriers ON Collections.courier_id = Couriers.id
    WHERE Collections.serial LIKE 'OT%' 
      AND Collections.post_number IS NOT NULL 
      AND Collections.created >= DATE_SUB(NOW(), INTERVAL 100 DAY)
";

$listy_przewozowe_zapisane_w_baziePROSTO_result = fetch_data($connectionPROSTO, $listy_przewozowe_zapisane_w_baziePROSTO_query);

// Pobranie istniejących listów z bazy KM
$listy_w_bazieKM_query = "SELECT nr_listu_przewozowego FROM km_base.gg_01_zamowienia_listy_przewozowe";
$listy_w_bazieKM_result = fetch_data($connectionKM, $listy_w_bazieKM_query);

$ilosc_dodanych_listow_z_bazy = 0; // Zmienna do zliczania dodanych listów

$existing_post_numbers = array_column($listy_w_bazieKM_result[1], 'nr_listu_przewozowego');

foreach ($listy_przewozowe_zapisane_w_baziePROSTO_result[1] as $row) {
    if (!in_array($row['post_number'], $existing_post_numbers)) {  //czyli jeśli nie ma jeszcze tego listu w bazie KM
        //pobieramy serial zamowienia
        $insert_query = "
                INSERT INTO km_base.gg_01_zamowienia_listy_przewozowe 
                (zamowienie, nr_listu_przewozowego, file_id, firma_kurierska, data_dodania) 
                VALUES (?, ?,'z bazy PROSTO', ?, NOW())";

        $stmt = $connectionKM->prepare($insert_query);
        $zamowienie = $row['serial'];
        $nr_listu = $row['post_number'];
        $file_id = 'z bazy PROSTO';
        $kurier = $row['name'];
        // Usuń znaki takie jak apostrof z wartości przed wstawieniem do bazy
        //$zamowienie = str_replace("'", "", $zamowienie);
        $nr_listu=  $connectionKM->real_escape_string($nr_listu);
        $kurier = $connectionKM->real_escape_string($kurier);

        $stmt->bind_param("sss", $zamowienie, $nr_listu, $kurier);
        $stmt->execute();
        $stmt->close();
        $ilosc_dodanych_listow_z_bazy++;
        echo "<br> Dodano list przewozowy: $nr_listu do bazy KM z bazy PROSTO.<br>";
    }
}

echo "<Dodano $ilosc_dodanych_listow_z_bazy listów przewozowych z bazy PROSTO do bazy KM.<br>";
ob_flush();
flush();

//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^dodanie do bazy KM listów przewozowych z bazy PROSTO  - bierzemy zamówienia z ostatnich 30 dni//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

//pobieranie najnowszego file_id z bazy KM
$najnowszy_fileid_query = "
    SELECT file_id 
    FROM km_base.gg_01_zamowienia_listy_przewozowe
    WHERE file_id IS NOT NULL
      AND file_id <> 'z bazy PROSTO'
    ORDER BY file_id DESC
    LIMIT 1
";
$najnowszy_fileid_result = fetch_data($connectionKM, $najnowszy_fileid_query);

//pobiera listę file_id z bazy PROSTO, które zostały dodane po ostatnim pobraniu (czyli których nie ma jeszcze w bazie KM)
//poniżej jest inner join z orders, żeby pobrać tylko te pliki, które są związane z zamówieniami
$lista_fileid_dodana_od_ostatniego_pobrania_query = "
    SELECT 
        files.id,
        files.name,
        types.type,
        Collections.type AS collection_type,
        o.serial AS order_serial
    FROM prosto.files AS files
    JOIN prosto.files_types AS types ON files.files_type_id = types.id
    JOIN prosto.files_orders AS fo ON fo.file_id = files.id
    JOIN prosto.orders AS o ON o.id = fo.order_id
    JOIN prosto.collections_orders AS co ON o.id = co.order_id
    JOIN prosto.collections AS Collections ON co.collection_id = Collections.id
    WHERE files.id > " . intval($najnowszy_fileid_result[1][0]['file_id']) . "
    ORDER BY files.id ASC
";


$lista_fileid_dodana_od_ostatniego_pobrania_result = fetch_data($connectionPROSTO, $lista_fileid_dodana_od_ostatniego_pobrania_query);

echo "<br><br>";
echo "Ilość plików do przetworzenia: " . count($lista_fileid_dodana_od_ostatniego_pobrania_result[1]) . "<br>";


$url_poczatek = 'https://prosto.fops.pl/index.php/files/download/';
$url_part2 = '?disposition=inline';

$liczba_dodanych_listow = 0; // Zmienna do zliczania dodanych listów
$ilosc_plikow_do_przetworzenia = count($lista_fileid_dodana_od_ostatniego_pobrania_result[1]); // Ilość plików do przetworzenia
$ilosc_plikow_przetworzonych = 0; // Lista do przechowywania przetworzonych plików

foreach ($lista_fileid_dodana_od_ostatniego_pobrania_result[1] as $file) {


    $file_id = $file['id'];

    // SQL do pobrania tokena pliku
    $sql = 'SELECT token FROM prosto.files_tokens WHERE file_id = ' . $file_id . ' ORDER BY created DESC LIMIT 1';
    $file_token = fetch_data($connectionPROSTO, $sql);


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

    $ilosc_plikow_przetworzonych++;

    //pobieramy zamówienie, do którego należy ten plik
    $zamowienie_query = "SELECT DISTINCT orders.serial FROM prosto.orders orders
        JOIN prosto.files_orders files_orders ON orders.id = files_orders.order_id
        JOIN prosto.files files ON files_orders.file_id = files.id
        JOIN prosto.files_tokens files_tokens ON files.id = files_tokens.file_id
        WHERE files.id= " . $file_id;
    $zamowienie_query_result = fetch_data($connectionPROSTO, $zamowienie_query);
    $zamowienie = $zamowienie_query_result[1][0]["serial"];

    foreach ($listy_przewozowe as $list) {    //drukujemy wynik na ekran i dodajemy rekordy do bazy
        $firma_kurierska = trim($list['firma_kurierska']);
        $numer_listu_przewozowego = trim($list['numer_listu_przewozowego']);

        //echo empty($numer_listu_przewozowego) ? "To nie list przewozowy <br>" : "Numer listu przewozowego: $numer_listu_przewozowego<br>";

        // Dodajemy zapis do bazy tylko, jeśli numer listu przewozowego nie jest pusty
        if (!empty($numer_listu_przewozowego)) {
            $sql_insert = "INSERT INTO km_base.gg_01_zamowienia_listy_przewozowe (zamowienie, nr_listu_przewozowego, file_id, firma_kurierska, data_dodania) 
                       VALUES ('$zamowienie', '$numer_listu_przewozowego', $file_id, '$firma_kurierska', NOW())";

            fetch_data($connectionKM, $sql_insert);
            //echo "Dodano list do bazy: $numer_listu_przewozowego<br>";
            $liczba_dodanych_listow++;

        }
    }
    //dodaje info do bazy, że plik został zczytany
    //$sql_add_file_id_to_checked = "INSERT INTO km_base.gg_02_sprawdzone_pliki (file_id, serial, check_date) VALUES ('$file[id]', '$zamowienie', NOW())";
    //fetch_data($connectionKM, $sql_add_file_id_to_checked);

    //echo json_encode($listy_przewozowe);

    //echo "Testujemy echo z końca." . $file_id . "<br>";

    echo $file_id . " / " . $zamowienie . " / " . $file['collection_type'] . " / " . $file['type'] . " / " . $file['name'] .  " / Przetworzono plik numer " . $ilosc_plikow_przetworzonych . " ,zamowienie: " . $zamowienie . ") z " . $ilosc_plikow_do_przetworzenia . "(dodano " . $liczba_dodanych_listow . " listów przewozowych)<br>";

}

echo "Ilość dodanych listów: " . $liczba_dodanych_listow . "<br>";



