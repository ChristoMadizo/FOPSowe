
<?php
    require('includes/functions.php');

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["uruchom"])) {

       //echo '<div class="content">gdzie ten napis</div>';

        // Ścieżki do Node.js i skryptu login.js
        $nodePath = '"C:\\Program Files\\nodejs\\node.exe"';
        $scriptPath = '"C:\\xampp\\htdocs\\km_www\\scripts\\login.js"';

        // Budowanie polecenia
        $command = $nodePath . ' ' . $scriptPath;

        // Otwieranie procesu
        $process = popen($command, 'r'); // Otwiera strumień do czytania wyjścia z Node.js

        // Tablica asocjacyjna czujników
         $czujniki = 
         [
        'id_20_input_label' => null,
        'id_27_input_label' => null,
        'id_35_input_label' => null,
        'id_41_input_label' => null,
        'id_143_input_label' => null,
        'id_197_input_label' => null,
        'id_255_input_label' => null,
        'id_242_input_label' => null,
        'id_226_switch' => null,
        ];




        if ($process) {
            $startTime = time(); // Zapisz czas rozpoczęcia

            while (!feof($process)) { // Czytaj dane zwracane przez Node.js w czasie rzeczywistym
                $line = fgets($process); // Pobierz jedną linię z wyjścia

                // Uzupełnienie tablicy czujników DANYMI
                foreach ($czujniki as $key => &$value) {
                    if (strpos($line, $key) !== false) {
                        $value = trim(str_replace($key . ':', '', $line));
                    }
                }
                unset($value); // Unset reference to avoid potential issues

                echo '<div class="content">' . htmlspecialchars($line) . '</div>'; // Wyświetl linię w klasie content (po przetworzeniu)

                if (strpos($line, 'FINISH') !== false) { // Jeśli znajdziesz "FINISH", zakończ czytanie
                    break; // Zakończ pętlę
                }

                // Sprawdź, czy przekroczono limit czasu
                if ((time() - $startTime) > 45) { // 20 sekund
                    echo '<div class="content">Przekroczono limit czasu oczekiwania na dane.</div>';
                    break; // Zakończ pętlę
                }
            }
            pclose($process); // Zamknij proces
        } else {
            echo "Nie udało się otworzyć procesu.";
        }

        // Wyświetlenie wartości czujników w klasie content
        echo '<div class="content">';
        foreach ($czujniki as $key => $value) {
            if ($value !== null) {
                echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "<br>";
            } else {
                echo htmlspecialchars($key) . ": Brak danych<br>";
            }
        }
        echo '</div>';
   
        echo "Skrypt Node.js działa dalej w tle.";
    }
    ?>


<div class="content">
        <form method="post">
            <button type="submit" name="uruchom">Uruchom</button>
            <?php
           /* if (!empty($_POST)) {
                foreach ($_POST as $key => $value) {
                    echo "<p>" . htmlspecialchars($key) . ": " . htmlspecialchars($value) . "</p>";
                    echo 'no to nie jest empty';
                }
            }*/
            ?>
        </form>
    </div>


<div class="banner">
    <p style="position: absolute; top: 100px; right: 200px; border: 1px solid black; background-color: 
        <?php 
            echo (strpos($czujniki['id_226_switch'] ?? '', 'WYŁ') !== false) ? 'grey' : 
                 ((strpos($czujniki['id_226_switch'] ?? '', 'WŁ') !== false) ? 'lightgreen' : 'transparent'); 
        ?>;">
        Stan id_226_switch: <?php echo htmlspecialchars($czujniki['id_226_switch'] ?? 'Brak danych'); ?>
    </p>
</div>

<?php
    $_POST = null;
?>  