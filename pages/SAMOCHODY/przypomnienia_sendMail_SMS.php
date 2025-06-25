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
    INNER JOIN ff_01_cars ON ff_02b_cars_przypomnienia.car_id = ff_01_cars.id WHERE status_przypomnienia='do_zrobienia'
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
    $car_nazwa = $task['nazwa'];
    $task_typ_tresc = $task['zadanie_typ'] . ' - ' . $task['zadanie_uwagi1'];


    $link_do_tabeli = "http://192.168.101.203/pages/SAMOCHODY/form_edit_data.php?data=" . urlencode(json_encode([
        "car_id" => $task['car_id'],
        "table" => "ff_02b_cars_przypomnienia",
        "query" => $query_przypomnienia,
        "columns" => ["id", "status_przypomnienia", "zadanie_typ", "zadanie_uwagi1", "zadanie_uwagi2", "zadanie_deadline"],
        "header" => "Lista przypomnień dla " . $car_nazwa,
    ]));


    switch ($task['zadanie_typ']) {
        case 'badanie_techniczne':
            $adres_email = 'k.madzia@fops.pl';   //magazyn@fops.pl
            $numer_telefonu = '503100955';    //517566234
            if ($daysToDeadline <= 1) {  //magazyn dostaje info o deadaline badania technicznego dzień przed terminem
                //SendSMSNokia($numer_telefonu, "Przypomnienie o sprawdzeniu przebiegu dla samochodu " . $car_nazwa . ". Wyślij stan licznika w odpowiedzi na tę wiadomość."); //504655126
                sendEmail(
                    $adres_email,
                    "Przypomnienie o badaniu technicznym: " . $car_nazwa,
                    "Przypomnienie o terminie (" . $deadline->format('Y-m-d') . ") badania technicznego dla samochodu " . $car_nazwa . ".<br><br>
                    Link do zarządzania przypomnieniami: <a href='" . $link_do_tabeli . "'>Lista przypomnień</a>.",
                    $attachment_path = null,
                    $is_html = true,
                    $cc = 'k.madzia@fops.pl'
                );
                if ($daysToDeadline <= 0) {// w dniu terminu pytamy magazyn o potwierdzenie wykonania badania technicznego
                    SendSMSNokia($numer_telefonu, "Potwierdź wykonanie badania technicznego dla samochodu " . $car_nazwa . ". Wyślij tekst 'Potwierdzam badanie'  " . explode(" ", $car_nazwa)[0]
                        . " jako odpowiedź na tę wiadomość. Dziękuję."); //504655126
                }
            }
            break;
        case 'ubezpieczenie':
            $adres_email = 'k.madzia@fops.pl';   //j.dawid@fops.pl
            $numer_telefonu = '503100955';    //517566234
            if ($daysToDeadline <= 7) {
                sendEmail(
                    $adres_email,
                    "Przypomnienie o końcu ubezpieczenia: " . $car_nazwa,
                    "Dnia " . $deadline->format('Y-m-d') . " kończy się polisa ubezpieczeniowa dla samochodu " . $car_nazwa . ".<br><br>
                    Link do zarządzania przypomnieniami: <a href='" . $link_do_tabeli . "'>Lista przypomnień</a>.",
                    $attachment_path = null,
                    $is_html = true,
                    $cc = 'k.madzia@fops.pl'
                );
                if ($daysToDeadline == 1) {   //wysyłka do szefa
                    $adres_email = 'k.madzia@fops.pl';   //o.knapik@fops.pl
                    sendEmail(
                        $adres_email,
                        "Przypomnienie o końcu ubezpieczenia: " . $car_nazwa,
                        "Dnia " . $deadline . "kończy się polisa ubezpieczeniowa dla samochodu " . $car_nazwa . ".<br><br>
                    Link do zarządzania przypomnieniami: <a href='" . $link_do_tabeli . "'>Lista przypomnień</a>.",
                        $attachment_path = null,
                        $is_html = true,
                        $cc = 'k.madzia@fops.pl'
                    );
                }
            }
            break;
        case 'wymiana_oleju':
            $adres_email = 'k.madzia@fops.pl';   //j.boruta@fops.pl
            $numer_telefonu = '503100955';    //693428418 - telefon do Janka Boruty
            if ($daysToDeadline <= 7) {
                sendEmail(
                    $adres_email,
                    "Przypomnienie o wymianie oleju: " . $car_nazwa,
                    "Do " . $deadline->format('Y-m-d') . " należy wymienić olej silnikowy w samochodzie " . $car_nazwa . ".<br><br>
                    Link do zarządzania przypomnieniami: <a href='" . $link_do_tabeli . "'>Lista przypomnień</a>.",
                    $attachment_path = null,
                    $is_html = true,
                    $cc = 'k.madzia@fops.pl'
                );
                SendSMSNokia($numer_telefonu, "Dnia " . $deadline->format('Y-m-d') . " należy wymienić olej silnikowy w samochodzie " . $car_nazwa . ".");
            }
            break;
        default:
            $adres_email = 'k.madzia@fops.pl';   //j.boruta@fops.pl
            $numer_telefonu = '503100955';    //693428418 - telefon do Janka Boruty
            if ($daysToDeadline <= 7) {
                sendEmail(
                    $adres_email,
                    "Przypomnienie o zadaniu - samochód: " . $car_nazwa,
                    "Dnia " . $deadline->format('Y-m-d') . " mija termin zadania: " . $task_typ_tresc . " dla samochodu: " . $car_nazwa . ".<br><br>
                    Link do zarządzania przypomnieniami: <a href='" . $link_do_tabeli . "'>Lista przypomnień</a>.",
                    $attachment_path = null,
                    $is_html = true,
                    $cc = 'k.madzia@fops.pl'
                );

            }
    }
}



