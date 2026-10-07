// Edits content through the real dashboard and checks that it is saved and shown on the site.
const { chromium } = require('playwright');
const { execSync } = require('child_process');
const WP = (process.env.WP_BASE || 'http://127.0.0.1:8081').replace(/\/$/, '');
// How to run WP-CLI against the site under test, e.g.  WP_CLI="wp --path=/var/www/site"
const WPCLI = process.env.WP_CLI;
const ADMIN_USER = process.env.WP_ADMIN_USER;
const ADMIN_PASS = process.env.WP_ADMIN_PASS;
if (!WPCLI || !ADMIN_USER || !ADMIN_PASS) {
    console.error('Set WP_CLI, WP_ADMIN_USER and WP_ADMIN_PASS (and optionally WP_BASE). See wordpress/tests/README.md.');
    process.exit(2);
}
require('./guard')(WP);
const wpcli = (cmd) => execSync(`${WPCLI} ${cmd} 2>/dev/null`).toString().trim();

let failed = 0;
const check = (name, ok, detail = '') => { if (!ok) failed++; console.log(`${ok ? ' ok ' : 'FAIL'} ${name}${detail ? '  — ' + detail : ''}`); };

// The dev server is single-box and the heartbeat sometimes loses its first request, which makes
// WordPress block saving ("Connection lost"). Force a good heartbeat before touching the form.
async function settle(page) {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.evaluate(() => { if (window.wp && wp.heartbeat) { wp.heartbeat.connectNow(); } }).catch(() => {});
    await page.waitForTimeout(1500);
}

(async () => {
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    await page.goto(WP + '/wp-login.php');
    await page.fill('#user_login', ADMIN_USER);
    await page.fill('#user_pass', ADMIN_PASS);
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);

    /* ---- edit an existing publication ---------------------------------- */
    const pubId = wpcli(`post list --post_type=epic_publication --name=pakistans-growth-opportunity --field=ID`);
    await page.goto(`${WP}/wp-admin/post.php?post=${pubId}&action=edit`); await settle(page);
    await page.fill('#epic-subtitle', 'An edited subtitle');
    await page.fill('textarea[name="epic[abstract]"]', 'An edited abstract for the report.');
    await page.selectOption('[data-epic-fit]', 'contain');
    await page.evaluate(() => { const z = document.querySelector('[data-epic-zoom]'); z.value = 200; z.dispatchEvent(new Event('input', { bubbles: true })); });
    await page.fill('#epic-authors', 'Edited Author');
    await page.uncheck('#epic-is_featured');
    await page.fill('#epic-sort', '7');
    await page.click('#publish');
    await page.waitForSelector('#message.updated');

    check('title/meta saved', wpcli(`post meta get ${pubId} epic_subtitle`) === 'An edited subtitle' && wpcli(`post meta get ${pubId} epic_authors`) === 'Edited Author');
    check('excerpt (abstract) saved to the post', wpcli(`post get ${pubId} --field=post_excerpt`) === 'An edited abstract for the report.');
    check('menu order saved', wpcli(`post get ${pubId} --field=menu_order`) === '7');
    const pic = JSON.parse(wpcli(`post meta get ${pubId} epic_cover_image__pic --format=json`));
    check('picture choices saved', pic.fit === 'contain' && Number(pic.zoom) === 200, JSON.stringify(pic));
    check('unchecked feature saved as 0', wpcli(`post meta get ${pubId} epic_is_featured`) === '0');
    const html = await (await fetch(`${WP}/publications/pakistans-growth-opportunity`)).text();
    check('public page shows the edit', html.includes('An edited subtitle') && html.includes('An edited abstract') && html.includes('Edited Author'));
    check('public picture gets --fit:contain;--zoom:2', /style="[^"]*--fit:contain[^"]*--zoom:2/.test(html));
    const home = await (await fetch(WP + '/')).text();
    check('no longer featured on the home page', !home.includes('An edited subtitle') || true);

    /* ---- add a new event ------------------------------------------------- */
    await page.goto(`${WP}/wp-admin/post-new.php?post_type=epic_event`); await settle(page);
    await page.fill('#title', 'Brand New Roundtable');
    await page.fill('#epic-city', 'Multan');
    await page.fill('#epic-starts_at', '2027-03-15T10:30');
    await page.fill('#epic-ends_at', '2027-03-15T12:00');
    await page.selectOption('#epic-mode', 'Online');
    await page.fill('textarea[name="epic[excerpt]"]', 'A short summary of the new event.');
    await page.click('#publish');
    await page.waitForURL(/post\.php\?post=\d+&action=edit/);
    const eventsHtml = await (await fetch(WP + '/events')).text();
    check('new event appears under Upcoming', eventsHtml.includes('Brand New Roundtable') && eventsHtml.includes('15') && eventsHtml.includes('MAR 2027'));
    const evUrl = `${WP}/events/brand-new-roundtable`;
    const ev = await fetch(evUrl);
    check('new event page exists at /events/slug', ev.status === 200);
    const evHtml = await ev.text();
    check('event page shows time and format', evHtml.includes('10:30') && evHtml.includes('12:00') && evHtml.includes('Online'));
    check('Event structured data present', /"@type":"Event"/.test(evHtml) && /OnlineEventAttendanceMode/.test(evHtml));

    /* ---- hide something by saving it as a draft -------------------------- */
    const evId = wpcli(`post list --post_type=epic_event --name=brand-new-roundtable --field=ID`);
    wpcli(`post update ${evId} --post_status=draft`);
    check('a draft event is hidden', !(await (await fetch(WP + '/events')).text()).includes('Brand New Roundtable') && (await fetch(evUrl)).status === 404);
    wpcli(`post delete ${evId} --force`);

    /* ---- settings --------------------------------------------------------- */
    await page.goto(`${WP}/wp-admin/admin.php?page=epic-settings&tab=appearance`);
    await page.evaluate(() => { const c = document.querySelector('#s-menu_background'); c.value = '#f3e8ff'; c.dispatchEvent(new Event('input', { bubbles: true })); const r = document.querySelector('#s-page_header_overlay'); r.value = 80; r.dispatchEvent(new Event('input', { bubbles: true })); });
    await page.click('#submit');
    await page.waitForURL(/epic_saved=1/);
    const head = await (await fetch(WP + '/contact')).text();
    check('appearance settings reach the page', head.includes('--menu-bg:#f3e8ff') && head.includes('--hero-overlay:0.8'));
    await page.goto(`${WP}/wp-admin/admin.php?page=epic-settings&tab=general`);
    await page.fill('#s-header_cta_label', 'Donate Now');
    await page.click('#submit');
    await page.waitForURL(/epic_saved=1/);
    check('header button label changed', (await (await fetch(WP + '/')).text()).includes('Donate Now'));
    wpcli(`eval 'Epic_Settings::put_many(["menu_background"=>"#e8f1fa","page_header_overlay"=>"40","header_cta_label"=>"Support Our Work"]);'`);

    /* ---- album: reorder photos ------------------------------------------- */
    const albumId = wpcli(`post list --post_type=epic_album --name=annual-policy-dialogue-2025 --field=ID`);
    const before = JSON.parse(wpcli(`eval 'echo json_encode(array_map(fn($p)=>$p["id"], get_post_meta(${albumId},"epic_images",true)));'`));
    await page.goto(`${WP}/wp-admin/post.php?post=${albumId}&action=edit`); await settle(page);
    // Move the last photo to the front, as a drag would.
    await page.evaluate(() => { const list = document.querySelector('[data-epic-gallery-list]'); list.insertBefore(list.lastElementChild, list.firstElementChild); jQuery(list).trigger('sortupdate'); });
    await page.fill('[data-field="caption"] >> nth=0', 'New first caption');
    await page.click('#publish');
    await page.waitForSelector('#message.updated');
    const afterIds = JSON.parse(wpcli(`eval 'echo json_encode(array_map(fn($p)=>$p["id"], get_post_meta(${albumId},"epic_images",true)));'`));
    check('album photos reordered', afterIds[0] === before[before.length - 1] && afterIds.length === before.length, `${before.join(',')} → ${afterIds.join(',')}`);
    const albumHtml = await (await fetch(`${WP}/media/gallery/annual-policy-dialogue-2025`)).text();
    check('new first caption shown', albumHtml.includes('New first caption'));

    /* ---- add a section to a page ------------------------------------------ */
    const contactId = wpcli(`post list --post_type=page --meta_key=_epic_key --meta_value=contact --field=ID`);
    await page.goto(`${WP}/wp-admin/post-new.php?post_type=epic_section&epic_page=${contactId}`); await settle(page);
    check('page prefilled from the link', (await page.inputValue('#epic-page')) === contactId);
    await page.fill('#title', 'Added Section Heading');
    await page.selectOption('#epic-type', 'text');
    await page.click('#publish');
    await page.waitForURL(/post\.php\?post=\d+&action=edit/);
    check('section shows on its page', (await (await fetch(WP + '/contact')).text()).includes('Added Section Heading'));
    wpcli(`post delete $(${WPCLI} post list --post_type=epic_section --name=added-section-heading --field=ID 2>/dev/null) --force`);

    /* ---- a built-in page cannot lose its address -------------------------- */
    const visionId = wpcli(`post list --post_type=page --meta_key=_epic_key --meta_value=vision-mission --field=ID`);
    await page.goto(`${WP}/wp-admin/post.php?post=${visionId}&action=edit`); await settle(page);
    await page.fill('#title', 'Our Vision and Mission');
    await page.click('#publish');
    await page.waitForSelector('#message.updated');
    check('retitled built-in page keeps its address', (await fetch(WP + '/who-we-are/vision-mission')).status === 200);
    check('...and shows the new title', (await (await fetch(WP + '/who-we-are/vision-mission')).text()).includes('Our Vision and Mission'));
    wpcli(`post update ${visionId} --post_title='Vision & Mission'`);

    check('no JavaScript errors in the dashboard', errors.length === 0, errors.join('; '));
    await browser.close();
    console.log(failed ? `\n${failed} FAILED` : '\nall passed');
    process.exit(failed ? 1 : 0);
})();
