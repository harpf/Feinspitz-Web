<?php
/**
 * Startseiten-Pattern: Über uns / Story-Teaser.
 *
 * Registriert in inc/homepage.php als feinspitz/home-story.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
			<p class="has-wine-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Über Feinspitz', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"style":{"typography":{"lineHeight":"1.1"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"fontSize":"x-large","fontFamily":"heading"} -->
			<h2 class="wp-block-heading has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);line-height:1.1"><?php esc_html_e( '20 Jahre Leidenschaft für ehrlichen Genuss', 'feinspitz' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Feinspitz steht seit über zwei Jahrzehnten für sorgfältig ausgewählte Weine und feine Delikatessen. Als erster Anbieter histamingeprüfter Weine der Schweiz machen wir Genuss auch für empfindliche Weinliebhaberinnen und -liebhaber möglich — transparent, ehrlich und mit persönlicher Beratung.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/ueber-uns/"><?php esc_html_e( 'Mehr über uns', 'feinspitz' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:group {"gradient":"wine-fade","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|60","right":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"},"border":{"radius":"24px"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group has-contrast-color has-text-color has-wine-fade-gradient-background has-background" style="border-radius:24px;padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--60)">
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"600","lineHeight":"1"}},"fontSize":"xx-large","fontFamily":"heading"} -->
					<p class="has-gold-color has-text-color has-heading-font-family has-xx-large-font-size" style="font-weight:600;line-height:1"><?php esc_html_e( '20+', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size"><?php esc_html_e( 'Jahre Erfahrung im Weinhandel', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"600","lineHeight":"1"}},"fontSize":"xx-large","fontFamily":"heading"} -->
					<p class="has-gold-color has-text-color has-heading-font-family has-xx-large-font-size" style="font-weight:600;line-height:1"><?php esc_html_e( 'Nr. 1', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size"><?php esc_html_e( 'für histamingeprüfte Weine in der Schweiz', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"600","lineHeight":"1"}},"fontSize":"xx-large","fontFamily":"heading"} -->
					<p class="has-gold-color has-text-color has-heading-font-family has-xx-large-font-size" style="font-weight:600;line-height:1"><?php esc_html_e( '100%', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size"><?php esc_html_e( 'handverlesen & ehrlich beraten', 'feinspitz' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
