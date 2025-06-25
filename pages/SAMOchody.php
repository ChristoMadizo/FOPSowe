<?php
require '/home/kmadzia/www/vendor/autoload.php';
require '/home/kmadzia/www/includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

//$_SESSION['historia_napraw_query'] = "SELECT * FROM km_base.ff_02_cars_serwis_historia";


$connection = db_connect_mysqli_KM_VM();

$event_type_dostepne_wartosci = ['wymiana_oleju', 'badanie_techniczne', 'ubezpieczenie', 'wymiana_rozrzadu', 'stan_licznika', 'naprawa','pozostale']; //dostępne wartości dla event_type

//Teraz ff_01_cars zawiera tablicę z samochodami
list($query_result, $ff_01_cars) = fetch_data($connection, 'SELECT * FROM km_base.ff_01_cars');  //pobiera listę samochodów
$columns_cars = [
    'id',
    'nr_rejestracyjny',
    'nazwa',
    'paliwo_rodzaj',
    'silnik_pojemnosc',
    'silnik_moc_KM',
    'VIN',
    'rok_produkcji',
    'winiety',
    'uwagi',
    'ladownosc',
    'pierwsza_rejestracja_data',
    'liczba_pasazerow',
    'kolor',
    'gasnica',
    'trojkat',
    'lewarek',
    'zapasowy_klucz',
    'apteczka',
    'kamizelka',
    'zarowka_zapas',
    'akumulator',
    'data_wymiany_opon_wiosna',
    'data_wymiany_opon_jesien',
    'wymiary',
    'sredni_przebieg_miesieczny'
];  //definiuję kolumny do wyświetlania

//pobranie danych - historia napraw (i nie tylko)
$table_name = "km_base.ff_02_cars_serwis_historia"; // Nazwa tabeli
$query_historia_napraw = "SELECT * FROM " . $table_name . " WHERE zadanie_typ LIKE '%'"; // Bazowe zapytanie SQL bez filtra
list($query_result, $result_historia_napraw) = fetch_data($connection, $query_historia_napraw); //pobiera historię napraw POSORTOWANĄ
usort($result_historia_napraw, function ($a, $b) {
    return strtotime($b['serwis_date']) - strtotime($a['serwis_date']);
});
$columns_historia_napraw = ['id', 'serwis_date', 'zadanie_typ', 'serwis_uwagi1', 'serwis_uwagi2', 'stan_licznika'];  //definiuję kolumny do wyświetlania


//pobranie danych - cykliczne zadania
//$table_name = "km_base.ff_01b_cars_zadania_cykliczne"; // Nazwa tabeli
$query_cykliczne_zadania = "SELECT ff_01b_cars_zadania_cykliczne.*,ff_01_cars.nazwa FROM km_base.ff_01b_cars_zadania_cykliczne INNER JOIN 
ff_01_cars WHERE ff_01b_cars_zadania_cykliczne.car_id = ff_01_cars.id"; // Bazowe zapytanie SQL bez filtra
list($query_result, $result_cykliczne_zadania) = fetch_data($connection, $query_cykliczne_zadania); //pobiera historię napraw POSORTOWANĄ
$columns_cykliczne_zadania = ['id', 'car_id', 'zadanie_typ', 'zadanie_interwal_dni', 'zadanie_interwal_kilometry', 'data_nastepny_daedline', 'zadanie_uwagi1', 'zadanie_typ_uwagi2'];  //definiuję kolumny do wyświetlania



//pobranie danych - PRZYPOMNIENIA
//$table_name = 'ff_02b_cars_przypomnienia'; // Nazwa tabeli
$query_przypomnienia = "SELECT ff_02b_cars_przypomnienia.*,ff_01_cars.nazwa FROM ff_02b_cars_przypomnienia INNER JOIN 
ff_01_cars WHERE ff_02b_cars_przypomnienia.car_id = ff_01_cars.id"; // Bazowe zapytanie SQL bez filtra
list($query_result, $result_przypomnienia) = fetch_data($connection, $query_przypomnienia); //pobiera listę przypomnień 
usort($result_przypomnienia, function ($a, $b) {
    return strtotime($a['zadanie_deadline']) - strtotime($b['zadanie_deadline']);
});
$columns_przypomnienia = ['id', 'status_przypomnienia', 'zadanie_typ', 'zadanie_uwagi1', 'zadanie_uwagi2', 'zadanie_deadline'];  //definiuję kolumny do wyświetlania

//wywołanie skryptu, który rozsyła info o przypomnieniach - samo require URUCHAMIA skrypt
//require_once '/home/kmadzia/www/pages/SAMOCHODY/przypomnienia_sendMail_SMS.php';
//przypomnienia_sendMail_SMS($result_przypomnienia);



?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Samochodów</title>
    <style>
        body {
            background-color: #e0e0e0;
        }

        .tab {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .tab-buttons {
            margin-bottom: 10px;
        }

        .tab-buttons button {
            margin-right: 5px;
            padding: 10px;
            cursor: pointer;
        }

        .tab-buttons button.active {
            background-color: #007bff;
            color: white;
        }



        .grid {
            display: grid;
            grid-template-columns: 200px 350px 500px 500px;

            /* Określenie szerokości kolumn */
            gap: 30px;
            /* Opcjonalnie: dodaj odstęp między kolumnami */
        }


        .box {
            /*width: 100%;*/
            /* height: 100%;*/
            border: 1px solid #aaa;
            flex-direction: row;
            white-space: nowrap;
            overflow-y: auto;
            /* Dodaje pionowy pasek przewijania, jeśli potrzeba */
            /*  overflow: hidden;  /* Opcjonalne: ukrycie nadmiarowego tekstu */
            /*text-overflow: ellipsis;*/
            /* Opcjonalne: dodanie efektu "..." dla długiego tekstu */
            justify-content: flex-start;
            /* Wyrównanie tekstu do góry */
            align-items: left;
            /* Opcjonalne: wyrównanie do lewej */
            background-color: #f9f9f9;
            box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);
            padding: 10px;
            text-align: left;
            border-radius: 10px;
            /* Opcjonalnie: wyrównanie tekstu w bloku */
        }

        .button-edit {
            margin-right: auto;
            margin-left: 0;
            align-self: flex-start;
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 25px 25px / 25px 25px;
            border: 1px solid;
        }

        .box strong {
            /* Opcjonalne: dodanie marginesu między etykietą a wartością */
            margin-right: 10px;
        }

        .box table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .title {
            color: rgb(88, 11, 231);
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>

<body>

    <h2>Lista Samochodów</h2>

    <div class="tab-buttons">
        <button onclick="showTab('main')" class="active" style="background-color: #888; color: white;">Lista
            przypomnień</button>
        <?php foreach ($ff_01_cars as $index => $car): ?>
            <button onclick="showTab(<?php echo $index; ?>)" data-car-id="<?php echo $car['id']; ?>">
                <?php echo htmlspecialchars($car['nazwa']); ?>
            </button>

            </button>

            <?php


        endforeach; ?>
    </div>

    <div class="content">

        <div class="tab" id="tab-main" style="display: block;">
            <div class="grid">
                <!-- LISTA PRZYPOMNIEŃ-->
                <div class="box"
                    style="background-color:rgb(164, 202, 223); grid-row: 1; grid-column: 1; height: 800px; width: 1500px">
                    <h4>Lista przypomnień</h4>
                    <table border="1" cellpadding="5" cellspacing="0">
                        <tr>
                            <th>Pojazd</th>
                            <th>zadanie_typ</th>
                            <th>Termin</th>
                            <th style="width: auto; max-width: 300px;">Uwagi</th>
                            <th>Nawiguj do listy</th>
                        </tr>
                        <?php foreach ($result_przypomnienia as $przypomnienie): ?>
                            <?php if ($przypomnienie['status_przypomnienia'] === "do_zrobienia"): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($przypomnienie['nazwa']); ?></td>
                                    <td><?php echo htmlspecialchars($przypomnienie['zadanie_typ']); ?></td>
                                    <?php
                                    $deadline = $przypomnienie['zadanie_deadline'];
                                    $deadline_timestamp = strtotime($deadline);
                                    $today_timestamp = strtotime(date('Y-m-d'));

                                    $is_past = $deadline_timestamp < $today_timestamp;
                                    $is_soon = !$is_past && ($deadline_timestamp - $today_timestamp) <= (5 * 86400); // 5 dni w sekundach
                            
                                    $style = $is_past ? 'background-color:rgb(231, 69, 69);' : ($is_soon ? 'background-color:rgb(255, 165, 0);' : 'background-color:rgb(69, 231, 69);');
                                    ?>

                                    <td style="<?php echo $style; ?> text-align: center;">
                                        <?php echo htmlspecialchars($deadline); ?>
                                    </td>
                                    <td style="width: auto; max-width: 300px; word-wrap: break-word;">
                                        <?php echo htmlspecialchars($przypomnienie['zadanie_uwagi1'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0);"
                                            data-car-id="<?php echo htmlspecialchars($przypomnienie['car_id']); ?>"
                                            data-table="ff_02b_cars_przypomnienia"
                                            data-query="<?php echo htmlspecialchars($query_przypomnienia); ?>"
                                            data-columns='<?php echo htmlspecialchars(json_encode($columns_przypomnienia)); ?>'
                                            data-event_type_dostepne_wartosci='<?php echo htmlspecialchars(json_encode($event_type_dostepne_wartosci)); ?>'
                                            data-dropdown='<?php echo !empty($dropdown_values) ? htmlspecialchars(json_encode($dropdown_values)) : htmlspecialchars(json_encode(["Brak danych"])); ?>'
                                            data-header="Lista przypomnień dla <?php echo htmlspecialchars($przypomnienie['nazwa']); ?>"
                                            onclick="openForm(this)">
                                            📋 Otwórz przypomnienia
                                        </a>
                                    </td>


                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </table>

                </div> <!-- box -->
            </div>
        </div>

        <?php foreach ($ff_01_cars as $index => $car): ?>
            <div class="tab" id="tab-<?php echo $index; ?>">

                <div class="grid">

                    <!-- box z ZDJĘCIEM -->
                    <div
                        style="background-color: rgb(248, 248, 248); grid-row: 1; grid-column: 1; height: 220px; width: 200px;">
                        <img id="car-image-<?php echo $index; ?>"
                            src="/pages/SAMOCHODY/PHOTOS/<?php echo $car['id']; ?>.jpg"
                            alt="Zdjęcie samochodu nr <?php echo $car['id']; ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <!------------------------------------------------------------------------------------>

                    <!---------------------------- INFORMACJE OGÓLNE----------------------->
                <div class="box"
                    style="background-color:rgb(164, 202, 223); grid-row: 1; grid-column: 2; height: 250px; width: 350px">
                    <h4 class="title">

                        INFORMACJE OGÓLNE
                        <br>
                        <?php echo htmlspecialchars($car['nazwa']); ?>
                    </h4>

                    <strong>Nr rejestracyjny:</strong>
                    <?php echo htmlspecialchars($car['nr_rejestracyjny']); ?><br>
                    <strong>Paliwo:</strong>
                    <?php echo htmlspecialchars($car['paliwo_rodzaj'] ?? '-'); ?><br>
                    <strong>Pojemność silnika (moc KM):</strong>
                    <?php echo htmlspecialchars($car['silnik_pojemnosc'] ?? '-') . ' (' . htmlspecialchars($car['silnik_moc_KM'] ?? '-') . ' KM)'; ?>
                    <br>
                    <strong>VIN:</strong>
                    <?php echo htmlspecialchars($car['VIN'] ?? '-'); ?><br>
                    <strong>Rok produkcji:</strong>
                    <?php echo htmlspecialchars($car['rok_produkcji'] ?? '-'); ?><br>
                    <strong>Średni przebieg/mc:</strong>
                    <?php echo number_format((int) $car['sredni_przebieg_miesieczny'] ?? '-', 0, ',', ' '); ?> km<br>

                    <button class="button-edit" data-car-id="<?php echo $car['id']; ?>" data-table="ff_01_cars"
                        data-query="SELECT * FROM km_base.ff_01_cars"
                        data-columns='<?php echo htmlspecialchars(json_encode($columns_cars)); ?>'
                        data-header="Informacje o  <?php echo htmlspecialchars($car['nazwa']); ?>"
                        onclick="openForm(this)">
                        ✏️ Edytuj
                    </button>
                </div> <!-- box -->
                    <!------------------------------------------------------------------------------------>

                    <!------CYKLICZNE ZADANIA - USTAWIENIA-------------------------------------------------------------------------------------------------------------------------->
                    <div class="box"
                        style="background-color:rgb(170, 199, 90); grid-row: 1; grid-column: 3; height: 300px; width: 500px">
                        <!-- NAGŁÓWEK BOXA z przyciskiem uruchamiającym formularz do zmiany danych (przekazuje polecenie sql) -->
                        <h4 style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="title">Cykliczne zadania - USTAWIENIA</div>
                            <!-- Button wysyła do formularza (odrębna strona)   id samochodu, treść zapytania SQL oraz kolumny do wyświetlania -->
                            <?php $table_name = "ff_01b_cars_zadania_cykliczne"; // Nazwa tabeli ?>
                            <button class="button-edit" data-car-id="<?php echo $car['id']; ?>"
                                data-table="<?php echo $table_name; ?>"
                                data-query="<?php echo htmlspecialchars($query_cykliczne_zadania); ?>"
                                data-columns='<?php echo htmlspecialchars(json_encode($columns_cykliczne_zadania)); ?>'
                                data-event_type_dostepne_wartosci='<?php echo htmlspecialchars(json_encode($event_type_dostepne_wartosci)); ?>'
                                data-header="Lista cyklicznych zadań dla <?php echo htmlspecialchars($car['nazwa']); ?>"
                                onclick="openForm(this)">
                                ✏️ Edytuj
                            </button>



                        </h4>

                        <table border="1" cellpadding="5" cellspacing="0">
                            <thead>
                                <tr>

                                    <th>Typ zadania</th>
                                    <th>Najbliższy termin</th>
                                    <th>Interwał (dni)</th>
                                    <th>Interwał (km)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result_cykliczne_zadania as $cykliczne_zadanie): ?>
                                    <?php if ($cykliczne_zadanie['car_id'] == $car['id']): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cykliczne_zadanie['zadanie_typ'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($cykliczne_zadanie['data_nastepny_daedline'] ?? ''); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($cykliczne_zadanie['zadanie_interwal_dni'] ?? ''); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($cykliczne_zadanie['zadanie_interwal_kilometry'] ?? ''); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>

                    <!------------------------------------------------------------------------------------>


                    <!------box z PRZYPOMNIENIAMI-------------------------------------------------------------------------------------------------------------------------->

                    <div class="box"
                        style="background-color:rgb(144, 180, 175); grid-row: 1; grid-column: 4; height: 300px; width: 800px">
                        <!-- NAGŁÓWEK BOXA z przyciskiem uruchamiającym formularz do zmiany danych (przekazuje polecenie sql) -->
                        <h4 style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="title">PRZYPOMNIENIA</div>
                            <!-- Button wysyła do formularza (odrębna strona)   id samochodu, treść zapytania SQL oraz kolumny do wyświetlania -->
                            <?php $table_name = "ff_02b_cars_przypomnienia"; // Nazwa tabeli ?>

                            <button class="button-edit" data-car-id="<?php echo $car['id']; ?>"
                                data-table="<?php echo $table_name; ?>"
                                data-query="<?php echo htmlspecialchars($query_przypomnienia); ?>"
                                data-columns='<?php echo htmlspecialchars(json_encode($columns_przypomnienia)); ?>'
                                data-event_type_dostepne_wartosci='<?php echo htmlspecialchars(json_encode($event_type_dostepne_wartosci)); ?>'
                                data-header="Lista przypomnień dla <?php echo htmlspecialchars($car['nazwa']); ?>"
                                onclick="openForm(this)">
                                ✏️ Edytuj
                            </button>

                        </h4>

                        <table border="1" cellpadding="5" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Deadline</th>
                                    <th>Typ zadania</th>
                                    <th>Uwagi 1</th>

                                </tr>
                            </thead>
                            <tbody>
                            <tbody>
                                <?php foreach ($result_przypomnienia as $przypomnienie): ?>
                                    <?php if ($przypomnienie['car_id'] == $car['id'] && $przypomnienie['status_przypomnienia'] == "do_zrobienia"): ?>
                                        <?php
                                        $deadline = $przypomnienie['zadanie_deadline'] ?? '';
                                        $deadline_timestamp = strtotime($deadline);
                                        $today_timestamp = strtotime(date('Y-m-d'));

                                        // Ustalenie stylu w zależności od deadline
                                        if ($deadline) {
                                            if ($deadline_timestamp < $today_timestamp) {
                                                $row_style = 'background-color:rgb(221, 115, 115);';
                                            } elseif (($deadline_timestamp - 5 * 86400) < $today_timestamp) {
                                                $row_style = 'background-color:rgb(255, 165, 0);';
                                            } else {
                                                $row_style = 'background-color:rgb(140, 194, 140);';
                                            }
                                        } else {
                                            $row_style = '';
                                        }
                                        ?>
                                        <tr style="<?php echo $row_style; ?>">
                                            <td><?php echo htmlspecialchars($deadline); ?></td>
                                            <td><?php echo htmlspecialchars($przypomnienie['zadanie_typ'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($przypomnienie['zadanie_uwagi1'] ?? ''); ?></td>

                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>

                            </tbody>
                        </table>

                    </div>

                    <!------------------------------------------------------------------------------------>




                    <!------box z HISTORIĄ NAPRAW i-------------------------------------------------------------------------------------------------------------------------->

                    <div class="box"
                        style="background-color:rgb(144, 180, 175); grid-row: 2; grid-column: 1; height: 300px; width: 1500px">
                        <!-- NAGŁÓWEK BOXA z przyciskiem uruchamiającym formularz do zmiany danych (przekazuje polecenie sql) -->
                        <h4 style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="title">HISTORIA</div <!-- Button wysyła do formularza (odrębna strona) id
                            samochodu, treść zapytania SQL oraz kolumny do wyświetlania -->
                            <?php $table_name = "ff_02_cars_serwis_historia"; // Nazwa tabeli ?>

                            <button class="button-edit" data-car-id="<?php echo $car['id']; ?>"
                                data-table="<?php echo $table_name; ?>"
                                data-query="<?php echo htmlspecialchars($query_historia_napraw); ?>"
                                data-columns="<?php echo htmlspecialchars(json_encode($columns_historia_napraw)) ?>"
                                data-event_type_dostepne_wartosci='<?php echo htmlspecialchars(json_encode($event_type_dostepne_wartosci)); ?>'
                                data-header="Historia dla <?php echo htmlspecialchars($car['nazwa']); ?>"
                                onclick="openForm(this)">
                                ✏️ Edytuj
                            </button>
                        </h4>
                        <table border="1" cellpadding="5" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Data</th>
                                    <th>Typ zadania</th>
                                    <th>Uwagi</th>
                                    <th>Stan licznika</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result_historia_napraw as $naprawa): ?>
                                    <?php if ($naprawa['car_id'] == $car['id']): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($naprawa['serwis_date'] ?? ''); ?></td>
                                            <td style="<?php echo ($naprawa['zadanie_typ'] === 'naprawa') ? 'color:rgb(173, 18, 153) ;' : ''; ?>">
                                                <?php echo htmlspecialchars($naprawa['zadanie_typ'] ?? ''); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($naprawa['serwis_uwagi1'] ?? ''); ?></td>
                                            <td><?php echo number_format((int) $naprawa['stan_licznika'], 0, ',', ' '); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>

                    <!------------------------------------------------------------------------------------>







                </div> <!-- grid -->
            </div>
        <?php endforeach; ?>
    </div>




    <script>
        function showTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const buttons = document.querySelectorAll('.tab-buttons button');

            tabs.forEach(tab => tab.style.display = 'none');
            buttons.forEach(btn => btn.classList.remove('active'));

            const selectedTab = index === 'main' ? document.getElementById('tab-main') : document.getElementById('tab-' + index);
            selectedTab.style.display = 'block';
            if (index === 'main') {
                buttons[0].classList.add('active'); // pierwszy button to "Główna"
            } else {
                buttons[index + 1].classList.add('active'); // +1 bo pierwszy to "Główna"
            }

            // Zapisujemy wybrany tab w localStorage
            localStorage.setItem('selectedTab', index);
        }

        // Domyślnie pokazuj ostatnio wybraną zakładkę
        document.addEventListener("DOMContentLoaded", function () {
            const savedTab = localStorage.getItem('selectedTab') || 'main';
            showTab(savedTab);
        });

    </script>


    <script>

        function openForm(button) {
            let data = {
                car_id: button.getAttribute('data-car-id'),
                table: button.getAttribute('data-table'),
                query: button.getAttribute('data-query'),
                columns: JSON.parse(button.getAttribute('data-columns')),
                header: button.getAttribute('data-header'),
                event_type_dostepne_wartosci: JSON.parse(button.getAttribute('data-event_type_dostepne_wartosci')), // Poprawne pobranie danych
                dropdown: JSON.parse(button.getAttribute('data-dropdown'))
            };

            console.log("Załadowane event_type_dostepne_wartosci:", data.event_type_dostepne_wartosci); // Debugowanie

            let jsonData = encodeURIComponent(JSON.stringify(data));
            let formUrl = data.table === "ff_01_cars"
                ? "/pages/SAMOCHODY/form_edit_data_SAMOCHODY.php"
                : "/pages/SAMOCHODY/form_edit_data.php";

            window.open(formUrl + "?data=" + jsonData, "_blank");
        }



    </script>


</body>

</html>