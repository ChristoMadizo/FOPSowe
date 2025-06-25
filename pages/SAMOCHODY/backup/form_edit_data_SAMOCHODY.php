<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
//require __DIR__ . '/../../includes/header.php'; // Nagłówek

ini_set('display_errors', 1);
error_reporting(E_ALL);

$connection = db_connect_mysqli_KM_VM();

// Pobieramy dane z JSON przekazanego przez GET
//$data_json = $_GET['data'] ?? null;
$data_json = $_GET['data'] ?? null;

if (!$data_json) {
    die("Błąd: Brak wymaganych parametrów!");
}

// Dekodowanie JSON-a
$data = json_decode(urldecode($data_json), true);


if (!is_array($data) || empty($data['table']) || empty($data['query']) || empty($data['columns']) || empty($data['car_id'])) {
    die("Błąd: Niepoprawne dane JSON!");
}

$table = mysqli_real_escape_string($connection, $data['table']);
$car_id = mysqli_real_escape_string($connection, $data['car_id']);
$query_base = urldecode($data['query']);
$columns = $data['columns'];

// Obsługa formularza: Zapisz, Dodaj, Usuń
if (isset($_POST['zapisz'])) {
    $rowId = mysqli_real_escape_string($connection, $_POST['zapisz']);

    if (!empty($_POST['dane'][$rowId])) {
        $row_data = $_POST['dane'][$rowId];

        if ($rowId === 'new') {
            // INSERT NOWEGO REKORDU
            $cols = [];
            $vals = [];

            foreach ($row_data as $col => $val) {
                if ($col === 'id')
                    continue; // pomiń ID
                $cols[] = "`" . mysqli_real_escape_string($connection, $col) . "`";
                $vals[] = "'" . mysqli_real_escape_string($connection, $val) . "'";
            }


            // Dodaj car_id jeśli nie ma w formularzu
            if (!array_key_exists('car_id', $row_data)) {
                $cols[] = "`car_id`";
                $vals[] = "'" . mysqli_real_escape_string($connection, $car_id) . "'";
            }

            $insert_query = "INSERT INTO `$table` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
            if (mysqli_query($connection, $insert_query)) {
                echo "<p style='color: green;'>Nowy rekord został dodany!</p>";
            } else {
                echo "<p style='color: red;'>Błąd dodawania: " . mysqli_error($connection) . "</p>";
            }

        } else {
            // UPDATE ISTNIEJĄCEGO
            $set_values = [];

            foreach ($row_data as $column => $value) {
                $safe_value = mysqli_real_escape_string($connection, $value);
                $set_values[] = "`$column` = '$safe_value'";
            }

            if (!empty($set_values)) {
                $query = "UPDATE `$table` SET " . implode(", ", $set_values) . " WHERE id = $rowId";

                if (mysqli_query($connection, $query)) {
                    echo "<p style='color: green;'>Zmiany zostały zapisane!</p>";
                } else {
                    echo "<p style='color: red;'>Błąd zapisu!</p>";
                }
            }
        }
    }
}

if (isset($_POST['usun'])) {   //obsluga usuwania
    $deleteId = mysqli_real_escape_string($connection, $_POST['usun']);
    $delete_query = "DELETE FROM `$table` WHERE id = $deleteId LIMIT 1";
    if (mysqli_query($connection, $delete_query)) {
        echo "<p style='color: green;'>Rekord został usunięty!</p>";
    } else {
        echo "<p style='color: red;'>Błąd usuwania: " . mysqli_error($connection) . "</p>";
    }
}


if ($table === 'ff_01_cars') {
    $car_id_filter = 'id';
} else {
    $car_id_filter = 'car_id';
}

// Tworzenie dynamicznego `SELECT` dla tych kolumn - POBIERANIE DANYCH PO ZMIANACH
$selected_columns = implode(", ", array_map(fn($col) => "`$col`", $columns));
$query = "SELECT $selected_columns FROM `$table` WHERE $car_id_filter = $car_id";
$result = mysqli_query($connection, $query);
$info_to_display = mysqli_fetch_assoc(mysqli_query($connection, $query));


// Sprawdzanie, czy kolumny z datami są dostępne i sortowanie po nich
$date_columns = ['data_nastepny_daedline', 'zadanie_deadline', 'serwis_date'];
$sort_column = null;
foreach ($date_columns as $col) {
    if (in_array($col, $columns)) {
        $sort_column = $col;
        break;
    }
}

if ($sort_column) {
    $query = "SELECT $selected_columns FROM `$table` WHERE car_id = $car_id ORDER BY `$sort_column` asc";
    $result = mysqli_query($connection, $query);
    $info_to_display = mysqli_fetch_assoc(mysqli_query($connection, $query));
}

$header_text = $data['header'];  //pobiera z przekazanego JSONa treść nagłówka


?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <link rel="stylesheet" href="../../style.css">
</head>
<style>
    html,
    body {
        height: auto;
        overflow-y: auto;
        background-color: #e0e0e0;
    }

    /* .table-container {
        max-width: 70%;
        margin-left: 0;
        overflow-x: auto;
    }*/

    table {

        max-width: 90%;
        border-collapse: collapse;
    }

    th {
        padding: 10px;
        text-align: center;
        min-width: 100px;
        max-width: 400px;
        vertical-align: middle;
        background-color: rgb(123, 187, 155);


        /* lub top, lub bottom */
    }


    td {
        margin-left: 10px;
        text-align: center;
        max-width: 400px;
        vertical-align: middle;
        /* lub top, lub bottom */
    }

    tr {}


    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: rgb(185, 174, 174);
    }

    .buttons_edit_save_delete {
        /*vertical-align: middle;*/
        /* ustaw pionowe wyrównanie przycisku
        /*padding: 5px; /* Opcjonalnie, aby nie były za małe *
        /*margin-bottom: 15px;*/
        /*display: inline-block;*/
        margin-bottom: 20px;
        margin-right: 20px;
        height: 30px;
        /* opcjonalnie */
    }
</style>


<a href="http://192.168.101.203/index.php?page=SAMOchody"
    onclick="window.opener.location.reload(); window.close(); return false;">
    ⬅️ Powrót do listy
</a>

<h2 style="color: rgb(68, 43, 206)"><?php echo htmlspecialchars($header_text); ?></h2>


<button style="width: 100px;margin-bottom:15px;" type="button" onclick="addNewRow()">➕ Dodaj nowy
    wiersz</button>

<form method="POST">

    <table border="1">
        <?php mysqli_data_seek($result, 0); ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <th colspan="2" style="background-color: #aaa;">Rekord ID: <?php echo $row['id']; ?></th>
            </tr>
            <?php $first = true; ?>
            <?php foreach ($columns as $column): ?>
                <?php if ($column !== 'id' && $column !== 'car_id'): ?>
                    <tr <?php if ($first): ?>id="row-<?php echo $row['id']; ?>" <?php $first = false; endif; ?>>
                        <td><strong><?php echo htmlspecialchars($column_names[$column] ?? $column); ?></strong></td>
                        <td data-column="<?php echo htmlspecialchars($column); ?>" data-id="<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row[$column] ?? ''); ?>
                        </td>

                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>

            <tr>
                <td colspan="2">
                    <button class="buttons_edit_save_delete" type="button" id="edit-btn-<?php echo $row['id']; ?>"
                        onclick="toggleEdit('<?php echo $row['id']; ?>')">✏️ Edytuj</button>
                    <button class="buttons_edit_save_delete" type="submit" name="zapisz"
                        value="<?php echo $row['id']; ?>">💾 Zapisz</button>
                    <button class="buttons_edit_save_delete" type="submit" name="usun" value="<?php echo $row['id']; ?>"
                        onclick="return confirmDelete()">🗑️ Usuń</button>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="background-color: #ddd; height: 10px;"></td>
            </tr>
        <?php endwhile; ?>
    </table>


</form>


<a href="http://192.168.101.203/index.php?page=SAMOchody" onclick="window.close(); return false;">⬅️ Powrót do
    listy</a>

<?php require __DIR__ . '/../../includes/footer.php'; ?>


<script>
    function addNewRow() {
        if (document.querySelectorAll('tr[data-new-row="1"]').length > 0) return;

        const table = document.querySelector("form table"); // upewniamy się że wewnątrz <form>
        const columns = <?php echo json_encode($columns); ?>;

        columns.forEach(col => {
            if (col === 'id' || col === 'car_id') return;

            const row = document.createElement("tr");
            row.setAttribute("data-new-row", "1");

            const labelTd = document.createElement("td");
            labelTd.innerHTML = `<strong>${col}</strong>`;
            row.appendChild(labelTd);

            const inputTd = document.createElement("td");

            if (col === 'status_przypomnienia') {
                inputTd.innerHTML = `
                <select name="dane[new][${col}]" style="width:100%">
                    <option value="do_zrobienia">do_zrobienia</option>
                    <option value="zrobione">zrobione</option>
                </select>
            `;
            } else if (col === 'zadanie_typ') {
                inputTd.innerHTML = `
                <select name="dane[new][${col}]" style="width:100%">
                    <option value="wymiana_rozrzadu">Wymiana rozrządu</option>
                    <option value="badanie_techniczne">Badanie techniczne</option>
                    <option value="wymiana_oleju">Wymiana oleju</option>
                    <option value="ubezpieczenie">Ubezpieczenie</option>
                    <option value="wymiana_opon_na_letnie">Wymiana opon na letnie</option>
                    <option value="wymiana_opon_na_zimowe">Wymiana opon na zimowe</option>
                </select>
            `;
            } else {
                inputTd.innerHTML = `<input type="text" name="dane[new][${col}]" style="width:100%">`;
            }

            row.appendChild(inputTd);
            table.appendChild(row);
        });

        // dodaj wiersz z przyciskami
        const buttonRow = document.createElement("tr");
        buttonRow.setAttribute("data-new-row", "1");

        const buttonTd = document.createElement("td");
        buttonTd.colSpan = 2;
        buttonTd.innerHTML = `<button class="buttons_edit_save_delete" type="submit" name="zapisz" value="new">💾 Zapisz</button>`;
        buttonRow.appendChild(buttonTd);

        table.appendChild(buttonRow);
    }

</script>






<script>
    function cancelNewRow() {
        const newRow = document.getElementById("row-new");
        if (newRow) newRow.remove();
    }

</script>


</body>

</html>

<script>

    function toggleEdit(rowId) {
        let editBtn = document.getElementById("edit-btn-" + rowId);
        let isEditing = editBtn.innerText !== "✏️ Edytuj";

        // Zamiast ograniczać się do jednego <tr>, szukamy po data-id
        document.querySelectorAll(`td[data-column][data-id="${rowId}"]`).forEach(cell => {
            let columnName = cell.getAttribute("data-column");
            let originalText = cell.getAttribute("data-original");
            let currentText = cell.innerText.trim();

            if (!isEditing) {
                // zapisz oryginał tylko raz
                cell.setAttribute("data-original", currentText);

                if (columnName === "status_przypomnienia") {
                    cell.innerHTML = `
                    <select name="dane[${rowId}][${columnName}]" style="width:100%">
                        <option value="do_zrobienia" ${currentText === "do_zrobienia" ? "selected" : ""}>do_zrobienia</option>
                        <option value="zrobione" ${currentText === "zrobione" ? "selected" : ""}>zrobione</option>
                    </select>
                `;
                } else {
                    cell.innerHTML = `<textarea name="dane[${rowId}][${columnName}]" rows="1" style="width:100%">${currentText}</textarea>`;
                }

            } else {
                // anuluj edycję – przywróć oryginał
                cell.innerHTML = originalText || '';
            }
        });

        editBtn.innerText = isEditing ? "✏️ Edytuj" : "❌ Anuluj edycję";
    }

</script>


</script>


<script>
    function confirmDelete() {
        return confirm("Czy na pewno chcesz usunąć ten rekord?");
    }
</script>