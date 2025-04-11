<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '/home/kmadzia/www/vendor/autoload.php';
include_once '/home/kmadzia/www/includes/functions.php';

$sql = 'with LastModDate as(
SELECT 
	wf.ware_id as ware_id,
	wf.unit_id unit_id,
	wf.type unit_type,
	max(created) as LastModDate
FROM
prosto.wares_formats wf
group by wf.ware_id
)
SELECT
	row_number() over (order by wf.ware_id) as Lp,
    wf.ware_id ware_id,
	wares.code,
	wares.name,
	wf.unit_id unit_id,
	units.code unit_code,
	wf.type unit_type,
	units_type.code unit_type
FROM
	prosto.wares_formats wf inner join LastModDate LastModDate on wf.ware_id =LastModDate.ware_id
		inner join prosto.units units on wf.unit_id= units.id inner join prosto.wares wares on wf.ware_id = wares.id
		inner join prosto.units units_type on wf.type=units_type.id
WHERE wf.created=LastModDate.LastModDate and wf.single_label = 0 and units_type.code != "Rola"
'
;

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
    <title>Wspólne etykiety</title>
</head>
<body>
    <p style="color: darkgreen; font-weight: bold;">Lista kodów z wyłączoną opcją Dozw.wsp.etykiety: (bez kodów z typem "Rola")</p>
    <div class="content">
        <?php display_table_from_array($result_all_data,['Lp','code','name','unit_code','unit_type']); ?>
    </div>
</body>
</html>
