// Setzt die Social-Media-URLs (Option feinspitz_social) via REST /wp/v2/settings.
// URLs hier pflegen; nur Plattformen mit URL erscheinen im Footer.
import { wp } from '../lib/wp.mjs';

const SOCIAL = {
  instagram: 'https://www.instagram.com/feinspitz8902/',
  // facebook: 'https://www.facebook.com/...',
  // linkedin: '', x: '', youtube: '', tiktok: '', whatsapp: '',
};

const clean = Object.fromEntries(Object.entries(SOCIAL).filter(([, v]) => v && v.trim()));
const res = await wp('/wp/v2/settings', { method: 'POST', body: { feinspitz_social: clean } });
console.log('Gesetzt:', JSON.stringify(res.feinspitz_social || {}));
