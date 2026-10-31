#!/usr/bin/env bash
# Apache mod_php (prefork, no HTTP/2) → PHP-FPM + mpm_event + HTTP/2. Debian 12, PHP 8.2. Run as root:
#   sudo bash server/perf-upgrade.sh
# Idempotent. Take a Lightsail snapshot first. Rollback (mod_php):
#   a2dismod mpm_event proxy_fcgi http2; a2disconf php8.2-fpm http2; a2enmod mpm_prefork php8.2; systemctl restart apache2
set -euo pipefail
PHP="${PHP:-8.2}"
W="${WP_PATH:-/var/www/html}"
[ "$(id -u)" = 0 ] || { echo "run with sudo"; exit 1; }

echo "== preflight"
# php_value/php_flag lines outside <IfModule> blocks would break Apache without mod_php.
if [ -f "$W/.htaccess" ]; then
  BAD=$(awk 'BEGIN{d=0} /<IfModule/{d++} /<\/IfModule>/{d--} d==0 && /^[[:space:]]*php_(value|flag|admin_value|admin_flag)/{print NR": "$0}' "$W/.htaccess" || true)
  if [ -n "$BAD" ]; then
    echo "!! $W/.htaccess has php_* directives outside <IfModule> blocks; move them to the FPM pool first:"; echo "$BAD"; exit 1
  fi
fi
apache2ctl -M 2>/dev/null | grep -qE 'mpm_prefork|php_module|php'"${PHP//./_}"'_module' && echo "   current: mod_php/prefork" || echo "   current: not prefork (already migrated?)"

echo "== php${PHP}-fpm"
apt-get -y -q install --no-install-recommends "php${PHP}-fpm" >/dev/null
INI="/etc/php/${PHP}/fpm/php.ini"
sed -i \
  -e 's/^;\?\s*upload_max_filesize\s*=.*/upload_max_filesize = 2000M/' \
  -e 's/^;\?\s*post_max_size\s*=.*/post_max_size = 2000M/' \
  -e 's/^;\?\s*memory_limit\s*=.*/memory_limit = 256M/' \
  -e 's/^;\?\s*max_execution_time\s*=.*/max_execution_time = 60/' \
  -e 's/^;\?\s*max_input_vars\s*=.*/max_input_vars = 5000/' \
  "$INI"
cat >"/etc/php/${PHP}/fpm/conf.d/90-yamaha.ini" <<'EOF'
; yamaha-audio.ro: opcache sized for WordPress + WooCommerce, timestamps on (theme deploys via git pull)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=30000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
realpath_cache_size=4096K
realpath_cache_ttl=600
EOF
POOL="/etc/php/${PHP}/fpm/pool.d/www.conf"
sed -i \
  -e 's/^pm = .*/pm = dynamic/' \
  -e 's/^pm.max_children = .*/pm.max_children = 40/' \
  -e 's/^pm.start_servers = .*/pm.start_servers = 8/' \
  -e 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 4/' \
  -e 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 12/' \
  -e 's/^;\?pm.max_requests = .*/pm.max_requests = 1000/' \
  -e 's/^;\?pm.status_path = .*/pm.status_path = \/fpm-status/' \
  "$POOL"
# Wordfence firewall: mod_php got it via .htaccess php_value; FPM needs it from the pool.
if [ -f "$W/wordfence-waf.php" ] && ! grep -q 'auto_prepend_file' "$POOL"; then
  echo "php_admin_value[auto_prepend_file] = $W/wordfence-waf.php" >>"$POOL"
  echo "   wordfence-waf.php prepended via the FPM pool"
fi
systemctl enable --now "php${PHP}-fpm" >/dev/null
systemctl restart "php${PHP}-fpm"

echo "== apache: mpm_event + proxy_fcgi + http2"
a2dismod -q "php${PHP}" >/dev/null 2>&1 || true
a2dismod -q mpm_prefork >/dev/null 2>&1 || true
a2enmod -q mpm_event proxy_fcgi setenvif http2 >/dev/null
a2enconf -q "php${PHP}-fpm" >/dev/null
printf 'Protocols h2 http/1.1\nH2Direct off\n' >/etc/apache2/conf-available/http2.conf
a2enconf -q http2 >/dev/null
cat >/etc/apache2/mods-available/mpm_event.conf <<'EOF'
# yamaha-audio.ro (4 vCPU / 16 GB)
<IfModule mpm_event_module>
	StartServers              2
	MinSpareThreads          25
	MaxSpareThreads          75
	ThreadLimit              64
	ThreadsPerChild          25
	MaxRequestWorkers       150
	MaxConnectionsPerChild    0
</IfModule>
EOF
apache2ctl configtest
systemctl restart apache2

echo "== verify"
apache2ctl -M 2>/dev/null | grep -Eo '(mpm_event|proxy_fcgi|http2|mpm_prefork|php[0-9_]*)_module' | sort | tr '\n' ' '; echo
D=$(getent hosts yamaha-audio.ro >/dev/null && echo yamaha-audio.ro || echo localhost)
curl -sk --http2 -o /dev/null -w "HTTP %{http_version}  status %{http_code}  ttfb %{time_starttransfer}s\n" "https://$D/"
curl -sk -o /dev/null -w "HTTP %{http_version}  status %{http_code}  ttfb %{time_starttransfer}s  (second hit)\n" --http2 "https://$D/"
php -v | head -1
systemctl is-active "php${PHP}-fpm" apache2 | tr '\n' ' '; echo
echo "done"
