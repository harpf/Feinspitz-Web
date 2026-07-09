<?php
/**
 * Title: Weinlexikon-Eintrag (Vorlage)
 * Slug: feinspitz/editor-weinlexikon-eintrag
 * Categories: feinspitz
 * Post Types: post
 * Description: Fertige Struktur für einen Weinlexikon-Eintrag: Kurzdefinition, Merkmale-Liste, „Gut zu wissen“-Box und verwandte Begriffe. Platzhaltertexte einfach überschreiben.
 * Keywords: weinlexikon, lexikon, begriff, definition, vorlage, glossar
 * Inserter: true
 *
 * @package Feinspitz
 *
 * Reine Block-Struktur (Überschriften/Absätze/Liste/Hinweisbox) OHNE Shortcodes
 * und OHNE Übersetzungsfunktionen – die Texte sind bewusste deutsche Platzhalter,
 * die der Redakteur direkt im Editor überschreibt (keine neuen gettext-msgids).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic","fontWeight":"400"}},"fontSize":"medium"} -->
<p class="has-medium-font-size" style="font-style:italic;font-weight:400">[Platzhalter Kurzdefinition] Ein bis zwei Sätze, die den Begriff auf den Punkt bringen – so, dass ihn auch jemand ohne Vorwissen versteht.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Merkmale</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
	<!-- wp:list-item -->
	<li>[Platzhalter] Erstes typisches Merkmal oder Eigenschaft</li>
	<!-- /wp:list-item -->

	<!-- wp:list-item -->
	<li>[Platzhalter] Zweites Merkmal – z. B. Geschmack, Herkunft oder Verwendung</li>
	<!-- /wp:list-item -->

	<!-- wp:list-item -->
	<li>[Platzhalter] Drittes Merkmal – Liste nach Bedarf ergänzen oder kürzen</li>
	<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Bedeutung für den Genuss</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter] Erkläre in ein bis zwei Absätzen, warum dieser Begriff für Weingeniesserinnen und -geniesser relevant ist – etwa wie er sich im Glas bemerkbar macht oder worauf man beim Kauf achten sollte.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"6px","left":{"color":"#7d8471","width":"4px"}},"color":{"background":"#f6f1e7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:6px;border-left-color:#7d8471;border-left-width:4px;background-color:#f6f1e7;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.18em","fontWeight":"700"}},"fontSize":"small"} -->
	<p class="has-small-font-size" style="text-transform:uppercase;letter-spacing:0.18em;font-weight:700">Gut zu wissen</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>[Platzhalter] Eine überraschende Tatsache, ein häufiger Irrtum oder ein praktischer Merksatz rund um den Begriff.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Verwandte Begriffe</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter] Nenne zwei bis drei verwandte Lexikon-Einträge und verlinke sie, damit die Leserschaft weiterstöbern kann – z. B. <em>Begriff A</em>, <em>Begriff B</em>, <em>Begriff C</em>.</p>
<!-- /wp:paragraph -->
