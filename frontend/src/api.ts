// Central API client for the EventFlow ERP backend.
// Base URL: set VITE_API_URL in frontend/.env, e.g. VITE_API_URL=http://localhost:8000/api/v1
export const API_URL = import.meta.env.VITE_API_URL ?? '/api/v1'

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  constructor(status: number, message: string, errors?: Record<string, string[]>) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export function getToken(): string | null {
  return localStorage.getItem('ef_token')
}

export function setSession(token: string, user: unknown) {
  localStorage.setItem('ef_token', token)
  localStorage.setItem('ef_user', JSON.stringify(user))
}

export function clearSession() {
  localStorage.removeItem('ef_token')
  localStorage.removeItem('ef_user')
  clearOfflineCache()
}

// Offline support: the last successful read of each screen is kept on this device (per user) so staff can still look
// things up when the connection drops. Writes are never queued; they fail with a plain message instead.
const CACHE_PREFIX = 'ef_cache:'
export function clearOfflineCache() {
  try { Object.keys(localStorage).filter((k) => k.startsWith(CACHE_PREFIX)).forEach((k) => localStorage.removeItem(k)) } catch { /* ignore */ }
}
const cacheKey = (path: string) => {
  const u = localStorage.getItem('ef_user')
  let id = 'anon'
  try { id = String(JSON.parse(u ?? 'null')?.id ?? 'anon') } catch { /* ignore */ }
  return `${CACHE_PREFIX}${id}:${path}`
}
const cacheable = (path: string) => !path.startsWith('/auth') && !path.startsWith('/documents') && !path.startsWith('/saved-views')
function readCache(path: string): unknown | undefined {
  try { const raw = localStorage.getItem(cacheKey(path)); return raw ? JSON.parse(raw) : undefined } catch { return undefined }
}
function writeCache(path: string, json: unknown) {
  try { localStorage.setItem(cacheKey(path), JSON.stringify(json)) } catch { /* storage full: skip */ }
}
const announce=(stale:boolean)=>{if(typeof window!=='undefined')window.dispatchEvent(new CustomEvent('offline-state',{detail:{stale}}))}
export const OFFLINE_MESSAGE = 'You are offline. Changes cannot be saved until the connection is back.'

export function homeFor(role?: string): string {
  if (role === 'client') return '/portal/client'
  if (role === 'vendor') return '/portal/vendor'
  return '/dashboard'
}

export function getUser(): { id: number; name: string; email: string; role: string } | null {
  const raw = localStorage.getItem('ef_user')
  return raw ? JSON.parse(raw) : null
}

const latestLists = new Map<string, Promise<{res:Response;json:any}>>()
export async function api<T = any>(
  path: string,
  options: { method?: string; body?: unknown } = {},
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  }
  const token = getToken()
  if (token) headers['Authorization'] = `Bearer ${token}`

  const activePath=typeof window==='undefined'?'':window.location.pathname
  const portalList=activePath.startsWith('/portal/')&&path.startsWith(activePath+'/records')
  const groupedList=(!options.method||options.method==='GET')&&['/budgets','/work-orders'].includes(activePath)&&/^\/events\/\d+\/(work-orders|budget\/items)/.test(path)
  const modulePath = activePath === '/admin' ? '/admin/users' : activePath
  const isList = groupedList || portalList || ((!options.method || options.method === 'GET') && path.split('?')[0] === modulePath && ['leads','clients','events','venues','vendors','quotations','invoices','expenses','guests','tickets','admin'].includes(modulePath.split('/')[1]))
  if (isList) {
    const [base,query] = path.split('?'); const p = new URLSearchParams(query)
    new URLSearchParams(window.location.search).forEach((v,k)=>p.set(k,v))
    path = base + '?' + p
    window.dispatchEvent(new CustomEvent('list-state',{detail:{endpoint:groupedList||portalList?path.split('?')[0]:modulePath,busy:true}}))
  }
  const request = fetch(`${API_URL}${path}`, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  }).then(async res=>({res,json:res.status===204?{}:await res.json().catch(()=>({}))}))
    .then(r=>{
      if(r.res.ok&&(!options.method||options.method==='GET')&&cacheable(path)){writeCache(path,r.json);announce(false)}
      return r
    })
    .catch(err=>{
      if(err instanceof ApiError)throw err
      const isGet=!options.method||options.method==='GET'
      const saved=isGet&&cacheable(path)?readCache(path):undefined
      if(saved!==undefined){announce(true);return {res:new Response(null,{status:200}),json:saved}}
      if(!isGet)throw new ApiError(0,OFFLINE_MESSAGE)
      throw err
    })
  const listKey=activePath+':'+path.split('?')[0]
  if(isList)latestLists.set(listKey,request)
  let result=await request
  // Earlier filter/sort responses must never overwrite the latest query's rows.
  if(isList){while(latestLists.get(listKey)!==request){const newest=latestLists.get(listKey)!;result=await newest;if(latestLists.get(listKey)===newest)break}}
  const {res,json}=result

  if (isList) window.dispatchEvent(new CustomEvent('list-state',{detail:{endpoint:groupedList||portalList?path.split('?')[0]:modulePath,busy:false,meta:json.meta}}))
  if (!res.ok) {
    if (res.status === 401) clearSession()
    throw new ApiError(res.status, (json as any).message ?? `Request failed (${res.status})`, (json as any).errors)
  }
  if ((options.method ?? 'GET') === 'DELETE') {
    const m = path.split('?')[0].match(/^\/(leads|clients|venues|vendors|guests|events|quotations)\/(\d+)$/)
    if (m) window.dispatchEvent(new CustomEvent('record-deleted', { detail: { type: m[1], id: Number(m[2]) } }))
  }
  return json as T
}

// Laravel paginator shape
export type Paginated<T> = {
  data: T[]
  meta: { total: number; current_page: number; last_page: number }
}

// Download an authenticated PDF (Bearer token) and trigger a browser save.
export async function downloadPdf(path: string, filename: string) {
  const match=path.match(/^\/(quotations|invoices|expenses|tickets|work-orders)\/(\d+)\/pdf$|^\/(events)\/(\d+)\/closeout-pdf$/)
  if(match){const type=match[1]??match[3],id=match[2]??match[4];const link=await api<{url:string}>(`/documents/${type}/${id}/link`);path=link.url}
  const res = await fetch(path.startsWith('http')||path.startsWith('/api/')?path:`${API_URL}${path}`, {
    headers: { Authorization: `Bearer ${getToken() ?? ''}`, Accept: 'application/pdf' },
  })
  if (!res.ok) throw new ApiError(res.status, "Couldn't create the PDF. Try again.")
  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = res.headers.get('Content-Disposition')?.match(/filename="?([^";]+)/)?.[1] ?? filename
  a.click()
  URL.revokeObjectURL(url)
}

export async function allOptions<T>(path:string):Promise<Paginated<T>> {
 let page=1,data:T[]=[],last=1;do{const r=await api<Paginated<T>>(`${path}${path.includes('?')?'&':'?'}per_page=100&page=${page}`);data=data.concat(r.data);last=r.meta.last_page;page++}while(page<=last)
 return {data,meta:{total:data.length,current_page:1,last_page:1}}
}
