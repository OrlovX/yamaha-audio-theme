<?php
/**
 * Politica de cookie-uri – text generat de temă.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="hx-note hx-note--accent"><strong>Pe scurt:</strong> folosim cookie‑uri strict necesare pentru coș, cont și securitate (nu au nevoie de acord) și, doar cu acordul tău, cookie‑uri de analiză și de marketing. Îți poți schimba alegerea oricând.</p>

<h2>1. Ce sunt cookie‑urile</h2>
<p>Cookie‑urile sunt fișiere text de mici dimensiuni pe care un site le salvează în browserul tău. Ele permit site‑ului să te recunoască între pagini (de exemplu ca să‑ți păstreze coșul) sau între vizite. Tehnologii similare – <em>local storage</em>, pixeli de urmărire, scripturi – sunt tratate la fel în această politică. Prelucrarea se face în temeiul art. 4 din Legea nr. 506/2004 și al GDPR.</p>

<h2>2. Ce cookie‑uri folosește <?php echo $v['site']; ?></h2>
<h3>Strict necesare (fără consimțământ)</h3>
<p>Fără ele site‑ul nu poate funcționa: nu pot fi dezactivate din banner, doar din browser.</p>
<table>
	<thead><tr><th>Cookie</th><th>Rol</th><th>Durată</th></tr></thead>
	<tbody>
		<tr><td><code>woocommerce_cart_hash</code>, <code>woocommerce_items_in_cart</code>, <code>wp_woocommerce_session_*</code></td><td>păstrează coșul de cumpărături și sesiunea de comandă</td><td>sesiune / 2 zile</td></tr>
		<tr><td><code>wordpress_logged_in_*</code>, <code>wordpress_sec_*</code>, <code>wordpress_test_cookie</code></td><td>autentificarea în contul de client</td><td>sesiune / 14 zile</td></tr>
		<tr><td><code>yith_wcwl_*</code></td><td>lista de dorințe (wishlist) pentru vizitatorii fără cont</td><td>până la 1 an</td></tr>
		<tr><td><code>hx_consent</code></td><td>reține alegerile tale privind cookie‑urile</td><td>6 luni</td></tr>
		<tr><td><code>hx_h</code></td><td>protejează filtrele catalogului de accesări automate</td><td>1 an</td></tr>
		<tr><td><code>hxr_c</code></td><td>reține răspunsul tău la întrebarea despre reamintirile pe e‑mail</td><td>6 luni</td></tr>
	</tbody>
</table>
<h3>Funcționale și de preferințe</h3>
<p>Rețin, de exemplu, tema aleasă (luminoasă sau întunecată), ultimul produs afișat pe prima pagină (<code>hx_hero</code>, 7 zile) sau faptul că ai închis un anunț. Nu te identifică personal.</p>
<h3>Analiză (cu consimțământ)</h3>
<p>Ne ajută să înțelegem cum este folosit site‑ul (pagini vizitate, timp petrecut, erori), pentru a‑l îmbunătăți. Dacă sunt activate, folosim Google Analytics (<code>_ga</code>, <code>_ga_*</code>, până la 2 ani), cu adresa IP anonimizată, în temeiul consimțământului tău.</p>
<h3>Marketing (cu consimțământ)</h3>
<p>Permit măsurarea campaniilor noastre și afișarea de reclame relevante pe alte site‑uri: Google Ads (<code>_gcl_au</code>, 3 luni) și atribuirea comenzilor din magazin (<code>sbjs_*</code>, până la 6 luni), care reține din ce sursă ai ajuns pe site. Se activează doar dacă le accepți.</p>

<h2>3. Cum îți gestionezi acordul</h2>
<ul>
	<li>La prima vizită, anunțul despre cookie‑uri îți permite să accepți sau să refuzi categoriile opționale. Poți reveni oricând asupra alegerii din linkul „Setări cookie‑uri”, aflat în subsolul fiecărei pagini, sau chiar de aici: <a href="#setari-cookie">deschide setările cookie‑urilor</a>. Dacă îți retragi acordul, cookie‑urile de analiză și de marketing deja salvate sunt șterse.</li>
	<li>Din browser poți șterge cookie‑urile existente sau bloca setarea lor: <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a>, <a href="https://support.mozilla.org/ro/kb/cookie-urile" target="_blank" rel="noopener">Firefox</a>, <a href="https://support.apple.com/ro-ro/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Safari</a>, <a href="https://support.microsoft.com/ro-ro/microsoft-edge/" target="_blank" rel="noopener">Edge</a>. Blocarea cookie‑urilor strict necesare face imposibilă finalizarea unei comenzi.</li>
	<li>Pentru dezactivarea personalizării reclamelor poți folosi și <a href="https://www.youronlinechoices.com/ro/" target="_blank" rel="noopener">youronlinechoices.com</a>.</li>
</ul>

<h2>4. Cookie‑uri ale terților</h2>
<p>Unele pagini pot include conținut de la terți (hărți Google, videoclipuri YouTube, butoane de rețele sociale, pagina procesatorului de plăți). Aceștia pot seta propriile cookie‑uri, conform politicilor lor, asupra cărora nu avem control.</p>

<h2>5. Date personale</h2>
<p>Informațiile colectate prin cookie‑uri pot fi date cu caracter personal (de exemplu adresa IP). Le prelucrăm conform <a href="<?php echo $v['url_politica_de_confidentialitate']; ?>">Politicii de confidențialitate</a>, unde găsești și drepturile tale și datele noastre de contact.</p>

<h2>6. Actualizări</h2>
<p>Lista cookie‑urilor se poate schimba odată cu instrumentele folosite pe site; versiunea curentă este cea de pe această pagină. Ultima actualizare: <?php echo $v['updated']; ?>.</p>
