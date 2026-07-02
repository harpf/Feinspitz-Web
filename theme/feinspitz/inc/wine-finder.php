<?php
/**
 * Feinspitz · Wein-Finder (feature/wein-finder-filter).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Wein-Finder-Branch. Sie stellt einen kleinen Quiz-Assistenten
 * bereit, der in wenigen Schritten zur passenden Wein-Empfehlung führt.
 *
 * Bereitgestellt wird:
 *   - Shortcode [feinspitz_wine_finder]        — der vollständige Finder (Quiz + Ergebnis).
 *   - Shortcode [feinspitz_wine_finder_teaser] — kompakter Teaser (Kurztext + Button),
 *     z. B. für die Startseite; verlinkt auf die Seite „Wein-Finder".
 *   - Pattern  feinspitz/wine-finder-teaser    — bettet den Teaser-Shortcode als
 *     wiederverwendbaren Block ein (im Inserter verfügbar; die Startseite kann ihn
 *     konfliktfrei einbinden, ohne dass dieser Branch front-page.html anfasst).
 *
 * Funktionsweise (bewusst OHNE Abhängigkeit von JavaScript):
 *   Der Quiz ist ein GET-Formular. Beim Absenden lädt die Seite mit den Antworten
 *   als Query-Parameter neu; PHP wertet sie aus und rendert die Empfehlungen
 *   serverseitig (WP_Query auf Produkte) direkt darunter — plus einen Button in den
 *   gefilterten Shop. Ohne JS sind alle Schritte gleichzeitig sichtbar und ein
 *   einzelner „Weine finden"-Button schickt ab. Mit JS wird daraus ein
 *   Schritt-für-Schritt-Assistent (progressive Verbesserung, Footer-Skript).
 *
 * Filter-Logik (gestützt auf die realen Taxonomien/Attribute aus Baustein ①):
 *   - Histamin  → Produkt-Tag  histamingeprueft (nur wenn „ja").
 *   - Farbe     → Produkt-Kategorie weissweine/rotweine/rose/schaumweine/suessweine.
 *   - Geschmack → Attribut pa_suesse (Terme trocken/halbtrocken/suess[+lieblich]).
 *   - Anlass    → optional; setzt nur dann eine Kategorie, wenn keine Farbe gewählt
 *                 wurde (Aperitif→Schaumweine, Zum Essen→Rotweine, Dessert→Süssweine).
 *
 * Sprach-Strategie: Wie im Formular-Branch (inc/forms.php) bewusst KEINE neuen
 * gettext-msgids, sondern eine kleine DE/EN-Inline-Auswahl (feinspitz_wf_t). So
 * bleibt der Wortschatz vollständig in DIESER Datei und bricht nicht die zentrale
 * i18n-Build-Pipeline. Da der Text per Shortcode zur RENDER-Zeit erzeugt wird, ist
 * die Polylang-Sprache zuverlässig ermittelbar.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sprachbewusste Textauswahl (DE als Standard, EN auf englischen Polylang-Seiten).
 *
 * @param string $de Deutscher Text.
 * @param string $en Englischer Text.
 * @return string
 */
function feinspitz_wf_t( $de, $en ) {
	if ( function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang() ) {
		return $en;
	}
	return $de;
}

/**
 * Farbe (Antwort-Slug) → Produkt-Kategorie-Slug.
 *
 * @return array<string,string>
 */
function feinspitz_wf_color_categories() {
	return array(
		'weiss'      => 'weissweine',
		'rot'        => 'rotweine',
		'rose'       => 'rose',
		'schaumwein' => 'schaumweine',
		'suesswein'  => 'suessweine',
	);
}

/**
 * Geschmack (Antwort-Slug) → pa_suesse-Term-Slugs. „Süss" schliesst „Lieblich" ein.
 *
 * @return array<string,string[]>
 */
function feinspitz_wf_taste_terms() {
	return array(
		'trocken'     => array( 'trocken' ),
		'halbtrocken' => array( 'halbtrocken' ),
		'suess'       => array( 'suess', 'lieblich' ),
	);
}

/**
 * Anlass (Antwort-Slug) → Produkt-Kategorie-Slug (nur als sanfte Vorauswahl,
 * greift ausschliesslich, wenn keine Farbe gewählt wurde).
 *
 * @return array<string,string>
 */
function feinspitz_wf_anlass_categories() {
	return array(
		'aperitif' => 'schaumweine',
		'essen'    => 'rotweine',
		'dessert'  => 'suessweine',
	);
}

/**
 * Die Quiz-Fragen als EINE Quelle für Rendering und Auswertung.
 *
 * Jede Frage: key, Frage-Text (de/en), optional (bool) und geordnete Optionen
 * (Slug → de/en-Label). Der leere Slug '' steht für „egal/keine Vorliebe".
 *
 * @return array<string,array>
 */
function feinspitz_wf_questions() {
	return array(
		'histamin'  => array(
			'de'       => 'Reagierst du empfindlich auf Histamin?',
			'en'       => 'Are you sensitive to histamine?',
			'optional' => false,
			'options'  => array(
				'ja'   => array( 'de' => 'Ja · bitte histamingeprüft', 'en' => 'Yes · histamine-checked please' ),
				'nein' => array( 'de' => 'Nein', 'en' => 'No' ),
			),
		),
		'farbe'     => array(
			'de'       => 'Welcher Wein darf es sein?',
			'en'       => 'Which kind of wine?',
			'optional' => false,
			'options'  => array(
				'weiss'      => array( 'de' => 'Weisswein', 'en' => 'White' ),
				'rot'        => array( 'de' => 'Rotwein', 'en' => 'Red' ),
				'rose'       => array( 'de' => 'Rosé', 'en' => 'Rosé' ),
				'schaumwein' => array( 'de' => 'Schaumwein', 'en' => 'Sparkling' ),
				'suesswein'  => array( 'de' => 'Süsswein', 'en' => 'Dessert wine' ),
				''           => array( 'de' => 'Überrasch mich', 'en' => 'Surprise me' ),
			),
		),
		'geschmack' => array(
			'de'       => 'Wie trinkst du am liebsten?',
			'en'       => 'How do you like it?',
			'optional' => false,
			'options'  => array(
				'trocken'     => array( 'de' => 'Trocken', 'en' => 'Dry' ),
				'halbtrocken' => array( 'de' => 'Halbtrocken', 'en' => 'Off-dry' ),
				'suess'       => array( 'de' => 'Fruchtig süss', 'en' => 'Fruity & sweet' ),
				''            => array( 'de' => 'Egal', 'en' => 'No preference' ),
			),
		),
		'anlass'    => array(
			'de'       => 'Wozu passt der Wein? (optional)',
			'en'       => 'For which occasion? (optional)',
			'optional' => true,
			'options'  => array(
				'aperitif' => array( 'de' => 'Als Aperitif', 'en' => 'As an apéritif' ),
				'essen'    => array( 'de' => 'Zum Essen', 'en' => 'With a meal' ),
				'dessert'  => array( 'de' => 'Zum Dessert', 'en' => 'With dessert' ),
				'geschenk' => array( 'de' => 'Als Geschenk', 'en' => 'As a gift' ),
				''         => array( 'de' => 'Keine Angabe', 'en' => 'Not sure' ),
			),
		),
	);
}

/**
 * Eine sanitisierte Antwort aus $_GET holen (nur erlaubte Slugs, sonst '').
 *
 * @param string $key Fragen-Key (ohne wf_-Präfix).
 * @return string
 */
function feinspitz_wf_answer( $key ) {
	$questions = feinspitz_wf_questions();
	if ( ! isset( $questions[ $key ] ) ) {
		return '';
	}
	$raw = isset( $_GET[ 'wf_' . $key ] ) ? sanitize_key( wp_unslash( $_GET[ 'wf_' . $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return array_key_exists( $raw, $questions[ $key ]['options'] ) ? $raw : '';
}

/**
 * Wurde der Finder abgeschickt? (verstecktes Feld wf_submitted, robust auch wenn
 * alle Antworten „egal" sind).
 *
 * @return bool
 */
function feinspitz_wf_submitted() {
	return isset( $_GET['wf_submitted'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Aus den Antworten den Filter-Zustand ableiten: Kategorie-Slug, Tag-Slug,
 * pa_suesse-Term-Slugs.
 *
 * @return array{cat:string,tag:string,suesse:string[]}
 */
function feinspitz_wf_state() {
	$farbe     = feinspitz_wf_answer( 'farbe' );
	$geschmack = feinspitz_wf_answer( 'geschmack' );
	$histamin  = feinspitz_wf_answer( 'histamin' );
	$anlass    = feinspitz_wf_answer( 'anlass' );

	$color_cat = feinspitz_wf_color_categories();
	$cat       = isset( $color_cat[ $farbe ] ) ? $color_cat[ $farbe ] : '';

	// Anlass nur als Vorauswahl, wenn keine Farbe gewählt wurde.
	if ( '' === $cat ) {
		$anlass_cat = feinspitz_wf_anlass_categories();
		if ( isset( $anlass_cat[ $anlass ] ) ) {
			$cat = $anlass_cat[ $anlass ];
		}
	}

	$taste  = feinspitz_wf_taste_terms();
	$suesse = isset( $taste[ $geschmack ] ) ? $taste[ $geschmack ] : array();

	$tag = ( 'ja' === $histamin ) ? 'histamingeprueft' : '';

	return array(
		'cat'    => $cat,
		'tag'    => $tag,
		'suesse' => $suesse,
	);
}

/**
 * Root-relative Shop-URL für einen Filter-Zustand (analog Shop-Filterleiste).
 * pa_suesse wird über WooCommerce Layered-Nav (filter_suesse[,…] + query_type)
 * abgebildet, damit es sich mit product_cat/product_tag sauber kombiniert.
 *
 * @param array $state {cat, tag, suesse}.
 * @return string
 */
function feinspitz_wf_shop_url( $state ) {
	$args = array();
	if ( ! empty( $state['cat'] ) ) {
		$args['product_cat'] = $state['cat'];
	}
	if ( ! empty( $state['tag'] ) ) {
		$args['product_tag'] = $state['tag'];
	}
	if ( ! empty( $state['suesse'] ) ) {
		$args['filter_suesse'] = implode( ',', $state['suesse'] );
		if ( count( $state['suesse'] ) > 1 ) {
			$args['query_type_suesse'] = 'or';
		}
	}
	return empty( $args ) ? '/shop/' : add_query_arg( $args, '/shop/' );
}

/**
 * Passende Produkte zum Filter-Zustand laden.
 *
 * @param array $state {cat, tag, suesse}.
 * @param int   $limit Maximale Trefferzahl.
 * @return WC_Product[]
 */
function feinspitz_wf_find_products( $state, $limit = 8 ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$tax_query = array( 'relation' => 'AND' );
	if ( ! empty( $state['cat'] ) ) {
		$tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $state['cat'] );
	}
	if ( ! empty( $state['tag'] ) ) {
		$tax_query[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $state['tag'] );
	}
	if ( ! empty( $state['suesse'] ) ) {
		$tax_query[] = array( 'taxonomy' => 'pa_suesse', 'field' => 'slug', 'terms' => $state['suesse'], 'operator' => 'IN' );
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $limit,
		'orderby'             => 'menu_order title',
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	// Nur kaufbare/sichtbare Produkte (analog WooCommerce-Katalog).
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$visibility = wc_get_product_visibility_term_ids();
		if ( ! empty( $visibility['exclude-from-catalog'] ) ) {
			$existing              = isset( $args['tax_query'] ) ? $args['tax_query'] : array( 'relation' => 'AND' );
			$existing[]            = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $visibility['exclude-from-catalog'] ),
				'operator' => 'NOT IN',
			);
			$args['tax_query']     = $existing; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
	}

	$query    = new WP_Query( $args );
	$products = array();
	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( $product instanceof WC_Product ) {
			$products[] = $product;
		}
	}
	wp_reset_postdata();
	return $products;
}

/**
 * Eine einzelne Produktkarte rendern (Bild · Titel · Preis · Link).
 *
 * @param WC_Product $product Produkt.
 * @return string
 */
function feinspitz_wf_product_card( $product ) {
	$link  = get_permalink( $product->get_id() );
	$title = $product->get_name();
	$img   = $product->get_image( 'woocommerce_thumbnail' );
	$price = $product->get_price_html();

	$html  = '<li class="feinspitz-wf__card">';
	$html .= '<a class="feinspitz-wf__card-link" href="' . esc_url( $link ) . '">';
	$html .= '<span class="feinspitz-wf__card-media">' . $img . '</span>';
	$html .= '<span class="feinspitz-wf__card-title">' . esc_html( $title ) . '</span>';
	if ( $price ) {
		$html .= '<span class="feinspitz-wf__card-price">' . wp_kses_post( $price ) . '</span>';
	}
	$html .= '</a></li>';
	return $html;
}

/**
 * Die Ergebnis-Sektion (nach dem Absenden) rendern.
 *
 * @param array $state {cat, tag, suesse}.
 * @return string
 */
function feinspitz_wf_render_result( $state ) {
	$products = feinspitz_wf_find_products( $state, 8 );
	$shop_url = feinspitz_wf_shop_url( $state );

	$out  = '<section class="feinspitz-wf__result" id="wf-result" aria-live="polite">';

	if ( empty( $products ) ) {
		$out .= '<p class="feinspitz-wf__result-lead">' . esc_html( feinspitz_wf_t(
			'Zu dieser Kombination haben wir gerade keinen passenden Wein gefunden. Stöbere gern im ganzen Sortiment.',
			'We could not find a matching wine for this exact combination. Feel free to browse the full range.'
		) ) . '</p>';
		$out .= '<a class="feinspitz-wf__shop-btn" href="' . esc_url( $shop_url ) . '">'
			. esc_html( feinspitz_wf_t( 'Zum Shop', 'Go to the shop' ) ) . '</a>';
		$out .= '</section>';
		return $out;
	}

	$out .= '<p class="feinspitz-wf__result-eyebrow">' . esc_html( feinspitz_wf_t( 'Deine Empfehlung', 'Your recommendation' ) ) . '</p>';
	$out .= '<h3 class="feinspitz-wf__result-title">' . esc_html( feinspitz_wf_t(
		'Diese Weine passen zu dir',
		'These wines suit you'
	) ) . '</h3>';

	$out .= '<ul class="feinspitz-wf__grid">';
	foreach ( $products as $product ) {
		$out .= feinspitz_wf_product_card( $product );
	}
	$out .= '</ul>';

	$out .= '<a class="feinspitz-wf__shop-btn" href="' . esc_url( $shop_url ) . '">'
		. esc_html( feinspitz_wf_t( 'Alle passenden Weine im Shop ansehen', 'See all matching wines in the shop' ) )
		. '</a>';

	$out .= '</section>';
	return $out;
}

/**
 * Ein Quiz-Schritt (Fieldset mit Radio-Optionen) rendern.
 *
 * @param string $key    Fragen-Key.
 * @param array  $config Fragen-Konfiguration.
 * @param int    $index  0-basierter Schritt-Index.
 * @param int    $total  Gesamtzahl Schritte.
 * @return string
 */
function feinspitz_wf_render_step( $key, $config, $index, $total ) {
	$lang    = ( function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang() ) ? 'en' : 'de';
	$current = feinspitz_wf_answer( $key );
	$legend  = $config[ $lang ];
	$num     = $index + 1;

	$out  = '<fieldset class="feinspitz-wf__step" data-step="' . esc_attr( (string) $index ) . '">';
	$out .= '<legend class="feinspitz-wf__legend">';
	$out .= '<span class="feinspitz-wf__step-count">' . esc_html( sprintf(
		/* Schritt-Zähler, z. B. „Schritt 2 von 4" */
		feinspitz_wf_t( 'Schritt %1$d von %2$d', 'Step %1$d of %2$d' ),
		$num,
		$total
	) ) . '</span>';
	$out .= '<span class="feinspitz-wf__question">' . esc_html( $legend ) . '</span>';
	$out .= '</legend>';

	$out .= '<div class="feinspitz-wf__options">';
	foreach ( $config['options'] as $slug => $labels ) {
		$id      = 'wf-' . $key . '-' . ( '' === $slug ? 'egal' : $slug );
		$checked = ( $slug === $current ) ? ' checked' : '';
		$out    .= '<label class="feinspitz-wf__option" for="' . esc_attr( $id ) . '">';
		$out    .= '<input type="radio" id="' . esc_attr( $id ) . '" name="wf_' . esc_attr( $key ) . '" value="' . esc_attr( $slug ) . '"' . $checked . '>';
		$out    .= '<span class="feinspitz-wf__option-label">' . esc_html( $labels[ $lang ] ) . '</span>';
		$out    .= '</label>';
	}
	$out .= '</div>';
	$out .= '</fieldset>';
	return $out;
}

/**
 * Shortcode [feinspitz_wine_finder] — der vollständige Finder.
 *
 * @return string HTML.
 */
function feinspitz_wine_finder_shortcode() {
	$GLOBALS['feinspitz_wf_rendered'] = true;

	$questions = feinspitz_wf_questions();
	$total     = count( $questions );
	$action    = get_permalink();
	if ( ! $action ) {
		$action = feinspitz_wf_current_url();
	}

	$out  = '<section class="feinspitz-wf" id="feinspitz-wine-finder">';
	$out .= '<div class="feinspitz-wf__inner">';

	$out .= '<p class="feinspitz-wf__eyebrow">' . esc_html( feinspitz_wf_t( 'Wein-Finder', 'Wine finder' ) ) . '</p>';
	$out .= '<h2 class="feinspitz-wf__title">' . esc_html( feinspitz_wf_t(
		'In vier Schritten zum richtigen Wein',
		'Find your wine in four steps'
	) ) . '</h2>';
	$out .= '<p class="feinspitz-wf__intro">' . esc_html( feinspitz_wf_t(
		'Beantworte ein paar kurze Fragen — wir empfehlen dir dazu passende, histaminbewusste Weine aus unserem Sortiment.',
		'Answer a few short questions — we will recommend matching, histamine-aware wines from our range.'
	) ) . '</p>';

	$out .= '<form class="feinspitz-wf__form" method="get" action="' . esc_url( $action ) . '#wf-result">';
	$out .= '<input type="hidden" name="wf_submitted" value="1">';

	$i = 0;
	foreach ( $questions as $key => $config ) {
		$out .= feinspitz_wf_render_step( $key, $config, $i, $total );
		$i++;
	}

	$out .= '<div class="feinspitz-wf__actions">';
	$out .= '<button type="button" class="feinspitz-wf__nav feinspitz-wf__prev" hidden>' . esc_html( feinspitz_wf_t( 'Zurück', 'Back' ) ) . '</button>';
	$out .= '<button type="button" class="feinspitz-wf__nav feinspitz-wf__next" hidden>' . esc_html( feinspitz_wf_t( 'Weiter', 'Next' ) ) . '</button>';
	$out .= '<button type="submit" class="feinspitz-wf__submit">' . esc_html( feinspitz_wf_t( 'Weine finden', 'Find wines' ) ) . '</button>';
	$out .= '</div>';

	$out .= '</form>';

	if ( feinspitz_wf_submitted() ) {
		$out .= feinspitz_wf_render_result( feinspitz_wf_state() );
	}

	$out .= '</div></section>';
	return $out;
}

/**
 * Aktuelle Frontend-URL ermitteln (Fallback, falls kein Seiten-Permalink vorliegt).
 *
 * @return string
 */
function feinspitz_wf_current_url() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( '' === $host || '' === $uri ) {
		return home_url( '/' );
	}
	$scheme = is_ssl() ? 'https' : 'http';
	$path   = explode( '?', $scheme . '://' . $host . $uri, 2 );
	return $path[0];
}

/**
 * Shortcode [feinspitz_wine_finder_teaser] — kompakter Teaser mit Button auf die
 * Wein-Finder-Seite (z. B. für die Startseite).
 *
 * @return string HTML.
 */
function feinspitz_wine_finder_teaser_shortcode() {
	$url = function_exists( 'feinspitz_nav_page_url' )
		? feinspitz_nav_page_url( 'wein-finder', '/wein-finder/' )
		: home_url( '/wein-finder/' );

	$out  = '<section class="feinspitz-wf-teaser">';
	$out .= '<div class="feinspitz-wf-teaser__inner">';
	$out .= '<p class="feinspitz-wf-teaser__eyebrow">' . esc_html( feinspitz_wf_t( 'Unsicher bei der Wahl?', 'Not sure which to pick?' ) ) . '</p>';
	$out .= '<h2 class="feinspitz-wf-teaser__title">' . esc_html( feinspitz_wf_t(
		'Finde deinen Wein in vier Schritten',
		'Find your wine in four steps'
	) ) . '</h2>';
	$out .= '<p class="feinspitz-wf-teaser__text">' . esc_html( feinspitz_wf_t(
		'Farbe, Geschmack, Verträglichkeit — unser Wein-Finder führt dich schnell zur passenden Empfehlung.',
		'Colour, taste, tolerance — our wine finder quickly guides you to the right recommendation.'
	) ) . '</p>';
	$out .= '<a class="feinspitz-wf-teaser__btn" href="' . esc_url( $url ) . '">'
		. esc_html( feinspitz_wf_t( 'Wein-Finder starten', 'Start the wine finder' ) ) . '</a>';
	$out .= '</div></section>';
	return $out;
}

/**
 * Shortcodes registrieren.
 */
add_action( 'init', function () {
	add_shortcode( 'feinspitz_wine_finder', 'feinspitz_wine_finder_shortcode' );
	add_shortcode( 'feinspitz_wine_finder_teaser', 'feinspitz_wine_finder_teaser_shortcode' );
} );

/**
 * Teaser-Pattern registrieren (feinspitz/wine-finder-teaser). Analog inc/shop.php:
 * die Pattern-Datei enthält reines Block-Markup (hier einen Shortcode-Block) und
 * wird hier manuell registriert. Die Startseite kann den Teaser konfliktfrei
 * einbinden, ohne dass dieser Branch front-page.html verändert.
 */
add_action( 'init', function () {
	register_block_pattern_category( 'feinspitz', array( 'label' => __( 'Feinspitz', 'feinspitz' ) ) );

	$file = get_template_directory() . '/patterns/wine-finder-teaser.php';
	if ( ! is_readable( $file ) ) {
		return;
	}
	ob_start();
	include $file;
	$content = ob_get_clean();

	register_block_pattern(
		'feinspitz/wine-finder-teaser',
		array(
			'title'      => __( 'Wein-Finder: Teaser', 'feinspitz' ),
			'categories' => array( 'feinspitz' ),
			'content'    => $content,
			'inserter'   => true,
		)
	);
} );

/**
 * Gescopte Styles · an das Theme-Stylesheet gehängt (style.css bleibt Phase-0),
 * mit Fallback-Handle. Bold-Design-Tokens des Themes (wine/gold/cream/base).
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = <<<'CSS'
/* ---------- Wein-Finder ---------- */
.feinspitz-wf{--wf-wine:var(--wp--preset--color--wine,#7b1f2b);--wf-gold:var(--wp--preset--color--gold,#c9a24b);--wf-ink:var(--wp--preset--color--base,#0e0b08);background:#fff;border:1px solid rgba(14,11,8,.1);border-top:4px solid var(--wf-wine);border-radius:18px;padding:clamp(1.5rem,4vw,2.75rem);margin:2rem auto;max-width:860px;box-shadow:0 24px 60px -48px rgba(14,11,8,.6)}
.feinspitz-wf__eyebrow{text-transform:uppercase;letter-spacing:.28em;font-weight:600;font-size:.72rem;color:var(--wf-wine);margin:0 0 .5rem}
.feinspitz-wf__title{font-family:var(--wp--preset--font-family--heading,serif);font-weight:600;line-height:1.1;font-size:clamp(1.6rem,4vw,2.3rem);margin:0 0 .5rem;color:var(--wf-ink)}
.feinspitz-wf__intro{color:rgba(14,11,8,.72);margin:0 0 1.75rem;font-size:1rem;line-height:1.6;max-width:60ch}
.feinspitz-wf__step{border:0;margin:0 0 1.75rem;padding:0}
.feinspitz-wf__legend{padding:0;margin:0 0 .9rem;display:block;width:100%}
.feinspitz-wf__step-count{display:block;text-transform:uppercase;letter-spacing:.18em;font-size:.68rem;font-weight:600;color:var(--wf-gold);margin:0 0 .35rem}
.feinspitz-wf__question{display:block;font-family:var(--wp--preset--font-family--heading,serif);font-size:clamp(1.15rem,2.6vw,1.45rem);font-weight:600;line-height:1.2;color:var(--wf-ink)}
.feinspitz-wf__options{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.7rem}
.feinspitz-wf__option{position:relative;display:flex;align-items:center;gap:.6rem;padding:.85rem 1rem;border:1.5px solid rgba(123,31,43,.22);border-radius:12px;cursor:pointer;font-weight:600;font-size:.95rem;color:var(--wf-ink);background:#faf8f5;transition:border-color .15s ease,background .15s ease,box-shadow .15s ease}
.feinspitz-wf__option:hover{border-color:var(--wf-wine)}
.feinspitz-wf__option input{accent-color:var(--wf-wine);width:1.1rem;height:1.1rem;flex:none;margin:0}
.feinspitz-wf__option:has(input:checked){border-color:var(--wf-wine);background:rgba(123,31,43,.07);box-shadow:0 0 0 3px rgba(201,162,75,.2)}
.feinspitz-wf__option:has(input:focus-visible){outline:2px solid var(--wf-gold);outline-offset:2px}
.feinspitz-wf__actions{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-top:1.5rem}
.feinspitz-wf__submit,.feinspitz-wf__nav{font:inherit;font-weight:600;letter-spacing:.02em;cursor:pointer;border-radius:999px;padding:.85rem 1.9rem;transition:transform .15s ease,background .15s ease,color .15s ease,opacity .15s ease}
.feinspitz-wf__submit{background:var(--wf-wine);color:#fff;border:0}
.feinspitz-wf__submit:hover{background:#611620;transform:translateY(-1px)}
.feinspitz-wf__next{background:var(--wf-wine);color:#fff;border:0}
.feinspitz-wf__next:hover{background:#611620;transform:translateY(-1px)}
.feinspitz-wf__prev{background:transparent;color:var(--wf-wine);border:1.5px solid rgba(123,31,43,.35)}
.feinspitz-wf__prev:hover{border-color:var(--wf-wine)}
.feinspitz-wf__nav[hidden]{display:none}
/* Ergebnis */
.feinspitz-wf__result{margin-top:2.25rem;padding-top:2rem;border-top:1px solid rgba(14,11,8,.1)}
.feinspitz-wf__result-eyebrow{text-transform:uppercase;letter-spacing:.24em;font-size:.7rem;font-weight:600;color:var(--wf-gold);margin:0 0 .35rem}
.feinspitz-wf__result-title{font-family:var(--wp--preset--font-family--heading,serif);font-size:clamp(1.35rem,3vw,1.8rem);font-weight:600;line-height:1.15;margin:0 0 1.4rem;color:var(--wf-ink)}
.feinspitz-wf__result-lead{color:rgba(14,11,8,.72);font-size:1rem;line-height:1.6;margin:0 0 1.4rem}
.feinspitz-wf__grid{list-style:none;margin:0 0 1.75rem;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
@media(min-width:640px){.feinspitz-wf__grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:1.25rem}}
.feinspitz-wf__card{margin:0}
.feinspitz-wf__card-link{display:flex;flex-direction:column;height:100%;text-decoration:none;color:inherit;background:#fff;border:1px solid rgba(14,11,8,.08);border-radius:14px;overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.feinspitz-wf__card-link:hover{transform:translateY(-4px);box-shadow:0 14px 30px -18px rgba(74,17,25,.55);border-color:rgba(201,162,75,.5)}
.feinspitz-wf__card-media{display:block;background:#f6f1e7}
.feinspitz-wf__card-media img{display:block;width:100%;height:auto;aspect-ratio:4/5;object-fit:contain;padding:.6rem}
.feinspitz-wf__card-title{font-family:var(--wp--preset--font-family--heading,serif);font-size:.98rem;line-height:1.25;font-weight:600;margin:.7rem .9rem .3rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.feinspitz-wf__card-price{margin:auto .9rem 1rem;font-weight:700;color:var(--wf-wine);font-size:1rem}
.feinspitz-wf__card-price del{color:rgba(14,11,8,.45);font-weight:400;margin-right:.35rem}
.feinspitz-wf__card-price ins{text-decoration:none}
.feinspitz-wf__shop-btn{display:inline-block;background:var(--wf-wine);color:#fff;text-decoration:none;font-weight:600;border-radius:999px;padding:.9rem 2rem;transition:background .15s ease,transform .15s ease}
.feinspitz-wf__shop-btn:hover{background:#611620;transform:translateY(-1px)}
@media(max-width:520px){.feinspitz-wf__actions{flex-direction:column;align-items:stretch}.feinspitz-wf__actions button{width:100%}}

/* ---------- Wein-Finder Teaser ---------- */
.feinspitz-wf-teaser{background:linear-gradient(135deg,#611620,#7b1f2b);color:#fff;border-radius:20px;padding:clamp(1.75rem,5vw,3.25rem);margin:2rem auto;max-width:1080px}
.feinspitz-wf-teaser__inner{max-width:640px}
.feinspitz-wf-teaser__eyebrow{text-transform:uppercase;letter-spacing:.26em;font-size:.72rem;font-weight:600;color:var(--wp--preset--color--gold,#c9a24b);margin:0 0 .5rem}
.feinspitz-wf-teaser__title{font-family:var(--wp--preset--font-family--heading,serif);font-size:clamp(1.7rem,4vw,2.5rem);font-weight:600;line-height:1.1;margin:0 0 .75rem}
.feinspitz-wf-teaser__text{font-size:1.05rem;line-height:1.6;color:rgba(255,255,255,.85);margin:0 0 1.6rem}
.feinspitz-wf-teaser__btn{display:inline-block;background:var(--wp--preset--color--gold,#c9a24b);color:#0e0b08;text-decoration:none;font-weight:700;border-radius:999px;padding:.9rem 2.1rem;transition:transform .15s ease,filter .15s ease}
.feinspitz-wf-teaser__btn:hover{transform:translateY(-1px);filter:brightness(1.05)}
CSS;

	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-wf-inline', false );
		wp_enqueue_style( 'feinspitz-wf-inline' );
		wp_add_inline_style( 'feinspitz-wf-inline', $css );
	}
}, 20 );

/**
 * Progressive JS-Verbesserung: den Quiz in einen Schritt-für-Schritt-Assistenten
 * verwandeln. Ohne JS bleiben alle Schritte sichtbar und der „Weine finden"-Button
 * schickt das GET-Formular normal ab. Nur ausgegeben, wenn ein Finder gerendert wurde.
 */
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['feinspitz_wf_rendered'] ) ) {
		return;
	}
	?>
<script>
( function () {
	var form = document.querySelector( '.feinspitz-wf__form' );
	if ( ! form ) { return; }
	var steps = Array.prototype.slice.call( form.querySelectorAll( '.feinspitz-wf__step' ) );
	if ( steps.length < 2 ) { return; }

	var prev   = form.querySelector( '.feinspitz-wf__prev' );
	var next   = form.querySelector( '.feinspitz-wf__next' );
	var submit = form.querySelector( '.feinspitz-wf__submit' );
	var idx    = 0;

	function render() {
		steps.forEach( function ( step, i ) { step.hidden = ( i !== idx ); } );
		if ( prev ) { prev.hidden = ( idx === 0 ); }
		var last = ( idx === steps.length - 1 );
		if ( next ) { next.hidden = last; }
		if ( submit ) { submit.hidden = ! last; }
	}

	function go( delta ) {
		idx = Math.max( 0, Math.min( steps.length - 1, idx + delta ) );
		render();
		var q = steps[ idx ].querySelector( '.feinspitz-wf__question' );
		if ( q && q.scrollIntoView ) { q.scrollIntoView( { block: 'nearest' } ); }
	}

	if ( next )   { next.addEventListener( 'click', function () { go( 1 ); } ); }
	if ( prev )   { prev.addEventListener( 'click', function () { go( -1 ); } ); }

	// Auswahl schiebt sanft weiter (nicht im letzten Schritt).
	steps.forEach( function ( step, i ) {
		step.addEventListener( 'change', function () {
			if ( i < steps.length - 1 ) { setTimeout( function () { go( 1 ); }, 220 ); }
		} );
	} );

	render();
}() );
</script>
	<?php
}, 30 );
