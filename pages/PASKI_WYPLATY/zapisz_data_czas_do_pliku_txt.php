<?php

date_default_timezone_set('Europe/Warsaw'); // Ustawienie strefy czasowej na Warszawę
// Ścieżka do pliku, w którym zapisywana będzie obecność użytkownika
$file = '/home/kmadzia/www/pages/PASKI_WYPLATY/sprawdzacz_obecnosci_na_stronie.txt';

// Pobranie aktualnej daty i godziny
$date = date('Y-m-d H:i:s');

// Zapisanie daty i godziny do pliku, zastępując wcześniejszą zawartość
file_put_contents($file, $date);

// Wyświetlenie komunikatu (opcjonalne, można go wykorzystać w JS)
echo 'Zapisano: ' . $date;
?>