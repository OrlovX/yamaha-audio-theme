#!/usr/bin/env bash
# build-yamaha-live.sh — Apache/PHP/MariaDB server for yamaha-audio.ro (live or staging) on Amazon Lightsail
#
# Target: Debian 12 "bookworm" Lightsail instance - either "OS Only -> Debian 12" or the Lightsail "WordPress"
# blueprint (AWS's own; the live server was built from it; its leftovers are removed in step 4).
# Mirrors the hifiexpert.ro live server (fingerprint taken 2026-10-30):
#   Apache 2.4 + mod_php (prefork) + PHP 8.2, WordPress root /var/www/html, wp-cli, certbot (apache plugin),
#   HTTP->HTTPS redirect in 000-default.conf, TLS vhost in default-ssl.conf,
#   php.ini (apache2): upload_max_filesize 2000M, post_max_size 2000M, memory_limit 128M, max_execution_time 30.
# Deliberate differences:
#   - MariaDB runs on this instance (hifiexpert uses a Lightsail managed MySQL).
#   - wp-cron runs every 5 minutes as www-data from /etc/cron.d (on hifiexpert it sits in root's crontab).
#   - php8.2-mcrypt is installed only if a repo provides it (hifiexpert has it; WordPress does not need it).
#
# Usage (as the "admin" user on the new instance):
#   sudo bash build-yamaha-live.sh                                   # live:    yamaha-audio.ro
#   sudo DOMAIN=staging.yamaha-audio.ro bash build-yamaha-live.sh    # staging
# Safe to re-run after a failure. Prints no secrets: the DB password is written to /root/.yamaha-db.cnf only.

set -euo pipefail

DOMAIN="${DOMAIN:-yamaha-audio.ro}"
DB_NAME="yamaha_audio"
DB_USER="yamaha_audio"
PHP="8.2"
WEBROOT="/var/www/html"

[ "$(id -u)" -eq 0 ] || { echo "Run as root:  sudo bash $0"; exit 1; }
grep -q 'VERSION_ID="12"' /etc/os-release || { echo "This script expects Debian 12 (bookworm)."; exit 1; }

export DEBIAN_FRONTEND=noninteractive
log() { printf '\n\033[1;33m== %s\033[0m\n' "$*"; }

# ---------------------------------------------------------------------------------------------------------------
log "1/9  Base system"
apt-get update -q
apt-get -y -q upgrade
apt-get -y -q install ca-certificates curl gnupg unzip git rsync less htop ncdu unattended-upgrades
# cron is not part of the Lightsail image (without it /etc/cron.d/wordpress never runs); no-install-recommends
# keeps the exim4 mail daemon out - the site sends mail through WP Mail SMTP, as hifiexpert does.
apt-get -y -q install --no-install-recommends cron

# ---------------------------------------------------------------------------------------------------------------
log "2/9  Apache 2.4 + PHP ${PHP} (mod_php, module set as on hifiexpert)"
apt-get -y -q install apache2 ssl-cert \
  "libapache2-mod-php${PHP}" "php${PHP}-cli" "php${PHP}-common" "php${PHP}-curl" "php${PHP}-gd" \
  "php${PHP}-mbstring" "php${PHP}-mysql" "php${PHP}-xml" "php${PHP}-zip" "php${PHP}-opcache" \
  "php${PHP}-readline" "php${PHP}-bz2"
apt-get -y -q install "php${PHP}-mcrypt" 2>/dev/null \
  || echo "   php${PHP}-mcrypt is not in the enabled repos - skipped (only matters for exact parity, not for WordPress)"

a2enmod -q rewrite ssl headers expires

# Document root (Debian's index.html goes away; placeholder page until the site is migrated)
install -d -o www-data -g www-data "$WEBROOT"
rm -f "$WEBROOT/index.html"
[ -e "$WEBROOT/wp-settings.php" ] || cat >"$WEBROOT/index.html" <<EOF
<!doctype html><title>${DOMAIN}</title><p>${DOMAIN} - new server, site not migrated yet.</p>
EOF
chown www-data:www-data "$WEBROOT/index.html" 2>/dev/null || true

# Permalinks / .htaccess need AllowOverride All on the web root
cat >/etc/apache2/conf-available/wordpress.conf <<EOF
<Directory ${WEBROOT}>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF
a2enconf -q wordpress
echo "ServerName localhost" >/etc/apache2/conf-available/fqdn.conf && a2enconf -q fqdn

# Port 80: redirect everything to HTTPS (same rule set as hifiexpert; ACME challenge excluded)
cat >/etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    ServerAdmin webmaster@localhost
    DocumentRoot ${WEBROOT}

    # Redirect all non-HTTPS requests to HTTPS. Both the CloudFront and the
    # standard forwarded-protocol headers are checked so the redirect works
    # behind a CDN.
    RewriteEngine On
    RewriteCond %{HTTP:CloudFront-Forwarded-Proto} !=https
    RewriteCond %{HTTP:X-Forwarded-Proto} !=https
    RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/ [NC]
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301,NE]

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Port 443: snakeoil certificate for now so Apache listens on 443 from day one;
# "certbot --apache" replaces the two certificate lines at cutover.
cat >/etc/apache2/sites-available/default-ssl.conf <<EOF
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    ServerAdmin webmaster@localhost
    DocumentRoot ${WEBROOT}

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

    <FilesMatch "\.(?:cgi|shtml|phtml|php)$">
        SSLOptions +StdEnvVars
    </FilesMatch>
</VirtualHost>
</IfModule>
EOF
a2ensite -q 000-default default-ssl

# php.ini (apache2) limits as on hifiexpert
PHPINI="/etc/php/${PHP}/apache2/php.ini"
sed -i -E \
  -e 's/^;?\s*upload_max_filesize\s*=.*/upload_max_filesize = 2000M/' \
  -e 's/^;?\s*post_max_size\s*=.*/post_max_size = 2000M/' \
  -e 's/^;?\s*memory_limit\s*=.*/memory_limit = 128M/' \
  -e 's/^;?\s*max_execution_time\s*=.*/max_execution_time = 30/' \
  "$PHPINI"

apache2ctl configtest
systemctl enable -q apache2
systemctl restart apache2

# ---------------------------------------------------------------------------------------------------------------
log "3/9  MariaDB on this instance"
apt-get -y -q install mariadb-server mariadb-client
cat >/etc/mysql/mariadb.conf.d/60-yamaha.cnf <<'EOF'
[mysqld]
# 16 GB instance shared with Apache/PHP; ~140 products -> this is generous
innodb_buffer_pool_size = 2G
innodb_log_file_size    = 256M
max_allowed_packet      = 64M
character-set-server    = utf8mb4
collation-server        = utf8mb4_unicode_ci
EOF
systemctl enable -q mariadb
systemctl restart mariadb

if [ ! -f /root/.yamaha-db.cnf ]; then
  DB_PASS="$(openssl rand -hex 16)"   # no pipe: "tr | head" dies of SIGPIPE under pipefail and aborts the script
  mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  ( umask 077; printf '[client]\nuser=%s\npassword=%s\ndatabase=%s\n' "$DB_USER" "$DB_PASS" "$DB_NAME" >/root/.yamaha-db.cnf )
  echo "   database ${DB_NAME} + user ${DB_USER} created; credentials in /root/.yamaha-db.cnf (not printed)"
else
  echo "   /root/.yamaha-db.cnf exists - keeping the existing database credentials"
fi
# root keeps unix_socket auth (Debian default): "sudo mariadb" works, no root password to manage.

# ---------------------------------------------------------------------------------------------------------------
log "4/9  Lightsail WordPress-blueprint leftovers (no-op on an OS-only image)"
if [ -d /opt/aws/wordpress ]; then
  # the blueprint's install script created these on first boot; none of them is used
  mariadb -e "DROP DATABASE IF EXISTS \`wordpress-db\`; DROP USER IF EXISTS 'user'@'localhost'; DROP DATABASE IF EXISTS phpmyadmin;"
  rm -f /opt/aws/wordpress/credentials.log /home/admin/application_credentials
  rm -rf "${WEBROOT}/phpmyadmin" "${WEBROOT}/wp-content/plugins/google-analytics-for-wordpress"
  if dpkg -l phpmyadmin 2>/dev/null | grep -q '^ii'; then
    a2disconf -q phpmyadmin 2>/dev/null || true
    apt-get -y -q purge phpmyadmin && apt-get -y -q autoremove --purge
  fi
  if command -v crontab >/dev/null && crontab -l 2>/dev/null | grep -q 'wp-cron.php'; then
    crontab -l | grep -v 'wp-cron.php' | crontab -
    echo "   removed the blueprint's root-crontab wp-cron entry (ours runs as www-data from /etc/cron.d)"
  fi
  echo "   blueprint leftovers removed (its pre-installed WordPress in ${WEBROOT} is replaced by the migration)"
else
  echo "   OS-only image, nothing to do"
fi

# ---------------------------------------------------------------------------------------------------------------
log "5/9  wp-cli"
curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /usr/local/bin/wp
install -d -o www-data -g www-data -m 700 /var/www/.wp-cli
sudo -u www-data /usr/local/bin/wp --info | grep -E 'WP-CLI version|PHP version'

# ---------------------------------------------------------------------------------------------------------------
log "6/9  certbot (apache plugin) - NOT run yet; DNS must point here first (cutover step)"
apt-get -y -q install certbot python3-certbot-apache

# ---------------------------------------------------------------------------------------------------------------
log "7/9  wp-cron every 5 minutes as www-data (set DISABLE_WP_CRON true in wp-config.php at migration)"
cat >/etc/cron.d/wordpress <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
*/5 * * * * www-data cd ${WEBROOT} && [ -f wp-cron.php ] && /usr/bin/php wp-cron.php >/dev/null 2>&1
EOF
chmod 644 /etc/cron.d/wordpress

# ---------------------------------------------------------------------------------------------------------------
log "8/9  SSH key for www-data (future read-only GitHub deploy key for the theme repo)"
install -d -o www-data -g www-data -m 700 /var/www/.ssh
[ -f /var/www/.ssh/id_ed25519 ] || sudo -u www-data ssh-keygen -q -t ed25519 -N "" -f /var/www/.ssh/id_ed25519 -C "${DOMAIN}"

# ---------------------------------------------------------------------------------------------------------------
log "9/9  Summary"
echo "OS:       $(. /etc/os-release && echo "$PRETTY_NAME")"
echo "Apache:   $(apache2 -v | head -1)"
echo "PHP:      $(php -v | head -1)"
echo "MariaDB:  $(mariadb --version)"
echo "wp-cli:   $(sudo -u www-data /usr/local/bin/wp --version)"
echo "certbot:  $(certbot --version 2>&1)"
echo "Modules:  $(php -m | grep -v -E '^\[|^$' | tr '\n' ' ')"
echo "HTTPS:    $(curl -sk -o /dev/null -w '%{http_code}' https://localhost/) (self-signed placeholder cert)"
echo "HTTP:     $(curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}' -H "Host: ${DOMAIN}" http://localhost/)"
echo
echo "Deploy key (public, safe to share) - add later in GitHub -> yamaha-audio-theme -> Settings -> Deploy keys:"
cat /var/www/.ssh/id_ed25519.pub
echo
echo "Next: migrate the site (files -> ${WEBROOT}, dump -> ${DB_NAME}), wp-config.php in /var/www/, DNS, certbot --apache."
