import { chromium } from '@playwright/test';
const b = await chromium.launch();
const pg = await b.newPage({ viewport: { width: 1440, height: 900 } });
pg.on('console', (m) => { if (m.type() === 'error') console.log('PAGE-ERR', m.text().slice(0, 200)); });
pg.on('pageerror', (e) => console.log('PAGEERROR', String(e).slice(0, 300)));
await pg.goto('http://127.0.0.1:5173/login');
await pg.fill('input[type="email"]', 'admin@eventflow.test');
await pg.fill('input[type="password"]', 'password');
await pg.keyboard.press('Enter');
await pg.waitForTimeout(2500);
const pages = ['budgets', 'work-orders', 'admin', 'portal/client', 'portal/vendor', 'reports', 'gate'];
for (const p of pages) {
  await pg.goto('http://127.0.0.1:5173/' + p);
  await pg.waitForTimeout(1800);
  await pg.screenshot({ path: '/tmp/shot-' + p.replaceAll('/', '-') + '.png' });
  console.log('shot', p);
}
await b.close();
console.log('done');
