<?php
/**
 * Feinspitz · HTML-Sitemap.
 *
 * Shortcode [feinspitz_sitemap] rendert eine gestaltete, gruppierte Übersicht
 * (Seiten, Shop-Kategorien, Ratgeber, Rechtliches) als benutzerfreundliche
 * Alternative zur rohen XML-Sitemap. Wird in die Seite "Sitemap" eingebunden.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Eine Sitemap-Sektion (Überschrift + Linkliste) rendern.
 *
 * @param string $title Sektionstitel.
 * @param array  $links Liste [ label, url ].
 * @return string
 */
function feinspitz_sitemap_section( $title, $links ) {
	$links = array_filter( $links, function ( $l ) {
		return ! empty( $l[1] );
	} );
	if ( empty( $links ) ) {
		return '';
	}
	$items = '';
	foreach ( $links as $l ) {
		$items .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $l[1] ), esc_html( $l[0] ) );
	}
	return sprintf(
		'<section class="feinspitz-sitemap__group"><h2 class="feinspitz-sitemap__title">%s</h2><ul class="feinspitz-sitemap__list">%s</ul></section>',
		esc_html( $title ),
		$items
	);
}

add_shortcode( 'feinspitz_sitemap', function () {
	$is_en = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();
	$purl  = function ( $slug, $fallback ) {
		return function_exists( 'feinspitz_nav_page_url' ) ? feinspitz_nav_page_url( $slug, $fallback ) : home_url( $fallback );
	};
	$cat_url = function ( $de, $en, $fallback ) {
		return function_exists( 'feinspitz_nav_category_url' ) ? feinspitz_nav_category_url( $de, $en, $fallback ) : home_url( $fallback );
	};

	$t = $is_en
		? array( 'pages' => 'Pages', 'shop' => 'Shop categories', 'guide' => 'Guide', 'legal' => 'Legal' )
		: array( 'pages' => 'Seiten', 'shop' => 'Shop-Kategorien', 'guide' => 'Ratgeber', 'legal' => 'Rechtliches' );

	$out = '<div class="feinspitz-sitemap">';

	// Hauptseiten.
	$out .= feinspitz_sitemap_section( $t['pages'], array(
		array( $is_en ? 'Home' : 'Startseite', home_url( '/' ) ),
		array( 'Shop', home_url( '/shop/' ) ),
		array( $is_en ? 'Guide' : 'Ratgeber', $cat_url( 'ratgeber', 'guide', '/category/ratgeber/' ) ),
		array( 'FAQ', $purl( 'faq', '/faq/' ) ),
		array( $is_en ? 'About us' : 'Über uns', $purl( 'ueber-uns', '/ueber-uns/' ) ),
		array( $is_en ? 'Contact' : 'Kontakt', $purl( 'kontakt', '/kontakt/' ) ),
	) );

	// Shop-Kategorien (nur nicht-leere Top-Level).
	if ( taxonomy_exists( 'product_cat' ) ) {
		$cats  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
		$links = array();
		if ( ! is_wp_error( $cats ) ) {
			foreach ( $cats as $c ) {
				if ( 'uncategorized' === $c->slug ) {
					continue;
				}
				$name = ( function_exists( 'feinspitz_shop_category_en_title' ) && $is_en ) ? feinspitz_shop_category_en_title( $c ) : null;
				$link = get_term_link( $c );
				$links[] = array( $name ? $name : $c->name, is_wp_error( $link ) ? '' : $link );
			}
		}
		$out .= feinspitz_sitemap_section( $t['shop'], $links );
	}

	// Ratgeber-Beiträge (aktuelle Sprache).
	$posts = get_posts( array(
		'category_name'  => $is_en ? 'guide' : 'ratgeber',
		'posts_per_page' => 50,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	$links = array();
	foreach ( $posts as $p ) {
		$links[] = array( get_the_title( $p ), get_permalink( $p ) );
	}
	$out .= feinspitz_sitemap_section( $t['guide'], $links );

	// Rechtliches.
	$out .= feinspitz_sitemap_section( $t['legal'], array(
		array( $is_en ? 'Imprint' : 'Impressum', $purl( 'about', '/about/' ) ),
		array( 'AGB', $purl( 'agb', '/agb/' ) ),
		array( $is_en ? 'Delivery & Payment' : 'Liefer- und Zahlungsbedingungen', home_url( '/liefer-und-zahlungsbedingungen/' ) ),
		array( $is_en ? 'Privacy' : 'Datenschutz', home_url( '/datenschutzerklaerung/' ) ),
		array( $is_en ? 'Cookie Policy' : 'Cookie-Richtlinie', home_url( '/cookie-richtlinie/' ) ),
		array( 'XML-Sitemap', home_url( '/wp-sitemap.xml' ) ),
	) );

	$out .= '</div>';
	return $out;
} );

add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-sitemap{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:clamp(1.5rem,4vw,2.5rem);margin:2rem 0}
.feinspitz-sitemap__title{font-family:var(--wp--preset--font-family--heading);font-size:1.1rem;color:var(--wp--preset--color--wine);margin:0 0 .75rem;padding-bottom:.5rem;border-bottom:2px solid var(--wp--preset--color--gold)}
.feinspitz-sitemap__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem}
.feinspitz-sitemap__list a{text-decoration:none;color:var(--wp--preset--color--base);transition:color .15s ease}
.feinspitz-sitemap__list a:hover,.feinspitz-sitemap__list a:focus{color:var(--wp--preset--color--wine)}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	}
}, 21 );
