import { expect, test, type Page } from '@playwright/test'

// These tests run against the LIVE backend (artisan serve on :8000 + MySQL) through
// the Vite dev server. They validate the frontend-backend integration end to end.

async function login(page: Page) {
  await page.goto('/login')
  await page.fill('input[type="email"]', 'admin@eventflow.test')
  await page.fill('input[type="password"]', 'password')
  const submit = page.getByRole('button', { name: /sign in/i })
  await submit.click()
  try {
    await page.waitForURL('**/dashboard', { timeout: 12_000 })
  } catch {
    // occasionally the first submit races the dev server - resubmit
    await page.getByRole('button', { name: /sign in/i }).click()
    await page.waitForURL('**/dashboard', { timeout: 20_000 })
  }
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 20_000 })
}

test('login lands on a dashboard populated from the API', async ({ page }) => {
  await login(page)
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible()
  // KPI cards render ₹ figures - proof the /api/v1/dashboard call succeeded
  await expect(page.locator('text=/₹[0-9]/').first()).toBeVisible()
  // Upcoming events table has at least one seeded event row from the API
  await expect(page.getByRole('heading', { name: 'Upcoming events' })).toBeVisible()
  const rows = page.locator('table tbody tr')
  await expect(rows.first()).toBeVisible()
  expect(await rows.count()).toBeGreaterThan(0)
})

test('leads kanban drag moves a card between stages via the API', async ({ page }) => {
  await login(page)
  await page.goto('/leads')
  await expect(page.getByRole('heading', { name: /Leads/ })).toBeVisible()
  await page.waitForTimeout(1000)

  const columns = page.locator('div.min-w-\\[230px\\]')
  const countOf = async (i: number) =>
    Number(await columns.nth(i).locator('span.rounded-full').first().innerText())

  // seed a fresh lead in the first column so the drag is deterministic
  const dragTitle = `E2E Drag ${Date.now() % 100000}`
  await page.getByRole('button', { name: '+ New lead' }).click()
  await page.fill('input[placeholder="Lead title"]', dragTitle)
  await page.fill('input[placeholder="Contact name"]', 'Drag Contact')
  await page.getByRole('button', { name: 'Create lead' }).click()
  await expect(page.locator(`text=${dragTitle}`)).toBeVisible({ timeout: 8000 })

  // drag the first card of the first non-empty column to the next stage
  let src = -1
  for (let i = 0; i < (await columns.count()) - 1; i++) {
    if ((await countOf(i)) > 0) { src = i; break }
  }
  test.skip(src === -1, 'no draggable leads on the board')

  const beforeSrc = await countOf(src)
  const beforeDst = await countOf(src + 1)
  const card = columns.nth(src).locator('div[draggable="true"]').first()
  const title = await card.locator('div.font-semibold').innerText()

  await card.hover()
  await page.mouse.down()
  const target = columns.nth(src + 1).locator('div.min-h-\\[120px\\]')
  const box = await target.boundingBox()
  await page.mouse.move((box?.x ?? 300) + 115, (box?.y ?? 300) + 30, { steps: 15 })
  await page.mouse.up()

  await page.waitForTimeout(1500)
  await expect.poll(() => countOf(src), { timeout: 10_000 }).toBe(beforeSrc - 1)
  await expect.poll(() => countOf(src + 1), { timeout: 10_000 }).toBe(beforeDst + 1)
  await expect(page.locator(`div[draggable="true"]:has-text("${title}")`)).toBeVisible()
})

test('invoice PDF downloads a real PDF document', async ({ page }) => {
  await login(page)
  await page.goto('/invoices')
  await expect(page.getByRole('heading', { name: /Invoices/ })).toBeVisible()
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByRole('button', { name: 'PDF' }).first().click(),
  ])
  expect(download.suggestedFilename()).toMatch(/^INV-.*\.pdf$/)
  const path = await download.path()
  const fs = await import('node:fs')
  const buf = fs.readFileSync(path!)
  expect(buf.subarray(0, 5).toString()).toBe('%PDF-')
})

test('gate verification accepts a real ticket token', async ({ page, request }) => {
  await login(page)

  // Grab a real qr_token straight from the API, using the UI's bearer token.
  const token = await page.evaluate(() => localStorage.getItem('ef_token'))
  const res = await request.get('http://127.0.0.1:8000/api/v1/tickets', {
    headers: { Authorization: `Bearer ${token}` },
  })
  const tickets = (await res.json()).data
  const valid = tickets.find((t: { ticket_status: string }) => t.ticket_status === 'valid') ?? tickets[0]
  expect(valid).toBeTruthy()

  await page.goto('/tickets')
  await page.fill('input[placeholder*="QR token"]', valid.qr_token)
  await page.getByRole('button', { name: 'Verify at gate' }).click()
  // first scan: success; repeat runs: warning (already checked in) - both are business results
  await expect(page.locator('div.rounded-lg.border').first()).toBeVisible()
  await expect(page.locator('div.rounded-lg.border').first()).toContainText(/valid pass|admitted|already|checked in/i)
})
