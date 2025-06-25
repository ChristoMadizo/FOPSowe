<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';

//jedziemy według samochodów
//ściągamy po kolei wszystkie obowiązki dla samochodu, sprawdzamy kiedy był ostatni, kiedy ma być następny i czy jest już przypomnienie
//czyli przypomnienie musi być dla danego samochodu, dany typ obowiązku, data przypomnienia musi być większa od daty ostatniego obowiązku i mniejsza od daty następnego obowiązku

$connection = db_connect_mysqli_KM_VM();

$cars_list = fetch_data($connection, 'SELECT id,sredni_przebieg_miesieczny FROM ff_01_cars'); // Pobieramy listę samochodów

//$cars_list = [1 => [['id' => 9]]]; // Tymczasowo ustawiamy na 2, aby testować na jednym samochodzie

//$car_id=2;  //tu trzeba będzie iterować po wszystkich samochodach
// Pobieramy listę cyklicznych zadań dla danego samochodu

foreach ($cars_list[1] as $car) {
    $car_id = $car['id']; // Poprawny dostęp do ID
    przypomnienia_spisywanie_licznikow($car_id);   //wywołujemy osobną funkcję, która przypomina o sprawdzaniu liczników
    //$car_id = 7;

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

                // aktualizuje datę następnego deadline w ff_01b_cars_zadania_cykliczne
                $update_query = "UPDATE ff_01b_cars_zadania_cykliczne SET data_nastepny_daedline = '$new_deadline_date' WHERE id = " . $zadanie['id'];
                mysqli_query($connection, $update_query);

                //dodaje PRZYPOMNIENIE o tym zadaniu do ff_02b_cars_przypomnienia
                $update_query = "INSERT INTO ff_02b_cars_przypomnienia
                (id, car_id, zadanie_typ, zadanie_deadline, zadanie_uwagi1, zadanie_uwagi2, status_przypomnienia) VALUES
                (NULL, $car_id, '{$zadanie['zadanie_typ']}', '$new_deadline_date', '', '', 'do_zrobienia')";
                mysqli_query($connection, $update_query);
            }
        }
        //sprawdza czy to zadanie oparte na PRZEBIEG (czyli w praktyce WYMIANA OLEJU I ROZRZĄDU)
        if (!empty($zadanie['przebieg_nastepny_deadline'])) {
            // $column_to_update = "przebieg_nastepny_deadline";

            // Pobranie ostatniego stanu licznika
            $licznik_query = "SELECT serwis_date, stan_licznika FROM ff_02_cars_serwis_historia 
                      WHERE car_id = $car_id AND stan_licznika IS NOT NULL AND stan_licznika != 0 
                      ORDER BY serwis_date DESC LIMIT 1";
            $licznik_result = fetch_data($connection, $licznik_query);

            $ostatni_stan_licznika = $licznik_result[1][0]['stan_licznika'];
            $data_ostatniego_stanu_licznika = $licznik_result[1][0]['serwis_date'];
            $sredni_przebieg_miesieczny = $car_sredni_przebieg_miesieczny[1][0]['sredni_przebieg_miesieczny'];
            $zadanie_typ = $zadanie['zadanie_typ'];

            // Obliczenie symulowanego przebiegu
            $miesiace_od_ostatniego_licznika = (strtotime($current_date) - strtotime($data_ostatniego_stanu_licznika)) / (30 * 24 * 60 * 60);
            $symulowany_aktualny_przebieg = $ostatni_stan_licznika + ($miesiace_od_ostatniego_licznika * $sredni_przebieg_miesieczny);


            $temp_query_result = fetch_data($connection, "SELECT przebieg_nastepny_deadline, zadanie_interwal_kilometry 
            FROM ff_01b_cars_zadania_cykliczne WHERE car_id = $car_id AND zadanie_typ = '$zadanie_typ'");
            $przebieg_nastepny_deadline = $temp_query_result[1][0]['przebieg_nastepny_deadline'];
            $zadanie_interwal_kilometry = $temp_query_result[1][0]['zadanie_interwal_kilometry'];


            //AKTUALIZACJA daty PRZYPOMNIENIA DLA WYMIANY OLEJU  - robię to wyjątkowo tutaj, bo tam deadline jest ruchomy i zależy od przebiegu
            $symulowane_dni_do_osiagniecia_deadline_przebieg = ($przebieg_nastepny_deadline - $symulowany_aktualny_przebieg) / $sredni_przebieg_miesieczny * 30;
            $zaktualizowana_data_nastepnego_deadline = date(
                'Y-m-d',
                strtotime($current_date . " + " . round($symulowane_dni_do_osiagniecia_deadline_przebieg) . " days")
            );


            if ($zadanie_typ == 'wymiana_oleju') {  //dla oleju musimy sie upewnić, że wymieniamy olej nie rzadzij niż co 18 miesięcy
                $data_ostatniej_wymiany_oleju_query = "SELECT serwis_date FROM ff_02_cars_serwis_historia 
                    WHERE car_id = $car_id AND zadanie_typ = 'wymiana_oleju' 
                    ORDER BY serwis_date DESC LIMIT 1";
                $data_ostatniej_wymiany_oleju = fetch_data($connection, $data_ostatniej_wymiany_oleju_query);
                $data_ostatniej_wymiany_oleju_plus18miesiecy = date(
                    'Y-m-d',
                    strtotime($data_ostatniej_wymiany_oleju[1][0]['serwis_date'] . " + 18 months")
                );

                if ($data_ostatniej_wymiany_oleju_plus18miesiecy < $current_date) {  //jeśli olej był wymieniany tak dawno, że minęło już ponad 18 miesięcy, to ustawiamy datę następnego przypomnienia na 10 dni od teraz
                    $data_ostatniej_wymiany_oleju_plus18miesiecy = date('Y-m-d', strtotime($current_date . ' + 10 days'));
                }

                //dla wymiany oleju pilnujemy, żeby interwały nie były dłuższe niż 18 miesięcy
                $zaktualizowana_data_nastepnego_deadline = min($zaktualizowana_data_nastepnego_deadline, $data_ostatniej_wymiany_oleju_plus18miesiecy);
            }


            //aktualizuje datę następnego przypomnienia (działa tylko na nie zrobionych przypomnianiach z przyszłości) - jeśli jednak takiego przypomnienia nie ma, to robi INSERT
            $czy_istnieje_przyszle_przypomnienie_do_zrobienia = fetch_data($connection, "SELECT * FROM ff_02b_cars_przypomnienia 
                WHERE car_id = $car_id 
                AND zadanie_typ = '$zadanie_typ' 
                AND status_przypomnienia = 'do_zrobienia' 
                AND zadanie_deadline > CURDATE()");

            if (!empty($czy_istnieje_przyszle_przypomnienie_do_zrobienia[1])) {  //jeśli istnieje już takie przypomnienie, to aktualizujemy jego datę
                $sql = "UPDATE ff_02b_cars_przypomnienia 
                SET zadanie_deadline = '$zaktualizowana_data_nastepnego_deadline' 
                WHERE car_id = $car_id 
                AND zadanie_typ = '$zadanie_typ' 
                AND status_przypomnienia = 'do_zrobienia' 
                AND zadanie_deadline > CURDATE()";
            } else {  //jeśli nie ma takiego przypomnienia, to DODAJEMY nowe
                $sql = "INSERT INTO ff_02b_cars_przypomnienia 
                (id, car_id, zadanie_typ, zadanie_deadline, zadanie_uwagi1, zadanie_uwagi2, status_przypomnienia) 
                VALUES (NULL, $car_id, '$zadanie_typ', '$zaktualizowana_data_nastepnego_deadline', '', '', 'do_zrobienia')";
            }

            fetch_data($connection, $sql);
            //^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
            echo ($czy_istnieje_przyszle_przypomnienie_do_zrobienia[1] ? "Zaktualizowano" : "Dodano") . "datę następnego przypomnienia dla zadania typu $zadanie_typ dla samochodu o ID $car_id na $zaktualizowana_data_nastepnego_deadline.<br>";
            //tutaj liczy symulowaną datę następnej wymiany oleju - bo symulowany przebieg przekroczył już deadline w kilometrach
            if ($symulowany_aktualny_przebieg > $przebieg_nastepny_deadline) {
                $new_deadline_km = $przebieg_nastepny_deadline + $przebieg_nastepny_deadline;

                //poniżej oblicza za ile miesięcy trzeba wymienić olej - używa deadlinu w kilometrach, aktualnego symulowanego przebiegu i średniego przebiegu miesięcznego
                $symulowana_liczba_miesiecy_do_wymiany_oleju = ($new_deadline_km - $symulowany_aktualny_przebieg) / $sredni_przebieg_miesieczny;
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




function przypomnienia_spisywanie_licznikow($car_id)
{
    $connection = db_connect_mysqli_KM_VM();

    // Pobranie średniego przebiegu miesięcznego dla danego auta
    $query_przebieg = "SELECT sredni_przebieg_miesieczny,nr_rejestracyjny FROM ff_01_cars WHERE id = " . intval($car_id);
    list(, $car_nazwa_arr) = fetch_data($connection, "SELECT nazwa FROM ff_01_cars WHERE id = " . intval($car_id));
    list(, $wynik) = fetch_data($connection, $query_przebieg);

    if (empty($wynik) || empty($car_nazwa_arr)) {
        return; // brak danych – przerywamy
    }

    $car_nazwa = $car_nazwa_arr[0]['nazwa'];
    $car_numer_rejestracyjny = str_replace(' ', '', $wynik[0]['nr_rejestracyjny']);
    $sredni_przebieg = (int) $wynik[0]['sredni_przebieg_miesieczny'];

    // Pobranie daty ostatniego serwisu z niezerowym stanem licznika
    $query = "SELECT serwis_date FROM km_base.ff_02_cars_serwis_historia 
              WHERE stan_licznika IS NOT NULL AND stan_licznika <> 0 
              AND car_id = " . intval($car_id) . " 
              ORDER BY serwis_date DESC LIMIT 1";
    list(, $data_ostatniego_licznika) = fetch_data($connection, $query);

    if (!empty($data_ostatniego_licznika)) {
        $serwis_date = new DateTime($data_ostatniego_licznika[0]['serwis_date']);
        $dzis = new DateTime();
        $roznica = $dzis->diff($serwis_date)->m + ($dzis->diff($serwis_date)->y * 12);
        $mail_address_to = 'j.boruta@fops.pl';  //magazyn@fops.pl
        switch ($car_nazwa) {
            case 'FORD TRANSIT':
                $numer_telefonu = '503100955';   //502037590
                $adres_email = $mail_address_to;
                break;
            case 'Mercedes Sprinter 313 CDI SP2 Transport':
                $numer_telefonu = '503100955';   //665307009
                $adres_email = $mail_address_to;
                break;
            case 'Mercedes Sprinter SP1 Montaż':
                $numer_telefonu = '503100955';   //508308728
                $adres_email = $mail_address_to;
                break;
            case 'Mercedes Citan':
                $numer_telefonu = '503100955';   //665307009
                $adres_email = $mail_address_to;
                break;
            case 'Skoda Roomster/Praktik':
                $numer_telefonu = '503100955';   //665307009
                $adres_email = $mail_address_to;
                break;
            case 'Volkswagen UP!':
                $numer_telefonu = '503100955';   //506015500
                $adres_email = $mail_address_to;
                break;
            case 'Skoda Citigo':
                $numer_telefonu = '503100955';   //509670240  
                $adres_email = $mail_address_to;
                break;
        }
        // $adres_email = ($car_nazwa == 'Skoda Citigo' || $car_nazwa == 'FORD TRANSIT') ? 'kmadzia@fops.pl' : 'kmadzia@fops.pl'; //ustala adres email do wysyłki w zależności od nazwy samochodu

        if (($sredni_przebieg <= 1000 && $roznica >= 3) || ($sredni_przebieg > 1000 && $roznica >= 1)) {
            // Wysyła przypomnienie o sprawdzeniu przebiegu, jeśli spełnione są warunki

            $body = "Proszę o wysłanie stanu licznika samochodu " . $car_nazwa . " (" . $car_numer_rejestracyjny . ") w odpowiedzi na tę wiadomość.<br>
            Odpowiedź powinna zawierać w pierwszym wierszu tylko wartość przebiegu, bez dodatkowego tekstu.<br>
            Zawartość kolejnych wierszy nie ma znaczenia.<br>Dziękuję.";

            // Poprawiono brakujący nawias zamykający i cudzysłów
            // SendSMSNokia($numer_telefonu, "Przypomnienie o sprawdzeniu przebiegu dla samochodu " . $car_nazwa . ". " . $body);

            // Poprawiono zamknięcie cudzysłowu i nawiasu dla sendEmail
            sendEmail($adres_email, "Przypomnienie o sprawdzeniu przebiegu dla samochodu " . $car_nazwa . " (" . 
            $car_numer_rejestracyjny . ").", $body, $attachment_path = null, $is_html = true, $cc = 'k.madzia@fops.pl');

    }
}
}