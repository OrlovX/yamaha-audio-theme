<?php
/**
 * Cookie consent: the theme's own notice, no outside service.
 *
 * - A small inline script at the very top of <head>, before Google Tag Manager, tells Google's tags what
 *   this visitor allowed (Consent Mode v2). Everything optional is "denied" until a choice is stored in the
 *   hx_consent cookie; a stored choice is sent as an update on every page.
 * - The notice and the settings panel are drawn by assets/js/main.js and styled in main.css. The footer
 *   link "Setări cookie-uri" (and any element with data-hx-consent-open) opens the panel again.
 * - WooCommerce order attribution (the sbjs_* cookies) starts switched off; the script turns it on only
 *   after marketing consent. With the WP Consent API plugin active, the choice is passed to it as well.
 *
 * Cookie: hx_consent = "<version>.<analytics 0|1>.<marketing 0|1>.<unix time>", kept 180 days.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

function yamaha_audio_consent_cfg() {
	static $cfg = null;
	if ( null !== $cfg ) {
		return $cfg;
	}
	$policy = get_page_by_path( 'politica-cookies' );
	$cfg    = apply_filters(
		'yamaha_audio_consent_cfg',
		array(
			// Raise the version to ask every visitor again (a new category or a new tool that needs consent).
			'v'      => 1,
			'days'   => 180,
			'policy' => $policy && 'publish' === $policy->post_status ? get_permalink( $policy ) : '',
			// Hosts whose scripts must never load, wherever they are added from. CookieYes is still added from
			// inside the Google Tag Manager container: until that tag is removed there, its script is stopped
			// here so two notices never show. Once it is gone from GTM this can be an empty array.
			'block'  => array( 'cdn-cookieyes.com' ),
			'text'   => array(
				'title'   => 'Folosim cookie-uri',
				'text'    => 'Cele necesare țin coșul și contul în funcțiune. Cu acordul tău folosim și cookie-uri de analiză și de marketing (Google Analytics, Google Ads). Îți poți schimba alegerea oricând din „Setări cookie-uri”, în subsolul paginii.',
				'policy'  => 'Politica de cookie-uri',
				'all'     => 'Accept toate',
				'none'    => 'Doar necesare',
				'more'    => 'Personalizează',
				'save'    => 'Salvează alegerile',
				'close'   => 'Închide',
				'need'    => 'Necesare',
				'need_d'  => 'Coș, cont, securitate și alegerea ta privind cookie-urile. Mereu active.',
				'stats'   => 'Analiză',
				'stats_d' => 'Google Analytics: cum este folosit site-ul, în mod agregat.',
				'mkt'     => 'Marketing',
				'mkt_d'   => 'Google Ads și sursa comenzilor: măsurarea reclamelor și reclame relevante.',
			),
		)
	);
	return $cfg;
}

/**
 * Before anything else in <head>: the default (denied), the stored choice if there is one, and a small
 * object the theme script uses to send a new choice.
 */
add_action(
	'wp_head',
	function () {
		$cfg   = yamaha_audio_consent_cfg();
		$block = array_values( array_filter( array_map( 'strval', (array) $cfg['block'] ) ) );
		$js    = '(function(w,d){var V=' . (int) $cfg['v'] . ',B=' . wp_json_encode( $block ) . ';w.dataLayer=w.dataLayer||[];function g(){w.dataLayer.push(arguments)}'
			. 'function rd(){var m=d.cookie.match(/(?:^|; )hx_consent=([^;]*)/);if(!m){return null}var p=decodeURIComponent(m[1]).split(".");return p[0]===String(V)?{a:p[1]==="1",m:p[2]==="1",t:+p[3]||0}:null}'
			. 'function st(s){var a=s&&s.a?"granted":"denied",k=s&&s.m?"granted":"denied";return{ad_storage:k,ad_user_data:k,ad_personalization:k,analytics_storage:a,personalization_storage:k,functionality_storage:"granted",security_storage:"granted"}}'
			. 'var s=null;try{s=rd()}catch(e){}'
			. 'g("consent","default",st(null));g("set","ads_data_redaction",!(s&&s.m));if(s){g("consent","update",st(s))}'
			. 'w.hxConsent={v:V,state:s,signal:function(n){g("consent","update",st(n));g("set","ads_data_redaction",!n.m)}};'
			. 'if(B.length){var P=w.HTMLScriptElement&&w.HTMLScriptElement.prototype,D=P&&Object.getOwnPropertyDescriptor(P,"src"),no=function(u){u=String(u||"");for(var i=0;i<B.length;i++){if(u.indexOf("//"+B[i]+"/")>-1){return true}}return false};'
			. 'if(D&&D.set){Object.defineProperty(P,"src",{configurable:true,enumerable:D.enumerable,get:function(){return D.get.call(this)},set:function(u){if(no(u)){this.setAttribute("data-hx-blocked",u);return}D.set.call(this,u)}});'
			. 'var S=P.setAttribute;P.setAttribute=function(n,u){if(String(n).toLowerCase()==="src"&&no(u)){return S.call(this,"data-hx-blocked",u)}return S.apply(this,arguments)}}}'
			. '})(window,document);';
		echo '<script id="hx-consent-js" data-cfasync="false">' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	0
);

/**
 * Texts and settings for the notice, handed to the theme script.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$cfg = yamaha_audio_consent_cfg();
		wp_localize_script(
			'yamaha-audio-main',
			'hxConsentCfg',
			array(
				'days'   => (int) $cfg['days'],
				'policy' => (string) $cfg['policy'],
				'text'   => $cfg['text'],
			)
		);
	},
	21
);

// Order attribution waits for marketing consent: off in the page, switched on by the script afterwards.
add_filter( 'wc_order_attribution_allow_tracking', '__return_false', 99 );

// WP Consent API (when the plugin is active): this site asks before setting optional cookies.
add_filter(
	'wp_get_consent_type',
	function () {
		return 'optin';
	}
);
