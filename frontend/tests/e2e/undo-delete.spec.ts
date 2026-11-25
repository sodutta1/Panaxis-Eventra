import { test, expect, type Page } from '@playwright/test'

const API = 'http://127.0.0.1:8000/api/v1'

async function login(page: Page, email = 'admin@eventflow.test') {
  const r = await page.request.post(`${API}/auth/login`, { data: { email, password: 'password' } })
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
  return s.token as string
}

test('deleting a draft quotation shows Undo and Undo brings it back', async ({ page }) => {
  const token = await login(page)
  const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` }
  const clients = await (await page.request.get(`${API}/clients?per_page=100`, { headers })).json()
  const q = (await (await page.request.post(`${API}/quotations`, { headers, data: { client_id: clients.data[0].id, issue_date: '2026-11-25', tax_rate: 18, items: [{ item_name: 'Undo demo item', quantity: 1, unit_price: 4321 }] } })).json()).data
  await page.goto('/quotations')
  const narrow = (page.viewportSize()?.width ?? 1440) < 700
  const rowSel = narrow ? '.record-card' : 'tbody tr'
  const row = page.locator(rowSel, { hasText: q.quotation_number }).first()
  await expect(row).toBeVisible({ timeout: 20000 })
  page.once('dialog', (d) => d.accept())
  await row.getByRole('button', { name: 'Delete' }).first().click()
  await expect(page.getByRole('status').getByText('Quotation deleted.')).toBeVisible()
  await expect(page.locator(rowSel, { hasText: q.quotation_number })).toHaveCount(0)
  await page.getByRole('status').getByRole('button', { name: 'Undo' }).click()
  await expect(page.locator(rowSel, { hasText: q.quotation_number }).first()).toBeVisible({ timeout: 15000 })
  await expect(page.getByText('Quotation deleted.')).toHaveCount(0)
})

test('the API refuses to delete a sent quotation even though the UI hides the button', async ({ page }) => {
  const token = await login(page)
  const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` }
  const list = await (await page.request.get(`${API}/quotations?status=accepted&per_page=1`, { headers })).json()
  const target = list.data[0]
  expect(target).toBeTruthy()
  const res = await page.request.delete(`${API}/quotations/${target.id}`, { headers })
  expect(res.status()).toBe(409)
})
