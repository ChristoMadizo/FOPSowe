
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    
    <link rel="icon" href="data:,"> <!-- Nie używamy pliku ikony strony -->
    <title>KMset</title>
</head>


<body>

<header>
    <?php include 'includes/header.php'; ?>
</header>



    <div class="content">
        <!-- Treść strony będzie zależna od wybranego menu -->
        <?php
            // Sprawdź, który plik podstrony załadować
            if (isset($_GET['page']) && file_exists('pages/' . $_GET['page'] . '.php')) {
                include('pages/' . $_GET['page'] . '.php');  // Załaduj odpowiednią stronę z folderu pages

            } else {
                echo '<p>Wybierz opcję z menu...</p>';
            }
          //  var_dump($_GET['page']);
        ?>
    </div>

<footer>   
    <?php include 'includes/footer.php'; ?>
</footer>

</body>

</html>


