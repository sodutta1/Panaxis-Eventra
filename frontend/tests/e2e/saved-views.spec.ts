import { test, expect, type Page } from '@playwright/test'

const API = 'http://127.0.0.1:8000/api/v1'

async function login(page: Page) {
  const r = await page.request.post(`${API}/auth/login`, { data: { email: 'admin@eventflow.test', password: 'password' } })
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
  return s.token as string
}

test('a saved view follows the user to a second device', async ({ page, browser }) => {
  const token = await login(page)
  const name = 'Sync view ' + Date.now()
  await page.goto('/clients?search=nexus')
  page.once('dialog', (d) => d.accept(name))
  await page.getByRole('button', { name: 'Save view' }).click()
  await expect.poll(async () => {
    const r = await page.request.get(`${API}/saved-views?module=${encodeURIComponent('/clients')}`, { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } })
    return JSON.stringify((await r.json()).data)
  }).toContain(name)

  // second device: fresh browser context, nothing in local storage
  const other = await browser.newContext({ baseURL: 'http://127.0.0.1:5173', viewport: page.viewportSize() ?? undefined })
  const p2 = await other.newPage()
  await login(p2)
  await p2.goto('/clients')
  await expect(p2.getByLabel('Saved views').locator('option', { hasText: name })).toHaveCount(1, { timeout: 20000 })
  await p2.getByLabel('Saved views').selectOption({ label: name })
  await expect(p2).toHaveURL(/search=nexus/)
  await other.close()
})
