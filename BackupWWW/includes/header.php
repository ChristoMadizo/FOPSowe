<header>
    <nav>
        <ul>
            <div class="ul li">
                <li><a href="index.php" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == '') ? 'active' : ''; ?>">Main</a></li>
                <li><a href="index.php?page=zlecenia_arki" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'zlecenia_arki') ? 'active' : ''; ?>">Zlecenia Arki</a></li>
                <li><a href="index.php?page=wentylacja" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'wentylacja') ? 'active' : ''; ?>">Wentylacja</a></li>
            </div>
        </ul>
    </nav>
</header>
