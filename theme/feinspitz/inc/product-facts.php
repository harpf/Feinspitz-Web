<?php
/**
 * Feinspitz · Produkt-Fakten (feature/product-descriptions).
 *
 * Shortcode [feinspitz_product_facts] rendert die strukturierten WooCommerce-
 * Attribute des aktuellen Produkts (Weingut, Rebsorte, Region, Jahrgang, Süsse,
 * Volumen) als aufgeräumte Tabelle im Bold-Design.
 *
 * Die Attribute werden vom Content-Skript scripts/content/product-enrich.mjs als
 * globale Attribute (pa_*) gesetzt und dort bewusst auf visible=false gestellt —
 * diese Tabelle ist die einzige, gestylte Darstellung (kein Doppel mit dem
 * WooCommerce-Tab „Zusätzliche Informationen").
 *
 * Wird von functions.php automatisch geladen (glob inc/*.php) und in
 * templates/single-product.html per wp:shortcode eingebunden.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anzeige-Reihenfolge der Attribute (Slug ohne pa_-Präfix).
 * Nicht gelistete Attribute werden in ihrer natürlichen Reihenfolge angehängt.
 *
 * @return string[]
 */
function feinspitz_facts_order() {
	return array( 'weingut', 'rebsorte', 'jahrgang', 'region', 'suesse', 'volumen' );
}

/**
 * Sprachbewusste Labels je Attribut-Slug (ohne pa_-Präfix).
 * Fällt für unbekannte Attribute auf wc_attribute_label() zurück.
 *
 * @param string $key Attribut-Slug ohne pa_-Präfix (lowercase).
 * @return string|null Übersetztes Label oder null.
 */
function feinspitz_facts_label( $key ) {
	$is_en = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();

	$labels = array(
		'weingut'  => array( 'de' => 'Weingut', 'en' => 'Winery' ),
		'rebsorte' => array( 'de' => 'Rebsorte', 'en' => 'Grape variety' ),
		'region'   => array( 'de' => 'Region', 'en' => 'Region' ),
		'jahrgang' => array( 'de' => 'Jahrgang', 'en' => 'Vintage' ),
		'suesse'   => array( 'de' => 'Süsse', 'en' => 'Sweetness' ),
		'volumen'  => array( 'de' => 'Volumen', 'en' => 'Volume' ),
	);

	if ( isset( $labels[ $key ] ) ) {
		return $is_en ? $labels[ $key ]['en'] : $labels[ $key ]['de'];
	}
	return null;
}

/**
 * Attribut-Slug normalisieren: pa_-Präfix entfernen, lowercase.
 *
 * @param string $name Attributname (z. B. „pa_weingut").
 * @return string
 */
function feinspitz_facts_normalize_key( $name ) {
	$name = strtolower( (string) $name );
	if ( 0 === strpos( $name, 'pa_' ) ) {
		$name = substr( $name, 3 );
	}
	return $name;
}

/**
 * Fakten-Zeilen (Label + Wert) für ein Produkt sammeln.
 *
 * @param WC_Product $product Produkt.
 * @return array<int,array{key:string,label:string,value:string}>
 */
function feinspitz_collect_facts( $product ) {
	$rows = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		if ( $attribute->get_variation() ) {
			continue; // Variations-Attribute gehören nicht in die Fakten-Tabelle.
		}

		$key = feinspitz_facts_normalize_key( $attribute->get_name() );

		// Werte holen (Taxonomie- ODER Freitext-Attribut).
		if ( $attribute->is_taxonomy() ) {
			$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
		} else {
			$values = $attribute->get_options();
		}
		$values = array_filter( array_map( 'trim', (array) $values ), 'strlen' );
		if ( empty( $values ) ) {
			continue;
		}

		$label = feinspitz_facts_label( $key );
		if ( null === $label ) {
			$label = wc_attribute_label( $attribute->get_name() );
		}

		$rows[ $key ] = array(
			'key'   => $key,
			'label' => $label,
			'value' => implode( ', ', $values ),
		);
	}

	// Nach definierter Reihenfolge sortieren; Unbekanntes hinten anhängen.
	$order  = array_flip( feinspitz_facts_order() );
	$sorted = array();
	foreach ( feinspitz_facts_order() as $key ) {
		if ( isset( $rows[ $key ] ) ) {
			$sorted[] = $rows[ $key ];
			unset( $rows[ $key ] );
		}
	}
	foreach ( $rows as $row ) {
		$sorted[] = $row;
	}

	return $sorted;
}

/**
 * Shortcode [feinspitz_product_facts] — Fakten-Tabelle des aktuellen Produkts.
 * Gibt einen leeren String zurück, wenn kein Produkt/keine Attribute vorliegen.
 */
add_shortcode( 'feinspitz_product_facts', function () {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$rows = feinspitz_collect_facts( $product );
	if ( empty( $rows ) ) {
		return '';
	}

	$is_en   = function_exists( 'feinspitz_current_lang' ) && 'en' === feinspitz_current_lang();
	$heading = $is_en ? 'At a glance' : 'Auf einen Blick';

	$html  = '<section class="feinspitz-facts">';
	$html .= '<h2 class="feinspitz-facts__title">' . esc_html( $heading ) . '</h2>';
	$html .= '<table class="feinspitz-facts__table"><tbody>';
	foreach ( $rows as $row ) {
		$html .= sprintf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $row['label'] ),
			esc_html( $row['value'] )
		);
	}
	$html .= '</tbody></table></section>';

	return $html;
} );

/**
 * Styling der Fakten-Tabelle (Theme-Tokens: wine/gold/cream/base/contrast).
 * An das Theme-Style-Handle angehängt, damit theme.json/style.css unangetastet
 * bleiben.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
	.feinspitz-facts{margin:2.5rem auto;max-width:640px}
	.feinspitz-facts__title{font-family:var(--wp--preset--font-family--heading);font-size:1.5rem;line-height:1.1;margin:0 0 1rem}
	.feinspitz-facts__table{width:100%;border-collapse:collapse;font-family:var(--wp--preset--font-family--body);background:var(--wp--preset--color--base);border:1px solid color-mix(in srgb,var(--wp--preset--color--contrast) 12%,transparent);border-radius:12px;overflow:hidden}
	.feinspitz-facts__table th,.feinspitz-facts__table td{padding:.85rem 1.1rem;text-align:left;vertical-align:top;font-size:.95rem}
	.feinspitz-facts__table tr+tr th,.feinspitz-facts__table tr+tr td{border-top:1px solid color-mix(in srgb,var(--wp--preset--color--contrast) 8%,transparent)}
	.feinspitz-facts__table th{width:38%;font-weight:600;letter-spacing:.02em;color:var(--wp--preset--color--wine);white-space:nowrap}
	.feinspitz-facts__table td{color:var(--wp--preset--color--contrast)}
	@media (max-width:480px){.feinspitz-facts__table th{width:44%}}';

	wp_add_inline_style( 'feinspitz-style', $css );
}, 20 );
