<?php
/**
 * Feinspitz · Redaktion — geführte Artikel-Maske (Ratgeber / Weinlexikon).
 *
 * Rendert ein einfaches Formular zum Schreiben/Bearbeiten eines Beitrags und
 * speichert es über die WP-Post-API. Die Kategorie wird automatisch aus dem
 * gewählten Typ gesetzt (kein Kategorie-Fehler mehr). Ergänzt den Gutenberg-
 * Editor (der als Fallback erreichbar bleibt). Reiner Admin-Code.
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
 * Artikel-Typen: Kategorie-Slug => deutsches Label.
 *
 * @return array<string,string>
 */
function feinspitz_article_types() {
	return array(
		'ratgeber'    => 'Ratgeber',
		'weinlexikon' => 'Weinlexikon',
	);
}

/**
 * Artikel-Maske rendern (leer = neu, ?id=… = bearbeiten).
 */
function feinspitz_article_form_render() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}

	echo '<div class="wrap"><h1>Neuer Artikel</h1>';
	feinspitz_forms_notice();

	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post = $id ? get_post( $id ) : null;

	$title   = $post ? $post->post_title : '';
	$excerpt = $post ? $post->post_excerpt : '';
	$content = $post ? $post->post_content : '';
	$image   = $post ? (int) get_post_thumbnail_id( $post ) : 0;
	$status  = $post ? $post->post_status : 'publish';

	// Aktuellen Typ aus zugewiesener Kategorie ableiten.
	$type = 'ratgeber';
	if ( $post ) {
		foreach ( array_keys( feinspitz_article_types() ) as $slug ) {
			if ( has_term( $slug, 'category', $post ) ) {
				$type = $slug;
				break;
			}
		}
	}

	if ( $post ) {
		echo '<p>Sie bearbeiten: <strong>' . esc_html( $title ) . '</strong></p>';
		if ( strpos( $content, '<!-- wp:' ) !== false ) {
			echo '<div class="notice notice-info inline"><p>Dieser Beitrag wurde mit dem Block-Editor erstellt. '
				. 'Für komplexe Layouts empfiehlt sich weiterhin der Standard-Editor.</p></div>';
		}
	}

	feinspitz_forms_open( 'feinspitz_save_article', 'feinspitz_save_article', array( 'feinspitz_id' => $id ) );

	echo '<h2>Art des Artikels</h2>';
	feinspitz_forms_radio( 'feinspitz_type', 'Typ', feinspitz_article_types(), $type );

	echo '<h2>Inhalt</h2>';
	feinspitz_forms_text( 'feinspitz_title', 'Titel', $title, true );
	feinspitz_forms_textarea( 'feinspitz_excerpt', 'Teaser / Kurzbeschreibung (erscheint in der Übersicht)', $excerpt, 3 );

	echo '<h2>Beitragsbild</h2>';
	feinspitz_forms_image( 'feinspitz_image', 'Bild', $image );

	echo '<h2>Text</h2>';
	feinspitz_forms_editor( 'feinspitz_content', $content );

	echo '<h2>Veröffentlichung</h2>';
	feinspitz_forms_radio(
		'feinspitz_status',
		'Status',
		array( 'publish' => 'Veröffentlichen', 'draft' => 'Als Entwurf speichern' ),
		$status === 'draft' ? 'draft' : 'publish'
	);

	echo '<div class="feinspitz-submit"><button type="submit" class="button button-primary button-hero">Speichern</button></div>';
	feinspitz_forms_close();
	echo '</div>';
}

/**
 * Artikel speichern (admin-post.php).
 */
add_action( 'admin_post_feinspitz_save_article', 'feinspitz_article_form_save' );
function feinspitz_article_form_save() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	check_admin_referer( 'feinspitz_save_article' );

	$id      = isset( $_POST['feinspitz_id'] ) ? absint( $_POST['feinspitz_id'] ) : 0;
	$type    = feinspitz_forms_choice( isset( $_POST['feinspitz_type'] ) ? wp_unslash( $_POST['feinspitz_type'] ) : '', array_keys( feinspitz_article_types() ), 'ratgeber' );
	$title   = isset( $_POST['feinspitz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['feinspitz_title'] ) ) : '';
	$excerpt = isset( $_POST['feinspitz_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feinspitz_excerpt'] ) ) : '';
	$content = isset( $_POST['feinspitz_content'] ) ? wp_kses_post( wp_unslash( $_POST['feinspitz_content'] ) ) : '';
	$status  = feinspitz_forms_choice( isset( $_POST['feinspitz_status'] ) ? wp_unslash( $_POST['feinspitz_status'] ) : '', array( 'publish', 'draft' ), 'draft' );
	$image   = isset( $_POST['feinspitz_image'] ) ? absint( $_POST['feinspitz_image'] ) : 0;

	if ( '' === $title ) {
		feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	$postarr = array(
		'post_type'    => 'post',
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => $status,
	);

	if ( $id ) {
		$postarr['ID'] = $id;
		$post_id       = wp_update_post( $postarr, true );
	} else {
		$post_id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	// Kategorie aus Typ (über Term-ID).
	$term = get_term_by( 'slug', $type, 'category' );
	if ( $term ) {
		wp_set_post_terms( $post_id, array( (int) $term->term_id ), 'category', false );
	}

	// Beitragsbild.
	if ( $image ) {
		set_post_thumbnail( $post_id, $image );
	} else {
		delete_post_thumbnail( $post_id );
	}

	// Polylang: neue Beiträge der Standardsprache DE zuordnen.
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, 'de' );
	}

	feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $post_id, 'feinspitz_notice' => 'saved' ) );
}
