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
	return array(
		'instagram' => array( 'Instagram', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>' ),
		'facebook'  => array( 'Facebook', '<path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14V9c0-.6.4-1 1-1z"/>' ),
		'linkedin'  => array( 'LinkedIn', '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4M11 10v7" fill="none" stroke="currentColor" stroke-width="2"/>' ),
		'x'         => array( 'X', '<path d="M4 4l16 16M20 4L4 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>' ),
		'youtube'   => array( 'YouTube', '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="M10 9.5l5 2.5-5 2.5z" fill="var(--wp--preset--color--base,#000)"/>' ),
		'tiktok'    => array( 'TikTok', '<path d="M14 4v8.5a3.5 3.5 0 1 1-3-3.46V11a1.5 1.5 0 1 0 1 1.4V4h2c.3 1.6 1.4 2.7 3 3v2c-1.1 0-2.1-.3-3-.9z"/>' ),
		'whatsapp'  => array( 'WhatsApp', '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3zm4.3 12.3c-.2.5-1 1-1.5 1-.4 0-.9.2-3-1-2.4-1.3-3.9-3.9-4-4.1-.1-.2-.9-1.2-.9-2.3s.6-1.6.8-1.8c.2-.2.4-.3.6-.3h.4c.2 0 .4 0 .6.4l.7 1.6c.1.2.1.4 0 .5l-.3.5c-.1.2-.3.3-.1.6.2.3.7 1.2 1.6 1.9 1.1.9 1.7 1 2 .8.2-.2.4-.6.6-.8.2-.2.3-.2.6-.1l1.6.8c.3.1.4.2.5.3.1.3.1.6-.1 1z"/>' ),
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
			'<a class="feinspitz-social__link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">%3$s</svg></a>',
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
.feinspitz-social__link{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;color:var(--wp--preset--color--contrast);border:1px solid rgba(246,241,231,.28);transition:background .15s ease,color .15s ease,border-color .15s ease}
.feinspitz-social__link:hover,.feinspitz-social__link:focus{background:var(--wp--preset--color--gold);color:var(--wp--preset--color--base);border-color:var(--wp--preset--color--gold)}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	}
}, 21 );
