<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Wyniki z bazy danych__</title> 
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="content">
        <!-- Tutaj wstawiasz treść strony -->
        <h1>Wyniki z bazy danych</h1>
        <p>Tu jakiś bajer itd</p>
        <?php  display_table($danezPROSTO); ?>
    </div>

    <div class="container">
    <p>To jest kontener w prawym górnym rogu strony.</p>
</div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
