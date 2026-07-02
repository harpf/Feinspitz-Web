<?php
/**
 * Pattern-Markup: FAQ-Akkordeon.
 *
 * Registriert in inc/ratgeber.php als feinspitz/faq-accordion. Der Inhalt stammt
 * bewusst NICHT aus statischem Markup, sondern aus feinspitz_faq_accordion_markup()
 * — derselben Quelle wie das FAQPage-JSON-LD, damit sichtbarer Inhalt und
 * strukturierte Daten deckungsgleich bleiben.
 *
 * Diese Datei liefert das Markup (kein Datei-Header) — Strings via Textdomain
 * feinspitz innerhalb der Builder-Funktion.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'feinspitz_faq_accordion_markup' ) ) {
	echo feinspitz_faq_accordion_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Builder liefert bereits sicheres Block-Markup.
}
