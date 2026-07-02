<?php
/**
 * Wein-Finder-Pattern: Startseiten-Teaser.
 *
 * Registriert in inc/wine-finder.php als feinspitz/wine-finder-teaser.
 * Reines Block-Markup (kein Pattern-Header): ein Shortcode-Block, der den Teaser
 * zur RENDER-Zeit sprachbewusst erzeugt (DE/EN inline, ohne neue gettext-msgids).
 *
 * Die Startseite kann diesen Teaser konfliktfrei einbinden, ohne dass der
 * Wein-Finder-Branch front-page.html verändert.
 *
 * @package Feinspitz
 */
?>
<!-- wp:group {"align":"wide","className":"feinspitz-wf-teaser-wrap","layout":{"type":"constrained","contentSize":"1080px"}} -->
<div class="wp-block-group alignwide feinspitz-wf-teaser-wrap">
	<!-- wp:shortcode -->[feinspitz_wine_finder_teaser]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
