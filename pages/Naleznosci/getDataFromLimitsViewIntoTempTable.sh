#!/bin/bash

ISQL="/opt/firebird/bin/isql"
DB="/opt/firebird/FAKT_LIVE_COPY/0002BAZA.FDB"
USER="KRZYSIEK"
PASS="Bielawa55"

$ISQL $DB -user $USER -password $PASS <<EOF
DELETE FROM zz_TMP_NALEZNOSCI;

INSERT INTO zz_TMP_NALEZNOSCI (
    KONTRAHENT,
    RODZAJLIMITU,
    DATAPRZYZNANIALIMITU,
    WYSOKOSC_LIMITU,
    FAKTURYKLUCZKONTR,
    NIP,
    "Do zapłatyPLN",
    "Przed terminemPLN",
    "Po terminiePLN",
    "0-15 dniPLN",
    "16-30 dniPLN",
    "31-60 dniPLN",
    "61-90 dniPLN",
    "91-180 dniPLN",
    "181-365 dniPLN",
    "365+ dniPLN",
    "Sprzedaż ubezpieczonaPLN",
    "NajdlUbezpPrzetermDNI"
)
SELECT
    KONTRAHENT,
    RODZAJLIMITU,
    DATAPRZYZNANIALIMITU,
    WYSOKOSC_LIMITU,
    FAKTURYKLUCZKONTR,
    NIP,
    "Do zapłatyPLN",
    "Przed terminemPLN",
    "Po terminiePLN",
    "0-15 dniPLN",
    "16-30 dniPLN",
    "31-60 dniPLN",
    "61-90 dniPLN",
    "91-180 dniPLN",
    "181-365 dniPLN",
    "365+ dniPLN",
    "Sprzedaż ubezpieczonaPLN",
    "NajdlUbezpPrzetermDNI"
FROM UBEZP_04_FAK_ZAPL_FILT_AGGR;

COMMIT;
EOF