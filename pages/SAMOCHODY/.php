<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

//jedziemy według samochodów
//ściągamy po kolei wszystkie obowiązki dla samochodu, sprawdzamy kiedy był ostatni, kiedy ma być następny i czy jest już przypomnienie
//czyli przypomnienie musi być dla danego samochodu, dany typ obowiązku, data przypomnienia musi być większa od daty ostatniego obowiązku i mniejsza od daty następnego obowiązku

$connection = db_connect_mysqli_KM_VM();


$car_id = 2;  //tu trzeba będzie iterować po wszystkich samochodach
// Pobranie listy cyklicznych zadań
$car_lista_cyklicznych_zadan = fetch_data($connection, "SELECT event_type, data_nastepny_daedline FROM ff_01b_cars_zadania_cykliczne WHERE car_id = $car_id");

// Pobranie listy przypomnień
$car_lista_przypomnien = fetch_data($connection, "SELECT typ_zadania, zadanie_deadline FROM ff_01b_cars_przypomnienia WHERE car_id = $car_id");

// Tworzymy tablicę istniejących przypomnień dla sprawdzenia
$przypomnienia_map = [];
foreach ($car_lista_przypomnien as $przypomnienie) {
    $przypomnienia_map[$przypomnienie['typ_zadania']][] = $przypomnienie['zadanie_deadline'];
}

// Iteracja po cyklicznych zadaniach
foreach ($car_lista_cyklicznych_zadan as $zadanie) {
    $event_type = $zadanie['event_type'];
    $deadline = $zadanie['data_nastepny_daedline'];

    if (!$deadline) {
        $deadline = date('Y-m-d'); // Jeśli brak wartości, ustawiamy na dzisiejszą datę
    }

    // Sprawdzamy, czy już istnieje przypomnienie dla tego event_type i tej daty
    if (!isset($przypomnienia_map[$event_type]) || !in_array($deadline, $przypomnienia_map[$event_type])) {
        // Wstawienie nowego przypomnienia
        $insert_query = "INSERT INTO ff_01b_cars_przypomnienia (cars_id, typ_zadania, zadanie_deadline) VALUES ('$car_id', '$event_type', '$deadline')";
        mysqli_query($connection, $insert_query);
    }
}

echo "Przypomnienia zostały sprawdzone i zaktualizowane.";
?>