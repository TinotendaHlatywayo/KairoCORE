import fs from 'node:fs';
import pkg from '/home/wayne/.npm/_npx/e41f203b7505f1fb/node_modules/playwright/index.js';
const { chromium } = pkg;

const BASE = 'https://lvh.me';
const ROUTES = ['/', '/features', '/blog', '/about', '/contact', '/school-login', '/register', '/blog/' + 'nonexistent'.replace('nonexistent','')];
// keep only likely routes; remove bad
const CLEAN_ROUTES = ['/', '/features', '/blog', '/about', '/contact', '/school-login'];
const VIEWPORTS = [
  { width: 1280, height: 800 },
  { width: 1440, height: 900 },
  { width: 1840, height: 1000 },
  { width: 1920, height: 1080 },
  { width: 2560, height: 1300 },
];

function dockSnapshot(page) {
  return page.evaluate(() => {
    const items = [...document.querySelectorAll('.kf-dock-item')];
    const container = document.querySelector('.kf-dock-list');
    const cs = (el) => getComputedStyle(el);
    const c = cs(container);
    return {
      container: {
        background: c.background, border: c.border, borderRadius: c.borderRadius,
        position: c.position, top: c.top, right: c.right, transform: c.transform,
      },
      items: items.map((b) => {
        const s = cs(b);
        const svg = b.querySelector('svg');
        const ss = svg ? cs(svg) : null;
        return {
          background: s.background, color: s.color, opacity: s.opacity,
          filter: s.filter, mixBlendMode: s.mixBlendMode,
          svgFill: ss && ss.fill, svgStroke: ss && ss.stroke, svgColor: ss && ss.color,
          svgOpacity: ss && ss.opacity, svgFilter: ss && ss.filter,
        };
      }),
    };
  });
}

function navMetrics(page) {
  return page.evaluate(() => {
    const logo = document.querySelector('.kf-logo');
    const btn = [...document.querySelectorAll('a')].find(a => a.textContent.trim() === 'Register School');
    const lr = logo.getBoundingClientRect();
    const br = btn.getBoundingClientRect();
    const vw = window.innerWidth;
    return { logoLeft: lr.left, btnRight: br.right, viewport: vw };
  });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  const shotDir = 'tests/playwright/screenshots';
  fs.mkdirSync(shotDir, { recursive: true });

  let failures = 0;
  const rows = [];

  for (const theme of ['light', 'dark']) {
    for (const vp of VIEWPORTS) {
      await page.setViewportSize(vp);
      // baseline blog
      await page.goto(BASE + '/blog', { waitUntil: 'networkidle' });
      if (theme === 'dark') {
        await page.click('button[data-kf-theme]');
        await page.waitForTimeout(200);
      }
      const blogNav = await navMetrics(page);
      const blogDock = await dockSnapshot(page);

      for (const route of CLEAN_ROUTES) {
        await page.goto(BASE + route, { waitUntil: 'networkidle' });
        if (theme === 'dark') {
          // ensure dark: toggle only if html not dark
          const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
          if (!isDark) await page.click('button[data-kf-theme]');
          await page.waitForTimeout(200);
        }
        const m = await navMetrics(page);
        const d = await dockSnapshot(page);
        const leftGap = m.logoLeft;
        const rightGap = m.viewport - m.btnRight;
        const gapDiff = Math.abs(leftGap - rightGap);
        const logoDiff = Math.abs(m.logoLeft - blogNav.logoLeft);
        const btnDiff = Math.abs(m.btnRight - blogNav.btnRight);
        const navOk = gapDiff <= 2 && logoDiff <= 2 && btnDiff <= 2;
        const dockOk = JSON.stringify(d) === JSON.stringify(blogDock);

        // screenshots
        await page.screenshot({ path: `${shotDir}/${route.replace(/\//g,'_') || 'home'}-${vp.width}x${vp.height}-${theme}-top.png`, clip: { x: 0, y: 0, width: vp.width, height: 200 } });
        const dockBox = await page.evaluate(() => {
          const el = document.querySelector('.kf-dock');
          const r = el.getBoundingClientRect();
          return { x: Math.max(0, r.left - 20), y: Math.max(0, r.top - 20), width: r.width + 40, height: r.height + 40 };
        });
        await page.screenshot({ path: `${shotDir}/${route.replace(/\//g,'_') || 'home'}-${vp.width}x${vp.height}-${theme}-dock.png`, clip: dockBox });

        const ok = navOk && dockOk;
        if (!ok) failures++;
        rows.push({ theme, vp: `${vp.width}x${vp.height}`, route, navOk, dockOk, gapDiff: gapDiff.toFixed(1), logoDiff: logoDiff.toFixed(1), btnDiff: btnDiff.toFixed(1) });
      }
    }
  }

  console.log('\nPASS/FAIL TABLE');
  console.log('theme | viewport | route | nav | dock | gapDiff | logoDiff | btnDiff');
  for (const r of rows) {
    console.log(`${r.theme} | ${r.vp} | ${r.route} | ${r.navOk ? 'PASS' : 'FAIL'} | ${r.dockOk ? 'PASS' : 'FAIL'} | ${r.gapDiff} | ${r.logoDiff} | ${r.btnDiff}`);
  }
  console.log('\nTotal failures:', failures);
  await browser.close();
  process.exit(failures ? 1 : 0);
})();
