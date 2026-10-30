<?php
/**
 * Retur și drept de retragere – text generat de temă (include formularul de retragere, Anexa B la OUG 34/2014).
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="hx-note hx-note--accent"><strong>Pe scurt:</strong> pentru comenzile online ai 14 zile de la primire să te răzgândești, fără să dai explicații. Ne anunți, trimiți produsul înapoi complet și în bună stare în alte 14 zile, iar noi îți returnăm banii în cel mult 14 zile de la anunț. Cablurile tăiate la lungimea cerută și discurile desigilate fac excepție.</p>

<h2>1. Dreptul de retragere</h2>
<p>Dacă ești <strong>consumator</strong> (persoană fizică) și ai cumpărat la distanță (online, telefonic), te poți retrage din contract în termen de <strong>14 zile calendaristice</strong>, fără a indica un motiv și fără alte costuri decât cele prevăzute mai jos, conform art. 9 din OUG nr. 34/2014. Termenul curge de la ziua în care tu sau un terț desemnat de tine (altul decât curierul) intră în posesia fizică a produsului; pentru comenzile livrate în mai multe tranșe, de la ultimul produs primit.</p>
<p>Dreptul de retragere nu se aplică persoanelor juridice și nici achizițiilor făcute în showroom (unde poți testa produsul înainte de cumpărare); pentru acestea încercăm oricum să găsim o soluție amiabilă – întreabă‑ne.</p>

<h2>2. Cum procedezi</h2>
<ol>
	<li><strong>Anunță‑ne în termenul de 14 zile</strong> printr‑o declarație neechivocă: e‑mail la <a href="mailto:<?php echo $v['email']; ?>"><?php echo $v['email']; ?></a> (subiect „Retragere comandă nr. …”), prin formularul de mai jos sau prin poștă la adresa sediului. Îți confirmăm primirea prin e‑mail.</li>
	<li><strong>Returnează produsul în cel mult 14 zile</strong> de la data anunțului: prin curier, la adresa comunicată în confirmare, sau direct la showroom (<?php echo $v['showroom']; ?>, <?php echo $v['hours']; ?>). Ambalează‑l corespunzător, de preferință în ambalajul original, cu toate accesoriile, manualele, certificatul de garanție și o copie a facturii.</li>
	<li><strong>Rambursarea</strong>: îți returnăm toate sumele plătite, inclusiv costul livrării standard, în cel mult 14 zile de la data la care am fost informați, folosind aceeași metodă de plată ca la comandă (sau alta, dacă ești de acord), fără comisioane. Putem amâna rambursarea până primim produsul înapoi sau dovada că l‑ai expediat.</li>
</ol>

<h2>3. Costuri și condiții</h2>
<ul>
	<li><strong>Costul returului</strong> (transportul produsului înapoi) este suportat de tine; pentru produsele voluminoase care nu pot fi trimise prin poștă (boxe de podea, amplificatoare grele), costul estimat al unui curier este de 40–150 lei, în funcție de greutate și distanță. Dacă returul se datorează unei erori a noastre sau unui produs neconform, transportul este pe costul nostru.</li>
	<li><strong>Starea produsului</strong>: în perioada de retragere poți despacheta și testa produsul așa cum ai face‑o într‑un magazin, pentru a‑i stabili natura, caracteristicile și funcționarea. Dacă valoarea produsului s‑a diminuat din cauza manipulării dincolo de acest nivel (zgârieturi, urme de montaj, accesorii sau ambalaj lipsă, produse folosite intensiv), putem reține din rambursare o sumă proporțională cu diminuarea valorii (art. 14 alin. (3) din OUG nr. 34/2014).</li>
	<li>Dacă ai plătit o livrare specială (de exemplu livrare proprie programată), rambursăm doar echivalentul livrării standard.</li>
	<li>Produsele cumpărate în rate: rambursarea se face în contul cardului; banca anulează ratele conform propriilor proceduri.</li>
</ul>

<h2>4. Excepții de la dreptul de retragere</h2>
<p>Conform art. 16 din OUG nr. 34/2014, nu poți renunța la:</p>
<ul>
	<li><strong>produsele confecționate după specificațiile tale sau personalizate</strong> – cabluri tăiate la lungimea cerută, cabluri terminate (conectorizate) la comandă, produse comandate într‑un finisaj special pentru tine;</li>
	<li><strong>înregistrări audio/video sau software sigilate</strong> (viniluri, CD‑uri, SACD‑uri, Blu‑ray) care au fost desigilate după livrare;</li>
	<li>produse care, după livrare, au fost inseparabil amestecate cu alte elemente (de exemplu accesorii consumabile montate).</li>
</ul>

<h2>5. Produse defecte sau neconforme</h2>
<p>Dacă produsul primit este defect, deteriorat sau diferit de cel comandat, nu este vorba despre retragere, ci despre <strong>garanția legală de conformitate</strong>: te rugăm să ne anunți în cel mult 48 de ore de la primire pentru deteriorări de transport, respectiv oricând în termenul de garanție pentru defecte. Înlocuirea sau repararea sunt gratuite; detalii în <a href="<?php echo $v['url_garantie']; ?>">Garanție și service</a>.</p>

<h2>6. Formular de retragere</h2>
<p>Completează și trimite acest formular numai dacă dorești să te retragi din contract (model conform Anexei B la OUG nr. 34/2014). Poți copia textul în e‑mail sau tipări pagina.</p>
<div class="hx-form">
	<p><strong>Către:</strong> <?php echo $v['company']; ?>, <?php echo $v['address']; ?>, e‑mail <?php echo $v['email']; ?></p>
	<p>Vă informez prin prezenta cu privire la retragerea mea din contractul referitor la vânzarea următoarelor produse:<span class="hx-form__line"></span><span class="hx-form__line"></span></p>
	<p>Comandate la data de: <span class="hx-form__line"></span></p>
	<p>Primite la data de: <span class="hx-form__line"></span></p>
	<p>Numărul comenzii / facturii: <span class="hx-form__line"></span></p>
	<p>Numele consumatorului: <span class="hx-form__line"></span></p>
	<p>Adresa consumatorului: <span class="hx-form__line"></span></p>
	<p>Contul (IBAN) pentru rambursare, dacă plata s‑a făcut prin transfer sau ramburs: <span class="hx-form__line"></span></p>
	<p>Semnătura consumatorului (doar dacă formularul este transmis pe hârtie): <span class="hx-form__line"></span></p>
	<p>Data: <span class="hx-form__line"></span></p>
</div>

<h2>7. Contact</h2>
<p>Pentru orice retur sau întrebare: <a href="mailto:<?php echo $v['email']; ?>"><?php echo $v['email']; ?></a>, <?php echo $v['phone']; ?> (telefon și WhatsApp), în programul showroom‑ului. Ultima actualizare: <?php echo $v['updated']; ?>.</p>
