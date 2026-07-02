<?php
/**
 * Feinspitz — Einzelprodukt (feature/product-single).
 *
 * Registriert die product-* Block-Patterns, die von
 * templates/single-product.html referenziert werden, sowie einen Shortcode
 * für die Flag-Badges (histamingeprüft/vegan/alkoholfrei) und deren Styling.
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php).
 * Die Pattern-Dateien liegen in theme/feinspitz/patterns/product-*.php und
 * werden hier BEWUSST manuell registriert (wie bei inc/homepage.php), damit
 * die Registrierung nachvollziehbar an einer Stelle gebündelt ist. Die
 * Pattern-Dateien enthalten daher KEINEN Datei-Header — reines Block-Markup
 * mit übersetzbaren Strings (Textdomain feinspitz).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flag-Tags: Product-Tag-Slug => Anzeige-Label (übersetzbar).
 *
 * Auf dem Server als WooCommerce-Product-Tags mit genau diesen Slugs vorhanden
 * (siehe Content-Migration). Ein Badge wird NUR gezeigt, wenn das Produkt das
 * jeweilige Tag trägt.
 *
 * @return array<string,string>
 */
function feinspitz_product_flag_tags() {
	return array(
		'histamingeprueft' => __( 'Histamingeprüft', 'feinspitz' ),
		'vegan'            => __( 'Vegan', 'feinspitz' ),
		'alkoholfrei'      => __( 'Alkoholfrei', 'feinspitz' ),
	);
}

/**
 * Shortcode [feinspitz_product_badges] — rendert die Flag-Badges des aktuellen
 * Produkts. Läuft im Single-Product-Kontext (globales $product) und wird von
 * der Pattern-Datei product-showcase.php eingebunden.
 */
add_shortcode( 'feinspitz_product_badges', function () {
	// Nur sinnvoll, wenn WooCommerce aktiv ist.
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$product_id = $product->get_id();
	$badges     = '';

	foreach ( feinspitz_product_flag_tags() as $slug => $label ) {
		if ( has_term( $slug, 'product_tag', $product_id ) ) {
			$badges .= sprintf(
				'<span class="feinspitz-flag feinspitz-flag--%1$s" role="listitem">%2$s</span>',
				esc_attr( $slug ),
				esc_html( $label )
			);
		}
	}

	if ( '' === $badges ) {
		return '';
	}

	return sprintf(
		'<div class="feinspitz-flags" role="list" aria-label="%1$s">%2$s</div>',
		esc_attr__( 'Produkt-Eigenschaften', 'feinspitz' ),
		$badges
	);
} );

/**
 * Badge-Styling (Theme-Tokens: wine/gold/sage/cream, runde Pillen).
 *
 * Wird an das in functions.php registrierte Theme-Style-Handle angehängt, damit
 * theme.json/style.css unangetastet bleiben.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
	.feinspitz-flags{display:flex;flex-wrap:wrap;gap:.5rem;margin:.25rem 0 1.25rem}
	.feinspitz-flag{display:inline-flex;align-items:center;font-family:var(--wp--preset--font-family--body);font-size:.75rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;line-height:1;padding:.5rem .9rem;border-radius:999px}
	.feinspitz-flag--histamingeprueft{background:var(--wp--preset--color--wine);color:var(--wp--preset--color--contrast)}
	.feinspitz-flag--vegan{background:var(--wp--preset--color--sage);color:var(--wp--preset--color--base)}
	.feinspitz-flag--alkoholfrei{background:var(--wp--preset--color--gold);color:var(--wp--preset--color--base)}';

	wp_add_inline_style( 'feinspitz-style', $css );
}, 20 );

/**
 * Pattern-Kategorie sicherstellen + product-* Patterns registrieren.
 */
add_action( 'init', function () {

	// Kategorie "feinspitz" wird i. d. R. bereits in inc/homepage.php registriert;
	// defensiv nachziehen, falls diese Datei fehlt.
	if (
		class_exists( 'WP_Block_Pattern_Categories_Registry' )
		&& ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'feinspitz' )
	) {
		register_block_pattern_category(
			'feinspitz',
			array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
		);
	}

	// Slug (ohne Präfix) => Anzeigetitel (übersetzbar).
	$patterns = array(
		'product-showcase' => __( 'Produkt: Galerie & Kaufbereich', 'feinspitz' ),
		'product-details'  => __( 'Produkt: Beschreibung & Details', 'feinspitz' ),
		'product-related'  => __( 'Produkt: Verwandte Produkte', 'feinspitz' ),
	);

	foreach ( $patterns as $slug => $title ) {
		$file = get_template_directory() . '/patterns/' . $slug . '.php';

		if ( ! is_readable( $file ) ) {
			continue;
		}

		// Pattern-Datei ausführen (übersetzt Strings) und Markup einfangen.
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
} );
