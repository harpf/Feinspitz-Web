<?php
/**
 * Produkt-Pattern: Verwandte Produkte.
 *
 * Registriert in inc/product.php als feinspitz/product-related.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * woocommerce/related-products rendert serverseitig verwandte Produkte des
 * aktuellen Produkts und bleibt leer, falls keine ermittelbar sind.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">

	<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"fontSize":"small"} -->
	<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:600;letter-spacing:0.28em;text-transform:uppercase"><?php esc_html_e( 'Passt dazu', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","textColor":"contrast","style":{"typography":{"lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-text-align-center has-contrast-color has-text-color has-heading-font-family has-x-large-font-size" style="margin-bottom:var(--wp--preset--spacing--60);line-height:1.1"><?php esc_html_e( 'Das könnte Ihnen auch schmecken', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/related-products {"columns":4} /-->

</div>
<!-- /wp:group -->
