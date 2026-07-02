<?php
/**
 * Startseiten-Pattern: Ausgewählte Produkte.
 *
 * Registriert in inc/homepage.php als feinspitz/home-featured.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * Das Produkt-Grid nutzt den WooCommerce-[products]-Shortcode: er rendert
 * zuverlässig, sobald Produkte importiert sind, und bleibt leer/unauffällig,
 * solange der Shop noch keine Produkte hat. So bleibt die Startseite auch
 * vor der Content-Migration lauffähig.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"horizontal","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","margin":{"bottom":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained","contentSize":"640px","justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
			<p class="has-wine-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Aus dem Keller', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"style":{"typography":{"lineHeight":"1.05"}},"fontSize":"x-large","fontFamily":"heading"} -->
			<h2 class="wp-block-heading has-heading-font-family has-x-large-font-size" style="line-height:1.05"><?php esc_html_e( 'Ausgewählte Weine & Delikatessen', 'feinspitz' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline","textColor":"wine"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-wine-color has-text-color wp-element-button" href="/shop/"><?php esc_html_e( 'Alle Produkte ansehen', 'feinspitz' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:shortcode -->
	[products limit="4" columns="4" orderby="popularity" class="feinspitz-featured-products"]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
