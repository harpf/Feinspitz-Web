// Bindet die Anfrage-Formulare in die Seiten ein (idempotent, HTTP-only via wp/v2):
//
//   - Kontakt-Seite (DE + EN-Übersetzung): Shortcode [feinspitz_form type="kontakt"]
//     an den bestehenden Inhalt anhängen (nur, falls noch nicht vorhanden).
//   - Weinproben-Seite (Slug "weinproben"): anlegen (falls fehlt) mit Theme-Stil-
//     Inhalt + [feinspitz_form type="weinprobe"].
//   - Catering-Seite (Slug "catering"): anlegen (falls fehlt) mit Theme-Stil-Inhalt
//     + [feinspitz_form type="catering"].
//
// Voraussetzung: Das Theme (inc/forms.php) muss auf dem Server AKTIV sein, damit der
// Shortcode gerendert wird. Der Shortcode selbst ist sprachbewusst (DE/EN), sodass
// dieselbe Einbindung auf EN-Seiten automatisch englische Labels zeigt.
//
// Der Mailversand des Servers ist derzeit DEFEKT (wp_mail schlägt fehl, kein SMTP) —
// das Formular speichert Anfragen daher zusätzlich als privaten CPT (siehe forms.php).
// Sobald SMTP eingerichtet ist, funktioniert der Versand an info@feinspitz.ch ohne
// weitere Änderung.
import { wp } from './lib/wp.mjs';

const FORM_MARK = 'feinspitz_form';

// Dry-Run (Standard bei FORMS_DRY=1): liest nur, schreibt nichts — zum gefahrlosen
// Vorab-Prüfen. WICHTIG: Erst NACH dem Theme-Deploy scharf ausführen, sonst zeigen
// die Live-Seiten den Shortcode als literalen Text (inc/forms.php noch nicht aktiv).
const DRY = process.env.FORMS_DRY === '1';

/** Schreibende REST-Calls; im Dry-Run nur protokollieren. */
async function mutate( route, opts, note ) {
	if ( DRY ) {
		console.log( `   [dry] würde schreiben: ${ note }` );
		return { id: '(dry)' };
	}
	return wp( route, opts );
}

/** Formular-Abschnitt (Block-Markup) um den Shortcode. */
function formSection( type ) {
	return `

<!-- wp:group {"align":"full","backgroundColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">[${FORM_MARK} type="${type}"]</div>
<!-- /wp:group -->
`;
}

/** Erste Seite zu einem Slug (context=edit, jeder Status) oder null. */
async function pageBySlug( slug ) {
	const pages = await wp( '/wp/v2/pages', { query: { slug, context: 'edit', status: 'any' } } );
	return Array.isArray( pages ) && pages.length ? pages[ 0 ] : null;
}

/** Seite nach ID (context=edit). */
async function pageById( id ) {
	return wp( `/wp/v2/pages/${ id }`, { query: { context: 'edit' } } );
}

function hasForm( page ) {
	return !!page && new RegExp( FORM_MARK ).test( page.content?.raw ?? '' );
}

/** Formular an eine bestehende Seite anhängen (nur, wenn noch nicht vorhanden). */
async function appendForm( page, type, label ) {
	if ( ! page ) {
		console.log( `· ${ label }: Seite nicht gefunden — übersprungen.` );
		return;
	}
	if ( hasForm( page ) ) {
		console.log( `✓ ${ label } (ID ${ page.id }): Formular bereits eingebunden.` );
		return;
	}
	const content = ( page.content?.raw ?? '' ) + formSection( type );
	await mutate( `/wp/v2/pages/${ page.id }`, { method: 'POST', body: { content } }, `Formular an ${ label } (ID ${ page.id })` );
	console.log( `✓ ${ label } (ID ${ page.id }): [${ FORM_MARK } type="${ type }"] angehängt.` );
}

/** Seite per Slug sicherstellen (anlegen falls fehlt), Inhalt inkl. Formular setzen. */
async function ensurePage( { slug, title, content } ) {
	const existing = await pageBySlug( slug );
	if ( existing ) {
		if ( hasForm( existing ) ) {
			console.log( `✓ ${ slug } (ID ${ existing.id }): existiert bereits inkl. Formular.` );
		} else {
			await appendForm( existing, formTypeForSlug( slug ), slug );
		}
		return existing.id;
	}
	const body = { slug, title, content, status: 'publish', lang: 'de' };
	const created = await mutate( '/wp/v2/pages', { method: 'POST', body }, `Seite "${ slug }" anlegen (publish, lang=de)` );
	console.log( `✓ ${ slug }: angelegt & veröffentlicht (ID ${ created.id }, lang=de).` );
	return created.id;
}

function formTypeForSlug( slug ) {
	if ( 'weinproben' === slug ) return 'weinprobe';
	if ( 'catering' === slug ) return 'catering';
	return 'kontakt';
}

// --- Seiteninhalte (DE) für neu anzulegende Seiten -------------------------

const WEINPROBEN_CONTENT = `<!-- wp:cover {"gradient":"wine-fade","isDark":true,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)"><span aria-hidden="true" class="wp-block-cover__background has-wine-fade-gradient-background has-background-gradient"></span><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->
<p class="has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600">Weinproben</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.02"}},"textColor":"contrast","fontSize":"xx-large"} -->
<h1 class="wp-block-heading has-contrast-color has-text-color has-xx-large-font-size" style="font-style:normal;font-weight:600;line-height:1.02">Verkosten, was verträglich ist</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"textColor":"contrast","fontSize":"large"} -->
<p class="has-contrast-color has-text-color has-large-font-size" style="margin-top:var(--wp--preset--spacing--40)">Erleben Sie histamingeprüfte Weine mit allen Sinnen · geführt, ehrlich und ohne Berührungsängste. Ob im Shop, an Ihrem Event oder privat bei Ihnen zu Hause.</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1080px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size">Im Shop</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Geführte Degustation an der Bahnhofstrasse in Urdorf · kleine Runde, grosse Auswahl, persönliche Einordnung zu jedem Wein.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size">An Events</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Firmenanlässe, Jubiläen oder Messen · wir bringen die Verkostung zu Ihnen und begleiten Ihre Gäste durch die Weine.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size">Privat zu Hause</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Die Weinprobe im eigenen Wohnzimmer · mit Freunden, in entspannter Runde und ganz auf Ihren Geschmack abgestimmt.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
${ formSection( 'weinprobe' ) }`;

const CATERING_CONTENT = `<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained","contentSize":"960px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->
<p class="has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600">Catering</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.02"}},"textColor":"contrast","fontSize":"xx-large"} -->
<h1 class="wp-block-heading has-contrast-color has-text-color has-xx-large-font-size" style="font-style:normal;font-weight:600;line-height:1.02">Vom Apéro bis zum Grillevent</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"textColor":"contrast","fontSize":"large"} -->
<p class="has-contrast-color has-text-color has-large-font-size" style="margin-top:var(--wp--preset--spacing--40)">Wir gestalten Ihren Anlass kulinarisch · vom feinen Apéro riche bis zum entspannten Grillevent, immer mit der passenden Weinbegleitung.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1080px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"border":{"top":{"color":"var:preset|color|wine","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|40","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--wine);border-top-width:3px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Apéro &amp; Apéro riche</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Feine Häppchen, Antipasti und Gourmet-Spezialitäten aus unserem Sortiment · der stilvolle Einstieg in jeden Anlass.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"style":{"border":{"top":{"color":"var:preset|color|wine","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|40","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--wine);border-top-width:3px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Firmen- &amp; Privatanlässe</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Jubiläen, Geschäftsessen oder private Feiern · abgestimmt auf Ihre Gästezahl und mit passender Weinbegleitung.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"style":{"border":{"top":{"color":"var:preset|color|wine","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|40","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--wine);border-top-width:3px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Grillevent</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Entspannt draussen: vom Grill frisch auf den Teller, kombiniert mit unkomplizierten, verträglichen Weinen für laue Abende.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
${ formSection( 'catering' ) }`;

// --- Ablauf ----------------------------------------------------------------

// 1) Kontakt (DE + EN-Übersetzung).
const kontaktDe = await pageBySlug( 'kontakt' );
await appendForm( kontaktDe, 'kontakt', 'Kontakt (DE)' );
const enId = kontaktDe?.pll_translations?.en;
if ( enId && enId !== kontaktDe.id ) {
	const kontaktEn = await pageById( enId ).catch( () => null );
	await appendForm( kontaktEn, 'kontakt', 'Kontakt (EN)' );
} else {
	console.log( '· Kontakt (EN): keine verknüpfte EN-Übersetzung gefunden — übersprungen.' );
}

// 2) Weinproben-Seite.
await ensurePage( { slug: 'weinproben', title: 'Weinproben', content: WEINPROBEN_CONTENT } );

// 3) Catering-Seite.
await ensurePage( { slug: 'catering', title: 'Catering', content: CATERING_CONTENT } );

console.log( '\nFertig (idempotent). Hinweis: Der Shortcode wird erst nach Theme-Deploy gerendert.' );
