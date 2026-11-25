import { test, expect, type Page } from '@playwright/test'

const API = 'http://127.0.0.1:8000/api/v1'

async function login(page: Page) {
  const r = await page.request.post(`${API}/auth/login`, { data: { email: 'admin@eventflow.test', password: 'password' } })
  const s = await r.json()
  await page.goto('/login')
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, s)
}

test('dashboard lists can be searched and sorted', async ({ page }) => {
  await login(page)
  await page.goto('/dashboard')
  await expect(page.getByRole('heading', { name: 'Upcoming events' })).toBeVisible({ timeout: 30000 })

  // sort: clicking a header toggles aria-sort
  const eventHeader = page.getByRole('columnheader', { name: /^Event/ }).first()
  await eventHeader.getByRole('button').click()
  await expect(eventHeader).toHaveAttribute('aria-sort', 'ascending')
  await eventHeader.getByRole('button').click()
  await expect(eventHeader).toHaveAttribute('aria-sort', 'descending')

  // search narrows to nothing, then clears
  const box = page.getByLabel('Search upcoming events')
  await box.fill('zzzz-no-such-event')
  await expect(page.getByText('No events match your search')).toBeVisible()
  await box.fill('')
  await expect(page.getByText('No events match your search')).toHaveCount(0)

  const taskBox = page.getByLabel('Search tasks')
  await taskBox.fill('zzzz-no-such-task')
  await expect(page.getByText('No tasks match your search')).toBeVisible()
  await taskBox.fill('')
  await expect(page.getByText('No tasks match your search')).toHaveCount(0)
})
