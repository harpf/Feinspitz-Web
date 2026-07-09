<?php
/**
 * Feinspitz · Editor-Vorlagen & Produkt-Hilfe (feature/editor-templates).
 *
 * Besitzt (laut Design-Spec Baustein ②):
 *   - theme/feinspitz/inc/editor-templates.php        (diese Datei)
 *   - theme/feinspitz/patterns/editor-*.php           (Block-Pattern-Vorlagen)
 *
 * Ziel: Artikel schreiben (Ratgeber / Weinlexikon) und Produkte anlegen im
 * WordPress-Backend erleichtern. Alles läuft AUSSCHLIESSLICH im Admin
 * (is_admin()-Gates bzw. Admin-only Hooks) – KEINE Frontend-Änderung.
 *
 * Die Pattern-Dateien unter /patterns/editor-*.php tragen Datei-Header und
 * werden von WordPress ab 6.0 automatisch registriert. Hier stellen wir nur
 * die Pattern-Kategorie sicher und liefern eine idempotente Fallback-
 * Registrierung, falls die Auto-Registrierung deaktiviert ist – analog zu
 * inc/pages.php. Bewusst KEINE neuen gettext-msgids (i18n-Build stabil).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern-Kategorie „Feinspitz“ sicherstellen (idempotent – falls ein anderes
 * inc-Modul sie schon registriert hat, überschreibt dies nur die Metadaten).
 */
add_action( 'init', function () {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}

	// Nur das Label (bereits existierende msgid) setzen – bewusst KEINE
	// description, um keine neue gettext-msgid einzuführen. inc/pages.php
	// liefert die Beschreibung; register_block_pattern_category ist idempotent.
	register_block_pattern_category(
		'feinspitz',
		array(
			'label' => _x( 'Feinspitz', 'Block pattern category', 'feinspitz' ),
		)
	);
} );

/**
 * Fallback: editor-*.php-Patterns explizit registrieren, falls die
 * automatische Theme-Pattern-Registrierung nicht greift. Idempotent – bereits
 * registrierte Slugs werden übersprungen. Header werden aus derselben Datei
 * gelesen (kein Übersetzungs-Wrapping, Strings stammen aus dem Datei-Header).
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

	foreach ( glob( $dir . '/editor-*.php' ) as $file ) {
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

/**
 * Produkt-Hilfe: Checkliste als Meta-Box auf der Produkt-Bearbeitungsseite.
 *
 * Erscheint nur im Admin auf dem Post-Typ `product` (WooCommerce). Reiner
 * Hinweistext – kein Speichern, keine Datenänderung. Der ausgebende Callback
 * verwendet festen deutschen Text (keine neuen gettext-msgids).
 */
add_action( 'add_meta_boxes', function () {
	// Nur sinnvoll, wenn der WooCommerce-Produkt-Typ vorhanden ist.
	if ( ! post_type_exists( 'product' ) ) {
		return;
	}

	add_meta_box(
		'feinspitz_wein_checkliste',
		'Wein anlegen · Checkliste',
		'feinspitz_render_wein_checkliste',
		'product',
		'side',
		'high'
	);
} );

/**
 * Callback für die Produkt-Checkliste-Meta-Box.
 *
 * Nur Admin-Kontext (Meta-Boxen rendern ausschliesslich im Backend). Gibt eine
 * kurze, klar strukturierte deutsche Anleitung zum Anlegen eines Weins aus.
 */
function feinspitz_render_wein_checkliste() {
	?>
	<div class="feinspitz-wein-checkliste" style="font-size:13px;line-height:1.55">
		<p style="margin-top:0">So legst du einen Wein vollständig an:</p>
		<ul style="margin:0 0 10px 18px;padding:0;list-style:disc">
			<li><strong>Name</strong> – Produkttitel (z. B. „Pinot Noir 2021, Weingut …“)</li>
			<li><strong>Preis</strong> – im Feld „Produktdaten → Allgemein“ in CHF</li>
			<li><strong>Bild</strong> – „Produktbild“ setzen (rechte Spalte)</li>
			<li><strong>Kategorie</strong> – passende Wein-Kategorie wählen (z. B. Rotweine, Weissweine)</li>
			<li><strong>Flags</strong> – als <em>Schlagwort</em> setzen, falls zutreffend:
				<code>histamingeprueft</code>, <code>vegan</code>, <code>alkoholfrei</code></li>
			<li><strong>Attribute</strong> – unter „Produktdaten → Attribute“ aus den
				vorhandenen Listen wählen: Rebsorte, Weingut, Region, Süße, Jahrgang, Volumen</li>
		</ul>
		<p style="margin-bottom:0;color:#666">Tipp: Die Attribute sind globale Listen – Werte per
			Dropdown auswählen statt neu tippen, damit Filter und Wein-Finder sauber funktionieren.</p>
	</div>
	<?php
}
