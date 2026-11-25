import { expect, test, type Page, type APIRequestContext } from '@playwright/test'

// Notification centre cases. Run on desktop and on the phone project (iPhone 13).
const API = 'http://127.0.0.1:8000/api/v1'
const H = { Accept: 'application/json', 'Content-Type': 'application/json' }

async function token(request: APIRequestContext, email: string) {
  const r = await request.post(`${API}/auth/login`, { headers: H, data: { email, password: 'password' } })
  return (await r.json()).token as string
}
async function auth(request: APIRequestContext, email: string) { return { ...H, Authorization: `Bearer ${await token(request, email)}` } }

async function login(page: Page, email: string) {
  await page.goto('/login')
  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', 'password')
  await page.getByRole('button', { name: /sign in/i }).click()
  await page.waitForURL(u => !u.pathname.includes('/login'), { timeout: 20_000 })
}
async function recordPayment(request: APIRequestContext) {
  const h = await auth(request, 'finance@eventflow.test')
  const list = await (await request.get(`${API}/invoices?per_page=100`, { headers: h })).json()
  const inv = list.data.find((i: any) => Number(i.balance_due) >= 50 && i.status !== 'paid')
  expect(inv, 'an invoice with balance').toBeTruthy()
  const r = await request.post(`${API}/invoices/${inv.id}/payments`, { headers: h, data: { amount: 50, payment_method: 'cash', payment_date: new Date().toISOString().slice(0, 10) } })
  expect(r.status()).toBe(201)
}
async function noPageOverflow(page: Page) {
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1)
}

test.describe('notification centre', () => {
  test('bell shows unread count after a payment is recorded, opens the record and counts down', async ({ page, request }) => {
    await login(page, 'admin@eventflow.test')
    await page.request.post(`${API}/notifications/read-all`, { headers: await auth(request, 'admin@eventflow.test') })
    await recordPayment(request)
    await page.reload()
    const badge = page.getByTestId('notice-badge')
    await expect(badge).toBeVisible()
    const before = Number(await badge.innerText())
    await page.getByRole('button', { name: /^Notifications/ }).click()
    await expect(page.getByRole('dialog', { name: 'Notifications', exact: true })).toBeVisible()
    await page.getByTestId('notice-row').filter({ hasText: 'Payment received' }).first().click()
    await expect(page).toHaveURL(/\/invoices/)
    if (before > 1) await expect(page.getByTestId('notice-badge')).toHaveText(String(before - 1))
    else await expect(page.getByTestId('notice-badge')).toHaveCount(0)
  })

  test('mark all read clears the badge', async ({ page, request }) => {
    await recordPayment(request)
    await login(page, 'admin@eventflow.test')
    await page.getByRole('button', { name: /^Notifications/ }).click()
    await page.getByRole('button', { name: 'Mark all read' }).first().click()
    await expect(page.getByTestId('notice-badge')).toHaveCount(0)
    await expect(page.getByRole('button', { name: 'Notifications', exact: true })).toBeVisible()
  })

  test('sales exec is not told about payments (role scoping)', async ({ page, request }) => {
    await recordPayment(request)
    const h = await auth(request, 'sales@eventflow.test')
    const r = await (await request.get(`${API}/notifications?per_page=50`, { headers: h })).json()
    expect(r.data.filter((n: any) => n.type === 'payment_recorded')).toHaveLength(0)
    await login(page, 'sales@eventflow.test')
    await page.getByRole('button', { name: /^Notifications/ }).click()
    await expect(page.getByText(/Payment received/)).toHaveCount(0)
  })

  test('empty state for a user with nothing yet (client portal)', async ({ page }) => {
    await login(page, 'client@eventflow.test')
    await page.getByRole('button', { name: /^Notifications/ }).click()
    await expect(page.getByText('You are all caught up.')).toBeVisible()
  })

  test('full page lists notifications, unread filter works, no sideways scroll', async ({ page, request }) => {
    await recordPayment(request)
    await login(page, 'admin@eventflow.test')
    await page.goto('/notifications')
    await expect(page.getByRole('heading', { name: 'Notifications', exact: true })).toBeVisible()
    await expect(page.getByTestId('notice-row').first()).toBeVisible()
    await page.getByRole('button', { name: 'Mark all read' }).first().click()
    await page.getByRole('button', { name: 'Unread only' }).click()
    await expect(page.getByText('No unread notifications.')).toBeVisible()
    await noPageOverflow(page)
  })

  test('shows a retry message when the network fails, then recovers', async ({ page }) => {
    await login(page, 'admin@eventflow.test')
    await page.route('**/api/v1/notifications?*', r => r.abort())
    await page.getByRole('button', { name: /^Notifications/ }).click()
    await expect(page.getByText('Could not load notifications.')).toBeVisible()
    await page.unroute('**/api/v1/notifications?*')
    await page.getByRole('button', { name: 'Retry' }).click()
    await expect(page.getByText('Could not load notifications.')).toHaveCount(0)
  })

  test('keyboard: Escape closes the panel; bell has an accessible name', async ({ page }) => {
    await login(page, 'admin@eventflow.test')
    const bell = page.getByRole('button', { name: /^Notifications/ })
    await bell.click()
    await page.keyboard.press('Escape')
    await expect(page.getByRole('dialog', { name: 'Notifications' })).toHaveCount(0)
  })

  test('phone: panel is full screen with 44px rows and taps navigate', async ({ page, request }, info) => {
    test.skip(info.project.name !== 'mobile', 'phone only')
    await recordPayment(request)
    await login(page, 'admin@eventflow.test')
    await page.getByRole('button', { name: /^Notifications/ }).click()
    const panel = page.getByRole('dialog', { name: 'Notifications' })
    const box = (await panel.boundingBox())!
    expect(box.width).toBeGreaterThanOrEqual(388)
    expect(box.height).toBeGreaterThanOrEqual(page.viewportSize()!.height - 2)
    const row = page.getByTestId('notice-row').first()
    expect((await row.boundingBox())!.height).toBeGreaterThanOrEqual(44)
    expect((await page.getByRole('button', { name: /^Notifications/ }).boundingBox())!.width).toBeGreaterThanOrEqual(44)
    await noPageOverflow(page)
    await page.screenshot({ path: '../docs/screenshots/notifications-390.png' })
    await row.click()
    await expect(panel).toHaveCount(0)
  })
})
