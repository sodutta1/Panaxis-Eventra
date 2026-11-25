import { test, expect, type Page } from '@playwright/test'

const API = 'http://127.0.0.1:8000/api/v1'

async function login(page: Page) {
  const r = await page.request.post(`${API}/auth/login`, { data: { email: 'admin@eventflow.test', password: 'password' } })
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
}

test('a list already opened stays readable offline, with a banner, and writes are refused', async ({ page, context }) => {
  await login(page)
  await page.goto('/clients', { waitUntil: 'domcontentloaded' })
  const rows = page.locator('table tbody tr:visible, .record-card:visible')
  await expect(rows.first()).toBeVisible({ timeout: 30000 })
  const firstText = (await rows.first().innerText()).split('\n')[0]

  // connection drops, then the user opens the same list again from inside the app
  await context.setOffline(true)
  await page.evaluate(() => window.dispatchEvent(new Event('offline')))
  await expect(page.getByRole('status').filter({ hasText: 'You are offline' })).toBeVisible()
  await page.evaluate(() => { history.pushState({}, '', '/clients?page=1'); window.dispatchEvent(new PopStateEvent('popstate')) })
  await expect(rows.first()).toBeVisible({ timeout: 15000 })
  await expect(rows.first()).toContainText(firstText)

  // a write is refused with a plain message
  const msg = await page.evaluate(async () => {
    const t = localStorage.getItem('ef_token')
    try { await fetch('/api/v1/clients', { method: 'POST', headers: { Authorization: `Bearer ${t}`, 'Content-Type': 'application/json' }, body: '{}' }); return 'sent' } catch { return 'blocked' }
  })
  expect(msg).toBe('blocked')

  await context.setOffline(false)
  await page.evaluate(() => window.dispatchEvent(new Event('online')))
  await expect(page.getByRole('status').filter({ hasText: 'You are offline' })).toHaveCount(0)
})
