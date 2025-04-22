<?php


    session_start();
     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_sesji') {
        //session_unset(); // Tylko reset_sesji czyści sesję
      //  $_POST['action']=[];
    };

    require '/home/kmadzia/www/vendor/autoload.php';
    require '/home/kmadzia/www/includes/functions.php';

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $path_origin = '/home/kmadzia/www/pages/PASKI_WYPLATY/TestySPLIT/joined.pdf';
    $path_destination = '/home/kmadzia/www/pages/PASKI_WYPLATY/PDFyDoOdczytu';
    //$result = splitPdf($path_origin, $path_destination);

    $lista_pracownikow=getAllNamesFromPdfDir($path_destination);  //lista pracowników pochodzi z poszczególnych plików pdf

    
    foreach ($lista_pracownikow as $index => $pracownik) {  //buduje tabelę: pierwsza jest Lp, druga to nazwisko i imię, trzecia to nr telefonu, czwarta to adres email
        // Dodajemy pracownika do tablicy
        $table[] = [
            'Lp' => $index + 1,
            'nazwisko_imie' => $pracownik['nazwisko_imie'],
            'nr_telefonu' => '',
            'adres_email' => '',
            'sciezka_pdf' => $pracownik['sciezka_pdf']  // ← nowa kolumna
        ];
    }
    
    $connection=db_connect_mysqli_KM_VM();

    $sql="select CONCAT(surname,' ',name) as nazwisko_imie,name,surname,phone_number,e_mail_address as e_address from km_base.aa01_workers";
    $workers_info= fetch_data($connection, $sql)[1];  //pobiera info o pracownikach

    // Iteracja przez tabelę pracowników
    foreach ($table as $index => $row) {
        // Dopasowanie nazwiska i imienia
        if ($row['nazwisko_imie'] === $workers_info[0]['nazwisko_imie']) {
            // Dodaj dane do wiersza tabeli
            $table[$index]['adres_email'] = $workers_info[0]['e_address'];
            $table[$index]['nr_telefonu'] = $workers_info[0]['phone_number'];
        }
    }

    //$table_for_display=display_table_from_array($table,['nazwisko_imie','nr_telefonu','adres_email']);  - to już NIAKTUALNE, BO WYŚWIETLAMY FORMULARZ Z PRZYCISKAMI


//********************************obsługa PRZYCISKÓW W WIERSZACH ************************************************************

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&($_POST['action'] === 'sms' || $_POST['action'] === 'email')) {
    
    $nazwisko_imie = $_POST['nazwisko_imie'];
    $nr_telefonu = $_POST['nr_telefonu'];
    $adres_email = $_POST['adres_email'];
    $action = $_POST['action'];

    if ($action === 'sms') {
        // Wysyłanie SMS-a
        $tekst_wiadomosci = "Co tam u ciebie?";
        $result = SendSMSNokia($nr_telefonu, $tekst_wiadomosci);
       /* echo $result                                                          //na razie WYWALAM
            ? "<p>SMS został wysłany do $nazwisko_imie na numer $nr_telefonu.</p>"
            : "<p>Nie udało się wysłać SMS-a do $nazwisko_imie.</p>"; */
    } elseif ($action === 'email') {
        // Wysyłanie e-maila
        $tytul = "To jest tytul";
        $tresc = "Witamy z formularza";
        $sciezka_pdf = $_POST['sciezka_pdf'];
        $result = sendEmail($adres_email, $tytul, $tresc, $sciezka_pdf);

      /*  echo $result                                                           //na razie WYWALAM
            ? "<p>E-mail został wysłany do $nazwisko_imie na adres $adres_email.</p>"
            : "<p>Nie udało się wysłać e-maila do $nazwisko_imie.</p>";*/
    }

}
?>

 

<!--//*********************************WYŚWIETLANIE STRONY*************************************************************  -->

<div class="content">
    <table border="1">
        <tr>
            <!-- Nagłówki tabeli -->
            <?php foreach (array_keys($table[0]) as $column_name): ?>
                <th><?php echo htmlspecialchars($column_name, ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
            <th>Akcja</th>
        </tr>

        <!-- Wiersze danych -->
        <?php foreach ($table as $row): ?>
            <tr>
                <!-- Kolumny danych -->
                <?php foreach ($row as $key => $value): ?>
                    <td><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endforeach; ?>

                <!-- Kolumna z przyciskami akcji -->
                <td>
                    <form method="post">
                        <!-- Przekazanie wszystkich danych wiersza -->
                        <input type="hidden" name="nazwisko_imie" value="<?php echo htmlspecialchars($row['nazwisko_imie'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="nr_telefonu" value="<?php echo htmlspecialchars($row['nr_telefonu'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="adres_email" value="<?php echo htmlspecialchars($row['adres_email'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="sciezka_pdf" value="<?php echo htmlspecialchars($row['sciezka_pdf'], ENT_QUOTES, 'UTF-8'); ?>">  <!-- uzupełnienie scieżki do pliku pdf -->

                        <!-- Przyciski akcji -->
                        <button type="submit" name="action" value="sms">Wyślij SMS</button>
                        <button type="submit" name="action" value="email">Wyślij E-mail</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form method="post">
        <button type="submit" name="action" value="reset_sesji">Resetuj</button>
    </form>               

</div>


