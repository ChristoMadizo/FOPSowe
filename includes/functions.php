<?php 
require '/home/kmadzia/www/vendor/autoload.php'; // Jeśli używasz Composer
echo "Plik functions.php został załadowany przez autoloader.<br>"; ?>


<?php
function executeOnVM($command) {
    // Parametry połączenia z VM
    $vm_ip = '192.168.101.203'; // IP maszyny wirtualnej
    $vm_user = 'kmadzia'; // Nazwa użytkownika
    $vm_password = 'Zima2024'; // Hasło użytkownika

    // Ścieżka do pliku plink.exe
    //$plink_path = 'C:\Apps\plink.exe'; // Zmień na rzeczywistą lokalizację pliku plink.exe

    // Budowanie polecenia
    //$cmd = "\"$plink_path\" -pw $vm_password $vm_user@$vm_ip \"$command\"";

    $cmd=$command ;//nie trzeba już podawać plink_path ani się logować, bo php działa na VM

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


<?php
function ReadSMSNokia($messages_count) {
    // Wykonanie polecenia na VM i uzyskanie wyniku
    $result = executeOnVM('nokia read ' . $messages_count);

    // Sprawdzanie, czy wynik nie jest pusty
    if (!empty($result)) {
        // Rozdzielamy wynik na linie
        $lines = explode("\n", $result);
        
        // Inicjalizacja tablicy na wiadomości
        $messages = [];
        
        // Przetwarzanie wyników, zakładając, że każda wiadomość to 3 linie
        for ($i = 0; $i < count($lines); $i += 3) {
            // Sprawdzanie, czy mamy co najmniej 3 linie na jedną wiadomość
            if (isset($lines[$i], $lines[$i+1], $lines[$i+2])) {
                // Rozdzielanie na części
                $SMS_phone_number = trim($lines[$i]);
                $SMS_date_time = trim($lines[$i+1]);
                $SMS_message_content = trim($lines[$i+2]);

                // Konwersja daty
                $date = DateTime::createFromFormat('Y.m.d H:i:s', $SMS_date_time);
                if ($date) {
                    $SMS_date_time = $date->format('Y-m-d H:i:s'); // Standardowy format
                } else {
                    $SMS_date_time = null; // W przypadku błędu, ustawiamy na null
                }

                // Dodawanie wiadomości do tablicy
                $messages[] = [
                    'phone_number' => $SMS_phone_number,
                    'date_time' => $SMS_date_time,
                    'message_content' => $SMS_message_content
                ];
            }
        }

        // Zwracamy tablicę z wiadomościami
        return $messages;
    } else {
        return "Brak danych z komendy.";
    }
}
?>



<?php
function findSMSByPhoneNumber($worker_id,$nr_telefonu, $trescSMS) {
    // Flaga, która wskazuje, czy znaleziono SMS z zawartością numeru telefonu
    $found = false;

    // Iterowanie po tablicy $trescSMS
    foreach ($trescSMS as $sms) {
        // Sprawdzamy, czy w treści wiadomości (message_content) znajduje się numer telefonu
        if (strpos($sms['message_content'], $nr_telefonu) !== false) {
            // Jeśli numer telefonu znajduje się w treści wiadomości
            echo "Znaleziono SMS z numerem telefonu $nr_telefonu:\n";
            echo "Numer telefonu: " . $sms['phone_number'] . "\n";
            echo "Data: " . $sms['date_time'] . "\n";
            echo "Treść: " . $sms['message_content'] . "\n";
            
            // Ustawiamy flagę na true i przerywamy pętlę
            $SMS_confirmation_data = [  //tworzy dane do dodania do bazy danych - event potwierdzenia smsa
                'worker_id' => $worker_id,
                'date' => $sms['date_time'], // Użycie obiektu DateTime
                'event_type' => 'SMS_delivery_confirmation',
                'password' => '',  //nie potrzebujemy tu hasła
                'remarks1' => '',
                'remarks2' => ''
            ];
            return $SMS_confirmation_data;

            break;
        }
    }

    // Jeśli nie znaleziono wiadomości, wyświetlamy komunikat
    if (!$found) {
        echo "Nie znaleziono SMS zawierającego numer telefonu $nr_telefonu.\n";
    }
}


?>








<?php
function db_connect_mysqli() {
    $servername = "192.168.101.240";
    $username = "kmadzia";
    $password = "PLdpzwZ]gvj_W5SZ";
    $database = "prosto";

    // Nawiązanie połączenia z użyciem mysqli
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Sprawdzenie, czy połączenie się powiodło
    if (!$conn) {
        die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
    }

    return $conn; // Zwrócenie obiektu połączenia
}

?>  

<?php
function db_connect_mysqli_KM_VM() {
    $servername = "192.168.101.203";
    $username = "root";
    $password = "MojeHaslo33";
    $database = "km_base";

    // Nawiązanie połączenia z użyciem mysqli
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Sprawdzenie, czy połączenie się powiodło
    if (!$conn) {
        die("Błąd połączenia z bazą danych: " . mysqli_connect_error());
    }

    return $conn; // Zwrócenie obiektu połączenia
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


<?php  //zwraca zarówno obiekt jak i gotowe dane
function fetch_data($conn, $sql) {
    $result = $conn->query($sql);
    if ($result) {
        // Zwraca tablicę z dwoma elementami
        return [
            $result,                           // Pierwszy element - obiekt wynikowy
            $data_table=$result->fetch_all(MYSQLI_ASSOC)   // Drugi element - tablica danych
        ];
    }
    return null;
}
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

<?php    //wersja dla FAKTUR! - jest specjalna wersja, bo musi zmieniać typy kolumn - np. żeby  była input, albo lista wyboru
function display_table_from_arrayFAKTURY($result_all_data, $columns_to_display = [], $lista_towarow = []) {
    if (!empty($result_all_data)) {
        $table = "<table border='1'>"; // Tabela bez formularza
        $firstRow = $result_all_data[0]; // Pierwszy element tablicy

        // Nagłówki tabeli
        $table .= "<tr>";
        $columns = empty($columns_to_display) ? array_keys($firstRow) : $columns_to_display;
        foreach ($columns as $column) {
            $table .= "<th>" . htmlspecialchars($column) . "</th>";
        }
        $table .= "</tr>";

        // Wiersze tabeli
        foreach ($result_all_data as $row) {
            $prefix = substr($row['Nazwa_produktu'], 0, 5);

            // Filtrowanie pozycji w liście `$lista_towarow`
            $filteredOptions = array_filter($lista_towarow, function ($item) use ($prefix) {
                return stripos($item, $prefix) !== false;
            });
            

            // Kolorowanie wierszy na podstawie wartości `name_fakt`
            $isOnList = in_array(strtolower($row['name_fakt']), array_map('strtolower', $lista_towarow));
            $table .= '<tr style="background-color: ' . ($isOnList ? 'white' : 'red') . ';">';

            foreach ($columns as $column) {
                if (strtolower($column) === 'name_fakt') {
                    // Generowanie pola input z wartością pobraną z bazy danych
                    $table .= '<td>';
                    $table .= '<input type="text" name="name_fakt[]" value="' . htmlspecialchars($row['name_fakt']) . '" list="options_' . htmlspecialchars($row['Zamowienie']) . '">'; // Pole tekstowe z podpowiedziami
                    $table .= '<datalist id="options_' . htmlspecialchars($row['Zamowienie']) . '">';
                    foreach ($filteredOptions as $option) {
                        $table .= '<option value="' . htmlspecialchars($option) . '">';
                    }
                    $table .= '</datalist>';
                    $table .= '</td>';
                } else {
                    // Wyświetlenie wartości dla innych kolumn
                    $table .= '<td>' . htmlspecialchars($row[$column] ?? '') . '</td>';
                }
            }
            
            
            $table .= "</tr>";
        }

        $table .= "</table>";

        return $table; // Zwracamy tylko tabelę
    } else {
        return "Brak wyników.<br>";
    }
}

?>








<?php
// funkcja do WYSYŁANIA EMAILI z opcjonalnym załącznikiem
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $attachment_path = null) {
    echo "wchodze do funkcji";
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

        if ($attachment_path && file_exists($attachment_path)) {
            $mail->addAttachment($attachment_path);
        }

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

        //var_dump($sql); // Debugging - wyświetlenie treści zapytania SQL

        // Przygotowanie zapytania
        $stmt = $connection->prepare($sql);

        //var_dump($stmt); // Debugging - wyświetlenie obiektu zapytania  

        // Sprawdzenie, czy zapytanie zostało poprawnie przygotowane
        if (!$stmt) {
            throw new Exception("Błąd przygotowania zapytania SQL: " . $connection->error);
        }

        // Jeśli parametr został podany, przypisujemy wartość do zapytania
        if ($param !== null) {
            $stmt->bind_param('s', $param); // 's' oznacza typ parametru (string)
        }

        $finalQuery = str_replace("?", "'" . $param . "'", $sql);  //tylko do podglądu treści zapytania

        //var_dump($finalQuery); // Debugging - wyświetlenie treści zapytania SQL

        // Wykonanie zapytania
        $stmt->execute();

        //var_dump($stmt); // Debugging - wyświetlenie obiektu zapytania

        // Pobranie wyników
        $result = $stmt->get_result();
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }

       // var_dump($results); // Debugging - wyświetlenie wyników

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



function ClearSessionAndReload_KM() {
    // Sprawdzenie, czy sesja jest już uruchomiona
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Usunięcie wszystkich zmiennych sesyjnych
    $_SESSION = [];
    // Zniszczenie sesji
    session_destroy();
    // Przekierowanie na tę samą stronę w celu odświeżenia
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}




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

<?php
function save_data_to_csv($data, $file_path) {
    // Otwieranie pliku w trybie zapisu (wstawienie danych na początku, jeżeli plik istnieje)
    $file = fopen($file_path, 'w');

    if ($file === false) {
        echo "Błąd: Nie udało się otworzyć pliku!";
        return false;
    }

    // Zapisywanie nagłówków, jeśli są dostępne
    if (!empty($data)) {
        // Jeśli dane są tablicą asocjacyjną, zapisujemy nagłówki
        fputcsv($file, array_keys($data[0]));  // Pierwszy wiersz jako nagłówki
    }

    // Zapis danych do pliku CSV
    foreach ($data as $row) {
        fputcsv($file, $row);
    }

    // Zamknięcie pliku
    fclose($file);

    return true;
}

?>

<?php

use setasign\Fpdi\Fpdi;

function splitPdf($path_origin, $path_destination) {
    if (!file_exists($path_origin)) {
        echo "Plik PDF nie istnieje: $path_origin\n";
        return;
    }

    // Główny obiekt do odczytu źródła
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($path_origin);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $newPdf = new Fpdi();
        $newPdf->setSourceFile($path_origin);

        // Importowanie strony
        $templateId = $newPdf->importPage($pageNo);

        // Pobieranie rozmiaru strony
        $size = $newPdf->getTemplateSize($templateId);

        // Sprawdzenie orientacji na podstawie rozmiarów
        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P'; // 'L' = Landscape, 'P' = Portrait

        // Dodanie strony z odpowiednią orientacją
        $newPdf->addPage($orientation, [$size['width'], $size['height']]);
        $newPdf->useTemplate($templateId);

        // Zapis nowego pliku PDF
        $outputFile = $path_destination . '/strona_' . $pageNo . '.pdf';
        $newPdf->Output('F', $outputFile);

        echo "Strona $pageNo została zapisana jako: $outputFile\n";
    }

    echo "Proces podziału PDF zakończony!\n";
}
?>

<?php
use Smalot\PdfParser\Parser;
use setasign\FpdiProtection\FpdiProtection;

function getAllNamesFromPdfDir($dir_path, $szyfrowac = false) {
    $result = [];

    if (!is_dir($dir_path)) {
        return ["error" => "Podany katalog nie istnieje: $dir_path"];
    }

    $pdf_files = glob($dir_path . '/*.pdf');

    if (empty($pdf_files)) {
        return ["error" => "Brak plików PDF w katalogu: $dir_path"];
    }

    $parser = new Parser();

    foreach ($pdf_files as $pdf_path) {
        try {
            // Parsowanie PDF w celu uzyskania nazwy pracownika
            $pdf = $parser->parseFile($pdf_path);
            $pages = $pdf->getPages();

            if (!isset($pages[0])) {
                continue;
            }

            $text = $pages[0]->getText();

            if (preg_match('/\|\s*1\|(.*?)\|/', $text, $matches)) {
                $nazwisko_imie = trim($matches[1]);

                // Generowanie hasła: na przykład stałe hasło 'mojehaslo'
                $password = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', $nazwisko_imie), 0, 4)) . rand(1000, 9999);
                //$password = 'mojehaslo';

                if ($szyfrowac) {
                    // Szyfrowanie istniejącego pliku PDF za pomocą FPDI Protection
                    $fpdi = new FpdiProtection();
                    $pageCount = $fpdi->setSourceFile($pdf_path);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $fpdi->importPage($pageNo);
                        $size = $fpdi->getTemplateSize($templateId);
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                        // Dodanie strony z oryginalnym rozmiarem i orientacją
                        $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                    }

                    // Ustawienie hasła i zapisanie pliku
                    $fpdi->SetProtection([], $password);
                    $fpdi->Output('F', $pdf_path); // Nadpisuje oryginalny plik
                }

                $result[] = [
                    'nazwisko_imie' => $nazwisko_imie,
                    'sciezka_pdf' => $pdf_path,
                    'haslo_pdf' => $szyfrowac ? $password : null // Hasło tylko, gdy szyfrowanie jest aktywne
                ];
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $result;
}


function insertIntoTable($conn, $table, $data) {
    if (!$conn) {
        die("Brak połączenia z bazą danych.");
    }

    if (empty($table) || empty($data)) {
        throw new Exception("Nie podano tabeli ani danych.");
    }

    // Budowanie zapytania
    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));
    
    // Dynamiczne ustalanie typów dla bind_param
    $types = "";
    foreach ($data as $value) {
        if (is_int($value)) {
            $types .= "i"; // integer
        } elseif (is_float($value)) {
            $types .= "d"; // double
        } elseif (is_string($value)) {
            $types .= "s"; // string
        } elseif (is_null($value)) {
            $types .= "s"; // null traktujemy jako string
        } elseif ($value instanceof DateTime) {
            $types .= "s"; // dla obiektów DateTime traktujemy jako string
        } else {
            $types .= "s"; // domyślnie traktujemy wszystko jako string
        }
    }

    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania: " . $conn->error);
    }

    // Przygotowanie wartości
    $values = array_map(function ($value) {
        return $value instanceof DateTime ? $value->format('Y-m-d H:i:s') : $value; // formatujemy DateTime na string
    }, array_values($data));

    // Bindowanie parametrów
    $stmt->bind_param($types, ...$values);

    // Wykonanie zapytania
    if ($stmt->execute()) {
        return true;
    } else {
        throw new Exception("Błąd wykonania zapytania: " . $stmt->error);
    }

    $stmt->close();
}
?>





