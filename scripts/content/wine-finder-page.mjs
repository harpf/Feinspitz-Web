// Legt die Seite „Wein-Finder" (slug wein-finder) an bzw. aktualisiert sie
// idempotent. Der Seiteninhalt ist ausschliesslich der Shortcode
// [feinspitz_wine_finder] — die gesamte Logik/Darstellung liegt im Theme
// (theme/feinspitz/inc/wine-finder.php). Zweitlauf ergibt identischen Inhalt.
//
// HTTP-only via scripts/lib/wp.mjs (Cookie + X-WP-Nonce).
//
// Aufruf:
//   node scripts/content/wine-finder-page.mjs

import { wp, WP_BASE } from '../lib/wp.mjs';

const slug = 'wein-finder';
const title = 'Wein-Finder';
const content = '<!-- wp:shortcode -->[feinspitz_wine_finder]<!-- /wp:shortcode -->';

async function main() {
  console.log(`→ Ziel: ${WP_BASE}`);

  const existing = (await wp('/wp/v2/pages', { query: { slug, context: 'edit', status: 'any' } }))[0];
  const body = { title, content, status: 'publish' };

  if (existing) {
    await wp(`/wp/v2/pages/${existing.id}`, { method: 'POST', body });
    console.log(`✓ Seite „${title}" aktualisiert (ID ${existing.id}) → ${existing.link}`);
  } else {
    body.slug = slug;
    const created = await wp('/wp/v2/pages', { method: 'POST', body });
    console.log(`✓ Seite „${title}" angelegt (ID ${created.id}) → ${created.link}`);
  }
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
