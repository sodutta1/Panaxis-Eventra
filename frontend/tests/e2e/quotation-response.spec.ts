import { test, expect, type Page } from '@playwright/test'

const API = 'http://127.0.0.1:8000/api/v1'

async function login(page: Page, email: string) {
  const r = await page.request.post(`${API}/auth/login`, { data: { email, password: 'password' } })
  expect(r.ok()).toBeTruthy()
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); if (x.user) localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
  return s.token as string
}

async function makeQuotation(page: Page, token: string, note: string) {
  const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` }
  const clients = await (await page.request.get(`${API}/clients?per_page=100`, { headers })).json()
  const client = clients.data.find((c: any) => c.client_code === 'CL-1001')
  expect(client).toBeTruthy()
  const r = await page.request.post(`${API}/quotations`, { headers, data: { client_id: client.id, issue_date: '2026-11-24', tax_rate: 18, notes: note, items: [{ item_name: 'Sound system', quantity: 1, unit_price: 10000 }] } })
  expect(r.ok()).toBeTruthy()
  return (await r.json()).data
}

test('staff sends a quotation, client accepts it, staff is notified', async ({ page, browser }) => {
  const adminToken = await login(page, 'admin@eventflow.test')
  const q = await makeQuotation(page, adminToken, 'resp-accept-' + Date.now())
  await page.goto('/quotations')
  const narrow = (page.viewportSize()?.width ?? 1440) < 700
  const rowSel = narrow ? '.record-card' : 'tbody tr'
  const row = page.locator(rowSel, { hasText: q.quotation_number }).first()
  await expect(row).toBeVisible({ timeout: 20000 })
  await row.getByRole('button', { name: 'Send to client' }).first().click()
  await expect(row.getByText('sent', { exact: false }).first()).toBeVisible()

  const ctx = await browser.newContext({ baseURL: 'http://127.0.0.1:5173', viewport: page.viewportSize() ?? undefined })
  const cp = await ctx.newPage()
  await login(cp, 'client@eventflow.test')
  await cp.goto('/portal/client?section=quotations')
  const crow = cp.locator(narrow ? '.record-card' : 'tr', { hasText: q.quotation_number }).first()
  await expect(crow).toBeVisible({ timeout: 20000 })
  await crow.getByRole('button', { name: 'Accept' }).click()
  await cp.getByLabel('Note for the event team').fill('Looks good, please proceed')
  await cp.getByRole('button', { name: 'Confirm accept' }).click()
  await expect(crow.getByText('accepted').first()).toBeVisible()
  await expect(crow.getByRole('button', { name: 'Accept' })).toHaveCount(0)
  await ctx.close()

  await page.goto('/quotations')
  const after = page.locator(rowSel, { hasText: q.quotation_number }).first()
  await expect(after.getByText('accepted').first()).toBeVisible()
  await expect(after.getByText('Looks good, please proceed')).toBeVisible()
  const notes = await (await page.request.get(`${API}/notifications`, { headers: { Accept: 'application/json', Authorization: `Bearer ${adminToken}` } })).json()
  expect(JSON.stringify(notes)).toContain(`Quotation ${q.quotation_number} accepted`)
})

test('client can decline a quotation and a second response is refused', async ({ page }) => {
  const adminToken = await login(page, 'admin@eventflow.test')
  const q = await makeQuotation(page, adminToken, 'resp-decline-' + Date.now())
  await page.request.post(`${API}/quotations/${q.id}/send`, { headers: { Accept: 'application/json', Authorization: `Bearer ${adminToken}` } })
  const clientToken = await login(page, 'client@eventflow.test')
  await page.goto('/portal/client?section=quotations')
  const crow = page.locator(page.viewportSize()!.width < 700 ? '.record-card' : 'tr', { hasText: q.quotation_number }).first()
  await crow.getByRole('button', { name: 'Decline' }).click()
  await page.getByLabel('Note for the event team').fill('Over budget')
  await page.getByRole('button', { name: 'Confirm decline' }).click()
  await expect(crow.getByText('rejected').first()).toBeVisible()
  const again = await page.request.post(`${API}/portal/quotations/${q.id}/respond`, { headers: { Accept: 'application/json', Authorization: `Bearer ${clientToken}` }, data: { decision: 'accepted' } })
  expect(again.status()).toBe(409)
})

test('draft quotations show no Accept or Decline to the client', async ({ page }) => {
  const adminToken = await login(page, 'admin@eventflow.test')
  const q = await makeQuotation(page, adminToken, 'resp-draft-' + Date.now())
  await login(page, 'client@eventflow.test')
  await page.goto('/portal/client?section=quotations')
  const crow = page.locator(page.viewportSize()!.width < 700 ? '.record-card' : 'tr', { hasText: q.quotation_number }).first()
  await expect(crow).toBeVisible()
  await expect(crow.getByRole('button', { name: 'Accept' })).toHaveCount(0)
})
