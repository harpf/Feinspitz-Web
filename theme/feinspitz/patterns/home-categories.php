<?php
/**
 * Startseiten-Pattern: Kategorien-Teaser.
 *
 * Registriert in inc/homepage.php als feinspitz/home-categories.
 * Reines Block-Markup (kein Pattern-Header) — Strings via Textdomain feinspitz.
 *
 * Farb-Kacheln verlinken auf Produkt-Kategorien/Filter. Die Slugs
 * (/produkt-kategorie/<slug>/) sind Annahmen — sobald die Content-Migration
 * die echten Kategorien/Attribute anlegt, ggf. hier anpassen.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","textColor":"gold","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.24em","fontWeight":"600"}},"fontSize":"small"} -->
	<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.24em;text-transform:uppercase"><?php esc_html_e( 'Entdecken', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"fontSize":"x-large","fontFamily":"heading"} -->
	<h2 class="wp-block-heading has-text-align-center has-heading-font-family has-x-large-font-size" style="margin-bottom:var(--wp--preset--spacing--60)"><?php esc_html_e( 'Unser Sortiment', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","minimumColumnWidth":"15rem"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"backgroundColor":"wine","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"18px"},"dimensions":{"minHeight":"260px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group has-contrast-color has-wine-background-color has-text-color has-background" style="border-radius:18px;min-height:260px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"medium","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-medium-font-size"><?php esc_html_e( 'Weine', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Weiss, Rot, Rosé, Schaumwein & Süsswein — sorgfältig kuratiert.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="has-small-font-size" style="font-weight:600"><a href="/shop/" style="color:inherit"><?php esc_html_e( 'Entdecken →', 'feinspitz' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"backgroundColor":"gold","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"18px"},"dimensions":{"minHeight":"260px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group has-base-color has-gold-background-color has-text-color has-background" style="border-radius:18px;min-height:260px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"medium","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-medium-font-size"><?php esc_html_e( 'Gourmet', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Essige & Verjus, Öle, Senf, Pesto, Chutney & Pasta für die feine Küche.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="has-small-font-size" style="font-weight:600"><a href="/produkt-kategorie/kulinarium/" style="color:inherit"><?php esc_html_e( 'Entdecken →', 'feinspitz' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"backgroundColor":"wine-deep","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"18px"},"dimensions":{"minHeight":"260px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group has-contrast-color has-wine-deep-background-color has-text-color has-background" style="border-radius:18px;min-height:260px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"medium","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-medium-font-size"><?php esc_html_e( 'Histamingeprüft', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Laborgeprüfte Weine für empfindliche Geniesser — unsere Spezialität.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="has-small-font-size" style="font-weight:600"><a href="/produkt-schlagwort/histamingeprueft/" style="color:inherit"><?php esc_html_e( 'Entdecken →', 'feinspitz' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"backgroundColor":"sage","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"18px"},"dimensions":{"minHeight":"260px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group has-base-color has-sage-background-color has-text-color has-background" style="border-radius:18px;min-height:260px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"medium","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-medium-font-size"><?php esc_html_e( 'Vegan', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Weine & Delikatessen ganz ohne tierische Hilfsstoffe.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="has-small-font-size" style="font-weight:600"><a href="/produkt-schlagwort/vegan/" style="color:inherit"><?php esc_html_e( 'Entdecken →', 'feinspitz' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"18px","width":"2px"},"dimensions":{"minHeight":"260px"}},"borderColor":"gold","textColor":"contrast","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group has-border-color has-gold-border-color has-contrast-color has-text-color" style="border-width:2px;border-radius:18px;min-height:260px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"fontSize":"medium","fontFamily":"heading"} -->
				<h3 class="wp-block-heading has-heading-font-family has-medium-font-size"><?php esc_html_e( 'Alkoholfrei', 'feinspitz' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small"} -->
				<p class="has-small-font-size"><?php esc_html_e( 'Entalkoholisierte Weine & Alternativen mit vollem Geschmack.', 'feinspitz' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"gold","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="has-gold-color has-text-color has-small-font-size" style="font-weight:600"><a href="/produkt-schlagwort/alkoholfrei/" style="color:inherit"><?php esc_html_e( 'Entdecken →', 'feinspitz' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
