<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


//echo '<div class="content">teststtslajljfgdjldfgljsdlfgk sdlf;gks;dfglj</div>';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["file_url"])) {
    $url = filter_var($_POST["file_url"], FILTER_SANITIZE_URL);
    header("Location: $url");
    exit;
}

?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Pobieranie pliku</title>
</head>
<body>
    <br><br><br><br>
    <form method="post">
        <label for="file_url">Wklej adres URL pliku:</label>
        <input type="text" name="file_url" id="file_url" required>
        <button type="submit">Pobierz plik</button>
    </form>
</body>
</html>