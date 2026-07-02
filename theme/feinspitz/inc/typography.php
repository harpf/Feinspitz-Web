<?php
/**
 * Feinspitz — Typografie.
 *
 * WordPress' wptexturize() wandelt beim Rendern automatisch " - " → " – " (en dash),
 * "--" → "–", "..." → "…" und gerade in typografische Anführungszeichen um. Dadurch
 * tauchten die bewusst entfernten Gedankenstriche als &#8211; wieder auf. Wir
 * deaktivieren diese automatische Ersetzung für eine schlichte, striches-freie
 * Typografie (gerade Anführungszeichen, einfache Bindestriche).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'run_wptexturize', '__return_false' );

/**
 * Separator-Striche entschärfen: " - " (Leerzeichen-Bindestrich-Leerzeichen)
 * ZWISCHEN BUCHSTABEN durch einen Mittelpunkt " · " ersetzen. Bewusst eng gefasst:
 *  - Wort-Bindestriche ("histamin-geprüft", "e-mail") bleiben (keine Leerzeichen).
 *  - Zahlenbereiche ("1 - 16 von 171") bleiben (keine Buchstaben ringsum).
 *  - CSS wie "calc(25% - var(…))" bleibt (kein Buchstabe vor dem Strich).
 *
 * @param string $text
 * @return string
 */
function feinspitz_normalize_dashes( $text ) {
	if ( ! is_string( $text ) || false === strpos( $text, ' - ' ) ) {
		return $text;
	}
	return preg_replace( '/(?<=\p{L}) - (?=\p{L})/u', ' · ', $text );
}

// Auf allen relevanten Text-Ausgaben filtern (Produktnamen, Titel, Inhalte,
// Auszüge, WooCommerce-Ausgaben). Block-Pattern-Texte werden zusätzlich direkt
// in den Quell-Dateien bereinigt (Patterns laufen nicht durch the_content).
// Dokumenttitel-Trenner (Browser-Tab): "Seite - Feinspitz" → "Seite · Feinspitz".
add_filter( 'document_title_separator', function () {
	return '·';
} );

foreach ( array(
	'the_title',
	'single_post_title',
	'the_content',
	'the_excerpt',
	'get_the_excerpt',
	'get_the_archive_title',
	'single_term_title',
	'woocommerce_page_title',
	'woocommerce_short_description',
	'woocommerce_product_get_name',
	'widget_text',
) as $feinspitz_dash_hook ) {
	add_filter( $feinspitz_dash_hook, 'feinspitz_normalize_dashes', 50 );
}
