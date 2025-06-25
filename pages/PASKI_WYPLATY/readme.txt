Opis mechanizmu kasowania plików z paskami:

1. W g³ównym skrypcie (paski_wyplaty_secure.php) jest w <head> skrypt js, który zaczyna siê wykonywaæ po tym jak 
user wejdzie na stronê i wykonuje siê co 10 sekund. Pobiera on aktualny czas i wrzuca to info do pliku 
sprawdzacz_obecnosci_na_stronie.txt.
2. kasowanie_paskow.php uruchamia siê z crontaba co minutê i sprawdza czy godzina w tym pliku jest starsza lub
równa 1 minuta. Jeœli tak, to kasuje pliki pdf z folderów projektu. Dzia³a to, bo jak user wyjdzie ze strony,
to js przestaje aktualizowaæ czas w pliku.


