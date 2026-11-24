import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'
const require = createRequire(join(dirname(fileURLToPath(import.meta.url)), '../../frontend/package.json'))
const { chromium } = require('playwright')

const BASE = 'http://127.0.0.1:5173'
const API = 'http://127.0.0.1:8000'
const pause = (page, ms = 1100) => page.waitForTimeout(ms)

async function openGroup(page, name) {
  const toggle = page.getByRole('button', { name, exact: true })
  if (await toggle.getAttribute('aria-expanded') !== 'true') await toggle.click()
  await pause(page, 700)
}

const browser = await chromium.launch()

// ---------- Part 1: desktop ----------
const ctx = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  recordVideo: { dir: '/tmp/demo-video', size: { width: 1440, height: 900 } },
})
const page = await ctx.newPage()

// Sign in through the real login form
await page.goto(BASE + '/login')
await pause(page, 800)
await page.getByLabel(/email/i).fill('admin@eventflow.test')
await page.getByLabel(/password/i).fill('password')
await pause(page, 500)
await page.getByRole('button', { name: /sign in/i }).click()
await page.getByRole('heading', { name: 'Dashboard' }).waitFor()
await pause(page, 1600)

// Grouped sections: walk the accordion
for (const g of ['Inventory', 'Communication', 'Post-sale', 'Insights', 'Admin', 'Sales']) {
  await openGroup(page, g)
}
await pause(page, 900)

// Pin favourites
await openGroup(page, 'Inventory')
await page.getByRole('button', { name: 'Pin Venues', exact: true }).click()
await pause(page, 700)
await openGroup(page, 'Sales')
await page.getByRole('button', { name: 'Pin Quotations', exact: true }).click()
await pause(page, 900)

// Collapse to rail, expand again
await page.getByRole('button', { name: 'Collapse sidebar', exact: true }).click()
await pause(page, 1400)
await page.getByRole('button', { name: 'Expand sidebar', exact: true }).click()
await pause(page, 900)

// Reload: rail state, open group and pins persist
await page.getByRole('button', { name: 'Collapse sidebar', exact: true }).click()
await pause(page, 800)
await page.reload()
await page.getByRole('heading', { name: 'Dashboard' }).waitFor()
await pause(page, 1500)
await page.getByRole('button', { name: 'Expand sidebar', exact: true }).click()
await pause(page, 1000)

// Quick find
await page.keyboard.press('Control+k')
await pause(page, 700)
await page.getByLabel('Search across modules').pressSequentially('Oberoi', { delay: 90 })
await page.locator('.quick-result').first().waitFor()
await pause(page, 1100)
await page.locator('.quick-result').first().click()
await pause(page, 1500)

// Theme toggle: dark, then back to light
await page.getByRole('button', { name: 'Toggle theme', exact: true }).click()
await pause(page, 1500)
await page.getByRole('button', { name: 'Toggle theme', exact: true }).click()
await pause(page, 900)

// Role-aware navigation: sign out, sign in as sales executive
await page.getByRole('button', { name: 'Sign out', exact: true }).click()
await pause(page, 900)
await page.getByLabel(/email/i).fill('sales@eventflow.test')
await page.getByLabel(/password/i).fill('password')
await pause(page, 400)
await page.getByRole('button', { name: /sign in/i }).click()
await page.getByRole('heading', { name: 'Dashboard' }).waitFor()
await pause(page, 1600)
await openGroup(page, 'Sales')
await openGroup(page, 'Inventory')
await openGroup(page, 'Insights')
await pause(page, 1200)

// Client role: only the client portal
await page.getByRole('button', { name: 'Sign out', exact: true }).click()
await pause(page, 900)
await page.getByLabel(/email/i).fill('client@eventflow.test')
await page.getByLabel(/password/i).fill('password')
await pause(page, 400)
await page.getByRole('button', { name: /sign in/i }).click()
await pause(page, 1800)
await openGroup(page, 'Post-sale')
await pause(page, 1400)
await ctx.close()

// ---------- Part 2: mobile drawer ----------
const mctx = await browser.newContext({
  viewport: { width: 390, height: 844 },
  recordVideo: { dir: '/tmp/demo-video', size: { width: 390, height: 844 } },
})
const mpage = await mctx.newPage()
const r = await mpage.request.post(API + '/api/v1/auth/login', { data: { email: 'admin@eventflow.test', password: 'password' } })
const s = await r.json()
await mpage.goto(BASE + '/login')
await mpage.evaluate(x => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
await mpage.goto(BASE + '/dashboard')
await mpage.getByRole('heading', { name: 'Dashboard' }).waitFor()
await pause(mpage, 1200)
await mpage.getByLabel('Open menu', { exact: true }).click()
await pause(mpage, 1400)
await openGroup(mpage, 'Sales')
await mpage.getByRole('link', { name: 'Clients', exact: true }).click()
await mpage.getByRole('heading', { name: /Clients/ }).waitFor()
await pause(mpage, 1200)
await mpage.getByLabel('Open menu', { exact: true }).click()
await pause(mpage, 1200)
await mpage.getByLabel('Close menu', { exact: true }).click()
await pause(mpage, 1200)
await mctx.close()

await browser.close()
console.log('DEMO_DONE')
