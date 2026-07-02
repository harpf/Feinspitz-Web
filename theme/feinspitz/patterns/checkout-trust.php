<?php
/**
 * Kassen-Pattern: Trust-/USP-Leiste.
 *
 * Vier Vertrauens-Argumente (sichere Zahlung, Schweizer Versand,
 * histamingeprüft, persönliche Beratung) - als Reassurance unter Warenkorb & Kasse.
 *
 * Registriert in inc/checkout.php als feinspitz/checkout-trust.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"base","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"700","lineHeight":"1"}},"fontSize":"large","fontFamily":"heading"} -->
			<p class="has-gold-color has-text-color has-heading-font-family has-large-font-size" style="font-weight:700;line-height:1"><?php esc_html_e( 'Sicher bezahlen', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
			<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Verschlüsselte Übertragung und geprüfte Zahlungsarten · Ihre Daten bleiben geschützt.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"700","lineHeight":"1"}},"fontSize":"large","fontFamily":"heading"} -->
			<p class="has-gold-color has-text-color has-heading-font-family has-large-font-size" style="font-weight:700;line-height:1"><?php esc_html_e( 'Schweizer Versand', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
			<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Sorgfältig verpackt und zuverlässig in die ganze Schweiz geliefert.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"700","lineHeight":"1"}},"fontSize":"large","fontFamily":"heading"} -->
			<p class="has-gold-color has-text-color has-heading-font-family has-large-font-size" style="font-weight:700;line-height:1"><?php esc_html_e( 'Histamingeprüft', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
			<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Der erste Anbieter histamingeprüfter Weine der Schweiz · Genuss auch für Empfindliche.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph {"textColor":"gold","style":{"typography":{"fontWeight":"700","lineHeight":"1"}},"fontSize":"large","fontFamily":"heading"} -->
			<p class="has-gold-color has-text-color has-heading-font-family has-large-font-size" style="font-weight:700;line-height:1"><?php esc_html_e( 'Persönliche Beratung', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"small"} -->
			<p class="has-small-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Fragen zum Wein? Wir beraten Sie gerne persönlich · ehrlich und kompetent.', 'feinspitz' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
