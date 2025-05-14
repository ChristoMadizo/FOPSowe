<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);



// Obsługa przesłanego pliku PDF
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['pdf_file']['tmp_name'])) {
    $pdf_text = getPdfContent($_FILES['pdf_file']['tmp_name']);   //wywołanie funkcji do odczytu PDF

    echo '<div class="content">';
    echo '<h3>Zawartość PDF:</h3>';
   // echo '<pre>' . htmlspecialchars($pdf_text) . '</pre>';
    echo '</div>';
} else {
    echo '<form method="POST" action="" enctype="multipart/form-data">
    <div class="content">
        <label for="pdf-file">Wybierz plik PDF:</label>
        <input type="file" id="pdf-file" name="pdf_file" accept=".pdf">
        <button type="submit" name="submit">Prześlij</button>
    </div>
    </form>';
}*/

$python_path = '/home/kmadzia/myenv/bin/python';
$script_path = '/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/ReadDataFromInvoice_OFFSET.py';
$command = "$python_path $script_path";

// Pobranie danych JSON z Pythona
$json_output = shell_exec($command);

// Dekodowanie JSON na tablicę PHP
$data = json_decode($json_output, true);

// Sprawdzenie, czy dekodowanie się powiodło
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Błąd dekodowania JSON: " . json_last_error_msg());
}

// Inicjalizacja zmiennych sumujących
$sum_wartosc_netto = 0.0;
$sum_vat = 0.0;
$sum_wartosc_brutto = 0.0;

// Sumowanie wartości w zamówieniach
foreach ($data["zamowienia"] as $zamowienie) {
    $sum_wartosc_netto += floatval($zamowienie["wartosc_netto"]);
    $sum_vat += floatval($zamowienie["vat"]);
    $sum_wartosc_brutto += floatval($zamowienie["wartosc_brutto"]);
}




?>
<p><strong></strong></p>  
<input type="text" id="nowa_wartosc" placeholder="Wpisz wartość" style="margin-top: 30px;margin-bottom: 30px; height: 40px;">
<button onclick="zmienWszystkie()">Zmień opis FAKT</button>
<button onclick="eksportDoCSV()">Eksportuj do CSV</button>

<table border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Lp</th>
            <th>Opis</th>
            <th>Opis FAKT</th>
            <th>Ilość</th>
            <th>Jednostka miary</th>
            <th>Cena</th>
            <th>Stawka VAT</th>
            <th>Wartość netto</th>
            <th>VAT</th>
            <th>Wartość brutto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data["zamowienia"] as $zamowienie): ?>
            <tr>
                <td><?php echo htmlspecialchars($zamowienie["lp"]); ?></td>
                <td><?php echo htmlspecialchars($zamowienie["opis"]); ?></td>
                <td>
                    <input type="text" name="nazwa_faktury_<?php echo $zamowienie["lp"]; ?>" value="druk">
                </td> <!-- Pole formularza -->
                <td><?php echo htmlspecialchars($zamowienie["ilosc"]); ?></td>
                <td><?php echo htmlspecialchars($zamowienie["jednostka_miary"]); ?></td>
                <td><?php echo number_format($zamowienie["cena"], 2, '.', ''); ?></td>
                <td><?php echo htmlspecialchars($zamowienie["stawka_vat"]); ?>%</td>
                <td><?php echo number_format($zamowienie["wartosc_netto"], 2, '.', ''); ?></td>
                <td><?php echo number_format($zamowienie["vat"], 2, '.', ''); ?></td>
                <td><?php echo number_format($zamowienie["wartosc_brutto"], 2, '.', ''); ?></td>
            </tr>
        <?php endforeach; ?>
        <!-- Wiersz podsumowania -->
        <tr>
            <td colspan="7" style="text-align:center;"><strong>Podsumowanie:</strong></td>
            <td><strong><?php echo number_format($sum_wartosc_netto, 2, '.', ' '); ?></strong></td>
            <td><strong><?php echo number_format($sum_vat, 2, '.', ' '); ?></strong></td>
            <td><strong><?php echo number_format($sum_wartosc_brutto, 2, '.', ' '); ?></strong></td>
        </tr>
    </tbody>
</table>


<p><strong>NIP:</strong> <?php echo htmlspecialchars($data["NIP"]); ?></p>
<p><strong>Data wystawienia:</strong> <?php echo htmlspecialchars($data["data_wystawienia"]); ?></p>
<p><strong>Data dostawy:</strong> <?php echo htmlspecialchars($data["data_dostawy"]); ?></p>





<script>
function zmienWszystkie() {
    let nowaWartosc = document.getElementById("nowa_wartosc").value; // Pobiera wartość z pola tekstowego
    if (nowaWartosc.trim() === "") {
        alert("Wpisz wartość przed zmianą!");
        return;
    }

    document.querySelectorAll("input[name^='nazwa_faktury_']").forEach(input => {
        input.value = nowaWartosc;
    });
}

function eksportDoCSV() {
    let rows = document.querySelectorAll("table tbody tr");
    let csvContent = [];

    rows.forEach(row => {
        let rowData = [];
        row.querySelectorAll("td").forEach((cell, index) => {
            let input = cell.querySelector("input");
            rowData.push(input ? input.value.trim() : cell.innerText.trim());
        });
        csvContent.push(rowData.join(";")); // Separator CSV
    });

    let formData = new FormData();
    formData.append("csv_data", csvContent.join("\n"));

    fetch("/home/kmadzia/www/pages/ODCZYtFakturyZAKUPOWEJ/zapiszDaneCSV.php", { 
        method: "POST",
        body: formData 
    })

    .then(response => response.text())
    .then(data => alert("Dane zapisane do CSV!"))
    .catch(error => console.error("Błąd:", error));
}

</script>

