<!DOCTYPE html>
<html>
<head>
    <title>Wybór pliku</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <label for="file">Wybierz plik:</label>
        <input type="file" id="file" name="file" required>
        <button type="submit">Wyślij</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $file = $_FILES['file'];

        // Wyświetlanie dostępnych informacji o pliku
        echo "Nazwa pliku: " . htmlspecialchars($file['name']) . "<br>";
        echo "Tymczasowa lokalizacja na serwerze: " . htmlspecialchars($file['tmp_name']) . "<br>";
        
        // Pełna ścieżka docelowa na serwerze
        $uploadDir = __DIR__ . '/uploads/';
        $uploadFile = $uploadDir . basename($file['name']);
        echo "Docelowa ścieżka na serwerze: " . htmlspecialchars($uploadFile) . "<br>";

        // Sprawdzenie katalogu i zapis pliku na serwerze
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            echo "Plik został przesłany i zapisany w: " . htmlspecialchars($uploadFile) . "<br>";
        } else {
            echo "Wystąpił problem podczas przesyłania pliku.<br>";
        }
    }
    ?>
</body>
</html>
