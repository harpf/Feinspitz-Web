// Installiert & aktiviert WooCommerce + Polylang über den REST-Endpoint wp/v2/plugins.
// Idempotent: bereits vorhandene Plugins werden nur aktiviert.
import { wp } from './lib/wp.mjs';

const PLUGINS = [
  { slug: 'woocommerce', name: 'WooCommerce' },
  { slug: 'polylang', name: 'Polylang' },
];

async function ensurePlugin({ slug, name }) {
  // Bereits installiert?
  const existing = await wp('/wp/v2/plugins', { query: { search: slug } }).catch(() => []);
  const found = Array.isArray(existing) ? existing.find((p) => p.plugin?.startsWith(`${slug}/`) || p.textdomain === slug) : null;

  if (found) {
    if (found.status !== 'active') {
      await wp(`/wp/v2/plugins/${encodeURIComponent(found.plugin)}`, { method: 'POST', body: { status: 'active' } });
      console.log(`✓ ${name} aktiviert (war installiert).`);
    } else {
      console.log(`✓ ${name} bereits aktiv.`);
    }
    return;
  }

  // Neu installieren + aktivieren.
  try {
    const created = await wp('/wp/v2/plugins', { method: 'POST', body: { slug, status: 'active' } });
    console.log(`✓ ${name} installiert & aktiviert (${created.plugin}).`);
  } catch (e) {
    if (String(e.message).includes('DISALLOW_FILE_MODS') || e.status === 403) {
      console.error(`✗ ${name}: Datei-Änderungen auf dem Server gesperrt (DISALLOW_FILE_MODS).`);
      console.error('  → Bitte einmalig per wp-admin installieren: Plugins → Installieren → "' + name + '".');
    } else {
      throw e;
    }
  }
}

for (const p of PLUGINS) {
  try { await ensurePlugin(p); }
  catch (e) { console.error(`✗ ${p.name}: ${e.message}`); process.exitCode = 1; }
}
