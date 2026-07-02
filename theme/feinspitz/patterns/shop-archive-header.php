<?php
/**
 * Shop-Pattern: Kopfbereich der Shop-Übersicht (archive-product).
 *
 * Registriert in inc/shop.php als feinspitz/shop-archive-header.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","gradient":"wine-fade","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-wine-fade-gradient-background has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Shop', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"style":{"typography":{"lineHeight":"1.02"}},"fontSize":"xx-large","fontFamily":"heading"} -->
	<h1 class="wp-block-heading has-heading-font-family has-xx-large-font-size" style="line-height:1.02"><?php esc_html_e( 'Unser gesamtes Sortiment', 'feinspitz' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.6"},"layout":{"selfStretch":"fit"}},"fontSize":"large"} -->
	<p class="has-large-font-size" style="line-height:1.6;max-width:640px"><?php esc_html_e( 'Histamingeprüfte Weine, Delikatessen und ausgesuchte Spezialitäten - sorgfältig kuratiert für empfindliche Geniesserinnen und Geniesser.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
