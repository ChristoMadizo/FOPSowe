
<?php
require 'vendor/autoload.php';

$test_data = [
    ['Kolumna1' => 'Wartość1', 'Kolumna2' => 'Wartość2'],
    ['Kolumna1' => 'Wartość3', 'Kolumna2' => 'Wartość4']
];
export_to_excel($test_data);
exit();


?>  