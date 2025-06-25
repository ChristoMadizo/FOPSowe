<?php
//POBIERA DWA NAJNOWSZE POMIARY (NIE Z TEGO SAMEGO DNIA) I OBLICZA ŚREDNI PRZEBIEG MIESIĘCZNY


require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Połączenie z bazą danych


//----------------sprawdzam czy jest mail z podanym przebiegiem - jeśli jest, to dodaję dane do bazy danych-------------------

// Pobierz wszystkie wiadomości ze skrzynki odbiorczej

$emails = checkEmails(
    "serwer1400163.home.pl",
    "k.madzia@fops.pl",
    "MojeHaslo33",
    null, // Usunięcie filtrowania po folderze (będzie pobierać z wszystkich dostępnych)
    10,
    null, // Usunięcie filtrowania po nadawcy (będzie pobierać od wszystkich nadawców)
    "Przypomnienie o sprawdzeniu przebiegu dla samochodu",
    null,
    "ALL"
);


// Wyświetlenie zwróconych danych - tylko wiadomości z folderu INBOX
$wyniki = [];

foreach ($emails as $email) {
    if ($email['nadawca'] === "FOPS <k.madzia@fops.pl>") {
        continue; // Pomijanie wiadomości od określonego nadawcy
    }

    // Pobranie numeru rejestracyjnego z tytułu (wartość w nawiasach)
    preg_match('/\(([^)]+)\)/', $email['tytul'], $matches);
    $car_numer_rejestracyjny = $matches[1] ?? "Nie znaleziono";

    // Pobranie przebiegu z początku treści (pierwsza liczba na początku wiadomości)
    $tresc_czysta = trim(html_entity_decode(strip_tags($email['tresc'])));
    $linijki = preg_split('/\r\n|\r|\n/', $tresc_czysta);
    $stan_licznika = preg_replace('/\D/', '', trim($linijki[0] ?? "Nie znaleziono"));


    // Dodanie wyniku do tablicy
    $wyniki[] = [
        'data' => substr($email['data'], 0, 10),
        'nr_rejestracyjny' => $car_numer_rejestracyjny,
        'stan_licznika' => preg_replace('/\D/', '', $stan_licznika)
    ];
}

//^^^^^^^^^^^^^^^^sprawdzam czy jest mail z podanym przebiegiem^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


//-------------------------dodaję kolumnę car_id do danych z maila
$connection = db_connect_mysqli_KM_VM();
$cars_list = fetch_data($connection, "SELECT id, nr_rejestracyjny FROM ff_01_cars");

// Tworzymy mapę nr_rejestracyjny -> car_id dla szybkiego wyszukiwania
$car_map = [];
foreach ($cars_list[1] as $car) {
    $clean_nr = preg_replace('/\s+/', '', trim($car['nr_rejestracyjny']));
    $car_map[$clean_nr] = $car['id'];
}

// Iterujemy przez istniejącą tablicę $wyniki i dodajemy car_id
foreach ($wyniki as &$item) {
    $clean_nr_rejestracyjny = preg_replace('/\s+/', '', trim($item['nr_rejestracyjny']));
    $item['car_id'] = $car_map[$clean_nr_rejestracyjny] ?? null;
}

//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^dodaję kolumnę car_id do danych z maila



//-----------------------sprawdzam czy dane zczytanie przebiegu jest dodane do bazy - jeśli nie, to INSERT

foreach ($wyniki as $item) {
    $car_id = intval($item['car_id']);
    $stan_licznika = intval($item['stan_licznika']);
    $serwis_date = $connection->real_escape_string($item['data']); // Pobranie daty z $wyniki

    // Sprawdzenie, czy wpis już istnieje
    $query = "SELECT COUNT(*) AS count FROM ff_02_cars_serwis_historia WHERE car_id = $car_id AND stan_licznika = $stan_licznika";
    $result = $connection->query($query)->fetch_assoc();

    if ($result['count'] == 0) { // Jeśli nie znaleziono rekordu, wykonaj INSERT
        $insert_query = "INSERT INTO ff_02_cars_serwis_historia 
                         (car_id, serwis_date, serwis_uwagi1, serwis_uwagi2, serwis_firma, zadanie_typ, stan_licznika) 
                         VALUES ($car_id, '$serwis_date', '', '', '', 'stan_licznika', $stan_licznika)";
        $connection->query($insert_query);
    }
}

//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^sprawdzam czy dane zczytanie przebiegu jest dodane do bazy - jeśli nie, to dodajemy












foreach ($cars_list[1] as $car) {
    $car_id = $car['id'];

    $query_historia_napraw = "
        SELECT stan_licznika, serwis_date 
        FROM ff_02_cars_serwis_historia 
        WHERE car_id = $car_id AND stan_licznika IS NOT NULL AND stan_licznika > 0 
        ORDER BY serwis_date DESC
    ";

    // Pobranie danych
    $records = fetch_data($connection, $query_historia_napraw);

    if (count($records[1]) < 2) {
        echo "Za mało danych do obliczeń dla car_id $car_id.<br>";
        continue;
    }

    // Pobranie pierwszego rekordu (najnowszego)
    $latest_record = $records[1][0];

    // Znalezienie kolejnego rekordu z **inną datą**
    $previous_record = null;
    foreach ($records[1] as $record) {
        if ($record['serwis_date'] != $latest_record['serwis_date']) {
            $previous_record = $record;
            break;
        }
    }

    if ($previous_record === null) {
        echo "Brak wcześniejszego rekordu o innej dacie dla car_id $car_id.<br>";
        continue;
    }

    $start_km = $previous_record['stan_licznika'];
    $end_km = $latest_record['stan_licznika'];
    $start_date = new DateTime($previous_record['serwis_date']);
    $end_date = new DateTime($latest_record['serwis_date']);

    // Obliczenie różnicy kilometrów
    $km_difference = $end_km - $start_km;

    // Obliczenie liczby miesięcy między datami
    $interval = $start_date->diff($end_date);
    $months_difference = $interval->y * 12 + $interval->m;

    if ($months_difference == 0) {
        echo "Nie można obliczyć średniego przebiegu miesięcznego dla car_id $car_id.<br>";
        continue;
    }

    // Obliczenie średniego przebiegu miesięcznego
    $avg_km_per_month = round($km_difference / $months_difference, 0);

    // Aktualizacja tabeli ff_01_cars
    $query_update = "
        UPDATE ff_01_cars 
        SET sredni_przebieg_miesieczny = $avg_km_per_month 
        WHERE id = $car_id
    ";

    // Wykonanie zapytania UPDATE
    fetch_data($connection, $query_update);

    echo "Średni przebieg miesięczny dla car_id $car_id wynosi: $avg_km_per_month km/miesiąc<br>";
}

?>