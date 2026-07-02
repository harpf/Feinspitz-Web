<?php
/**
 * Produkt-Pattern: Galerie & Kaufbereich (Showcase).
 *
 * Registriert in inc/product.php als feinspitz/product-showcase.
 * Reines Block-Markup (kein Pattern-Header) - Strings via Textdomain feinspitz.
 *
 * Läuft im Single-Product-Kontext von templates/single-product.html, daher
 * greifen die woocommerce/product-* Blöcke auf das globale Produkt zu. Titel &
 * Kurzbeschreibung nutzen Core-Blöcke (post-title/post-excerpt) als robuste
 * Basis; Preis, Galerie, Warenkorb-Formular und Meta kommen von WooCommerce.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">

	<!-- wp:woocommerce/breadcrumbs {"fontSize":"small","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} /-->

	<!-- wp:columns {"verticalAlignment":"top","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-top">

		<!-- wp:column {"verticalAlignment":"top","width":"54%"} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:54%">
			<!-- wp:group {"style":{"border":{"radius":"24px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-base-background-color has-background" style="border-radius:24px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:woocommerce/product-image-gallery /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"46%"} -->
		<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:46%">

			<!-- wp:post-terms {"term":"product_cat","separator":"  ·  ","textColor":"wine","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.22em","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"fontSize":"small"} /-->

			<!-- wp:post-title {"level":1,"style":{"typography":{"lineHeight":"1.05","fontWeight":"600"}},"fontSize":"x-large","fontFamily":"heading"} /-->

			<!-- wp:woocommerce/product-price {"fontSize":"large","textColor":"wine","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} /-->

			<!-- wp:post-excerpt {"excerptLength":40,"showMoreOnNewLine":false,"style":{"typography":{"lineHeight":"1.7"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

			<!-- wp:woocommerce/add-to-cart-form /-->

			<!-- wp:separator {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"}},"color":{"background":"var:preset|color|gold"}},"className":"is-style-wide"} -->
			<hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background is-style-wide" style="background-color:var(--wp--preset--color--gold);color:var(--wp--preset--color--gold)"/>
			<!-- /wp:separator -->

			<!-- wp:woocommerce/product-meta /-->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
