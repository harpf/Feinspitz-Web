<?php
/**
 * Feinspitz — Shop & Kategorie-Archive (feature/shop-archive).
 *
 * Registriert die shop-* Block-Patterns, die von templates/archive-product.html
 * und templates/taxonomy-product_cat.html referenziert werden, und passt einige
 * WooCommerce-Archiv-Ausgaben an, damit die BOLD gestalteten Kopfbereiche des
 * Themes den Titel/Description besitzen (statt der Standard-WooCommerce-Ausgabe).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php).
 * Die Pattern-Dateien liegen in theme/feinspitz/patterns/shop-*.php und werden
 * hier BEWUSST manuell registriert (analog zu inc/homepage.php), damit die
 * Registrierung nachvollziehbar an einer Stelle gebündelt ist. Die Pattern-
 * Dateien enthalten daher KEINEN Datei-Header — reines Block-Markup mit
 * übersetzbaren Strings (Textdomain feinspitz).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern-Kategorie + Shop-Patterns registrieren.
 */
add_action( 'init', function () {

	// Wird auch von inc/homepage.php registriert; erneuter Aufruf ist idempotent
	// und hält feature/shop-archive unabhängig lauffähig.
	register_block_pattern_category(
		'feinspitz',
		array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
	);

	// Slug (ohne Präfix) => Anzeigetitel (übersetzbar).
	$patterns = array(
		'shop-archive-header'  => __( 'Shop: Kopfbereich', 'feinspitz' ),
		'shop-category-header' => __( 'Shop: Kategorie-Kopf', 'feinspitz' ),
		'shop-filter-flags'    => __( 'Shop: Flag-Filter (histamingeprüft/vegan/alkoholfrei)', 'feinspitz' ),
		'shop-grid'            => __( 'Shop: Produkt-Grid (Sortierung + Pagination)', 'feinspitz' ),
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

/**
 * Der Produkt-Grid rendert über den WooCommerce-"Classic Template"-Block
 * (woocommerce/legacy-template), der Ergebnis-Zähler, Sortierung, Loop und
 * Pagination der Haupt-Query ausgibt. Damit unsere eigenen, BOLD gestalteten
 * Kopfbereiche (shop-archive-header / shop-category-header) den Titel und die
 * Kategorie-Beschreibung besitzen, unterdrücken wir hier die Standard-Ausgaben
 * von WooCommerce — sonst erschiene der Titel doppelt.
 */
add_action( 'init', function () {

	if ( ! function_exists( 'is_woocommerce' ) ) {
		return; // WooCommerce nicht aktiv — nichts zu tun.
	}

	// Standard-<h1> ("Shop" bzw. Kategoriename) der Archiv-Vorlage abschalten.
	add_filter( 'woocommerce_show_page_title', '__return_false' );

	// Standard-Beschreibungen (Shop-Seiteninhalt + Kategorie-Beschreibung)
	// abschalten — wir zeigen die Kategorie-Beschreibung im eigenen Kopf.
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
}, 99 );
