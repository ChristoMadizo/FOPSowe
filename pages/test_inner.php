<?php
$host = 'localhost';
$port = '3050';
$database_path = '/opt/firebird/FAKT_LIVE_COPY/0002BAZA.FDB';
$username = 'KRZYSIEK';
$password = 'Bielawa55';
$connection_string = "{$host}/{$port}:{$database_path}";

// Mierzymy czas połączenia
$t0 = microtime(true);
$conn = ibase_connect($connection_string, $username, $password, 'UTF8');
$t1 = microtime(true);

if (!$conn) {
    die("Błąd połączenia: " . ibase_errmsg());
}
echo "⏱ Czas połączenia: " . round($t1 - $t0, 3) . " sek\n";

// Mierzymy czas wykonania zapytania
$t2 = microtime(true);
$query = ibase_query($conn, "SELECT FIRST 100 * FROM UBEZP_04_FAK_ZAPL_FILT_AGGR"); // <-- Podmień na swoją tabelę
$t3 = microtime(true);

if (!$query) {
    die("Błąd zapytania: " . ibase_errmsg());
}
echo "⏱ Czas wykonania zapytania: " . round($t3 - $t2, 3) . " sek\n";

// Mierzymy czas pobierania danych
$t4 = microtime(true);
$row_count = 0;
while ($row = ibase_fetch_assoc($query)) {
    $row_count++;
}
$t5 = microtime(true);

echo "⏱ Czas pobierania danych: " . round($t5 - $t4, 3) . " sek\n";
echo "🔢 Łącznie wierszy: $row_count\n";

ibase_free_result($query);
ibase_close($conn);
?>
