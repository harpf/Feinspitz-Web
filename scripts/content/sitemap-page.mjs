import { wp } from '../lib/wp.mjs';
const slug='sitemap';
const existing=(await wp('/wp/v2/pages',{query:{slug,context:'edit',status:'any'}}))[0];
const body={title:'Sitemap',content:'<!-- wp:shortcode -->[feinspitz_sitemap]<!-- /wp:shortcode -->',status:'publish'};
if(existing){await wp(`/wp/v2/pages/${existing.id}`,{method:'POST',body});console.log('✓ Sitemap-Seite aktualisiert (ID '+existing.id+')');}
else{body.slug=slug;const c=await wp('/wp/v2/pages',{method:'POST',body});console.log('✓ Sitemap-Seite angelegt (ID '+c.id+')');}
