<?php
/**
 * Startseiten-Pattern: Über uns / Story-Teaser.
 *
 * Registriert in inc/homepage.php als feinspitz/home-story.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * Persönliche Story des Familienweinguts Steyrer (Traisental) mit echtem
 * Original-Motiv (assets/images/natur-winzer.jpg - Weinberg + Winzer + Siegel),
 * persönlichem Zitat + Signatur und dem Marken-Slogan. Bild neben Text,
 * stapelt auf Mobil automatisch.
 *
 * @package Feinspitz
 */

$story_img = esc_url( get_template_directory_uri() . '/assets/images/natur-winzer.jpg' );
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:image {"aspectRatio":"4/5","scale":"cover","sizeSlug":"large","style":{"border":{"radius":"24px"}}} -->
			<figure class="wp-block-image size-large has-custom-border"><img src="<?php echo $story_img; ?>" alt="<?php esc_attr_e( 'Winzer im Weinberg des Familienweinguts Steyrer im Traisental, mit Bio-, Histamin- und Vegan-Siegel', 'feinspitz' ); ?>" style="border-radius:24px;aspect-ratio:4/5;object-fit:cover"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
			<p class="has-wine-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Unsere Geschichte', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"style":{"typography":{"lineHeight":"1.1"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"fontSize":"x-large","fontFamily":"heading"} -->
			<h2 class="wp-block-heading has-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);line-height:1.1"><?php esc_html_e( 'Vom Traisental in Ihr Glas', 'feinspitz' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Unsere Weine stammen vom Familienweingut Steyrer im niederösterreichischen Traisental. Auf sonnenverwöhnten Terrassen wächst dort, was uns am Herzen liegt: ehrlicher Wein, im Einklang mit der Natur gewachsen und mit tiefem Respekt vor ihr.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
			<p style="margin-top:var(--wp--preset--spacing--30)"><?php esc_html_e( 'Familie Steyrer bewirtschaftet ihre Reben nachhaltig - zertifiziert nach Austria Bio Garantie, zu 100 % histamingeprüft und vegan. Als erster Anbieter histamingeprüfter Weine der Schweiz bringen wir dieses Handwerk seit über 20 Jahren zu Ihnen.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"},"padding":{"left":"var:preset|spacing|50"}},"border":{"left":{"color":"var:preset|color|gold","width":"3px"}}},"layout":{"type":"default"}} -->
			<div class="wp-block-group" style="border-left-color:var(--wp--preset--color--gold);border-left-width:3px;margin-top:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)">
				<!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic","fontWeight":"400","lineHeight":"1.4"}},"fontSize":"large","fontFamily":"heading"} -->
				<p class="has-heading-font-family has-large-font-size" style="font-style:italic;font-weight:400;line-height:1.4"><?php esc_html_e( '„Wir machen Wein so, wie wir ihn selbst trinken möchten: rein, verträglich und mit Freude am Genuss.“', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"textColor":"wine","style":{"typography":{"fontWeight":"600"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
				<p class="has-wine-color has-text-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--20);font-weight:600"><?php esc_html_e( '- Familie Steyrer & das Feinspitz-Team', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","lineHeight":"1.35"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"textColor":"wine","fontSize":"medium"} -->
			<p class="has-wine-color has-text-color has-medium-font-size" style="margin-top:var(--wp--preset--spacing--40);font-weight:600;line-height:1.35"><?php esc_html_e( 'Histamingeprüfte Weine mit mehr Vertrauen und Genuss erleben - für mehr Lebensqualität!', 'feinspitz' ); ?></p>
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
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
