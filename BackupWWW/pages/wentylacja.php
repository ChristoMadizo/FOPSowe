
<?php
    require('includes/functions.php');

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["uruchom"])) {
        // Komenda do wykonania w terminalu
      //  $command = "nokia send 503100955 pechad";
      //exec('ssh Krzysztof@192.168.101.9', $output, $status);
      //exec('scp /home/kmadzia/www/scripts/login.js Krzysztof@192.168.101.9:"C:\\Users\\Krzysztof\\Desktop\\SKRYPTY\\MonitorowanieWENTYLACJI_PHP\\SkryptyJS" 2>&1', $output, $status);
      exec('ssh Krzysztof@192.168.101.9', $output, $status);

        echo "<p>Wynik komendy: " . htmlspecialchars(implode("\n", $output)) . "</p>";

        // Wykonanie komendy w terminalu
      //  $output = [];
      //  $returnVar = 0;
      //  exec($command, $output, $returnVar);

        // Wyświetlenie wyniku komendy
     //   echo "<p>Wynik komendy: " . htmlspecialchars(implode("\n", $output)) . "</p>";
      //  echo "<p>Kod powrotu: " . htmlspecialchars($returnVar) . "</p>";
    }
    ?>






<div class="content">
        <form method="post">
            <button type="submit" name="uruchom">Uruchom</button>
            To jest ten test
            <?php
            if (!empty($_POST)) {
                foreach ($_POST as $key => $value) {
                    echo "<p>" . htmlspecialchars($key) . ": " . htmlspecialchars($value) . "</p>";
                }
            }
            ?>
        </form>
    </div>

    <?php
        $_POST = null;
    ?>  