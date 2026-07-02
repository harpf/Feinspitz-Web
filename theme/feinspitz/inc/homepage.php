<?php
/**
 * Feinspitz — Startseite (feature/homepage).
 *
 * Registriert die Block-Pattern-Kategorie "feinspitz" und die home-* Patterns,
 * die von templates/front-page.html referenziert werden.
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php).
 * Die Pattern-Dateien liegen in theme/feinspitz/patterns/home-*.php und werden
 * hier BEWUSST manuell registriert (statt über die WP-Core-Auto-Registrierung),
 * damit die Registrierung nachvollziehbar an einer Stelle gebündelt ist. Die
 * Pattern-Dateien enthalten daher KEINEN Datei-Header — reines Block-Markup mit
 * übersetzbaren Strings (Textdomain feinspitz).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern-Kategorie registrieren (sprachunabhängig → init genügt).
 */
add_action( 'init', function () {
	register_block_pattern_category(
		'feinspitz',
		array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
	);
} );

/**
 * Startseiten-Patterns registrieren.
 *
 * WICHTIG (i18n): Die Patterns enthalten übersetzbare Strings (esc_html_e), die
 * beim Registrieren via ob_start()/include AUSGEFÜHRT und im Content eingefroren
 * werden. Deshalb NICHT auf `init` registrieren — dort hat Polylang die Sprache
 * der Anfrage noch nicht gesetzt, und die Strings würden für ALLE Sprachen in der
 * Standardsprache (DE) eingefroren. Auf `template_redirect` steht die aktuelle
 * Sprache fest (und es ist vor dem Template-Rendern) → korrekte Übersetzung je
 * Sprache. Läuft nur im Frontend (fürs Editieren sind die Patterns unkritisch).
 */
add_action( 'template_redirect', function () {
	$patterns = array(
		'home-hero'       => __( 'Startseite: Hero', 'feinspitz' ),
		'home-featured'   => __( 'Startseite: Ausgewählte Produkte', 'feinspitz' ),
		'home-categories' => __( 'Startseite: Kategorien-Teaser', 'feinspitz' ),
		'home-story'      => __( 'Startseite: Über uns / Story', 'feinspitz' ),
		'home-cta'        => __( 'Startseite: Weinproben & Catering', 'feinspitz' ),
	);

	foreach ( $patterns as $slug => $title ) {
		$file = get_template_directory() . '/patterns/' . $slug . '.php';

		if ( ! is_readable( $file ) ) {
			continue;
		}

		// Pattern-Datei ausführen (übersetzt Strings in der aktuellen Sprache).
		ob_start();
		include $file;
		$content = ob_get_clean();

		register_block_pattern(
			'feinspitz/' . $slug,
			array(
				'title'      => $title,
				'categories' => array( 'feinspitz' ),
				'content'    => $content,
				'inserter'   => true,
			)
		);
	}
}, 1 );

/**
 * Shortcode [feinspitz_featured] — ausgewählte Produkte als Karten-Grid.
 *
 * Ersetzt den WooCommerce-[products]-Shortcode, der im registrierten Pattern
 * nicht zuverlässig verarbeitet wurde (er erschien als literaler Text). Dieser
 * Shortcode rendert pro Request über wc_get_products() und ist damit robust und
 * sprachneutral (Weinnamen). Bleibt leer, solange keine Produkte vorhanden sind.
 *
 * @param array $atts Attribute: limit (Standard 4).
 * @return string
 */
function feinspitz_render_featured( $limit = 4 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$products = wc_get_products(
		array(
			'status'     => 'publish',
			'limit'      => max( 1, (int) $limit ),
			'orderby'    => 'popularity',
			'order'      => 'DESC',
			'visibility' => 'catalog',
		)
	);
	if ( empty( $products ) ) {
		return '';
	}

	$items = '';
	foreach ( $products as $product ) {
		$items .= sprintf(
			'<li class="feinspitz-featured__item"><a class="feinspitz-featured__link" href="%1$s">'
			. '<span class="feinspitz-featured__media">%2$s</span>'
			. '<span class="feinspitz-featured__name">%3$s</span>'
			. '<span class="feinspitz-featured__price">%4$s</span></a></li>',
			esc_url( get_permalink( $product->get_id() ) ),
			$product->get_image( 'woocommerce_thumbnail' ),
			esc_html( $product->get_name() ),
			wp_kses_post( $product->get_price_html() )
		);
	}

	return '<ul class="feinspitz-featured">' . $items . '</ul>';
}

add_shortcode( 'feinspitz_featured', function ( $atts ) {
	$atts = shortcode_atts( array( 'limit' => 4 ), $atts, 'feinspitz_featured' );
	return feinspitz_render_featured( $atts['limit'] );
} );

/**
 * Styles für das Featured-Grid — an das Theme-Stylesheet gehängt.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-featured{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(2,1fr);gap:clamp(1rem,2.5vw,1.75rem)}
@media (min-width:900px){.feinspitz-featured{grid-template-columns:repeat(4,1fr)}}
.feinspitz-featured__link{display:flex;flex-direction:column;height:100%;text-decoration:none;color:inherit}
.feinspitz-featured__media{display:block;aspect-ratio:4/5;overflow:hidden;border-radius:14px;background:rgba(0,0,0,.04)}
.feinspitz-featured__media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
.feinspitz-featured__link:hover .feinspitz-featured__media img{transform:scale(1.04)}
.feinspitz-featured__name{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:.7rem 0 .15rem;font-family:var(--wp--preset--font-family--heading);font-size:1.02rem;line-height:1.25;min-height:2.5em}
.feinspitz-featured__price{font-weight:600;color:var(--wp--preset--color--wine)}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	}
}, 21 );
