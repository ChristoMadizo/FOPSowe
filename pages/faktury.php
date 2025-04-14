<?php

session_start();

// Usuwanie danych sesji tylko wtedy, gdy akcja nie jest "zapisz_zmiany"
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (isset($_POST['action']) && $_POST['action'] !== 'zapisz_zmiany')) {
    session_unset(); // Usuwa wszystkie dane sesji
}


ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';


// Połączenie z bazą danych
$connection = db_connect_mysqli();

// Zapytanie SQL
$sql = "
    -- lista zleceń z ostatnich 3 miesięcy
    SELECT DISTINCT orders.serial
    FROM prosto.orders orders
    WHERE orders.created >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
";

// Pobranie listy towarów istniejących w bazie FAKT          WYŁĄCZAM NA TESTY

//$lista_towarow = executeBatchFileOnKMpc('lista_towarow_FAKT');  //pobieranie listy towarów występujących w FAKT - WYŁĄCZAM NA TESTY

//***************************wersja do testów - nie pobieram listy towarów z FAKT, ale z mocno obciętego pliku tekstowego
$file_path = '/home/kmadzia/www/data/lista_towarow_FAKT_temp.txt';
// Wczytanie zawartości pliku do tablicy
$lista_towarow = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);  //to lista towarów występujących w FAKT
//***************************wersja do testów - nie pobieram listy towarów z FAKT, ale z mocno obciętego pliku tekstowego



?>
    <?php
    // Obsługa filtrowania
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter') {
        session_unset(); // Usuwa wszystkie dane sesji   USUWA DANE SESJI

        $zamowienie = $_POST['zamowienie'];

        // Kliknięto "Pokaż", więc pobiera dane z bazy dla danego zamówienia
        $result = mysqli_query($connection, $sql);
        $result_all_data = [];

        if ($result) { //przetwarza pobrane dane  - zmienia format
            while ($row = mysqli_fetch_assoc($result)) {
                $result_all_data[] = $row; // Każdy wiersz dodajemy jako osobną tablicę
            }
        } else {
            echo "Błąd zapytania: " . mysqli_error($connection);
        }


       // $_SESSION['zlecenie'] = $filtered_zlecenie;   // Zapisanie wartości w sesji
       //$_SESSION['form_data'] = $_POST;  //zapisanie wszystkich danych formularza w sesji

       // Pobieramy dane z sesji (jeśli istnieją)
        //$formData = $_SESSION['form_data'] ?? [];

        // Filtrowanie danych wg numeru zamówienia
        $filtered_data = array_filter($result_all_data, function ($row) use ($zamowienie) {
            return strpos($row['serial'], $zamowienie) !== false;
        }); 

        if (count($filtered_data) > 1) {
            echo '<p>Znaleziono więcej niż jedno zamówienie - wprowadź cały numer</p>';
        } elseif (count($filtered_data) === 1) {
            $final_zlecenie = reset($filtered_data)['serial'];

            // Ścieżka do pliku SQL
            $filePath = '/home/kmadzia/www/SQL/FAKTURY_SEKRETARIAT.txt';
            $zawartosc_zlecenia = executeSQL($connection, $filePath, $final_zlecenie);

          //  $lista_towarow=['WOBLER.'];
            $lista_towarow = array_filter($lista_towarow, function($line) {    //tu usuwa puste linie i linie bez alfanumerycznych znaków
                return !empty($line) && preg_match('/[a-zA-Z0-9]/', $line);
            });


            // Wywołanie funkcji i przechowywanie definicji HTML tabeli w zmiennej
            $table = display_table_from_arrayFAKTURY(
                $zawartosc_zlecenia,
                ['Zamowienie', 'DataZamowienia', 'Ilosc', 'Cena', 'PositionTotalAmount', 'CurrencyCode', 'JM', 'Nazwa_produktu', 'Name_fakt', 'name_fakt2'],
                $lista_towarow
            );

            $_SESSION['results_all_data'] = $zawartosc_zlecenia; // Zapisanie wyników do sesji
          
        } else {
            echo '<p>Nie znaleziono zamówienia. Spróbuj jeszcze raz.</p>';
        }
    }

    //po kliknięciu "Zapisz zmiany" - zapisujemy dane formularza do sesji
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'zapisz_zmiany') {

    
        // Dane z kolumny przesłane przez użytkownika
        $updated_column = $_POST['name_fakt'] ?? []; // Pobiera dane z 'name_fakt' przesłane w POST

        // Tworzymy zmienną, która będzie śledzić aktualny indeks
        $current_index = 0;

        // Iteracja po wynikach w sesji
        foreach ($_SESSION['results_all_data'] as &$row) {
            // Sprawdzamy, czy istnieje wartość w tablicy $updated_column dla danego indeksu
            if (isset($updated_column[$current_index])) {
                // Przypisujemy wartość z $updated_column na podstawie aktualnego indeksu
                $row['name_fakt'] = $updated_column[$current_index];
                
                // Inkrementujemy indeks, aby przy następnej iteracji przypisać kolejną wartość
                $current_index++;
            }
        }


    
        // Zapisujemy zaktualizowane dane w sesji
        $_SESSION['results_all_data'] = $_SESSION['results_all_data']; // Aktualizujemy dane sesji
    
        // Generujemy nową tabelę z zaktualizowanymi danymi
        $table = display_table_from_arrayFAKTURY(
            $_SESSION['results_all_data'], // Używamy zaktualizowanych danych z sesji
            ['Zamowienie', 'DataZamowienia', 'Ilosc', 'Cena', 'PositionTotalAmount', 'CurrencyCode', 'JM', 'Nazwa_produktu', 'Name_fakt', 'name_fakt2'],
            $lista_towarow
        );

        // Zapisujemy zaktualizowaną tabelę do sesji
      //  $_SESSION['updated_table'] = $result_all_data;
    }
    
    
    if (isset($_SESSION['table'])) {
        echo '<h2>Ostatnio wybrane zamówienie:</h2>';
        echo $_SESSION['table'];
    }


    ?>





<div class="content">
    <h1>Wybór zamówienia</h1>

    <!-- Formularz do filtrowania grup zleceń -->     
    <form method="POST">                      <!--       tu wstawia wartości z SESJI   -->
        <label for="zlecenie">Numer zamówienia:</label>
        <input type="text" name="zamowienie" id="zamowienie" placeholder="Wpisz numer zamówienia" value="<?php echo htmlspecialchars($_SESSION['zamowienie'] ?? ''); ?>">

         <!-- Wywołanie funkcji generującej tabelę -->
          <?php if (!empty($table)) echo $table; ?>
          
          <?php// echo display_table_from_arrayFAKTURY($result_all_data, ['Zamowienie', 'Name_fakt'], $lista_towarow); ?>  

        <button type="submit" name="action" value="filter" style="position: relative; left: 50px; width: 100px;">Pokaż</button>
        <button type="submit" name="action" value="zapisz_zmiany" style="position: relative; left: 50px; width: 100px;">Zapisz zmiany</button>
        <button type="button" onclick="ClearSessionKM()" style="position: relative; left: 60px; width: 120px;">Resetuj</button>
    </form>

</div>