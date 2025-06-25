Adresy:

http://192.168.101.203/pages/BDO/bdo_04_FINAL.php



1. Skrypt bdo_01_lista_keo.php pobiera listê Kart Ewidencji Odpadów (to te pliki zwi¹zane ze stanem magazynowym opadów robione w powi¹zaniu z Kartami Przekazania Odpadów).
Skrypt filtruje rok, wiêc trzeba go uruchamiaæ dla roku 2024, 2025 itd - iterowanie przez kolejne lata dzieje siê ju¿ ju¿ finalnym skrypcie (bdo_04).


2. Skrypt bdo_01b_lista_kart_kpo.php generuje listê kart (od pocz¹tku 2025) z ich id - to taki s³ownik, ¿eby przet³umaczyæ CardName na kpoid.
Pobieram te¿ ca³¹ resztê informacji o kartach, bo przyda siê w skrypcie finalnym bdo_04.

3. Teraz skrypt bdo_02_keo_items_kpo.php u¿yje listy Keo (z bdo_01) do wygenerowania listy zawieraj¹cej kpo. Dziêki temu mam listê kart kpo, które zosta³y ujête
na keo.

4. Skrypt bdo_04:

a. Uruchamia skrypt bdo_01b - czyli pobiera listê kart kpo od pocz¹tku 2025.
b. Uruchamia skrypt bdo_02 (czyli pobiera listê keo). Robi to dla podanych lat (2024-2030).
c. Dodaje do listy z kartami kpo info o kpoid (bo skrypt bdo_01b pobiera CardNames bez kpoid.
d. Dodaje do listy z kartami  kpo kolumnê mówi¹c¹, czy dana karta kpo by³a u¿yta w którymkolwiek keo (used_at_keo/not_used_at_keo).
e. Filtruje listê tak, ¿eby zosta³y tylko karty kpo nie u¿yte i nie wycofane.
f. Dodaje kolumnê URL do ka¿dej karty.
g. Generuje body HTML, które wyœwietla i wysy³a emaila (chyba, ¿e lista jest pusta).

Tym samym na maila trafi lista kart kpo, które wymagaj¹ czynnoœci - albo trzeba naprawiæ odrzucone, ale uj¹æ te prawido³owe w keo.



