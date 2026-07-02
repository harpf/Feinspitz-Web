<?php
/**
 * Startseiten-Pattern: Hero.
 *
 * Registriert in inc/homepage.php als feinspitz/home-hero.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:cover {"gradient":"wine-fade","dimRatio":100,"minHeight":86,"minHeightUnit":"vh","contentPosition":"center center","isDark":true,"align":"full","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-cover alignfull has-contrast-color has-text-color is-dark has-custom-content-position is-position-center-center" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50);min-height:86vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-wine-fade-gradient-background"></span><div class="wp-block-cover__inner-container">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"860px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"fontSize":"small"} -->
		<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--30);font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Weine & Genuss aus Leidenschaft', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"600","lineHeight":"0.95"}},"fontSize":"xx-large","fontFamily":"heading"} -->
		<h1 class="wp-block-heading has-text-align-center has-heading-font-family has-xx-large-font-size" style="font-weight:600;line-height:0.95"><?php esc_html_e( 'Feinspitz', 'feinspitz' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"italic","fontWeight":"400"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"large","fontFamily":"heading"} -->
		<p class="has-text-align-center has-heading-font-family has-large-font-size" style="margin-top:var(--wp--preset--spacing--20);font-style:italic;font-weight:400"><?php esc_html_e( 'Weine & Genuss', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"fontSize":"medium"} -->
		<p class="has-text-align-center has-medium-font-size" style="margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Der erste Anbieter histamingeprüfter Weine der Schweiz — seit über 20 Jahren Ihr Spezialist für ehrlichen, verträglichen Genuss.', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}},"typography":{"fontSize":"1.05rem"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50);font-size:1.05rem">
			<!-- wp:button {"backgroundColor":"gold","textColor":"base"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-gold-background-color has-text-color has-background wp-element-button" href="/shop/"><?php esc_html_e( 'Zum Weinshop', 'feinspitz' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"textColor":"contrast","className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-contrast-color has-text-color wp-element-button" href="/ueber-uns/"><?php esc_html_e( 'Unsere Geschichte', 'feinspitz' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
