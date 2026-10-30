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

## Code map (unchanged from HiFi Expert — full notes in `hifiexpert-theme/docs/HANDOVER.md`)
- `functions.php` requires `inc/*`. `inc/setup.php` (supports, menus, template routing by slug), `inc/customizer.php`
  (**all settings**: `hifiexpert_option_defaults()` code defaults + Customizer sections), `inc/helpers.php`,
  `inc/menu-walker.php` (mega menu; `nav_extra` slug list), `inc/template-tags.php` (product card, price block, search),
  `inc/woocommerce.php` (catalog ordering, single product, music inquiry flow), `inc/filters.php`, `inc/awards.php`,
  `inc/legal.php` + `legal/*.php` (six legal pages, HiFi Expert company data), `inc/seo.php` (Rank Math filters, og:image,
  JSON-LD Store), `inc/brand-admin.php`.
- Templates: `header.php`, `footer.php`, `front-page.php`, `page-brands.php`, `page-promotii.php`, `page-contact.php`,
  `page-legal.php`, `woocommerce/*`. One CSS (`assets/css/main.css`, tokens `--hx-*`, light theme under
  `html[data-theme="light"]`), one JS (`assets/js/main.js`). Campaign mode (Black Edition 30 Oct → 2 Dec 2026 is the code default).

## Redesign scope — decide with the owner, then do on staging
1. **Branding**: keep dark + gold (`--hx-accent: #c9a96a`) or a Yamaha palette; logo; Instrument Sans or other.
   Rename the Customizer panel ("HiFi Expert" → "Yamaha Audio"); keep function prefix `hifiexpert_` and text domain
   `hifiexpert` (renaming them is churn with no user-visible benefit).
2. **Single-brand shop**: no Branduri page / brand rail; `nav_extra` without `branduri`; hero + "High-End" band copy
   for Yamaha; menu by product type (amplificatoare, receivere AV, boxe, streaming / MusicCast, pick-up, căști…).
3. **Company data**: footer, contact page, legal pages. Confirm the legal entity for the Yamaha shop (hifiexpert uses
   HIGH-END EXPERT S.R.L., CUI RO31095680) **before** running "Pagini legale → Instalează".
4. **SEO**: yamaha-audio.ro uses **Yoast**, not Rank Math — `inc/seo.php` filters are no-ops here. Either port the title/
   description logic to Yoast filters (`wpseo_title`, `wpseo_metadesc`, `wpseo_opengraph_image`) or let Yoast handle it.
   The theme's JSON-LD Store schema needs the Yamaha shop data.
5. **404**: the `all-404-redirect-to-homepage` plugin redirects every 404 to home; the theme has `404.php`. Recommend
   deactivating the plugin on go-live (and the `ErrorDocument` line in `.htaccess` goes with it).
6. **Catalog**: ordering (in stock → priced → bestselling → newest) carries over; the music category / inquiry flow
   (`hifiexpert_is_music()`) is not relevant — leave dormant or remove.
7. **Campaign mode**: same Black Edition window (code default) or Yamaha-specific name/dates in the Customizer.
8. **Plugins to look at on live** (DB-side, at deploy): Code Snippets contents, FiboSearch (free version here) index,
   YITH wishlist label, Extra Product Options, LiteSpeed Cache (no page cache on Apache — consider WP Super Cache).
9. **Awards** (`tools/awards/`): Yamaha products do carry awards (EISA etc.) — dry run on staging before enabling.

## Open items outside the theme
- Delete the old Bitnami instance after ~1 week (snapshot first); remove the push key `yamaha-migration` lines from
  `/home/admin/.ssh/authorized_keys` on live when the old server is gone.
- Confirm in Lightsail that 3.76.240.143 is a *static* IP attached to `yamaha-audio-live`.
- Stock app: full stock push to yamaha-audio.ro after the move (stock changes 13:20–14:35 on 2026-10-30 may be missing).
- hifiexpert staging (`staging.hifiexpert.ro`) to be deleted ~a week after 2026-10-30 (instance + DB + DNS + deploy key).
