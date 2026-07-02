<?php
/**
 * Pattern-Markup: Ratgeber-Übersicht — Intro/Hero.
 *
 * Registriert in inc/ratgeber.php als feinspitz/ratgeber-intro und von
 * templates/category-ratgeber.html referenziert. Reines Block-Markup (kein
 * Datei-Header) — Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained","contentSize":"820px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->
	<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600"><?php esc_html_e( 'Ratgeber', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"lineHeight":"1.05"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"fontSize":"x-large"} -->
	<h1 class="wp-block-heading has-text-align-center has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);line-height:1.05"><?php esc_html_e( 'Wein verstehen, bewusst geniessen', 'feinspitz' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->
	<p class="has-text-align-center has-medium-font-size"><?php esc_html_e( 'Fundiertes Wissen rund um histaminarmen Weingenuss, vegane Weine und die kleinen Dinge, die den grossen Unterschied machen — verständlich erklärt vom Team hinter Feinspitz.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
