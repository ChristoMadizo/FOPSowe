<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection=db_connect_firebird_FAKT_LIVE();

$sql='SELECT * FROM FAKTURY_LISTA_UBEZPIECZENIE';

$faktury_zaplaty_info = fetch_data($connection, $sql); //

$table = [];   //buduje tabelę z danymi o fakturach

$kolumny_do_zaczytania = ['KONTRAHENT','WALUTA']; // Kolumny do zaczytania

foreach ($faktury_zaplaty_info as $row) {
    $row_data = []; // Nowa tablica do przechowywania wiersza
    foreach ($row as $key => $value) {
        // Jeśli kolumna jest na liście do zaczytania, dodajemy ją do row_data
        if (in_array($key, $kolumny_do_zaczytania)) {
            $row_data[$key] = $value;
        }
    }
    // Dodajemy wiersz do głównej tablicy
    $table[] = $row_data;
}

?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktury Zapłaty</title>
    <style>
        /* Styl dla tabeli */
        table {
            width: 80%; /* Szerokość tabeli */
            margin: auto; /* Wyśrodkowanie tabeli */
            border-collapse: collapse; /* Usunięcie przerw między ramkami */
        }
        th, td {
            border: 1px solid #ddd; /* Obramowanie komórek */
            padding: 8px; /* Wewnętrzne odstępy */
            text-align: center; /* Wyrównanie tekstu */
        }
        th {
            background-color: #f2f2f2; /* Kolor tła nagłówków */
        }
        tr:nth-child(even) {
            background-color: #f9f9f9; /* Kolor dla parzystych wierszy */
        }
        tr:hover {
            background-color: #d6d6d6; /* Efekt hover dla wierszy */
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Faktury Zapłaty</h1>
    <table>
        <thead>
            <tr>
                <!-- Dynamiczne generowanie nagłówków tabeli -->
                <?php if (!empty($table)): ?>
                    <?php foreach (array_keys($table[0]) as $column_name): ?>
                        <th><?php echo htmlspecialchars($column_name, ENT_QUOTES, 'UTF-8'); ?></th>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <!-- Dynamiczne generowanie wierszy danych -->
            <?php foreach ($table as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
