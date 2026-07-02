<?php
/**
 * Shop-Pattern: Kopfbereich einer Produkt-Kategorie (taxonomy-product_cat).
 *
 * Registriert in inc/shop.php als feinspitz/shop-category-header.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * Der Titel wird dynamisch über core/query-title (Archiv-Titel ohne Präfix)
 * gerendert, die Beschreibung über core/term-description · beide lösen auf den
 * aktuell aufgerufenen Kategorie-Term auf (/produkt-kategorie/<slug>/).
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Kategorie', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:query-title {"type":"archive","showPrefix":false,"level":1,"fontSize":"xx-large","fontFamily":"heading"} /-->

	<!-- wp:term-description {"style":{"typography":{"lineHeight":"1.6"}},"fontSize":"large"} /-->
</div>
<!-- /wp:group -->
