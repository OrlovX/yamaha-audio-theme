# Yamaha Audio — temă WooCommerce

Temă dedicată pentru **yamaha-audio.ro**, magazin Yamaha (un singur brand). Întunecată, violet Yamaha ca accent,
rapidă, fără page builder: pagina principală, categoriile și paginile de produs se redau direct din datele WooCommerce.
Fără build: PHP, un CSS (`assets/css/main.css`, pe tokeni `--hx-*`), un JS (`assets/js/main.js`, fără dependențe).

## Deploy

- **Staging** `https://staging.yamaha-audio.ro` — tema se trage automat din acest repo
  (cron `git pull` la fiecare minut, în `wp-content/themes/yamaha-audio`, cheie de deploy read-only).
- **Live** `https://yamaha-audio.ro` — pull manual, după `docs/DEPLOY.md`.

## Instalare / activare

1. Aspect → Teme → **Yamaha Audio** → Activează (logo‑ul `yamaha_purple.png` din mediatecă este preluat automat).
2. Aspect → Meniuri → atribuie meniul la locația **Meniu principal (mega menu)**: categoriile de produse la stânga,
   Blog / Promoții / Contact la dreapta (separarea se face după slug, lista `nav_extra` din Customizer).
3. Aspect → Personalizare → panoul **Yamaha Audio**: contact & bară de sus, promisiuni, hero, categorii & produse
   (rândurile de pe prima pagină = slug‑uri de categorii), pachet recomandat, banda MusicCast, campanie, date firmă.
4. **Yamaha Audio → Pagini legale → Instalează** — doar după ce datele firmei din Customizer sunt cele corecte.

## Structură

- `inc/setup.php` — suport teme, meniuri, rutare șabloane după slug (`promotii`, `contact`), titluri românești WooCommerce
- `inc/assets.php` — un CSS, un JS, fontul Manrope
- `inc/customizer.php` — toate textele și setările, cu valori implicite în cod (`yamaha_audio_option_defaults()`)
- `inc/helpers.php` — `yamaha_audio_opt()`, iconițe, preț/rate/stoc, categoria principală a unui produs, curățarea HTML importat, campanie
- `inc/template-tags.php` — card de produs, bloc de preț, header, căutare (FiboSearch)
- `inc/woocommerce.php` — ordonarea catalogului, pagina de produs, bara fixă de cumpărare
- `inc/filters.php` — filtre pe atribute (toate atributele WooCommerce, afișate doar când separă rezultatele), preț, stoc
- `inc/seo.php` — titluri/descrieri (Yoast; Rank Math suportat), og:image, schema Store
- `inc/legal.php` + `legal/` — șase pagini legale generate din datele firmei
- `inc/awards.php` — badge‑uri de premii din meta `_hx_awards`
- `woocommerce/` — șabloane; `docs/` — infrastructură, deploy, handover; `server/` — scripturile serverelor

Prefixul `hx-` al claselor CSS și al cheilor interne este spațiul de nume al temei (scurt, stabil); prefixul funcțiilor
este `yamaha_audio_`, text domain `yamaha-audio`.
