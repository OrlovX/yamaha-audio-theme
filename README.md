# Yamaha Audio — temă WooCommerce

Temă dedicată pentru **yamaha-audio.ro** (magazin Yamaha), derivată din tema HiFi Expert
(`github.com/OrlovX/hifiexpert-theme`, commit `c9afdba`, 2026-10-30). Întunecată, rapidă, fără page builder:
pagina principală, categoriile și paginile de produs se redau direct din datele WooCommerce.

Starea actuală: **copie 1:1 a temei HiFi Expert, redenumită.** Branding-ul, meniul, textele, paginile legale și
SEO sunt încă cele HiFi Expert — se adaptează pe staging (vezi `docs/HANDOVER.md`, secțiunea „Redesign”).

## Deploy

- **Staging** `https://staging.yamaha-audio.ro` — tema se trage automat din acest repo
  (cron `git pull` la fiecare minut, în `wp-content/themes/yamaha-audio`, cheie de deploy read-only).
- **Live** `https://yamaha-audio.ro` — pull manual, după `docs/DEPLOY.md`.

## Instalare / activare (staging)

1. Aspect → Teme → **Yamaha Audio** → Activează.
2. Aspect → Meniuri → atribuie meniul la locația **Meniu principal (mega menu)**.
3. Aspect → Personalizare → panoul **HiFi Expert** (numele panoului se redenumește la redesign): hero, contact,
   footer, campanie. Identitate site → Logo.
4. **Nu** rula „Pagini legale → Instalează” înainte ca datele firmei din `legal/*.php` să fie cele ale magazinului Yamaha.

## Structură

- `inc/setup.php` — suport teme, meniuri, rutare șabloane după slug
- `inc/assets.php` — un CSS, un JS
- `inc/customizer.php` — toate textele configurabile + valorile implicite din cod (`hifiexpert_option_defaults()`)
- `inc/helpers.php`, `inc/template-tags.php` — brand, preț, stoc, carduri de produs
- `inc/woocommerce.php` — hook-uri WooCommerce; `inc/seo.php` — filtre Rank Math (yamaha-audio.ro folosește Yoast — de adaptat)
- `inc/legal.php` + `legal/` — paginile legale (date HiFi Expert — de adaptat)
- `woocommerce/` — șabloane; `assets/css/main.css` — tot stilul, pe tokeni CSS (`--hx-*`); `assets/js/main.js`
- `docs/` — infrastructură (`SERVER.md`), runbook deploy live (`DEPLOY.md`), migrarea făcută (`CUTOVER.md`), handover
- `server/` — scripturile cu care s-au construit serverele live și staging
