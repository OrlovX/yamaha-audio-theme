<?php
/**
 * Customizer: every piece of homepage/header/footer copy lives here (Yamaha Audio defaults).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults (also used by yamaha_audio_opt()).
 */
function yamaha_audio_option_defaults() {
	return array(
		// Logo / top bar (kept from the current site).
		'theme_mode'          => 'light',   // Yamaha white; the header switch still offers dark
		'theme_switch'        => true,
		'logo_invert'         => true,   // the violet PNG logo is shown white on the dark theme, as is on the light one
		'logo_viewbox'        => '',
		'topbar_address'      => 'Bd. Eroii Sanitari 89, Cotroceni, București',
		'topbar_address_url'  => 'https://maps.app.goo.gl/1erfzMfo8ityregH7',
		'topbar_hours'        => 'Luni–Vineri 10:00–17:00 · Sâmbătă 10:00–14:00',
		'hours_weekday'       => '10:00-17:00',
		'hours_saturday'      => '10:00-14:00',
		'hours_sunday'        => '',
		'topbar_email'        => 'contact@yamaha-audio.ro',
		'phone'               => '0758 818 181',
		'whatsapp'            => '+40758818181',
		'showroom_directions' => "Metrou: M1 Eroilor sau Petrache Poenaru, apoi 7 minute pe jos pe Bd. Eroii Sanitari\nTramvai 41 / autobuze pe Bd. Eroii Sanitari, stația Universitatea de Medicină\nParcare: pe Bd. Eroii Sanitari și străzile adiacente (zonă cu plată în timpul zilei)\nIntrarea este la stradă, lângă Universitatea de Medicină „Carol Davila”",
		'waze_url'            => '',

		// Header menu: these items leave the category bar and form a separate group on the right.
		'nav_auto'            => true,   // menu built from the product categories; off = the WordPress menu
		'nav_links'           => "Promoții|/promotii/\nContact|/contact/",
		'nav_extra'           => 'promotii, contact',

		// Company (footer + legal pages).
		'company_name'        => 'HIGH-END EXPERT S.R.L.',
		'company_cui'         => 'RO31095680',
		'company_reg'         => 'J2013000452405',
		'company_address'     => 'Bd. Eroii Sanitari nr. 89, parter, sector 5, 050472 București',
		'company_capital'     => '',
		'company_bank'        => '',
		'company_iban'        => '',
		'company_courier_fee' => '19 lei (TVA inclus)',
		'legal_updated'       => '',
		'og_image'            => 0,
		'social_facebook'     => 'https://www.facebook.com/YamahaAudioRomania',
		'social_instagram'    => 'https://www.instagram.com/yamahaaudioromania/',
		'social_youtube'      => '',
		'social_tiktok'       => '',
		'facets_exclude'      => 'pa, pa1',

		// Promises shown in the strip and on product pages.
		'free_shipping_min'   => 300,
		'rates_enabled'       => true,
		'rates_count'         => 6,
		'rates_min_price'     => 500,
		'rates_label'         => 'fără dobândă cu Card Avantaj',
		'delivery_promise'    => 'Comandă până la ora 15:00 (L–V) și îl primești a doua zi lucrătoare.',
		'pickup_promise'      => 'Sau ridicare din showroom Cotroceni, în aceeași zi.',
		'warranty_text'       => 'Garanție 2 ani, service autorizat',
		'returns_text'        => 'Retur în 14 zile',
		'invoice_text'        => 'Factură fiscală, plată securizată',

		// Hero.
		'hero_eyebrow'        => 'Dealer autorizat Yamaha · în stoc, livrare în 24h',
		'hero_title'          => 'Yamaha. Ascultă diferența.',
		'hero_text'           => 'Receivere AV, amplificatoare și receivere stereo, streamere MusicCast, boxe și căști Yamaha — în stoc în București, livrate în 24 de ore. Până la 6 rate fără dobândă cu Card Avantaj.',
		'hero_product'        => 0,
		'hero_cta_label'      => 'Vezi ofertele Yamaha',
		'hero_cta_url'        => '',
		'hero_cta2_label'     => 'Vizitează showroom‑ul',
		'hero_cta2_url'       => '#showroom',

		// Categories + products.
		'home_categories_exclude' => 'uncategorized',
		'home_products_title' => 'Oferte Yamaha în stoc acum',
		'home_products_text'  => 'Prețuri reduse față de recomandarea Yamaha. Livrare gratuită din București, în 24h.',
		'home_products_source'=> 'sale',
		'home_products_count' => 8,
		'home_exclude_cats'   => 'uncategorized',
		'home_rows'           => "receivere|Receivere: stereo și surround\nboxe-yamaha-experienta-traditie-eleganta|Boxe: raft, podea, active, soundbar\namplificare|Amplificare: integrate, putere, streaming\ncasti|Căști: over‑ear, in‑ear",

		// Bundle block.
		'bundle_enabled'      => false,   // enable once a Yamaha-only bundle with current prices is configured
		'bundle_eyebrow'      => 'Sistem complet, un singur click',
		'bundle_title'        => 'Sistem stereo Yamaha. Gata de ascultat în seara asta.',
		'bundle_text'         => 'Receiver stereo Yamaha cu streaming MusicCast și boxe Yamaha potrivite de experții noștri. Cablul de boxe este inclus.',
		'bundle_items'        => '',
		'bundle_cta_label'    => 'Vezi pachetul',
		'bundle_cta_url'      => '',
		'bundle_price_note'   => '',
		'bundle_image'        => 0,

		// Feature band (MusicCast / complete system).
		'audio_eyebrow'       => 'MusicCast',
		'audio_title'         => 'Toată casa, un singur sistem.',
		'audio_text'          => 'MusicCast leagă receiverul, soundbar‑ul, boxele wireless și streamerul Yamaha într‑un singur sistem multiroom, controlat din telefon. Ne spui camerele, muzica și bugetul; îți compunem sistemul, ți‑l livrăm în 24 de ore și îl punem la punct împreună.',
		'audio_cta_label'     => 'Vreau un sistem MusicCast',
		'audio_cta2_label'    => 'Programează o audiție',

		// Campaign / big sale mode (Black Friday Sound Edition, Winter Sales…).
		// Black Friday Sound Edition 30 Oct → 2 Dec 2026 is baked in as the default, so deploying the theme files is enough
		// to switch it on at the right time on a site whose Customizer hasn't been touched. The date fence turns
		// it off by itself afterwards; the Customizer (Campanie / Sale) overrides all of this.
		'camp_enabled'        => true,
		'camp_name'           => 'Black Friday Sound Edition',
		'camp_tagline'        => 'Reduceri reale la Yamaha, doar câteva zile',
		'camp_style'          => 'neon',
		'camp_start'          => '2026-10-30',
		'camp_end'            => '2026-12-02 23:59',
		'camp_url'            => '',
		'camp_bar'            => true,
		'camp_badge'          => '',

		// Footer.
		'newsletter_shortcode'=> '',
		'newsletter_text'     => 'Primește oferte și noutăți, o dată pe săptămână. Fără spam.',
		'company_line'        => 'Yamaha Audio · High End Expert S.R.L. · Toate drepturile rezervate',   // TODO confirm legal entity
		'payments_line'       => 'Visa · Mastercard · Card Avantaj · Ramburs · Transfer bancar',
	);
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp ) {
		$d = yamaha_audio_option_defaults();

		$panel = 'yamaha-audio';
		$wp->add_panel( $panel, array( 'title' => 'Yamaha Audio', 'priority' => 10 ) );

		$sections = array(
			'hx_contact'  => 'Contact & bară de sus',
			'hx_promises' => 'Promisiuni (livrare, rate, garanție)',
			'hx_hero'     => 'Acasă: Hero',
			'hx_products' => 'Acasă: Categorii & produse',
			'hx_bundle'   => 'Acasă: Pachet recomandat',
			'hx_audio'    => 'Acasă: Bandă MusicCast / sistem complet',
			'hx_campaign' => 'Campanie / Sale (Black Friday Sound Edition, Winter Sales…)',
			'hx_footer'   => 'Footer',
			'hx_company'  => 'Date firmă (footer & pagini legale)',
		);
		foreach ( $sections as $id => $title ) {
			$wp->add_section( $id, array( 'title' => $title, 'panel' => $panel ) );
		}

		$fields = array(
			// section => [ key, label, type, extra ]
			array( 'hx_contact', 'theme_mode', 'Temă implicită', 'select', array( 'light' => 'Luminoasă (implicit)', 'dark' => 'Întunecată', 'auto' => 'După setarea dispozitivului' ) ),
			array( 'hx_contact', 'theme_switch', 'Afișează comutatorul luminos / întunecat în header', 'checkbox' ),
			array( 'hx_contact', 'logo_invert', 'Logo‑ul este închis la culoare: afișează‑l în alb pe fundalul întunecat', 'checkbox' ),
			array( 'hx_contact', 'logo_viewbox', 'Decupaj logo SVG (viewBox: x y lățime înălțime; gol = fișierul original)', 'text' ),
			array( 'hx_contact', 'topbar_address', 'Adresă showroom', 'text' ),
			array( 'hx_contact', 'topbar_address_url', 'Link hartă (Google Maps)', 'url' ),
			array( 'hx_contact', 'topbar_hours', 'Program (text, afișat în footer și ca tooltip)', 'text' ),
			array( 'hx_contact', 'hours_weekday', 'Program Luni–Vineri (ex. 10:00-17:00; gol = închis)', 'text' ),
			array( 'hx_contact', 'hours_saturday', 'Program Sâmbătă (ex. 10:00-14:00; gol = închis)', 'text' ),
			array( 'hx_contact', 'hours_sunday', 'Program Duminică (gol = închis)', 'text' ),
			array( 'hx_contact', 'topbar_email', 'E‑mail', 'text' ),
			array( 'hx_contact', 'phone', 'Telefon (afișat)', 'text' ),
			array( 'hx_contact', 'whatsapp', 'Număr WhatsApp (format internațional)', 'text' ),
			array( 'hx_contact', 'showroom_directions', 'Cum ajungi la showroom (un rând pe linie: metrou, tramvai, parcare, reper)', 'textarea' ),
			array( 'hx_contact', 'waze_url', 'Link Waze (opțional)', 'url' ),
			array( 'hx_contact', 'nav_auto', 'Meniul principal se generează din categoriile de produse (dezactivează pentru a folosi meniul WordPress „Meniu principal”)', 'checkbox' ),
			array( 'hx_contact', 'nav_links', 'Linkuri fixe după categorii, câte unul pe linie, format Etichetă|URL (ex. Promoții|/promotii/)', 'textarea' ),
			array( 'hx_contact', 'nav_extra', 'Doar cu meniul WordPress: elemente afișate separat de categorii, în dreapta (slug-uri sau titluri, separate prin virgulă)', 'text' ),

			array( 'hx_promises', 'free_shipping_min', 'Transport gratuit peste (lei)', 'number' ),
			array( 'hx_promises', 'facets_exclude', 'Atribute care NU devin filtre în magazin (slug-uri, separate prin virgulă; cele cu valori doar numerice sunt oricum ascunse)', 'text' ),
			array( 'hx_promises', 'rates_enabled', 'Afișează rata lunară', 'checkbox' ),
			array( 'hx_promises', 'rates_count', 'Număr rate', 'number' ),
			array( 'hx_promises', 'rates_min_price', 'Rate doar peste (lei)', 'number' ),
			array( 'hx_promises', 'rates_label', 'Text după rată (ex. „fără dobândă cu Card Avantaj”)', 'text' ),
			array( 'hx_promises', 'delivery_promise', 'Promisiune livrare (produse în stoc)', 'text' ),
			array( 'hx_promises', 'pickup_promise', 'Promisiune ridicare showroom', 'text' ),
			array( 'hx_promises', 'warranty_text', 'Garanție', 'text' ),
			array( 'hx_promises', 'returns_text', 'Retur', 'text' ),
			array( 'hx_promises', 'invoice_text', 'Plată / factură', 'text' ),

			array( 'hx_hero', 'hero_eyebrow', 'Text mic deasupra titlului', 'text' ),
			array( 'hx_hero', 'hero_title', 'Titlu', 'text' ),
			array( 'hx_hero', 'hero_text', 'Paragraf', 'textarea' ),
			array( 'hx_hero', 'hero_product', 'ID produs afișat în hero (0 = automat: cel mai nou produs la ofertă)', 'number' ),
			array( 'hx_hero', 'hero_cta_label', 'Buton principal: text', 'text' ),
			array( 'hx_hero', 'hero_cta_url', 'Buton principal: link (gol = pagina Magazin)', 'url' ),
			array( 'hx_hero', 'hero_cta2_label', 'Buton secundar: text', 'text' ),
			array( 'hx_hero', 'hero_cta2_url', 'Buton secundar: link', 'text' ),

			array( 'hx_products', 'home_products_title', 'Titlu grilă produse', 'text' ),
			array( 'hx_products', 'home_products_text', 'Subtitlu grilă produse', 'text' ),
			array( 'hx_products', 'home_products_source', 'Sursă produse', 'select', array( 'bestsellers' => 'Cele mai vândute', 'sale' => 'La ofertă', 'featured' => 'Recomandate (featured)', 'newest' => 'Cele mai noi' ) ),
			array( 'hx_products', 'home_products_count', 'Număr produse (4, 8 sau 12)', 'number' ),
			array( 'hx_products', 'home_categories_exclude', 'Categorii ascunse din grila de categorii (slug-uri, separate prin virgulă)', 'text' ),
			array( 'hx_products', 'home_exclude_cats', 'Categorii excluse din hero și din grile (slug-uri, separate prin virgulă)', 'text' ),
			array( 'hx_products', 'home_rows', 'Rânduri sub oferte: câte un rând pe linie, format slug-categorie|Titlu afișat', 'textarea' ),

			array( 'hx_bundle', 'bundle_enabled', 'Afișează blocul', 'checkbox' ),
			array( 'hx_bundle', 'bundle_eyebrow', 'Text mic', 'text' ),
			array( 'hx_bundle', 'bundle_title', 'Titlu', 'text' ),
			array( 'hx_bundle', 'bundle_text', 'Paragraf', 'textarea' ),
			array( 'hx_bundle', 'bundle_items', 'Componente (una pe linie)', 'textarea' ),
			array( 'hx_bundle', 'bundle_price_note', 'Notă preț / rate', 'text' ),
			array( 'hx_bundle', 'bundle_cta_label', 'Buton: text', 'text' ),
			array( 'hx_bundle', 'bundle_cta_url', 'Buton: link (produs pachet sau categorie)', 'url' ),
			array( 'hx_bundle', 'bundle_image', 'Imagine pachet', 'image' ),

			array( 'hx_audio', 'audio_eyebrow', 'Text mic', 'text' ),
			array( 'hx_audio', 'audio_title', 'Titlu', 'text' ),
			array( 'hx_audio', 'audio_text', 'Paragraf', 'textarea' ),
			array( 'hx_audio', 'audio_cta_label', 'Buton principal: text (deschide WhatsApp)', 'text' ),
			array( 'hx_audio', 'audio_cta2_label', 'Link secundar: text (deschide WhatsApp; gol = ascuns)', 'text' ),

			array( 'hx_campaign', 'camp_enabled', 'Campanie activă', 'checkbox' ),
			array( 'hx_campaign', 'camp_name', 'Nume (ex. Black Friday Sound Edition, Winter Sales)', 'text' ),
			array( 'hx_campaign', 'camp_tagline', 'Slogan scurt (bara de campanie, pagina Promoții, hero)', 'text' ),
			array( 'hx_campaign', 'camp_style', 'Stil vizual', 'select', array( 'neon' => 'Neon – negru & violet electric (LED)', 'black' => 'Black – negru & auriu', 'winter' => 'Winter Sales – alb glacial & albastru', 'red' => 'Red Days – roșu' ) ),
			array( 'hx_campaign', 'camp_start', 'Început (AAAA-LL-ZZ, opțional – campania pornește singură)', 'text' ),
			array( 'hx_campaign', 'camp_end', 'Sfârșit (AAAA-LL-ZZ HH:MM, opțional – numărătoare inversă și oprire automată)', 'text' ),
			array( 'hx_campaign', 'camp_url', 'Link buton „Vezi ofertele” (gol = pagina Promoții)', 'url' ),
			array( 'hx_campaign', 'camp_bar', 'Afișează bara de campanie sub header', 'checkbox' ),
			array( 'hx_campaign', 'camp_badge', 'Text pe etichetele de preț (gol = numele campaniei)', 'text' ),

			array( 'hx_footer', 'newsletter_shortcode', 'Shortcode formular newsletter (ex. Contact Form 7)', 'text' ),
			array( 'hx_footer', 'social_facebook', 'Facebook (URL pagină)', 'url' ),
			array( 'hx_footer', 'social_instagram', 'Instagram (URL profil)', 'url' ),
			array( 'hx_footer', 'social_youtube', 'YouTube (URL canal, opțional)', 'url' ),
			array( 'hx_footer', 'social_tiktok', 'TikTok (URL profil, opțional)', 'url' ),
			array( 'hx_footer', 'newsletter_text', 'Text newsletter', 'text' ),
			array( 'hx_footer', 'company_line', 'Linie copyright', 'text' ),
			array( 'hx_footer', 'payments_line', 'Metode de plată (text)', 'text' ),

			array( 'hx_company', 'company_name', 'Denumire societate', 'text' ),
			array( 'hx_company', 'company_cui', 'CUI / CIF', 'text' ),
			array( 'hx_company', 'company_reg', 'Nr. Registrul Comerțului', 'text' ),
			array( 'hx_company', 'company_address', 'Sediu social', 'text' ),
			array( 'hx_company', 'company_capital', 'Capital social (opțional)', 'text' ),
			array( 'hx_company', 'company_bank', 'Bancă (opțional, pentru plata prin transfer)', 'text' ),
			array( 'hx_company', 'company_iban', 'IBAN (opțional)', 'text' ),
			array( 'hx_company', 'company_courier_fee', 'Taxă curier sub pragul de transport gratuit (text)', 'text' ),
			array( 'hx_company', 'legal_updated', 'Pagini legale: data ultimei actualizări (gol = data generării)', 'text' ),
			array( 'hx_company', 'og_image', 'Imagine pentru distribuire pe rețele sociale (1200×630; gol = logo)', 'image' ),
		);

		foreach ( $fields as $f ) {
			list( $section, $key, $label, $type ) = $f;
			$choices = $f[4] ?? null;

			$sanitize = 'sanitize_text_field';
			if ( 'textarea' === $type ) {
				$sanitize = 'wp_kses_post';
			} elseif ( 'url' === $type ) {
				$sanitize = 'esc_url_raw';
			} elseif ( 'number' === $type || 'image' === $type ) {
				$sanitize = 'absint';
			} elseif ( 'checkbox' === $type ) {
				$sanitize = 'wp_validate_boolean';
			} elseif ( 'select' === $type ) {
				$sanitize = function ( $v ) use ( $choices ) {
					return array_key_exists( $v, $choices ) ? $v : array_key_first( $choices );
				};
			}

			$wp->add_setting( $key, array( 'default' => $d[ $key ] ?? '', 'sanitize_callback' => $sanitize, 'transport' => 'refresh' ) );

			if ( 'image' === $type ) {
				$wp->add_control( new WP_Customize_Media_Control( $wp, $key, array( 'section' => $section, 'label' => $label, 'mime_type' => 'image' ) ) );
				continue;
			}
			$args = array( 'section' => $section, 'label' => $label, 'type' => $type );
			if ( 'select' === $type ) {
				$args['choices'] = $choices;
			}
			$wp->add_control( $key, $args );
		}
	}
);
