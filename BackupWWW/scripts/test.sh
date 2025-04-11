#!/bin/bash

# Ustaw zmienne
WIN_IP="192.168.101.9"  # IP Twojego Windowsa
WIN_USER="Krzysztof"    # Nazwa użytkownika na Windowsie
WIN_PASSWORD="MojeHaslo33" # Hasło do Windowsa

# Wykonaj połączenie SSH do Windowsa z hasłem
echo "Uruchamianie skryptu na Windowsie..."
sshpass -p $WIN_PASSWORD ssh -o StrictHostKeyChecking=no $WIN_USER@$WIN_IP "C:/path/to/your/script.bat"
