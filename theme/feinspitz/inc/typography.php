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
