import requests
import json
import re
from bs4 import BeautifulSoup
from urllib.parse import quote
import sys


# if len(sys.argv) < 2:
#    print(json.dumps({'error': 'No URL provided'}))
#    sys.exit(1)

#raw_payload = sys.argv[1]           # JSON string from PHP
#payload = json.loads(raw_payload)   # decode JSON

#BASE_URL = payload["products_url"]
#selectors = payload["selectors"]

#product_container_selector = selectors["product_container"]
#name_selector = selectors["name"]
#price_selector = selectors["price"]
#image_selector = selectors["image"]
#link_selector = selectors["link"]
# BASE_URL = "https://www.peptidesciences.com/all-peptides"
BASE_URL = "https://verifiedpeptides.com/peptides/"
# product_container_selector = "li.c-product-card"
# name_selector = "h1.s-pdp__title"
# image_selector = "img.gallery-placeholder__image"
# price_selector = "span.price"
# link_selector = "a.c-product-card__body"

product_container_selector = "div.product-small"
name_selector = "h1.product-title"
image_selector = "img.wp-post-image"
price_selector = "bdi"
link_selector = "a"

SCRAPEDO_API_KEY = "233d356d97884d8d881b6e24b81e44d3356aa0922d1"   # <<< paste your API key here


session = requests.Session()

def fetch_via_scrape_do(url):
    response = session.get(
        "https://api.scrape.do/",
        params={
            "token": SCRAPEDO_API_KEY,
            "render": "true",
            "url": url,
        },
        headers={
            "User-Agent": "Mozilla/5.0"
        },
        timeout=60,
    )

    response.raise_for_status()
    return response.text


# -------- LIST PAGE SCRAPE --------

def parse_listing_for_product_urls(html):
    soup = BeautifulSoup(html, "html.parser")
    urls = []

    for product in soup.select(product_container_selector):
        a_el = product.select_one(link_selector)
        href = a_el.get("href") if a_el else None
        if href and href not in urls:
            urls.append(href)

    return urls, soup


def get_next_page_url(current_url, soup):
    next_btn = soup.select_one("a.next")
    if next_btn and next_btn.get("href"):
        return next_btn["href"]

    # m = re.search(r"/page/(\d+)", current_url)
    # if m:
    #     page = int(m.group(1)) + 1
    #     return re.sub(r"/page/\d+", f"/page/{page}", current_url)

    # if "all-peptides" in current_url:
    #     return current_url.rstrip("/") + "/page/2"

    return None


def collect_all_product_urls(start_url):
    all_urls = []
    url = start_url
    seen = set()

    while url and url not in seen:
        # print("Listing page:", url)
        seen.add(url)

        html = fetch_via_scrape_do(url)
        urls, soup = parse_listing_for_product_urls(html)

        all_urls.extend(urls)

        url = get_next_page_url(url, soup)

    return list(set(all_urls))


# -------- PRODUCT PAGE SCRAPE --------

def parse_product_page(html):
    soup = BeautifulSoup(html, "html.parser")

    # name
    name_el = soup.select_one(name_selector)
    name = name_el.text.strip() if name_el else None

    # image
    img_el = soup.select_one(image_selector)
    image_url = None
    if img_el:
        image_url = img_el.get("data-large_image") or img_el.get("src")

    # prices
    price = None
    discount_price = None

    price_wrapper = soup.select_one(price_selector)

    if price_wrapper:
        # ins = price_wrapper.select_one("ins .woocommerce-Price-amount")
        # de = price_wrapper.select_one("del .woocommerce-Price-amount")
        # single = price_wrapper.select_one(".woocommerce-Price-amount")

        # if ins and de:
        #     discount_price = ins.text.strip().replace("$", "").replace(",", "")
        #     price = de.text.strip().replace("$", "").replace(",", "")
        # elif single:
        #     price = single.text.strip().replace("$", "").replace(",", "")

        price = price_wrapper.text.strip().replace("$", "").replace(",", "")

    return {
        "name": name,
        "image_url": image_url,
        "price": price,
        "discount_price": discount_price
    }


def scrape_product_details(url):
    # print("Product page:", url)
    html = fetch_via_scrape_do(url)
    data = parse_product_page(html)
    data["product_url"] = url
    return data


# -------- MAIN --------

def scrape_all():
    product_urls = collect_all_product_urls(BASE_URL)

    # print(f"Found {len(product_urls)} product URLs")

    results = []
    k = 0
    for url in product_urls:
        try:
            detail = scrape_product_details(url)
            results.append(detail)
            k = k + 1
            if k > 0:
                break
        except Exception as e:
            print("Error processing", url, str(e))

    return results


if __name__ == "__main__":
    products = scrape_all()
    print(json.dumps({"products": products}, indent=2))
