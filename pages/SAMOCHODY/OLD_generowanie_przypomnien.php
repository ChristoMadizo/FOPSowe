<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

//jedziemy według samochodów
//ściągamy po kolei wszystkie obowiązki dla samochodu, sprawdzamy kiedy był ostatni, kiedy ma być następny i czy jest już przypomnienie
//czyli przypomnienie musi być dla danego samochodu, dany typ obowiązku, data przypomnienia musi być większa od daty ostatniego obowiązku i mniejsza od daty następnego obowiązku

$connection = db_connect_mysqli_KM_VM();

//$cars_list=fetch_data($connection, 'SELECT id FROM ff_01_cars'); // Pobieramy listę samochodów

$cars_list = [1 => [['id' => 9]]]; // Tymczasowo ustawiamy na 2, aby testować na jednym samochodzie

foreach ($cars_list[1] as $car) {
    $car_id = $car['id'];

    // Pobranie listy cyklicznych zadań
    $car_lista_cyklicznych_zadan = fetch_data($connection, "SELECT zadanie_typ, data_nastepny_daedline,przebieg_nastepny_deadline FROM ff_01b_cars_zadania_cykliczne WHERE car_id = $car_id");
    // Pobranie listy przypomnień
    $car_lista_przypomnien = fetch_data($connection, "SELECT zadanie_typ, zadanie_deadline FROM ff_02b_cars_przypomnienia WHERE car_id = $car_id");

    // Tworzymy tablicę istniejących przypomnień dla sprawdzenia
    $przypomnienia_map = [];
    foreach ($car_lista_przypomnien[1] as $przypomnienie) {
        $przypomnienia_map[$przypomnienie['zadanie_typ']][] = $przypomnienie['zadanie_deadline'];
    }

    // Iteracja po cyklicznych zadaniach
foreach ($car_lista_cyklicznych_zadan[1] as $zadanie) {
    if ($zadanie['zadanie_typ'] === 'wymiana_oleju') {  //nie bierzemy pod uwagę wymiany oleju - przypomnienia dla wymiany oleju ogarniam w skrypcie update_data_nastepnego_deadline.php
        //ponieważ zależą w dynamiczny sposób od przebiegu
        continue;  //czyli idzie do następnej iteracji
    }
    $zadanie_typ = $zadanie['zadanie_typ'];
    $deadline = $zadanie['data_nastepny_daedline'];
    if (!$deadline) {
        continue; // Jeśli zadanie cykliczne nie ma daty deadline, pomijamy je
    }

    // Konwersja daty do timestampu
    $deadline_timestamp = strtotime($deadline);
    $month_before = strtotime('-1 month', $deadline_timestamp);
    $month_after = strtotime('+1 month', $deadline_timestamp);

    // Sprawdzamy, czy istniejące przypomnienie mieści się w zakresie +/- miesiąca
    if (!isset($przypomnienia_map[$zadanie_typ]) || 
        !array_filter($przypomnienia_map[$zadanie_typ], function ($existing_deadline) use ($month_before, $month_after) {
            $existing_timestamp = strtotime($existing_deadline);
            return $existing_timestamp >= $month_before && $existing_timestamp <= $month_after;
        })) {

        // Wstawienie nowego przypomnienia
        $insert_query = "INSERT INTO ff_02b_cars_przypomnienia (car_id, zadanie_typ, zadanie_deadline) VALUES ('$car_id', '$zadanie_typ', '$deadline')";
        mysqli_query($connection, $insert_query);
    } 
}


echo "Przypomnienia zostały sprawdzone i zaktualizowane.";
}
?>