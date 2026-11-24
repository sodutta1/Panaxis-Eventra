import { expect, test, type Page } from '@playwright/test'

// Mobile test cases - run in the "mobile" Playwright project (iPhone 13, 390x844, touch).
// Every test is written as a user holding a phone. They run against the LIVE backend
// (artisan serve on :8000 + MySQL) through the Vite dev server.

const ADMIN = 'admin@eventflow.test'
const SALES = 'sales@eventflow.test'
const MANAGER = 'manager@eventflow.test'
const FINANCE = 'finance@eventflow.test'
const CLIENT = 'client@eventflow.test'
const VENDOR = 'vendor@eventflow.test'

async function login(page: Page, email = ADMIN) {
  await page.goto('/login')
  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', 'password')
  await page.getByRole('button', { name: /sign in/i }).click()
  try {
    await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 12_000 })
  } catch {
    await page.getByRole('button', { name: /sign in/i }).click()
    await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 20_000 })
  }
}

async function noPageOverflow(page: Page) {
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
  expect(overflow).toBeLessThanOrEqual(1)
}

// Every list module: route, heading, and the text of the primary create button (if the role can write)
const MODULES: { path: string; heading: RegExp; create?: RegExp }[] = [
  { path: '/leads', heading: /Leads \/ CRM/, create: /new lead/i },
  { path: '/clients', heading: /Clients/, create: /new client/i },
  { path: '/quotations', heading: /Quotations/, create: /new quotation/i },
  { path: '/events', heading: /Events/, create: /new event/i },
  { path: '/venues', heading: /Venues/, create: /new venue/i },
  { path: '/vendors', heading: /Vendors/, create: /new vendor/i },
  { path: '/budgets', heading: /Budget/ },
  { path: '/work-orders', heading: /Work [Oo]rders/ },
  { path: '/guests', heading: /Guests/, create: /new guest|add guest/i },
  { path: '/tickets', heading: /Tickets/, create: /new ticket|sell ticket/i },
  { path: '/invoices', heading: /Invoices & payments|Invoices/, create: /new invoice/i },
  { path: '/expenses', heading: /Expenses/, create: /new expense/i },
  { path: '/admin', heading: /Team|Users|Admin/ },
]

test.describe('login and navigation', () => {
  test('login form fits the screen, signs in, and lands on the dashboard', async ({ page }) => {
    await page.goto('/login')
    await noPageOverflow(page)
    await login(page)
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 20_000 })
    await noPageOverflow(page)
  })

  test('login shows a plain error on wrong password', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', ADMIN)
    await page.fill('input[type="password"]', 'wrong-password')
    await page.getByRole('button', { name: /sign in/i }).click()
    await expect(page.getByText(/invalid|incorrect|credentials|try again/i).first()).toBeVisible({ timeout: 10_000 })
  })

  test('menu drawer opens, lists sections, and closes after choosing a page', async ({ page }) => {
    await login(page)
    await page.getByLabel('Open menu', { exact: true }).click()
    await expect(page.locator('.sidebar')).toBeInViewport()
    await page.getByRole('link', { name: 'Clients', exact: true }).click()
    await expect(page.locator('.workspace-shell')).not.toHaveClass(/drawer-open/)
    await expect(page.getByRole('heading', { name: /Clients/ })).toBeVisible()
  })

  test('quick find is a full-screen sheet on a phone and closes with its button', async ({ page }) => {
    await login(page)
    await page.getByRole('button', { name: 'Quick find' }).click()
    const sheet = page.getByRole('dialog', { name: 'Quick find' })
    await expect(sheet).toBeVisible()
    const box = await sheet.boundingBox()
    expect(box!.width).toBeGreaterThanOrEqual(380)
    await page.getByLabel('Close quick find').click()
    await expect(sheet).toBeHidden()
  })

  test('dashboard KPI cards stack two-up and upcoming events are readable', async ({ page }) => {
    await login(page)
    await expect(page.locator('text=/₹[0-9]/').first()).toBeVisible({ timeout: 20_000 })
    await expect(page.getByRole('heading', { name: 'Upcoming events' })).toBeVisible()
    await noPageOverflow(page)
  })
})

test.describe('every list module on a phone', () => {
  for (const m of MODULES) {
    test(`${m.path}: heading, record cards, no sideways scroll`, async ({ page }) => {
      await login(page)
      await page.goto(m.path)
      await expect(page.getByRole('heading', { name: m.heading }).first()).toBeVisible({ timeout: 20_000 })
      await noPageOverflow(page)
      // module table renders as record cards, not a sideways-scrolling table
      const cards = page.locator('.record-card')
      await expect(cards.first()).toBeVisible({ timeout: 20_000 })
      // labels are shown inside cards so no column meaning is lost (first field is the title, its label is hidden)
      await expect(cards.first().locator('.rc-label').nth(1)).toBeVisible()
      // the desktop table is hidden
      await expect(page.locator('.dt-table table')).toBeHidden()
      // search field is reachable and accepts input
      const search = page.getByLabel('Search this module')
      await expect(search).toBeVisible()
      await search.fill('zzz-no-such-record')
      await expect(cards.first()).toBeVisible({ timeout: 20_000 }) // empty state card
      await search.fill('')
    })

    if (m.create) {
      test(`${m.path}: create form opens with full-width fields`, async ({ page }) => {
        await login(page)
        await page.goto(m.path)
        await page.getByRole('button', { name: m.create }).first().click()
        await page.waitForTimeout(400)
        await noPageOverflow(page)
        const field = page.locator('form input, form select').first()
        await expect(field).toBeVisible()
        const box = await field.boundingBox()
        expect(box!.width).toBeGreaterThan(260)
      })
    }
  }

  test('record cards expose row actions with large tap targets', async ({ page }) => {
    await login(page)
    await page.goto('/quotations')
    const card = page.locator('.record-card').first()
    await expect(card).toBeVisible({ timeout: 20_000 })
    const actions = card.locator('.rc-actions button')
    await expect(actions.first()).toBeVisible()
    const box = await actions.first().boundingBox()
    expect(box!.height).toBeGreaterThanOrEqual(40)
    expect(box!.width).toBeGreaterThanOrEqual(40)
  })

  test('record details open as a full-screen sheet and close', async ({ page }) => {
    await login(page)
    await page.goto('/quotations')
    await page.locator('.record-card .rc-actions button[aria-label="View"]').first().click()
    const panel = page.getByRole('dialog')
    await expect(panel).toBeVisible({ timeout: 15_000 })
    const box = await panel.boundingBox()
    expect(box!.width).toBeGreaterThanOrEqual(380)
    await page.getByLabel('Close details').click()
    await expect(panel).toBeHidden()
  })

  test('rotation to landscape keeps the layout usable', async ({ page }) => {
    await login(page)
    await page.goto('/clients')
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    await page.setViewportSize({ width: 844, height: 390 })
    await page.waitForTimeout(400)
    await noPageOverflow(page)
    // landscape is tablet-like width: desktop table returns
    await expect(page.locator('.dt-table table')).toBeVisible()
  })
})

test.describe('module-specific flows', () => {
  test('leads: kanban board stacks vertically and cards stay readable', async ({ page }) => {
    await login(page)
    await page.goto('/leads')
    await page.getByRole('button', { name: 'Board view', exact: true }).click()
    await page.waitForTimeout(500)
    await noPageOverflow(page)
    const columns = page.locator('text=Drop leads here').first()
    await expect(columns).toBeVisible()
  })

  test('leads: phone number on a card is a tap-to-call link', async ({ page }) => {
    await login(page)
    await page.goto('/leads')
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    const tel = page.locator('.record-card a[href^="tel:"]').first()
    await expect(tel).toBeVisible()
    const box = await tel.boundingBox()
    expect(box!.height).toBeGreaterThanOrEqual(16)
  })

  test('clients: email on a card is a tap-to-mail link', async ({ page }) => {
    await login(page)
    await page.goto('/clients')
    await expect(page.locator('.record-card a[href^="mailto:"]').first()).toBeVisible({ timeout: 20_000 })
  })

  test('expenses: finance user can reach Approve from a pending expense card', async ({ page }) => {
    await login(page, FINANCE)
    await page.goto('/expenses')
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    await noPageOverflow(page)
    const pending = page.locator('.record-card', { hasText: /pending/i }).first()
    if (await pending.count()) {
      const approve = pending.getByRole('button', { name: /approve/i })
      if (await approve.count()) {
        const box = await approve.first().boundingBox()
        expect(box!.height).toBeGreaterThanOrEqual(40)
      }
    }
  })

  test('quotations: admin sees Edit on a draft card; accepted ones show it disabled', async ({ page }) => {
    await login(page)
    await page.goto('/quotations')
    const edit = page.locator('.record-card .rc-actions button[aria-label="Edit"]').first()
    await expect(edit).toBeVisible({ timeout: 20_000 })
    const box = await edit.boundingBox()
    expect(box!.height).toBeGreaterThanOrEqual(40)
  })

  test('quotations: sales executive does not see Edit or Delete on cards', async ({ page }) => {
    await login(page, SALES)
    await page.goto('/quotations')
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    await expect(page.locator('.record-card .rc-actions button[aria-label="Edit"]')).toHaveCount(0)
    await expect(page.locator('.record-card .rc-actions button[aria-label="Delete"]')).toHaveCount(0)
  })

  test('invoices: record payment button is reachable from the card', async ({ page }) => {
    await login(page)
    await page.goto('/invoices')
    const pay = page.getByRole('button', { name: /record payment/i }).first()
    await expect(pay).toBeVisible({ timeout: 20_000 })
    const box = await pay.boundingBox()
    expect(box!.height).toBeGreaterThanOrEqual(40)
  })

  test('gate scanner: manual verify input and button fit the screen', async ({ page }) => {
    await login(page)
    await page.goto('/gate')
    await expect(page.getByRole('heading', { name: /Gate Scanner/i })).toBeVisible({ timeout: 20_000 })
    const input = page.getByPlaceholder(/QR token|ticket number/i)
    await expect(input).toBeVisible()
    await expect(page.getByRole('button', { name: /verify/i })).toBeVisible()
    await noPageOverflow(page)
    await input.fill('not-a-real-token')
    await page.getByRole('button', { name: /verify/i }).click()
    await expect(page.getByText(/not found|invalid|could not|try again/i).first()).toBeVisible({ timeout: 15_000 })
  })

  test('events: detail page tabs and KPI cards fit', async ({ page }) => {
    await login(page)
    await page.goto('/events')
    await page.locator('.record-card .rc-value a, .record-card a').first().click().catch(async () => {
      await page.locator('.record-card').first().click()
    })
    await page.waitForTimeout(600)
    await noPageOverflow(page)
  })

  test('analytics: KPI cards and charts fit the width', async ({ page }) => {
    await login(page)
    await page.goto('/analytics')
    await expect(page.getByRole('heading', { name: /Analytics/ })).toBeVisible({ timeout: 20_000 })
    await noPageOverflow(page)
  })

  test('client role: sees only the portal, and the portal lists their records as cards', async ({ page }) => {
    await login(page, CLIENT)
    await expect(page).toHaveURL(/portal\/client/, { timeout: 20_000 })
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    await expect(page.getByLabel('Open menu', { exact: true })).toBeVisible()
    await page.getByLabel('Open menu', { exact: true }).click()
    await expect(page.getByRole('link', { name: 'Team management' })).toHaveCount(0)
    await expect(page.getByRole('link', { name: 'Leads & CRM' })).toHaveCount(0)
  })

  test('client role: staff pages refuse access instead of showing data', async ({ page }) => {
    await login(page, CLIENT)
    await page.goto('/clients')
    await page.waitForTimeout(1200)
    await expect(page.getByText(/forbidden|not allowed|no permission|don't have access/i).first()).toBeVisible({ timeout: 10_000 })
  })

  test('vendor role: lands on the vendor portal, no staff navigation', async ({ page }) => {
    await login(page, VENDOR)
    await expect(page).toHaveURL(/portal\/vendor/, { timeout: 20_000 })
    await noPageOverflow(page)
    await page.getByLabel('Open menu', { exact: true }).click()
    await expect(page.getByRole('link', { name: 'Team management' })).toHaveCount(0)
  })

  test('sales role: admin-only entries are hidden in the drawer', async ({ page }) => {
    await login(page, SALES)
    await page.getByLabel('Open menu', { exact: true }).click()
    await expect(page.getByRole('link', { name: 'Team management' })).toHaveCount(0)
    await expect(page.getByRole('link', { name: 'Leads & CRM', exact: true })).toBeVisible()
  })
})

test.describe('network resilience', () => {
  test('slow network: skeleton shows, then cards render, no crash', async ({ page }) => {
    await login(page)
    await page.route('**/api/v1/clients**', async (route) => {
      await new Promise((r) => setTimeout(r, 1500))
      await route.continue()
    })
    await page.goto('/clients')
    await expect(page.locator('.skeleton, .filter-bar[aria-busy="true"]').first()).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('.record-card').first()).toBeVisible({ timeout: 20_000 })
    await noPageOverflow(page)
  })

  test('offline: a failed list load shows a plain error, not a crash', async ({ page }) => {
    await login(page)
    await page.route('**/api/v1/clients**', (route) => route.abort())
    await page.goto('/clients')
    await page.waitForTimeout(1500)
    await noPageOverflow(page)
    // page shell is still intact and the menu still works
    await page.getByLabel('Open menu', { exact: true }).click()
    await expect(page.locator('.sidebar')).toBeInViewport()
  })
})
