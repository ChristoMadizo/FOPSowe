<?php
$output = shell_exec("sudo bash /home/kmadzia/www/pages/Należności_limity/kopiowanieBazyAktualizacjaZZtymczasNaleznosci.sh 2>&1");
echo nl2br(htmlspecialchars($output));
?>
