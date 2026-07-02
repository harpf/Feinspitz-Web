<?php
/**
 * Startseiten-Pattern: Hero.
 *
 * Registriert in inc/homepage.php als feinspitz/home-hero.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * Der Cover-Block nutzt ein echtes Original-Motiv aus dem Asset-Pool
 * (assets/images/shop-interior.jpg, referenziert via get_template_directory_uri)
 * mit dunklem Overlay (overlayColor base, dimRatio) für gute Lesbarkeit der
 * hellen Schrift.
 *
 * @package Feinspitz
 */

$hero_img = esc_url( get_template_directory_uri() . '/assets/images/shop-interior.jpg' );
?>
<!-- wp:cover {"url":"<?php echo $hero_img; ?>","dimRatio":60,"overlayColor":"base","isDark":true,"minHeight":86,"minHeightUnit":"vh","contentPosition":"center center","align":"full","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-cover alignfull has-contrast-color has-text-color is-dark has-custom-content-position is-position-center-center" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50);min-height:86vh"><span aria-hidden="true" class="wp-block-cover__background has-base-background-color has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $hero_img; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"860px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"fontSize":"small"} -->
		<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--30);font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Weine & Genuss aus Leidenschaft', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"600","lineHeight":"1.02"}},"fontSize":"xx-large","fontFamily":"heading"} -->
		<h1 class="wp-block-heading has-text-align-center has-heading-font-family has-xx-large-font-size" style="font-weight:600;line-height:1.02"><?php esc_html_e( 'Weine, die man wirklich geniessen kann', 'feinspitz' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"fontSize":"medium"} -->
		<p class="has-text-align-center has-medium-font-size" style="margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Als erster Anbieter histamingeprüfter Weine der Schweiz stehen wir seit über 20 Jahren für ehrlichen, verträglichen Genuss - handverlesen und persönlich beraten.', 'feinspitz' ); ?></p>
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

		<!-- wp:paragraph {"align":"center","textColor":"contrast","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.18em","fontWeight":"600"},"spacing":{"margin":{"top":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"fontSize":"small"} -->
		<p class="has-text-align-center has-contrast-color has-text-color has-link-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--50);font-weight:600;letter-spacing:0.18em;text-transform:uppercase"><?php esc_html_e( 'Austria Bio Garantie · Histamin 100 % geprüft · Vegan', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
