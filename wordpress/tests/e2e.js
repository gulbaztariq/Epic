// Behaviour checks for the WordPress port: redirects, pagination, forms, counter, JS widgets.
const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');
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
const RUN = Date.now().toString(36); // unique per run, so the checks can be repeated on the same site
const check = (name, ok, detail = '') => { if (!ok) failed++; console.log(`${ok ? ' ok ' : 'FAIL'} ${name}${detail ? '  — ' + detail : ''}`); };

(async () => {
    /* ------------------------------------------------------------ redirects */
    const redirects = [
        ['/p/privacy-policy', 301, '/privacy-policy'],
        ['/p/terms-of-use', 301, '/terms-of-use'],
        ['/p/research-agenda', 301, '/research-agenda'],
        ['/who-we-are/board-of-governance', 301, '/who-we-are/board-of-directors'],
        ['/sitemap.xml', 301, '/sitemap_index.xml'],
        ['/what-we-do', 301, '/what-we-do/themes'],
        ['/get-involved', 301, '/get-involved/careers'],
        ['/media', 301, '/media/press-releases'],
        ['/?s=growth', 301, '/search?q=growth'],
        ['/publications/', 301, '/publications'],
        ['/who-we-are/board-of-directors/', 301, '/who-we-are/board-of-directors'],
        ['/media/press-releases/epic-launches-journal', 200, null],
        ['/blogs-and-articles/epic-launches-journal', 301, '/media/press-releases/epic-launches-journal'],
        ['/media/press-releases/why-skills-matter', 301, '/blogs-and-articles/why-skills-matter'],
        ['/publications/journal', 200, null],
        ['/sitemap_index.xml', 200, null],
        ['/robots.txt', 200, null],
        ['/favicon.ico', 200, null],
        ['/wp-json/epic/v1/hit', 404, null],
    ];
    for (const [url, status, to] of redirects) {
        const res = await fetch(WP + url, { redirect: 'manual' });
        const loc = res.headers.get('location') || '';
        check(`redirect ${url}`, res.status === status && (!to || loc.replace(WP, '') === to), `${res.status} ${loc.replace(WP, '')}`);
    }

    /* ----------------------------------------------------------- pagination */
    wpcli(`eval 'for ($i = 1; $i <= 11; $i++) { $id = wp_insert_post(["post_type"=>"epic_publication","post_status"=>"publish","post_title"=>"Pagination test $i"]); update_post_meta($id,"epic_collection","collection"); update_post_meta($id,"epic_type","Policy Brief"); update_post_meta($id,"epic_published_at","2020-01-".sprintf("%02d",$i)); }'`);
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const jsErrors = [];
    page.on('pageerror', (e) => jsErrors.push(e.message));

    await page.goto(WP + '/publications');
    check('pagination page 1 has 9 cards', (await page.locator('.pub-card').count()) === 9);
    check('pagination controls shown', (await page.locator('.pagination li.active span').innerText()) === '1');
    await page.goto(WP + '/publications?page=2');
    const cards2 = await page.locator('.pub-card').count();
    check('?page=2 works (not 404)', cards2 > 0 && (await page.locator('.pagination li.active span').innerText()) === '2', `${cards2} cards`);
    check('page 2 not a 404', !(await page.title()).includes('Page not found'));
    await page.goto(WP + '/publications?type=Policy%20Brief&page=2');
    check('type filter + page works', (await page.locator('.pub-card').count()) > 0);
    check('type chip active', (await page.locator('.chip.is-active').innerText()).includes('Policy Brief'));
    wpcli(`eval '$ids = get_posts(["post_type"=>"epic_publication","s"=>"Pagination test","posts_per_page"=>-1,"fields"=>"ids"]); foreach($ids as $i) wp_delete_post($i,true);'`);

    /* --------------------------------------------------------------- search */
    await page.goto(WP + '/search?q=growth');
    check('search finds results', (await page.locator('main article.card').count()) > 0, (await page.locator('.text-muted').first().innerText()));
    await page.goto(WP + '/?s=skills');
    check('core ?s= search lands on /search', page.url().includes('/search?q=skills'));

    /* ---------------------------------------------------------------- forms */
    wpcli(`eval 'delete_transient("epic_rate_" . md5("127.0.0.1"));'`);
    const before = JSON.parse(wpcli(`eval 'echo json_encode([(int) wp_count_posts("epic_message")->publish, (int) wp_count_posts("epic_subscriber")->publish, (int) wp_count_posts("epic_volunteer")->publish]);'`));

    await page.goto(WP + '/contact');
    await page.fill('#c-name', 'Test Person');
    await page.fill('#c-email', `test-${RUN}@example.com`);
    await page.fill('#c-message', 'Hello from the end-to-end test.');
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('form[action*="contact"] button[type=submit]')]);
    check('contact success message', (await page.locator('.alert-success').innerText()).includes('Thank you for reaching out'));
    check('flash is gone on reload', await (async () => { await page.goto(WP + '/contact'); return (await page.locator('.alert').count()) === 0; })());

    await page.goto(WP + '/contact');
    await page.fill('#c-name', 'Keep Me');
    await page.fill('#c-email', 'not-an-email');
    await page.evaluate(() => document.querySelectorAll('form').forEach((f) => f.setAttribute('novalidate', '')));
    await page.fill('#c-message', '');
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('form[action*="contact"] button[type=submit]')]);
    const errText = await page.locator('.alert-error').innerText();
    check('contact validation errors listed', /message field is required/.test(errText) && /valid email/.test(errText), errText.replace(/\n/g, ' | ').slice(0, 120));
    check('old input kept after error', (await page.inputValue('#c-name')) === 'Keep Me');

    await page.goto(WP + '/get-involved/subscribe');
    await page.fill('#s-email', `reader-${RUN}@example.com`);
    await page.fill('#s-name', 'Reader');
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('.form-card button[type=submit]')]);
    check('subscribe success', (await page.locator('.alert-success').innerText()).includes('You are subscribed'));
    // Subscribing again must update, not duplicate.
    await page.goto(WP + '/get-involved/subscribe');
    await page.fill('#s-email', `reader-${RUN}@example.com`);
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('.form-card button[type=submit]')]);

    // The inline subscribe band on another page.
    await page.goto(WP + '/events');
    await page.fill('.subscribe-inline input[type=email]', `band-${RUN}@example.com`);
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('.subscribe-inline button')]);
    check('footer band subscribe returns to the page it was on', page.url().includes('/events?epic_flash='), page.url().replace(WP, ''));

    fs.writeFileSync(path.join(os.tmpdir(), 'cv-test.pdf'), '%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF');
    await page.goto(WP + '/get-involved/volunteer');
    await page.fill('#v-name', 'Vol Unteer');
    await page.fill('#v-email', `vol-${RUN}@example.com`);
    await page.selectOption('#v-interest', { index: 1 });
    await page.setInputFiles('#v-cv', path.join(os.tmpdir(), 'cv-test.pdf'));
    await page.fill('#v-message', 'I can help.');
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('.form-card button[type=submit]')]);
    check('volunteer success', (await page.locator('.alert-success').innerText()).includes('volunteering'));

    fs.writeFileSync(path.join(os.tmpdir(), 'bad.exe'), 'MZ');
    await page.goto(WP + '/get-involved/volunteer');
    await page.fill('#v-name', 'Bad File');
    await page.fill('#v-email', `bad-${RUN}@example.com`);
    await page.evaluate(() => document.querySelectorAll('form').forEach((f) => f.setAttribute('novalidate', '')));
    await page.setInputFiles('#v-cv', path.join(os.tmpdir(), 'bad.exe'));
    await Promise.all([page.waitForURL(/epic_flash=/), page.click('.form-card button[type=submit]')]);
    check('volunteer rejects a non-document CV', (await page.locator('.alert-error').count()) === 1, (await page.locator('.alert-error').innerText()).replace(/\n/g, ' ').slice(0, 100));

    const after = JSON.parse(wpcli(`eval 'echo json_encode([(int) wp_count_posts("epic_message")->publish, (int) wp_count_posts("epic_subscriber")->publish, (int) wp_count_posts("epic_volunteer")->publish]);'`));
    check('one message saved', after[0] === before[0] + 1, `${before[0]} → ${after[0]}`);
    check('two subscribers saved (one repeated)', after[1] === before[1] + 2, `${before[1]} → ${after[1]}`);
    check('one volunteer application saved', after[2] === before[2] + 1, `${before[2]} → ${after[2]}`);
    const cv = wpcli(`eval '$p = get_posts(["post_type"=>"epic_volunteer","posts_per_page"=>1,"orderby"=>"ID","order"=>"DESC"])[0]; $id = get_post_meta($p->ID,"epic_cv_path",true); echo wp_get_attachment_url($id);'`);
    check('CV stored in the media library', /\.pdf$/.test(cv), cv.replace(WP, ''));

    /* ------------------------------------------------------------ the counter */
    wpcli(`eval 'global $wpdb; $wpdb->query("TRUNCATE TABLE " . Epic_Visits::table()); delete_transient("epic_visit_totals");'`);
    const visitor = await browser.newContext({ viewport: { width: 1440, height: 900 }, userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36' });
    const vp = await visitor.newPage();
    await vp.goto(WP + '/publications');
    await vp.waitForTimeout(800);
    await vp.goto(WP + '/events');
    await vp.waitForTimeout(800);
    const cookies = await visitor.cookies();
    check('visitor cookie set', cookies.some((c) => c.name === 'epic_vid'));
    const rows = JSON.parse(wpcli(`eval 'global $wpdb; echo json_encode($wpdb->get_results("SELECT path, device FROM " . Epic_Visits::table() . " ORDER BY id", ARRAY_A));'`));
    check('two page views recorded', rows.length === 2 && rows[0].path === '/publications' && rows[1].path === '/events', JSON.stringify(rows));
    const totals = JSON.parse(wpcli(`eval 'delete_transient("epic_visit_totals"); echo json_encode(Epic_Visits::totals());'`));
    check('counter: 1 visitor, 2 views', totals.visitors === 1 && totals.views === 2, JSON.stringify(totals));

    // An editor browsing the site is not a visitor, and neither is a bot.
    await page.goto(WP + '/wp-login.php');
    const bot = await (await browser.newContext({ userAgent: 'Googlebot/2.1 (+http://www.google.com/bot.html)' })).newPage();
    await bot.goto(WP + '/contact'); await bot.waitForTimeout(500);
    const botRows = JSON.parse(wpcli(`eval 'global $wpdb; echo (int) $wpdb->get_var("SELECT COUNT(*) FROM " . Epic_Visits::table());'`));
    check('a crawler is not counted', botRows === 2, `${botRows} rows`);

    /* ------------------------------------------------------------ JavaScript */
    await page.goto(WP + '/media/gallery/annual-policy-dialogue-2025');
    await page.click('.gallery-item >> nth=0');
    check('lightbox opens', (await page.locator('.lightbox.is-open').count()) === 1);

    await page.goto(WP + '/research-agenda');
    const second = page.locator('.accordion-trigger').nth(1);
    await second.click();
    check('accordion opens', await page.locator('.accordion-item.is-open').nth(0).count() > 0 && (await second.getAttribute('aria-expanded')) === 'true');

    await page.goto(WP + '/');
    await page.click('[data-search-open]');
    check('search panel opens', (await page.locator('.search-panel.is-open').count()) === 1);
    await page.keyboard.press('Escape');
    check('search panel closes on Esc', (await page.locator('.search-panel.is-open').count()) === 0);
    await page.hover('.nav-list > li.has-drop >> nth=0');
    await page.waitForTimeout(500);
    check('desktop dropdown visible on hover', await page.locator('.nav-list > li.has-drop >> nth=0').locator('.nav-drop a').first().isVisible());

    const mobile = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
    await mobile.goto(WP + '/');
    await mobile.click('[data-nav-open]');
    check('mobile menu opens', (await mobile.locator('.mobile-nav.is-open').count()) === 1);
    await mobile.click('.mobile-nav .m-parent >> nth=0');
    check('mobile submenu expands', (await mobile.locator('.mobile-nav li.is-open').count()) === 1);
    check('menu has the Board of Directors link', (await mobile.locator('.mobile-nav a', { hasText: 'Board of Directors' }).count()) === 1);

    check('no JavaScript errors', jsErrors.length === 0, jsErrors.join('; '));
    await browser.close();
    console.log(failed ? `\n${failed} FAILED` : '\nall passed');
    process.exit(failed ? 1 : 0);
})();
