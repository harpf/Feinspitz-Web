<?php
/**
 * Title: Ratgeber-Artikel (Vorlage)
 * Slug: feinspitz/editor-ratgeber-artikel
 * Categories: feinspitz
 * Post Types: post
 * Description: Fertige Struktur für einen Ratgeber-Beitrag: Einleitung, drei Abschnitte, Tipp-Box und Fazit. Platzhaltertexte einfach überschreiben.
 * Keywords: ratgeber, artikel, vorlage, beitrag, blog, struktur
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
<!-- wp:paragraph {"placeholder":"Kurze Einleitung …"} -->
<p>[Platzhalter Einleitung] Führe die Leserin oder den Leser in zwei bis drei Sätzen an das Thema heran: Worum geht es, und warum lohnt sich das Weiterlesen? Halte den Einstieg konkret und einladend.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">[Platzhalter Zwischentitel 1] Der erste Aspekt</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter] Erläutere hier den ersten Abschnitt. Ein bis zwei Absätze reichen. Nutze klare, alltagsnahe Beispiele, damit das Thema greifbar wird.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">[Platzhalter Zwischentitel 2] Der zweite Aspekt</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter] Vertiefe das Thema oder beleuchte eine andere Perspektive. Verlinke bei Bedarf auf passende Produkte im Shop oder auf einen verwandten Weinlexikon-Eintrag.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"6px","left":{"color":"#c9a24b","width":"4px"}},"color":{"background":"#f6f1e7"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:6px;border-left-color:#c9a24b;border-left-width:4px;background-color:#f6f1e7;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.18em","fontWeight":"700"}},"fontSize":"small"} -->
	<p class="has-small-font-size" style="text-transform:uppercase;letter-spacing:0.18em;font-weight:700">Tipp vom Feinspitz</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>[Platzhalter Tipp] Ein praktischer Hinweis, eine Empfehlung oder eine kleine Faustregel, die den Artikel abrundet. Diese Box hebt sich vom Fliesstext ab.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">[Platzhalter Zwischentitel 3] Optionaler dritter Aspekt</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter] Diesen Abschnitt bei Bedarf verwenden oder löschen. Nicht jeder Artikel braucht drei Abschnitte – lieber knapp und gut als lang und beliebig.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Fazit</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>[Platzhalter Fazit] Fasse die wichtigste Erkenntnis in zwei bis drei Sätzen zusammen und gib der Leserschaft einen klaren nächsten Schritt mit (z. B. eine Weinempfehlung oder eine Einladung zur Verkostung).</p>
<!-- /wp:paragraph -->
