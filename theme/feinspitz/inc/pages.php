<?php
/**
 * Feinspitz · Inhaltsseiten (Über uns, Kontakt, Weinproben, Catering, Rechtstexte).
 *
 * Besitzt (laut Implementierungsplan, Branch feature/content-pages):
 *   - theme/feinspitz/templates/page.html   (flexibles Seiten-Template)
 *   - theme/feinspitz/patterns/page-*.php    (wiederverwendbare Seiten-Patterns)
 *   - theme/feinspitz/inc/pages.php          (diese Datei)
 *
 * Die eigentlichen Pattern-Dateien liegen unter /patterns/ und werden von
 * WordPress ab 6.0 automatisch registriert (Header-basiert). Hier registrieren
 * wir nur die Pattern-Kategorie, unter der sie im Inserter erscheinen, sowie
 * eine Fallback-Registrierung, falls die Auto-Registrierung deaktiviert ist.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern-Kategorie für die Feinspitz-Inhaltsseiten registrieren.
 *
 * Alle page-*.php-Patterns referenzieren diese Kategorie in ihrem Header
 * (Categories: feinspitz). Titel/Beschreibung sind über die Textdomain
 * `feinspitz` übersetzbar.
 */
add_action( 'init', function () {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}

	register_block_pattern_category(
		'feinspitz',
		array(
			'label'       => _x( 'Feinspitz', 'Block pattern category', 'feinspitz' ),
			'description' => __( 'Seiten-Layouts für die Feinspitz-Inhaltsseiten.', 'feinspitz' ),
		)
	);
} );

/**
 * Fallback: Patterns explizit registrieren, falls die automatische
 * Registrierung von Theme-Patterns nicht greift (z. B. durch ein Plugin
 * deaktiviert). Idempotent · bereits registrierte Slugs werden übersprungen.
 *
 * WordPress liest die Header (Title, Slug, Categories, Description) beim
 * Auto-Load selbst; hier lesen wir sie im Bedarfsfall aus derselben Datei.
 */
add_action( 'init', function () {
	if (
		! function_exists( 'register_block_pattern' ) ||
		! class_exists( 'WP_Block_Patterns_Registry' )
	) {
		return;
	}

	$registry = WP_Block_Patterns_Registry::get_instance();
	$dir      = get_template_directory() . '/patterns';

	foreach ( glob( $dir . '/page-*.php' ) as $file ) {
		$headers = get_file_data(
			$file,
			array(
				'title'       => 'Title',
				'slug'        => 'Slug',
				'description' => 'Description',
				'categories'  => 'Categories',
				'keywords'    => 'Keywords',
				'postTypes'   => 'Post Types',
				'inserter'    => 'Inserter',
			)
		);

		if ( empty( $headers['slug'] ) || $registry->is_registered( $headers['slug'] ) ) {
			continue;
		}

		ob_start();
		include $file;
		$content = ob_get_clean();

		$properties = array(
			// translators laufen über die Theme-Textdomain; hier bewusst kein
			// Wrapping mit __(), da die Strings aus dem Datei-Header stammen.
			'title'      => $headers['title'],
			'content'    => $content,
			'categories' => array_filter( array_map( 'trim', explode( ',', $headers['categories'] ) ) ),
		);

		if ( ! empty( $headers['description'] ) ) {
			$properties['description'] = $headers['description'];
		}
		if ( ! empty( $headers['keywords'] ) ) {
			$properties['keywords'] = array_filter( array_map( 'trim', explode( ',', $headers['keywords'] ) ) );
		}
		if ( ! empty( $headers['postTypes'] ) ) {
			$properties['postTypes'] = array_filter( array_map( 'trim', explode( ',', $headers['postTypes'] ) ) );
		}
		if ( '' !== $headers['inserter'] ) {
			$properties['inserter'] = in_array( strtolower( $headers['inserter'] ), array( 'yes', 'true', '1' ), true );
		}

		register_block_pattern( $headers['slug'], $properties );
	}
}, 11 );
