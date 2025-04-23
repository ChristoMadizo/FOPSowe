<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection=db_connect_mysqli_KM_VM();

$sql="SELECT name,surname,phone_number,e_mail_address FROM `aa01_workers`";

$workers_info = fetch_data($connection, $sql)[1]; // Pobieranie danych o pracownikach

//$table=[];

if (isset($_POST['action']) && $_POST['action'] === 'wyslij_sms') {  //odświeża potwierdzenie SMS
    $nr_telefonu = 503100955;   //'$_POST[phone_number']
    $adres_email = $_POST['e_mail_address'];
   
    $result = SendSMSNokia($nr_telefonu, 'Proszę o podanie adresu e-mail, na który będą wysyłane paski wynagrodzeń.
     Adres należy podać w odpowiedzi na tę wiadomość. Dziękuję, dział Kadr FOPS.');
};



?>

<div class="content">
    <table border="1">
        <tr>
            <!-- Nagłówki tabeli -->
            <?php foreach (array_keys($workers_info[0]) as $column_name): ?>
                <th><?php echo htmlspecialchars($column_name, ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
            <th>Akcja</th>
        </tr>

        <!-- Wiersze danych -->
        <?php foreach ($workers_info as $row): ?>
            <tr>
                <!-- Kolumny danych -->
                <?php foreach ($row as $key => $value): ?>
                    <td><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endforeach; ?>

                <!-- Kolumna z przyciskami akcji -->
                <td>
                    <form method="post">
                        <!-- Przekazanie wszystkich danych wiersza -->

                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="surname" value="<?php echo htmlspecialchars($row['surname'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="e_mail_address" value="<?php echo htmlspecialchars($row['e_mail_address'], ENT_QUOTES, 'UTF-8'); ?>">  <!-- uzupełnienie scieżki do pliku pdf -->
                        
                        <!-- Przyciski akcji -->
                        <button type="submit" name="action" value="wyslij_sms" style="all: unset; color: blue; text-decoration: underline; cursor: pointer; display: block; text-align: center;">Wyślij SMS</button>

                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form method="post">
        <button type="submit" name="action" value="sprawdz_potwierdzenieSMS">Sprawdź potwierdzenie SMS</button>
    </form>               

</div>
