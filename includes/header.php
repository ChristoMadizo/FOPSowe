<header>
    <nav>
        <ul>
            <div class="ul li">
                <li><a href="index.php" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == '') ? 'active' : ''; ?>">Main</a></li>
                <li><a href="index.php?page=zlecenia_arki" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'zlecenia_arki') ? 'active' : ''; ?>">Zlecenia Arki</a></li>
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
                <li><a href="index.php?page=naleznosci" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'naleznosci') ? 'active' : ''; ?>">Naleznosci</a></li>

                <li>
                    <a href="index.php?page=wentylacja_alarmy" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'wentylacja_alarmy') ? 'active' : ''; ?>">Wentylacja (alarmy)</a>
                    <ul class="submenu">
                        <li><a href="index.php?page=wykres_temperatury" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'wykres_temperatury') ? 'active' : ''; ?>">Wykres temperatury</a></li>
                    </ul>
                </li>

                <li><a href="index.php?page=sterowanie_bramy" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'sterowanie_bramy') ? 'active' : ''; ?>">Sterowanie bramy</a></li>
                <li><a href="index.php?page=odczytFAKTURYzakupowej" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'odczytFAKTURYzakupowej') ? 'active' : ''; ?>">odczytFAKTURYzakupowej</a></li>
                <li><a href="index.php?page=test_inner" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'test_inner') ? 'active' : ''; ?>">TEST_inner</a></li>
            </div>
        </ul>
    </nav>
</header>
