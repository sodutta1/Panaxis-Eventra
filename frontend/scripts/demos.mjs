import { chromium } from '@playwright/test';
import fs from 'node:fs';

const BASE = 'http://127.0.0.1:4173'; // production build
const b = await chromium.launch();

async function tour(name, fn, { user = 'admin@eventflow.test' } = {}) {
  const ctx = await b.newContext({
    viewport: { width: 1440, height: 810 },
    recordVideo: { dir: '/tmp/demos-raw', size: { width: 1440, height: 810 } },
  });
  const pg = await ctx.newPage();
  try {
    await pg.goto(BASE + '/login');
    await pg.fill('#login-email', user);
    await pg.fill('#login-password', 'password');
    await pg.getByRole('button', { name: /sign in/i }).click();
    await pg.waitForURL('**/dashboard', { timeout: 20000 });
    await pg.waitForTimeout(1200);
    await fn(pg);
  } catch (e) {
    console.log('TOUR-FAIL', name, String(e).slice(0, 200));
  }
  await pg.waitForTimeout(800);
  await ctx.close();
  const vid = pg.video();
  if (vid) {
    const p = await vid.path();
    fs.renameSync(p, `/tmp/demos-raw/${name}.webm`);
    console.log('recorded', name);
  }
}

const idle = (pg, ms = 2000) => pg.waitForTimeout(ms);

await tour('01-dashboard', async (pg) => { await idle(pg, 3500); });

await tour('02-leads-kanban', async (pg) => {
  await pg.goto(BASE + '/leads'); await idle(pg, 2200);
  // switch to table view and back to show both
  const toggle = pg.getByRole('button', { name: /table|board/i }).first();
  if (await toggle.count()) { await toggle.click(); await idle(pg, 1500); await toggle.click(); await idle(pg, 1500); }
});

await tour('03-clients', async (pg) => { await pg.goto(BASE + '/clients'); await idle(pg, 3000); });

await tour('04-events-run-of-show', async (pg) => {
  await pg.goto(BASE + '/events'); await idle(pg, 2200);
  await pg.locator('table tbody tr').first().click();
  await idle(pg, 3000);
});

await tour('05-venues', async (pg) => { await pg.goto(BASE + '/venues'); await idle(pg, 2800); });

await tour('06-vendors-workorders', async (pg) => {
  await pg.goto(BASE + '/vendors'); await idle(pg, 2000);
  await pg.goto(BASE + '/work-orders'); await idle(pg, 2500);
});

await tour('07-quotations-pdf', async (pg) => {
  await pg.goto(BASE + '/quotations'); await idle(pg, 2200);
  const dl = pg.waitForEvent('download', { timeout: 8000 }).catch(() => null);
  await pg.getByRole('button', { name: 'PDF' }).first().click();
  await dl; await idle(pg, 1200);
});

await tour('08-invoices-payments', async (pg) => {
  await pg.goto(BASE + '/invoices'); await idle(pg, 2200);
  const pay = pg.getByRole('button', { name: 'Record payment' }).first();
  if (await pay.count()) { await pay.click(); await idle(pg, 1800); await pg.getByRole('button', { name: 'Cancel' }).click(); }
  const dl = pg.waitForEvent('download', { timeout: 8000 }).catch(() => null);
  await pg.getByRole('button', { name: 'PDF' }).first().click();
  await dl; await idle(pg, 1200);
});

await tour('09-expenses', async (pg) => { await pg.goto(BASE + '/expenses'); await idle(pg, 2800); });

await tour('10-budgets', async (pg) => { await pg.goto(BASE + '/budgets'); await idle(pg, 3000); });

await tour('11-guests-rsvp', async (pg) => { await pg.goto(BASE + '/guests'); await idle(pg, 2800); });

await tour('12-tickets-gate', async (pg) => {
  await pg.goto(BASE + '/tickets'); await idle(pg, 2000);
  // copy a real qr token from the table and verify it
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
console.log('all demos recorded');
