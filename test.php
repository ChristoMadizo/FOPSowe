<?php
$tekst = "|1)|Krzysztof Madzia | | | | |";

$regex = "/\|\s*(?:[1-9]|[1-9]\d|100)\)\|\s*([^|]+)\s*\|/";

preg_match($regex, $tekst, $matches);

if (!empty($matches[1])) {
    echo "Imię i nazwisko: " . $matches[1];
} else {
    echo "Brak dopasowania";
}
?>
