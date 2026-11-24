import { chromium } from '@playwright/test'
import fs from 'node:fs'
const BASE = 'http://127.0.0.1:5173'
const OUT = process.argv[2] || '/tmp/mobile-shots'
const routes = process.argv[3].split(',').map((r) => [r.replaceAll(':','-'), '/' + r.replaceAll(':','/')])
fs.mkdirSync(OUT, { recursive: true })
const browser = await chromium.launch()
const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })
const page = await ctx.newPage()
await page.goto(BASE + '/login')
await page.fill('input[type="email"]', process.env.EMAIL || 'admin@eventflow.test')
await page.fill('input[type="password"]', 'password')
await page.getByRole('button', { name: /sign in/i }).click()
await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 15000 })
for (const [name, path] of routes) {
  try {
    await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 20000 })
    await page.waitForTimeout(500)
    const overflowX = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
    await page.screenshot({ path: `${OUT}/${name}.png` })
    console.log(name, 'overflowX=' + overflowX)
  } catch (e) { console.log(name, 'ERR', String(e).slice(0,120)) }
}
await browser.close()
