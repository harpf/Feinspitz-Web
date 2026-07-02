<?php
/**
 * Kassen-Pattern: Leerer-Warenkorb-Zustand.
 *
 * Wird innerhalb des woocommerce/empty-cart-block in templates/cart.html
 * eingesetzt und ersetzt den WooCommerce-Standardtext durch einen bold
 * gestalteten Leer-Zustand mit Weg-zum-Shop-CTA.
 *
 * Registriert in inc/checkout.php als feinspitz/checkout-empty-cart.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"24px"}},"backgroundColor":"contrast","layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group has-contrast-background-color has-background" style="border-radius:24px;padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-text-align-center has-wine-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Noch nichts ausgewählt', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"600","lineHeight":"1.05"}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-text-align-center has-heading-font-family has-x-large-font-size" style="font-weight:600;line-height:1.05"><?php esc_html_e( 'Ihr Warenkorb ist noch leer', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->
	<p class="has-text-align-center has-medium-font-size"><?php esc_html_e( 'Entdecken Sie histamingeprüfte Weine und feine Delikatessen · handverlesen und ehrlich beraten.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)">
		<!-- wp:button {"backgroundColor":"wine","textColor":"contrast"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-contrast-color has-wine-background-color has-text-color has-background wp-element-button" href="/shop/"><?php esc_html_e( 'Zum Weinshop', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"textColor":"wine","className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-wine-color has-text-color wp-element-button" href="/weinproben/"><?php esc_html_e( 'Weinprobe buchen', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
