<?php
/**
 * Feinspitz - Mehrsprachigkeit (feature/i18n-multilingual).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem i18n-Branch. Sie stellt den Sprachumschalter bereit:
 *
 *  - Shortcode [pll_languages] - rendert einen kompakten DE/EN-Umschalter über
 *    Polylangs pll_the_languages(). Wird in parts/header.html und parts/footer.html
 *    per core/shortcode-Block eingebunden. Bewusst als Shortcode (statt des nativen
 *    Polylang-Blocks), damit die Ausgabe deterministisch, voll gestaltbar und
 *    unabhängig von der Polylang-Block-Registrierung ist - und ohne Polylang
 *    sauber leer bleibt (kein Fatal).
 *
 *    Alternative (falls gewünscht): der native Block
 *    <!-- wp:polylang/language-switcher {"dropdown":false,"show_names":true} /-->
 *    kann den Shortcode-Block in den Parts ersetzen, sobald Polylang aktiv ist.
 *
 *  - Kleine, auf die Switcher-Klasse gescopte Inline-CSS (an das Theme-Stylesheet
 *    gehängt), damit style.css unangetastet bleibt (Phase-0-Datei).
 *
 * Textdomain: feinspitz.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * gettext-Locale an die Polylang-Sprache angleichen.
 *
 * Beobachtet: Polylang setzt zwar sein internes `curlang` (pll_current_language()
 * liefert 'en'), schaltet auf dem Frontend aber die WordPress-gettext-Locale NICHT
 * zuverlässig um → esc_html_e()/__() und WooCommerce liefern die deutschen Quell-
 * Strings, obwohl feinspitz-en_US.mo korrekt vorliegt.
 *
 * Fix: Auf Seiten, die Polylang als Englisch erkennt (z. B. /en/, Ratgeber, FAQ,
 * übersetzte Seiten), die Locale explizit auf en_US umschalten - VOR dem Rendern
 * (template_redirect, sehr früh), damit alle nachfolgend registrierten Patterns
 * und Ausgaben in Englisch erscheinen. Läuft nur im Frontend.
 *
 * Hinweis: Tiefe WooCommerce-Seiten (/en/produkt-kategorie/…) erkennt freies
 * Polylang mangels Produkt-Übersetzung als Deutsch → dort bleibt es (bewusst) DE.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || ! function_exists( 'pll_current_language' ) ) {
		return;
	}
	$slug = pll_current_language( 'slug' );
	if ( ! $slug ) {
		return;
	}
	$locale = pll_current_language( 'locale' );
	if ( ! $locale ) {
		return;
	}
	// Polylang setzt zwar die Locale (get_locale() == en_US), lädt aber die
	// Theme-Textdomain NICHT für die Sprache → __()/esc_html_e() liefern die
	// deutschen Quell-Strings. Daher die passende .mo IMMER explizit (mit vollem
	// Pfad) laden - unabhängig davon, ob die Locale schon stimmt.
	if ( get_locale() !== $locale ) {
		switch_to_locale( $locale );
	}
	$mo = get_template_directory() . '/languages/feinspitz-' . $locale . '.mo';
	if ( file_exists( $mo ) ) {
		unload_textdomain( 'feinspitz' );
		load_textdomain( 'feinspitz', $mo );
	}
}, 0 );

/**
 * Sprachumschalter-Markup erzeugen.
 *
 * Nutzt Polylangs pll_the_languages(). Ist Polylang nicht aktiv, wird ein leerer
 * String zurückgegeben (Seite bleibt funktionsfähig, nur ohne Umschalter).
 *
 * @param array $atts Shortcode-Attribute:
 *                    - display: 'slug' (DE/EN, Standard) oder 'name' (Deutsch/English).
 * @return string
 */
function feinspitz_language_switcher( $atts = array() ) {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'display' => 'slug',
		),
		$atts,
		'pll_languages'
	);

	$display_as = ( 'name' === $atts['display'] ) ? 'name' : 'slug';

	$items = pll_the_languages(
		array(
			'echo'                   => 0,
			'display_names_as'       => $display_as,
			'show_names'             => 1,
			'show_flags'             => 0,
			'hide_if_no_translation' => 0,
			'hide_current'           => 0,
		)
	);

	if ( empty( $items ) ) {
		return '';
	}

	return sprintf(
		'<nav class="feinspitz-lang-switcher" aria-label="%1$s"><ul class="feinspitz-lang-switcher__list">%2$s</ul></nav>',
		esc_attr__( 'Sprache wählen', 'feinspitz' ),
		$items
	);
}

/**
 * Shortcode [pll_languages] registrieren.
 */
add_action( 'init', function () {
	add_shortcode( 'pll_languages', 'feinspitz_language_switcher' );
} );

/**
 * Gescopte Styles für den Sprachumschalter - an das Theme-Stylesheet gehängt,
 * damit style.css (Phase 0) unberührt bleibt.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-lang-switcher__list{list-style:none;display:flex;flex-wrap:wrap;gap:0;margin:0;padding:0;align-items:center;font-size:.72rem;letter-spacing:.18em;text-transform:uppercase;font-weight:600;line-height:1}
.feinspitz-lang-switcher__list li{margin:0}
.feinspitz-lang-switcher__list a{text-decoration:none;color:inherit;opacity:.55;padding:0 .55rem;transition:opacity .15s ease}
.feinspitz-lang-switcher__list a:hover,.feinspitz-lang-switcher__list a:focus{opacity:1}
.feinspitz-lang-switcher__list li:first-child a{padding-left:0}
.feinspitz-lang-switcher__list li + li{border-left:1px solid currentColor}
.feinspitz-lang-switcher__list .current-lang a{opacity:1;color:var(--wp--preset--color--gold,currentColor)}
.wp-block-footer .feinspitz-lang-switcher__list,.feinspitz-lang-switcher[data-align="center"] .feinspitz-lang-switcher__list{justify-content:center}
';
	// An das in functions.php registrierte Theme-Stylesheet hängen; Fallback,
	// falls das Handle (noch) nicht existiert.
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-i18n-inline', false );
		wp_enqueue_style( 'feinspitz-i18n-inline' );
		wp_add_inline_style( 'feinspitz-i18n-inline', $css );
	}
}, 20 );

/**
 * REST-Brücke für Polylang (Sprachzuordnung & Verknüpfung).
 *
 * Das freie Polylang legt über REST zwar die Sprachen an (pll/v1/languages),
 * bietet aber KEINEN REST-Weg, um einzelnen Beiträgen/Seiten eine Sprache
 * zuzuweisen oder Übersetzungen zu verknüpfen (das kann sonst nur Polylang Pro).
 *
 * Diese Brücke registriert daher zwei REST-Felder auf post/page/product:
 *   - `lang`            : liest/setzt die Polylang-Sprache (Slug, z. B. "de"/"en").
 *   - `pll_translations`: liest/setzt die Übersetzungs-Verknüpfung ({slug: post_id}).
 *
 * Damit wird die Sprachzuordnung über wp/v2 (Cookie+Nonce als Admin) skriptbar -
 * genutzt von scripts/i18n/polylang-content.mjs. Die Felder existieren nur, wenn
 * Polylang aktiv ist; die Schreib-Callbacks erfordern reguläre Bearbeitungsrechte
 * (REST prüft das ohnehin), sind also nicht öffentlich missbrauchbar.
 */
add_action( 'rest_api_init', function () {
	if ( ! function_exists( 'pll_set_post_language' ) ) {
		return; // Polylang nicht aktiv - keine Brücke.
	}

	$types = array( 'post', 'page', 'product' );

	foreach ( $types as $type ) {
		register_rest_field(
			$type,
			'lang',
			array(
				'schema'          => array(
					'description' => 'Polylang language slug.',
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'get_callback'    => function ( $obj ) {
					if ( ! function_exists( 'pll_get_post_language' ) ) {
						return null;
					}
					$slug = pll_get_post_language( (int) $obj['id'], 'slug' );
					return $slug ? $slug : null;
				},
				'update_callback' => function ( $value, $post ) {
					if ( is_string( $value ) && '' !== $value ) {
						pll_set_post_language( $post->ID, sanitize_key( $value ) );
					}
					return true;
				},
			)
		);

		register_rest_field(
			$type,
			'pll_translations',
			array(
				'schema'          => array(
					'description' => 'Polylang translations as {language-slug: post-id}.',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'get_callback'    => function ( $obj ) {
					if ( ! function_exists( 'pll_get_post_translations' ) ) {
						return array();
					}
					return pll_get_post_translations( (int) $obj['id'] );
				},
				'update_callback' => function ( $value, $post ) {
					if ( is_array( $value ) && function_exists( 'pll_save_post_translations' ) ) {
						$map = array();
						foreach ( $value as $slug => $id ) {
							$map[ sanitize_key( $slug ) ] = (int) $id;
						}
						if ( $map ) {
							pll_save_post_translations( $map );
						}
					}
					return true;
				},
			)
		);
	}
} );

/**
 * REST-Brücke für TAXONOMIE-TERME (z. B. Beitrags-Kategorie „Ratgeber").
 *
 * Analog zur Post-Brücke, aber term-basiert (pll_set_term_language /
 * pll_save_term_translations). Nötig, um die Ratgeber-Kategorie über REST
 * zweisprachig zu verknüpfen (freies Polylang bietet dafür keinen REST-Weg).
 */
add_action( 'rest_api_init', function () {
	if ( ! function_exists( 'pll_set_term_language' ) ) {
		return;
	}

	$taxonomies = array( 'category' );

	foreach ( $taxonomies as $tax ) {
		register_rest_field(
			$tax,
			'lang',
			array(
				'schema'          => array(
					'description' => 'Polylang language slug (term).',
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
				),
				'get_callback'    => function ( $obj ) {
					if ( ! function_exists( 'pll_get_term_language' ) ) {
						return null;
					}
					$slug = pll_get_term_language( (int) $obj['id'], 'slug' );
					return $slug ? $slug : null;
				},
				'update_callback' => function ( $value, $term ) {
					if ( is_string( $value ) && '' !== $value ) {
						pll_set_term_language( (int) $term->term_id, sanitize_key( $value ) );
					}
					return true;
				},
			)
		);

		register_rest_field(
			$tax,
			'pll_translations',
			array(
				'schema'          => array(
					'description' => 'Polylang term translations as {language-slug: term-id}.',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'get_callback'    => function ( $obj ) {
					if ( ! function_exists( 'pll_get_term_translations' ) ) {
						return array();
					}
					return pll_get_term_translations( (int) $obj['id'] );
				},
				'update_callback' => function ( $value, $term ) {
					if ( is_array( $value ) && function_exists( 'pll_save_term_translations' ) ) {
						$map = array();
						foreach ( $value as $slug => $id ) {
							$map[ sanitize_key( $slug ) ] = (int) $id;
						}
						if ( $map ) {
							pll_save_term_translations( $map );
						}
					}
					return true;
				},
			)
		);
	}
} );
