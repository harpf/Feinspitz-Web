<?php
/**
 * Feinspitz · Redaktion — geführte Produkt-Maske.
 *
 * Rendert ein einfaches Formular zum Anlegen/Bearbeiten eines Weins und
 * speichert es über die WooCommerce-CRUD-API. Ergänzt die WooCommerce-
 * Standardseite (die als Fallback erreichbar bleibt). Reiner Admin-Code.
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
 * Wein-Attribut-Taxonomien in Anzeige-Reihenfolge (mit deutschem Label).
 * Schlüssel = Feld-Suffix (ohne pa_), Wert = [Taxonomie, Label].
 *
 * @return array<string,array{0:string,1:string}>
 */
function feinspitz_product_attributes() {
	return array(
		'weingut'  => array( 'pa_weingut', 'Weingut' ),
		'rebsorte' => array( 'pa_rebsorte', 'Rebsorte' ),
		'jahrgang' => array( 'pa_jahrgang', 'Jahrgang' ),
		'region'   => array( 'pa_region', 'Region' ),
		'suesse'   => array( 'pa_suesse', 'Süsse' ),
		'volumen'  => array( 'pa_volumen', 'Volumen' ),
	);
}

/**
 * Flag-Slugs (product_tag) mit deutschem Label.
 *
 * @return array<string,string>
 */
function feinspitz_product_flags() {
	return array(
		'histamingeprueft' => 'Histamingeprüft',
		'vegan'            => 'Vegan',
		'alkoholfrei'      => 'Alkoholfrei',
	);
}

/**
 * Produkt-Maske rendern (leer = neu, ?id=… = bearbeiten).
 */
function feinspitz_product_form_render() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}

	echo '<div class="wrap"><h1>Neues Produkt</h1>';
	feinspitz_forms_notice();

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		echo '<p>WooCommerce ist nicht aktiv — Produkte können derzeit nicht angelegt werden.</p></div>';
		return;
	}

	$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$product = $id ? wc_get_product( $id ) : null;

	// Vorbelegung.
	$name        = $product ? $product->get_name() : '';
	$description = $product ? $product->get_description() : '';
	$price       = $product ? $product->get_regular_price() : '';
	$image_id    = $product ? (int) $product->get_image_id() : 0;
	$cats        = $product ? $product->get_category_ids() : array();
	$cat_id      = $cats ? (string) $cats[0] : '';
	$status      = $product ? $product->get_status() : 'publish';

	if ( $product ) {
		echo '<p>Sie bearbeiten: <strong>' . esc_html( $name ) . '</strong></p>';
	}

	feinspitz_forms_open( 'feinspitz_save_product', 'feinspitz_save_product', array( 'feinspitz_id' => $id ) );

	echo '<h2>Wein</h2>';
	feinspitz_forms_text( 'feinspitz_name', 'Weinname', $name, true );
	feinspitz_forms_text( 'feinspitz_price', 'Preis (CHF)', (string) $price, true, 'number' );

	echo '<h2>Beschreibung</h2>';
	feinspitz_forms_editor( 'feinspitz_description', $description );

	echo '<h2>Bild</h2>';
	feinspitz_forms_image( 'feinspitz_image', 'Produktbild', $image_id );

	echo '<h2>Einordnung</h2>';
	// Kategorie-Dropdown.
	$cat_options = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
		if ( ! is_wp_error( $term ) ) {
			$cat_options[ (string) $term->term_id ] = $term->name;
		}
	}
	feinspitz_forms_select( 'feinspitz_category', 'Kategorie', $cat_options, $cat_id );

	// Wein-Attribute: je Select aus vorhandenen Termen + Freitext „oder neu".
	foreach ( feinspitz_product_attributes() as $suffix => $meta ) {
		list( $taxonomy, $label ) = $meta;
		$options  = array();
		$selected = '';
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
			if ( ! is_wp_error( $term ) ) {
				$options[ (string) $term->term_id ] = $term->name;
			}
		}
		if ( $product ) {
			$current = get_the_terms( $product->get_id(), $taxonomy );
			if ( $current && ! is_wp_error( $current ) ) {
				$selected = (string) $current[0]->term_id;
			}
		}
		echo '<div class="feinspitz-attr-row">';
		feinspitz_forms_select( 'feinspitz_attr_' . $suffix, $label, $options, $selected );
		feinspitz_forms_text( 'feinspitz_attr_' . $suffix . '_new', 'oder neu:', '' );
		echo '</div>';
	}

	echo '<h2>Merkmale</h2>';
	foreach ( feinspitz_product_flags() as $slug => $label ) {
		$checked = $product ? has_term( $slug, 'product_tag', $product->get_id() ) : false;
		feinspitz_forms_checkbox( 'feinspitz_flag_' . $slug, $label, $checked );
	}

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
 * Attribut-Objekte aus dem POST bauen (Select-Term-ID oder neuer Freitext-Term).
 *
 * @param array $post $_POST (unslashed durch Aufrufer).
 * @return WC_Product_Attribute[]
 */
function feinspitz_product_build_attributes( array $post ) {
	$attributes = array();
	$position   = 0;

	foreach ( feinspitz_product_attributes() as $suffix => $meta ) {
		$taxonomy = $meta[0];
		$field    = 'feinspitz_attr_' . $suffix;

		$term_id = isset( $post[ $field ] ) ? absint( $post[ $field ] ) : 0;
		$new_val = isset( $post[ $field . '_new' ] ) ? sanitize_text_field( wp_unslash( $post[ $field . '_new' ] ) ) : '';

		// Freitext hat Vorrang: bestehenden Term nehmen oder neu anlegen.
		if ( '' !== $new_val ) {
			$existing = term_exists( $new_val, $taxonomy );
			if ( $existing && ! is_wp_error( $existing ) ) {
				$term_id = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
			} else {
				$created = wp_insert_term( $new_val, $taxonomy );
				if ( ! is_wp_error( $created ) ) {
					$term_id = (int) $created['term_id'];
				}
			}
		}

		if ( ! $term_id ) {
			continue;
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term_id ) );
		$attribute->set_position( $position++ );
		$attribute->set_visible( false );
		$attribute->set_variation( false );
		$attributes[] = $attribute;
	}

	return $attributes;
}

/**
 * Flags (product_tag) setzen — immer über Term-ID (Slug-String würde einen neuen
 * Term anlegen). Fehlende Häkchen entfernen das Tag (append=false).
 *
 * @param int   $product_id Produkt-ID.
 * @param array $post       $_POST.
 */
function feinspitz_product_apply_flags( $product_id, array $post ) {
	$assign = array();
	foreach ( array_keys( feinspitz_product_flags() ) as $slug ) {
		if ( ! empty( $post[ 'feinspitz_flag_' . $slug ] ) ) {
			$term = get_term_by( 'slug', $slug, 'product_tag' );
			if ( $term ) {
				$assign[] = (int) $term->term_id;
			}
		}
	}
	wp_set_object_terms( $product_id, $assign, 'product_tag', false );
}

/**
 * Produkt speichern (admin-post.php).
 */
add_action( 'admin_post_feinspitz_save_product', 'feinspitz_product_form_save' );
function feinspitz_product_form_save() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	check_admin_referer( 'feinspitz_save_product' );

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'feinspitz_notice' => 'nowc' ) );
	}

	$id      = isset( $_POST['feinspitz_id'] ) ? absint( $_POST['feinspitz_id'] ) : 0;
	$product = $id ? wc_get_product( $id ) : null;
	if ( ! $product ) {
		$product = new WC_Product_Simple();
	}

	$name = isset( $_POST['feinspitz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['feinspitz_name'] ) ) : '';
	if ( '' === $name ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	$product->set_name( $name );
	$product->set_description( isset( $_POST['feinspitz_description'] ) ? wp_kses_post( wp_unslash( $_POST['feinspitz_description'] ) ) : '' );
	$product->set_regular_price( isset( $_POST['feinspitz_price'] ) ? wc_format_decimal( wp_unslash( $_POST['feinspitz_price'] ) ) : '' );

	$image_id = isset( $_POST['feinspitz_image'] ) ? absint( $_POST['feinspitz_image'] ) : 0;
	$product->set_image_id( $image_id ? $image_id : '' );

	$cat_id = isset( $_POST['feinspitz_category'] ) ? absint( $_POST['feinspitz_category'] ) : 0;
	$product->set_category_ids( $cat_id ? array( $cat_id ) : array() );

	$status = feinspitz_forms_choice( isset( $_POST['feinspitz_status'] ) ? wp_unslash( $_POST['feinspitz_status'] ) : '', array( 'publish', 'draft' ), 'draft' );
	$product->set_status( $status );

	$product->set_attributes( feinspitz_product_build_attributes( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing — Nonce oben geprüft.

	$product_id = $product->save();
	if ( ! $product_id ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	feinspitz_product_apply_flags( $product_id, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $product_id, 'feinspitz_notice' => 'saved' ) );
}
