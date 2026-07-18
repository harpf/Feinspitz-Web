<?php
/**
 * Feinspitz · Redaktion — „Einfach bearbeiten"-Zeilenaktionen.
 *
 * Ergänzt die Produkt- und Beitragsliste um einen Link in die jeweilige
 * geführte Feinspitz-Maske. Der Standard-„Bearbeiten"-Link bleibt bestehen.
 * Reiner Admin-Code.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

/**
 * „Einfach bearbeiten" an Produkt- und Beitragszeilen anhängen.
 *
 * @param array   $actions Vorhandene Zeilenaktionen.
 * @param WP_Post $post    Aktueller Beitrag/Produkt.
 * @return array
 */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'post' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
		$url                       = admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG . '&id=' . (int) $post->ID );
		$actions['feinspitz_edit'] = sprintf( '<a href="%s">Einfach bearbeiten</a>', esc_url( $url ) );
	}
	if ( 'product' === $post->post_type && current_user_can( 'edit_product', $post->ID ) ) {
		$url                       = admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG . '&id=' . (int) $post->ID );
		$actions['feinspitz_edit'] = sprintf( '<a href="%s">Einfach bearbeiten</a>', esc_url( $url ) );
	}
	return $actions;
}, 10, 2 );
