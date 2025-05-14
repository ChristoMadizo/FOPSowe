def get_vat_and_arrows(KrajKontrahenta):
    """
    Zwraca odpowiednią stawkę VAT i liczbę strzałek na podstawie kraju kontrahenta.

    Args:
        country (str): Kraj kontrahenta.

    Returns:
        tuple: Stawka VAT (str), liczba strzałek (int)
    """
    if KrajKontrahenta == "PL":
        return 6
    elif KrajKontrahenta in (
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'GR', 'ES', 'NL', 'IE', 'LT', 'LU', 'LV', 'MT', 'DE',
    'PT', 'RO', 'SK', 'SI', 'SE', 'HU', 'IT'):   #UE bez Polski
        return 13
    else:
        return 1 #eksport
