<?php
/**
 * Title: Kontakt
 * Slug: feinspitz/page-contact
 * Categories: feinspitz
 * Post Types: page
 * Description: Kontaktseite mit Shop- und Administrations-Adresse, Telefon und Öffnung nach Vereinbarung.
 * Keywords: kontakt, contact, adresse, standort, urdorf
 * Inserter: true
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained","contentSize":"960px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->
	<p class="has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600"><?php esc_html_e( 'Kontakt', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.02"}},"fontSize":"xx-large"} -->
	<h1 class="wp-block-heading has-xx-large-font-size" style="font-style:normal;font-weight:600;line-height:1.02"><?php esc_html_e( 'Sprechen Sie mit uns', 'feinspitz' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"fontSize":"large"} -->
	<p class="has-large-font-size" style="margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Fragen zu Weinen, Bestellungen oder einer Verkostung? Wir sind persönlich für Sie da.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1080px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"backgroundColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"left":{"color":"var:preset|color|wine","width":"4px"}}}} -->
		<div class="wp-block-column has-contrast-background-color has-background" style="border-left-color:var(--wp--preset--color--wine);border-left-width:4px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.2em","fontWeight":"600"}},"textColor":"wine","fontSize":"small"} -->
			<p class="has-wine-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.2em;font-weight:600"><?php esc_html_e( 'Shop', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Feinspitz Weine & Genuss', 'feinspitz' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.8"}}} -->
			<p style="line-height:1.8"><?php echo esc_html__( 'Bahnhofstrasse 80', 'feinspitz' ) . '<br>' . esc_html__( 'CH-8902 Urdorf', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<p style="margin-top:var(--wp--preset--spacing--40)"><strong><?php esc_html_e( 'Telefon', 'feinspitz' ); ?></strong><br><a href="tel:+41765888902">+41 76 588 89 02</a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<p style="margin-top:var(--wp--preset--spacing--40)"><strong><?php esc_html_e( 'Öffnungszeiten', 'feinspitz' ); ?></strong><br><?php esc_html_e( 'Öffnung nach Vereinbarung', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="tel:+41765888902"><?php esc_html_e( 'Anrufen', 'feinspitz' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"backgroundColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"left":{"color":"var:preset|color|gold","width":"4px"}}}} -->
		<div class="wp-block-column has-contrast-background-color has-background" style="border-left-color:var(--wp--preset--color--gold);border-left-width:4px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.2em","fontWeight":"600"}},"textColor":"wine","fontSize":"small"} -->
			<p class="has-wine-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.2em;font-weight:600"><?php esc_html_e( 'Administration', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Büro & Verwaltung', 'feinspitz' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.8"}}} -->
			<p style="line-height:1.8"><?php echo esc_html__( 'Baumgartenstrasse 16', 'feinspitz' ) . '<br>' . esc_html__( 'CH-8902 Urdorf', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<p style="margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Für administrative Anliegen, Rechnungen und Partnerschaften. Ein Besuch ist nach Vereinbarung möglich.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}},"typography":{"textTransform":"uppercase","letterSpacing":"0.15em"}},"textColor":"sage","fontSize":"small"} -->
	<p class="has-text-align-center has-sage-color has-text-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--60);text-transform:uppercase;letter-spacing:0.15em"><?php esc_html_e( 'Besuch & Abholung jederzeit nach Vereinbarung', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
