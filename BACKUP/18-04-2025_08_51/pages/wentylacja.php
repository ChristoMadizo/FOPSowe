
<?php
    //require_once '/home/kmadzia/www/includes/functions.php';

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["uruchom"])||true) {      //dodałem TRUE - wykonuje się zawsze!!!

       //echo '<div class="content">gdzie ten napis</div>';

        // Ścieżki do Node.js i skryptu login.js
       // $nodePath = '"C:\\Program Files\\nodejs\\node.exe"';
        //$scriptPath = '"C:\\xampp\\htdocs\\km_www\\scripts\\login.js"';
        $scriptPath = '/home/kmadzia/www/scripts/login.js'; // Ścieżka do skryptu login.js

        // Budowanie polecenia
        $command = $scriptPath;    // $nodePath . ' ' . $scriptPath;

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
                        $value = trim(str_replace($key . ':', '', $line));     //ustawianie wartości zmiennej $czujniki
                    }
                }
                unset($value); // Unset reference to avoid potential issues

               // echo '<div class="content">' . htmlspecialchars($line) . '</div>'; // Wyświetl linię w klasie content (po przetworzeniu)

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
   
       // echo "Skrypt Node.js działa dalej w tle.";
    }
    ?>



  
    <p style="position: absolute; top: 100px; right: 200px; border: 1px solid black; background-color:    
        <?php //banerek ze statusem wentylacji
            echo (strpos($czujniki['id_226_switch'] ?? '', 'WYŁ') !== false) ? 'grey' : 
                 ((strpos($czujniki['id_226_switch'] ?? '', 'WŁ') !== false) ? 'lightgreen' : 'transparent'); 
        ?>;">
        Stan id_226_switch: <?php echo htmlspecialchars($czujniki['id_226_switch'] ?? 'Brak danych'); ?>
    </p>

    
    <!-- Wysyłanie maila-->
    <form method="post">
        <button type="submit" name="send_mail">Wyślij maila</button>
    </form>
        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_mail"])) {
            sendEmail('k.madzia@fops.pl', 'Stan wentylacji', 'Stan id_226_switch: ' . htmlspecialchars($czujniki['id_226_switch'] ?? 'Brak danych'));
        }
        ?>

    <!-- Wysyłanie SMSa-->
    <form method="post">
        <button type="submit" name="send_SMS" style="position: absolute; top: 300px;">Wyślij SMSa</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_SMS"])) {
        SendSMSNokia('503100955', 'Stan wentylacji');
    }
    ?>

