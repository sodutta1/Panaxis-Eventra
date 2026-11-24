import {useEffect,useState} from 'react'
import {useSearchParams} from 'react-router-dom'
import {api} from '../api'
import {Badge,Card,ErrorBox,PageHeader,Table} from './ui'
import {DocumentRow} from './DocumentTools'
import {inr} from '../types'
const configs:Record<string,{heads:string[];keys:string[]}>= {
 events:{heads:['Reference','Event','Start date','Contract value','Status'],keys:['event_code','title','start_date','contract_value','status']},
 invoices:{heads:['Reference','Total','Balance due','Due date','Status'],keys:['invoice_number','grand_total','balance_due','due_date','status']},
 quotations:{heads:['Reference','Total','Issued','Status'],keys:['quotation_number','grand_total','issue_date','status']},
 'work-orders':{heads:['Reference','Service','Contract','Paid','Status'],keys:['id','service_title','contract_amount','paid_amount','status']},
 expenses:{heads:['Reference','Expense','Date','Amount','Status'],keys:['expense_number','title','incurred_date','amount','status']},
}
export default function PortalRecords({kind}:{kind:'client'|'vendor'}){const [p,set]=useSearchParams();const options=kind==='client'?['events','invoices','quotations']:['work-orders','expenses'];const section=options.includes(p.get('section')??'')?p.get('section')!:options[0];const config=configs[section];const [items,setItems]=useState<Record<string,any>[]>([]);const [error,setError]=useState<unknown>();useEffect(()=>{api<{data:typeof items}>(`/portal/${kind}/records?${p.toString()}`).then(r=>{setItems(r.data);setError(undefined)}).catch(setError)},[kind,p.toString()]);return <div><PageHeader title={kind==='client'?'Client portal':'Vendor preview'} sub={kind==='client'?'Review your events, quotations and invoices.':'Review this supplier’s work orders and expense claims.'}/><div className="detail-tabs" role="tablist" aria-label="Portal sections">{options.map(s=><button key={s} role="tab" aria-selected={section===s} onClick={()=>set({section:s})}>{s.replace('-',' ').replace(/^./,c=>c.toUpperCase())}</button>)}</div><ErrorBox error={error}/>{!error&&<Card><Table head={[...config.heads,'Documents']}>{items.map(row=><tr key={row.id}>{config.keys.map(k=><td className="px-4 py-3" key={k}>{k==='status'?<Badge value={row[k]}/>:['grand_total','balance_due','contract_value','contract_amount','paid_amount','amount'].includes(k)?inr(row[k]):String(row[k]??'Not set')}</td>)}<td className="px-4 py-3"><DocumentRow type={section} id={row.id}/></td></tr>)}{!items.length&&<tr><td colSpan={config.keys.length+1} className="p-4">No records in this view. Clear filters or ask your event team for an update.</td></tr>}</Table></Card>}</div>}
