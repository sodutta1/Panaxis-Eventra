import { test, expect, type Page } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

const API = 'http://127.0.0.1:8000/api/v1'
const PAGES = ['/login', '/dashboard', '/events', '/leads', '/clients', '/venues', '/vendors', '/quotations', '/invoices', '/expenses', '/guests', '/tickets', '/budgets', '/work-orders', '/notifications', '/reports', '/analytics', '/admin']

async function login(page: Page) {
  const r = await page.request.post(`${API}/auth/login`, { data: { email: 'admin@eventflow.test', password: 'password' } })
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
}

test.setTimeout(240000)
test('no serious or critical WCAG 2.1 A/AA violations on staff pages', async ({ page }) => {
  const problems: string[] = []
  await page.goto('/login')
  await page.waitForLoadState('domcontentloaded')
  const loginScan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze()
  for (const v of loginScan.violations.filter((x) => x.impact === 'serious' || x.impact === 'critical')) problems.push(`/login ${v.id} (${v.nodes.length}): ${v.nodes[0].target.join(' ')}`)
  await login(page)
  for (const path of PAGES.slice(1)) {
    await page.goto(path, { waitUntil: 'domcontentloaded' })
    await page.locator('main h1, main h2').first().waitFor({ timeout: 30000 }).catch(() => {})
    await page.waitForTimeout(600)
    const res = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze()
    for (const v of res.violations.filter((x) => x.impact === 'serious' || x.impact === 'critical')) problems.push(`${path} ${v.id} (${v.nodes.length}): ${v.nodes[0].target.join(' ')}`)
  }
  expect(problems, problems.join('\n')).toEqual([])
})
