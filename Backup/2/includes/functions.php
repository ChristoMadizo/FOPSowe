

<?php
#region // łączenie do VM
require 'vendor/autoload.php'; // Jeśli używasz Composer
function ssh_connect_vm($host, $user, $password) {
    // Połącz się z VM
    $connection = ssh2_connect($host, 22);

    // Sprawdź, czy połączenie zostało nawiązane
    if ($connection === false) {
        echo "Błąd połączenia SSH!";
        exit; // Zatrzymaj skrypt w przypadku błędu
    } else {
        echo "Połączono z serwerem SSH!";
       
    }

    echo($connection);
    // Autoryzacja za pomocą hasła
    if (ssh2_auth_password($connection, $user, $password)) {
        return $connection; // Zwróć połączenie
    } else {
        echo "Błąd autoryzacji SSH!";
        return false;
    }
}
#endregion
?>


<?php
#region close_ssh_connection()
function close_ssh_connection() {
    // Sprawdź, czy połączenie istnieje i zamknij je
    if (isset($_SESSION['ssh_connection']) && $_SESSION['ssh_connection'] !== false) {
        ssh2_disconnect($_SESSION['ssh_connection']);
        unset($_SESSION['ssh_connection']); // Wyczyść połączenie z sesji
    }
}
#endregion
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
function display_table_from_array($result_all_data) {
    // Sprawdzamy, czy tablica nie jest pusta
    if (!empty($result_all_data)) {
        echo "<table border='1'>";
        
        // Pobieramy pierwszy wiersz, aby wyświetlić nagłówki tabeli
        $firstRow = $result_all_data[0]; // Pierwszy element tablicy
        echo "<tr>";
        
        // Wyświetlamy nagłówki tabeli na podstawie nazw kolumn w pierwszym wierszu
        foreach ($firstRow as $column => $value) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Wyświetlamy wszystkie wiersze
        foreach ($result_all_data as $row) {
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
?>



<?php   //funkcja do WYSYŁANIA EMAILI
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Załaduj PHPMailer (z autoloaderem Composer lub ręcznie)
require 'vendor/autoload.php'; // Jeśli używasz Composer
// lub
// require 'includes/PHPMailer/src/PHPMailer.php';
// require 'includes/PHPMailer/src/SMTP.php';
// require 'includes/PHPMailer/src/Exception.php';

function sendEmail($to, $subject, $body) {
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

