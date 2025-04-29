<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Funkcja wyświetlająca formularz WSKAŻ_PLIK_PDF
function wyswietl_formularz_pobierania_plikuPDF() {
    echo '<form method="POST" action="" enctype="multipart/form-data">
    <div class="content">
        <label for="pdf-file">Wybierz plik PDF:</label>
        <input type="file" id="pdf-file" name="pdf_file" accept=".pdf">
        <button type="submit" name="submit">Prześlij</button>
    </div>
    </form>';
}

//$_FILES['pdf_file']['name']=$_SESSION['pdf_file']??'';
//$_FILES['pdf_file']['tmp_name']=$_SESSION['pdf_file']??'';



// Sprawdzamy, czy został przesłany plik PDF - jeśli tak, to pobieramy nazwę i temp ścieżkę, jeśli nie to tylko wyświetlamy formularz do wskazania pliku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['pdf_file'])) {
    $_SESSION['pdf_file'] = $_FILES['pdf_file']['name']; // zapisuję zmienną z FILES do sesji, żeby była dostępna w następnym wywołaniu
    $path_origin = $_FILES['pdf_file']['tmp_name']; // Tymczasowa ścieżka do pliku
    $path_destination = '/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu';

    // Dzielenie PDF i uzyskiwanie nazwisk
    //$result = splitPdf($path_origin, $path_destination);
    //$_SESSION['lista_pracownikow'] = getAllNamesFromPdfDir($path_destination, $szyfrowac = true);

    echo 'Plik został przetworzony!';
    // Możesz tutaj przekierować użytkownika do widoku tabeli lub kolejnych kroków.

} elseif (!isset($_SESSION['lista_pracownikow']) || !empty($_SESSION['lista_pracownikow']['error'])) {  //musiałem dodać warunek z "error"
    //bo z jakiegoś powodu $_SESSION['lista_pracownikow']['error'] od razu na początku skryptu zawierała zawartość "katalog nie istnieje"
    // Wyświetlamy formularz, jeśli plik nie został przesłany
    unset($_SESSION['lista_pracownikow']);    //resetujemy sesję

    !empty($path_destination) && array_map('unlink', glob($path_destination . '/*'));//KASUJE wszystkie PLIKI w $path_destination

    wyswietl_formularz_pobierania_plikuPDF();
    exit("");

}

    //gdy kliknięto reset sesji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_sesji') {
    // Reset sesji
    unset($_SESSION['lista_pracownikow']);
    !empty($path_destination) && array_map('unlink', glob($path_destination . '/*')); //KASUJE wszystkie PLIKI w $path_destination
    wyswietl_formularz_pobierania_plikuPDF();
    exit("Sesja została zresetowana, wykonanie skryptu zakończone.");
}


//$path_origin = '/home/kmadzia/www/pages/PASKI_WYPLATY/TestySPLIT/joined.pdf';
//$path_destination = '/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu'; 


// Sprawdzanie czy lista pracowników jest w sesji - jeśli to stara sesja, to działamy na starych plikach i danych, 
// jeśli sesja jest pusta, to tniemy pdf i tworzymy nową sesję

if (!isset($_SESSION['lista_pracownikow'])) {         
    $result = splitPdf($path_origin, $path_destination); // Dzielenie PDF na pojedyncze pliki
    $_SESSION['lista_pracownikow'] = getAllNamesFromPdfDir($path_destination, $szyfrowac = true);  //pobiera listę pracowników z pdf
}  

//$result = splitPdf($path_origin, $path_destination); // Dzielenie PDF na pojedyncze pliki
//$_SESSION['lista_pracownikow'] = getAllNamesFromPdfDir($path_destination, $szyfrowac = true);

$lista_pracownikow = $_SESSION['lista_pracownikow'];   //czyli w tej zmiennej mamy listę pracowników pobraną z plików pdf.

$table = [];   //buduje tabelę z listy pracowników, którą pobrał z plików pdf
foreach ($lista_pracownikow as $index => $pracownik) {
    // Budowanie tabeli
    $table[] = [
        'Lp' => $index + 1,
        'nazwisko_imie' => $pracownik['nazwisko_imie'],
        'nr_telefonu' => '',
        'adres_email' => '',
    //    'sciezka_pdf' => $pracownik['sciezka_pdf'],
        'Last_SMS_Email_date'=>'',
        'Last_SMS_confirmation_date'=>''
    ];
}

$connection = db_connect_mysqli_KM_VM();

//pobiera info o pracownikach z bazy danych - żeby do listy pracowników pobranych z plików pdf dodać info o adresie i telefonie
$sql = "select 
CONCAT(surname,' ',name) as nazwisko_imie, 
name,
surname,
phone_number,
e_mail_address as e_address,
(select max(date) from km_base.bb01_events where event_type='SMS_email_sent' and worker_id = aa01.id) as Last_SMS_Email_date,
(select max(date) from km_base.bb01_events where event_type='SMS_delivery_confirmation' and worker_id = aa01.id) as Last_SMS_confirmation_date
from km_base.aa01_workers aa01";


$workers_info = fetch_data($connection, $sql)[1]; // Pobieranie danych o pracownikach


// Łączenie danych - do danych pobranych z plików pdf dodajemy dane z bazy danych (adres email i telefon)
foreach ($table as &$employee) {
    // Szukamy pracownika w $workers_info na podstawie 'nazwisko_imię' - bo tylko takie dane da się wyciągnąć z PDF
    foreach ($workers_info as $info) {
        if ($employee['nazwisko_imie'] === $info['nazwisko_imie']) {
            // Dodajemy dane z $workers_info do $table
            $employee['adres_email'] = $info['e_address'];
            $employee['nr_telefonu'] = $info['phone_number'];
            $employee['Last_SMS_Email_date'] = $info['Last_SMS_Email_date'];
            $employee['Last_SMS_confirmation_date'] = $info['Last_SMS_confirmation_date'];
            break; // Przerywamy pętlę, gdy znajdziemy odpowiednik
        }
    }
}


// Obsługa przycisków
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'sms' || $_POST['action'] === 'email' || $_POST['action'] === 'email_and_SMS' )) {
    $Lp = $_POST['Lp']; // Pobranie numeru wiersza
    $nazwisko_imie = $_POST['nazwisko_imie'];
    $nr_telefonu = $_POST['nr_telefonu'];
    $adres_email = $_POST['adres_email'];
    $action = $_POST['action'];


    if ($action === 'sms') {
        $tekst_wiadomosci = $lista_pracownikow[$Lp - 1]['haslo_pdf'];
        $result = SendSMSNokia($nr_telefonu, $tekst_wiadomosci);
    } elseif ($action === 'email') {
        $tytul = "To jest tytul";
        $tresc = "Witamy z formularza";
        $sciezka_pdf = $_POST['sciezka_pdf'];
        $result = sendEmail($adres_email, $tytul, $tresc, $sciezka_pdf);
    } elseif ($action === 'email_and_SMS') {
        $tytul = "To jest tytul";
        $tresc = "Witamy z formularza";
        $sciezka_pdf = $_POST['sciezka_pdf'];
        $result = sendEmail($adres_email, $tytul, $tresc, $sciezka_pdf);

        $sql='SELECT id FROM km_base.aa01_workers WHERE phone_number = ' . $nr_telefonu . ''; 
        $worker_id=fetch_data($connection, $sql); // Pobranie id pracownika na podstawie numeru telefonu
        $event_datetime=new DateTime();
        $password=$lista_pracownikow[$Lp - 1]['haslo_pdf'];
        $tekst_wiadomosci = $lista_pracownikow[$Lp - 1]['haslo_pdf'];

        $result = SendSMSNokia($nr_telefonu, $tekst_wiadomosci); //wysyła SMS do pracownika
        $sendCopyToNokia = SendSMSNokia(789757533,$nr_telefonu); //kopia wysyłana na Nokię - w treści numer telefonu pracownika na który wysłano SMS
        
         $data = [  //tworzy dane do dodania do bazy danych
            'worker_id' => $worker_id[1][0]['id'],
            'date' => $event_datetime, // Użycie obiektu DateTime
            'event_type' => 'SMS_email_sent',
            'password' => $password,
            'remarks1' => '',
            'remarks2' => ''
        ];

        $insert_into_table = 'bb01_events';  //uzupełnia nazwę tabeli do której dodaje dane  INSERT

        try {
            insertIntoTable($connection, $insert_into_table, $data);
            echo "Dane zostały dodane!";
        } catch (Exception $e) {
            echo "Wystąpił błąd: " . $e->getMessage();
        }
    
        $readSMSsFromNokia = ReadSMSNokia($messages_count=100); //pobiera wszystkie SMSy z Nokii
        $SMS_delivery_check = findSMSByPhoneNumber($worker_id[1][0]['id'],$nr_telefonu, $readSMSsFromNokia);  //sprawdza czy na Nokię dotarła kopia SMSa wysłanego do pracownika
        //DOPISAĆ IFA - ZE MA TO ROBIĆ JEŚLI KOPIA DOTARŁA DO NOKII
        try {
            insertIntoTable($connection, $insert_into_table, $SMS_delivery_check);  //dodaje do bazy rezultat pobierania potwierdzenia
            echo "Dane zostały dodane!";
        } catch (Exception $e) {
            echo "Wystąpił błąd: " . $e->getMessage();
            }
    } 
} 


if (isset($_POST['action']) && $_POST['action'] === 'sprawdz_potwierdzenieSMS') {  //odświeża potwierdzenie SMS
    foreach ($table as $row) {
        $nr_telefonu = $row['nr_telefonu'];
        if (empty($nr_telefonu)) continue; // pomiń jeśli brak numeru telefonu

        // Pobierz ID pracownika na podstawie numeru telefonu
        $sql = "SELECT id FROM km_base.aa01_workers WHERE phone_number = '" . $nr_telefonu . "'";
        $worker_id = fetch_data($connection, $sql);
        if (empty($worker_id[1][0]['id'])) continue; // pomiń jeśli brak ID

        $worker_id_value = $worker_id[1][0]['id'];

        // Pobierz wszystkie SMSy z Nokii
        $readSMSsFromNokia = ReadSMSNokia($messages_count = 100);

        // Sprawdź, czy przyszedł SMS z potwierdzeniem
        $SMS_delivery_check = findSMSByPhoneNumber($worker_id_value, $nr_telefonu, $readSMSsFromNokia);

        // Jeśli przyszła kopia (czyli wynik nie jest pusty)
        if (!empty($SMS_delivery_check)) {
            $insert_into_table = 'bb01_events';

            try {
                insertIntoTable($connection, $insert_into_table, $SMS_delivery_check);
                echo "Dane zostały dodane dla pracownika o nr: $nr_telefonu<br>";
            } catch (Exception $e) {
                echo "Błąd przy dodawaniu danych dla nr $nr_telefonu: " . $e->getMessage() . "<br>";
            }
        }
    }
}


//poniżej pobiera tylko dane z BAZY danych, nie z sesji - pobiera info dla wszystkich pracowników w celu wyświetlenia tabeli
// Iteracja przez tabelę pracowników - buduje TABELĘ Z DANYMI

/*foreach ($table as $index => $row) {
// Dopasowanie nazwiska i imienia
if ($row['nazwisko_imie'] === $workers_info[0]['nazwisko_imie']) {
    // Dodaj dane do wiersza tabeli
    $table[$index]['adres_email'] = $workers_info[0]['e_address'];
    $table[$index]['nr_telefonu'] = $workers_info[0]['phone_number'];
}
}*/

    //$table_for_display=display_table_from_array($table,['nazwisko_imie','nr_telefonu','adres_email']);  - to już NIAKTUALNE, BO WYŚWIETLAMY FORMULARZ Z PRZYCISKAMI
 
?>
<!--*********************************WYŚWIETLANIE STRONY*************************************************************  -->

<div class="content">
    <table border="1"">
        <tr>
            <!-- Nagłówki tabeli -->
            <?php foreach (array_keys($table[0]) as $column_name): ?>
                <th>
                    <?php if ($column_name === 'Last_SMS_confirmation_date'): ?>
                        <form method="post">
                            <button type="submit" name="action" value="refresh_SMS" style="width: 80%; background-color:rgb(131, 194, 102);">
                                Odśwież SMS
                            </button>
                        </form>
                    <?php else: ?>
                        <?php echo htmlspecialchars($column_name, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </th>
            <?php endforeach; ?>
            <th style="min-width: 130px;">Akcja</th>
        </tr>

        <!-- Wiersze danych -->
        <?php foreach ($table as $row): ?>
            <tr style="text-align: center; max-width: 200px;">
                <!-- Kolumny danych -->
                <?php foreach ($row as $key => $value): ?>       
                    <td><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></td>                   
                <?php endforeach; ?>

                <!-- Kolumna z przyciskami akcji -->
                <td>
                    <form method="post">
                        <input type="hidden" name="Lp" value="<?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="nazwisko_imie" value="<?php echo htmlspecialchars($row['nazwisko_imie'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="nr_telefonu" value="<?php echo htmlspecialchars($row['nr_telefonu'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="adres_email" value="<?php echo htmlspecialchars($row['adres_email'] ?? 'brak adresu', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="sciezka_pdf" value="<?php echo htmlspecialchars($row['sciezka_pdf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">  

                        <!-- Przyciski akcji -->
                        <button type="submit" name="action" value="email_and_SMS">E-mail + SMS</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form method="post">
        <button type="submit" name="action" value="reset_sesji" style="background-color: lightcoral;">Resetuj</button>
    </form>
</div>



