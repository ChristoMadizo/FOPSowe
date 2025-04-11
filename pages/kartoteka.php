<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';
//include_once '/home/kmadzia/www/includes/functions.php';

$sql = 'select
    distinct wares.code as code,
    wares.name as name
from
    `prosto`.wares wares
where wares.fsc_certificate=1';

echo $sql;

$connection=db_connect_mysqli();
$result = mysqli_query($connection, $sql); //pobranie danych z bazy danych 


if ($result) {  //zapełnia tablicę $result_all_data
    while ($row = mysqli_fetch_assoc($result)) {
        $result_all_data[] = $row;  // Każdy wiersz dodajemy jako osobną tablicę 
    }
} else {
    echo "Błąd zapytania: " . mysqli_error($connection);
};

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartoteka</title>
</head>
<body>
    <p style="color: darkgreen; font-weight: bold;">Lista kodów i nazw towarów z certyfikatem FSC:</p>
    <div class="content">
        <?php display_table_from_array($result_all_data); ?>
    </div>
</body>
</html>
