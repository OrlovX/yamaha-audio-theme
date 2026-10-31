#!/usr/bin/env bash
# Read-only SEO / good-practice check for the live shop. Changes nothing.
# Run on the server from the theme folder:  bash server/seo-check.sh [domain]
set -u
D="${1:-${DOMAIN:-yamaha-audio.ro}}"
U="https://$D"
W="${WP_PATH:-/var/www/html}"
# wp-config.php is readable by www-data only, so WP-CLI must run as www-data (DEPLOY.md).
if [ "$(id -un)" = "www-data" ]; then WP="wp --path=$W"; else WP="sudo -u www-data /usr/local/bin/wp --path=$W"; fi
hr()   { printf '\n== %s\n' "$1"; }
code() { curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}' --max-time 20 "$1"; echo; }

hr "Redirects (want: http→https and www→apex as one 301, 404 for junk URLs)"
printf '%-34s' "http://$D/";            code "http://$D/"
printf '%-34s' "http://www.$D/";        code "http://www.$D/"
printf '%-34s' "https://www.$D/";       code "https://www.$D/"
printf '%-34s' "$U/index.php";          code "$U/index.php"
printf '%-34s' "$U/?p=1";               code "$U/?p=1"
printf '%-34s' "$U/pagina-inexistenta/"; code "$U/pagina-inexistenta-404/"
printf '%-34s' "$U/categorie/receivere"; code "$U/categorie/receivere"

hr "Response headers (home)"
curl -sI --http2 -H 'Accept-Encoding: gzip, br' --max-time 20 "$U/" \
  | grep -iE '^(HTTP|server|content-encoding|cache-control|strict-transport|x-content-type|x-frame|referrer-policy|content-security|permissions-policy|x-litespeed|x-cache|vary|link|age):'

hr "Static asset caching"
curl -s --max-time 20 "$U/" > /tmp/home.html
CSS=$(grep -o 'https://[^"]*themes/[^"]*main[^"]*\.css[^"]*' /tmp/home.html | head -1)
echo "${CSS:-no theme css url found}"
[ -n "$CSS" ] && curl -sI -H 'Accept-Encoding: gzip, br' "$CSS" | grep -iE '^(HTTP|cache-control|expires|content-encoding|etag|last-modified):'
IMG=$(grep -o 'https://[^" ]*uploads/[^" ]*\.\(jpg\|jpeg\|png\|webp\)' /tmp/home.html | head -1)
echo "${IMG:-no upload image found}"
[ -n "$IMG" ] && curl -sI "$IMG" | grep -iE '^(HTTP|cache-control|expires|content-type|content-length):'

hr "Timing (TTFB / total) — 3× home, then a category and a product"
for i in 1 2 3; do curl -s -o /dev/null -w 'home:     ttfb %{time_starttransfer}s  total %{time_total}s  %{size_download} B\n' --max-time 30 "$U/"; done
curl -s -o /dev/null -w 'category: ttfb %{time_starttransfer}s  total %{time_total}s  %{size_download} B\n' --max-time 30 "$U/categorie/receivere/"
curl -s -o /dev/null -w 'product:  ttfb %{time_starttransfer}s  total %{time_total}s  %{size_download} B\n' --max-time 30 "$U/produs/receiver-stereo-yamaha-r-n600a/"

hr "robots.txt"
curl -s --max-time 20 "$U/robots.txt" | head -30

hr "Sitemaps"
curl -s -o /tmp/sm.xml -w 'sitemap_index.xml -> %{http_code} %{content_type}\n' --max-time 20 "$U/sitemap_index.xml"
grep -o '<loc>[^<]*' /tmp/sm.xml | sed 's/<loc>/  /' | head -20
curl -s -o /dev/null -w 'wp-sitemap.xml (core; should be 404/redirect when Yoast is on) -> %{http_code}\n' --max-time 20 "$U/wp-sitemap.xml"

hr "Head tags + structured data"
for P in "/" "/categorie/receivere/" "/categorie/amplificare/" "/produs/receiver-stereo-yamaha-r-n600a/" "/promotii/" "/contact/"; do
  curl -s --max-time 30 "$U$P" > /tmp/page.html
  python3 - "$U$P" <<'PY'
import re, sys, json, html
u = sys.argv[1]; s = open('/tmp/page.html', encoding='utf-8', errors='replace').read()
g = lambda rx: re.findall(rx, s, re.I | re.S)
print(f"\n--- {u}  ({len(s)//1024} KB html)")
print(" html lang:", g(r'<html[^>]*\blang="([^"]*)"')[:1])
print(" title:", [html.unescape(t.strip()) for t in g(r'<title>(.*?)</title>')][:1])
for n in ('description', 'robots', 'generator', 'viewport'):
    v = g(r'<meta[^>]+name="%s"[^>]+content="([^"]*)"' % n) or g(r'<meta[^>]+content="([^"]*)"[^>]+name="%s"' % n)
    print(f" meta {n}:", [html.unescape(x)[:160] for x in v])
print(" canonical:", g(r'<link[^>]+rel="canonical"[^>]+href="([^"]*)"'))
for k, v in g(r'<meta[^>]+property="(og:[a-z:_]+|product:[a-z:_]+)"[^>]+content="([^"]*)"'):
    print(f" {k}: {html.unescape(v)[:110]}")
for k, v in g(r'<meta[^>]+name="(twitter:[a-z:_]+)"[^>]+content="([^"]*)"'):
    print(f" {k}: {html.unescape(v)[:110]}")
print(" h1:", [re.sub('<[^>]+>', '', h).strip() for h in g(r'<h1[^>]*>(.*?)</h1>')])
imgs = g(r'<img\b[^>]*>')
noalt = [i for i in imgs if not re.search(r'\balt=', i)]
emptyalt = [i for i in imgs if re.search(r'\balt=""', i)]
lazy = [i for i in imgs if 'loading="lazy"' in i]
nodim = [i for i in imgs if not (re.search(r'\bwidth=', i) and re.search(r'\bheight=', i))]
print(f" img: {len(imgs)} total | {len(noalt)} no alt attr | {len(emptyalt)} empty alt | {len(lazy)} lazy | {len(nodim)} without width/height")
print(f" scripts: {len(g(r'<script[^>]+src='))} external, {len(g(r'<script(?![^>]*src=)'))} inline | stylesheets: {len(g(r'<link[^>]+rel=.stylesheet'))} | preload/preconnect: {len(g(r'<link[^>]+rel=.(?:preload|preconnect)'))}")
for ld in g(r'<script[^>]+application/ld\+json[^>]*>(.*?)</script>'):
    try:
        d = json.loads(ld)
        nodes = d.get('@graph', [d]) if isinstance(d, dict) else d
        for n in nodes:
            if not isinstance(n, dict):
                continue
            t = n.get('@type'); extra = ''
            if t == 'Product' or (isinstance(t, list) and 'Product' in t):
                o = n.get('offers') or {}
                o = o[0] if isinstance(o, list) and o else o
                b = n.get('brand'); b = b.get('name') if isinstance(b, dict) else b
                extra = f" price={o.get('price')} {o.get('priceCurrency')} avail={str(o.get('availability', ''))[-12:]} brand={b} sku={n.get('sku')} image={'yes' if n.get('image') else 'NO'} rating={'yes' if n.get('aggregateRating') else 'no'}"
            print(" ld+json:", t, (str(n.get('name') or ''))[:50], extra)
    except Exception as e:
        print(" ld+json: PARSE ERROR", e)
host = u.split('/')[2]
print(" leftovers: hifiexpert", len(re.findall('hifiexpert', s, re.I)), "| 127.0.0.1", s.count('127.0.0.1'), "| http://" + host, len(re.findall(r'http://' + re.escape(host), s)))
PY
done

hr "WordPress settings"
echo "blog_public:        $($WP option get blog_public)"
echo "permalinks:         $($WP option get permalink_structure)"
echo "siteurl / home:     $($WP option get siteurl) / $($WP option get home)"
echo "timezone:           $($WP option get timezone_string)"
echo "wc permalinks:      $($WP option get woocommerce_permalinks --format=json)"
$WP eval 'echo "versions:           WP ", get_bloginfo("version"), " | PHP ", PHP_VERSION, " | WC ", defined("WC_VERSION") ? WC_VERSION : "-", " | Yoast ", defined("WPSEO_VERSION") ? WPSEO_VERSION : "-", " | WP_CACHE ", ( defined("WP_CACHE") && WP_CACHE ) ? "on" : "off", "\n";'
echo "active plugins:"; $WP plugin list --status=active --fields=name,version | sed 's/^/  /'

hr "Yoast settings that matter"
$WP eval '$t = get_option("wpseo_titles"); foreach ( array("noindex-tax-product_tag","noindex-author-wpseo","disable-author","disable-date","disable-attachment","disable-post_format","breadcrumbs-enable","company_or_person","company_name") as $k ) { echo str_pad($k, 26), var_export($t[$k] ?? null, true), "\n"; } $s = get_option("wpseo_social"); echo str_pad("og_default_image", 26), var_export($s["og_default_image"] ?? null, true), "\n"; $w = get_option("wpseo"); echo str_pad("enable_xml_sitemap", 26), var_export($w["enable_xml_sitemap"] ?? null, true), "\n", str_pad("indexing_completed", 26), var_export($w["indexables_indexing_completed"] ?? null, true), "\n";'

hr "Catalogue health"
$WP eval '
$ids = get_posts(array("post_type" => "product", "post_status" => "publish", "posts_per_page" => -1, "fields" => "ids"));
$n = count($ids); $noimg = $noshort = $nodesc = $nometa = $nosku = $outstock = $noalt = 0; $imgids = array();
foreach ( $ids as $id ) { $p = wc_get_product($id); if ( ! $p ) continue;
  if ( ! $p->get_image_id() ) $noimg++; else $imgids[] = $p->get_image_id();
  if ( mb_strlen( wp_strip_all_tags( $p->get_short_description() ) ) < 60 ) $noshort++;
  if ( mb_strlen( wp_strip_all_tags( $p->get_description() ) ) < 200 ) $nodesc++;
  if ( ! get_post_meta( $id, "_yoast_wpseo_metadesc", true ) ) $nometa++;
  if ( ! $p->get_sku() ) $nosku++;
  if ( ! $p->is_in_stock() ) $outstock++;
}
$imgids = array_unique($imgids); foreach ( $imgids as $i ) { if ( ! get_post_meta( $i, "_wp_attachment_image_alt", true ) ) $noalt++; }
printf("products published: %d\n  no featured image: %d\n  short description under 60 chars: %d\n  description under 200 chars: %d\n  no Yoast meta description (theme fallback): %d\n  no SKU: %d\n  out of stock: %d\n  featured images without alt text: %d of %d\n", $n, $noimg, $noshort, $nodesc, $nometa, $nosku, $outstock, $noalt, count($imgids));
$terms = get_terms(array("taxonomy" => "product_cat", "hide_empty" => false)); $nd = $nt = 0;
foreach ( $terms as $t ) { if ( ! $t->description ) $nd++; if ( ! get_term_meta( $t->term_id, "thumbnail_id", true ) ) $nt++; }
printf("categories: %d | without description: %d | without thumbnail: %d\n", count($terms), $nd, $nt);
echo "approved reviews: ", get_comments(array("type" => "review", "status" => "approve", "count" => true)), "\n";
$c = wp_count_posts("product"); echo "product drafts/private/pending: {$c->draft}/{$c->private}/{$c->pending}\n";
$x = array(); foreach ( get_pages(array("post_status" => "publish")) as $pg ) { if ( ! get_post_meta( $pg->ID, "_yoast_wpseo_metadesc", true ) ) $x[] = $pg->post_name; }
echo "pages without Yoast meta description: ", implode(", ", $x), "\n";
'

hr "Category names and attributes (check diacritics / internal attributes)"
$WP eval 'foreach ( get_terms(array("taxonomy" => "product_cat", "hide_empty" => false, "orderby" => "name")) as $t ) { printf("  cat %-4d %-45s /%s/  (%d)\n", $t->term_id, $t->name, $t->slug, $t->count); } foreach ( wc_get_attribute_taxonomies() as $a ) { printf("  attr %-24s label=%s\n", $a->attribute_name, $a->attribute_label); }'

hr "Apache modules & cache/security config"
( sudo -n apache2ctl -M 2>/dev/null || apache2ctl -M 2>/dev/null ) | grep -Eo '(http2|deflate|brotli|expires|headers|rewrite|ssl)_module' | sort | tr '\n' ' '; echo
grep -rIl 'ExpiresActive\|Cache-Control\|Strict-Transport' /etc/apache2/sites-enabled/ /etc/apache2/conf-enabled/ /var/www/html/.htaccess 2>/dev/null | sed 's/^/  /'
grep -h 'Protocols\|ExpiresDefault\|Header always set' /etc/apache2/sites-enabled/*.conf /etc/apache2/conf-enabled/*.conf 2>/dev/null | sed 's/^/  /' | head -20

hr "Done"
