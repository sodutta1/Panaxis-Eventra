import { chromium } from '@playwright/test'
import fs from 'node:fs'
const BASE = 'http://127.0.0.1:5173', API = 'http://127.0.0.1:8000/api/v1', OUT = process.argv[2] || '/tmp/undo-demo'
fs.rmSync(OUT, { recursive: true, force: true }); fs.mkdirSync(OUT, { recursive: true })
const browser = await chromium.launch()
const ctx = await browser.newContext({ viewport: { width: 1280, height: 760 }, recordVideo: { dir: OUT, size: { width: 1280, height: 760 } } })
const page = await ctx.newPage()
const r = await (await page.request.post(API + '/auth/login', { data: { email: 'admin@eventflow.test', password: 'password' } })).json()
const h = { Accept: 'application/json', Authorization: 'Bearer ' + r.token }
await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' })
await page.evaluate((x) => { localStorage.setItem('ef_token', x.token); localStorage.setItem('ef_user', JSON.stringify(x.user)) }, r)
const clients = await (await page.request.get(API + '/clients?per_page=100', { headers: h })).json()
const mk = async (note) => (await (await page.request.post(API + '/quotations', { headers: h, data: { client_id: clients.data[0].id, issue_date: '2026-11-25', tax_rate: 18, notes: note, items: [{ item_name: 'Stage and truss', quantity: 1, unit_price: 64000 }] } })).json()).data
const q = await mk('demo')
await page.goto(BASE + '/quotations', { waitUntil: 'domcontentloaded' })
const row = page.locator('tbody tr', { hasText: q.quotation_number }).first()
await row.waitFor({ timeout: 60000 }); await page.waitForTimeout(1500)
page.once('dialog', (d) => d.accept())
await row.getByRole('button', { name: 'Delete' }).first().click(); await page.waitForTimeout(2500)
await page.getByRole('status').getByRole('button', { name: 'Undo' }).click(); await page.waitForTimeout(2500)
await row.waitFor({ timeout: 20000 }); await page.waitForTimeout(2000)
// sent quotation cannot be deleted even by the API
const sent = await mk('sent demo'); await page.request.put(API + '/quotations/' + sent.id, { headers: h, data: { client_id: clients.data[0].id, issue_date: '2026-11-25', tax_rate: 18, items: [{ item_name: 'x', quantity: 1, unit_price: 1 }] } })
await ctx.close(); await browser.close(); console.log(q.quotation_number)
