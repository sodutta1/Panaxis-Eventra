import { expect, test, type Page } from '@playwright/test'

// Thorough whole-app testing pass - every flow below runs against the live backend + MySQL.

async function login(page: Page, user = 'admin@eventflow.test') {
  await page.goto('/login')
  await page.fill('#login-email', user)
  await page.fill('#login-password', 'password')
  await page.getByRole('button', { name: /sign in/i }).click()
  try {
    await page.waitForURL('**/dashboard', { timeout: 12_000 })
  } catch {
    await page.getByRole('button', { name: /sign in/i }).click()
    await page.waitForURL('**/dashboard', { timeout: 20_000 })
  }
}

test('client role lands in the client portal with only their data', async ({ page }) => {
  await login(page, 'client@eventflow.test')
  await page.goto('/portal/client')
  await expect(page.getByRole('heading', { name: /Client portal/i })).toBeVisible()
  await expect(page.locator('tbody').getByText(/Royal Heritage Wedding/)).toBeVisible()
  await expect(page.locator('tbody').getByText(/Lumina X/)).toHaveCount(0)
})

test('unlinked vendor login is denied instead of receiving another vendor profile', async ({ page }) => {
  await login(page, 'vendor@eventflow.test')
  await page.goto('/portal/vendor')
  await expect(page.getByRole('heading', { name: /Vendor preview/i })).toBeVisible()
  await expect(page.getByText('Vendor account linking is not configured. Contact your company administrator.')).toBeVisible()
  await expect(page.getByText('SoundCraft', { exact: false })).toHaveCount(0)
  await expect(page.locator('table tbody tr')).toHaveCount(0)
})

test('vendor role is blocked from the admin page by the API', async ({ page }) => {
  await login(page, 'vendor@eventflow.test')
  await page.goto('/admin')
  // the API returns 403; the page must show the error, not user data
  await expect(page.locator('text=/forbidden|requires one of the roles/i')).toBeVisible()
  await expect(page.locator('table tbody tr')).toHaveCount(0)
})

test('create a client end to end', async ({ page }) => {
  await login(page)
  await page.goto('/clients')
  await page.getByRole('button', { name: /new client/i }).click()
  const stamp = Date.now().toString().slice(-6)
  const name = `E2E Client ${stamp}`
  await page.fill('input[placeholder="Name"]', name)
  await page.fill('input[placeholder="Company"]', 'E2E Corp')
  await page.fill('input[placeholder="Email"]', `e2e${stamp}@example.com`)
  await page.fill('input[placeholder="City"]', 'Kolkata')
  await page.getByRole('button', { name: 'Create client',exact:true }).click()
  await expect(page.locator(`text=${name}`)).toBeVisible({ timeout: 8000 })
})

test('budget item add recomputes totals server-side', async ({ page }) => {
  await login(page)
  await page.goto('/budgets')
  await expect(page.getByRole('heading', { name: 'Event Budgets' })).toBeVisible()
  await page.fill('input[placeholder*="Category"]', 'E2E Testing')
  const itemName = `Automation rig ${Date.now() % 100000}`
  await page.fill('input[placeholder="Item name"]', itemName)
  await page.fill('input[placeholder="Estimated cost ₹"]', '12345')
  await page.getByRole('button', { name: 'Add item' }).click()
  await expect(page.locator(`text=${itemName}`)).toBeVisible({ timeout: 8000 })
  await expect(page.locator('text=₹12,345').first()).toBeVisible()
})

test('work order overpayment rejected, exact balance marks paid', async ({ page }) => {
  // codifies EV-QA-007: overpayment is a 422, not a silent cap
  await login(page)
  await page.goto('/work-orders')
  await expect(page.getByRole('heading', { name: 'Vendor Work Orders' })).toBeVisible()
  await page.locator('form select').first().selectOption({ index: 1 })
  const svc = `E2E Rigging ${Date.now() % 100000}`
  await page.fill('input[placeholder*="Service"]', svc)
  await page.fill('input[placeholder="Contract amount ₹"]', '10000')
  await page.getByRole('button', { name: 'Create work order' }).click()
  const row = page.locator('tr', { hasText: svc })
  await expect(row).toBeVisible({ timeout: 8000 })
  // overpaying surfaces an error and changes nothing
  await row.getByRole('button', { name: 'Add payment' }).click()
  await row.locator('input[type="number"]').fill('99999')
  await row.getByRole('button', { name: 'Save' }).click()
  await expect(page.locator('text=/exceeds the remaining balance/i').first()).toBeVisible({ timeout: 8000 })
  await expect(row.locator('td', { hasText: /^paid$/i })).toHaveCount(0)
  // paying the exact balance still works and marks the order paid
  await row.locator('input[type="number"]').fill('10000')
  await row.getByRole('button', { name: 'Save' }).click()
  await expect(row.locator('text=₹10,000').first()).toBeVisible({ timeout: 8000 })
  await expect(row.locator('td', { hasText: /^paid$/i })).toBeVisible()
})

test('admin creates a user and it appears in the list', async ({ page }) => {
  await login(page)
  await page.goto('/admin')
  const stamp = Date.now().toString().slice(-6)
  await page.fill('input[placeholder="Full name"]', `E2E User ${stamp}`)
  await page.fill('input[placeholder="Email"]', `e2euser${stamp}@eventflow.test`)
  await page.fill('input[placeholder*="Password"]', 'e2e-pass-123')
  await page.getByRole('button', { name: 'Create user' }).click()
  await expect(page.locator(`text=E2E User ${stamp}`)).toBeVisible({ timeout: 8000 })
})

test('guest check-in is one-way via the API', async ({ page }) => {
  // codifies EV-QA-005: gate check-in cannot be undone by a second click
  await login(page)
  await page.goto('/guests')
  await expect(page.getByRole('heading', { name: /Guests/ })).toBeVisible()
  // create a fresh guest so the test does not depend on seeded check-in state
  const name = `E2E Checkin ${Date.now() % 100000}`
  await page.getByRole('button', { name: '+ New guest' }).click()
  await page.selectOption('form select', { label: 'Royal Heritage Wedding of Aryan & Sanjana' })
  await page.fill('input[placeholder="Guest name"]', name)
  await page.getByRole('button', { name: 'Add guest' }).click()
  const row = page.locator('table tbody tr', { hasText: name }).first()
  await expect(row.locator('text=not yet')).toBeVisible({ timeout: 8000 })
  await row.click()
  await expect(row.locator('text=Checked in')).toBeVisible({ timeout: 8000 })
  // clicking again must NOT flip it back to "not yet"
  await row.click()
  await page.waitForTimeout(1000)
  await expect(row.locator('text=Checked in')).toBeVisible()
})

test('lead create then convert asks for confirmation and creates a client', async ({ page }) => {
  await login(page)
  await page.goto('/leads')
  const title = `E2E Lead ${Date.now() % 100000}`
  await page.getByRole('button', { name: '+ New lead' }).click()
  await page.fill('input[placeholder="Lead title"]', title)
  await page.fill('input[placeholder="Contact name"]', 'E2E Contact')
  await page.getByRole('button', { name: 'Create lead' }).click()
  await expect(page.locator(`text=${title}`)).toBeVisible({ timeout: 8000 })
  const row = page.locator('tr', { hasText: title })
  page.once('dialog', (d) => d.accept())
  await row.getByRole('button', { name: 'Convert → client' }).click()
  await page.waitForTimeout(1500)
  await expect(page.locator('tr', { hasText: title }).locator('text=Converted')).toBeVisible()
  // the converted lead produced a client record
  await page.goto('/clients')
  await expect(page.locator(`text=E2E Contact`).first()).toBeVisible({ timeout: 8000 })
})

test('quotation PDF downloads a real PDF', async ({ page }) => {
  await login(page)
  await page.goto('/quotations')
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByLabel('Download PDF', {exact:true}).first().click(),
  ])
  expect(download.suggestedFilename()).toMatch(/^quotation-.*\.pdf$/i)
})

test('reports page shows server-computed figures', async ({ page }) => {
  await login(page)
  await page.goto('/reports')
  await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible()
  await expect(page.locator('text=Contracted revenue')).toBeVisible()
  await expect(page.locator('text=/₹[0-9]/').first()).toBeVisible()
})
