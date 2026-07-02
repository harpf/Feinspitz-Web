<?php
/**
 * Shop-Pattern: Filterleiste (Weintyp · Geschmack · Eigenschaften).
 *
 * Registriert in inc/shop.php als feinspitz/shop-filter-flags und von
 * templates/archive-product.html sowie templates/taxonomy-product_cat.html
 * referenziert.
 *
 * Reines Block-Markup: EIN Shortcode-Block, der die Filterleiste zur RENDER-Zeit
 * erzeugt (inc/shop.php → feinspitz_shop_filters_shortcode). Das ist bewusst so,
 * weil die Filter dadurch (a) den aktiven Zustand serverseitig aus der aktuellen
 * Query ableiten (funktioniert ohne JavaScript) und (b) die DE/EN-Labels inline
 * zur Render-Zeit wählen können, ohne neue gettext-msgids einzuführen.
 *
 * Gefiltert wird über WooCommerce-Query-Vars:
 *   - Weintyp        → product_cat   (weissweine/rotweine/rose/schaumweine/suessweine)
 *   - Eigenschaften  → product_tag   (histamingeprueft/vegan/alkoholfrei)
 *   - Geschmack      → filter_suesse (Layered-Nav des Attributs pa_suesse)
 * Alle drei kombinieren sich; „Alle zurücksetzen" leert die Filter (/shop/).
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--30)">
	<!-- wp:shortcode -->[feinspitz_shop_filters]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
