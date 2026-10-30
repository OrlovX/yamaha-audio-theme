# Yamaha Audio theme — handover notes

Written 2026-10-30 at the end of the "yamaha-audio.ro project" conversation (servers + migration + repo). Facts only;
no secrets. Infrastructure is in `SERVER.md`, the live deploy runbook in `DEPLOY.md`, the migration log in `CUTOVER.md`.

## Where things stand
- **Done today:** new Lightsail live server, site moved onto it (yamaha-audio.ro is live there since ~14:40),
  staging `staging.yamaha-audio.ro` built from a copy of live and locked down, this repo created as a copy of
  `OrlovX/hifiexpert-theme` (commit `c9afdba`) and wired to staging (`git pull` every minute).
- **Not done:** the redesign itself. The theme is still HiFi Expert in everything but the name (`style.css`). The
  active theme on both live and staging is still `yamaha-v2` (Flatsome child); activate **Yamaha Audio** on staging to start.

## Workflow
Edit → commit to `main` → within ~60 s it is on staging (`/var/www/html/wp-content/themes/yamaha-audio`). Live pulls
by hand per `DEPLOY.md`. Commit convention: descriptive message, then the attribution trailer lines (see history).
wp-cli on either server: `cd /var/www/html && sudo -u www-data wp …`.

## Safety (staging is the *master* copy of the Yamaha Sync)
The staging DB came from live with Yamaha Sync, the product importer, feed, ShortPixel, GTM/Clarity and WP Mail SMTP
**deactivated**, the two "Stoc" webhooks **disabled**, noindex on, no cron. Leave it that way. Test orders on staging are
fine (COD) — nothing leaves the box. Never migrate the staging DB to live. Never paste passwords into the chat.

## Code map
- `functions.php` requires `inc/*` (WooCommerce files only when WooCommerce is active). Prefix `yamaha_audio_`,
  text domain `yamaha-audio`, constants `YAMAHA_AUDIO_*`; CSS classes and internal keys keep the short `hx-`/`hx_`
  namespace. No brand taxonomy anywhere: where a multi-brand shop shows the brand, this theme shows the product's
  main category (`yamaha_audio_product_category()`, Yoast primary category when set).
- `inc/setup.php` (supports, menus, template routing by slug: `promotii|promotii-2|oferte → page-promotii.php`,
  `contact → page-contact.php`; Romanian WooCommerce page titles), `inc/customizer.php` (**all settings**:
  `yamaha_audio_option_defaults()` + Customizer panel "Yamaha Audio"), `inc/helpers.php`, `inc/menu-walker.php`
  (`nav_extra` slug list), `inc/template-tags.php` (product card with category kicker, price block, search),
  `inc/woocommerce.php` (catalog ordering, single product, sticky buy bar), `inc/filters.php` (facets = all product
  attributes, rendered only when they split the result set; price, stock, showroom), `inc/awards.php`,
  `inc/legal.php` + `legal/*.php` (six legal pages from the Customizer company data; no consignment clauses),
  `inc/seo.php` (Yoast + Rank Math hooks, og:image fallback, Store schema from the Customizer address).
- Templates: `header.php`, `footer.php`, `front-page.php` (hero, categories, offers, category rows, bundle, MusicCast
  band, blog), `page-promotii.php`, `page-contact.php`, `page-legal.php`, `woocommerce/*`. One CSS
  (`assets/css/main.css`; tokens `--hx-*`; accent as text `--hx-accent`, accent as surface `--hx-gold` + `--hx-on-accent`;
  light theme under `html[data-theme="light"]`), one JS (`assets/js/main.js`). Campaign mode keeps its own palette
  (Black Friday Sound Edition 30 Oct → 2 Dec 2026, neon style, is the code default).

## Done in the first redesign pass (2026-10-30)
- Palette: exact logo violet #4b1e78 (light theme / surfaces), #5b2a93 surfaces + lavender text on dark; Manrope 800 headings;
  **light theme is the default** (`theme_mode`), dark via the header switch. Logo white on dark, original on light.
- Layout: violet top strip, wordmark logo in the bar (no hanging badge), flush violet tab underline, squared buttons
  (violet primary), tinted hero stage with the product on a white disc, "shop by type" chip strip of sub-categories
  (`yamaha_audio_type_chips()`), light category tiles with the photo on top, solid violet MusicCast band, footer with a
  violet rule. Static preview of the home page: `server/../docs` has no copy; rebuild from `assets/css/main.css` if needed.
- All `hifiexpert` identifiers renamed; brand pages/rails/filters/labels, the music inquiry flow and the consignment
  clauses removed; home rows by product category; facets from WooCommerce attributes; SEO on Yoast hooks.
- Customizer defaults written for a Yamaha dealer (hero, offers, MusicCast band, menu, campaign tagline).

## Still to decide / do with the owner
1. **Company data** (`topbar_email` is a placeholder `office@yamaha-audio.ro`; company line says High End Expert S.R.L.
   — confirm the legal entity and phone/e-mail) **before** running "Pagini legale → Instalează".
2. **Menu + home rows**: set `home_rows` to the real category slugs (`wp term list product_cat`), build the primary menu
   by product type, pick the hero product and a Yamaha-only bundle (prices!) — then enable `bundle_enabled`.
3. Campaign bar (Black Edition) still uses the gold-on-black campaign palette next to the violet header — keep as a
   deliberate "sale" contrast or restyle the `.hx-camp--black` tokens to violet/black.
4. **404**: the `all-404-redirect-to-homepage` plugin redirects every 404 to home; the theme has `404.php`. Recommend
   deactivating the plugin on go-live (and the `ErrorDocument` line in `.htaccess` goes with it).
6. **Catalog**: ordering (in stock → priced → bestselling → newest) carries over.
7. **Campaign mode**: same Black Edition window (code default) or Yamaha-specific name/dates in the Customizer.
8. **Plugins to look at on live** (DB-side, at deploy): Code Snippets contents, FiboSearch (free version here) index,
   YITH wishlist label, Extra Product Options, LiteSpeed Cache (no page cache on Apache — consider WP Super Cache).
9. **Awards**: badges render from product meta `_hx_awards`; Yamaha products carry EISA / What Hi-Fi? awards — fill in by hand or write a small importer (the old scraper was hifiexpert-specific and was removed).

## Open items outside the theme
- Delete the old Bitnami instance after ~1 week (snapshot first); remove the push key `yamaha-migration` lines from
  `/home/admin/.ssh/authorized_keys` on live when the old server is gone.
- Confirm in Lightsail that 3.76.240.143 is a *static* IP attached to `yamaha-audio-live`.
- Stock app: full stock push to yamaha-audio.ro after the move (stock changes 13:20–14:35 on 2026-10-30 may be missing).
- hifiexpert staging (`staging.hifiexpert.ro`) to be deleted ~a week after 2026-10-30 (instance + DB + DNS + deploy key).
