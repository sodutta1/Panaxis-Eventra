import { chromium } from '@playwright/test';
import fs from 'node:fs';
const BASE = 'http://127.0.0.1:4173';
const b = await chromium.launch();
async function tour(name, fn) {
  const ctx = await b.newContext({ viewport: { width: 1440, height: 810 }, recordVideo: { dir: '/tmp/demos-raw', size: { width: 1440, height: 810 } } });
  const pg = await ctx.newPage();
  try {
    await pg.goto(BASE + '/login');
    await pg.fill('#login-email', 'admin@eventflow.test');
    await pg.fill('#login-password', 'password');
    await pg.getByRole('button', { name: /sign in/i }).click();
    await pg.waitForURL('**/dashboard', { timeout: 20000 });
    await pg.waitForTimeout(1200);
    await fn(pg);
  } catch (e) { console.log('TOUR-FAIL', name, String(e).slice(0, 200)); }
  await pg.waitForTimeout(800);
  await ctx.close();
  const vid = pg.video();
  if (vid) { const p = await vid.path(); fs.renameSync(p, `/tmp/demos-raw/${name}.webm`); console.log('recorded', name); }
}
const idle = (pg, ms = 2000) => pg.waitForTimeout(ms);
await tour('12-tickets-gate', async (pg) => {
  await pg.goto(BASE + '/tickets'); await idle(pg, 2000);
  const token = await pg.locator('table tbody tr td div.text-\\[10px\\]').first().innerText().catch(() => '');
  if (token) {
    await pg.fill('input[placeholder*="QR token"]', token);
    await pg.getByRole('button', { name: 'Verify at gate' }).click();
    await idle(pg, 2200);
  }
});
await tour('13-portals', async (pg) => {
  await pg.goto(BASE + '/portal/client'); await idle(pg, 2500);
  await pg.goto(BASE + '/portal/vendor'); await idle(pg, 2500);
});
await tour('14-reports', async (pg) => { await pg.goto(BASE + '/reports'); await idle(pg, 3000); });
await tour('15-admin-users', async (pg) => { await pg.goto(BASE + '/admin'); await idle(pg, 2800); });
await b.close();
console.log('batch2 done');
