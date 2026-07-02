<?php
/**
 * Kassen-Pattern: Bold-Kopfzeile für die Warenkorb-Seite.
 *
 * Registriert in inc/checkout.php als feinspitz/checkout-cart-header.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","gradient":"wine-fade","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-wine-fade-gradient-background has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Schritt 1 von 2', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"600","lineHeight":"1"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h1 class="wp-block-heading has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);font-weight:600;line-height:1"><?php esc_html_e( 'Ihr Warenkorb', 'feinspitz' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"medium"} -->
	<p class="has-medium-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Prüfen Sie Ihre Auswahl, passen Sie Mengen an und lösen Sie einen Gutschein ein — bevor es zur Kasse geht.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
