#!/bin/bash

ISQL="/opt/firebird/bin/isql"
DB="/opt/firebird/FAKT_LIVE_COPY/0002BAZA.FDB"
USER="KRZYSIEK"
PASS="Bielawa55"

$ISQL $DB -user $USER -password $PASS <<EOF
DELETE FROM zz_TMP_NALEZNOSCI;

INSERT INTO zz_TMP_NALEZNOSCI (
    KONTRAHENT,
    RODZAJ_LIMITU,
    DATA_PRZYZNANIA_LIMITU,
    WYSOKOSC_LIMITU,
    FAKTURY_KLUCZ_KONTR,
    NIP,
    "Do zap³aty PLN",
    "Przed terminem PLN",
    "Po terminie PLN",
    "0-15 dni PLN",
    "16-30 dni PLN",
    "31-60 dni PLN",
    "61-90 dni PLN",
    "91-180 dni PLN",
    "181-365 dni PLN",
    "365+ dni PLN",
    "Sprzeda¿ ubezpieczona PLN",
    "Najd³u¿sze ubezpieczone przeterminowanie DNI"
)
SELECT
    KONTRAHENT,
    RODZAJ_LIMITU,
    DATA_PRZYZNANIA_LIMITU,
    WYSOKOSC_LIMITU,
    FAKTURY_KLUCZ_KONTR,
    NIP,
    "Do zap³aty PLN",
    "Przed terminem PLN",
    "Po terminie PLN",
    "0-15 dni PLN",
    "16-30 dni PLN",
    "31-60 dni PLN",
    "61-90 dni PLN",
    "91-180 dni PLN",
    "181-365 dni PLN",
    "365+ dni PLN",
    "Sprzeda¿ ubezpieczona PLN",
    "Najd³u¿sze ubezpieczone przeterminowanie DNI"
FROM UBEZP_04_FAK_ZAPL_FILT_AGGR;

COMMIT;
EOF
