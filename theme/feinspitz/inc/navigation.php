<?php
/**
 * Feinspitz · Sprachbewusste Hauptnavigation.
 *
 * Der native core/navigation-Block lokalisiert unter freiem Polylang die Menü-
 * Labels/Links NICHT pro Sprache (DE und EN zeigten dieselben deutschen Labels
 * und verlinkten DE-Seiten). Diese Datei stellt stattdessen den Shortcode
 * [feinspitz_nav] bereit, der die Hauptnavigation deterministisch pro Sprache
 * rendert · deutsche Labels/Links auf DE, englische Labels + /en/-Links auf EN.
 *
 * Eingebunden in parts/header.html per core/shortcode-Block (Block-Theme-Parts
 * führen kein PHP aus). Mobile-Menü rein per CSS (Checkbox-Toggle), ohne JS.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aktuelle Sprache ('de'|'en'), Fallback 'de'.
 */
function feinspitz_current_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language( 'slug' );
		if ( $l ) {
			return $l;
		}
	}
	return 'de';
}

/**
 * Permalink einer Seite (per Slug) in der aktuellen Sprache.
 * Existiert eine Übersetzung, wird deren URL zurückgegeben.
 *
 * @param string $slug     DE-Seiten-Slug (z. B. 'ueber-uns').
 * @param string $fallback Fallback-Pfad, falls die Seite fehlt (z. B. '/ueber-uns/').
 * @return string
 */
function feinspitz_nav_page_url( $slug, $fallback ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		return home_url( $fallback );
	}
	$id = $page->ID;
	if ( 'en' === feinspitz_current_lang() && function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( $id, 'en' );
		if ( $tr ) {
			$id = $tr;
		}
	}
	return get_permalink( $id );
}

/**
 * Term-Link (Kategorie) in der aktuellen Sprache. Existiert eine Übersetzung
 * des Terms, wird deren Archiv-URL zurückgegeben (z. B. ratgeber → guide auf /en/).
 *
 * @param string $slug     DE-Kategorie-Slug.
 * @param string $fallback Fallback-Pfad.
 * @return string
 */
function feinspitz_nav_category_url( $de_slug, $en_slug, $fallback ) {
	// Auf /en/ filtert Polylang Term-Abfragen auf die aktuelle Sprache · daher
	// direkt den sprachrichtigen Slug nachschlagen (ratgeber = DE, guide = EN).
	$slug = ( 'en' === feinspitz_current_lang() ) ? $en_slug : $de_slug;
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		return home_url( $fallback );
	}
	$link = get_term_link( (int) $term->term_id, 'category' );
	return is_wp_error( $link ) ? home_url( $fallback ) : $link;
}

/**
 * Navigations-Items der aktuellen Sprache: array of [ label, url ].
 */
function feinspitz_nav_items() {
	$is_en = ( 'en' === feinspitz_current_lang() );

	if ( $is_en ) {
		return array(
			array( 'Shop', home_url( '/shop/' ) ),
			array( 'Guide', feinspitz_nav_category_url( 'ratgeber', 'guide', '/category/ratgeber/' ) ),
			array( 'FAQ', feinspitz_nav_page_url( 'faq', '/faq/' ) ),
			array( 'About us', feinspitz_nav_page_url( 'ueber-uns', '/ueber-uns/' ) ),
			array( 'Contact', feinspitz_nav_page_url( 'kontakt', '/kontakt/' ) ),
		);
	}

	return array(
		array( __( 'Shop', 'feinspitz' ), home_url( '/shop/' ) ),
		array( __( 'Ratgeber', 'feinspitz' ), feinspitz_nav_category_url( 'ratgeber', 'guide', '/category/ratgeber/' ) ),
		array( __( 'FAQ', 'feinspitz' ), feinspitz_nav_page_url( 'faq', '/faq/' ) ),
		array( __( 'Über uns', 'feinspitz' ), feinspitz_nav_page_url( 'ueber-uns', '/ueber-uns/' ) ),
		array( __( 'Kontakt', 'feinspitz' ), feinspitz_nav_page_url( 'kontakt', '/kontakt/' ) ),
	);
}

/**
 * Shortcode [feinspitz_nav] - rendert die sprachbewusste Hauptnavigation.
 */
add_shortcode( 'feinspitz_nav', function () {
	$items   = feinspitz_nav_items();
	$current = untrailingslashit( home_url( add_query_arg( array() ) ) );

	$lis = '';
	foreach ( $items as $item ) {
		list( $label, $url ) = $item;
		$is_current = ( untrailingslashit( $url ) === $current );
		$lis       .= sprintf(
			'<li class="feinspitz-nav__item%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
			$is_current ? ' is-current' : '',
			esc_url( $url ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $label )
		);
	}

	$toggle_label = ( 'en' === feinspitz_current_lang() ) ? 'Menu' : 'Menü';

	return sprintf(
		'<nav class="feinspitz-nav" aria-label="%1$s">'
		. '<input type="checkbox" id="feinspitz-nav-toggle" class="feinspitz-nav__toggle" hidden />'
		. '<label for="feinspitz-nav-toggle" class="feinspitz-nav__burger" role="button" tabindex="0" aria-label="%2$s"><span></span><span></span><span></span></label>'
		. '<ul class="feinspitz-nav__list">%3$s</ul>'
		. '</nav>',
		esc_attr( $toggle_label ),
		esc_attr( $toggle_label ),
		$lis
	);
} );

/**
 * Navigations-Styles (Desktop-Reihe + CSS-only Mobile-Dropdown), an das
 * Theme-Stylesheet gehängt, damit style.css/theme.json unberührt bleiben.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-nav{position:relative;display:flex;align-items:center}
.feinspitz-nav__list{list-style:none;display:flex;flex-wrap:wrap;align-items:center;gap:.25rem;margin:0;padding:0}
.feinspitz-nav__item a{display:inline-block;padding:.5rem .85rem;color:inherit;text-decoration:none;font-weight:600;font-size:.95rem;letter-spacing:.01em;border-radius:999px;transition:color .15s ease,background .15s ease}
.feinspitz-nav__item a:hover,.feinspitz-nav__item a:focus{color:var(--wp--preset--color--gold)}
.feinspitz-nav__item.is-current a{color:var(--wp--preset--color--gold)}
.feinspitz-nav__burger{display:none;flex-direction:column;justify-content:center;gap:5px;width:2.5rem;height:2.5rem;cursor:pointer;border-radius:.5rem}
.feinspitz-nav__burger span{display:block;width:22px;height:2px;background:currentColor;transition:transform .2s ease,opacity .2s ease}
@media (max-width:800px){
  .feinspitz-nav__burger{display:flex}
  .feinspitz-nav__list{position:absolute;top:calc(100% + .5rem);right:0;flex-direction:column;align-items:stretch;gap:0;min-width:12rem;background:var(--wp--preset--color--base);border:1px solid rgba(255,255,255,.12);border-radius:.75rem;padding:.4rem;box-shadow:0 12px 30px rgba(0,0,0,.35);display:none;z-index:120}
  .feinspitz-nav__toggle:checked ~ .feinspitz-nav__list{display:flex}
  .feinspitz-nav__item a{display:block;padding:.7rem .9rem}
  .feinspitz-nav__toggle:checked ~ .feinspitz-nav__burger span:nth-child(1){transform:translateY(7px) rotate(45deg)}
  .feinspitz-nav__toggle:checked ~ .feinspitz-nav__burger span:nth-child(2){opacity:0}
  .feinspitz-nav__toggle:checked ~ .feinspitz-nav__burger span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	}
}, 21 );
