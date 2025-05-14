<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("Europe/Warsaw");  //bez tego przesuwa czas o 2 godziny!


//ustalenie czy aktualnie jest dzień i godzina, w której można otworzyć bramę SMSem**********************
$now = new DateTime();
$dayOfWeek = $now->format('N'); // Numer dnia tygodnia (1 = poniedziałek, 7 = niedziela)
$hour = (int) $now->format('H'); // Pobranie godziny
$minute = (int) $now->format('i'); // Pobranie minut

$pozwolOtworzyc = false; // Domyślnie ustawiony na false

// Sprawdzenie przedziałów czasowych
if (
    // Wtorek, środa, czwartek, piątek: 00:00-02:59 i 05:00-05:59
    ($dayOfWeek >= 2 && $dayOfWeek <= 5 && (
        ($hour >= 0 && $hour <= 2) || ($hour == 5 && $minute <= 59) //|| ($hour ==14)
    )) ||    
    
    // Piątek: 17:00-23:59
    ($dayOfWeek == 5 && $hour >= 17 && $hour <= 23) ||

    // Sobota, niedziela: 03:00-05:59 i 12:00-14:59
    ($dayOfWeek == 6 || $dayOfWeek == 7 && (
        ($hour >= 3 && $hour <= 5) || ($hour >= 12 && $hour <= 14)
    ))
) {
    $pozwolOtworzyc = true;
}
//*****************************************************************************************



$update_database = false;

//przygotowanie połączenia do bazy danych
$connection=db_connect_mysqli_KM_VM();

$sms_brama=ReadSMSNokia(1)[0]; //ściąga ostatniego smsa

// Sprawdzenie treści wiadomości - czy wysłano sms o treści 'brama'
$last_gate_movement=fetch_data($connection, 'select max(gate_movement_datetime) from km_base.dd01_gateSMSmessages_actions');  //pobiera czas ostatniego ruchu bramy
$sms_content=ReadSMSNokia(1)[0]['message_content'];
$sms_from_number=ReadSMSNokia(1)[0]['phone_number'];
$sms_datetime=ReadSMSNokia(1)[0]['date_time'];

$is_brama = strtolower(trim($sms_content)) === 'brama';  //sprawdza czy treść smsa to 'brama' (niezależnie od wielkości liter i spacji)

// Sprawdzenie, czy wiadomość jest z ostatnich 60 sekund (zwiększyłem z 20, bo przez interwały i lag czasem nie łapał)
$sms_time = strtotime($sms_brama['date_time']);
$now = time();
$is_recent = ($now - $sms_time) < 60;
$time_from_last_movement = $now - strtotime($last_gate_movement[1][0]['max(gate_movement_datetime)']);


if (!$pozwolOtworzyc && strpos($sms_from_number,'503100955') === false ) {  //jeśli jesteśmy poza godzinami otwierania bramy, to zakończy działanie (dla numeru 503100955 nie kończy działania)
    if ($is_recent && $is_brama) { //jeśli jest SMS z "brama" + jest z ostatnich 60 sekund, a jesteśmy poza godzinami otwierania, to da znać na SMS o próbie otwarcia
        echo "<div class='content'>Nie można otworzyć bramy w tym czasie.</div>";
        SendSMSNokia($sms_from_number, "Próba otwarcia bramy w niedozwolonym czasie");
        SendSMSNokia(503100955, "Próba otwarcia bramy w niedozwolonym czasie");
        sleep(15); //dodałem czekanie 15 sekund, żeby nie dublował SMSów z tym komunikatem
        exit;
    }
    exit; //jeśli jesteśmy poza godzinami otwierania bramy, to zakończy działanie
}

// Ostateczna decyzja
if ($is_brama && $is_recent &&$time_from_last_movement>45) {   //czyli jeśli jest SMS z "brama" + jest z ostatnich 60 sekund + ostatni ruch bramy był ponad 60 sekund temu
    echo "<div class='content'>Wiadomość 'brama' jest z ostatnich 20 sekund.</div>";
    $python_path = '/home/kmadzia/myenv/bin/python'; // Ścieżka do Pythona w myenv
    $script_path = '/home/kmadzia/www/scripts/sterowanie_brama_chrome.py'; // Ścieżka do skryptu Pythona
    $command=$python_path  . ' ' .  $script_path;
    $result = shell_exec("$command");
    SendSMSNokia($sms_from_number,"Brama ruszona");
    SendSMSNokia(503100955,"Brama ruszona");
    $update_database = 'SMS_and_movement';
    $gate_movement_datetime = date("Y-m-d H:i:s", time());
} else {
    echo "<div class='content'>Nie spełnia warunków.</div>";
   // SendSMSNokia(503100955,"Brama nie ruszona");
}



if ($update_database) {
    // Przygotowanie danych do wstawienia
    $data = [
        'SMS_datetime' => $sms_datetime,
        'Nr_from' => $sms_from_number,
        'gate_action' => 'gate_movement',
        'message_content' => $sms_content,
        'gate_movement_datetime' => $gate_movement_datetime,
    ];
    // Wstawienie danych do tabeli
    insertIntoTable($connection, 'dd01_gateSMSmessages_actions', $data);
} 

//$result = shell_exec("$command");
//echo "<div class='content'>". $result . "</div>";

?>