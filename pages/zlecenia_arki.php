<?php   //php_1
require '/home/kmadzia/www/vendor/autoload.php';
//include_once '/home/kmadzia/www/includes/functions.php';

//ob_clean();  // Czyści bufor wyjścia 
ini_set('display_errors', 1);
error_reporting(E_ALL);

//die();


// session_start();  // Rozpoczęcie sesji
    ini_set('display_errors', 1);
    error_reporting(E_ALL);



    

    #region SQL query
    $sql = "
    WITH ordersKM AS (   
    SELECT
        orders.serial as Zamowienie,
        orders.id as OrderID,
        orders.items as OrderItemsJSON,
        orders.created as DataZamowienia,
        orders.orders_group_id as IdGrupyZamowienia,
        contractors.short_name as Kontrahent,
        Sections.Name as Miejsce
    FROM
        prosto.orders orders
    INNER JOIN
        prosto.contractors contractors ON orders.contractor_id = contractors.id
    INNER JOIN
        prosto.orders_sections OrdersSections ON orders.orders_section_id = OrdersSections.id
    INNER JOIN
        prosto.sections Sections ON OrdersSections.section_id = Sections.id
    WHERE contractors.short_name = 'ARKA'
),
OrderItems AS (
    SELECT
        ordersKM.Zamowienie as Zamowienie,
        JSON_UNQUOTE(JSON_EXTRACT(orderItem, '$.parent')) AS parent,
        JSON_UNQUOTE(JSON_EXTRACT(orderItem, '$.name')) AS name,
        JSON_UNQUOTE(JSON_EXTRACT(orderItem, '$.quantity')) AS quantity,
        JSON_UNQUOTE(JSON_EXTRACT(orderItem, '$.unit')) AS unit
    FROM ordersKM,
         JSON_TABLE(
             OrderItemsJSON, 
             '$.*' COLUMNS (
                 orderItem JSON PATH '$'
             )
         ) AS extracted_data
)
SELECT
	GrupyZamówień.Serial as NumerGrupyZamowien,
    ordersKM.Kontrahent as Kontrahent,
    ordersKM.Miejsce as Miejsce,
    DATE(ordersKM.DataZamowienia) as DataZamowienia,
    ordersKM.Zamowienie as Zamówienie,
    OrderItems.name as Nazwa_produktu,
    OrderItems.quantity as Ilość,
    OrderItems.unit as JM
--    OrderItems.parent as Parent
FROM
    ordersKM
INNER JOIN 
    OrderItems ON ordersKM.Zamowienie = OrderItems.Zamowienie
INNER JOIN
    prosto.orders_groups GrupyZamówień ON ordersKM.IdGrupyZamowienia = GrupyZamówień.id
WHERE 
    Parent = 0
    AND DataZamowienia > DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
"
#endregion SQL query
;

?>


<div class="content">   <!-- HTML1 -->
    <h1>Wyniki z bazy danych (Zlecenia Arki)</h1>

    

    <?php  //php_2
  
        $connection=db_connect_mysqli();
        $result = mysqli_query($connection, $sql); //pobranie danych z bazy danych 
        $unique_groups = [];
        $all_data = [];  // Inicjalizujemy pustą tablicę
        $result_all_data = []; // Inicjalizacja zmiennej przed użyciem
        $columns_to_display=['Zamówienie','Nazwa_produktu','Ilość','JM'];

        if ($result) {  //zapełnia tablicę $result_all_data
            while ($row = mysqli_fetch_assoc($result)) {
                $result_all_data[] = $row;  // Każdy wiersz dodajemy jako osobną tablicę 
            }
        } else {
            echo "Błąd zapytania: " . mysqli_error($connection);
        }


        foreach ($result_all_data as $row) {    //ładuje dane z kolumny 'NumerGrupyZamowien' do tablicy $unique_groups
            $unique_groups[] = $row['NumerGrupyZamowien']; // Dodaje dane z kolumny 'kolumna' do tablicy $kolumna_dane
        }

        $unique_groups = array_unique($unique_groups); // Usuwa duplikaty z tablicy

       // $_SESSION['result_all_data'] = $result_all_data; // Zapisujemy dane w sesji
              
        

       // display_table_from_array($result_all_data); // Wywołanie Twojej funkcji do wyświetlania danych
    ?>

    <h2>Wybierz grupę zamówień</h2>  <!-- HTML2 -->

    <form method="POST"> <!-- filtrowanie grup zleceń    -->
        <label for="grupa">Grupa zamówień:</label>
        <select id="grupa" name="grupa">
            <option value="">-- Wybierz grupę --</option>
            <?php
            foreach ($unique_groups as $group) {
                // Jeśli grupa jest wybrana w formularzu, ustaw "selected"
                $selected = (isset($_POST['grupa']) && $_POST['grupa'] === $group) ? 'selected' : '';
                echo "<option value=\"$group\" $selected>$group</option>";
            }
            ?>
        </select>

       

        <button type="submit" name="action" value="filter" style="position: relative; left: 50px; width: 100px;top:auto">Pokaż</button>
        <button type="submit" name="action" value="export" style="position: relative; left: 100px; width: 100px;top:auto">Export</button>
    </form>
 





    <?php  //php_3
        // Filtrowanie danych na podstawie wybranej grupy
   
        
        //echo '<pre>';   // To dla lepszego formatowania w HTML
        //    print_r($_POST); // Wyświetla całą zawartość tablicy $_GET
        //echo '</pre>';



       // display_table_from_array($result_all_data); // Wywołanie Twojej funkcji do wyświetlania danych

      
                
        // Filtrowanie danych na podstawie wybranej grupy
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['grupa']) && $_POST['grupa'] != '') {
                // Wybrano grupę zamówień
                $selected_group = $_POST['grupa'];
        
                // Filtrujemy dane z tablicy $result_all_data
                $filtered_data = array_filter($result_all_data, function($row) use ($selected_group) {
                    return $row['NumerGrupyZamowien'] == $selected_group;
                });
        
                $filtered_data = array_values($filtered_data); // Resetujemy klucze
        
                if (isset($_POST['action']) && $_POST['action'] === 'export') {
                    // Eksportujemy dane przefiltrowane do Excela
                  //  var_dump($filtered_data);   
            
                   // export_to_excel($test_data);              
                   export_to_excel($filtered_data,$columns_to_display);                   
                    exit(); // Kończymy skrypt, aby nie wysyłać dodatkowych danych HTML
                } else {
                    // Wyświetlamy przefiltrowane dane
                    if (!empty($filtered_data)) {
                        echo "<h2>Dane dla grupy zamówień: $selected_group</h2>";
                        $table= display_table_from_array($filtered_data,$columns_to_display);
                        echo $table     ; // Wyświetlenie tabeli - rezultat funkcji
                    } else {
                        echo "Brak danych dla wybranej grupy.";
                    }
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'export') {
                // Eksport wszystkich danych do Excela (gdy nie wybrano grupy)
                export_to_excel($result_all_data,$columns_to_display);
                exit(); // Kończymy skrypt, aby nie wysyłać dodatkowych danych HTML
            } else {
                // Wyświetlanie wszystkich danych
                echo '<p>Nie wybrano żadnej grupy zamówień - lista wszystkich zamówień:</p>';
                display_table_from_array($result_all_data);
            }
        }
        
        
        

       
    ?>



</div>