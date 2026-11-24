import {test,expect,type Page} from '@playwright/test'

async function openGroup(page:Page,name:string){
 const toggle=page.getByRole('button',{name,exact:true})
 if(await toggle.getAttribute('aria-expanded')!=='true') await toggle.click()
}

async function login(page:Page,email='admin@eventflow.test'){
 const r=await page.request.post('http://127.0.0.1:8000/api/v1/auth/login',{data:{email,password:'password'}})
 expect(r.ok()).toBeTruthy()
 const s=await r.json()
 await page.goto('/login')
 await page.evaluate(x=>{localStorage.setItem('ef_token',x.token);localStorage.setItem('ef_user',JSON.stringify(x.user))},s)
}

test('pinning adds a Pinned favourites section that persists across reloads and is per-user',async({page})=>{
 await login(page)
 await page.goto('/events')
 await openGroup(page,'Inventory')
 await page.getByRole('button',{name:'Pin Venues',exact:true}).click()
 await expect(page.locator('.nav-caption')).toHaveText('Pinned')
 await expect(page.locator('.favourites').getByRole('link',{name:'Venues',exact:true})).toBeVisible()
 await page.reload()
 await expect(page.locator('.favourites').getByRole('link',{name:'Venues',exact:true})).toBeVisible()
 // a different user must not inherit the admin's pins
 await login(page,'sales@eventflow.test')
 await page.goto('/events')
 await expect(page.locator('.nav-caption')).toHaveCount(0)
 // unpin removes the section again
 await login(page)
 await page.goto('/events')
 await openGroup(page,'Inventory')
 await page.getByRole('button',{name:'Unpin Venues',exact:true}).click()
 await expect(page.locator('.nav-caption')).toHaveCount(0)
})

test('the open group survives a reload',async({page})=>{
 await login(page)
 await page.goto('/clients')
 await openGroup(page,'Inventory')
 await expect(page.getByRole('link',{name:'Venues',exact:true})).toBeVisible()
 await page.reload()
 await expect(page.getByRole('link',{name:'Venues',exact:true})).toBeVisible()
 await expect(page.locator('.nav-group.is-open')).toContainText('Inventory')
})

test('sidebar items are role-aware',async({page})=>{
 // admin: everything, including admin-only entries
 await login(page)
 await page.goto('/dashboard')
 await expect(page.getByRole('link',{name:'Overview',exact:true})).toBeVisible()
 await expect(page.getByRole('button',{name:'Quick find',exact:true})).toBeVisible()
 await openGroup(page,'Admin')
 await expect(page.getByRole('link',{name:'Team management',exact:true})).toBeVisible()
 await openGroup(page,'Post-sale')
 await expect(page.getByRole('link',{name:'Client portal',exact:true})).toBeVisible()
 await expect(page.getByRole('link',{name:'Vendor preview',exact:true})).toBeVisible()

 // staff (sales executive): no admin-only entries
 await login(page,'sales@eventflow.test')
 await page.goto('/dashboard')
 await expect(page.getByRole('link',{name:'Overview',exact:true})).toBeVisible()
 await expect(page.getByRole('button',{name:'Admin',exact:true})).toHaveCount(0)
 await expect(page.getByRole('link',{name:'Team management',exact:true})).toHaveCount(0)
 await expect(page.getByRole('link',{name:'Vendor preview',exact:true})).toHaveCount(0)
 await expect(page.getByRole('link',{name:'Client portal',exact:true})).toHaveCount(0)
 await openGroup(page,'Sales')
 await expect(page.getByRole('link',{name:'Quotations',exact:true})).toBeVisible()

 // client: lands on the client portal (never the staff-only dashboard), no staff navigation or quick find
 await login(page,'client@eventflow.test')
 await page.goto('/')
 await expect(page).toHaveURL(/\/portal\/client/)
 await expect(page.getByText('Forbidden',{exact:false})).toHaveCount(0)
 await openGroup(page,'Post-sale')
 await expect(page.getByRole('link',{name:'Client portal',exact:true})).toBeVisible()
 await expect(page.getByRole('link',{name:'Overview',exact:true})).toHaveCount(0)
 await expect(page.getByRole('button',{name:'Quick find',exact:true})).toHaveCount(0)
 await expect(page.getByRole('link',{name:'Quotations',exact:true})).toHaveCount(0)
 await expect(page.getByRole('link',{name:'Team management',exact:true})).toHaveCount(0)
})

test('portal-only roles land on their portal, not the staff dashboard',async({page})=>{
 await login(page,'client@eventflow.test')
 await page.goto('/')
 await expect(page).toHaveURL(/\/portal\/client/)
 await login(page,'vendor@eventflow.test')
 await page.goto('/')
 await expect(page).toHaveURL(/\/portal\/vendor/)
 await expect(page.getByText('Forbidden',{exact:false})).toHaveCount(0)
 await login(page)
 await page.goto('/')
 await expect(page).toHaveURL(/\/dashboard/)
})

test('mobile drawer closes when a destination is chosen',async({page})=>{
 await login(page)
 await page.setViewportSize({width:390,height:844})
 await page.goto('/dashboard')
 await page.getByLabel('Open menu',{exact:true}).click()
 await expect(page.locator('.workspace-shell')).toHaveClass(/drawer-open/)
 await openGroup(page,'Sales')
 await page.getByRole('link',{name:'Clients',exact:true}).click()
 await expect(page.locator('.workspace-shell')).not.toHaveClass(/drawer-open/)
 await expect(page.getByRole('heading',{name:/Clients/})).toBeVisible()
})
