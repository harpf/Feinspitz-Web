<?php
/**
 * Startseiten-Pattern: Weinproben & Catering (Call-to-Action).
 *
 * Registriert in inc/homepage.php als feinspitz/home-cta.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * Zwei Bild-Karten (Cover mit Original-Motiven aus dem Asset-Pool) mit dunklem
 * Overlay und klarer CTA je Karte. Stapelt auf Mobil automatisch.
 *
 * @package Feinspitz
 */

$tasting_img  = esc_url( get_template_directory_uri() . '/assets/images/weinprobe-messe.jpg' );
$catering_img = esc_url( get_template_directory_uri() . '/assets/images/catering.jpg' );
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.3em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.3em;text-transform:uppercase"><?php esc_html_e( 'Gemeinsam geniessen', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","style":{"typography":{"lineHeight":"1.05"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|60"}}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-text-align-center has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--60);line-height:1.05"><?php esc_html_e( 'Weinproben & Catering', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:cover {"url":"<?php echo $tasting_img; ?>","dimRatio":55,"overlayColor":"base","isDark":true,"minHeight":400,"contentPosition":"bottom left","style":{"border":{"radius":"18px"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60","right":"var:preset|spacing|60"}}}} -->
			<div class="wp-block-cover has-custom-content-position is-position-bottom-left is-dark" style="border-radius:18px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-base-background-color has-background-dim-55 has-background-dim"></span><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Geführte Weinprobe an einem Messestand', 'feinspitz' ); ?>" src="<?php echo $tasting_img; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
				<!-- wp:heading {"level":3,"style":{"typography":{"lineHeight":"1.1"}},"fontSize":"large","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-large-font-size" style="line-height:1.1"><?php esc_html_e( 'Geführte Weinproben', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
				<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Entdecken Sie unsere Weine bei einer geführten Probe - auch histaminarm auf Wunsch.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
					<!-- wp:button {"backgroundColor":"gold","textColor":"base"} -->
					<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-gold-background-color has-text-color has-background wp-element-button" href="/weinproben/"><?php esc_html_e( 'Weinprobe buchen', 'feinspitz' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div></div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:cover {"url":"<?php echo $catering_img; ?>","dimRatio":55,"overlayColor":"base","isDark":true,"minHeight":400,"contentPosition":"bottom left","style":{"border":{"radius":"18px"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60","right":"var:preset|spacing|60"}}}} -->
			<div class="wp-block-cover has-custom-content-position is-position-bottom-left is-dark" style="border-radius:18px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-base-background-color has-background-dim-55 has-background-dim"></span><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Catering mit Apéro und Canapés', 'feinspitz' ); ?>" src="<?php echo $catering_img; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
				<!-- wp:heading {"level":3,"style":{"typography":{"lineHeight":"1.1"}},"fontSize":"large","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-large-font-size" style="line-height:1.1"><?php esc_html_e( 'Catering & Apéro', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
				<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Stilvolles Catering für Ihren Anlass - wir stellen ein Erlebnis zusammen, das schmeckt.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
					<!-- wp:button {"backgroundColor":"gold","textColor":"base"} -->
					<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-gold-background-color has-text-color has-background wp-element-button" href="/catering/"><?php esc_html_e( 'Catering anfragen', 'feinspitz' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div></div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
