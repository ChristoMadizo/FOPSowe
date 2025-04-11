<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';
session_start();

// Połączenie z bazą danych
$connection = db_connect_mysqli();

// Zapytanie SQL
$sql = "
    -- lista zleceń z ostatnich 3 miesięcy
    SELECT DISTINCT orders.serial
    FROM prosto.orders orders
    WHERE orders.created >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
";

// Pobranie danych z bazy danych
$result = mysqli_query($connection, $sql);
$result_all_data = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $result_all_data[] = $row; // Każdy wiersz dodajemy jako osobną tablicę
    }
} else {
    echo "Błąd zapytania: " . mysqli_error($connection);
}

?>

<div class="content">
    <h1>Wybór zamówienia</h1>

    <!-- Formularz do filtrowania grup zleceń -->     
    <form method="POST">                      <!--       tu wstawia wartości z SESJI   -->
        <label for="zlecenie">Numer zamówienia:</label>
        <input type="text" name="zlecenie" id="zlecenie" placeholder="Wpisz numer zamówienia" value="<?php echo htmlspecialchars($_SESSION['zlecenie'] ?? ''); ?>">
        <button type="submit" name="action" value="filter" style="position: relative; left: 50px; width: 100px;">Pokaż</button>
        <button type="button" onclick="ClearSessionKM()" style="position: relative; left: 60px; width: 120px;">Wyczyść Sesję</button>
    </form>

    <?php
    // Obsługa filtrowania
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter') {
        $filtered_zlecenie = $_POST['zlecenie'];

        $_SESSION['zlecenie'] = $filtered_zlecenie;   // Zapisanie wartości w sesji

        // Filtrowanie danych
        $filtered_data = array_filter($result_all_data, function ($row) use ($filtered_zlecenie) {
            return strpos($row['serial'], $filtered_zlecenie) !== false;
        }); 

        if (count($filtered_data) > 1) {
            echo '<p>Znaleziono więcej niż jedno zamówienie - wprowadź cały numer</p>';
        } elseif (count($filtered_data) === 1) {
            $final_zlecenie = reset($filtered_data)['serial'];

            // Ścieżka do pliku SQL
            $filePath = '/home/kmadzia/www/SQL/FAKTURY_SEKRETARIAT.txt';
            $zawartosc_zlecenia = executeSQL($connection, $filePath, $final_zlecenie);

            // Pobranie listy towarów
            $lista_towarow = executeBatchFileOnKMpc('lista_towarow_FAKT');
            $lista_towarow = array_filter($lista_towarow, function($line) {
                return !empty($line) && preg_match('/[a-zA-Z0-9]/', $line);
            });

            // Wywołanie funkcji i przechowywanie tabeli w zmiennej
            $table = display_table_from_arrayFAKTURY(
                $zawartosc_zlecenia,
                ['Zamowienie', 'DataZamowienia', 'Ilosc', 'Cena', 'PositionTotalAmount', 'CurrencyCode', 'JM', 'Nazwa_produktu', 'Name_fakt', 'name_fakt2'],
                $lista_towarow
            );

            $_SESSION['table'] = $table;
          
        } else {
            echo '<p>Nie znaleziono zamówienia. Spróbuj jeszcze raz.</p>';
        }
    }

    if (isset($_SESSION['table'])) {
        echo '<h2>Ostatnio wybrane zamówienie:</h2>';
        echo $_SESSION['table'];
    }

    ?>
</div>
