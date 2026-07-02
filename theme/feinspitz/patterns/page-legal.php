<?php
/**
 * Title: Rechtstext (AGB / Lieferbedingungen / Datenschutz / Impressum)
 * Slug: feinspitz/page-legal
 * Categories: feinspitz
 * Post Types: page
 * Description: Schlichtes, gut lesbares Layout für Rechtstexte. Pro Seite (AGB, Lieferbedingungen, Datenschutz, Impressum) einmal einfügen und Inhalt ergänzen.
 * Keywords: agb, lieferbedingungen, datenschutz, impressum, rechtstext, legal
 * Inserter: true
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"bottom":{"color":"var:preset|color|gold","width":"3px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="border-bottom-color:var(--wp--preset--color--gold);border-bottom-width:3px;padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"wine","fontSize":"small"} -->
	<p class="has-wine-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600"><?php esc_html_e( 'Rechtliches', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"x-large"} -->
	<h1 class="wp-block-heading has-x-large-font-size" style="font-style:normal;font-weight:600"><?php esc_html_e( 'Titel des Rechtstexts', 'feinspitz' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic"}},"textColor":"sage","fontSize":"small"} -->
	<p class="has-sage-color has-text-color has-small-font-size" style="font-style:italic"><?php esc_html_e( 'Zuletzt aktualisiert: TT.MM.JJJJ', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|80"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:paragraph {"fontSize":"medium"} -->
	<p class="has-medium-font-size"><?php esc_html_e( 'Diese Seite hält die rechtlichen Rahmenbedingungen fest. Ersetzen Sie die folgenden Platzhalter durch den jeweiligen Text (AGB, Lieferbedingungen, Datenschutzerklärung oder Impressum).', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"fontSize":"large"} -->
	<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( '1. Abschnitt', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Platzhaltertext. Beschreiben Sie hier den ersten Abschnitt des Rechtstexts. Nutzen Sie weitere Überschriften und Absätze für eine klare Gliederung.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"fontSize":"large"} -->
	<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( '2. Abschnitt', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Platzhaltertext. Ergänzen Sie so viele Abschnitte, wie der jeweilige Rechtstext benötigt.', 'feinspitz' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:separator {"backgroundColor":"gold","className":"is-style-wide"} -->
	<hr class="wp-block-separator has-text-color has-gold-color has-alpha-channel-opacity has-gold-background-color has-background is-style-wide"/>
	<!-- /wp:separator -->

	<!-- wp:heading {"level":2,"fontSize":"large"} -->
	<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Angaben gemäss Impressum', 'feinspitz' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"left":{"color":"var:preset|color|wine","width":"4px"}}},"backgroundColor":"contrast","layout":{"type":"constrained"}} -->
	<div class="wp-block-group has-contrast-background-color has-background" style="border-left-color:var(--wp--preset--color--wine);border-left-width:4px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
		<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.9"}}} -->
		<p style="line-height:1.9"><strong><?php esc_html_e( 'Feinspitz Weine & Genuss', 'feinspitz' ); ?></strong><br><?php echo esc_html__( 'Bahnhofstrasse 80', 'feinspitz' ) . '<br>' . esc_html__( 'CH-8902 Urdorf', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.9"}}} -->
		<p style="line-height:1.9"><strong><?php esc_html_e( 'Administration', 'feinspitz' ); ?></strong><br><?php echo esc_html__( 'Baumgartenstrasse 16', 'feinspitz' ) . '<br>' . esc_html__( 'CH-8902 Urdorf', 'feinspitz' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.9"}}} -->
		<p style="line-height:1.9"><strong><?php esc_html_e( 'Telefon', 'feinspitz' ); ?></strong> <a href="tel:+41765888902">+41 76 588 89 02</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
