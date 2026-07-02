<?php
/**
 * Feinspitz · Social-Media-Links.
 *
 * Die URLs liegen in der Option `feinspitz_social` (pro Plattform ein Eintrag) und
 * sind über die REST-API (/wp/v2/settings) sowie skriptbar setzbar. Es werden nur
 * Plattformen mit hinterlegter URL angezeigt · so lassen sich soziale Medien
 * jederzeit einbinden, ohne Code zu ändern.
 *
 * Rendern: Shortcode [feinspitz_social] oder Funktion feinspitz_render_social().
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unterstützte Plattformen: Schlüssel => [ Label, SVG-Pfad(e) ].
 * SVGs sind einfarbig (currentColor), 24x24-Viewbox.
 */
function feinspitz_social_platforms() {
	// Outline-Icons (Lucide-Stil). Das umschliessende <svg> nutzt
	// fill="none" stroke="currentColor" · gefüllte Elemente (z. B. YouTube-Dreieck)
	// setzen fill/stroke inline.
	return array(
		'instagram' => array( 'Instagram', '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>' ),
		'facebook'  => array( 'Facebook', '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ),
		'linkedin'  => array( 'LinkedIn', '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>' ),
		'x'         => array( 'X', '<path d="M4 4l16 16M20 4L4 20"/>' ),
		'youtube'   => array( 'YouTube', '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="currentColor" stroke="none"/>' ),
		'tiktok'    => array( 'TikTok', '<path d="M15 4c.5 2 1.9 3.4 4 3.5v3c-1.5 0-2.9-.5-4-1.3V15a5.5 5.5 0 1 1-5.5-5.5c.3 0 .6 0 .9.1v3.1a2.5 2.5 0 1 0 1.6 2.3V4z"/>' ),
		'whatsapp'  => array( 'WhatsApp', '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z"/><path d="M8.6 8.4c.2-.4.4-.4.6-.4h.5c.2 0 .4.1.5.4l.6 1.4c0 .2 0 .3-.1.5l-.4.4c-.1.2-.1.3 0 .5.5.7 1.1 1.3 1.9 1.7.2.1.3.1.4 0l.5-.5c.1-.1.3-.2.5-.1l1.3.7c.2.1.3.2.3.4"/>' ),
	);
}

/**
 * Konfigurierte Social-Links: [ key => url ] (nur nicht-leere).
 */
function feinspitz_social_urls() {
	$opt = get_option( 'feinspitz_social', array() );
	if ( ! is_array( $opt ) ) {
		$opt = array();
	}
	return array_filter( array_map( 'trim', $opt ) );
}

/**
 * Social-Media-Icons als HTML rendern (leer, wenn nichts konfiguriert).
 *
 * @param string $class Zusätzliche CSS-Klasse.
 * @return string
 */
function feinspitz_render_social( $class = '' ) {
	$urls = feinspitz_social_urls();
	if ( empty( $urls ) ) {
		return '';
	}
	$platforms = feinspitz_social_platforms();
	$icons     = '';
	foreach ( $urls as $key => $url ) {
		if ( empty( $platforms[ $key ] ) ) {
			continue;
		}
		list( $label, $paths ) = $platforms[ $key ];
		$icons .= sprintf(
			'<a class="feinspitz-social__link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg></a>',
			esc_url( $url ),
			esc_attr( $label ),
			$paths // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- statische SVG-Pfade.
		);
	}
	if ( '' === $icons ) {
		return '';
	}
	return sprintf( '<div class="feinspitz-social %s">%s</div>', esc_attr( $class ), $icons );
}

add_shortcode( 'feinspitz_social', function () {
	return feinspitz_render_social();
} );

/**
 * Option registrieren (über REST /wp/v2/settings setzbar → einfach integrierbar).
 */
add_action( 'init', function () {
	register_setting(
		'general',
		'feinspitz_social',
		array(
			'type'         => 'object',
			'default'      => array(),
			'show_in_rest' => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => array( 'type' => 'string' ),
				),
			),
		)
	);
} );

add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-social{display:flex;flex-wrap:wrap;gap:.5rem;margin:1rem 0 0}
.feinspitz-social__link{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;color:var(--wp--preset--color--contrast);opacity:1;border:1px solid rgba(246,241,231,.28);transition:background .15s ease,color .15s ease,border-color .15s ease}
.feinspitz-footer a.feinspitz-social__link{opacity:1}
.feinspitz-social__link:hover,.feinspitz-social__link:focus{background:var(--wp--preset--color--gold);color:var(--wp--preset--color--base);border-color:var(--wp--preset--color--gold)}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	}
}, 21 );
