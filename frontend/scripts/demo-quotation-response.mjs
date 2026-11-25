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
const mkQ = async (note, price) => (await (await staff.request.post(API + '/quotations', { headers: h, data: { client_id: c.id, issue_date: '2026-11-25', tax_rate: 18, notes: note, items: [{ item_name: 'Sound and lighting package', quantity: 1, unit_price: price }] } })).json()).data
const q1 = await mkQ('Demo: client uses the portal', 185000)
const q2 = await mkQ('Demo: client agreed by phone', 92000)
const go = async (page, path) => { await page.goto(BASE + path, { waitUntil: 'domcontentloaded', timeout: 90000 }) }
// 1. staff sends q1
await go(staff, '/quotations')
const r1 = staff.locator('tbody tr', { hasText: q1.quotation_number }).first()
await r1.waitFor({ timeout: 90000 }); await r1.scrollIntoViewIfNeeded(); await staff.waitForTimeout(1200)
await r1.getByRole('button', { name: 'Send to client' }).click(); await staff.waitForTimeout(1800)
// 2. client accepts in portal
const { page: cl } = await login(clientCtx, 'client@eventflow.test')
await go(cl, '/portal/client?section=quotations')
const cr = cl.locator('tr', { hasText: q1.quotation_number }).first()
await cr.waitFor({ timeout: 90000 }); await cl.waitForTimeout(1500)
await cr.getByRole('button', { name: 'Accept' }).click(); await cl.waitForTimeout(1200)
await cl.getByLabel('Note for the event team').fill('Looks good, please go ahead'); await cl.waitForTimeout(1200)
await cl.getByRole('button', { name: 'Confirm accept' }).click(); await cl.waitForTimeout(2500)
// 3. staff records q2 verbally
await go(staff, '/quotations')
const r2 = staff.locator('tbody tr', { hasText: q2.quotation_number }).first()
await r2.waitFor({ timeout: 90000 }); await r2.scrollIntoViewIfNeeded(); await staff.waitForTimeout(1200)
await r2.getByRole('button', { name: 'Record client response' }).click(); await staff.waitForTimeout(1500)
await staff.getByRole('combobox', { name: "Client's answer" }).selectOption('accepted'); await staff.waitForTimeout(800)
await staff.getByRole('combobox', { name: 'How did they tell you' }).selectOption('phone'); await staff.waitForTimeout(800)
await staff.getByRole('textbox', { name: "Note about the client's answer" }).fill('Client agreed on a call, village has no internet'); await staff.waitForTimeout(1500)
await staff.getByRole('button', { name: 'Save response' }).click(); await staff.waitForTimeout(2500)
// 4. trail + notifications
await staff.locator('tbody tr', { hasText: q1.quotation_number }).first().scrollIntoViewIfNeeded(); await staff.waitForTimeout(2500)
await staff.getByRole('button', { name: /Notifications/ }).click(); await staff.waitForTimeout(3500)
await staffCtx.close(); await clientCtx.close(); await browser.close()
console.log(q1.quotation_number, q2.quotation_number)
