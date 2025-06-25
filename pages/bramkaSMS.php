<?php

require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);




//SendSMSNokia($PhoneNr,$SMScontent)

$SMS_odbiorcza = ReadSMSNokia(100);




?>


<<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skrzynka odbiorcza</title>
    <style>
        table {
            width: 60%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

    <h2>Skrzynka odbiorcza</h2>

    <table>
        <tr>
            <th>Numer telefonu</th>
            <th>Data i czas</th>
            <th>Treść wiadomości</th>
        </tr>

        <?php
        // Iteracja przez tablicę $SMS_odbiorcza i wyświetlanie w tabeli
        foreach ($SMS_odbiorcza as $wiadomosc) {
            echo "<tr>";
            echo "<td>{$wiadomosc['phone_number']}</td>";
            echo "<td>{$wiadomosc['date_time']}</td>";
            echo "<td>{$wiadomosc['message_content']}</td>";
            echo "</tr>";
        }
        ?>

    </table>

</body>
</html>


