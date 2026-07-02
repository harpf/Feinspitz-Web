// Ergänzt die fehlenden Kontaktdaten (E-Mail, MwSt-Hinweis) auf der Kontaktseite
// (DE + EN). Idempotent: fügt nur hinzu, was noch fehlt.
import { wp } from '../lib/wp.mjs';

const EMAIL_DE = '<p>mail: <a href="mailto:info@feinspitz.ch">info@feinspitz.ch</a></p>';
const EMAIL_EN = '<p>mail: <a href="mailto:info@feinspitz.ch">info@feinspitz.ch</a></p>';
const MWST_DE = '<p><sup>1</sup> inkl. MwSt.</p>';
const MWST_EN = '<p><sup>1</sup> incl. VAT.</p>';

const de = ( await wp( '/wp/v2/pages', { query: { slug: 'kontakt', context: 'edit', status: 'any' } } ) )[ 0 ];
if ( ! de ) { console.log( 'Kontaktseite nicht gefunden' ); process.exit( 1 ); }

const targets = new Map();
targets.set( de.id, { email: EMAIL_DE, mwst: MWST_DE } );
if ( de.pll_translations ) {
  for ( const [ lang, id ] of Object.entries( de.pll_translations ) ) {
    if ( id !== de.id ) targets.set( id, { email: EMAIL_EN, mwst: MWST_EN } );
  }
}

for ( const [ id, t ] of targets ) {
  const page = await wp( `/wp/v2/pages/${id}`, { query: { context: 'edit' } } );
  let raw = page.content?.raw || '';
  let changed = false;

  if ( ! /info@feinspitz\.ch/i.test( raw ) ) {
    raw = raw.replace( /\s*$/, '' ) + '\n' + t.email + '\n';
    changed = true;
  }
  if ( ! /inkl\. MwSt|incl\. VAT/i.test( raw ) ) {
    raw = raw.replace( /\s*$/, '' ) + '\n' + t.mwst + '\n';
    changed = true;
  }

  if ( changed ) {
    await wp( `/wp/v2/pages/${id}`, { method: 'POST', body: { content: raw } } );
    console.log( `✓ Kontakt ${id} (${page.slug}, ${page.lang || '?'}): E-Mail/MwSt ergänzt` );
  } else {
    console.log( `= Kontakt ${id}: bereits vollständig` );
  }
}
console.log( '\nFertig (idempotent).' );
