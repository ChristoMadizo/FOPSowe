

<?php
function executeOnVM($command) {
    // Parametry połączenia z VM
    $vm_ip = '192.168.101.203'; // IP maszyny wirtualnej
    $vm_user = 'kmadzia'; // Nazwa użytkownika
    $vm_password = 'Zima2024'; // Hasło użytkownika

    // Ścieżka do pliku plink.exe
    $plink_path = 'C:\Apps\plink.exe'; // Zmień na rzeczywistą lokalizację pliku plink.exe

    // Budowanie polecenia
    $cmd = "\"$plink_path\" -pw $vm_password $vm_user@$vm_ip \"$command\"";

    // Wykonanie polecenia
    $output = [];
    $return_var = 0;
    exec($cmd, $output, $return_var);

    // Zwracanie wyniku
    if ($return_var === 0) {
        return implode("\n", $output); // Wyjście polecenia
    } else {
        return "Wystąpił błąd podczas wykonania polecenia. Kod błędu: $return_var";
    }
}
?>

<?php
function SendSMSNokia($PhoneNr,$SMScontent) {
    $result = executeOnVM('nokia send ' .$PhoneNr . ' ' .  $SMScontent);
    // Zwracanie wyniku
    $output = [];
    $return_var = 0;
    if ($return_var === 0) {
        return implode("\n", $output); // Wyjście polecenia
    } else {
        return "Wystąpił błąd podczas wykonania polecenia. Kod błędu: $return_var";
    }
}
?>







<?php  //łączenie do bazy PROSTO
#region db_connect($servername, $username, $password, $database)
function db_connect() {
    $servername = "192.168.101.240";
    $username = "kmadzia";
    $password = "PLdpzwZ]gvj_W5SZ";
    $database = "prosto";
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$database;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Ustawienie trybu wyjątków
        return $conn;
    } catch (PDOException $e) {
        // Zwróć błąd, jeśli połączenie się nie uda
        echo "Błąd połączenia: " . $e->getMessage(); // Wyświetlenie szczegółowego komunikatu o błędzie
        return null;
    }
}
#endregion
?>

<?php //łączenie do bazy PROSTO  mysqli
function db_connect_mysqli() {
    $servername = "192.168.101.240";
    $username = "kmadzia";
    $password = "PLdpzwZ]gvj_W5SZ";
    $database = "prosto";
    // Tworzymy połączenie
    $connection = mysqli_connect($servername, $username, $password, $database);
    // Sprawdzamy, czy połączenie się powiodło
    if (mysqli_connect_errno()) {
        // Jeśli wystąpił błąd połączenia, zwracamy false i wyświetlamy komunikat
        echo "Błąd połączenia z bazą danych: " . mysqli_connect_error();
        return false;  // Zwracamy false, jeśli połączenie nie udało się nawiązać
    }
    // Jeśli połączenie powiodło się, zwracamy obiekt połączenia
    return $connection;
}
?>


<?php //pobieranie danych z bazy PROSTO
#region fetch_data($conn, $sql)
function fetch_data($conn, $sql) {
    return $conn->query($sql);
}
#endregion
?>


<?php //wyświetlanie danych z bazy PROSTO
#region display_table($stmt)
function display_table($stmt) {
    if ($stmt->rowCount() > 0) {
        echo "<table border='1'><tr>";
        $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach (array_keys($firstRow) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";

        echo "<tr>";
        foreach ($firstRow as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Brak wyników.<br>";
    }
}
#endregion
?>

<?php
function display_table_from_arrayFAKTURY($result_all_data, $columns_to_display = [], $lista_towarow = []) {
    // Sprawdzamy, czy tablica nie jest pusta
    if (!empty($result_all_data)) {
        $table = '<form method="POST">'; // Formularz dla edycji danych
        $table .= "<table border='1'>";
        $firstRow = $result_all_data[0]; // Pierwszy element tablicy
        $table .= "<tr>";

        // Wyświetlamy tylko wybrane kolumny lub wszystkie, jeśli lista jest pusta
        $columns = empty($columns_to_display) ? array_keys($firstRow) : $columns_to_display;
        foreach ($columns as $column) {
            $table .= "<th>" . htmlspecialchars($column) . "</th>";
        }
        $table .= "</tr>";

        // Wyświetlamy wszystkie wiersze
        foreach ($result_all_data as $row) {
            $prefix = substr($row['Nazwa_produktu'], 0, 5);

            // Filtrowanie pozycji w liście `$lista_towarow` na podstawie pierwszych 5 znaków
            $filteredOptions = array_filter($lista_towarow, function($item) use ($prefix) {
                return strpos($item, $prefix) === 0;
            });

            // Sprawdzamy, czy wartość `Name_fakt` znajduje się na liście `$lista_towarow`
            $isOnList = in_array(strtolower($row['Name_fakt']), array_map('strtolower', $lista_towarow));
            $table .= '<tr style="background-color: ' . ($isOnList ? 'white' : 'red') . ';">';  // jeśli jest na liście towarów, to kolor biały, w przeciwnym razie czerwony

            foreach ($columns as $column) {
                if ($column === 'name_fakt') {
                    // Tworzenie listy rozwijanej z filtrowanymi opcjami
                    $table .= '<td><select name="name_fakt[' . htmlspecialchars($row['Zamowienie']) . ']">';
                    $table .= '<option value="' . htmlspecialchars($row[$column]) . '">' . htmlspecialchars($row[$column]) . '</option>'; // Obecna wartość jako domyślna
                    foreach ($filteredOptions as $option) {
                        $table .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                    }
                    $table .= '</select></td>';
                } else {
                    $table .= '<td>' . htmlspecialchars($row[$column] ?? '') . '</td>';
                }
            }
            $table .= "</tr>";
        }
        $table .= "</table>";
        $table .= '</form>';

        return $table; // Zwracamy tabelę jako ciąg znaków
    } else {
        return "Brak wyników.<br>";
    }
}

?>


<?php
function display_table_from_array($result_all_data, $columns_to_display = []) {
    // Sprawdzamy, czy tablica nie jest pusta
    if (!empty($result_all_data)) {
        $table = '<form method="POST">'; // Formularz dla edycji danych
        $table .= "<table border='1'>";
        $firstRow = $result_all_data[0]; // Pierwszy element tablicy
        $table .= "<tr>";

       
        // Wyświetlamy tylko wybrane kolumny lub wszystkie, jeśli lista jest pusta
        $columns = empty($columns_to_display) ? array_keys($firstRow) : $columns_to_display;
        foreach ($columns as $column) {
            $table .= "<th>" . htmlspecialchars($column) . "</th>";
        }
        $table .= "</tr>";

        
        // Wyświetlamy wszystkie wiersze
        foreach ($result_all_data as $row) {
            //******************************** Usunięto kolorowanie i sprawdzanie listy towarów ********************************
            $table .= '<tr>';

            foreach ($columns as $column) {
                if ($column === 'name_fakt') {
                    // Tworzenie listy rozwijanej bez filtrowania opcji
                    $table .= '<td><select name="name_fakt[' . htmlspecialchars($row['Zamowienie']) . ']">';
                    $table .= '<option value="' . htmlspecialchars($row[$column]) . '">' . htmlspecialchars($row[$column]) . '</option>'; // Obecna wartość jako domyślna
                    //******************************** Usunięto filtrowanie opcji ********************************
                    $table .= '</select></td>';
                } else {
                    $table .= '<td>' . htmlspecialchars($row[$column] ?? '') . '</td>';
                }
            }
            $table .= "</tr>";
        }
        $table .= "</table>";
        $table .= '</form>';


        return $table; // Zwracamy tabelę jako ciąg znaków
    } else {
        return "Brak wyników.<br>";
    }
}
?>








<?php   //funkcja do WYSYŁANIA EMAILI
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/home/kmadzia/www/vendor/autoload.php'; // Jeśli używasz Composer

function sendEmail($to, $subject, $body) {
    echo "wchodze do funkcji"   ;
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'serwer1400163.home.pl';
        $mail->SMTPAuth = true;
        $mail->Username = 'k.madzia@fops.pl';
        $mail->Password = 'MojeHaslo33';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('k.madzia@fops.pl', 'FOPS');
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        echo "Wiadomość wysłana pomyślnie!";
    } catch (Exception $e) {
        echo "Błąd wysyłania wiadomości: {$mail->ErrorInfo}";
    }
}
?>



<?php

function export_to_excel($data,$columns_to_display) {
    if (ob_get_contents()) {
        ob_end_clean();
    }
    ob_start();

    $file_name = "zamowienia_data.csv";
    header("Content-Disposition: attachment; filename=\"$file_name\"");
    header("Content-Type: text/csv");

    $column_names = false;
    foreach ($data as $row) {
        if (!$column_names) {
                    $filtered_columns = array_intersect_key($row, array_flip($columns_to_display));
                    echo implode(",", array_keys($filtered_columns)) . "\n"; // Separator: przecinek
                    $column_names = true;
                }

        array_walk($row, function (&$str) {
            $str = preg_replace("/,/", "\\,", $str); // Escapowanie przecinków
            $str = preg_replace("/\r?\n/", "\\n", $str);
            if (strstr($str, '"')) {
                $str = '"' . str_replace('"', '""', $str) . '"';
            }
        });


        $filtered_row = array_intersect_key($row, array_flip($columns_to_display));
        echo implode(",", array_values($filtered_row)) . "\n"; // Separator: przecinek
    }

    exit();
}


function executeSQL($connection, $filePath, $param = null) {
    try {
        // Sprawdzenie, czy plik SQL istnieje
        if (!file_exists($filePath)) {
            throw new Exception("Plik $filePath nie istnieje.");
        }

        // Wczytanie treści zapytania SQL z pliku
        $sql = file_get_contents($filePath);

        // Przygotowanie zapytania
        $stmt = $connection->prepare($sql);

        // Sprawdzenie, czy zapytanie zostało poprawnie przygotowane
        if (!$stmt) {
            throw new Exception("Błąd przygotowania zapytania SQL: " . $connection->error);
        }

        // Jeśli parametr został podany, przypisujemy wartość do zapytania
        if ($param !== null) {
            $stmt->bind_param('s', $param); // 's' oznacza typ parametru (string)
        }

        // Wykonanie zapytania
        $stmt->execute();

        // Pobranie wyników
        $result = $stmt->get_result();
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }

        // Zwracanie wyników
        return $results;
    } catch (Exception $e) {
        // Obsługa błędów
        echo 'Wystąpił błąd: ' . $e->getMessage();
        return false;
    }
}


function db_connect_firebird() {
    // Parametry połączenia
    $host = '192.168.101.79'; // Adres IP serwera
    $port = '3050'; // Port Firebird
    $database_path = 'c:\\fakt95\\0002\\0002BAZA.FDB'; // Ścieżka do pliku bazy danych
    $username = 'KRZYSIEK'; // Nazwa użytkownika
    $password = 'Bielawa5'; // Hasło

    // Tworzenie stringa połączenia
    $connection_string = "{$host}/{$port}:{$database_path}";

    // Próba połączenia
    $connection = ibase_connect($connection_string, $username, $password);

    if (!$connection) {
        // Obsługa błędu połączenia
        die("Nie udało się połączyć z bazą danych Firebird: " . ibase_errmsg());
    }

    // Wypisanie komunikatu o sukcesie
    echo "Połączono z bazą danych Firebird!";
    return $connection;
}


function db_connect_firebirdPDO() {
    // Parametry połączenia
    $host = '192.168.101.79'; // Adres IP serwera
    $port = '3050'; // Port Firebird
    $database_path = 'c:\\fakt95\\0002\\0002BAZA.FDB'; // Ścieżka do pliku bazy danych
    $username = 'KRZYSIEK'; // Nazwa użytkownika
    $password = 'Bielawa5'; // Hasło
    $dialect = '1'; // Dialekt bazy danych

    // Tworzenie stringa połączenia PDO
    $dsn = "firebird:dbname={$host}/{$port}:{$database_path};dialect={$dialect}";

    try {
        // Próba połączenia za pomocą PDO
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Wypisanie komunikatu o sukcesie
        echo "Połączono z bazą danych Firebird!";
        return $pdo;
    } catch (PDOException $e) {
        // Obsługa błędów PDO
        die("Błąd PDO: " . $e->getMessage());
    }
}

?>


<?php
function executeBatchFileOnKMpc($fileName) {
    // Stała część ścieżki na zdalnym komputerze
    $basePath = "C:\\Users\\Krzysztof\\Desktop\\SKRYPTY\\TEMP_SQL_REZULTS\\";
    $fullPath = $basePath . $fileName . ".bat";
    $outputFileRemote = $basePath . $fileName . ".txt"; // Plik wynikowy na zdalnym komputerze

    // Polecenie SSH do uruchomienia pliku .bat
    $sshCommand = 'ssh Krzysztof@192.168.101.9 "' . $fullPath . '"';

    // Wykonanie polecenia .bat na zdalnym komputerze
    $output = [];
    $returnStatus = 0;
    exec($sshCommand, $output, $returnStatus);

    if ($returnStatus === 0) {
       // echo "Plik {$fullPath} został pomyślnie uruchomiony.\n";
      //  echo implode("\n", $output);

        // Polecenie SSH do odczytu zawartości pliku wynikowego
        $readCommand = 'ssh Krzysztof@192.168.101.9 "type ' . $outputFileRemote . '"';
        $remoteFileContent = [];
        $readStatus = 0;

       // echo $readCommand;

        exec($readCommand, $remoteFileContent, $readStatus);

        if ($readStatus === 0) {
          //  echo "\nZawartość pliku wynikowego:\n";
          //  print_r($remoteFileContent); // Wyświetlenie zawartości
            return $remoteFileContent;  // Zwrócenie danych jako tablica
        } else {
            echo "\nBłąd podczas odczytu pliku wynikowego. Kod błędu: {$readStatus}\n";
            return null;
        }
    } else {
        echo "Wystąpił błąd podczas uruchamiania pliku {$fullPath}. Kod błędu: {$returnStatus}\n";
        return null;
    }
}

