<?php
/**
 * Livrare și plată – text generat de temă.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="hx-note hx-note--accent"><strong>Pe scurt:</strong> transport gratuit peste <?php echo $v['free_min']; ?>, expediere în aceeași zi pentru comenzile din stoc plasate până la ora 15:00 (luni–vineri), ridicare din showroom oricând în program. Plătești cu cardul, prin transfer, ramburs sau în până la <?php echo $v['rates']; ?> rate <?php echo $v['rates_lbl']; ?>.</p>

<h2>1. Metode de livrare</h2>
<table>
	<thead><tr><th>Metodă</th><th>Cost</th><th>Termen estimat</th></tr></thead>
	<tbody>
		<tr><td><strong>Curier rapid</strong>, la adresă, în toată România</td><td>gratuit peste <?php echo $v['free_min']; ?>; sub acest prag: <?php echo $v['courier']; ?></td><td>1–2 zile lucrătoare de la expediere (București: de regulă a doua zi)</td></tr>
		<tr><td><strong>Ridicare din showroom</strong> – <?php echo $v['showroom']; ?></td><td>gratuit</td><td>în aceeași zi pentru produsele din stoc, după confirmarea noastră; program <?php echo $v['hours']; ?></td></tr>
		<tr><td><strong>Livrare proprie în București</strong>, pentru boxe de podea, amplificatoare grele și sisteme complete</td><td>gratuit peste <?php echo $v['free_min']; ?></td><td>la o dată și oră convenite telefonic, cu toate indicațiile de instalare și reglaj de care ai nevoie</td></tr>
	</tbody>
</table>
<ul>
	<li>Costul exact al transportului este afișat în coș, înainte de finalizarea comenzii, în funcție de greutatea și dimensiunile coletului.</li>
	<li>Comenzile pentru produse <strong>în stoc</strong> plasate până la ora 15:00 în zilele lucrătoare se expediază în aceeași zi. Pentru produsele <strong>la comandă</strong>, termenul estimat este afișat pe pagina produsului și confirmat prin e‑mail; dacă nu îl putem respecta, te anunțăm și poți anula comanda fără costuri.</li>
	<li>Termenul maxim de livrare, dacă nu s‑a convenit altfel, este de 30 de zile de la confirmarea comenzii (OUG nr. 34/2014).</li>
	<li>Produsele voluminoase sau fragile (boxe de podea, pick‑up‑uri, lămpi) sunt ambalate suplimentar; pentru unele, curierul livrează până la ușa imobilului.</li>
</ul>

<h2>2. La primirea coletului</h2>
<ul>
	<li>Verifică ambalajul în prezența curierului. Dacă este deteriorat, deschide coletul împreună cu curierul și, dacă produsul este afectat, refuză‑l sau notează pe documentul de transport „colet deteriorat” și fotografiază‑l.</li>
	<li>Anunță‑ne în cel mult 48 de ore de la primire orice lipsă sau deteriorare, la <a href="mailto:<?php echo $v['email']; ?>"><?php echo $v['email']; ?></a> sau <?php echo $v['phone']; ?>, cu fotografii: rezolvăm prin înlocuire sau reparație fără costuri pentru tine. Riscul transportului este al nostru până la predarea produsului.</li>
	<li>Păstrează ambalajul original cel puțin pe durata termenului de retragere de 14 zile.</li>
</ul>

<h2>3. Metode de plată</h2>
<table>
	<thead><tr><th>Metodă</th><th>Detalii</th></tr></thead>
	<tbody>
		<tr><td><strong>Card bancar online</strong></td><td>Visa, Mastercard, Maestro, prin procesator de plăți autorizat, în pagină securizată (3D Secure). Datele cardului nu sunt stocate de noi. Fără comision.</td></tr>
		<tr><td><strong>Rate <?php echo $v['rates_lbl']; ?></strong></td><td>până la <?php echo $v['rates']; ?> rate, pentru produsele peste <?php echo $v['rates_min']; ?>, cu cardurile de cumpărături acceptate; numărul de rate și aprobarea depind de banca emitentă. Alte carduri de cumpărături pot fi acceptate în showroom – întreabă‑ne.</td></tr>
		<tr><td><strong>Transfer bancar</strong></td><td>pe baza facturii proforme trimise prin e‑mail; expediem după confirmarea încasării (de regulă în aceeași zi lucrătoare).<?php if ( $v['iban'] ) : ?> Cont: <?php echo $v['iban']; ?><?php if ( $v['bank'] ) : ?>, <?php echo $v['bank']; ?><?php endif; ?>.<?php endif; ?></td></tr>
		<tr><td><strong>Ramburs la curier</strong></td><td>numerar sau card la livrare, pentru comenzi de până la 10.000 lei; fără comision suplimentar.</td></tr>
		<tr><td><strong>În showroom</strong></td><td>numerar, card sau rate, la ridicarea produsului.</td></tr>
	</tbody>
</table>
<p>Pentru comenzi speciale (produse la comandă, importuri) putem solicita un avans; condițiile sunt confirmate în scris înainte de plasarea comenzii. Factura fiscală se emite electronic, pe datele indicate la comandă, și se transmite prin e‑mail (și prin sistemul RO e‑Factura, unde legea o cere).</p>

<h2>4. Ajutor</h2>
<p>Ai întrebări despre livrare sau o comandă în curs? Scrie‑ne la <a href="mailto:<?php echo $v['email']; ?>"><?php echo $v['email']; ?></a> sau pe WhatsApp la <?php echo $v['phone']; ?>, în programul showroom‑ului. Ultima actualizare: <?php echo $v['updated']; ?>.</p>
