<?php
function display_table($stmt) {
    if ($stmt->rowCount() > 0) {
        echo "<table border='1'><tr>";
        $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach (array_keys($firstRow) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";

        echo "<tr>";
        foreach ($firstRow as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Brak wyników.<br>";
    }
}
?>
