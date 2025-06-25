<?php
if (!isset($_GET['file'])) {
    die("Nie podano pliku do pobrania.");
}

$file = $_GET['file'];  
$filePath = $file; // Ponieważ parametr GET zwraca już poprawną ścieżkę

if (!file_exists($filePath)) {
    die("Plik nie istnieje.");
}

// Ustawienie nagłówków do pobrania pliku
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
