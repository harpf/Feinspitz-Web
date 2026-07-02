<?php
/**
 * Feinspitz - Warenkorb & Kasse (feature/cart-checkout).
 *
 * Registriert die checkout-* Block-Patterns, die von templates/cart.html und
 * templates/checkout.html referenziert werden (Bold-Kopfzeilen, Trust-/USP-Leiste,
 * leerer-Warenkorb-Zustand). Analog zu inc/homepage.php werden die Pattern-Dateien
 * BEWUSST manuell registriert (statt Core-Auto-Registrierung), damit die
 * Registrierung nachvollziehbar an einer Stelle gebündelt ist. Die Pattern-Dateien
 * unter theme/feinspitz/patterns/checkout-*.php enthalten daher KEINEN Datei-Header -
 * reines Block-Markup mit übersetzbaren Strings (Textdomain feinspitz).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout-Patterns registrieren.
 *
 * Die Pattern-Kategorie "feinspitz" wird ggf. bereits von inc/homepage.php
 * registriert; wir registrieren sie hier defensiv (idempotent), damit die
 * Cart/Checkout-Patterns auch unabhängig von der Ladereihenfolge eine Kategorie
 * haben.
 */
add_action( 'init', function () {

	$registry = WP_Block_Pattern_Categories_Registry::get_instance();
	if ( ! $registry->is_registered( 'feinspitz' ) ) {
		register_block_pattern_category(
			'feinspitz',
			array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
		);
	}

	// Slug (ohne Präfix) => Anzeigetitel (übersetzbar).
	$patterns = array(
		'checkout-cart-header'     => __( 'Kasse: Warenkorb-Kopf', 'feinspitz' ),
		'checkout-checkout-header' => __( 'Kasse: Kassen-Kopf', 'feinspitz' ),
		'checkout-trust'           => __( 'Kasse: Trust-/USP-Leiste', 'feinspitz' ),
		'checkout-empty-cart'      => __( 'Kasse: Leerer Warenkorb', 'feinspitz' ),
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
