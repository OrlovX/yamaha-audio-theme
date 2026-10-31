# yamaha-audio.ro — servers (facts, no secrets)

Written 2026-10-30 after the migration. Both servers are Amazon Lightsail, Frankfurt (eu-central-1), created from the
Lightsail **WordPress blueprint** (AWS's own, Debian 12) and then rebuilt with `server/build-yamaha-live.sh`, which
installs the stack and removes the blueprint leftovers. Same layout as hifiexpert.ro live: Apache 2.4 + mod_php 8.2,
WordPress in `/var/www/html`, `wp-config.php` one level up in `/var/www/`, wp-cli as `www-data`, certbot (apache plugin).
Difference from hifiexpert: **MariaDB 10.11 runs on the instance** (no managed DB).

SSH for both: user `admin`, key `HiFi-Expert-LS.pem` (owner's Mac: `~/Desktop/TECH/KEYS/`). wp-cli:
`cd /var/www/html && sudo -u www-data wp …`. MariaDB root: `sudo mariadb` (unix socket). App DB `yamaha_audio`, user
`yamaha_audio`, password only in `/root/.yamaha-db.cnf` (0600) and `/var/www/wp-config.php`.

## Live — `yamaha-audio.ro`
- Instance `yamaha-audio-live`, 16 GB / 4 vCPU / 320 GB (same plan as hifiexpert). Public **3.76.240.143** (static IP —
  Route 53 A records for apex and www point here), private 172.26.3.188.
- Went live 2026-10-30 ~14:40 (see `CUTOVER.md`). Data = the Bitnami site as of the rehearsal dump (~13:20) + files
  re-synced 14:25; owner confirmed no changes in between. Stock app pushes stock, so stock self-heals.
- Certificate: Let's Encrypt via certbot (`certbot certificates`), auto-renew (systemd timer). The Bitnami cert that
  was copied over for the switch is superseded.
- wp-cron: `/etc/cron.d/wordpress`, every 5 min as www-data (`DISABLE_WP_CRON` true in wp-config.php).
- php.ini (apache2): 2000M / 2000M / 128M / 30 s as on hifiexpert; wp-config adds `WP_MEMORY_LIMIT 256M`,
  `WP_MAX_MEMORY_LIMIT 512M` (the Bitnami server ran with 512M).
- Apache extras vs. default: `conf-available/wordpress.conf` (AllowOverride All, xmlrpc.php denied — as on Bitnami),
  `000-default.conf` 80 → `https://yamaha-audio.ro` (ACME path excluded), `default-ssl.conf` www → apex.
- `.htaccess`: WordPress block + `ErrorDocument 404 /index.php?error=404` (all-404-redirect plugin).
- LiteSpeed Cache plugin is active but this is Apache: no page cache in effect (same as on Bitnami). Candidate for
  WP Super Cache (hifiexpert) later. After a copy, purge: `wp litespeed-purge all` (fonts/CSS live in wp-content/litespeed).
- Theme deploy key (public, for `OrlovX/yamaha-audio-theme`, read-only): `/var/www/.ssh/id_ed25519.pub` =
  `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMLbUw/+kv2q1xJ9LRTgrq04KoOA0+vkDuDeqr0QSXVt yamaha-audio.ro`
  (comment may read `yamaha-audio-live`). Theme path when deployed: `/var/www/html/wp-content/themes/yamaha-audio`.
- Push key live → staging: `/root/.ssh/id_migr` (its pubkey is in staging's `admin` authorized_keys).

## Staging — `staging.yamaha-audio.ro`
- Instance `yamaha-audio-staging`, 4 GB plan. Public **3.69.127.68** (static IP named "Staging"), private 172.26.8.245.
- Copy of live taken 2026-10-30 ~15:15, URLs rewritten to the staging host (`SOURCE_DOMAIN` search-replace), own salts.
- **Locked down** (`yamaha-migrate.sh lockdown`) — this copy is the *master* of the Yamaha Sync, so: `yahfx-sync`,
  `yamaha-product-importer`, product feed, ShortPixel, GTM, Clarity, WP Mail SMTP **deactivated**; both "Stoc"
  webhooks **disabled**; `blog_public 0`; `DISABLE_WP_CRON` true and **no system cron for WP** (`/etc/cron.d/wordpress.disabled`);
  no mail transport. Never re-enable these on staging; never copy the staging DB anywhere.
- Theme: cloned from this repo into `wp-content/themes/yamaha-audio`, `git pull` every minute as www-data
  (`/etc/cron.d/yamaha-theme`), read-only deploy key `/var/www/.ssh/id_ed25519.pub` on the instance.
- Refresh from live when needed: dump + rsync from live (see `CUTOVER.md` block C for the commands, target 3.69.127.68),
  then on staging `DOMAIN=staging.yamaha-audio.ro SOURCE_DOMAIN=yamaha-audio.ro yamaha-migrate.sh import <dump>`
  followed **immediately** by `lockdown`.

## Old server (Bitnami) — to delete
- Lightsail WordPress (Bitnami) instance, private 172.26.12.134, **stopped** 2026-10-30. Keep ~1 week as fallback
  (Lightsail bills stopped instances), then snapshot + delete. Its DB has `siteurl/home = https://127.0.0.1`
  (Bitnami's dynamic WP_HOME) — irrelevant now.

## Scripts (`server/`)
- `build-yamaha-live.sh` — stack on a fresh Debian 12 Lightsail instance (OS-only or WordPress blueprint).
  `sudo DOMAIN=<host> bash build-yamaha-live.sh`.
- `yamaha-migrate.sh` — `prepare` / `import <dump>` / `lockdown` / `cert` / `certbot` / `status` / `golive`;
  `DOMAIN` and `SOURCE_DOMAIN` as environment variables. Run with `sudo -E`.

## Integrations (unchanged by the move — all by hostname over HTTPS)
- Stock app `https://stoc.hifiexpert.ro` → REST pushes stock; receives WooCommerce webhooks
  `order.created` / `order.updated` at `/webhooks/woo/yamaha`. Check its Sites screen: *Webhooks: ok*.
- Yamaha Sync (`yahfx-sync`): yamaha-audio.ro is master, pushes SKU + prices to hifiexpert.ro.
- DNS: Route 53. Payments: NETOPIA. Mail: WP Mail SMTP. SEO: Yoast (hifiexpert uses Rank Math).

## Loopback and headers (added after the SEO audit, 2026-10-31)

- The site calls itself over HTTPS (LiteSpeed purge from WP-CLI, REST/Site Health loopbacks). The VPC resolver can
  hold a stale answer for `yamaha-audio.ro` for the record's TTL after an IP swap, so each server pins its own name
  in `/etc/hosts`: `127.0.0.1 yamaha-audio.ro www.yamaha-audio.ro` on live (`127.0.0.1 staging.yamaha-audio.ro` on
  staging). Check with `getent hosts yamaha-audio.ro`.
- Browser caching + security headers: `server/apache-yamaha.conf` → `/etc/apache2/conf-available/yamaha.conf`,
  `a2enconf yamaha`. Theme assets are versioned by file time, so the one-year `immutable` is safe.
- Read-only audit: `cd /var/www/html && bash wp-content/themes/yamaha-audio/server/seo-check.sh`.

## Performance stack (2026-10-31)

- PHP-FPM 8.2 + `mpm_event` + HTTP/2 (`server/perf-upgrade.sh`, run as root). Pool: dynamic, 40 children; opcache 256 MB.
  Wordfence's `wordfence-waf.php` is prepended from the FPM pool (`php_admin_value[auto_prepend_file]`).
  Rollback: `a2dismod mpm_event proxy_fcgi http2; a2disconf php8.2-fpm http2; a2enmod mpm_prefork php8.2; systemctl restart apache2`.
- Page cache: WP Super Cache in simple mode (`server/page-cache-setup.sh` + `server/wpsc-configure.php`). LiteSpeed Cache
  is deactivated (it cannot cache pages on Apache). Not cached: logged-in users, visitors with a cart/session/wishlist
  cookie, any URL with a query string (filters, search, payment return), cart/checkout/account pages. Expiry 30 min,
  preload every 60 min; the theme purges product + home + category + Promoții pages on stock/product changes.
  Clear everything: `sudo -u www-data wp eval 'wp_cache_clear_cache();'` or Settings → WP Super Cache → Delete Cache.
