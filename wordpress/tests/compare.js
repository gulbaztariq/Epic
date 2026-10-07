// Screenshots every public route on the Laravel reference site and on the WordPress port, then
// compares them: pixel difference of the full page, and a diff of the visible text.
//   NODE_PATH=/opt/node22/lib/node_modules node compare.js [filter] [--mobile]
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const LARAVEL = (process.env.LARAVEL_BASE || 'http://127.0.0.1:8080').replace(/\/$/, '');
const WP = (process.env.WP_BASE || 'http://127.0.0.1:8081').replace(/\/$/, '');
const OUT = process.env.OUT_DIR || path.join(__dirname, 'out');
fs.mkdirSync(OUT, { recursive: true });

const filter = process.argv[2] && !process.argv[2].startsWith('--') ? process.argv[2] : '';
const mobile = process.argv.includes('--mobile');

// [name, laravel path, wordpress path]
const same = (name, p) => [name, p, p];
const routes = [
    same('home', '/'),
    same('about', '/who-we-are'),
    same('vision', '/who-we-are/vision-mission'),
    same('principles', '/who-we-are/epic-principles'),
    same('strengths', '/who-we-are/our-strengths'),
    same('team', '/who-we-are/epic-team'),
    same('board', '/who-we-are/board-of-directors'),
    same('advisory', '/who-we-are/advisory-council'),
    same('themes', '/what-we-do/themes'),
    same('projects', '/what-we-do/projects'),
    same('project-show', '/what-we-do/projects/skills-for-the-future-workforce'),
    same('chapters', '/what-we-do/international-chapters'),
    same('events', '/events'),
    same('events-past', '/events?show=past'),
    same('event-show', '/events/pakistans-economic-reform-agenda'),
    same('event-show-past', '/events/past-roundtable-on-youth-employment'),
    same('partnerships', '/partnerships'),
    same('mous', '/partnerships/mous'),
    same('memberships', '/partnerships/memberships'),
    same('publications', '/publications'),
    same('publications-type', '/publications?type=Policy%20Brief'),
    same('journal', '/publications/journal'),
    same('newsletter', '/publications/e-newsletter'),
    same('publication-show', '/publications/pakistans-growth-opportunity'),
    same('publication-show-2', '/publications/green-growth-for-a-resilient-pakistan'),
    same('blogs', '/blogs-and-articles'),
    same('blogs-article', '/blogs-and-articles?category=article'),
    same('blog-show', '/blogs-and-articles/why-skills-matter'),
    same('careers', '/get-involved/careers'),
    same('career-show', '/get-involved/careers/senior-research-fellow'),
    same('career-show-2', '/get-involved/careers/policy-intern'),
    same('volunteer', '/get-involved/volunteer'),
    same('subscribe', '/get-involved/subscribe'),
    same('contact', '/contact'),
    same('press', '/media/press-releases'),
    same('press-show', '/media/press-releases/epic-statement-on-the-budget'),
    same('podcast', '/media/podcast'),
    same('podcast-show', '/media/podcast/the-future-of-work-in-pakistan'),
    same('podcast-show-audio', '/media/podcast/startups-and-policy'),
    same('videos', '/media/youtube'),
    same('gallery', '/media/gallery'),
    same('album', '/media/gallery/annual-policy-dialogue-2025'),
    same('search', '/search?q=growth'),
    same('search-empty', '/search'),
    same('search-none', '/search?q=zzzzqq'),
    ['custom-page', '/p/research-agenda', '/research-agenda'],
    ['privacy', '/p/privacy-policy', '/privacy-policy'],
    ['terms', '/p/terms-of-use', '/terms-of-use'],
    same('not-found', '/no-such-page-here'),
].filter(([name]) => !filter || name.includes(filter));

const HIDE = `
  *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
  .visitor-counter strong { visibility: hidden !important; }
  .reveal { opacity: 1 !important; transform: none !important; }
`;

async function visit(context, url) {
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push('JS: ' + e.message));
    page.on('response', (r) => { if (r.status() >= 400 && !/favicon/.test(r.url())) errors.push(`${r.status()} ${r.url()}`); });
    const response = await page.goto(url, { waitUntil: 'load', timeout: 60000 });
    await page.addStyleTag({ content: HIDE });
    await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 40)); }
        window.scrollTo(0, 0);
        document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-visible'));
        document.querySelectorAll('img[loading=lazy]').forEach((i) => { i.loading = 'eager'; });
    });
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(250);
    const text = await page.evaluate(() => document.body.innerText.replace(/[ \t]+/g, ' ').replace(/\n\s*\n+/g, '\n').trim());
    const png = await page.screenshot({ fullPage: true });
    await page.close();
    return { status: response.status(), png, text, errors };
}

function diffImages(a, b) {
    const A = PNG.sync.read(a), B = PNG.sync.read(b);
    const width = Math.max(A.width, B.width), height = Math.max(A.height, B.height);
    const pad = (img) => {
        const out = new PNG({ width, height });
        out.data.fill(255);
        PNG.bitblt(img, out, 0, 0, img.width, img.height, 0, 0);
        return out;
    };
    const PA = pad(A), PB = pad(B), D = new PNG({ width, height });
    const changed = pixelmatch(PA.data, PB.data, D.data, width, height, { threshold: 0.12 });
    return { changed, total: width * height, sizeA: [A.width, A.height], sizeB: [B.width, B.height], diff: PNG.sync.write(D) };
}

function textDiff(a, b) {
    const la = a.split('\n'), lb = b.split('\n');
    const sb = new Set(lb), sa = new Set(la);
    return { onlyLaravel: la.filter((l) => !sb.has(l)).slice(0, 8), onlyWp: lb.filter((l) => !sa.has(l)).slice(0, 8) };
}

(async () => {
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    const viewport = mobile ? { width: 390, height: 844 } : { width: 1440, height: 900 };
    const ctxL = await browser.newContext({ viewport, deviceScaleFactor: 1 });
    const ctxW = await browser.newContext({ viewport, deviceScaleFactor: 1 });
    const summary = [];

    for (const [name, lp, wp] of routes) {
        const tag = mobile ? name + '.m' : name;
        let line;
        try {
            const [L, W] = [await visit(ctxL, LARAVEL + lp), await visit(ctxW, WP + wp)];
            fs.writeFileSync(path.join(OUT, tag + '.laravel.png'), L.png);
            fs.writeFileSync(path.join(OUT, tag + '.wp.png'), W.png);
            const d = diffImages(L.png, W.png);
            const ratio = d.changed / d.total;
            if (ratio > 0.0005) { fs.writeFileSync(path.join(OUT, tag + '.diff.png'), d.diff); }
            const t = textDiff(L.text, W.text);
            line = {
                name: tag, status: `${L.status}/${W.status}`, pixels: (ratio * 100).toFixed(3) + '%',
                size: `${d.sizeA.join('x')} vs ${d.sizeB.join('x')}`,
                textDiffs: t.onlyLaravel.length + t.onlyWp.length,
                errors: [...new Set([...L.errors.map((e) => 'L ' + e), ...W.errors.map((e) => 'W ' + e)])].slice(0, 4),
                onlyLaravel: t.onlyLaravel, onlyWp: t.onlyWp,
            };
        } catch (e) {
            line = { name: tag, error: String(e).slice(0, 200) };
        }
        summary.push(line);
        const flag = line.error ? 'ERR' : (parseFloat(line.pixels) > 0.1 || line.textDiffs || line.errors.length || line.status !== '200/200' && name !== 'not-found' ? 'DIFF' : ' ok ');
        console.log(`[${flag}] ${line.name.padEnd(22)} ${line.error || `${line.status}  px=${line.pixels.padStart(7)}  ${line.size}  text=${line.textDiffs}  err=${line.errors.length}`}`);
    }

    fs.writeFileSync(path.join(OUT, mobile ? 'summary.mobile.json' : 'summary.json'), JSON.stringify(summary, null, 2));
    await browser.close();
})();
