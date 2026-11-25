import { chromium } from '@playwright/test'
import fs from 'node:fs'
const BASE = 'http://127.0.0.1:5173', API = 'http://127.0.0.1:8000/api/v1', OUT = process.argv[2] || '/tmp/qr-demo'
fs.rmSync(OUT, { recursive: true, force: true }); fs.mkdirSync(OUT, { recursive: true })
const browser = await chromium.launch(); const _g = null
const mk = (u) => browser.newContext({ viewport: { width: 1280, height: 760 }, recordVideo: { dir: OUT + '/' + u, size: { width: 1280, height: 760 } } })
async function login(ctx, email) {
  const page = await ctx.newPage()
  const r = await (await page.request.post(API + '/auth/login', { data: { email, password: 'password' } })).json()
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded', timeout: 60000 })
  await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, r)
  return { page, token: r.token }
}
const staffCtx = await mk('staff'), clientCtx = await mk('client')
const { page: staff, token } = await login(staffCtx, 'admin@eventflow.test')
const h = { Accept: 'application/json', Authorization: 'Bearer ' + token }
const clients = await (await staff.request.get(API + '/clients?per_page=100', { headers: h })).json()
const c = clients.data.find((x) => x.client_code === 'CL-1001')
const q = (await (await staff.request.post(API + '/quotations', { headers: h, data: { client_id: c.id, issue_date: '2026-11-25', tax_rate: 18, notes: 'Demo: wedding sound and lighting', items: [{ item_name: 'Sound and lighting package', quantity: 1, unit_price: 185000 }] } })).json()).data
await staff.goto(BASE + '/quotations', { waitUntil: 'domcontentloaded' }); await staff.waitForTimeout(1500)
const row = staff.locator('tbody tr', { hasText: q.quotation_number }).first()
await row.scrollIntoViewIfNeeded(); await staff.waitForTimeout(1200)
await row.getByRole('button', { name: 'Send to client' }).click(); await staff.waitForTimeout(1800)
const { page: cl } = await login(clientCtx, 'client@eventflow.test')
await cl.goto(BASE + '/portal/client?section=quotations', { waitUntil: 'domcontentloaded' }); await cl.waitForTimeout(2500)
const crow = cl.locator('tr', { hasText: q.quotation_number }).first()
await crow.getByRole('button', { name: 'Accept' }).click(); await cl.waitForTimeout(1200)
await cl.getByLabel('Note for the event team').fill('Looks good, please go ahead'); await cl.waitForTimeout(1200)
await cl.getByRole('button', { name: 'Confirm accept' }).click(); await cl.waitForTimeout(2200)
await staff.goto(BASE + '/quotations', { waitUntil: 'domcontentloaded' }); await staff.waitForTimeout(2000)
await staff.getByRole('button', { name: /Notifications/ }).click(); await staff.waitForTimeout(3000)
await staffCtx.close(); await clientCtx.close(); await browser.close()
console.log(q.quotation_number)
