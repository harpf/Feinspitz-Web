// Verifiziert die REST-Authentifizierung: wer bin ich, welche Rollen?
import { wp, WP_BASE, WP_USER } from './lib/wp.mjs';

try {
  const me = await wp('/wp/v2/users/me', { query: { context: 'edit' } });
  console.log(`✓ Auth OK gegen ${WP_BASE}`);
  console.log(`  User: ${me.name} (id ${me.id}), Rollen: ${(me.roles || []).join(', ')}`);
  if (!(me.roles || []).includes('administrator')) {
    console.warn('  ⚠ Kein Administrator — Plugin-Install/Deploy könnten fehlschlagen.');
  }
} catch (e) {
  console.error(`✗ ${e.message}`);
  process.exitCode = 1;
}
