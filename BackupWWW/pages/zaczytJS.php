<?php
$command = 'node /home/kmadzia/www/pages/scripts/scrape.js';  // Ścieżka do skryptu Node.js
$output = shell_exec($command);
echo "Wynik z Puppeteer: " . $output;
?>
