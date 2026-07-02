<?php
/**
 * Feinspitz · Weinlexikon / Glossar (feature/weinlexikon).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Weinlexikon-Branch. Sie stellt bereit:
 *
 *  - Shortcode [feinspitz_lexikon_index] · rendert die alphabetisch gruppierte
 *    Glossar-Übersicht (A–Z-Sprungleiste + Karten-Grid) sprachbewusst: auf DE die
 *    Kategorie „Weinlexikon" (Slug weinlexikon), auf EN „Wine Glossary" (Slug
 *    glossary). Die Übersichts-Templates category-weinlexikon.html (DE) und
 *    category-glossary.html (EN) binden ihn per core/shortcode ein.
 *  - Meta-Description im <head> für das Lexikon-Kategorie-Archiv (kein SEO-Plugin
 *    nötig · analog inc/ratgeber.php, das bereits die EINZELbeiträge mit Excerpt
 *    abdeckt: is_singular('post') && has_excerpt() gilt auch für Lexikon-Beiträge,
 *    daher hier NUR das Archiv, um keine doppelte Meta-Description auszugeben).
 *  - Gescopte Inline-Styles fürs Karten-Grid und die A–Z-Navigation (an das
 *    Theme-Stylesheet gehängt, damit style.css · Phase-0-Datei · unberührt bleibt).
 *
 * Die eigentlichen Glossar-Inhalte (Kategorie + Beiträge) werden NICHT hier,
 * sondern idempotent per REST über scripts/content/lexikon.mjs (DE) bzw.
 * scripts/content/lexikon-en.mjs (EN) angelegt.
 *
 * Textdomain: feinspitz.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kategorie-Slug des Lexikons in der aktuellen Sprache.
 *
 * DE → weinlexikon, EN → glossary. Fällt auf 'weinlexikon' zurück, solange keine
 * EN-Kategorie existiert. Nutzt feinspitz_current_lang() aus inc/navigation.php,
 * mit sicherem Fallback, falls diese Datei einmal fehlen sollte.
 *
 * @return string
 */
function feinspitz_lexikon_category_slug() {
	$lang = function_exists( 'feinspitz_current_lang' ) ? feinspitz_current_lang() : 'de';
	return ( 'en' === $lang ) ? 'glossary' : 'weinlexikon';
}

/**
 * Anfangsbuchstabe eines Titels für die A–Z-Gruppierung (Umlaute normalisiert).
 *
 * Ä→A, Ö→O, Ü→U, ß→S; Ziffern/Sonderzeichen landen unter „#".
 *
 * @param string $title Beitragstitel.
 * @return string Ein Grossbuchstabe A–Z oder „#".
 */
function feinspitz_lexikon_initial( $title ) {
	$title = trim( wp_strip_all_tags( (string) $title ) );
	if ( '' === $title ) {
		return '#';
	}

	$first = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : substr( $title, 0, 1 );
	$upper = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first ) : strtoupper( $first );

	$map = array(
		'Ä' => 'A',
		'Ö' => 'O',
		'Ü' => 'U',
		'ß' => 'S',
		'Á' => 'A',
		'À' => 'A',
		'É' => 'E',
		'È' => 'E',
	);
	if ( isset( $map[ $upper ] ) ) {
		$upper = $map[ $upper ];
	}

	return preg_match( '/^[A-Z]$/', $upper ) ? $upper : '#';
}

/**
 * Sprachbewusste UI-Strings der Übersicht (literale DE/EN-Strings statt gettext,
 * um die i18n-Pipeline · languages/*.po · nicht anzufassen · analog navigation.php).
 *
 * @return array<string,string>
 */
function feinspitz_lexikon_strings() {
	$is_en = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();
	if ( $is_en ) {
		return array(
			'jump_label' => 'Jump to letter',
			'more'       => 'Read entry',
			'empty'      => 'Glossary entries will appear here soon.',
		);
	}
	return array(
		'jump_label' => 'Zum Buchstaben springen',
		'more'       => 'Eintrag lesen',
		'empty'      => 'Bald finden Sie hier die Einträge unseres Weinlexikons.',
	);
}

/**
 * Shortcode [feinspitz_lexikon_index] · alphabetisch gruppierte Glossar-Übersicht.
 *
 * Fragt alle Beiträge der Lexikon-Kategorie der aktuellen Sprache ab (Polylang
 * filtert Frontend-Queries automatisch auf die aktuelle Sprache), gruppiert sie
 * nach Anfangsbuchstaben und rendert eine A–Z-Sprungleiste sowie je Buchstabe ein
 * Karten-Grid. Die einzelnen Karten verlinken auf den jeweiligen Beitrag (single.html).
 *
 * @return string HTML.
 */
function feinspitz_lexikon_index_shortcode() {
	$slug = feinspitz_lexikon_category_slug();

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'category_name'       => $slug,
			'posts_per_page'      => -1,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	$strings = feinspitz_lexikon_strings();

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return '<p class="feinspitz-lexikon__empty">' . esc_html( $strings['empty'] ) . '</p>';
	}

	// Beiträge nach Anfangsbuchstaben gruppieren.
	$groups = array();
	foreach ( $query->posts as $post ) {
		$letter                = feinspitz_lexikon_initial( $post->post_title );
		$groups[ $letter ][]   = $post;
	}
	wp_reset_postdata();

	ksort( $groups );

	// A–Z-Sprungleiste: nur vorhandene Buchstaben sind aktive Links.
	$alphabet = array_merge( range( 'A', 'Z' ), array( '#' ) );
	$jump     = '';
	foreach ( $alphabet as $letter ) {
		if ( isset( $groups[ $letter ] ) ) {
			$jump .= sprintf(
				'<a class="feinspitz-lexikon__jump-link" href="#lex-%1$s">%2$s</a>',
				esc_attr( strtolower( '#' === $letter ? 'sym' : $letter ) ),
				esc_html( $letter )
			);
		} else {
			$jump .= sprintf(
				'<span class="feinspitz-lexikon__jump-link is-disabled" aria-hidden="true">%s</span>',
				esc_html( $letter )
			);
		}
	}

	// Buchstaben-Sektionen mit Karten-Grid.
	$sections = '';
	foreach ( $groups as $letter => $posts ) {
		$anchor = strtolower( '#' === $letter ? 'sym' : $letter );

		$cards = '';
		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post );
			$title     = get_the_title( $post );
			$excerpt   = wp_strip_all_tags( get_the_excerpt( $post ) );

			$cards .= sprintf(
				'<article class="feinspitz-lexikon__card">'
				. '<h3 class="feinspitz-lexikon__term"><a href="%1$s">%2$s</a></h3>'
				. '<p class="feinspitz-lexikon__desc">%3$s</p>'
				. '<a class="feinspitz-lexikon__more" href="%1$s">%4$s<span aria-hidden="true"> →</span></a>'
				. '</article>',
				esc_url( $permalink ),
				esc_html( $title ),
				esc_html( $excerpt ),
				esc_html( $strings['more'] )
			);
		}

		$sections .= sprintf(
			'<section class="feinspitz-lexikon__section" id="lex-%1$s">'
			. '<h2 class="feinspitz-lexikon__letter">%2$s</h2>'
			. '<div class="feinspitz-lexikon__grid">%3$s</div>'
			. '</section>',
			esc_attr( $anchor ),
			esc_html( $letter ),
			$cards
		);
	}

	return sprintf(
		'<div class="feinspitz-lexikon">'
		. '<nav class="feinspitz-lexikon__jump" aria-label="%1$s">%2$s</nav>'
		. '%3$s'
		. '</div>',
		esc_attr( $strings['jump_label'] ),
		$jump,
		$sections
	);
}
add_shortcode( 'feinspitz_lexikon_index', 'feinspitz_lexikon_index_shortcode' );

/**
 * Meta-Description im <head> · NUR auf dem Lexikon-Kategorie-Archiv.
 *
 * Die EINZELbeiträge (mit Excerpt) sind bereits über inc/ratgeber.php abgedeckt
 * (is_singular('post') && has_excerpt()); hier ergänzen wir ausschliesslich das
 * Archiv, damit keine doppelte Meta-Description entsteht. Bewusst eng gescoped.
 */
add_action( 'wp_head', function () {
	if ( ! is_category( array( 'weinlexikon', 'glossary' ) ) ) {
		return;
	}

	$is_en = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();

	$term        = get_queried_object();
	$description  = ( $term && ! empty( $term->description ) ) ? $term->description : '';
	if ( '' === $description ) {
		$description = $is_en
			? 'The Feinspitz wine glossary: grape varieties, wine regions, tasting terms and histamine knowledge · explained clearly and concisely.'
			: 'Das Feinspitz-Weinlexikon: Rebsorten, Weinregionen, Verkostungsbegriffe und Histamin-Wissen · kurz und klar erklärt.';
	}

	$description = trim( wp_strip_all_tags( $description ) );
	if ( '' === $description ) {
		return;
	}
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $description ) > 160 ) {
		$description = rtrim( mb_substr( $description, 0, 157 ) ) . '…';
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}, 5 );

/**
 * Gescopte Styles für das Weinlexikon (Karten-Grid + A–Z-Navigation, Bold-Stil).
 *
 * An das in functions.php registrierte Theme-Stylesheet gehängt (style.css bleibt
 * Phase-0-unangetastet), mit Fallback-Handle · analog inc/ratgeber.php.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-lexikon{--lex-wine:var(--wp--preset--color--wine,#7b1f2b);--lex-gold:var(--wp--preset--color--gold,#c9a24b)}
.feinspitz-lexikon__jump{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;gap:.25rem;justify-content:center;padding:.6rem .4rem;margin:0 0 2.5rem;background:rgba(255,255,255,.9);backdrop-filter:blur(6px);border-radius:999px;border:1px solid rgba(123,31,43,.14)}
.feinspitz-lexikon__jump-link{display:inline-flex;align-items:center;justify-content:center;min-width:1.9rem;height:1.9rem;padding:0 .35rem;border-radius:999px;font-family:var(--wp--preset--font-family--heading,serif);font-weight:700;font-size:.9rem;line-height:1;color:var(--lex-wine);text-decoration:none;transition:background .15s ease,color .15s ease}
a.feinspitz-lexikon__jump-link:hover,a.feinspitz-lexikon__jump-link:focus{background:var(--lex-wine);color:#fff}
.feinspitz-lexikon__jump-link.is-disabled{color:rgba(14,11,8,.22);pointer-events:none}
.feinspitz-lexikon__section{margin:0 0 3rem;scroll-margin-top:4.5rem}
.feinspitz-lexikon__letter{font-family:var(--wp--preset--font-family--heading,serif);font-weight:700;font-size:clamp(1.6rem,4vw,2.25rem);color:var(--lex-wine);margin:0 0 1.25rem;padding-bottom:.5rem;border-bottom:2px solid var(--lex-gold)}
.feinspitz-lexikon__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,17rem),1fr));gap:1.25rem}
.feinspitz-lexikon__card{display:flex;flex-direction:column;height:100%;padding:1.4rem 1.5rem;background:#fff;border:1px solid rgba(123,31,43,.16);border-radius:16px;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
.feinspitz-lexikon__card:hover{border-color:var(--lex-gold);box-shadow:0 12px 30px -22px rgba(14,11,8,.55);transform:translateY(-2px)}
.feinspitz-lexikon__term{font-family:var(--wp--preset--font-family--heading,serif);font-weight:700;font-size:1.2rem;line-height:1.2;margin:0 0 .6rem}
.feinspitz-lexikon__term a{color:var(--wp--preset--color--base,#0e0b08);text-decoration:none}
.feinspitz-lexikon__term a:hover,.feinspitz-lexikon__term a:focus{color:var(--lex-wine)}
.feinspitz-lexikon__desc{flex:1 1 auto;margin:0 0 1rem;color:rgba(14,11,8,.74);font-size:.94rem;line-height:1.5}
.feinspitz-lexikon__more{align-self:flex-start;font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--lex-wine);text-decoration:none}
.feinspitz-lexikon__more:hover,.feinspitz-lexikon__more:focus{color:var(--lex-gold)}
.feinspitz-lexikon__empty{text-align:center;color:rgba(14,11,8,.7)}
';

	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-lexikon-inline', false );
		wp_enqueue_style( 'feinspitz-lexikon-inline' );
		wp_add_inline_style( 'feinspitz-lexikon-inline', $css );
	}
}, 20 );
