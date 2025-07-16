import json
import sys
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
#from webdriver_manager.core.utils import ChromeType
from bs4 import BeautifulSoup
from webdriver_manager.chrome import ChromeDriverManager
import time
from selenium.webdriver.firefox.service import Service as FirefoxService
from selenium.webdriver.firefox.options import Options as FirefoxOptions
from webdriver_manager.firefox import GeckoDriverManager

if len(sys.argv) < 2:
    print(json.dumps({'error': 'No URL provided'}))
    sys.exit(1)

BASE_URL = sys.argv[1]
# BASE_URL = "https://simplepeptide.com/shop/"
# BASE_URL = "https://simplepeptide.com/shop/page/2"
def get_driver1():
    options = FirefoxOptions()
    options.add_argument("--headless")
    options.binary_location = "/snap/bin/firefox"
    return webdriver.Firefox(service=FirefoxService(GeckoDriverManager().install()), options=options)

def get_driver():
    chrome_options = Options()
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    #chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument(
        "user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    )
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--remote-debugging-port=9222")
    chrome_options.add_argument("--disable-software-rasterizer")
    # Explicitly set binary if needed
    #chrome_options.binary_location = "/usr/bin/chromium-browser"  # or /usr/bin/google-chrome

    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    return driver



def scrape_page(driver, url):
    driver.get(url)
    time.sleep(15)  # wait for Cloudflare/WooCommerce to load
    return driver.page_source

def parse_products(html):
    soup = BeautifulSoup(html, "html.parser")
    products = []

    for product in soup.select(".products .product"):
        name = product.select_one(".woocommerce-loop-product__title")
        name = name.text.strip() if name else None

        price = None
        discount_price = None
        second_price = None
        amounts = product.select(".price .woocommerce-Price-amount")
        ins = product.select_one(".price ins .woocommerce-Price-amount")
        del_ = product.select_one(".price del .woocommerce-Price-amount")

        if len(amounts) > 1 and not ins and not del_:
            price = amounts[0].text.replace("$", "").replace(",", "").strip()
            second_price = amounts[1].text.replace("$", "").replace(",", "").strip()
        elif ins and del_:
            discount_price = ins.text.replace("$", "").replace(",", "").strip()
            price = del_.text.replace("$", "").replace(",", "").strip()
        elif amounts:
            price = amounts[0].text.replace("$", "").replace(",", "").strip()

        img = product.select_one("img.attachment-woocommerce_thumbnail")
        image_url = img.get("src") if img else None

        a = product.select_one("a.woocommerce-LoopProduct-link")
        product_url = a.get("href") if a else None

        if name and product_url:
            products.append({
                "name": name,
                "price": price,
                "discount_price": discount_price,
                "second_price": second_price,
                "image_url": image_url,
                "product_url": product_url,
            })

    return products, soup

def get_next_page_url(soup):
    # WooCommerce pagination usually looks like <a class="next page-numbers" href="...">
    next_link = soup.select_one(".woocommerce-pagination a.next")
    return next_link["href"] if next_link else None

def scrape_all_products(start_url):
    all_products = []
    url = start_url

    while url:
        driver = get_driver()

        html = scrape_page(driver, url)

        products, soup = parse_products(html)
        all_products.extend(products)
        next_url = get_next_page_url(soup)


        driver.quit()

        url = next_url  # move to next page if exists

    return all_products

if __name__ == "__main__":
    products = scrape_all_products(BASE_URL)
    print(json.dumps({"products": products}, indent=2))
