<?php
/**
 * Shop-Pattern: Flag-Filter (histamingeprüft / vegan / alkoholfrei).
 *
 * Registriert in inc/shop.php als feinspitz/shop-filter-flags.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * Die Flags sind als Produkt-Tags mit den Slugs histamingeprueft / vegan /
 * alkoholfrei angelegt. Gefiltert wird über den registrierten Query-Var
 * `product_tag` auf der Shop-Seite (/shop/?product_tag=<slug>) — das funktioniert
 * unabhängig vom Tag-Permalink-Base und respektiert die Haupt-Query, sodass der
 * WooCommerce-"Classic Template"-Grid automatisch nur die getaggten Produkte
 * zeigt. "Alle Produkte" setzt den Filter zurück (/shop/).
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"fontSize":"small"} -->
	<p class="has-wine-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Filtern nach', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"wine","textColor":"contrast"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-contrast-color has-wine-background-color has-text-color has-background wp-element-button" href="/shop/"><?php esc_html_e( 'Alle Produkte', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline","textColor":"wine"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-wine-color has-text-color wp-element-button" href="/shop/?product_tag=histamingeprueft"><?php esc_html_e( 'Histamingeprüft', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline","textColor":"wine"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-wine-color has-text-color wp-element-button" href="/shop/?product_tag=vegan"><?php esc_html_e( 'Vegan', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline","textColor":"wine"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-wine-color has-text-color wp-element-button" href="/shop/?product_tag=alkoholfrei"><?php esc_html_e( 'Alkoholfrei', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
