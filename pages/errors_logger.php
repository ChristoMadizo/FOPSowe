<?php
// Uruchomienie prostego polecenia SSH na lokalnej maszynie VM
$command = 'sudo cat /var/log/apache2/error.log';
$output = shell_exec($command);

if ($output === null) {
    echo "Błąd podczas wykonywania polecenia.";
} 


// Rozdziel wynik na linie
$lines = explode("\n", $output);
// Pobierz ostatnie 5 linii
$last_five_lines = array_slice($lines, -5);
// Połącz ostatnie 5 linii z powrotem w jeden ciąg
$last_five_content = implode("\n", $last_five_lines);
// Wyświetlenie ostatnich 5 linii
echo "<div class='error-log'>$last_five_content</div>";

?>