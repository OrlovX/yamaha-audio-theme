# Deploying the theme to yamaha-audio.ro (live)

Only the theme files go live. The live database stays as it is; the few DB-side settings are done by hand (step 5).
Server facts in `SERVER.md`. Live paths: `/var/www/html`, wp-cli as `www-data`, theme folder
`/var/www/html/wp-content/themes/yamaha-audio`.

## 0. Before you start
- Lightsail snapshot of the live instance (or `cd /var/www/html && sudo -u www-data wp db export /tmp/pre-theme-$(date +%F).sql`).
- Redesign finished and verified on staging (branding, menu, legal pages with the right company data, SEO titles).
- Sale prices for the campaign set on the products (yamaha-audio.ro is the price master for Yamaha).
- Pick a quiet moment; the switch itself takes seconds, the checks ~15 minutes.

## 1. SSH in, check the basics
```bash
ssh -i ~/Desktop/TECH/KEYS/HiFi-Expert-LS.pem admin@yamaha-audio.ro
cd /var/www/html && sudo -u www-data wp core version && sudo -u www-data wp theme list
```
Current theme is `yamaha-v2` (Flatsome child) — note it for rollback.

## 2. Deploy key (once)
The key already exists on the live server: `sudo cat /var/www/.ssh/id_ed25519.pub`. Add it in GitHub →
`OrlovX/yamaha-audio-theme` → Settings → Deploy keys → Add key (read-only). Then:
```bash
sudo -u www-data ssh -o StrictHostKeyChecking=accept-new -T git@github.com   # "successfully authenticated"
```

## 3. Clone the theme
```bash
sudo -u www-data git clone git@github.com:OrlovX/yamaha-audio-theme.git /var/www/html/wp-content/themes/yamaha-audio
```

## 4. Activate
```bash
cd /var/www/html
sudo -u www-data wp theme activate yamaha-audio
sudo -u www-data wp rewrite flush
sudo -u www-data wp cache flush
sudo -u www-data wp eval 'wp_cache_clear_cache();'
```

## 5. DB-side setup (wp-admin)
1. **Yamaha Audio → Pagini legale → Instalează** — only after the company data in the Customizer is the Yamaha shop's;
   sets the WooCommerce terms + privacy pages.
2. **Aspect → Personalizare**: topbar/contact texts, hero, company data, campaign window.
3. **Aspect → Meniuri**: primary menu = categories (by product type) + Blog / Promoții / Contact (`nav_extra`).
4. **Yoast SEO**: the theme hooks Yoast's title/description/og:image filters; check the home title/description and a
   product page after activation.
5. **all-404-redirect-to-homepage**: decide — the theme has a proper `404.php`; a real 404 is better for SEO than a
   redirect to home. Deactivate the plugin or keep it consciously.
6. **Code Snippets**: review snippets that touch the catalog (ordering, labels) — the theme orders the catalog itself.
7. **WooCommerce → Setări → Plăți**: card (NETOPIA) first, ramburs second.
8. **FiboSearch → Indexer**: rebuild the index. **YITH Wishlist**: button label.
9. Translations: `cd /var/www/html && sudo -u www-data wp language plugin update --all && sudo -u www-data wp language core update && sudo -u www-data wp language theme update --all`
10. *Setări → Citire → "Descurajează motoarele de căutare"* is OFF on live (`wp option get blog_public` → 1).

## 6. Check (desktop + phone)
Home, a category, a product on sale, search overlay, cart → checkout (COD test order, cancel afterwards; confirms
webhooks to the stock app + e-mail), Contul meu, Promoții, Contact, a legal page, light/dark switch.
`sudo -u www-data wp theme list` → `yamaha-audio` active. Stock app → Sites → *Webhooks: ok*.

## 7. Optional, after go-live
- Image sizes for the new theme: `cd /var/www/html && sudo -u www-data nohup wp media regenerate --only-missing --yes > /tmp/regen.log 2>&1 &`
- Flatsome + `yamaha-v2` can be deleted once the new theme has run for a while (`wp theme delete flatsome yamaha-v2`).

## Updating later
No auto-pull on live on purpose (staging pulls every minute; live only when you decide):
```bash
cd /var/www/html/wp-content/themes/yamaha-audio && sudo -u www-data git pull --ff-only origin main && cd /var/www/html && sudo -u www-data wp eval 'wp_cache_clear_cache();'
```

## Rollback (seconds)
```bash
cd /var/www/html && sudo -u www-data wp theme activate yamaha-v2
```
