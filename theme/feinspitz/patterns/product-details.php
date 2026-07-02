<?php
/**
 * Produkt-Pattern: Beschreibung & Details.
 *
 * Registriert in inc/product.php als feinspitz/product-details.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * woocommerce/product-details rendert die Standard-Tabs (Beschreibung,
 * Zusätzliche Informationen, Bewertungen) zuverlässig aus den Produktdaten und
 * bleibt unauffällig, falls einzelne Tabs leer sind.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"860px"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">

	<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-wine-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Gut zu wissen', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"style":{"typography":{"lineHeight":"1.1"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|50"}}},"fontSize":"large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-heading-font-family has-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--50);line-height:1.1"><?php esc_html_e( 'Über dieses Produkt', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-details /-->

</div>
<!-- /wp:group -->
