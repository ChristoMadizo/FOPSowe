import os
import hashlib
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from bs4 import BeautifulSoup
import time
from datetime import datetime
from webdriver_manager.chrome import ChromeDriverManager
import pandas as pd
import json



# Funkcja do porównania i zwrócenia odpowiedzi
def compare_and_notify(poczatek, tekst):
    log_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), "logi.txt")

    if os.path.exists(log_file):
        with open(log_file, "r") as file:
            saved_content = file.read()

        if saved_content != f"{poczatek} {tekst}":
            # Zmiana wykryta
            with open(log_file, "w") as file:
                file.write(f"{poczatek} {tekst}")
            return ["zmiana wykryta", f"{poczatek} {tekst}"]
        else:
            return ["bez zmiany", f"{poczatek} {tekst}"]
    else:
        with open(log_file, "w") as file:
            file.write(f"{poczatek} {tekst}")
        return ["zmiana wykryta", f"{poczatek} {tekst}"]


# Selenium część skryptu
chrome_options = Options()
chrome_options.add_argument("--headless")
driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)

url = "http://192.168.101.89/en/svg/00000004.svg"
auth = ("admin", "2222")

# Dodajemy wyświetlanie aktualnej daty i godziny
current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
# print(f"Aktualny czas: {current_time}")  wywalam, bo psuje dane JSON dla PHP

driver.get(url)
time.sleep(3)

iframe = driver.find_element(By.TAG_NAME, 'iframe')
driver.switch_to.frame(iframe)
time.sleep(3)

table = driver.find_element(By.ID, 'historyTable')
html_content = table.get_attribute('outerHTML')

soup = BeautifulSoup(html_content, 'html.parser')
rows = soup.find_all('tr', class_=['activerow', 'normalrow'])
rows = rows[:30]

data = []

for row in rows:
    cols = row.find_all('td')
    if len(cols) >= 4:
        start_time = cols[0].get_text(strip=True)
        end_time = cols[1].get_text(strip=True)
        code = cols[2].get_text(strip=True)
        description = cols[3].get_text(strip=True)

        data.append([start_time, end_time, code, description])

df = pd.DataFrame(data, columns=['Poczatek', 'Koniec', 'Kod', 'Opis'])


if data:
    first_alarm = data[0]
    poczatek = first_alarm[0]
    tekst = first_alarm[3]



    # Sprawdzamy i porównujemy logi
    result = compare_and_notify(poczatek, tekst)

    print(json.dumps(result))

driver.quit()
