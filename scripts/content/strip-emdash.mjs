// Ersetzt Gedankenstriche (— / –) durch einen einfachen Bindestrich (-) in allen
// Seiten- und Beitrags-Inhalten (Titel, Inhalt, Auszug), alle Sprachen.
// Idempotent: läuft ohne Änderung durch, wenn keine Striche mehr da sind.
import { wp } from '../lib/wp.mjs';

const clean = ( s ) => ( typeof s === 'string' ? s.replace( /[—–]/g, '-' ) : s );

async function fetchAll( type ) {
  const out = [];
  for ( let page = 1; page <= 20; page++ ) {
    const batch = await wp( `/wp/v2/${type}`, { query: { per_page: 100, page, status: 'any', context: 'edit' } } ).catch( () => [] );
    if ( ! Array.isArray( batch ) || ! batch.length ) break;
    out.push( ...batch );
    if ( batch.length < 100 ) break;
  }
  return out;
}

let changed = 0;
for ( const type of [ 'pages', 'posts' ] ) {
  const items = await fetchAll( type );
  for ( const it of items ) {
    const body = {};
    const title = it.title?.raw ?? '';
    const content = it.content?.raw ?? '';
    const excerpt = it.excerpt?.raw ?? '';
    if ( clean( title ) !== title ) body.title = clean( title );
    if ( clean( content ) !== content ) body.content = clean( content );
    if ( clean( excerpt ) !== excerpt ) body.excerpt = clean( excerpt );
    if ( Object.keys( body ).length ) {
      await wp( `/wp/v2/${type}/${it.id}`, { method: 'POST', body } );
      console.log( `✓ ${type}/${it.id} (${it.slug}): ${Object.keys( body ).join( ', ' )} bereinigt` );
      changed++;
    }
  }
}
console.log( `\nFertig. ${changed} Objekte geändert (idempotent).` );
