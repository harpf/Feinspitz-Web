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
 * Pattern-Kategorie + Startseiten-Patterns registrieren.
 */
add_action( 'init', function () {

	register_block_pattern_category(
		'feinspitz',
		array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
	);

	// Slug (ohne Präfix) => Anzeigetitel (übersetzbar).
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
