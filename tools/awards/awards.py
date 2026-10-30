#!/usr/bin/env python3
"""
HiFi Expert — award finder.

Scans the catalog's hardware products, looks each one up on its manufacturer's site
(and the main award bodies), extracts awards (What Hi-Fi? stars, EISA, Diapason d'Or,
Stereophile, Hi-Fi Choice, Hi-Fi News, Sound & Vision, The Absolute Sound, Hi-Fi+)
and writes a CSV report. With --apply it stores the findings on each product as the
`_hx_awards` meta, which the theme renders as badges.

Usage (on the staging server, inside the theme folder):
    python3 tools/awards/awards.py --out awards.csv                # report only
    python3 tools/awards/awards.py --out awards.csv --limit 50     # first 50 products
    python3 tools/awards/awards.py --out awards.csv --brand yamaha # one brand
    python3 tools/awards/awards.py --apply awards.csv              # write accepted rows to products

Requires: wp-cli at /var/www/html, python3 with requests + beautifulsoup4.
Polite by design: 1 request / 1.5 s per host, a real UA, honours robots.txt disallow lists.
"""

import argparse
import csv
import json
import os
import re
import subprocess
import sys
import time
from urllib.parse import quote_plus, urlparse, urljoin
from urllib import robotparser

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    sys.exit("pip3 install requests beautifulsoup4   (apt: python3-requests python3-bs4)")

WP_PATH = os.environ.get("WP_PATH", "/var/www/html")
HERE = os.path.dirname(os.path.abspath(__file__))
UA = "Mozilla/5.0 (compatible; HiFiExpertAwardsBot/1.0; +https://hifiexpert.ro/contact)"
HARDWARE_CATS = ["boxe", "amplificatoare", "surse", "cabluri", "accesorii-2", "consignatie"]
DELAY = 1.5

# Award patterns: (label, regex, kind). Year and stars captured where present.
AWARD_PATTERNS = [
    ("What Hi-Fi?", r"what\s*hi[\s\-]?fi\??[^.\n]{0,80}?(?:(5|five)[\s\-]*(?:star|stars|\*)|(award|awards|best\s+buy|product\s+of\s+the\s+year))(?:[^.\n]{0,40}?(20\d\d))?", "mag"),
    ("What Hi-Fi?", r"(5|five)[\s\-]*stars?[^.\n]{0,40}?what\s*hi[\s\-]?fi(?:[^.\n]{0,40}?(20\d\d))?", "mag"),
    ("EISA Award", r"EISA[\s\-]*(?:award|awards)?[^.\n]{0,60}?(20\d\d)(?:\s*[-–/]\s*(?:20)?\d\d)?", "award"),
    ("Diapason d'Or", r"diapason\s+d['’]or(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Stereophile Recommended", r"stereophile[^.\n]{0,40}?(?:recommended\s+component|class\s+[A-C]\+?)(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Hi-Fi Choice", r"hi[\s\-]?fi\s+choice[^.\n]{0,40}?(?:recommended|editor['’]?s\s+choice|award|group\s+test\s+winner|best\s+buy)(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Hi-Fi News Outstanding Product", r"hi[\s\-]?fi\s+news[^.\n]{0,40}?(?:outstanding\s+product|highly\s+commended)(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Sound & Vision Top Pick", r"sound\s*&(?:amp;)?\s*vision[^.\n]{0,40}?top\s+pick(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("The Absolute Sound Golden Ear", r"(?:the\s+)?absolute\s+sound[^.\n]{0,40}?(?:golden\s+ear|editors['’]?\s+choice|product\s+of\s+the\s+year)(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Hi-Fi+ Recommended", r"hi[\s\-]?fi\s*\+[^.\n]{0,40}?(?:recommended|award)(?:[^.\n]{0,40}?(20\d\d))?", "award"),
    ("Red Dot Design Award", r"red\s+dot(?:\s+design)?\s+award(?:[^.\n]{0,40}?(20\d\d))?", "design"),
    ("iF Design Award", r"\biF\s+design\s+award(?:[^.\n]{0,40}?(20\d\d))?", "design"),
    ("Stereo Magazine", r"stereo\s+magazine[^.\n]{0,40}?(?:highlight|award|reference)(?:[^.\n]{0,40}?(20\d\d))?", "mag"),
    ("Audio (DE)", r"\baudio\b[^.\n]{0,20}?(?:referenz|highlight|testsieger)(?:[^.\n]{0,40}?(20\d\d))?", "mag"),
]

_session = requests.Session()
_session.headers.update({"User-Agent": UA, "Accept-Language": "en,ro;q=0.8"})
_last_hit = {}
_robots = {}


def polite_get(url, timeout=20):
    host = urlparse(url).netloc
    rp = _robots.get(host)
    if rp is None:
        rp = robotparser.RobotFileParser()
        try:
            rp.set_url(f"https://{host}/robots.txt")
            rp.read()
        except Exception:
            rp = None
        _robots[host] = rp
    if rp and not rp.can_fetch(UA, url):
        return None
    wait = DELAY - (time.time() - _last_hit.get(host, 0))
    if wait > 0:
        time.sleep(wait)
    _last_hit[host] = time.time()
    try:
        r = _session.get(url, timeout=timeout, allow_redirects=True)
        if r.status_code == 200 and "text/html" in r.headers.get("content-type", ""):
            return r
    except requests.RequestException:
        return None
    return None


def wp(args):
    out = subprocess.run(["wp", "--path=" + WP_PATH, "--skip-themes", "--quiet"] + args, capture_output=True, text=True)
    if out.returncode != 0:
        sys.exit("wp-cli failed: " + out.stderr.strip())
    return out.stdout


def load_products(brand_filter=None, limit=0):
    """Hardware products with brand, from wp-cli; music excluded."""
    # Brand terms, then the products of each brand (wp-cli, JSON). Music is excluded by category.
    brand_terms = json.loads(wp(["term", "list", "pa_producator", "--fields=term_id,slug,name", "--format=json"]))
    if brand_filter:
        brand_terms = [t for t in brand_terms if t["slug"] == brand_filter]
    hardware_cat_ids = set()
    for c in json.loads(wp(["term", "list", "product_cat", "--fields=term_id,slug,parent", "--format=json"])):
        if c["slug"] in HARDWARE_CATS or any(c["slug"].startswith(h) for h in ("boxe", "amplificatoare", "surse", "cabluri")):
            hardware_cat_ids.add(int(c["term_id"]))
    products = []
    for bt in brand_terms:
        raw = wp(["post", "list", "--post_type=product", "--post_status=publish", "--posts_per_page=-1",
                  "--fields=ID,post_title", "--format=json", "--pa_producator=" + bt["slug"]])
        for p in json.loads(raw):
            pid = int(p["ID"])
            cats = json.loads(wp(["post", "term", "list", str(pid), "product_cat", "--fields=term_id,slug", "--format=json"]))
            if not any(int(c["term_id"]) in hardware_cat_ids for c in cats):
                continue
            products.append({"id": pid, "name": p["post_title"], "brand": bt["slug"], "brand_name": bt["name"], "cats": [c["slug"] for c in cats]})
            if limit and len(products) >= limit:
                break
        if limit and len(products) >= limit:
            break
    if limit:
        products = products[:limit]
    return products


# Model tokens: "A-S2200", "R-N1000A", "BR09", "D-10X", "AIO 3", "Oberon 1", "L-509Z", "RX-V4A", "CD-S303"
MODEL_RE = re.compile(r"(?<![\w-])((?:[A-Z]{1,4}-)?[A-Z]{1,6}-?\d{1,5}[A-Z0-9\-\.]{0,8}|\d{2,5}[A-Z]{1,4}|[A-Z][a-z]+\s\d{1,4}[A-Za-z]?)(?![\w-])")
STOPWORDS = {"Boxe", "Amplificator", "Receiver", "Cablu", "Pachet", "Subwoofer", "CD", "Player", "Pickup", "Preamplificator", "Streamer", "DAC", "Negru", "Alb", "Argintiu", "Resigilat", "Resigilate", "Set", "Pereche"}


def model_query(product):
    """Brand + the most model-number-ish token(s) from the name."""
    name = re.sub(r"\(.*?\)|\[.*?\]", " ", product["name"])
    name = re.sub(r"\b(?:" + "|".join(STOPWORDS) + r")\b", " ", name, flags=re.I)
    name = re.sub(r"\s+", " ", name).strip()
    brand = product["brand_name"]
    name = re.sub(re.escape(brand), " ", name, flags=re.I)  # brand is added back below
    tokens = [m.group(0).strip() for m in MODEL_RE.finditer(name)]
    core = " ".join(dict.fromkeys(tokens[:3])) if tokens else re.sub(r"\s+", " ", name).strip()
    return f"{brand} {core}".strip()


def search_brand_site(product, cfg):
    q = model_query(product)
    urls = []
    if cfg.get("search"):
        r = polite_get(cfg["search"].format(q=quote_plus(q)))
        if r:
            soup = BeautifulSoup(r.text, "html.parser")
            for a in soup.select("a[href]"):
                href = urljoin(r.url, a["href"])
                if urlparse(href).netloc.endswith(cfg["site"]) and len(urlparse(href).path) > 8:
                    urls.append(href)
    if not urls:
        # DuckDuckGo HTML endpoint, site-restricted
        r = polite_get("https://html.duckduckgo.com/html/?q=" + quote_plus(f"site:{cfg['site']} {q}"))
        if r:
            soup = BeautifulSoup(r.text, "html.parser")
            for a in soup.select("a.result__a[href]"):
                href = a["href"]
                m = re.search(r"uddg=([^&]+)", href)
                if m:
                    from urllib.parse import unquote
                    href = unquote(m.group(1))
                if cfg["site"] in urlparse(href).netloc:
                    urls.append(href)
    # de-dupe, keep order, prefer URLs containing a model token
    seen, ordered = set(), []
    tokens = [t.lower().replace(" ", "") for t in q.split()[1:]]
    for u in urls:
        if u in seen:
            continue
        seen.add(u)
        score = sum(1 for t in tokens if t and t in u.lower().replace("-", "").replace("_", ""))
        ordered.append((score, u))
    ordered.sort(key=lambda x: -x[0])
    return [u for s, u in ordered[:3]]


def extract_awards(html, url):
    text = BeautifulSoup(html, "html.parser").get_text(" ", strip=True)
    text = re.sub(r"\s+", " ", text)
    found = {}
    for label, pattern, kind in AWARD_PATTERNS:
        for m in re.finditer(pattern, text, flags=re.I):
            year = next((g for g in m.groups() if g and re.fullmatch(r"20\d\d", g)), "")
            stars = "5" if any(g and g.lower() in ("5", "five") for g in m.groups()) else ""
            key = (label, year)
            ctx = text[max(0, m.start() - 60): m.end() + 60]
            if key not in found:
                found[key] = {"award": label, "year": year, "stars": stars, "kind": kind, "source": url, "context": ctx}
    return list(found.values())


def confirm_model_on_page(html, product):
    """The page must mention the model token, or the award belongs to something else."""
    q = model_query(product).split()[1:]
    page = html.lower().replace("-", "").replace(" ", "")
    return any(t.lower().replace("-", "") in page for t in q if len(t) >= 3)


def run_report(args):
    with open(os.path.join(HERE, "brands.json"), encoding="utf-8") as f:
        brands = {k: v for k, v in json.load(f).items() if not k.startswith("_")}
    products = load_products(args.brand, args.limit)
    print(f"{len(products)} hardware products to check", file=sys.stderr)
    rows = []
    with open(args.out, "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=["product_id", "product", "brand", "query", "award", "year", "stars", "kind", "source", "context", "accept"])
        w.writeheader()
        for i, p in enumerate(products, 1):
            cfg = brands.get(p["brand"])
            if not cfg:
                continue
            q = model_query(p)
            print(f"[{i}/{len(products)}] {p['name']}  →  {q}", file=sys.stderr)
            awards = []
            for url in search_brand_site(p, cfg):
                r = polite_get(url)
                if not r or not confirm_model_on_page(r.text, p):
                    continue
                awards = extract_awards(r.text, url)
                if awards:
                    break
            for a in awards:
                row = {"product_id": p["id"], "product": p["name"], "brand": p["brand_name"], "query": q, **a, "accept": "yes"}
                rows.append(row)
                w.writerow(row)
                f.flush()
            if not awards:
                w.writerow({"product_id": p["id"], "product": p["name"], "brand": p["brand_name"], "query": q, "award": "", "year": "", "stars": "", "kind": "", "source": "", "context": "", "accept": ""})
                f.flush()
    hits = len({r["product_id"] for r in rows})
    print(f"done: {hits} products with awards, {len(rows)} award rows → {args.out}", file=sys.stderr)


def run_apply(args):
    """Write accepted rows (accept=yes) to products as _hx_awards (JSON list)."""
    by_product = {}
    with open(args.apply, newline="", encoding="utf-8") as f:
        for row in csv.DictReader(f):
            if row.get("accept", "").strip().lower() != "yes" or not row.get("award"):
                continue
            by_product.setdefault(int(row["product_id"]), []).append({
                "award": row["award"].strip(), "year": row["year"].strip(), "stars": row["stars"].strip(), "source": row["source"].strip(),
            })
    for pid, awards in by_product.items():
        # de-dupe by award+year
        uniq = list({(a["award"], a["year"]): a for a in awards}.values())
        wp(["post", "meta", "update", str(pid), "_hx_awards", json.dumps(uniq, ensure_ascii=False)])
        print(f"#{pid}: {len(uniq)} award(s)", file=sys.stderr)
    print(f"applied to {len(by_product)} products", file=sys.stderr)


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", help="CSV report path (report mode)")
    ap.add_argument("--apply", help="CSV report to apply (write _hx_awards meta)")
    ap.add_argument("--brand", help="only this brand slug")
    ap.add_argument("--limit", type=int, default=0, help="max products (report mode)")
    args = ap.parse_args()
    if args.apply:
        run_apply(args)
    elif args.out:
        run_report(args)
    else:
        ap.print_help()
