<?php
/**
 * Shop-Pattern: Produkt-Grid inkl. Sortierung & Pagination.
 *
 * Registriert in inc/shop.php als feinspitz/shop-grid.
 * Reines Block-Markup (kein Pattern-Header) — keine übersetzbaren Strings, da
 * die Ausgabe (Ergebnis-Zähler, Sortier-Dropdown, Produkt-Loop, Pagination)
 * vollständig von WooCommerce kommt.
 *
 * Für maximale Zuverlässigkeit rendert der WooCommerce-"Classic Template"-Block
 * (woocommerce/legacy-template) die Produkt-Archiv-Ausgabe der HAUPT-Query. Damit
 * funktioniert dieses eine Pattern sowohl auf der Shop-Übersicht (archive-product)
 * als auch auf Kategorie-Archiven (taxonomy-product_cat) korrekt: der Block folgt
 * jeweils der aktuellen Abfrage (Kategorie, ?product_tag-Filter, Sortierung,
 * Seitenzahl). Der Standard-Titel/-Beschreibung wird in inc/shop.php unterdrückt,
 * sodass unsere BOLD gestalteten Kopfbereiche den Titel besitzen.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","className":"feinspitz-shop-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull feinspitz-shop-grid" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:woocommerce/legacy-template {"template":"archive-product"} /-->
</div>
<!-- /wp:group -->
