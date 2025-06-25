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
$dropdown_values = json_decode($_GET['data'], true)['dropdown_values'] ?? [];
$event_type_dostepne_wartosci = json_decode($_GET['data'], true)['event_type_dostepne_wartosci'] ?? [];

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

    // Pobranie rzeczywistego klucza dla nowego wiersza (jeśli jest dynamiczny)
    $dane_klucz = array_key_first($_POST['dane']); // Pobiera pierwszy klucz np. "new"
    $row_data = $_POST['dane'][$dane_klucz]; // Pobranie danych wiersza

    // Sprawdzenie, czy są dane do zapisania
    if (empty($row_data)) {
        echo "<p style='color: red;'>Brak danych do zapisania!</p>";
        return;
    }

    if ($rowId === 'new') {
        // INSERT NOWEGO REKORDU
        $cols = [];
        $vals = [];

        foreach ($row_data as $col => $val) {
            if ($col === 'id')
                continue; // Pominięcie ID
            $cols[] = "`" . mysqli_real_escape_string($connection, $col) . "`";
            $vals[] = "'" . mysqli_real_escape_string($connection, $val) . "'";
        }

        // Dodaj car_id, jeśli nie ma w formularzu
        if (!array_key_exists('car_id', $row_data)) {
            $cols[] = "`car_id`";
            $vals[] = "'" . mysqli_real_escape_string($connection, $car_id) . "'";
        }

        // Zapytanie SQL: INSERT INTO
        $insert_query = "INSERT INTO `$table` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
        if (mysqli_query($connection, $insert_query)) {
            $nowe_id = mysqli_insert_id($connection); // Pobranie ID nowo dodanego rekordu
            echo "<p style='color: green;'>Nowy rekord został dodany! ID: $nowe_id</p>";
        } else {
            echo "<p style='color: red;'>Błąd dodawania: " . mysqli_error($connection) . "</p>";
        }

    } else {
        // UPDATE ISTNIEJĄCEGO REKORDU
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
                echo "<p style='color: red;'>Błąd zapisu: " . mysqli_error($connection) . "</p>";
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
    $query = "SELECT $selected_columns FROM `$table` WHERE car_id = $car_id ORDER BY `$sort_column` desc";
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






    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: rgb(185, 174, 174);
    }

    .existing-row button[name="zapisz"] {
        display: none;
    }

    .completed-row {
        color: gray;
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
        <tr>
            <?php foreach ($columns as $column):

                $column_names = [   //słownik do przejścia 
                    'zadanie_typ' => 'Typ zadania',
                    'zadanie_interwal_dni' => 'Interwał (dni)',
                    'status_przypomnienia' => 'Status przypomnienia',
                    'data_nastepny_daedline' => 'Następny deadline',
                    'serwis_date' => 'Data serwisu',
                    'badanie_techniczne' => 'Badanie techniczne',
                    'zadanie_uwagi1' => 'Uwagi 1',
                    'zadanie_typ_uwagi2' => 'Uwagi 2',
                    'zadanie_interwal_kilometry' => 'Interwał (kilometry)',
                    // Dodaj więcej kolumn według potrzeb
                ];


                ?>
                <?php if ($column !== 'id' && $column !== 'car_id'): //żeby nie wyświetlać ID i car_id ?>
                    <th><?php echo htmlspecialchars($column_names[$column] ?? $column); ?></th>
                <?php endif; ?>
            <?php endforeach; ?>
            <th>🗑️ Akcja</th>
        </tr>


        <?php mysqli_data_seek($result, 0); ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <!--jeśli jest kolumna status_przypomnienia i status jest "zrobione" to dodaj klasę completed-row-->


            <tr id="row-<?php echo $row['id']; ?>" class="existing-row">
                <!--<?php echo ($row['status_przypomnienia'] === 'zrobione') ? 'completed-row' : 'ole'; ?>-->
                <?php foreach ($columns as $column): ?>
                    <?php if ($column !== 'id' && $column !== 'car_id'): ?>
                        <td data-column="<?php echo htmlspecialchars($column); ?>">
                            <?php echo htmlspecialchars($row[$column] ?? ''); ?>
                        </td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <td style="">
                    <button class="buttons_edit_save_delete" type="button" id="edit-btn-<?php echo $row['id']; ?>"
                        onclick="toggleEdit('<?php echo $row['id']; ?>')">✏️ Edytuj</button>
                    <button class="buttons_edit_save_delete" type="submit" name="zapisz"
                        value="<?php echo $row['id']; ?>">💾 Zapisz</button>
                    <button class="buttons_edit_save_delete" type="submit" name="usun" value="<?php echo $row['id']; ?>"
                        onclick="return confirmDelete()">🗑️
                        Usuń</button>
                </td>
            </tr>


        <?php endwhile; ?>

    </table>

</form>


<a href="http://192.168.101.203/index.php?page=SAMOchody" onclick="window.close(); return false;">⬅️ Powrót do
    listy</a>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

<script>
    const originalData = {};

    function toggleEdit(id) {
        const row = document.getElementById("row-" + id);
        if (!row) {
            console.error(`Nie znaleziono elementu z id: row-${id}`);
            return;
        }

        originalData[id] = {};

        const dropdownValues = <?php echo json_encode($dropdown_values); ?>;
        const event_type_dostepne_wartosci = <?php echo json_encode($event_type_dostepne_wartosci); ?>;
        console.log("Załadowane event_type_dostepne_wartosci:", event_type_dostepne_wartosci);

        row.querySelectorAll("td[data-column]").forEach(td => {
            const column = td.dataset.column;
            const originalValue = td.textContent.trim();
            originalData[id][column] = originalValue;

            let inputElement;

            if (column === "status_przypomnienia") {
                inputElement = document.createElement("select");
                inputElement.name = `dane[${id}][${column}]`;
                ["do_zrobienia", "zrobione"].forEach(optionValue => {
                    const option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                    //if (optionValue === originalValue) option.selected = true;
                    inputElement.appendChild(option);
                });
            } else if (column === "zadanie_typ") {
                inputElement = document.createElement("select");
                inputElement.name = `dane[${id}][${column}]`;

                event_type_dostepne_wartosci.forEach(optionValue => {
                    console.log(`Dodaję opcję: ${optionValue}`); // Debugowanie
                    const option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                   // if (optionValue === originalValue) option.selected = true;
                    inputElement.appendChild(option);
                });
            } else {
                inputElement = document.createElement("input");
                inputElement.name = `dane[${id}][${column}]`;
                inputElement.value = originalValue;
                inputElement.style.width = "90%";
            }

            td.innerHTML = "";
            td.appendChild(inputElement);
        });

        // Sterowanie przyciskami
        const saveBtn = document.querySelector(`button[name="zapisz"][value="${id}"]`);
        const editBtn = document.getElementById(`edit-btn-${id}`);
        const deleteBtn = document.querySelector(`button[name="usun"][value="${id}"]`);
        let cancelBtn = document.getElementById("cancel-btn-" + id);

        console.log("Przyciski:", { saveBtn, editBtn, cancelBtn, deleteBtn });

        if (saveBtn) {
            saveBtn.style.display = "inline-block";
        } else {
            console.warn(`Przycisk "Zapisz" dla id=${id} nie istnieje.`);
        }

        if (editBtn) {
            editBtn.style.display = "none";
        }

        if (deleteBtn) {
            deleteBtn.style.display = "none"; // Ukryj przycisk "Usuń" podczas edycji
        }

        if (!cancelBtn) {
            cancelBtn = document.createElement("button");
            cancelBtn.id = "cancel-btn-" + id;
            cancelBtn.className = "buttons_edit_save_delete";
            cancelBtn.type = "button";
            cancelBtn.innerText = "❌ Anuluj";
            cancelBtn.onclick = () => cancelEdit(id);
            if (saveBtn && saveBtn.parentElement) {
                saveBtn.parentElement.appendChild(cancelBtn);
            } else {
                console.warn(`Nie można dodać przycisku "Anuluj", brak odpowiedniego kontenera.`);
            }
        } else {
            cancelBtn.style.display = "inline-block";
        }
    }



    function cancelEdit(id) {
        const row = document.getElementById("row-" + id);
        if (!row || !originalData[id]) return;

        // Przywróć oryginalne wartości komórek
        row.querySelectorAll("td[data-column]").forEach(td => {
            const column = td.dataset.column;
            td.innerHTML = originalData[id][column];
        });

        // Pobierz przyciski
        const saveBtn = document.querySelector(`button[name="zapisz"][value="${id}"]`);
        const editBtn = document.getElementById(`edit-btn-${id}`);
        const deleteBtn = document.querySelector(`button[name="usun"][value="${id}"]`);
        const cancelBtn = document.getElementById(`cancel-btn-${id}`);

        // Ukryj "Zapisz"
        if (saveBtn) {
            saveBtn.style.display = "none";
        }

        // Przywróć "Edytuj"
        if (editBtn) {
            editBtn.style.display = "inline-block";
        }

        // Przywróć "Usuń"
        if (deleteBtn) {
            deleteBtn.style.display = "inline-block";
        }

        // Usuń "Anuluj"
        if (cancelBtn) {
            cancelBtn.remove();
        }

        // Usuń przechowywane dane edycji
        delete originalData[id];
    }


    function confirmDelete() {
        return confirm("Czy na pewno chcesz usunąć ten rekord?");
    }



    function addNewRow() {
    const table = document.querySelector("table");
    if (!table) {
        console.error("Nie znaleziono tabeli!");
        return;
    }

    const newRowId = "new";
    const newRow = document.createElement("tr");
    newRow.id = `row-${newRowId}`;
    newRow.className = "existing-row";

    const columns = <?php echo json_encode($columns); ?>;
    const event_type_dostepne_wartosci = <?php echo json_encode($event_type_dostepne_wartosci); ?>;

    console.log("Załadowane event_type_dostepne_wartosci:", event_type_dostepne_wartosci);

    columns.forEach(column => {
        if (column !== "id" && column !== "car_id") {
            const td = document.createElement("td");
            td.dataset.column = column;

            let inputElement;
            if (column === "zadanie_typ") {
                inputElement = document.createElement("select");
                inputElement.name = `dane[${newRowId}][${column}]`;

                event_type_dostepne_wartosci.forEach(optionValue => {
                    console.log(`Dodaję opcję: ${optionValue}`); // Debugowanie
                    const option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                    inputElement.appendChild(option);
                });
            } else {
                inputElement = document.createElement("input");
                inputElement.name = `dane[${newRowId}][${column}]`;
                inputElement.style.width = "90%";
            }

            td.appendChild(inputElement);
            newRow.appendChild(td);
        }
    });

    // Dodanie przycisków akcji
    const actionTd = document.createElement("td");

    const saveBtn = document.createElement("button");
    saveBtn.className = "buttons_edit_save_delete";
    saveBtn.type = "submit";
    saveBtn.name = "zapisz";
    saveBtn.value = newRowId;
    saveBtn.innerHTML = "💾 Zapisz";
    saveBtn.style.display = "inline-block"; // Zapewnienie widoczności

    const cancelBtn = document.createElement("button");
    cancelBtn.className = "buttons_edit_save_delete";
    cancelBtn.type = "button";
    cancelBtn.innerText = "❌ Anuluj";
    cancelBtn.onclick = () => newRow.remove();
    cancelBtn.style.display = "inline-block"; // Zapewnienie widoczności

    actionTd.appendChild(saveBtn);
    actionTd.appendChild(cancelBtn);
    newRow.appendChild(actionTd);

    // Dodanie nowego wiersza na górę tabeli
    if (table.rows.length > 1) {
        table.tBodies[0].insertBefore(newRow, table.rows[1]);
    } else {
        table.appendChild(newRow);
    }

    console.log("Dodano nowy wiersz na górze:", newRow);
}



</script>