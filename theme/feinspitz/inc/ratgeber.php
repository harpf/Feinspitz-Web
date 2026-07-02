<?php
/**
 * Feinspitz — Ratgeber & FAQ (feature/ratgeber-faq).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Ratgeber/FAQ-Branch. Sie stellt bereit:
 *
 *  - feinspitz_faq_items() — die EINE Quelle der FAQ-Fragen/Antworten. Sowohl das
 *    sichtbare Akkordeon (Pattern feinspitz/faq-accordion) als auch das
 *    FAQPage-JSON-LD werden hieraus erzeugt → beide können NICHT auseinanderlaufen
 *    (Google verlangt, dass das strukturierte Markup dem sichtbaren Inhalt
 *    entspricht, sonst kein Rich Snippet).
 *  - Registrierung der Patterns feinspitz/faq-accordion und feinspitz/ratgeber-intro
 *    (analog zu inc/homepage.php: Markup-Datei unter /patterns/, Registrierung hier).
 *  - FAQPage-JSON-LD im wp_head, ausschliesslich auf der FAQ-Seite (Slug „faq").
 *  - Gescopte Inline-Styles fürs Akkordeon (an das Theme-Stylesheet gehängt, damit
 *    style.css — Phase-0-Datei — unberührt bleibt).
 *
 * Der eigentliche Ratgeber-Inhalt (Beiträge, Kategorie „Ratgeber", FAQ-Seite) wird
 * NICHT hier, sondern idempotent per REST über scripts/content/ratgeber.mjs angelegt.
 *
 * Textdomain: feinspitz.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kanonische FAQ-Liste.
 *
 * EINZIGE Quelle für Akkordeon UND JSON-LD. Fragen kurz, Antworten als schlichter
 * Fliesstext (eine Aussage pro Antwort → sauberes strukturiertes Markup).
 *
 * @return array<int,array{q:string,a:string}> Liste aus Frage/Antwort-Paaren.
 */
function feinspitz_faq_items() {
	return array(
		array(
			'q' => __( 'Was bedeutet „histamingeprüft" bei einem Wein?', 'feinspitz' ),
			'a' => __( 'Histamingeprüfte Weine werden im Labor auf ihren Histamingehalt untersucht. So wissen Sie vor dem Kauf, woran Sie sind — ideal für alle, die auf Verträglichkeit achten und Wein bewusst geniessen möchten.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Sind histamingeprüfte Weine automatisch histaminfrei?', 'feinspitz' ),
			'a' => __( 'Nein. Wein enthält von Natur aus geringe Mengen Histamin — histaminfrei gibt es nicht. „Geprüft" heisst: der Gehalt ist bekannt und niedrig ausgewiesen. Bei diagnostizierter Histaminintoleranz halten Sie im Zweifel bitte Rücksprache mit Ihrer Ärztin oder Ihrem Arzt.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Wie erkenne ich vegane und alkoholfreie Weine im Shop?', 'feinspitz' ),
			'a' => __( 'Passende Weine sind mit den Merkmalen „vegan" bzw. „alkoholfrei" gekennzeichnet und lassen sich über die Filter im Shop gezielt anzeigen. Vegane Weine kommen ohne tierische Klärhilfsmittel aus; alkoholfreie Weine sind eine schonende Alternative für den unbeschwerten Genuss.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Wohin liefert Feinspitz und was kostet der Versand?', 'feinspitz' ),
			'a' => __( 'Wir versenden schweizweit mit Schweizer Versandpartnern. Die Lieferung erfolgt sorgfältig verpackt in bruchsicheren Weinkartons. Die aktuellen Versandkosten und allfällige Freigrenzen sehen Sie transparent im Warenkorb, bevor Sie bestellen.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Wie lange dauert die Lieferung innerhalb der Schweiz?', 'feinspitz' ),
			'a' => __( 'Bestellungen bearbeiten wir in der Regel innerhalb von ein bis zwei Werktagen. Danach ist Ihr Wein üblicherweise innert weniger Werktage bei Ihnen — abhängig von Versandart und Region.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Kann ich meine Bestellung in Urdorf abholen?', 'feinspitz' ),
			'a' => __( 'Ja. Sie können Ihre Bestellung nach Absprache bei uns in Urdorf abholen und sparen so die Versandkosten. Bitte warten Sie unsere Abhol-Bestätigung ab, bevor Sie vorbeikommen, damit alles bereitsteht.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Ab welchem Alter kann ich Wein bestellen?', 'feinspitz' ),
			'a' => __( 'Alkoholische Weine geben wir ausschliesslich an Personen ab 18 Jahren ab. Mit Ihrer Bestellung bestätigen Sie, dass Sie volljährig sind.', 'feinspitz' ),
		),
		array(
			'q' => __( 'Berät mich Feinspitz auch persönlich bei der Auswahl?', 'feinspitz' ),
			'a' => __( 'Sehr gerne. Wir verkosten unser Sortiment selbst und beraten Sie ehrlich — ob zur Verträglichkeit, zu Speisekombinationen oder zum passenden Wein für einen Anlass. Nehmen Sie einfach über die Kontaktseite mit uns Kontakt auf.', 'feinspitz' ),
		),
	);
}

/**
 * Erzeugt das Block-Markup des FAQ-Akkordeons aus feinspitz_faq_items().
 *
 * Reine core/details-Blöcke in einem constrained Group-Wrapper mit der Klasse
 * „feinspitz-faq" (siehe gescopte Styles unten). Wird sowohl vom Pattern
 * feinspitz/faq-accordion als auch — als Fallback — direkt genutzt.
 *
 * @return string Gültiges Gutenberg-Block-Markup.
 */
function feinspitz_faq_accordion_markup() {
	$items = feinspitz_faq_items();

	$details = '';
	foreach ( $items as $item ) {
		$q = $item['q'];
		$a = $item['a'];

		// Block-Attribute als sauberes JSON (Summary landet als Attribut + sichtbar).
		$attrs = wp_json_encode( array( 'summary' => $q ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$details .= sprintf(
			"<!-- wp:details %s -->\n"
			. '<details class="wp-block-details"><summary>%s</summary>'
			. "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph --></details>\n"
			. "<!-- /wp:details -->\n\n",
			$attrs,
			esc_html( $q ),
			esc_html( $a )
		);
	}

	return "<!-- wp:group {\"className\":\"feinspitz-faq\",\"layout\":{\"type\":\"constrained\"}} -->\n"
		. '<div class="wp-block-group feinspitz-faq">' . "\n"
		. $details
		. "</div>\n"
		. "<!-- /wp:group -->\n";
}

/**
 * Pattern-Kategorie „feinspitz" sowie die Ratgeber/FAQ-Patterns registrieren.
 *
 * register_block_pattern_category ist idempotent (bereits vorhandene Kategorie
 * wird überschrieben, nicht dupliziert). faq-accordion wird aus dem gemeinsamen
 * Builder gespeist, ratgeber-intro aus der Markup-Datei — analog inc/homepage.php.
 */
add_action( 'init', function () {
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category(
			'feinspitz',
			array( 'label' => _x( 'Feinspitz', 'Block pattern category', 'feinspitz' ) )
		);
	}

	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	// FAQ-Akkordeon: Inhalt aus dem gemeinsamen Builder (Single Source of Truth).
	register_block_pattern(
		'feinspitz/faq-accordion',
		array(
			'title'      => __( 'FAQ: Akkordeon', 'feinspitz' ),
			'categories' => array( 'feinspitz' ),
			'content'    => feinspitz_faq_accordion_markup(),
			'inserter'   => true,
		)
	);

	// Ratgeber-Intro: statisches Markup aus der Pattern-Datei einfangen.
	$intro = get_template_directory() . '/patterns/ratgeber-intro.php';
	if ( is_readable( $intro ) ) {
		ob_start();
		include $intro;
		register_block_pattern(
			'feinspitz/ratgeber-intro',
			array(
				'title'      => __( 'Ratgeber: Übersicht-Intro', 'feinspitz' ),
				'categories' => array( 'feinspitz' ),
				'content'    => ob_get_clean(),
				'inserter'   => true,
			)
		);
	}
} );

/**
 * FAQPage-JSON-LD (schema.org) im <head> ausgeben — NUR auf der FAQ-Seite.
 *
 * Speist sich aus derselben feinspitz_faq_items()-Liste wie das sichtbare
 * Akkordeon, damit strukturierte Daten und Seiteninhalt deckungsgleich sind
 * (Voraussetzung für ein gültiges FAQ-Rich-Snippet).
 */
add_action( 'wp_head', function () {
	if ( ! is_page( 'faq' ) ) {
		return;
	}

	$entities = array();
	foreach ( feinspitz_faq_items() as $item ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $item['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $item['a'] ),
			),
		);
	}

	if ( empty( $entities ) ) {
		return;
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);

	echo "\n<script type=\"application/ld+json\">"
		. wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. "</script>\n";
}, 20 );

/**
 * Meta-Description im <head> — aus dem Excerpt der Ratgeber-Beiträge bzw. der
 * FAQ-Seite. Bewusst eng gescoped (nur einzelne Beiträge mit Excerpt und die
 * FAQ-Seite), damit kein anderes SEO-Verhalten überschrieben wird. Kein
 * SEO-Plugin nötig (siehe Spec: saubere Semantik genügt).
 */
add_action( 'wp_head', function () {
	$description = '';

	if ( is_singular( 'post' ) && has_excerpt() ) {
		$description = get_the_excerpt();
	} elseif ( is_page( 'faq' ) ) {
		$description = __( 'Häufige Fragen zu histamingeprüften Weinen, Versand innerhalb der Schweiz, Abholung in Urdorf sowie veganen und alkoholfreien Weinen — klar beantwortet von Feinspitz.', 'feinspitz' );
	}

	$description = trim( wp_strip_all_tags( $description ) );
	if ( '' === $description ) {
		return;
	}

	// Auf eine sinnvolle Meta-Länge kürzen (ganze Wörter).
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $description ) > 160 ) {
		$description = rtrim( mb_substr( $description, 0, 157 ) ) . '…';
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}, 5 );

/**
 * Gescopte Styles für Akkordeon (FAQ) und Ratgeber-Artikelinhalt.
 *
 * An das in functions.php registrierte Theme-Stylesheet gehängt (style.css bleibt
 * Phase-0-unangetastet), mit Fallback-Handle. Nur auf FAQ-/Single-Ansichten nötig,
 * aber die Regeln sind klein und gescoped — bewusst leichtgewichtig gehalten.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-faq .wp-block-details{border:1px solid rgba(123,31,43,.18);border-radius:14px;padding:0 clamp(1rem,3vw,1.75rem);margin:0 0 .75rem;background:#fff;transition:border-color .15s ease,box-shadow .15s ease}
.feinspitz-faq .wp-block-details[open]{border-color:var(--wp--preset--color--gold,#c9a24b);box-shadow:0 6px 24px -18px rgba(14,11,8,.5)}
.feinspitz-faq .wp-block-details summary{cursor:pointer;list-style:none;padding:1.15rem 0;font-family:var(--wp--preset--font-family--heading,serif);font-weight:600;font-size:1.0625rem;line-height:1.35;color:var(--wp--preset--color--base,#0e0b08);display:flex;align-items:center;justify-content:space-between;gap:1rem}
.feinspitz-faq .wp-block-details summary::-webkit-details-marker{display:none}
.feinspitz-faq .wp-block-details summary::after{content:"+";font-family:var(--wp--preset--font-family--body,sans-serif);font-weight:400;font-size:1.5rem;line-height:1;color:var(--wp--preset--color--wine,#7b1f2b);transition:transform .2s ease;flex:0 0 auto}
.feinspitz-faq .wp-block-details[open] summary::after{transform:rotate(45deg)}
.feinspitz-faq .wp-block-details summary:focus-visible{outline:2px solid var(--wp--preset--color--gold,#c9a24b);outline-offset:3px;border-radius:6px}
.feinspitz-faq .wp-block-details > :not(summary){margin-top:0;padding-bottom:1.25rem;color:rgba(14,11,8,.82)}
.feinspitz-ratgeber-card{height:100%}
.feinspitz-ratgeber-card .wp-block-post-featured-image img{aspect-ratio:16/10;object-fit:cover;width:100%;border-radius:14px}
.feinspitz-ratgeber-card .wp-block-post-title{font-size:1.25rem;line-height:1.25;margin:.9rem 0 .5rem}
.feinspitz-ratgeber-card .wp-block-post-excerpt__excerpt{color:rgba(14,11,8,.72);font-size:.95rem}
.feinspitz-article-body{max-width:none}
.feinspitz-article-body h2{margin-top:2.25rem}
.feinspitz-article-body h3{margin-top:1.5rem}
';

	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-ratgeber-inline', false );
		wp_enqueue_style( 'feinspitz-ratgeber-inline' );
		wp_add_inline_style( 'feinspitz-ratgeber-inline', $css );
	}
}, 20 );
