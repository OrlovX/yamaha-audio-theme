#!/usr/bin/env bash
# yamaha-migrate.sh — migration helper, runs on the NEW yamaha-audio.ro server (Debian 12, built by build-yamaha-live.sh).
# Source: the Lightsail Bitnami WordPress instance (WP root /opt/bitnami/wordpress, wp-content 4.5 GB, DB 551 MB,
# MariaDB 11.2 -> here MariaDB 10.11, siteurl/home stored as https://127.0.0.1).
#
# Usage:  sudo [DOMAIN=staging.yamaha-audio.ro SOURCE_DOMAIN=yamaha-audio.ro] bash yamaha-migrate.sh <step> [args]
#   DOMAIN        hostname this server serves (default yamaha-audio.ro); SOURCE_DOMAIN: hostname in the dump when it
#                 differs (staging: SOURCE_DOMAIN=yamaha-audio.ro -> every URL is rewritten to DOMAIN on import).
#   prepare            WP core 7.1.4 ro_RO into /var/www/html (no wp-content), /var/www/wp-config.php from the source
#                      config (salts kept, this server's DB credentials), Apache: www->apex redirect + xmlrpc.php
#                      blocked, wp-cron.d parked. Expects the source wp-config.php at /home/admin/old-wp-config.php.
#   import  <dump>     Import <dump> (.sql or .sql.gz) into yamaha_audio, fix URLs (127.0.0.1 and SOURCE_DOMAIN ->
#                      DOMAIN), ownership/permissions, .htaccess, flush. Re-runnable (dump has DROP TABLE).
#   lockdown           REHEARSAL ONLY: deactivate Yamaha Sync (master!), importer, feed, ShortPixel, analytics, SMTP;
#                      disable all WooCommerce webhooks; noindex. NEVER run this after the real cutover import.
#   cert               Install a Let's Encrypt cert copied from another server (/home/admin/DOMAIN.{crt,key}).
#   certbot            Get a certificate from Let's Encrypt for DOMAIN (+ www.DOMAIN if it resolves). DNS must point here.
#   status             Print what matters (versions, URLs, plugins, webhooks, cron, cert).
#   golive             Live only, after the cutover import: refuse a locked-down copy, enable wp-cron.d, certbot, checks.
#                      Staging never runs golive (no cron, lockdown stays).
#
# Prints no secrets. DB password is read from /root/.yamaha-db.cnf; the old wp-config/key files are removed after use.

set -euo pipefail

DOMAIN="${DOMAIN:-yamaha-audio.ro}"
SOURCE_DOMAIN="${SOURCE_DOMAIN:-}"
W="/var/www/html"
CFG="/var/www/wp-config.php"
WPV="7.1.4"
DB_NAME="yamaha_audio"
DB_USER="yamaha_audio"
CERT_EMAIL="razvan.stefanescu@red-lion.ro"   # same contact as the old server's lego renewal
WP="sudo -u www-data /usr/local/bin/wp --path=${W}"

[ "$(id -u)" -eq 0 ] || { echo "Run as root:  sudo bash $0 $*"; exit 1; }
log() { printf '\n\033[1;33m== %s  [%s]\033[0m\n' "$*" "$(date +%H:%M:%S)"; }
step="${1:-}"

# ---------------------------------------------------------------------------------------------------------------
do_prepare() {
  local OLDCFG="/home/admin/old-wp-config.php"

  log "wp-cron.d parked until go-live (cron ignores files with a dot in the name)"
  [ -f /etc/cron.d/wordpress ] && mv /etc/cron.d/wordpress /etc/cron.d/wordpress.disabled
  ls /etc/cron.d/ | grep wordpress

  log "WordPress ${WPV} core (ro_RO, without wp-content) in ${W}"
  rm -f "${W}/index.html"
  if [ -f "${W}/wp-settings.php" ]; then
    echo "   core already present: $(${WP} core version 2>/dev/null || echo '?')"
  else
    ${WP} core download --version="${WPV}" --locale=ro_RO --skip-content
  fi

  log "wp-config.php -> ${CFG} (outside the web root, as on hifiexpert)"
  local DBP SALTS
  DBP="$(awk -F= '/^password=/{print $2}' /root/.yamaha-db.cnf)"
  [ -n "$DBP" ] || { echo "no password in /root/.yamaha-db.cnf"; exit 1; }
  if [ -f "$OLDCFG" ]; then
    SALTS="$(grep -E "^\s*define\(\s*'(AUTH|SECURE_AUTH|LOGGED_IN|NONCE)_(KEY|SALT)'" "$OLDCFG" || true)"
  else
    SALTS=""
  fi
  if [ -z "$SALTS" ]; then
    echo "   old salts not found - generating new ones (all users will be logged out once)"
    SALTS="$(curl -fsS https://api.wordpress.org/secret-key/1.1/salt/)"
  else
    echo "   salts copied from the old wp-config.php (logins and carts survive the move)"
  fi
  cat >"$CFG" <<EOF
<?php
/**
 * yamaha-audio.ro - wp-config.php, generated $(date -I) by yamaha-migrate.sh.
 * Lives in /var/www (parent of the web root), like hifiexpert.ro. WordPress root: ${W}
 */

define( 'DB_NAME',     '${DB_NAME}' );
define( 'DB_USER',     '${DB_USER}' );
define( 'DB_PASSWORD', '${DBP}' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

${SALTS}

\$table_prefix = 'wp_';

define( 'WP_CACHE', true );                 // LiteSpeed Cache plugin (as on the old server)
define( 'FS_METHOD', 'direct' );
define( 'DISABLE_WP_CRON', true );          // /etc/cron.d/wordpress runs wp-cron.php every 5 minutes as www-data
define( 'WP_MEMORY_LIMIT', '256M' );        // php.ini mirrors hifiexpert (128M); the old Bitnami server gave PHP 512M
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/html/' );
}
require_once ABSPATH . 'wp-settings.php';
EOF
  chown www-data:www-data "$CFG"; chmod 640 "$CFG"
  [ -f "$OLDCFG" ] && rm -f "$OLDCFG"
  echo "   written: $(wc -l <"$CFG") lines"

  log "Apache: xmlrpc.php blocked (as on Bitnami), www -> ${DOMAIN}"
  cat >/etc/apache2/conf-available/wordpress.conf <<EOF
<Directory ${W}>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
# As on the old Bitnami vhost: no XML-RPC (no Jetpack on this site)
<Files xmlrpc.php>
    Require all denied
</Files>
EOF
  # port 80: everything -> https://DOMAIN (one hop, also for www)
  sed -i "s|RewriteRule \^ https://%{HTTP_HOST}%{REQUEST_URI} \[L,R=301,NE\]|RewriteRule ^ https://${DOMAIN}%{REQUEST_URI} [L,R=301,NE]|" \
    /etc/apache2/sites-available/000-default.conf
  # port 443: www -> apex (certbot's ACME path excluded), inserted once after DocumentRoot
  if ! grep -q 'www -> apex' /etc/apache2/sites-available/default-ssl.conf; then
    sed -i "/^\s*DocumentRoot ${W//\//\\/}/a\\
\\
    # www -> apex (as on the old Bitnami vhost)\\
    RewriteEngine On\\
    RewriteCond %{HTTP_HOST} ^www\\\\. [NC]\\
    RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/ [NC]\\
    RewriteRule ^ https://${DOMAIN}%{REQUEST_URI} [L,R=301,NE]" /etc/apache2/sites-available/default-ssl.conf
  fi
  apache2ctl configtest && systemctl reload apache2
  grep -nE 'RewriteRule|Files xmlrpc' /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/default-ssl.conf /etc/apache2/conf-available/wordpress.conf
}

# ---------------------------------------------------------------------------------------------------------------
do_import() {
  local F="${1:-}"
  [ -f "$F" ] || { echo "usage: $0 import <dump.sql|dump.sql.gz>"; exit 1; }
  [ -f "$CFG" ] || { echo "run 'prepare' first"; exit 1; }
  [ -d "${W}/wp-content/uploads" ] || echo "   WARNING: ${W}/wp-content/uploads missing - was wp-content rsynced?"

  log "Dump -> /root/import.sql"
  case "$F" in
    *.gz) zcat "$F" >/root/import.sql ;;
    *)    cp "$F" /root/import.sql ;;
  esac
  echo "   $(du -h /root/import.sql | cut -f1), $(grep -c '^CREATE TABLE' /root/import.sql) tables"
  head -1 /root/import.sql | cut -c1-80
  # MariaDB 11 -> 10.11: collations 10.11 does not know
  if grep -q 'uca1400' /root/import.sql; then
    sed -i 's/utf8mb4_uca1400[a-zA-Z0-9_]*/utf8mb4_unicode_ci/g' /root/import.sql
    echo "   rewrote utf8mb4_uca1400_* collations to utf8mb4_unicode_ci"
  fi

  log "Import into ${DB_NAME} (tables are dropped and recreated by the dump)"
  mariadb "${DB_NAME}" </root/import.sql
  rm -f /root/import.sql
  echo "   tables now: $(mariadb -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'")"

  log "Ownership and permissions"
  chown -R www-data:www-data "${W}"
  find "${W}" -type d -exec chmod 755 {} +
  find "${W}" -type f -exec chmod 644 {} +

  log ".htaccess (standard WordPress block; old Bitnami file was inactive)"
  cat >"${W}/.htaccess" <<'EOF'
# 404 handler kept by the "all-404-redirect-to-homepage" plugin (as on the old server)
ErrorDocument 404 /index.php?error=404
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF
  chown www-data:www-data "${W}/.htaccess"

  log "URLs: 127.0.0.1 -> ${DOMAIN}"
  ${WP} cache flush >/dev/null
  ${WP} option update home "https://${DOMAIN}" >/dev/null
  ${WP} option update siteurl "https://${DOMAIN}" >/dev/null
  ${WP} search-replace "https://127.0.0.1" "https://${DOMAIN}" --all-tables --report-changed-only
  ${WP} search-replace "http://127.0.0.1" "https://${DOMAIN}" --all-tables --report-changed-only
  if [ -n "$SOURCE_DOMAIN" ] && [ "$SOURCE_DOMAIN" != "$DOMAIN" ]; then
    log "URLs: ${SOURCE_DOMAIN} -> ${DOMAIN} (all tables, serialized data handled)"
    ${WP} search-replace "https://www.${SOURCE_DOMAIN}" "https://${DOMAIN}" --all-tables --report-changed-only
    ${WP} search-replace "https://${SOURCE_DOMAIN}" "https://${DOMAIN}" --all-tables --report-changed-only
    ${WP} search-replace "http://${SOURCE_DOMAIN}" "https://${DOMAIN}" --all-tables --report-changed-only
    ${WP} search-replace "//${SOURCE_DOMAIN}" "//${DOMAIN}" --all-tables --report-changed-only
    ${WP} option update home "https://${DOMAIN}" >/dev/null
    ${WP} option update siteurl "https://${DOMAIN}" >/dev/null
  fi
  ${WP} rewrite flush >/dev/null
  ${WP} cache flush >/dev/null

  log "Result"
  echo "   WP $(${WP} core version)  |  home $(${WP} option get home)  |  siteurl $(${WP} option get siteurl)  |  $(${WP} option get blogname)"
  echo "   products: $(${WP} post list --post_type=product --post_status=publish --format=count)   orders: $(${WP} wc shop_order list --user=1 --format=count 2>/dev/null || echo '?')"
  echo "   theme: $(${WP} theme list --status=active --field=name)"
}

# ---------------------------------------------------------------------------------------------------------------
do_lockdown() {
  log "REHEARSAL LOCKDOWN - this copy must not talk to hifiexpert.ro, the stock app, feeds, mail or analytics"
  ${WP} plugin deactivate yahfx-sync yamaha-product-importer webappick-product-feed-for-woocommerce \
      shortpixel-image-optimiser wp-mail-smtp duracelltomi-google-tag-manager microsoft-clarity 2>&1 | grep -vE '^$' || true
  echo "   webhooks before:"; ${WP} db query "SELECT webhook_id, name, status, delivery_url FROM wp_wc_webhooks"
  ${WP} db query "UPDATE wp_wc_webhooks SET status='disabled'"
  ${WP} option update blog_public 0 >/dev/null
  ${WP} transient delete --all >/dev/null
  ${WP} cache flush >/dev/null
  echo "   webhooks after:"; ${WP} db query "SELECT webhook_id, name, status FROM wp_wc_webhooks"
  echo "   yahfx-sync: $(${WP} plugin get yahfx-sync --field=status)   blog_public: $(${WP} option get blog_public)"
  echo "   NOTE: no mail transport is installed on this server, so nothing can send e-mail here either."
  echo "   This is a rehearsal copy - do not place orders, do not save products."
}

# ---------------------------------------------------------------------------------------------------------------
do_cert() {
  local C="/home/admin/${DOMAIN}.crt" K="/home/admin/${DOMAIN}.key"
  [ -f "$C" ] && [ -f "$K" ] || { echo "expected $C and $K (scp them from the old server first)"; exit 1; }
  log "Installing the Let's Encrypt certificate copied from the old server"
  install -m 644 -o root -g root "$C" "/etc/ssl/certs/${DOMAIN}.crt"
  install -m 600 -o root -g root "$K" "/etc/ssl/private/${DOMAIN}.key"
  rm -f "$C" "$K"
  sed -i -e "s|^\(\s*SSLCertificateFile\).*|\1 /etc/ssl/certs/${DOMAIN}.crt|" \
         -e "s|^\(\s*SSLCertificateKeyFile\).*|\1 /etc/ssl/private/${DOMAIN}.key|" \
         /etc/apache2/sites-available/default-ssl.conf
  apache2ctl configtest && systemctl reload apache2
  echo | openssl s_client -connect 127.0.0.1:443 -servername "${DOMAIN}" 2>/dev/null | openssl x509 -noout -subject -ext subjectAltName -enddate
}

# ---------------------------------------------------------------------------------------------------------------
do_certbot() {
  local MYIP DNSIP WWW_IP DOMS
  MYIP="$(curl -s --max-time 5 https://checkip.amazonaws.com || true)"
  DNSIP="$(getent ahostsv4 "${DOMAIN}" 2>/dev/null | awk 'NR==1{print $1}' || true)"
  WWW_IP="$(getent ahostsv4 "www.${DOMAIN}" 2>/dev/null | awk 'NR==1{print $1}' || true)"
  echo "   this server: ${MYIP}   ${DOMAIN} -> ${DNSIP:-unresolved}   www.${DOMAIN} -> ${WWW_IP:-unresolved}"
  if [ -z "$DNSIP" ] || [ "$DNSIP" != "$MYIP" ]; then
    echo "   ${DOMAIN} does not resolve to this server (yet) - fix DNS / wait for the cache, then rerun 'certbot'."
    echo "   (If public DNS is already right, the local resolver cache lags: FORCE=1 skips this check.)"
    [ "${FORCE:-0}" = "1" ] || return 1
  fi
  DOMS=(-d "${DOMAIN}")
  [ -n "$WWW_IP" ] && [ "$WWW_IP" = "$MYIP" ] && DOMS+=(-d "www.${DOMAIN}")
  log "certbot for ${DOMS[*]} (our own redirect stays: --no-redirect)"
  certbot --apache --non-interactive --agree-tos -m "${CERT_EMAIL}" --no-redirect "${DOMS[@]}" --cert-name "${DOMAIN}"
  systemctl reload apache2
  certbot certificates 2>/dev/null | grep -E "Certificate Name|Domains|Expiry"
}

# ---------------------------------------------------------------------------------------------------------------
do_status() {
  log "Status"
  echo "host:     $(hostname)  public IP $(curl -s --max-time 5 https://checkip.amazonaws.com || echo '?')"
  echo "services: apache2 $(systemctl is-active apache2)  mariadb $(systemctl is-active mariadb)"
  echo "cron.d:   $(ls /etc/cron.d/ | grep wordpress || echo 'none')"
  echo "cert:     $(echo | openssl s_client -connect 127.0.0.1:443 -servername "${DOMAIN}" 2>/dev/null | openssl x509 -noout -subject -enddate | tr '\n' ' ')"
  if [ -f "${W}/wp-settings.php" ] && [ -f "$CFG" ]; then
    echo "WP:       $(${WP} core version)  home $(${WP} option get home)  blog_public $(${WP} option get blog_public)  theme $(${WP} theme list --status=active --field=name)"
    echo "active:   $(${WP} plugin list --status=active --field=name | tr '\n' ' ')"
    echo "inactive: $(${WP} plugin list --status=inactive --field=name | tr '\n' ' ')"
    echo "webhooks:"; ${WP} db query "SELECT webhook_id, name, status, delivery_url FROM wp_wc_webhooks"
    echo "uploads:  $(du -sh ${W}/wp-content/uploads 2>/dev/null | cut -f1)   DB: $(${WP} db size --human-readable 2>/dev/null | tail -2 | head -1)"
  else
    echo "WP:       not installed yet"
  fi
}

# ---------------------------------------------------------------------------------------------------------------
do_golive() {
  log "Go-live checks"
  local MYIP DNSIP
  MYIP="$(curl -s --max-time 5 https://checkip.amazonaws.com || true)"
  DNSIP="$(getent ahostsv4 "${DOMAIN}" | awk 'NR==1{print $1}' || true)"
  echo "   this server: ${MYIP}   DNS ${DOMAIN} -> ${DNSIP}"
  echo "   yahfx-sync: $(${WP} plugin get yahfx-sync --field=status)   blog_public: $(${WP} option get blog_public)"
  ${WP} db query "SELECT webhook_id, name, status FROM wp_wc_webhooks"
  if [ "$(${WP} plugin get yahfx-sync --field=status)" != "active" ]; then
    echo "   STOP: yahfx-sync is inactive - this looks like the rehearsal copy. Re-import the final dump without 'lockdown'."; exit 1
  fi

  log "wp-cron.d on"
  [ -f /etc/cron.d/wordpress.disabled ] && mv /etc/cron.d/wordpress.disabled /etc/cron.d/wordpress
  ls /etc/cron.d/ | grep wordpress

  do_certbot || echo "   certbot step pending - run 'certbot' (or 'golive') again once DNS resolves here."
  ${WP} cache flush >/dev/null
  echo
  echo "   Next: open https://${DOMAIN}/ and wp-admin, check stock app -> Sites (Webhooks: ok) and the Yamaha Sync status page."
}

case "$step" in
  prepare)  do_prepare ;;
  import)   do_import "${2:-}" ;;
  lockdown) do_lockdown ;;
  cert)     do_cert ;;
  certbot)  do_certbot ;;
  status)   do_status ;;
  golive)   do_golive ;;
  *) sed -n '2,20p' "$0"; exit 1 ;;
esac
