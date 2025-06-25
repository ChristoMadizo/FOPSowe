<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$plik_csv = '/home/kmadzia/www/pages/Naleznosci/dane_UBEZP_05_FAK_ALL_FILT_AGGR.csv';
$table = [];



if (($handle = fopen($plik_csv, 'r')) !== false) {
    $naglowki = fgetcsv($handle, 0, ',');
    while (($dane = fgetcsv($handle, 0, ',')) !== false) {
        if (count($dane) === count($naglowki)) {
            $wiersz = array_combine($naglowki, $dane);
            $table[] = $wiersz; // bez filtrowania – ładujemy wszystko
        } else {
            // np. error_log("Niepoprawny wiersz: ".implode(',', $dane));
            continue;
        }
    }
    fclose($handle);
}

usort($table, function ($a, $b) {
    $aVal = (float)($a['Sprzedaż - ostatnie pół roku'] ?? 0);
    $bVal = (float)($b['Sprzedaż - ostatnie pół roku'] ?? 0);

    return $bVal <=> $aVal; // sortowanie malejąco (największe na górze)
});


$sumaSprzedazUbezpieczona = 0;
foreach ($table as $row) {
    $sumaSprzedazUbezpieczona += (float) ($row['Sprzedaż ubezpieczonaPLN'] ?? 0);
}
$składkaUbezpieczeniowa = $sumaSprzedazUbezpieczona * 0.0019;

$start_date = new DateTime('2025-02-01');
$end_date = new DateTime('2025-12-31');
$current_date = new DateTime();
$days_elapsed = $start_date->diff($current_date)->days;
$total_days = $start_date->diff($end_date)->days;
$ratio = $total_days > 0 ? $days_elapsed / $total_days : 0;
$symulowana_wysokosc_skladki = $ratio > 0 ? ($składkaUbezpieczeniowa / $ratio) : 0;
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>Dane z CSV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;
            table-layout: auto;
        }

        th {
            position: sticky;
            top: 30px;
            background-color: rgb(184, 191, 231);
            z-index: 10;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            overflow: hidden;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #d6d6d6;
        }
    </style>
</head>

<body>
    <h1 style="text-align: center;"><br></h1>
    <p style="margin-left: 200px; font-weight: bold;">
        Suma sprzedaży ubezpieczonej: <span
            style="color:rgb(37,59,184);"><?php echo number_format($sumaSprzedazUbezpieczona, 0, '', ' '); ?>
            PLN</span><br>
        Składka ubezpieczeniowa: <span
            style="color:rgb(37,59,184);"><?php echo number_format($składkaUbezpieczeniowa, 0, '', ' '); ?>
            PLN</span><br>
        Symulowana wysokość składki do końca 2025 roku: <span
            style="color:rgb(37,59,184);"><?php echo number_format($symulowana_wysokosc_skladki, 0, '', ' '); ?>
            PLN</span>
        <span style="font-size: 14px; color: grey;">(Minimalna wysokość składki: 34 000 PLN)</span>
    </p>
    <table>
        <thead>
            <tr>
                <th>KONTRAHENT</th>
                <th>RODZAJ LIMITU</th>
                <th>DATA PRZYZNANIA LIMITU</th>
                <th>WYSOKOŚĆ LIMITU</th>
                <th>NIP</th>
                <th>Do zapłaty (PLN)<br><span style="font-size: 80%; color: blue;">(%limitu)</span></th>
                <th>Przed terminem<br>(PLN)</th>
                <th>Po terminie<br>(PLN)</th>
                <th>Sprzedaż - ostatnie pół roku<br>(PLN)</th>
                <th>Sprzedaż ubezpieczona<br>(PLN)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($table as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['KONTRAHENT'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['RODZAJLIMITU'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['DATAPRZYZNANIALIMITU'] ?? ''); ?></td>
                    <td><?php echo number_format((float) ($row['WYSOKOSC_LIMITU'] ?? 0), 0, '', ' '); ?></td>
                    <td><?php echo htmlspecialchars($row['NIP'] ?? ''); ?></td>
                    <td style="position: relative; overflow: hidden; width: 100px;">
                        <?php
                        $doZaplaty = (float) ($row['Do zapłatyPLN'] ?? 0);
                        $limit = (float) ($row['WYSOKOSC_LIMITU'] ?? 0);
                        $procent = ($limit > 0) ? ($doZaplaty / $limit) * 100 : 0;

                        echo number_format($doZaplaty, 0, '', ' ') .
                            ' <span style="color: blue; font-size: 14px;">(' . number_format($procent, 0) . '%)</span>';

                        // Gradient: zielony do czerwonego
                        $r = min(255, round(255 * ($procent / 100))); // czerwony rośnie
                        $g = max(0, round(255 * ((100 - $procent) / 100))); // zielony maleje
                        $color = "rgb($r, $g, 0)";
                        ?>

                        <div
                            style="width: 100%; height: 10px; background-color: #ddd; position: relative; margin-top: 3px;">
                            <div
                                style="width: <?php echo min($procent, 100); ?>%; height: 100%; background-color: <?php echo $color; ?>;">
                            </div>
                        </div>
                    </td>

                    <td><?php echo number_format((float) ($row['Przed terminemPLN'] ?? 0), 0, '', ' '); ?></td>
                    <td><?php echo number_format((float) ($row['Po terminiePLN'] ?? 0), 0, '', ' '); ?></td>
                    <td><?php echo number_format((float) ($row['Sprzedaż - ostatnie pół roku'] ?? 0), 0, '', ' '); ?></td>
                    <td><?php echo number_format((float) ($row['Sprzedaż ubezpieczonaPLN'] ?? 0), 0, '', ' '); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>