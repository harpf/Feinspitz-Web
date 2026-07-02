// Entfernt den doppelten Kontakt-/Administrations-Block von der Über-uns-Seite
// (DE + EN). Dieser Block ("öffnungszeiten … Shop-Abhollager … Administration
// Baumgartenstrasse 16 …") gehört auf die Kontakt-Seite und wurde beim Import
// versehentlich auch an die Über-uns-Seite gehängt.
//
// Idempotent: schneidet den Inhalt vor der ersten Öffnungszeiten-/Kontakt-
// Überschrift ab; ist der Block schon weg, passiert nichts.
import { wp } from '../lib/wp.mjs';

// Marker, ab dem der (duplizierte) Kontaktblock beginnt — DE und EN.
const CUT_MARKERS = [
  /<h[1-6][^>]*>\s*öffnungszeiten/i,
  /<h[1-6][^>]*>\s*Öffnungszeiten/i,
  /<h[1-6][^>]*>\s*Opening hours/i,
  /<h[1-6][^>]*>\s*So finden Sie uns/i,
  /<h[1-6][^>]*>\s*How to find us/i,
  /<h[1-6][^>]*>\s*hier sind wir/i,
];

function stripContactBlock( html ) {
  let cut = -1;
  for ( const re of CUT_MARKERS ) {
    const m = html.match( re );
    if ( m && ( cut === -1 || m.index < cut ) ) {
      cut = m.index;
    }
  }
  if ( cut === -1 ) {
    return null; // kein Block gefunden → nichts zu tun
  }
  return html.slice( 0, cut ).replace( /\s+$/, '' ) + '\n';
}

// Über uns, Impressum (about) und AGB bekamen beim Import den Kontaktblock
// fälschlich angehängt. Die Kontakt-Seite selbst wird bewusst NICHT angefasst.
for ( const slug of [ 'ueber-uns', 'about', 'agb' ] ) {
  // Alle Sprachversionen der Über-uns-Seite (DE + verknüpfte EN).
  const de = ( await wp( '/wp/v2/pages', { query: { slug, context: 'edit', status: 'any' } } ) )[ 0 ];
  if ( ! de ) { console.log( `- ${slug}: nicht gefunden` ); continue; }

  const ids = new Set( [ de.id ] );
  if ( de.pll_translations ) {
    for ( const id of Object.values( de.pll_translations ) ) {
      ids.add( id );
    }
  }

  for ( const id of ids ) {
    const page = await wp( `/wp/v2/pages/${id}`, { query: { context: 'edit' } } );
    const raw = page.content?.raw || '';
    const cleaned = stripContactBlock( raw );
    if ( cleaned === null ) {
      console.log( `= Seite ${id} (${page.slug}, ${page.lang || '?'}): kein Kontaktblock — ok` );
      continue;
    }
    if ( cleaned === raw ) {
      console.log( `= Seite ${id}: bereits sauber` );
      continue;
    }
    await wp( `/wp/v2/pages/${id}`, { method: 'POST', body: { content: cleaned } } );
    console.log( `✓ Seite ${id} (${page.slug}, ${page.lang || '?'}): Kontaktblock entfernt (${raw.length} → ${cleaned.length} Zeichen)` );
  }
}
console.log( '\nFertig (idempotent).' );
