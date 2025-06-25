<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Warsaw'); // Ustawienie strefy czasowej na Warszawę


// Funkcja wyświetlająca formularz WSKAŻ_PLIK_PDF
function wyswietl_formularz_pobierania_plikuPDF()
{
    echo '<form method="POST" action="" enctype="multipart/form-data">
    <div class="content">
        <label for="pdf-file">Wybierz plik PDF:</label>
        <input type="file" id="pdf-file" name="pdf_file" accept=".pdf">
        <button type="submit" name="submit">Zaczytaj plik</button>
    </div>
    </form>';
}

//$_FILES['pdf_file']['name']=$_SESSION['pdf_file']??'';
//$_FILES['pdf_file']['tmp_name']=$_SESSION['pdf_file']??'';

$path_destination = '/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu';

/*
//KASOWANIE wszystkich plików w folderze /home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu
if (is_dir($path_destination)) {       
    $files = glob($path_destination . '/*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}*/

$base_PDF_url = "http://192.168.101.203/pages/PASKI_WYPLATY/PDFyDoOdczytu/";  //żeby wyświetlić pdf w iframe

// Sprawdzamy, czy został przesłany plik PDF - jeśli tak, to pobieramy nazwę i temp ścieżkę, jeśli nie to tylko wyświetlamy formularz do wskazania pliku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['pdf_file'])) {
    $_SESSION['pdf_file'] = $_FILES['pdf_file']['name']; // zapisuję zmienną z FILES do sesji, żeby była dostępna w następnym wywołaniu
    $path_origin = $_FILES['pdf_file']['tmp_name']; // Tymczasowa ścieżka do pliku
    $path_destination = '/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu';

    //ustawiam na początek czy_juz_byla_wysylka na false dla każdego pracownika
    foreach ($_SESSION['lista_pracownikow'] as &$pracownik) {
        $pracownik['czy_juz_byla_wysylka'] = false; // Możesz zmienić na `true`, gdy wiadomość została wysłana
    }

    // Dzielenie PDF i uzyskiwanie nazwisk
    //$result = splitPdf($path_origin, $path_destination);
    //$_SESSION['lista_pracownikow'] = getAllNamesFromPdfDir($path_destination, $szyfrowac = true);

    //echo 'Plik został przetworzony!';
    // Możesz tutaj przekierować użytkownika do widoku tabeli lub kolejnych kroków.

} elseif (!isset($_SESSION['lista_pracownikow']) || !empty($_SESSION['lista_pracownikow']['error'])) {  //musiałem dodać warunek z "error"
    //bo z jakiegoś powodu $_SESSION['lista_pracownikow']['error'] od razu na początku skryptu zawierała zawartość "katalog nie istnieje"
    // Wyświetlamy formularz, jeśli plik nie został przesłany
    unset($_SESSION['lista_pracownikow']);    //resetujemy sesję

    !empty($path_destination) && array_map('unlink', glob($path_destination . '/*'));//KASUJE wszystkie PLIKI w $path_destination

    wyswietl_formularz_pobierania_plikuPDF();
    exit("");

}

//gdy kliknięto RESET sesji
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

$set_protection = false;   //nie szyfruję na etapie dzielenia pliku, żeby móc podglądać pdf w iframe

if (!isset($_SESSION['lista_pracownikow'])) {      //jeśli lista pracowników nie jest jeszcze ustalona     
    $result = splitPdf($path_origin, $path_destination); // Dzielenie PDF na pojedyncze pliki
    $regex_nazwisko_imie = "/\|\s*(?:[1-9]|[1-9]\d|100)\)\|\s*([^|]+?)\s*\|/";
    $_SESSION['lista_pracownikow'] = ReadFromPdfDirSetProtection($path_destination, $szyfrowac = $set_protection, $regex_nazwisko_imie);  //pobiera listę pracowników z pdf.

    //odwracamy Imie Nazwisko na Nazwisko Imie
    foreach ($_SESSION['lista_pracownikow'] as &$pracownik) {
    if (isset($pracownik['nazwisko_imie'])) {
        $czesci = explode(" ", $pracownik['nazwisko_imie'], 2);
        if (count($czesci) == 2) {
            $pracownik['nazwisko_imie'] = $czesci[1] . " " . $czesci[0];
        }
    }
    }

    // Iteracja przez listę i dodanie nowego pola - czy_juz_byla_wysylka na starcie jest false
    foreach ($_SESSION['lista_pracownikow'] as &$pracownik) {
        $pracownik['czy_juz_byla_wysylka'] = false; // Możesz zmienić na `true`, gdy wiadomość została wysłana
    }

    //poniżej zczytuje okres za który była wypłata
    $pdf_content = readPdfText($path_origin);
    $pdf_content = str_replace(["¹", "³", "¿"], ["ą", "ł", "ż"], $pdf_content);
    $regex_miesiac_wyplaty = '/($miesiace)\s+(\d{4})/i';
    // Lista polskich nazw miesięcy (powinna być zdefiniowana przed regexem!)
    $miesiace = 'styczeń|luty|marzec|kwiecień|maj|czerwiec|lipiec|sierpień|wrzesień|październik|listopad|grudzień';

    // Regex dopasowujący nazwę miesiąca i rok po niej
    $regex_miesiac_wyplaty = "/($miesiace)\s+(\d{4})/i";

    $miesiac_wyplaty = [];
    if (preg_match($regex_miesiac_wyplaty, $pdf_content, $matches)) {
        $miesiac = $matches[1];
        $rok = $matches[2];
        //echo "Miesiąc wypłaty: $miesiac $rok";
        $miesiac_wyplaty = $miesiac . ' ' . $rok;
        $_SESSION['miesiac_wyplaty'] = $miesiac_wyplaty;
    } else {
        echo "Brak dopasowania";
    }

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

    $connection = db_connect_mysqli_KM_VM();

    $workers_info = fetch_data($connection, $sql)[1]; // Pobieranie danych o pracownikach

    //rozszerza zmienną $_SESSION['lista_pracownikow'] o nowe pola
    foreach ($_SESSION['lista_pracownikow'] as $index => $pracownik) {
        $_SESSION['lista_pracownikow'][$index] = array_merge($pracownik, [
            'Lp' => $index + 1,
            'nr_telefonu' => '',
            'adres_email' => '',
            'sciezka_pdf' => $base_PDF_url . basename($pracownik['sciezka_pdf']), 
            'Last_SMS_Email_date' => '',
            'Last_SMS_confirmation_date' => ''
    ]);
    }



    // Łączenie danych - do danych pobranych z plików pdf dodajemy dane z bazy danych (adres email i telefon)
    foreach ($_SESSION['lista_pracownikow'] as $index => $employee) {
        // Szukamy pracownika w $workers_info na podstawie 'nazwisko_imie'
        foreach ($workers_info as $info) {
            if ($employee['nazwisko_imie'] === $info['nazwisko_imie']) {
                // Aktualizujemy dane bez utraty struktury
                $_SESSION['lista_pracownikow'][$index]['adres_email'] = $info['e_address'];
                $_SESSION['lista_pracownikow'][$index]['nr_telefonu'] = $info['phone_number'];
                $_SESSION['lista_pracownikow'][$index]['Last_SMS_Email_date'] = $info['Last_SMS_Email_date'];
                $_SESSION['lista_pracownikow'][$index]['Last_SMS_confirmation_date'] = $info['Last_SMS_confirmation_date'];
                break; // Przerywamy pętlę, gdy znajdziemy odpowiednik
            }
        }
}

}  //KONIEC ifa, który się wykonuje tylko jeśli w sesji nie było jeszcze danych pracowników (czyli przed zaczytaniem PDFa)


//pobieram dane z sesji
$connection = db_connect_mysqli_KM_VM();
//$lista_pracownikow = $_SESSION['lista_pracownikow'];   //czyli w tej zmiennej mamy listę pracowników pobraną z plików pdf.
//$table=$_SESSION['table'];   //to tabela, którą potem wyświetla html


// Obsługa przycisków
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'sms' || $_POST['action'] === 'email' || $_POST['action'] === 'email_and_SMS')) {
    $Lp = $_POST['Lp']; // Pobranie numeru wiersza
    $nazwisko_imie = $_POST['nazwisko_imie'];
    $nr_telefonu = $_POST['nr_telefonu'];
    $adres_email = $_POST['adres_email'];
    $action = $_POST['action'];

    if ($action === 'sms') { //nie mam już takiego buttona
        $tekst_wiadomosci = $_SESSION['lista_pracownikow'][$Lp - 1]['haslo_pdf'];
        $result = SendSMSNokia($nr_telefonu, $tekst_wiadomosci);
    } elseif ($action === 'email') {  //nie mam już takiego buttona
        $tytul = 'Odcinek wypłaty - ' . $_SESSION['miesiac_wyplaty'] . '.';
        $tresc = 'Dzień dobry,W załączniku przesyłamy pasek z wypłaty za ' . $_SESSION['miesiac_wyplaty'] . '.';
        $sciezka_pdf = $_POST['sciezka_pdf'];
        $result = sendEmail($adres_email, $tytul, $tresc, $sciezka_pdf);
    } elseif ($action === 'email_and_SMS') {
        $tytul = 'Odcinek wypłaty - ' . $_SESSION['miesiac_wyplaty'] . '.';
        $tresc = 'Dzień dobry, <br>w załączniku przesyłamy pasek z wypłaty za ' . $_SESSION['miesiac_wyplaty'] . '.<br>';
        $sciezka_pdf = $_POST['sciezka_pdf'];

        $password = $_SESSION['lista_pracownikow'][$Lp - 1]['haslo_pdf'];
        $sciezka_pdf_local = str_replace("http://192.168.101.203/pages/", "/home/kmadzia/www/pages/", $sciezka_pdf); // Zamiana na lokalną ścieżkę

        if (!$set_protection) {   //jeśli zmienna $set_protection to znaczy ze przy podziale pliku nie był on zaszyfrowany, więc szyfrujemy go teraz
            setProtection($sciezka_pdf_local, $password); //szyfruje plik pdf
        }

        $result = sendEmail($adres_email, $tytul, $tresc, $sciezka_pdf_local, $is_html = true);
        $_SESSION['lista_pracownikow'][$Lp-1]['Last_SMS_Email_date']=date("Y-m-d H:i:s");   //aktualizujemy zmienną w sesji

        $sql = 'SELECT id FROM km_base.aa01_workers WHERE phone_number = ' . $nr_telefonu . '';
        $worker_id = fetch_data($connection, $sql); // Pobranie id pracownika na podstawie numeru telefonu
        $event_datetime = new DateTime();

        $tekst_wiadomosci = 'Dzień dobry. Hasło do pliku pdf z paskiem wypłaty za ' . $_SESSION['miesiac_wyplaty'] . ' to: ' . $password;

        $result = SendSMSNokia($nr_telefonu, $tekst_wiadomosci); //wysyła SMS do pracownika
        $sendCopyToNokia = SendSMSNokia(789757533, $nr_telefonu); //kopia wysyłana na Nokię - w treści numer telefonu pracownika na który wysłano SMS

        //zmienia stan wysyłki dla danego pracownika
        $_SESSION['lista_pracownikow'][$Lp - 1]['czy_juz_byla_wysylka'] = true;



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
            // echo "Dane zostały dodane!";
        } catch (Exception $e) {
            echo "Wystąpił błąd: " . $e->getMessage();
        }

        $readSMSsFromNokia = ReadSMSNokia($messages_count = 100); //pobiera wszystkie SMSy z Nokii
        $SMS_delivery_check = findSMSByPhoneNumber($worker_id[1][0]['id'], $nr_telefonu, $readSMSsFromNokia);  //sprawdza czy na Nokię dotarła kopia SMSa wysłanego do pracownika
        $_SESSION['lista_pracownikow'][$Lp-1]['Last_SMS_confirmation_date']=$SMS_delivery_check['date'];   //aktualizujemy zmienną w sesji

        //DOPISAĆ IFA - ZE MA TO ROBIĆ JEŚLI KOPIA DOTARŁA DO NOKII
        try {
            insertIntoTable($connection, $insert_into_table, $SMS_delivery_check);  //dodaje do bazy rezultat pobierania potwierdzenia
            //echo "Dane zostały dodane!";
        } catch (Exception $e) {
            echo "Wystąpił błąd: " . $e->getMessage();
        }
    }
}


if (isset($_POST['action']) && $_POST['action'] === 'refresh_SMS') {  //odświeża potwierdzenie SMS
    foreach ($_SESSION['lista_pracownikow'] as $row) {
        $nr_telefonu = $row['nr_telefonu'];
        if (empty($nr_telefonu))
            continue; // pomiń jeśli brak numeru telefonu

        // Pobierz ID pracownika na podstawie numeru telefonu
        $sql = "SELECT id FROM km_base.aa01_workers WHERE phone_number = '" . $nr_telefonu . "'";
        $worker_id = fetch_data($connection, $sql);
        if (empty($worker_id[1][0]['id']))
            continue; // pomiń jeśli brak ID

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
                //echo "Dane zostały dodane dla pracownika o nr: $nr_telefonu<br>";
            } catch (Exception $e) {
                echo "Błąd przy dodawaniu danych dla nr $nr_telefonu: " . $e->getMessage() . "<br>";
            }
        }
    }
}


//$lista_pracownikow = $_SESSION['lista_pracownikow'];   //czyli w tej zmiennej mamy listę pracowników pobraną z plików pdf.
//$table=$_SESSION['table'];   //to tabela, którą potem wyświetla html

?>

<!--*********************************WYŚWIETLANIE STRONY*************************************************************  -->


<head>
    <meta charset="UTF-8">
    <title>Moja Strona</title>
    <script>
        setInterval(function() {
            fetch('http://192.168.101.203/pages/PASKI_WYPLATY/zapisz_data_czas_do_pliku_txt.php')
                .then(response => response.text())
                .then(data => console.log('PHP skrypt wykonany:', data))
                .catch(error => console.error('Błąd:', error));
        }, 10000);
    </script>
</head>
<body>
    <br><br>
    <div style="font-size: 2em; font-weight: bold; margin-bottom: 20px;">
        Paski wypłat za <span style="color: darkviolet;">
    <?php echo htmlspecialchars($_SESSION['miesiac_wyplaty'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
</span>

    </div>
    <div class="content" display: flex; gap: 20px;>
        <table border="1">
            <tr>
                <th>Lp</th>
                <th>Nazwisko i imię</th>
                <th>Nr telefonu</th>
                <th>Adres e-mail</th>
                <th>Last SMS Email Date</th>
                <th>Last SMS Confirmation Date</th>
                <th>Czas od <br>ostatniego email'a / SMSa<br>(godziny)</th>
                <th style="width: 120px;">Akcja</th>
                <th style="width: 120px;">Podgląd PDF</th>
                <th style="width: 120px;">Pasek wysłany?</th>
            </tr>

            <!-- Wiersze danych -->
            <?php foreach ($_SESSION['lista_pracownikow'] as $row): ?>
                <tr id="row-<?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?>">
                    <td><?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['nazwisko_imie'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['nr_telefonu'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['adres_email'] ?? 'brak adresu', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['Last_SMS_Email_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['Last_SMS_confirmation_date'] ?? '2000-01-01', ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                        <?php //oblicza ile minut temu było ostatni mail
                            $lastEmailDate = new DateTime($row['Last_SMS_Email_date'] ?? '2000-01-01');
                            $lastSMSlDate = new DateTime($row['Last_SMS_confirmation_date'] ?? '2000-01-01');
                            $now = new DateTime();
                            $interval_email = $now->diff($lastEmailDate);
                            $interval_SMSconf = $now->diff($lastSMSlDate);
                            $totalHoursfromEmail = ($interval_email->days * 24 * 60)  + $interval_email->i;
                            $totalHoursfromSMSConf = ($interval_SMSconf->days * 24 * 60) + $interval_SMSconf->i;
                            $bgColor = ($totalHoursfromEmail < 1440 || $totalHoursfromSMSConf < 1440) ? 'background-color:rgb(233, 22, 22);' : '';
                            echo '<div style="text-align: center; ' . $bgColor . '">' . $totalHoursfromEmail . ' / ' . $totalHoursfromSMSConf . '</div>';
                            ?>

                    </td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="Lp"
                                value="<?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="nazwisko_imie"
                                value="<?php echo htmlspecialchars($row['nazwisko_imie'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="nr_telefonu"
                                value="<?php echo htmlspecialchars($row['nr_telefonu'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="adres_email"
                                value="<?php echo htmlspecialchars($row['adres_email'] ?? 'brak adresu', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="sciezka_pdf"
                                value="<?php echo htmlspecialchars($row['sciezka_pdf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="action" value="email_and_SMS" <?php echo ($_SESSION['lista_pracownikow'][$row['Lp'] - 1]['czy_juz_byla_wysylka'] 
                            === true) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                E-mail + SMS
                            </button>
                        </form>
                    </td>
                    <!-- Ukryty input przechowujący ścieżkę PDF -->
                    <td>
                        <input type="hidden" id="pdf-<?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?>"
                            value="<?php echo htmlspecialchars($row['sciezka_pdf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <button 
                            type="button"
                            style="top: 2px;<?php echo ($_SESSION['lista_pracownikow'][$row['Lp'] - 1]['czy_juz_byla_wysylka'] === true) ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                            onclick="pokazPDF('<?php echo htmlspecialchars($row['Lp'], ENT_QUOTES, 'UTF-8'); ?>')"
                            <?php echo ($_SESSION['lista_pracownikow'][$row['Lp'] - 1]['czy_juz_byla_wysylka'] === true) ? 'disabled' : ''; ?>
                        >Podgląd PDF</button>
                    <td
                        style="text-align: center;<?php echo $_SESSION['lista_pracownikow'][$row['Lp'] - 1]['czy_juz_byla_wysylka'] ? 'background-color:rgb(196, 99, 34);' : ''; ?>">
                        <?php echo $_SESSION['lista_pracownikow'][$row['Lp'] - 1]['czy_juz_byla_wysylka'] ? 'Tak' : 'Nie'; ?>
                    </td>

                </tr>



            <?php endforeach; ?>
        </table>

        <form method="post">
            <button type="submit" name="action" value="refresh_SMS" style=background-color:rgb(131, 194, 102);">Odśwież
                SMS</button>
        </form>

        <form method="post">
            <button type="submit" name="action" value="reset_sesji"
                style="background-color: lightcoral;">Resetuj</button>
        </form>


        <div style="width: 30%;"> <!--kontener na PDF -->
            <iframe id="pdf-viewer" style="width: 100%; height: 500px; border: 1px solid #ccc;"></iframe>
        </div>

        <button onclick="window.location.href='http://192.168.101.203/index.php?page=PASKI_WYPLATY/dane_pracownikow'">
            Przejdź do danych pracowników
        </button>

    </div>

    </div>



    <script>
        function pokazPDF(Lp) {
            // Pobieranie wartości z ukrytego pola input
            const sciezkaPDF = document.getElementById(`pdf-${Lp}`).value;
            const pdfViewer = document.getElementById('pdf-viewer');

            if (sciezkaPDF && pdfViewer) {
                console.log("Ładowanie PDF:", sciezkaPDF);  // Debugowanie w konsoli
                pdfViewer.src = sciezkaPDF + "#zoom=200";
            } else {
                console.error("Błąd: Nie znaleziono ścieżki PDF dla Lp:", Lp);
                alert("Nie można wyświetlić pliku PDF. Sprawdź poprawność danych.");
            }
        }


     setInterval(function() {
    fetch('zapisz_data_czas_do_pliku_txt.php')
        .then(response => response.text())
        .then(data => {
            console.log('PHP skrypt wykonany:', data);

            // Pobranie elementu do wyświetlania komunikatu
            const infoBox = document.getElementById('status-box');
            if (infoBox) {
                infoBox.innerText = 'Ostatnia aktualizacja: ' + data;
            }
        })
        .catch(error => console.error('Błąd:', error));
}, 15000); // 15 sekund



    </script>



</body>