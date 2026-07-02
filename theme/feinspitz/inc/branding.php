<?php
/**
 * Feinspitz - Branding, Header & Footer (refine/branding-header-footer).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Branding-Branch. Sie stellt bereit:
 *
 *  - Shortcode [feinspitz_logo]  - rendert die Bold-Wortmarke (assets/logo.svg) als
 *    <img> mit Pfad aus get_template_directory_uri(), verlinkt auf die Startseite.
 *    Bewusst als Shortcode (statt rohem <?php … ?> in parts/header.html): Block-Theme-
 *    Template-Parts sind statisches HTML und führen KEIN PHP aus. Der wp:shortcode-Block
 *    ist der etablierte, funktionierende Weg für dynamische Ausgaben in den Parts
 *    (siehe [pll_languages] in inc/i18n.php). Kein core/site-logo, da der Server
 *    SVG-Uploads in die Mediathek blockt.
 *
 *  - Shortcode [feinspitz_cart]  - kompaktes Warenkorb-Icon, verlinkt auf die
 *    WooCommerce-Cart-URL (englischer Slug /cart/), mit optionalem Mengen-Badge.
 *
 *  - Auf .feinspitz-header / .feinspitz-footer gescopte Inline-CSS, an das in
 *    functions.php registrierte Theme-Stylesheet gehängt (wp_add_inline_style),
 *    damit style.css und theme.json unangetastet bleiben (Phase-0-Dateien):
 *    schlanker, klebriger (sticky) Header und aufgeräumter, niedriger Footer.
 *
 * Textdomain: feinspitz.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logo-Wortmarke als <img> ausgeben (verlinkt auf die Startseite).
 *
 * @param array $atts Shortcode-Attribute:
 *                    - class  : zusätzliche CSS-Klasse(n) für den Link.
 *                    - height : optionale feste CSS-Höhe (z. B. "38px").
 * @return string
 */
function feinspitz_logo_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'class'  => '',
			'height' => '',
		),
		$atts,
		'feinspitz_logo'
	);

	$src   = esc_url( get_template_directory_uri() . '/assets/logo.svg' );
	$home  = esc_url( home_url( '/' ) );
	$label = esc_attr__( 'Feinspitz - zur Startseite', 'feinspitz' );
	$alt   = esc_attr__( 'Feinspitz - Weine & Genuss', 'feinspitz' );
	$class = $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '';
	$style = $atts['height'] ? sprintf( ' style="height:%s"', esc_attr( $atts['height'] ) ) : '';

	return sprintf(
		'<a class="feinspitz-logo-link%1$s" href="%2$s" rel="home" aria-label="%3$s"><img class="feinspitz-logo" src="%4$s" alt="%5$s" width="300" height="72" decoding="async"%6$s></a>',
		$class,
		$home,
		$label,
		$src,
		$alt,
		$style
	);
}

/**
 * Kompaktes Warenkorb-Icon mit Link auf die Cart-Seite und optionalem Mengen-Badge.
 *
 * @return string
 */
function feinspitz_cart_shortcode() {
	$url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

	$count = 0;
	if ( function_exists( 'WC' ) && WC() && isset( WC()->cart ) && WC()->cart ) {
		$count = WC()->cart->get_cart_contents_count();
	}

	$badge = $count > 0
		? sprintf( '<span class="feinspitz-cart__count" aria-hidden="true">%s</span>', esc_html( $count ) )
		: '';

	$label = ( $count > 0 )
		/* translators: %s: number of items in cart. */
		? esc_attr( sprintf( _n( 'Warenkorb, %s Artikel', 'Warenkorb, %s Artikel', $count, 'feinspitz' ), $count ) )
		: esc_attr__( 'Warenkorb', 'feinspitz' );

	$icon = '<svg class="feinspitz-cart__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
		. '<path d="M5.2 8h13.6l-1.05 10.4A2 2 0 0 1 15.76 20.2H8.24a2 2 0 0 1-1.99-1.8L5.2 8Z"/>'
		. '<path d="M9 8V6.6a3 3 0 0 1 6 0V8"/>'
		. '</svg>';

	return sprintf(
		'<a class="feinspitz-cart" href="%1$s" aria-label="%2$s">%3$s%4$s</a>',
		esc_url( $url ),
		$label,
		$icon,
		$badge
	);
}

/**
 * Sprachbewusster Footer-Inhalt (4 Spalten).
 *
 * Block-Template-Parts (parts/footer.html) führen kein PHP aus, daher waren die
 * Footer-Texte/-Links zuvor hartcodiert deutsch und zeigten sich auch auf /en/ in
 * Deutsch (inkl. eines core/navigation-Blocks mit literalen deutschen Labels).
 * Dieser Shortcode rendert Überschriften, Tagline und Quicklinks pro Sprache
 * (analog zur Header-Navigation) und nutzt die vorhandenen Footer-CSS-Klassen.
 *
 * @return string
 */
function feinspitz_footer_shortcode() {
	$is_en = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();

	$t = $is_en
		? array(
			'tagline'  => 'Histamine-tested wines for more trust and enjoyment - for a better quality of life.',
			'contact'  => 'Contact',
			'discover' => 'Discover',
			'language' => 'Language',
		)
		: array(
			'tagline'  => 'Histamingeprüfte Weine mit mehr Vertrauen und Genuss - für mehr Lebensqualität.',
			'contact'  => 'Kontakt',
			'discover' => 'Entdecken',
			'language' => 'Sprache',
		);

	// Quicklinks aus der sprachbewussten Hauptnavigation ableiten.
	$links = function_exists( 'feinspitz_nav_items' ) ? feinspitz_nav_items() : array();
	$lis   = '';
	foreach ( $links as $item ) {
		list( $label, $url ) = $item;
		$lis .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}

	$logo     = function_exists( 'feinspitz_logo_shortcode' ) ? feinspitz_logo_shortcode() : '';
	$switcher = function_exists( 'feinspitz_language_switcher' ) ? feinspitz_language_switcher() : '';

	// Rechtslinks (Impressum/AGB/Kontakt sprachlokalisiert; Datenschutz/Cookie/
	// Liefer nur DE; Sitemap = automatische WP-XML-Sitemap).
	$purl = function ( $slug, $fallback ) {
		return function_exists( 'feinspitz_nav_page_url' ) ? feinspitz_nav_page_url( $slug, $fallback ) : home_url( $fallback );
	};
	$legal_items = $is_en
		? array(
			array( 'Imprint', $purl( 'about', '/about/' ) ),
			array( 'Terms', $purl( 'agb', '/agb/' ) ),
			array( 'Delivery & Payment', home_url( '/liefer-und-zahlungsbedingungen/' ) ),
			array( 'Privacy', home_url( '/datenschutzerklaerung/' ) ),
			array( 'Cookie Policy', home_url( '/cookie-richtlinie/' ) ),
			array( 'Contact', $purl( 'kontakt', '/kontakt/' ) ),
			array( 'Sitemap', home_url( '/wp-sitemap.xml' ) ),
		)
		: array(
			array( 'Impressum', $purl( 'about', '/about/' ) ),
			array( 'AGB', $purl( 'agb', '/agb/' ) ),
			array( 'Liefer- und Zahlungsbedingungen', home_url( '/liefer-und-zahlungsbedingungen/' ) ),
			array( 'Datenschutz', home_url( '/datenschutzerklaerung/' ) ),
			array( 'Cookie-Richtlinie', home_url( '/cookie-richtlinie/' ) ),
			array( 'Kontakt', $purl( 'kontakt', '/kontakt/' ) ),
			array( 'Sitemap', home_url( '/wp-sitemap.xml' ) ),
		);
	$legal = '';
	foreach ( $legal_items as $li ) {
		$legal .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $li[1] ), esc_html( $li[0] ) );
	}

	ob_start();
	?>
<div class="wp-block-columns feinspitz-footer__cols is-layout-flex">
	<div class="wp-block-column" style="flex-basis:34%">
		<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="feinspitz-footer__tag has-small-font-size"><?php echo esc_html( $t['tagline'] ); ?></p>
	</div>
	<div class="wp-block-column">
		<h2 class="wp-block-heading has-small-font-size"><?php echo esc_html( $t['contact'] ); ?></h2>
		<p class="feinspitz-footer__contact">Feinspitz<br>Bahnhofstrasse 80<br>CH-8902 Urdorf<br><a href="tel:+41765888902">+41 76 588 89 02</a></p>
	</div>
	<div class="wp-block-column">
		<h2 class="wp-block-heading has-small-font-size"><?php echo esc_html( $t['discover'] ); ?></h2>
		<ul class="feinspitz-footer__links"><?php echo $lis; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
	</div>
	<div class="wp-block-column">
		<h2 class="wp-block-heading has-small-font-size"><?php echo esc_html( $t['language'] ); ?></h2>
		<?php echo $switcher; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<ul class="feinspitz-footer__legal"><?php echo $legal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
	<?php
	return ob_get_clean();
}

/**
 * Shortcodes registrieren.
 */
add_action( 'init', function () {
	add_shortcode( 'feinspitz_logo', 'feinspitz_logo_shortcode' );
	add_shortcode( 'feinspitz_cart', 'feinspitz_cart_shortcode' );
	add_shortcode( 'feinspitz_footer', 'feinspitz_footer_shortcode' );
} );

/**
 * Gescopte Styles für Header & Footer - an das Theme-Stylesheet gehängt, damit
 * style.css / theme.json (Phase 0) unberührt bleiben.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = <<<CSS
/* ---------- Header: schlank, klebrig (sticky) ---------- */
.wp-site-blocks > header.wp-block-template-part{position:sticky;top:0;z-index:100}
.feinspitz-header{
	padding-top:.6rem;padding-bottom:.6rem;
	border-bottom:1px solid rgba(201,162,75,.28);
	box-shadow:0 2px 14px rgba(14,11,8,.35);
}
.feinspitz-header__bar{gap:1.25rem}
.feinspitz-logo-link{display:inline-flex;align-items:center;line-height:0;flex:0 0 auto}
.feinspitz-logo{height:clamp(32px,4.4vw,44px);width:auto;display:block}
.feinspitz-header__nav{gap:clamp(.8rem,2vw,1.75rem)}

/* Navigation kompakt */
.feinspitz-header .wp-block-navigation{
	font-family:var(--wp--preset--font-family--body);
	font-size:.82rem;letter-spacing:.11em;text-transform:uppercase;font-weight:600
}
.feinspitz-header .wp-block-navigation a{text-decoration:none;color:var(--wp--preset--color--contrast)}
.feinspitz-header .wp-block-navigation .wp-block-navigation-item__content{padding:.3rem .1rem}
.feinspitz-header .wp-block-navigation a:hover,
.feinspitz-header .wp-block-navigation a:focus{color:var(--wp--preset--color--gold)}
.feinspitz-header .wp-block-navigation__responsive-container.is-menu-open{
	background:var(--wp--preset--color--base)
}

/* Warenkorb-Icon */
.feinspitz-cart{position:relative;display:inline-flex;align-items:center;justify-content:center;
	color:var(--wp--preset--color--contrast);text-decoration:none;transition:color .15s ease}
.feinspitz-cart:hover,.feinspitz-cart:focus{color:var(--wp--preset--color--gold)}
.feinspitz-cart__icon{width:22px;height:22px;display:block;fill:none;stroke:currentColor;
	stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
.feinspitz-cart__count{position:absolute;top:-7px;right:-9px;min-width:16px;height:16px;padding:0 4px;
	border-radius:999px;background:var(--wp--preset--color--wine);color:var(--wp--preset--color--contrast);
	font-family:var(--wp--preset--font-family--body);font-size:.62rem;font-weight:700;line-height:16px;
	text-align:center;box-shadow:0 0 0 2px var(--wp--preset--color--base)}

/* Sprachumschalter etwas absetzen (Grund-Styles kommen aus inc/i18n.php) */
.feinspitz-header .feinspitz-lang-switcher{margin-left:.15rem;
	padding-left:.85rem;border-left:1px solid rgba(246,241,231,.22)}

/* ---------- Footer: niedrig, ruhig, mehrspaltig ---------- */
.feinspitz-footer{
	padding-top:clamp(2rem,4vw,3rem);padding-bottom:1.25rem;
	border-top:3px solid var(--wp--preset--color--wine)
}
.feinspitz-footer__cols{gap:clamp(1.5rem,4vw,3rem)}
.feinspitz-footer .wp-block-column{min-width:170px}
.feinspitz-footer .wp-block-heading{
	font-size:.78rem;letter-spacing:.16em;text-transform:uppercase;font-weight:600;
	color:var(--wp--preset--color--gold);margin:0 0 .85rem
}
.feinspitz-footer .feinspitz-logo{height:36px}
.feinspitz-footer__tag{opacity:.82;max-width:34ch;margin:.9rem 0 0}
.feinspitz-footer__contact{line-height:1.9;margin:0}
.feinspitz-footer p,.feinspitz-footer a{color:var(--wp--preset--color--contrast)}
.feinspitz-footer a{text-decoration:none;opacity:.85;transition:opacity .15s ease,color .15s ease}
.feinspitz-footer a:hover,.feinspitz-footer a:focus{opacity:1;color:var(--wp--preset--color--gold)}
.feinspitz-footer .wp-block-navigation{font-size:.92rem}
.feinspitz-footer .wp-block-navigation ul{gap:.45rem}
.feinspitz-footer__links{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem;font-size:.92rem}
.feinspitz-footer__links a{display:inline-block}
.feinspitz-footer__legal{list-style:none;display:flex;flex-wrap:wrap;justify-content:center;gap:.35rem 0;margin:clamp(1.5rem,4vw,2.25rem) 0 0;padding:0;font-size:.76rem;opacity:.72}
.feinspitz-footer__legal li:not(:last-child)::after{content:"|";margin:0 .6rem;opacity:.4}
.feinspitz-footer__legal a{opacity:1}
.feinspitz-footer .feinspitz-lang-switcher__list{justify-content:flex-start}
.feinspitz-footer__bottom{
	margin-top:clamp(1.5rem,4vw,2.5rem);padding-top:1.1rem;
	border-top:1px solid rgba(246,241,231,.14);
	font-size:.78rem;opacity:.7;text-align:center;margin-bottom:0
}

/* ---------- Mobile ---------- */
@media (max-width:781px){
	.feinspitz-header__nav{gap:.85rem}
	.feinspitz-footer__cols{gap:1.5rem}
}
CSS;

	// An das in functions.php registrierte Theme-Stylesheet hängen; Fallback,
	// falls das Handle (noch) nicht existiert.
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-branding-inline', false );
		wp_enqueue_style( 'feinspitz-branding-inline' );
		wp_add_inline_style( 'feinspitz-branding-inline', $css );
	}
}, 20 );
