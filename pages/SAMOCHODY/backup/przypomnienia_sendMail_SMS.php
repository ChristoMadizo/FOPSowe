<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


$connection = db_connect_mysqli_KM_VM();
// Pobranie danych - PRZYPOMNIENIA
$query_przypomnienia = "
    SELECT ff_02b_cars_przypomnienia.*, ff_01_cars.nazwa 
    FROM ff_02b_cars_przypomnienia 
    INNER JOIN ff_01_cars ON ff_02b_cars_przypomnienia.car_id = ff_01_cars.id
";

list($query_result, $result_przypomnienia) = fetch_data($connection, $query_przypomnienia);

// Sortowanie po terminie deadline rosnąco
usort($result_przypomnienia, function ($a, $b) {
    return strtotime($a['zadanie_deadline']) - strtotime($b['zadanie_deadline']);
});

$base_url = 'http://192.168.101.203/pages/SAMOCHODY/form_edit_data.php?data=';  //to jest url do wklejenia w wiadomości email
$today = new DateTime();

foreach ($result_przypomnienia as $task) {
    $deadline = new DateTime($task['zadanie_deadline']);
    $interval = $today->diff($deadline);
    $daysToDeadline = (int) $interval->format('%r%a');
    switch ($task['zadanie_typ']) {
        case 'badanie_techniczne':
            $adres_email = 'k.madzia@fops.pl';   //magazyn@fops.pl
            $numer_telefonu = '503100955';    //517566234
            if ($daysToDeadline == 1) {  //magazyn dostaje info o deadaline badania technicznego dzień przed terminem
                //SendSMSNokia($numer_telefonu, "Przypomnienie o sprawdzeniu przebiegu dla samochodu " . $car_nazwa . ". Wyślij stan licznika w opowidzi na tę wiadomość."); //504655126
                sendEmail(
                    $adres_email,
                    "Przypomnienie o badaniu technicznym",
                    "Jutro (" . $deadline . ") mija termin badania technicznego dla samochodu " . $car_nazwa . ".",
                    $attachment_path = null,
                    $is_html = false,
                    $cc = 'k.madzia@fops.pl'
                );
            } elseif ($daysToDeadline <= 0) {// w dniu terminu pytamy magazyn o potwierdzenie wykonania badania technicznego
                SendSMSNokia($numer_telefonu, "Potwierdź wykonanie badania technicznego dla samochodu " . $car_nazwa . ". Wyślij tekst 'Potwierdzam badanie' + " . $car_nazwa
                    . " jako odpowiedź na tę wiadomość."); //504655126
            }
            break;
        case 'ubezpieczenie':
            $adres_email = 'k.madzia@fops.pl';   //j.dawid@fops.pl
            $numer_telefonu = '503100955';    //517566234
            if ($daysToDeadline <= 7) {
                sendEmail(
                    $adres_email,
                    "Przypomnienie o końcu ubezpieczenia",
                    "Dnia " . $deadline . "kończy się polisa ubezpieczeniowa dla samochodu " . $car_nazwa . ".",
                    $attachment_path = null,
                    $is_html = false,
                    $cc = 'k.madzia@fops.pl'
                );
            } elseif ($daysToDeadline == 1) {   //wysyłka do szefa
                $adres_email = 'k.madzia@fops.pl';   //o.knapik@fops.pl
                sendEmail(
                    $adres_email,
                    "Przypomnienie o końcu ubezpieczenia",
                    "Dnia " . $deadline . "kończy się polisa ubezpieczeniowa dla samochodu " . $car_nazwa . ".",
                    $attachment_path = null,
                    $is_html = false,
                    $cc = 'k.madzia@fops.pl'
                );
            }
            break;
        case 'wymiana_oleju':
            $adres_email = 'k.madzia@fops.pl';   //j.boruta@fops.pl
            $numer_telefonu = '503100955';    //693428418 - telefon do Janka Boruty

    }
}

// Sprawdzamy czy deadline jest w ciągu najbliższych 7 dni (ujemne wartości też bierzemy pod uwagę)
/*if ($daysToDeadline <= 7) {
    $email = 'k.madzia@fops.pl';
    $cc = null;  // domyślnie brak CC

    // Typy zadań, dla których jest CC
    $cc_types = ['wymiana_oleju', 'badanie_techniczne'];
    if (in_array($task['zadanie_typ'], $cc_types)) {
        $cc = 'k.madzia@fops.pl';
    }

    $subject = "Przypomnienie o zadaniu: {$task['zadanie_typ']} dla pojazdu {$task['nazwa']}";

    $data_array = [
        'car_id' => $task['car_id'],
        'table' => 'ff_02b_cars_przypomnienia',
        'query' => "SELECT ff_02b_cars_przypomnienia.*, ff_01_cars.nazwa FROM ff_02b_cars_przypomnienia INNER JOIN ff_01_cars ON ff_02b_cars_przypomnienia.car_id = ff_01_cars.id",
        'columns' => ['id', 'status_przypomnienia', 'zadanie_typ', 'zadanie_uwagi1', 'zadanie_uwagi2', 'zadanie_deadline'],
        'header' => "Lista przypomnień dla {$task['nazwa']}"
    ];


    przypomnienia_spisywanie_licznikow($task['car_id']);



    $encoded_data = urlencode(json_encode($data_array));
    $url = $base_url . $encoded_data;

    $body = "Przypomnienie o zadaniu:<br><br>";
    $body .= "Typ zadania: {$task['zadanie_typ']}<br>";
    $body .= "Pojazd: {$task['nazwa']}<br>";
    $body .= "Uwagi: {$task['zadanie_uwagi1']}<br>";
    if ($daysToDeadline < 0) {
        $body .= 'Termin: <span style="color:red;">' . htmlspecialchars($task['zadanie_deadline']) . '</span><br>';
    } elseif ($daysToDeadline <= 7) {
        $body .= 'Termin: <span style="color:orange;">' . htmlspecialchars($task['zadanie_deadline']) . '</span><br>';
    } else {
        $body .= 'Termin: ' . htmlspecialchars($task['zadanie_deadline']) . '<br>';
    }
    $body .= 'Szczegóły i lista przypomnień: <a href="' . htmlspecialchars($url) . '">link</a><br>';

   // sendEmail($email, $subject, $body, null, true, $cc);
   // echo "Wysłano przypomnienie";
}
}
*/


//osobna funkcja pilnująca SPISYWANIA LICZNIKÓW

