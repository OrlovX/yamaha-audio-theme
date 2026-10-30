# yamaha-audio.ro — cutover runbook (old Bitnami instance → new server)

Mechanism: the **production static IP moves from the old instance to the new one**. Route 53 is not touched.
Expected downtime: 10–15 minutes (maintenance page, HTTP 503). Pick a quiet hour (evening).

Servers (private IPs don't change): OLD Bitnami `172.26.12.134` (root via `sudo -i`), NEW `172.26.3.188`
(`admin`, key `HiFi-Expert-LS.pem`; `3.69.127.68` until the swap, the production IP afterwards).

## Before (any time, site stays up)
1. Lightsail → old Bitnami instance → **Snapshots → Create snapshot** (rollback insurance; takes a few minutes).
2. NEW server has the current `yamaha-migrate.sh` (re-copy it: `scp -i ~/Desktop/TECH/KEYS/HiFi-Expert-LS.pem ~/Downloads/yamaha-migrate.sh admin@3.69.127.68:`).
3. OLD server (root): link still works →
   `ssh -i /root/.ssh/id_migr admin@172.26.3.188 'hostname; sudo -n true && echo sudo-ok'`
4. Open two terminals (OLD root, NEW admin) and the Lightsail **Networking → Static IPs** page.

## T+0 — OLD server (root): freeze the shop
```bash
echo '<?php $upgrading = time() + 3600;' > /opt/bitnami/wordpress/.maintenance   # 503 for ~1 h (WP's own 10-min expiry avoided)
curl -sk -o /dev/null -w '%{http_code}\n' https://127.0.0.1/ -H "Host: yamaha-audio.ro"    # expect 503
```
From now on no order can be placed on the old site.

## T+1 — OLD server (root): final dump + delta copy
```bash
cd /opt/bitnami/wordpress
wp db export /tmp/yamaha-final.sql --add-drop-table --single-transaction --quick --default-character-set=utf8mb4 && gzip -f /tmp/yamaha-final.sql && ls -lh /tmp/yamaha-final.sql.gz
rsync -az --delete --info=progress2 --rsync-path="sudo rsync" -e "ssh -i /root/.ssh/id_migr" \
  --exclude '/cache/' --exclude '/litespeed/' --exclude '/upgrade/' --exclude '/upgrade-temp-backup/' \
  /bitnami/wordpress/wp-content/ admin@172.26.3.188:/var/www/html/wp-content/
scp -i /root/.ssh/id_migr /tmp/yamaha-final.sql.gz admin@172.26.3.188:~/
```

## T+4 — NEW server (admin): import (NO lockdown this time)
```bash
sudo bash yamaha-migrate.sh import ~/yamaha-final.sql.gz
sudo bash yamaha-migrate.sh status 2>&1 | grep -vE '^(PHP )?Warning'
```
Check in `status`: `blog_public 1`, `yahfx-sync` and the other plugins **active**, both webhooks **active**, newest order present.

## T+8 — Lightsail console: swap the IP
1. Static IPs → **"Staging" (3.69.127.68)** → Detach (from `yamaha-audio-live`).
2. Static IPs → **production IP** → Detach (from the Bitnami instance) → Attach → `yamaha-audio-live`.
3. Static IPs → **"Staging" (3.69.127.68)** → Attach → the **Bitnami instance** (so the old server stays reachable).
Browser (private window): `https://yamaha-audio.ro/` → the shop, valid certificate.

## T+10 — NEW server (now `ssh … admin@<production IP>`): go live
```bash
sudo bash yamaha-migrate.sh golive
```
Enables wp-cron (`/etc/cron.d/wordpress`), runs `certbot --apache` for apex + www (renewals from now on via certbot),
prints webhooks and plugin state. If it says DNS does not point here yet, wait a minute and run it again.

## T+12 — checks
- Front: home, category, product, search, cart → checkout page; `https://www.yamaha-audio.ro/` → apex.
- `/wp-admin`: WooCommerce → Stare (no red items), Comenzi (newest order = the last one from the old site).
- Stock app `https://stoc.hifiexpert.ro` → Sites: yamaha-audio.ro **Webhooks: ok**. Yamaha Sync status page on the new server.
- A small real order (COD) end to end, then cancel it in admin — confirms webhooks + e-mail (WP Mail SMTP) work.
- `ssh … admin@<production IP> 'sudo tail -n 20 /var/log/apache2/error.log'` — nothing alarming.

## After — OLD server (root, now at 3.69.127.68): make sure it can never act as master again
```bash
cd /opt/bitnami/wordpress && wp plugin deactivate yahfx-sync yamaha-product-importer webappick-product-feed-for-woocommerce wp-mail-smtp
wp db query "UPDATE wp_wc_webhooks SET status='disabled'"
```
Leave `.maintenance` in place. Keep the instance ~1 week, then snapshot and delete it (Lightsail bills stopped
instances too). Delete the migration key from the new server when done:
`sed -i '/yamaha-migration/d' /home/admin/.ssh/authorized_keys`.

## Rollback (while the old server still exists)
Lightsail: move the production IP back to the Bitnami instance; OLD server: `rm /opt/bitnami/wordpress/.maintenance`.
Orders placed on the new server in between would have to be re-entered by hand — keep the window short.
