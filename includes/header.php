<header>
    <nav>
        <ul>
            <div class="ul li">
                <li><a href="index.php" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == '') ? 'active' : ''; ?>">Main</a></li>
                <li><a href="index.php?page=zlecenia_arki" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'zlecenia_arki') ? 'active' : ''; ?>">Zlecenia Arki</a></li>
                <li><a href="index.php?page=wentylacja" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'wentylacja') ? 'active' : ''; ?>">Wentylacja</a></li>
                <li><a href="index.php?page=kartoteka" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'kartoteka') ? 'active' : ''; ?>">Kartoteka</a></li>
                <li><a href="index.php?page=etykiety" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'etykiety') ? 'active' : ''; ?>">Wspólne etykiety</a></li>
                <li><a href="index.php?page=faktury" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'faktury') ? 'active' : ''; ?>">Faktury</a></li>
                <li><a href="index.php?page=bdo" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'bdo') ? 'active' : ''; ?>">BeDeO</a></li>
                <li>
                    <a href="index.php?page=paski_wyplaty" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'paski_wyplaty') ? 'active' : ''; ?>">Paski</a>
                    <ul class="submenu">
                        <li><a href="index.php?page=dane_pracownikow" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'dane_pracownikow') ? 'active' : ''; ?>">Dane pracowników</a></li>
                    </ul>
                </li>
            </div>
        </ul>
    </nav>
</header>
