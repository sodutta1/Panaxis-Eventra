import { test, expect, type Page } from '@playwright/test'

async function login(page: Page, email = 'admin@eventflow.test') {
  const r = await page.request.post('http://127.0.0.1:8000/api/v1/auth/login', { data: { email, password: 'password' } })
  expect(r.ok()).toBeTruthy()
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); if (x.user) localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
  return s.token as string
}

async function seedQuotation(page: Page, token: string, note: string) {
  const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` }
  const clients = await (await page.request.get('http://127.0.0.1:8000/api/v1/clients', { headers })).json()
  const r = await page.request.post('http://127.0.0.1:8000/api/v1/quotations', {
    headers,
    data: { client_id: clients.data[0].id, issue_date: '2026-11-24', tax_rate: 18, notes: note, items: [{ item_name: 'Stage setup', quantity: 2, unit_price: 5000 }] },
  })
  expect(r.ok()).toBeTruthy()
  return (await r.json()).data
}

test('admin can edit a draft quotation from the row Edit icon', async ({ page }) => {
  const token = await login(page)
  const q = await seedQuotation(page, token, 'edit-flow-' + Date.now())
  await page.goto('/quotations')
  await page.getByLabel('Search', { exact: false }).first().fill(q.quotation_number).catch(() => {})
  const row = page.locator('tbody tr', { hasText: q.quotation_number }).first()
  await expect(row).toBeVisible()
  const edit = row.locator('.desktop-actions').getByLabel('Edit', { exact: true })
  await expect(edit).toBeVisible()
  await expect(edit).toHaveAttribute('title', 'Edit')
  await edit.click()
  await expect(page.getByText(`Editing quotation ${q.quotation_number}`)).toBeVisible()
  await expect(page.getByLabel('Item name').first()).toHaveValue('Stage setup')
  await page.getByLabel('Item name').first().fill('Stage and truss')
  await page.getByLabel('Qty').first().fill('3')
  await page.getByRole('button', { name: 'Save changes' }).click()
  await expect(page.getByText(`Editing quotation`)).toBeHidden()
  const after = await (await page.request.get(`http://127.0.0.1:8000/api/v1/quotations/${q.id}`, { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } })).json()
  expect(after.data.items[0].item_name).toBe('Stage and truss')
  expect(Number(after.data.subtotal)).toBe(15000)
  expect(Number(after.data.grand_total)).toBe(17700)
})

test('accepted quotations show a disabled Edit icon with a reason', async ({ page }) => {
  await login(page)
  await page.goto('/quotations?status=accepted')
  await page.waitForLoadState('networkidle')
  const first = page.locator('tbody tr').first()
  if (await first.locator('td').count() < 2) test.skip(true, 'no accepted quotation in demo data')
  const edit = first.locator('.desktop-actions').getByLabel('Edit', { exact: true })
  await expect(edit).toBeDisabled()
  await expect(edit).toHaveAttribute('title', /can't be edited/)
})

test('non-admin roles do not see Edit on quotations', async ({ page }) => {
  const admin = await login(page)
  await seedQuotation(page, admin, 'role-check')
  const r = await page.request.get('http://127.0.0.1:8000/api/v1/admin/users', { headers: { Accept: 'application/json', Authorization: `Bearer ${admin}` } })
  test.skip(!r.ok(), 'users list unavailable')
  const users = (await r.json()).data as { email: string; role: string }[]
  const sales = users.find((u) => u.role === 'sales_executive' || u.role === 'event_manager')
  test.skip(!sales, 'no non-admin user')
  await login(page, sales!.email)
  await page.goto('/quotations')
  await page.waitForSelector('tbody tr')
  await expect(page.locator('tbody').getByLabel('Edit', { exact: true })).toHaveCount(0)
})
