<?php
$connection = ssh2_connect('192.168.101.203', 22);
if (ssh2_auth_pubkey_file($connection, 'kmadzia', 'C:\Users\Krzysztof\.ssh\id_ed25519.pub', 'C:\Users\Krzysztof\.ssh\id_ed25519')) {
    echo "Zalogowano pomyślnie!";
} else {
    echo "Błąd logowania!";
}
