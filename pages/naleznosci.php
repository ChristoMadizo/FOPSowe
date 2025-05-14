<?php
session_start();
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection=db_connect_firebird_FAKT_LIVE();

$sql='SELECT * FROM ZZ_TMP_NALEZNOSCI';

$faktury_zaplaty_info = fetch_data($connection, $sql); //

$table = [];   //buduje tabelę z danymi o fakturach

$kolumny_do_zaczytania = ['KONTRAHENT','NIP','RODZAJLIMITU','DATAPRZYZNANIALIMITU',
'WYSOKOSC_LIMITU','DO ZAPŁATYPLN','Przed terminemPLN','Po terminiePLN','Sprzedaż ubezpieczonaPLN','Do zapłatyPLN','NajdlUbezpPrzetermDNI']; // Kolumny do zaczytania


list($result, $faktury_zaplaty_info) = fetch_data($connection, $sql); // <- poprawne wyciągnięcie danych (destrukturyzacja)

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

    $sumaSprzedazUbezpieczona = 0;
    foreach ($table as $row) {
        $sumaSprzedazUbezpieczona += (float)($row['Sprzedaż ubezpieczonaPLN'] ?? 0);
    }
    
    $składkaUbezpieczeniowa = $sumaSprzedazUbezpieczona * 0.0019;

    //obliczenie symulowanej wysokości składki do końca roku 2025
    $start_date = new DateTime('2025-02-01');
    $end_date = new DateTime('2025-12-31');
    $current_date = new DateTime();
    $days_elapsed = $start_date->diff($current_date)->days;
    $total_days = $start_date->diff($end_date)->days;
    $ratio = $total_days > 0 ? $days_elapsed / $total_days : 0;
    $symulowana_wysokosc_skladki = $składkaUbezpieczeniowa/$ratio;
   


}

?>


<!DOCTYPE html>
<html lang="pl">

<!--przycisk do odświeżania danych-->
<script>
document.getElementById("refreshData").addEventListener("click", function() {
    fetch("Naleznosci/scripts.php")
        .then(response => response.text())
        .then(data => document.getElementById("response").innerText = data)
        .catch(error => console.error("Błąd:", error));
});
</script>


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
            table-layout: auto; /* Automatyczne dostosowanie szerokości */
        }

        th {
            position: sticky;
            top: 0;
            top: 30px;
            background-color: rgb(184, 191, 231);
            z-index: 10; /* Nagłówek zawsze na wierzchu */

        }

        th, td {
            border: 1px solid #ddd; /* Obramowanie komórek */
            padding: 8px; /* Wewnętrzne odstępy */
            text-align: center; /* Wyrównanie tekstu */
            word-wrap: break-word;
            overflow: hidden;
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
    <h1 style="text-align: center;">Dane dla ubezpieczonych klientów.
  <!--  <button id="refreshData" style="position: absolute; top: 100px; right: 20px; width: 100px;">Uruchom skrypt</button>  -->
    </h1>
    <p style="text-align: left; font-weight: bold; margin-left: 200px;">
        Suma sprzedaży ubezpieczonej: <span style="color:rgb(37, 59, 184);"><?php echo number_format($sumaSprzedazUbezpieczona, 0, '', ' '); ?> PLN</span>
        <br>Składka ubezpieczeniowa: <span style="color:rgb(37, 59, 184);"><?php echo number_format($składkaUbezpieczeniowa, 0, '', ' '); ?> PLN</span>
        <br>Symulowana wysokość składki do końca 2025 roku: <span style="color:rgb(37, 59, 184) ;"><?php echo number_format($symulowana_wysokosc_skladki, 0, '', ' '); ?> PLN</span>
        <span style="font-size: 14px; color: grey;">(Minimalna wysokość składki: 34 000 PLN)</span>
    </p>
    <table>
        <thead>
            <tr>
                <!-- Ręcznie określone kolumny -->
                <th style="width: 10px;">KONTRAHENT</th>
                <th>RODZAJ LIMITU</th>
                <th>DATA PRZYZNANIA LIMITU</th>
                <th>WYSOKOŚĆ LIMITU</th>
             <!--   <th>FAKTURY KLUCZ KONTR</th>  -->
                <th>NIP</th>
                <th>Do zapłaty<br>(PLN)<br><span style="font-size: 80%; color: blue;">(%limitu)</span></th>
                <th>Przed terminem<br>(PLN)</th>
                <th>Po terminie<br>(PLN)</th>
                <!--<th>0-15 dni</th>
                <th>16-30 dni</th>
                <th>31-60 dni</th>
                <th>61-90 dni</th>
                <th>91-180 dni</th>
                <th>181-365 dni</th>
                <th>365+ dni</th>  -->
                <th>Sprzedaż ubezpieczona<br>(PLN)</th>
                <th>Najdł. przeter. ubezpieczona faktura (dni)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Dynamiczne generowanie wierszy danych -->
            <?php foreach ($table as $row): ?>
                <tr>
                    <td style="width: 10px;"><?php echo htmlspecialchars($row['KONTRAHENT'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="width: 100px;"><?php echo htmlspecialchars($row['RODZAJLIMITU'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="width: 110px;"><?php echo htmlspecialchars($row['DATAPRZYZNANIALIMITU'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="width: 100px;"><?php echo number_format((float)($row['WYSOKOSC_LIMITU'] ?? 0), 0, '', ' '); ?></td>
                 <!--   <td><?php echo htmlspecialchars($row['FAKTURY KLUCZ KONTR'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>  -->
                    <td style="width: 80px;"><?php echo htmlspecialchars($row['NIP'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="width: 100px; position: relative; overflow: hidden;">
                        <?php
                            $doZaplaty = (float)($row['Do zapłatyPLN'] ?? 0);
                            $wysokoscLimitu = (float)($row['WYSOKOSC_LIMITU'] ?? 0);
                            $procent = ($wysokoscLimitu > 0) ? ($doZaplaty / $wysokoscLimitu) * 100 : 0;

                            echo number_format($doZaplaty, 0, '', ' ') . ' <span style="color: blue; font-size: 14px;">(' . number_format($procent, 0) . '%)</span>';
                            
                            // Obliczanie stopniowego przejścia koloru od zielonego do czerwonego
                            $r = min(255, round(255 * ($procent / 100))); // Czerwony zwiększa się
                            $g = max(0, round(255 * ((100 - $procent) / 100))); // Zielony zmniejsza się
                            $color = "rgb($r, $g, 0)";
                        ?>

                        <div style="width: 100%; height: 10px; background-color: #ddd; position: relative;">
                            <div style="width: <?php echo min($procent, 100); ?>%; height: 100%; background-color: <?php echo $color; ?>;"></div>
                        </div>
                    </td>




                   <!-- <td style="width: 100px;"><?php echo number_format((float)($row['Do zapłatyPLN'] ?? 0), 0, '', ' '); ?></td> -->
                    <td style="width: 100px;"><?php echo number_format((float)($row['Przed terminemPLN'] ?? 0), 0, '', ' '); ?></td>
                    <td style="width: 100px;"><?php echo number_format((float)($row['Po terminiePLN'] ?? 0), 0, '', ' '); ?></td>
                    <!-- <td><?php echo htmlspecialchars($row['0-15 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['16-30 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['31-60 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['61-90 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['91-180 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['181-365 dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['365+ dniPLN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td> -->
                    <td style="width: 120px;"><?php echo number_format((float)($row['Sprzedaż ubezpieczonaPLN'] ?? 0), 0, '', ' '); ?></td>
                    <td style="width: 80px;">
                        <?php   //jeśli wartość jest większa od 0 (czyli przed terminem), albo 9999 (brak przetermiowania), to wyświetl '-'
                        $value = $row['NajdlUbezpPrzetermDNI'] ?? '';
                        echo ($value == 9999 || $value > 0) ? '-' : htmlspecialchars($value * -1, ENT_QUOTES, 'UTF-8'); 
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

