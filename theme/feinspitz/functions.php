<?php
/**
 * Feinspitz Theme — Bootstrap.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme-Supports & i18n.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'feinspitz', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );

	// WooCommerce.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

/**
 * Fonts (Google Fonts: Fraunces + Inter) einbinden.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'feinspitz-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Inter:wght@400..700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'feinspitz-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );

/**
 * Erweiterungspunkt für DevSwarm-Branches:
 * Jeder Feature-Branch legt seine eigene Datei unter theme/feinspitz/inc/<branch>.php an
 * (z. B. Pattern-Registrierung), um Merge-Konflikte in functions.php zu vermeiden.
 */
foreach ( glob( get_template_directory() . '/inc/*.php' ) as $include ) {
	require_once $include;
}
