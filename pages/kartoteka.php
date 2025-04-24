<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';
include_once '/home/kmadzia/www/includes/functions.php';

//ten select szuka towarów z certyfikatem FSC, które nie mają w nazwie "FSC" i nie są usunięte
$sql_FSC_items_without_FSC_in_name = 'select     
    distinct wares.code as code,
    wares.name as name
from
    `prosto`.wares wares
where wares.fsc_certificate=1 and wares.name not like \'%FSC%\' and isnull(wares.deleted)';

//ten select szuka towarów bez certyfikatu FSC, które mają w nazwie "FSC" i nie są usunięte
$sql_nonFSC_items_with_FSC_in_name = 'select
    distinct wares.code as code,
    wares.name as name
from
    `prosto`.wares wares
where wares.fsc_certificate=0 and wares.name like \'%FSC%\' and isnull(wares.deleted)';

$connection=db_connect_mysqli();

$FSC_items_without_FSC_in_name = []; //tworzymy tablicę, do której będziemy dodawać dane
$result1 = mysqli_query($connection, $sql_FSC_items_without_FSC_in_name); //pobranie danych z bazy danych 
if ($result1) {  //zapełnia tablicę $result_all_data
    while ($row = mysqli_fetch_assoc($result1)) {
        $FSC_items_without_FSC_in_name[] = $row;  // Każdy wiersz dodajemy jako osobną tablicę 
    }
} else {
    echo "Błąd zapytania: " . mysqli_error($connection);
};

$nonFSC_items_with_FSC_in_name = []; //tworzymy tablicę, do której będziemy dodawać dane
$result2 = mysqli_query($connection, $sql_nonFSC_items_with_FSC_in_name); //pobranie danych z bazy danych 
if ($result2) {  //zapełnia tablicę $result_all_data
    while ($row = mysqli_fetch_assoc($result2)) {
        $nonFSC_items_with_FSC_in_name[] = $row;  // Każdy wiersz dodajemy jako osobną tablicę 
    }
} else {
    echo "Błąd zapytania: " . mysqli_error($connection);
};

//wysyłanie maila, jeśli znaleziono sprzeczności:
if (PHP_SAPI === 'cli' && (!empty($FSC_items_without_FSC_in_name) || !empty($nonFSC_items_with_FSC_in_name))) {  //jeśli skrypt uruchomiony z linii 
// poleceń (np. CRONTAB) i są sprzeczności
    $body='Kody ze znacznikiem FSC bez "FSC" w nazwie:<br>';
    $body.=display_table_from_array($FSC_items_without_FSC_in_name);
    $body.='<br>Kody bez znacznika FSC z "FSC" w nazwie:<br>';
    $body.=display_table_from_array($nonFSC_items_with_FSC_in_name);

    sendEmail('j.boruta@fops.pl;k.madzia@fops.pl', 'Sprzecznosc kodow FSC',$body, $attachment_path = null,$is_html=true);
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartoteka</title>
</head>
<body>
	<p style="color: gray; font-size: 12px; font-style: italic;">
		<?php 
			$current_date_time = date('Y-m-d H:i:s'); // Pobranie aktualnej daty i godziny
			$date = new DateTime($current_date_time); // Utworzenie obiektu DateTime
			$date->modify('+2 hours'); // Dodanie 2 godzin
			echo "Ostatnie odświeżenie strony: " . $date->format('Y-m-d H:i:s'); // Wyświetlenie zmienionej daty 
		?>
    </p>

    <p style="color: darkgreen; font-weight: bold;">Lista kodów ze znacznikiem FSC bez "FSC" w nazwie:</p>

    <div class="content">
        <?php $table1=display_table_from_array($FSC_items_without_FSC_in_name);
        echo $table1;
                ?>
        <p style="color: darkgreen; font-weight: bold;">Lista kodów bez znacznika FSC z "FSC" w nazwie:</p>

        <?php $table2=display_table_from_array($nonFSC_items_with_FSC_in_name);
        echo $table2;
                ?>
    </div>


</body>
</html>
