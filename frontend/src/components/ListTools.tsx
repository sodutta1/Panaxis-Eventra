import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { useLocation, useSearchParams } from 'react-router-dom'
import { API_URL, api, getToken, getUser } from '../api'
import { Search, X, ArrowDown, ArrowUp, Download, Bookmark, ChevronLeft, ChevronRight } from 'lucide-react'

export type ListConfig = { endpoint: string; fields: string[]; statuses?: string[]; owner?: boolean; extra?: [string,string][] }
export const lists: Record<string,ListConfig> = {
 '/leads': {endpoint:'/leads',fields:['title','contact_name','source','event_date','estimated_budget','status'],statuses:['new','contacted','qualified','proposal_sent','negotiation','converted','lost'],owner:true,extra:[['source','Source']]},
 '/clients': {endpoint:'/clients',fields:['client_code','name','email','city','client_type','events_count','status'],statuses:['active','inactive'],extra:[['city','City']]},
 '/events': {endpoint:'/events',fields:['event_code','title','client_id','start_date','contract_value','amount_received','status'],statuses:['proposed','in_planning','confirmed','completed','cancelled'],owner:true},
 '/venues': {endpoint:'/venues',fields:['name','type','city','capacity','rental_cost_per_day','status'],statuses:['active','inactive'],extra:[['city','City'],['type','Venue type']]},
 '/vendors': {endpoint:'/vendors',fields:['vendor_code','name','vendor_category_id','city','rating','status'],statuses:['active','inactive','blacklisted'],extra:[['city','City'],['vendor_category_id','Category ID']]},
 '/quotations': {endpoint:'/quotations',fields:['quotation_number','client_id','issue_date','valid_until','subtotal','tax_amount','grand_total','status'],statuses:['draft','sent','accepted','rejected','expired']},
 '/invoices': {endpoint:'/invoices',fields:['invoice_number','client_id','due_date','grand_total','paid_amount','balance_due','status'],statuses:['draft','sent','partially_paid','paid','overdue','cancelled']},
 '/expenses': {endpoint:'/expenses',fields:['expense_number','title','incurred_date','payment_method','amount','status'],statuses:['pending','approved','paid','rejected','reversed'],owner:true,extra:[['vendor_id','Vendor ID'],['expense_category_id','Category ID'],['event_id','Event ID'],['campaign','Campaign'],['payment_method','Payment mode']]},
 '/guests': {endpoint:'/guests',fields:['name','event_id','plus_ones','table_number','dietary_preference','rsvp_status','checked_in'],statuses:['pending','confirmed','declined','maybe'],extra:[['event_id','Event ID']]},
 '/tickets': {endpoint:'/tickets',fields:['ticket_number','holder_name','event_id','ticket_type_id','price_paid','payment_status','ticket_status'],statuses:['valid','checked_in','cancelled'],extra:[['event_id','Event ID']]},
 '/work-orders': {endpoint:'/work-orders',fields:['vendor_id','service_title','contract_amount','paid_amount','balance','status'],statuses:['assigned','in_progress','completed','paid','cancelled'],extra:[['event_id','Event ID']]},
 '/budgets': {endpoint:'/budget-items',fields:['category','item_name','estimated_cost','actual_cost','variance','notes']},
 '/admin': {endpoint:'/admin/users',fields:['name','email','role','status'],statuses:['active','inactive']},
}
const labels:Record<string,string> = {id:'Reference ID',title:'Name',client_id:'Client',vendor_id:'Vendor',event_id:'Event',created_by:'Maker',grand_total:'Total',estimated_budget:'Budget',contract_value:'Contract value',amount_received:'Received',rental_cost_per_day:'Rent per day',price_paid:'Price paid'}
export const human = (s:string) => labels[s] ?? s.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase())
export type Meta = {total:number;current_page:number;last_page:number;from?:number;to?:number;per_page?:number}
const ListContext = createContext<{config?:ListConfig;meta?:Meta;busy:boolean}>({busy:false})
export function useList() { return useContext(ListContext) }
export function ListScope({children}:{children:ReactNode}) {
 const {pathname,search}=useLocation();const section=new URLSearchParams(search).get('section')??(pathname==='/portal/client'?'events':'work-orders');const base=pathname.startsWith('/portal/')?{...lists['/'+section],endpoint:pathname+'/records'}:lists[pathname];const eventId=new URLSearchParams(search).get('event_id');const config=base&&eventId&&['/budgets','/work-orders'].includes(pathname)?{...base,endpoint:`/events/${eventId}/${pathname==='/budgets'?'budget/items':'work-orders'}`}:base;const [meta,setMeta]=useState<Meta>();const [busy,setBusy]=useState(false)
 useEffect(()=>{setMeta(undefined)},[pathname])
 useEffect(()=>{const f=(event:Event)=>{const d=(event as CustomEvent).detail;if(d.endpoint===config?.endpoint){setBusy(d.busy);if(d.meta)setMeta(d.meta)}};window.addEventListener('list-state',f);return()=>window.removeEventListener('list-state',f)},[config])
 // Every page can retain its existing mutation and form flow; navigation invalidates its main list.
 useEffect(()=>{window.dispatchEvent(new Event('list-query'))},[search])
 return <ListContext.Provider value={{config,meta,busy}}>{children}</ListContext.Provider>
}
export function FilterBar(){
 const {config,busy}=useList();const [params,setParams]=useSearchParams();const loc=useLocation();const [search,setSearch]=useState(params.get('search')??'');
 const key=`views:${getUser()?.id}:${loc.pathname}`;const [views,setViews]=useState<{name:string;query:string}[]>(()=>JSON.parse(localStorage.getItem(key)??'[]'))
 useEffect(()=>{const local:{name:string;query:string}[]=JSON.parse(localStorage.getItem(key)??'[]');setViews(local);setSearch(params.get('search')??'')
  // Saved views follow the user across devices: the server copy is merged with anything saved only on this browser.
  if(!config)return;let live=true;Promise.resolve(api<{data:{name:string;query:string}[]}>(`/saved-views?module=${encodeURIComponent(loc.pathname)}`)).then(r=>{if(!live)return;const names=new Set((r?.data??[]).map(v=>v.name));const extra=local.filter(v=>!names.has(v.name));extra.forEach(v=>api('/saved-views',{method:'POST',body:{module:loc.pathname,name:v.name,query:v.query}}).catch(()=>{}));const merged=[...(r?.data??[]).map(v=>({name:v.name,query:v.query})),...extra];setViews(merged);localStorage.setItem(key,JSON.stringify(merged))}).catch(()=>{});return()=>{live=false}},[loc.pathname,config])
 useEffect(()=>{setSearch(params.get('search')??'')},[params.get('search')])
 const update=(name:string,value:string)=>{const n=new URLSearchParams(window.location.search);value?n.set(name,value):n.delete(name);n.delete('page');setParams(n,{replace:true})}
 useEffect(()=>{const t=setTimeout(()=>{if(search!==(params.get('search')??''))update('search',search)},300);return()=>clearTimeout(t)},[search])
 if(!config)return null
 const save=()=>{const name=prompt('Name this saved view');if(!name?.trim())return;const next=[...views.filter(v=>v.name!==name),{name:name.trim(),query:params.toString()}];setViews(next);localStorage.setItem(key,JSON.stringify(next));api('/saved-views',{method:'POST',body:{module:loc.pathname,name:name.trim(),query:params.toString()}}).catch(()=>{})}
 const exportList=async(format:string)=>{try{const p=new URLSearchParams(params);p.set('format',format);const r=await fetch(`${API_URL}${config.endpoint}?${p}`,{headers:{Authorization:`Bearer ${getToken()}`}});if(!r.ok)throw Error();const u=URL.createObjectURL(await r.blob());const a=document.createElement('a');a.href=u;a.download=`${loc.pathname.slice(1)}.${format}`;a.click();URL.revokeObjectURL(u)}catch{alert('Could not export these records. Try again.')}}
 return <section className="filter-bar" aria-label="List filters" aria-busy={busy}>
 <div className="filter-primary"><label className="search-field"><Search size={16}/><input aria-label="Search this module" placeholder="Search this module" value={search} onChange={e=>setSearch(e.target.value)}/></label>
 <button onClick={save} className="tool-button"><Bookmark size={16}/>Save view</button>
 <select aria-label="Saved views" value="" onChange={e=>{setParams(e.target.value);setSearch(new URLSearchParams(e.target.value).get('search')??'')}}><option value="">Saved views</option>{views.map(v=><option key={v.name} value={v.query}>{v.name}</option>)}</select>
 <button className="tool-button" onClick={()=>exportList('csv')}><Download size={16}/>CSV</button><button className="tool-button" onClick={()=>exportList('xlsx')}><Download size={16}/>Excel</button></div>
 <details><summary>Filters and sorting</summary><div className="filter-grid">
 {[['name','Name'],['reference','Reference ID'],...(config.owner?[['owner','Owner ID']]:[]),...(config.extra??[])].map(([key,label])=><label key={key}>{label}<input aria-label={`Filter by ${label.toLowerCase()}`} value={params.get(key)??''} onChange={e=>update(key,e.target.value)}/></label>)}
 {config.statuses&&<label>Status<select aria-label="Filter by status" value={params.get('status')??''} onChange={e=>update('status',e.target.value)}><option value="">All statuses</option>{config.statuses.map(s=><option key={s} value={s}>{human(s)}</option>)}</select></label>}
 <label>From date<input type="date" value={params.get('from')??''} onChange={e=>update('from',e.target.value)}/></label><label>To date<input type="date" value={params.get('to')??''} onChange={e=>update('to',e.target.value)}/></label>
 <label>Sort by<select aria-label="Sort by" value={params.get('sort')??'id'} onChange={e=>update('sort',e.target.value)}><option value="id">Reference ID</option>{config.fields.map(f=><option key={f} value={f}>{human(f)}</option>)}</select></label><label>Order<select aria-label="Sort order" value={params.get('direction')??'desc'} onChange={e=>update('direction',e.target.value)}><option value="desc">High to low / Z to A</option><option value="asc">Low to high / A to Z</option></select></label>
 </div></details>
 {params.size>0&&<div className="filter-chips">{Array.from(params).filter(([k])=>!['page','per_page'].includes(k)).map(([k,v])=><button key={k} onClick={()=>{update(k,'');if(k==='search')setSearch('')}} aria-label={`Remove ${human(k)}: ${human(v)} filter`}>{human(k)}: {human(v)}<X size={12}/></button>)}<button onClick={()=>{setParams({});setSearch('')}}>Clear all</button></div>}
 </section>
}
export function Pagination(){const {config,meta,busy}=useList();const [params,setParams]=useSearchParams();if(!config)return null
 const page=Number(params.get('page')??1),last=meta?.last_page??1;const go=(p:number)=>setParams(q=>{q.set('page',String(p));return q})
 return <div className="pagination"><span role="status">{busy?'Loading records…':`Showing ${meta?.from??0} to ${meta?.to??0} of ${meta?.total??0}`}</span><label>Rows <select aria-label="Rows per page" value={params.get('per_page')??'25'} onChange={e=>setParams(q=>{q.set('per_page',e.target.value);q.delete('page');return q})}>{[10,25,50,100].map(n=><option key={n}>{n}</option>)}</select></label><div className="page-numbers"><button aria-label="Previous page" title="Previous page" disabled={page<=1||busy} onClick={()=>go(page-1)}><ChevronLeft size={16}/></button>{Array.from(new Set([1,...Array.from({length:5},(_,i)=>page-2+i).filter(n=>n>0&&n<=last),last])).sort((a,b)=>a-b).map(n=><button key={n} aria-current={page===n?'page':undefined} disabled={busy} onClick={()=>go(n)}>{n}</button>)}<button aria-label="Next page" title="Next page" disabled={page>=last||busy} onClick={()=>go(page+1)}><ChevronRight size={16}/></button></div></div>
}
export function SortHeading({label,index}:{label:string;index:number}){const {config}=useList();const [p,set]=useSearchParams();const field=config?.fields[index];if(!field)return <>{label}</>;const active=p.get('sort')===field,asc=p.get('direction')==='asc';return <button className="sort-heading" onClick={()=>set(q=>{q.set('sort',field);q.set('direction',active&&!asc?'asc':'desc');q.delete('page');return q})}>{label}{active?(asc?<ArrowUp size={12}/>:<ArrowDown size={12}/>):<span className="sort-hint">↕</span>}</button>}
export function Highlight({children}:{children:ReactNode}){const [p]=useSearchParams();const needle=p.get('search')??p.get('name')??'';if(typeof children!=='string'||!needle)return <>{children}</>;const i=children.toLowerCase().indexOf(needle.toLowerCase());return i<0?<>{children}</>:<>{children.slice(0,i)}<mark>{children.slice(i,i+needle.length)}</mark>{children.slice(i+needle.length)}</>}
