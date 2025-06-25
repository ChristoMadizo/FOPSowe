<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

//jedziemy według samochodów
//ściągamy po kolei wszystkie obowiązki dla samochodu, sprawdzamy kiedy był ostatni, kiedy ma być następny i czy jest już przypomnienie
//czyli przypomnienie musi być dla danego samochodu, dany typ obowiązku, data przypomnienia musi być większa od daty ostatniego obowiązku i mniejsza od daty następnego obowiązku

$connection = db_connect_mysqli_KM_VM();

$cars_list = fetch_data($connection, 'SELECT id,sredni_przebieg_miesieczny FROM ff_01_cars'); // Pobieramy listę samochodów

$cars_list = [1 => [['id' => 9]]]; // Tymczasowo ustawiamy na 2, aby testować na jednym samochodzie

//$car_id=2;  //tu trzeba będzie iterować po wszystkich samochodach
// Pobieramy listę cyklicznych zadań dla danego samochodu

foreach ($cars_list[1] as $car) {
    $car_id = $car['id']; // Poprawny dostęp do ID

    $car_lista_cyklicznych_zadan = fetch_data($connection, "SELECT * FROM ff_01b_cars_zadania_cykliczne WHERE car_id = $car_id");
    $car_sredni_przebieg_miesieczny = fetch_data($connection, "SELECT * FROM ff_01_cars WHERE id = $car_id");

    $current_date = date('Y-m-d'); // Dzisiejsza data

    foreach ($car_lista_cyklicznych_zadan[1] as $zadanie) {
        $nastepny_deadline = $zadanie['data_nastepny_daedline'];
        //sprawdza czy to zadanie oparte na dacie i czy jest już przeterminowane
        if ((empty($zadanie['przebieg_nastepny_deadline']) || $zadanie['przebieg_nastepny_deadline'] == 0) && $nastepny_deadline < $current_date) {
            if (!is_null($zadanie['zadanie_interwal_dni'])) {
                $new_deadline_date = date('Y-m-d', strtotime("$nastepny_deadline + {$zadanie['zadanie_interwal_dni']} days"));
                $column_to_update = "data_nastepny_deadline";
                $result = 'Będzie aktualizacja daty następnego deadline na ' . $new_deadline_date;

                // Aktualizacja w bazie danych
                $update_query = "UPDATE ff_01b_cars_zadania_cykliczne SET data_nastepny_daedline = '$new_deadline_date' WHERE id = " . $zadanie['id'];
                mysqli_query($connection, $update_query);
            }
        }
        //sprawdza czy to zadanie oparte na przebiegu
        if (!empty($zadanie['przebieg_nastepny_deadline'])) {
            // $column_to_update = "przebieg_nastepny_deadline";

            // Pobranie ostatniego stanu licznika
            $licznik_query = "SELECT serwis_date, stan_licznika FROM ff_02_cars_serwis_historia 
                      WHERE car_id = $car_id AND stan_licznika IS NOT NULL AND stan_licznika != 0 
                      ORDER BY serwis_date DESC LIMIT 1";
            $licznik_result = fetch_data($connection, $licznik_query);

            $ostatni_stan_licznika = $licznik_result[1][0]['stan_licznika'];
            $data_ostatniego_stanu_licznika = $licznik_result[1][0]['serwis_date'];

            // Obliczenie symulowanego przebiegu
            $miesiace_od_ostatniego_licznika = (strtotime($current_date) - strtotime($data_ostatniego_stanu_licznika)) / (30 * 24 * 60 * 60);
            $symulowany_aktualny_przebieg = $ostatni_stan_licznika + ($miesiace_od_ostatniego_licznika * $car_sredni_przebieg_miesieczny[1][0]['sredni_przebieg_miesieczny']);

            $przebieg_nastepny_deadline = fetch_data($connection, "SELECT przebieg_nastepny_deadline, zadanie_interwal_kilometry 
            FROM ff_01b_cars_zadania_cykliczne WHERE car_id = $car_id AND zadanie_typ = 'wymiana_oleju'");

            if ($symulowany_aktualny_przebieg > $przebieg_nastepny_deadline[1][0]['przebieg_nastepny_deadline']) {
                $new_deadline_km = $przebieg_nastepny_deadline[1][0]['przebieg_nastepny_deadline'] + $przebieg_nastepny_deadline[1][0]['zadanie_interwal_kilometry'];

                //poniżej oblicza za ile miesięcy trzeba wymienić olej - używa deadlinu w kilometrach, aktualnego symulowanego przebiegu i średniego przebiegu miesięcznego
                $symulowana_liczba_miesiecy_do_wymiany_oleju = ($new_deadline_km - $symulowany_aktualny_przebieg) / $car_sredni_przebieg_miesieczny[1][0]['sredni_przebieg_miesieczny'];
                $date = new DateTime($current_date);
                // Konwersja liczby miesięcy na sekundy (przy założeniu 30 dni w miesiącu)
                $sekundy_do_dodania = $symulowana_liczba_miesiecy_do_wymiany_oleju * 30 * 24 * 60 * 60;
                // Dodanie sekund do aktualnej daty
                $new_deadline_timestamp = strtotime($current_date) + $sekundy_do_dodania;
                // Konwersja na format daty
                $new_deadline_date = date('Y-m-d', $new_deadline_timestamp);


                // aktualizuje deadline w kilometrach i dacie
                $update_query = "UPDATE ff_01b_cars_zadania_cykliczne SET przebieg_nastepny_deadline = '$new_deadline_km' WHERE id = " . $zadanie['id'];
                mysqli_query($connection, $update_query);
                $update_query = "UPDATE ff_01b_cars_zadania_cykliczne SET data_nastepny_daedline = '$new_deadline_date' WHERE id = " . $zadanie['id'];
                mysqli_query($connection, $update_query);
            } else {
                $result = 'To zadanie oparte na przebiegu, ale nie ma potrzeby aktualizacji (niski przebieg)';
            }
        }

    }
}







//echo $car_lista_cyklicznych_zadan;

?>