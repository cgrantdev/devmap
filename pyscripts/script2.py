import os

# ✅ Force webdriver_manager to use a local cache writable by www-data
os.environ['WDM_LOCAL'] = '1'
os.environ['WDM_CACHE'] = '/var/www/.cache/selenium'

import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

def scrape_trueaminos(url):
    options = Options()
    options.headless = True
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    # options.add_argument("--window-size=1920,1080")
    options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36")

    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--remote-debugging-port=9222")
    options.add_argument("--disable-software-rasterizer")

    driver = webdriver.Chrome(options=options)
    driver.get(url)
    time.sleep(5)  # Wait for React to render products (adjust as needed)

    products = []
    product_cards = driver.find_elements(By.CSS_SELECTOR, "div.product-card")
    for card in product_cards:
        # Product URL
        a = card.find_element(By.CSS_SELECTOR, 'a[href^="/products/"]')
        product_url = a.get_attribute('href')
        if product_url and not product_url.startswith('http'):
            product_url = 'https://trueaminos.com' + product_url

        # Name
        name = None
        try:
            name = card.find_element(By.TAG_NAME, 'h3').text.strip()
        except:
            pass

        # Price
        price = None
        try:
            price_span = card.find_element(By.CSS_SELECTOR, 'span.font-heading.font-bold.text-xl')
            price = price_span.text.replace('$', '').replace(',', '').strip()
        except:
            pass

        # Image
        image_url = None
        try:
            img = card.find_element(By.TAG_NAME, 'img')
            image_url = img.get_attribute('src')
            if image_url and image_url.startswith('/api/image-proxy?url='):
                from urllib.parse import urlparse, parse_qs, unquote
                parsed = urlparse(image_url)
                params = parse_qs(parsed.query)
                if 'url' in params:
                    image_url = unquote(params['url'][0])
        except:
            pass

        if name and product_url:
            products.append({
                'name': name,
                'price': price,
                'discount_price': None,
                'second_price': None,
                'image_url': image_url,
                'product_url': product_url,
            })

    driver.quit()
    return products

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No URL provided'}))
        sys.exit(1)
    url = sys.argv[1]
    try:
        products = scrape_trueaminos(url)
        print(json.dumps({'products': products}))
    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(2)