<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Połączenie z bazą danych
$connection = db_connect_mysqli();

// Definicja zapytania SQL
$sql = "SELECT * FROM prosto_test.view_dd10_b_ORDERS_notCan_notDel_DzialHandlowy WHERE Serial LIKE '%LS%'";

// Pobranie danych z bazy
$zlecenia_w_dziale_handlowym = fetch_data($connection, $sql);

$to = "l.salachna@fops.pl";
$cc = "k.madzia@fops.pl";
$subject = "Lista zleceń w Dziale Handlowym";

$body = '<html>
<head>
    <style>
        table { border-collapse: collapse; width: auto; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; word-wrap: break-word; max-width: 200px; }
        th { background-color: #f2f2f2; text-align: center;}
        td { white-space: nowrap; text-align: center; }
    </style>
</head>
<body>
    <p><strong style="color: purple;">Lista zleceń stworzonych w ciągu ostatnich 24 godzin, które nadal pozostają w Dziale Handlowym:</strong></p>
    <table>
        <tr>
            <th>Nr Zlecenia</th>
            <th>Data i czas stworzenia</th>
            <th>Firma</th>
            <th>Godziny od stworzenia zlecenia</th>
        </tr>';

// Zlecenia młodsze niż 24 godziny
foreach ($zlecenia_w_dziale_handlowym[1] as $zlecenie) {
    if ($zlecenie['WiekZamowieniawGodzinach'] <= 24) {
        $body .= "<tr>
            <td>{$zlecenie['Serial']}</td>
            <td>{$zlecenie['OrderCreated']}</td>
            <td>{$zlecenie['Company']}</td>
            <td>{$zlecenie['WiekZamowieniawGodzinach']}</td>
        </tr>";
    }
}

$body .= '</table><br><p><strong>Lista starszych zleceń, które nadal pozostają w Dziale Handlowym:</strong></p>
    <table>
        <tr>
            <th>Nr Zlecenia</th>
            <th>Data i czas stworzenia</th>
            <th>Firma</th>
            <th>Godziny od stworzenia zlecenia</th>
        </tr>';

// Starsze zlecenia
foreach ($zlecenia_w_dziale_handlowym[1] as $zlecenie) {
    if ($zlecenie['WiekZamowieniawGodzinach'] > 24) {
        $body .= "<tr>
            <td>{$zlecenie['Serial']}</td>
            <td>{$zlecenie['OrderCreated']}</td>
            <td>{$zlecenie['Company']}</td>
            <td>{$zlecenie['WiekZamowieniawGodzinach']}</td>
        </tr>";
    }
}

$body .= '</table></body></html>';

// Wyświetlenie treści dla testów
echo $body;


// Sprawdź, czy istnieje co najmniej jedno zlecenie młodsze lub równe 72 godziny
$jest_zlecenie_ponizej_72h = false;

foreach ($zlecenia_w_dziale_handlowym[1] as $zlecenie) {
    if ($zlecenie['WiekZamowieniawGodzinach'] <= 72) {
        $jest_zlecenie_ponizej_72h = true;
        break;
    }
}

// Tylko jeśli są takie zlecenia – wyślij maila
if ($jest_zlecenie_ponizej_72h) {
    // Wyświetlenie treści dla testów
    echo $body;

    // Wysłanie e-maila
    sendEmail($to, $subject, $body, null, true, $cc);
}

?>
