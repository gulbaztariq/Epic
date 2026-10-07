// Logs in to the WordPress dashboard and opens every EPIC screen, looking for PHP errors.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
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
const wpcli = (cmd) => execSync(`${WPCLI} ${cmd} 2>/dev/null`).toString().trim();

const OUT = process.env.OUT_DIR || path.join(__dirname, 'out');

function firstId(type) {
    const out = execSync(`${WPCLI} post list --post_type=${type} --post_status=any --field=ID --orderby=ID --order=ASC --posts_per_page=1 2>/dev/null | grep -E '^[0-9]+$' | head -1`).toString().trim();
    return out;
}

(async () => {
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    const problems = [];
    page.on('pageerror', (e) => problems.push('JS error: ' + e.message));
    page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) problems.push('console: ' + m.text().slice(0, 160)); });

    await page.goto(WP + '/wp-login.php');
    await page.fill('#user_login', ADMIN_USER);
    await page.fill('#user_pass', ADMIN_PASS);
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);

    const types = ['epic_section', 'epic_item', 'epic_focus', 'epic_stat', 'epic_member', 'epic_project', 'epic_chapter', 'epic_partner', 'epic_career', 'epic_publication', 'epic_post', 'epic_event', 'epic_podcast', 'epic_video', 'epic_album', 'epic_message', 'epic_volunteer', 'epic_subscriber'];
    const screens = [
        ['overview', '/wp-admin/admin.php?page=epic'],
        ['settings', '/wp-admin/admin.php?page=epic-settings'],
        ['settings-appearance', '/wp-admin/admin.php?page=epic-settings&tab=appearance'],
        ['settings-analytics', '/wp-admin/admin.php?page=epic-settings&tab=analytics'],
        ['visitors', '/wp-admin/admin.php?page=epic-visitors'],
        ['pages-list', '/wp-admin/edit.php?post_type=page'],
        ['menus', '/wp-admin/nav-menus.php'],
    ];
    for (const t of types) {
        screens.push(['list-' + t, `/wp-admin/edit.php?post_type=${t}`]);
        const id = firstId(t);
        if (id) { screens.push(['edit-' + t, `/wp-admin/post.php?post=${id}&action=edit`]); }
        if (!['epic_message', 'epic_volunteer', 'epic_subscriber'].includes(t)) { screens.push(['new-' + t, `/wp-admin/post-new.php?post_type=${t}`]); }
    }
    for (const key of ['home', 'about-us', 'contact']) {
        const id = execSync(`${WPCLI} post list --post_type=page --meta_key=_epic_key --meta_value=${key} --field=ID 2>/dev/null | grep -E '^[0-9]+$' | head -1`).toString().trim();
        screens.push(['edit-page-' + key, `/wp-admin/post.php?post=${id}&action=edit`]);
    }

    for (const [name, url] of screens) {
        problems.length = 0;
        const res = await page.goto(WP + url, { waitUntil: 'load' });
        const html = await page.content();
        const body = await page.evaluate(() => document.body.innerText);
        // PHP's own display format, "Warning: message in /file.php on line N"; a bare word such as
        // Rank Math's "Notice" setting in its page data is not an error.
        const phpErrors = (html.match(/(?:Warning|Notice|Fatal error|Parse error|Deprecated|Uncaught[^:<]*)(?:<\/b>)?:\s[^\n]{0,300}?\.php[^\n]{0,40}/g) || []).slice(0, 3);
        const ok = res.status() === 200 && !phpErrors.length && !problems.length;
        console.log(`${ok ? ' ok ' : 'FAIL'} ${name.padEnd(28)} ${res.status()} ${phpErrors.join(' | ')} ${problems.join(' | ')}`);
        if (['overview', 'settings', 'visitors', 'edit-epic_publication', 'edit-epic_album', 'edit-epic_event', 'edit-page-home', 'edit-epic_section', 'new-epic_item', 'list-epic_publication'].includes(name)) {
            await page.screenshot({ path: path.join(OUT, 'admin-' + name + '.png'), fullPage: true });
        }
    }
    await browser.close();
})();
