import { chromium } from '@playwright/test'
import fs from 'node:fs'

// Phone-viewport demo of the mobile CRM: navigation, cards, actions, sheets, roles.
const BASE = 'http://127.0.0.1:5173'
const OUT = '/tmp/mobile-demo-video'
fs.rmSync(OUT, { recursive: true, force: true })
fs.mkdirSync(OUT, { recursive: true })

const pause = (page, ms) => page.waitForTimeout(ms)

async function login(page, email) {
  await page.goto(BASE + '/login')
  await pause(page, 700)
  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', 'password')
  await pause(page, 400)
  await page.getByRole('button', { name: /sign in/i }).click()
  await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 20000 })
  await page.waitForLoadState('networkidle').catch(() => {})
  await pause(page, 1100)
}

const browser = await chromium.launch()
const ctx = await browser.newContext({
  viewport: { width: 390, height: 844 },
  isMobile: true, hasTouch: true, deviceScaleFactor: 2,
  recordVideo: { dir: OUT, size: { width: 390, height: 844 } },
})
const page = await ctx.newPage()

// 1. Login
await login(page, 'admin@eventflow.test')

// 2. Dashboard: morning numbers
await pause(page, 900)
await page.evaluate(() => window.scrollTo({ top: 500, behavior: 'smooth' }))
await pause(page, 900)
await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }))
await pause(page, 800)

// 3. Drawer navigation
await page.getByLabel('Open menu', { exact: true }).click()
await pause(page, 800)
await page.getByRole('button', { name: 'Inventory', exact: true }).click()
await pause(page, 700)
await page.getByRole('button', { name: 'Sales', exact: true }).click()
await pause(page, 500)
await page.getByRole('link', { name: 'Leads & CRM', exact: true }).click()
await page.waitForLoadState('networkidle').catch(() => {})
await pause(page, 900)

// 4. Leads as cards, scroll through
await page.evaluate(() => window.scrollTo({ top: 600, behavior: 'smooth' }))
await pause(page, 900)
await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }))
await pause(page, 600)

// 5. Board view stacked
await page.getByRole('button', { name: 'Board view', exact: true }).click()
await pause(page, 900)
await page.evaluate(() => window.scrollTo({ top: 500, behavior: 'smooth' }))
await pause(page, 800)
await page.getByRole('button', { name: 'Table view', exact: true }).click()
await pause(page, 800)

// 6. Search a module
await page.getByLabel('Search this module').fill('Oberoi')
await pause(page, 1100)
await page.getByLabel('Search this module').fill('')
await pause(page, 800)

// 7. Quotations: card with footer actions, record panel sheet, edit
await page.goto(BASE + '/quotations')
await page.waitForLoadState('networkidle').catch(() => {})
await pause(page, 900)
await page.locator('.record-card .rc-actions button[aria-label="View"]').first().click()
await pause(page, 1100)
await page.evaluate(() => document.querySelector('.record-panel')?.scrollTo({ top: 300, behavior: 'smooth' }))
await pause(page, 800)
await page.getByLabel('Close details').click()
await pause(page, 700)
const editBtn = page.locator('.record-card .rc-actions button[aria-label="Edit"]:not([disabled])').first()
if (await editBtn.count()) {
  await editBtn.click()
  await pause(page, 1100)
  await page.evaluate(() => window.scrollTo({ top: 400, behavior: 'smooth' }))
  await pause(page, 900)
  await page.getByRole('button', { name: /cancel|close/i }).first().click().catch(() => page.keyboard.press('Escape'))
  await pause(page, 600)
}

// 8. Quick find sheet
await page.getByRole('button', { name: 'Quick find' }).click()
await pause(page, 800)
await page.getByLabel('Search across modules').fill('Oberoi')
await pause(page, 1100)
await page.getByLabel('Close quick find').click()
await pause(page, 500)

// 9. Invoices: record payment on the card
await page.goto(BASE + '/invoices')
await page.waitForLoadState('networkidle').catch(() => {})
await pause(page, 900)
await page.evaluate(() => window.scrollTo({ top: 400, behavior: 'smooth' }))
await pause(page, 900)

// 10. Gate scanner
await page.goto(BASE + '/gate')
await pause(page, 900)

// 11. Client role: portal cards only
await page.getByTitle('Sign out').click()
await page.waitForURL(/login/, { timeout: 15000 })
await pause(page, 500)
await login(page, 'client@eventflow.test')
await pause(page, 1100)
await page.evaluate(() => window.scrollTo({ top: 400, behavior: 'smooth' }))
await pause(page, 800)
await page.getByLabel('Open menu', { exact: true }).click()
await pause(page, 900)

await ctx.close()
await browser.close()
const v = fs.readdirSync(OUT).filter((f) => f.endsWith('.webm'))
console.log('videos:', v)
