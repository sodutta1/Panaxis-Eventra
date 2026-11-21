import { chromium } from 'playwright'
import { readFileSync, mkdirSync } from 'node:fs'
import { join } from 'node:path'
import { tmpdir } from 'node:os'
const evidenceDir = process.env.EVENTRA_EVIDENCE_DIR ?? join(tmpdir(), 'eventra-evidence')
mkdirSync(evidenceDir, { recursive: true })
const session = JSON.parse(readFileSync('/tmp/eventra-camera-session.json','utf8'))
const browser = await chromium.launch({ headless: true, args: ['--use-fake-device-for-media-stream', '--use-fake-ui-for-media-stream', '--use-file-for-fake-video-capture=/tmp/eventra-qr.y4m'] })
const context = await browser.newContext({ viewport:{width:1440,height:1000}, permissions: ['camera'] })
await context.addInitScript(({token,user}) => {
 localStorage.setItem('ef_token',token); localStorage.setItem('ef_user',JSON.stringify(user))
 delete window.BarcodeDetector
 const gum=navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices)
 window.__streams=[]
 navigator.mediaDevices.getUserMedia=async (...args)=>{const s=await gum(...args);window.__streams.push(s);return s}
},session)
const page=await context.newPage()
const errors=[];page.on('pageerror',e=>errors.push(e.message))
await page.goto('http://127.0.0.1:5173/gate')
await page.getByRole('button',{name:'Start camera',exact:true}).click()
try { await page.getByRole('button',{name:'Stop camera',exact:true}).waitFor({timeout:10000}) }
catch(e) { await page.screenshot({path:join(evidenceDir, 'scanner-start-failure.png'),fullPage:true}); console.log(await page.locator('body').innerText()); await browser.close(); throw e }
await page.getByText(/VALID PASS!|ALREADY ADMITTED!/).first().waitFor({timeout:20000})
await page.evaluate(() => { window.__cameraTracks = Array.from(document.querySelectorAll('video')).flatMap(v => v.srcObject?.getTracks?.() ?? []) })
await page.screenshot({path:join(evidenceDir, 'scanner-camera-success.png'),fullPage:true})
await page.getByRole('button',{name:'Stop camera',exact:true}).click()
await page.getByRole('button',{name:'Start camera',exact:true}).waitFor()
const tracks=await page.evaluate(()=>window.__cameraTracks.map(t=>t.readyState))
if(!tracks.length || tracks.some(s=>s!=='ended')) throw Error('Camera tracks did not stop: '+JSON.stringify(tracks))
await page.waitForTimeout(3100)
await page.getByPlaceholder('Or paste QR token / ticket number').fill(session.qr)
await page.getByRole('button',{name:'Verify',exact:true}).click()
await page.getByText(/ALREADY ADMITTED!/).first().waitFor()
await page.setViewportSize({width:360,height:900})
await page.waitForFunction(() => document.querySelector('aside').getBoundingClientRect().right <= 0)
await page.screenshot({path:join(evidenceDir, 'scanner-mobile.png'),fullPage:true})
const overflow=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,width:innerWidth}))
console.log('camera QR decode/admission succeeded; native BarcodeDetector disabled; stopped tracks:',tracks,'mobile widths:',overflow,'JS errors:',errors)
if(errors.length) throw Error(errors.join(';'))
if(overflow.scroll > overflow.width) throw Error('Scanner overflows the mobile viewport')
await browser.close()
const deniedBrowser=await chromium.launch({headless:true})
const deniedContext=await deniedBrowser.newContext({viewport:{width:1440,height:1000}})
await deniedContext.addInitScript(({token,user})=>{localStorage.setItem('ef_token',token);localStorage.setItem('ef_user',JSON.stringify(user))},session)
const denied=await deniedContext.newPage()
await denied.goto('http://127.0.0.1:5173/gate')
await denied.getByRole('button',{name:'Start camera',exact:true}).click()
await denied.getByText(/Camera unavailable:/).waitFor()
await denied.getByPlaceholder('Or paste QR token / ticket number').fill('invalid-camera-fallback')
await denied.getByRole('button',{name:'Verify',exact:true}).click()
await denied.getByText(/INVALID TICKET/).first().waitFor()
await denied.screenshot({path:join(evidenceDir, 'scanner-no-device-fallback.png'),fullPage:true})
console.log('No-device error shown, manual fallback still works')
await deniedBrowser.close()
