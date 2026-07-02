<?php
/**
 * Startseiten-Pattern: Weinproben & Catering (Call-to-Action).
 *
 * Registriert in inc/homepage.php als feinspitz/home-cta.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Gemeinsam geniessen', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","style":{"typography":{"lineHeight":"1.05"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-text-align-center has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);line-height:1.05"><?php esc_html_e( 'Weinproben & Catering', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"fontSize":"medium"} -->
	<p class="has-text-align-center has-medium-font-size" style="margin-top:var(--wp--preset--spacing--30)"><?php esc_html_e( 'Ob geführte Weinprobe oder stilvolles Catering für Ihren Anlass — wir stellen ein Erlebnis zusammen, das schmeckt. Auch histaminarm auf Wunsch.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:button {"backgroundColor":"gold","textColor":"base"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-gold-background-color has-text-color has-background wp-element-button" href="/weinproben/"><?php esc_html_e( 'Weinprobe buchen', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"textColor":"contrast","className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-contrast-color has-text-color wp-element-button" href="/catering/"><?php esc_html_e( 'Catering anfragen', 'feinspitz' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
