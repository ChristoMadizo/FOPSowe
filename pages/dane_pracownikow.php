<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection=db_connect_mysqli_KM_VM();

$sql="SELECT name,surname,phone_number,e_mail_address FROM `aa01_workers`";

$workers_info = fetch_data($connection, $sql)[1]; // Pobieranie danych o pracownikach

//$table=[];

if (isset($_POST['action']) && $_POST['action'] === 'wyslij_sms') {  //wysyła SMS z prośbą o adres e-mail
    $nr_telefonu = $_POST['phone_number'];   //573299922
    $adres_email = $_POST['e_mail_address'];
    $result = SendSMSNokia($nr_telefonu, 'Proszę o podanie adresu e-mail do wysyłki pasków wynagrodzeń, poprzez odpowiedź na tę wiadomość. Dziękuję, Dział Kadr FOPS.');
};

//Prosze o podanie adresu e-mail, na ktory beda wysylane paski wynagrodzen, poprzez odpowiedz na te wiadomosc. Dziękuje, dzial Kadr FOPS. - testy ok, długość 136 znaków
//Czy to za dlugi tekst? Czy to za dlugi tekst? Czy to za dlugi tekst? Czy to za dlugi tekst? Czy to za dlugi tekst? Czy to za dlugi tekst? - testy ok, długość 138 znaków

if (isset($_POST['action']) && $_POST['action'] === 'sprawdz_odpowiedzi') {  //odświeża potwierdzenie SMS i robi UPDATE bazy, jeśli podano maila
    $messages = ReadSMSNokia(100);
    //$sql = "SELECT id FROM km_base.aa01_workers WHERE phone_number = '" . $nr_telefonu . "'"; //pobiera id pracownika z bazy
   // $worker_id = fetch_data($connection, $sql);

   $table_to_update = 'aa01_workers'; //tabela do aktualizacji (ewentualne dodanie adresu email)

    foreach($workers_info as $row) { //iteruje przez wszystkich pracownikow z formularza i jeśli w bazie adres email jest pusty, to szuka SMSa z adresem
        $nr_telefonu_formularz = $row['phone_number'];
        $adres_email_formularz = $row['e_mail_address'];

        if (empty($adres_email_formularz)) {  //jeśli w bazie (czyli w formularzu) adres e-mail jest pusty
            $messages_this_worker = array_values(      //dzięki array_values() mamy indeksy numerowane od nowa (od zera)
                    array_filter($messages, function($item) use ($nr_telefonu_formularz) {  
                    // Sprawdzamy, czy element [0] zawiera "503100955" (np. czy wartość istnieje w telefonie)
                    $found_number = strpos($item['phone_number'] ?? 'brak numeru', $nr_telefonu_formularz ?? 'brak numeru') !== false;
                    // Sprawdzamy, czy element [2] jest poprawnym adresem e-mail
                    $is_valid_email = filter_var($item['message_content']??'brak tresci', FILTER_VALIDATE_EMAIL);
            
                    // Zwracamy true tylko jeśli oba warunki są spełnione
                    return $found_number && $is_valid_email;
            }       )      );
        }
    
        
        if (!empty($messages_this_worker)){   //jeśli pracownik nie miał jeszcze podanego adresu, a go przysłał, to robimy UPDATE
            $data = [
                'e_mail_address' => $messages_this_worker[0]['message_content'] //przypisanie pobranego adresu email do zmiennej
            ];
            $where=[
                'phone_number' => $nr_telefonu_formularz //przypisanie numeru telefonu do zmiennej
            ];

            updateTable($connection, $table_to_update, $data, $where);  //uruchamia aktualizację bazy o pobrany adres email
        } 
    }

};

  
    //tu musi wejść do każdego usera z tabelki, wyfiltrować z $messages jego odpowiedzi, a potem sprawdzić czy w tych odpowiedziach jest adres mail
    //szukać musi po telefonie, ale uwaga na +48503100955, bo w bazie jest 503100955
      


$workers_info = fetch_data($connection, $sql)[1]; // Pobieranie danych o pracownikach





?>

<div class="content">

    <form method="post">
        <button type="submit" name="action" value="sprawdz_odpowiedzi">Sprawdź odpowiedzi.</button>
    </form>               


    <table border="1">
        <tr>
            <!-- Nagłówki tabeli -->
            <?php foreach (array_keys($workers_info[0]) as $column_name): ?>
                <th><?php echo htmlspecialchars($column_name, ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
            <th>Akcja</th>
        </tr>

        <!-- Wiersze danych -->
        <?php foreach ($workers_info as $row): ?>
            <tr>
                <!-- Kolumny danych -->
                <?php foreach ($row as $key => $value): ?>
                    <td><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endforeach; ?>

                <!-- Kolumna z przyciskami akcji -->
                <td>
                    <form method="post">
                        <!-- Przekazanie wszystkich danych wiersza -->

                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="surname" value="<?php echo htmlspecialchars($row['surname'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="e_mail_address" value="<?php echo htmlspecialchars($row['e_mail_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">  <!-- uzupełnienie scieżki do pliku pdf -->
                        
                        <!-- Przyciski akcji -->
                        <button type="submit" name="action" value="wyslij_sms" style="all: unset; color: blue; text-decoration: underline; cursor: pointer; display: block; text-align: center;">Wyślij SMS</button>

                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>


</div>
