import { Children, cloneElement, isValidElement, type ReactNode, type ReactElement } from 'react'
import { BulkDocuments, DocumentRow, isDocument } from './DocumentTools'
import { RowActions, PdfAction } from './Actions'
import { useLocation } from 'react-router-dom'
import { FilterBar, Pagination, SortHeading, Highlight, useList } from './ListTools'
import { getUser } from '../api'
import { STATUS_COLORS } from '../types'

export function PageHeader({ title, sub, children, titleClassName = '' }: { title: string; sub?: string; children?: ReactNode; titleClassName?: string }) {
  const role=getUser()?.role??'';const module=useLocation().pathname.split('/')[1];const admin=['super_admin','company_admin','admin'].includes(role);const canWrite=admin||(['leads','clients','quotations'].includes(module)?['event_manager','sales_executive']:['invoices','expenses'].includes(module)?['accountant']:module==='venues'?[]:['event_manager','staff']).includes(role)
  return (
    <><div className="page-header flex items-start justify-between mb-6">
      <div>
        <h1 className={`text-[22px] font-extrabold ${titleClassName || 'text-pa-900'} tracking-tight`}>{title}</h1>
        {sub && <p className="text-[13px] text-pamuted mt-0.5">{sub}</p>}
      </div>
      <div className="header-actions flex gap-2">{Children.map(children,c=>isValidElement(c)&&typeof (c.props as any).children==='string'&&/^\+ (New|Ticket)/.test((c.props as any).children)&&!canWrite?null:c)}</div>
    </div><FilterBar /><BulkDocuments /></>
  )
}

export function Badge({ value }: { value?: string }) {
  if (!value) return null
  const cls = STATUS_COLORS[value] ?? 'bg-slate-200 text-slate-700'
  return (
    <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11.5px] font-bold capitalize ${cls}`}>
      {value.replaceAll('_', ' ')}
    </span>
  )
}

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`bg-white rounded-2xl shadow-sm border border-paline ${className}`}>{children}</div>
}

// One table presentation, including nested detail lists. Main module tables use server metadata.
function highlightTree(node:ReactNode):ReactNode {
 if(typeof node==='string') {
   const text=/^\d{4}-\d{2}-\d{2}(T[0-9:.Z+\-]+)?$/.test(node)?new Date(node.length===10?node+'T12:00:00+05:30':node).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric',timeZone:'Asia/Kolkata'}):node
   return <Highlight>{text}</Highlight>
 }
 if(!isValidElement(node)) return node
 const e=node as ReactElement<{children?:ReactNode}>
 if(['button','select','option','input','svg'].includes(String(e.type))) return e
 return cloneElement(e,{},Children.map(e.props.children,highlightTree))
}
export function DataTable({ head, children }: { head: string[]; children: ReactNode }) {
 const {busy}=useList();const loc=useLocation();const type=loc.pathname.slice(1);const document=isDocument(type)
 const cards:ReactNode[]=[]
 const rows=Children.map(children,(node)=>{
  if(!isValidElement(node)||node.type!=='tr')return node
  const row=node as ReactElement<{children?:ReactNode}>;const cells=Children.toArray(row.props.children)
  const id=Number(String(row.key??'').replace(/^.*?\$/,''));
  const grouped=cells.map(c=>{if(!isValidElement(c))return c;const cell=c as ReactElement<{children?:ReactNode;className?:string}>;if((cell.props.className??'').includes('text-right') && Children.toArray(cell.props.children).some(x=>isValidElement(x)&&typeof x.type!=='string'))return cloneElement(cell,{},<RowActions>{cell.props.children}</RowActions>);return cell})
  const processed=grouped.map(c=>highlightTree(c))
  // Phone card: same cells, labelled by column, actions in a footer row.
  const cardFields:ReactNode[]=[];const cardActions:ReactNode[]=[]
  if(cells.length===1&&isValidElement(cells[0])&&((cells[0] as ReactElement<{colSpan?:number}>).props.colSpan??0)>1){cards.push(<div className="record-card rc-empty" key={row.key??cards.length}>{(cells[0] as ReactElement<{children?:ReactNode}>).props.children}</div>);return cloneElement(row,{},...processed)}
  processed.forEach((c,i)=>{if(!isValidElement(c)){cardFields.push(<div className="rc-field" key={i}><span className="rc-label">{head[i]}</span><div className="rc-value">{c}</div></div>);return}
   const cell=c as ReactElement<{children?:ReactNode;className?:string}>;const kids=Children.toArray(cell.props.children)
   const isActions=(cell.props.className??'').includes('text-right')&&kids.some(x=>isValidElement(x)&&typeof x.type!=='string')
   if(isActions)cardActions.push(...kids.map((k,j)=><span className="rc-action" key={`a${j}`}>{isValidElement(k)?(k as ReactElement<{children?:ReactNode}>).props.children??k:k}</span>))
   else cardFields.push(<div className="rc-field" key={i}><span className="rc-label">{head[i]}</span><div className="rc-value">{kids}</div></div>)})
  if(!id||!document){cards.push(<div className="record-card" key={row.key??cards.length}><div className="rc-fields">{cardFields}</div>{cardActions.length>0&&<div className="rc-actions">{cardActions}</div>}</div>);return cloneElement(row,{},...processed)}
  let hasPdf=false;const check=(n:ReactNode):void=>{Children.forEach(n,x=>{if(isValidElement(x)){const e=x as ReactElement<{children?:ReactNode}>;if(e.type===PdfAction)hasPdf=true;check(e.props.children)}})};check(cells)
  const docCell=<td className="px-4" key="docs"><RowActions><DocumentRow type={type} id={id} pdfExists={hasPdf}/></RowActions></td>
  cards.push(<div className="record-card" key={row.key??cards.length}><div className="rc-fields">{cardFields}</div><div className="rc-actions">{cardActions}<span className="rc-action"><DocumentRow type={type} id={id} pdfExists={hasPdf}/></span></div></div>)
  return cloneElement(row,{},...processed,docCell)
 })
 return <><div className="table-scroll dt-table"><table className="w-full text-[13.5px]"><thead><tr>{head.map((h,i)=><th key={i}><SortHeading label={h} index={i}/></th>)}{document&&<th>Documents</th>}</tr></thead><tbody>{busy?<tr><td colSpan={head.length+(document?1:0)}><div className="skeleton" aria-label="Loading records"/></td></tr>:rows}</tbody></table></div><div className="record-cards">{busy?<div className="record-card"><div className="skeleton" aria-label="Loading records"/></div>:cards}</div><Pagination /></>
}
export const Table = DataTable

export function plainError(text:string){
 if(/\/api\/|[a-f0-9]{8}-[a-f0-9-]{27,}|undefined|\bnull\b|NaN/i.test(text)) return 'Something went wrong. Please try again.'
 return text.replace(/\b([a-z]+(?:_[a-z]+)+)\b/g, w=>w.replaceAll('_',' '))
}
export function ErrorBox({ error }: { error: unknown }) {
  if (!error) return null
  const e = error as { message?: string; errors?: Record<string, string[]> }
  const details = e.errors ? Object.values(e.errors).flat() : []
  return (
    <div className="bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl px-4 py-3 mb-4">
      <div className="font-medium">{plainError(e.message ?? 'Something went wrong. Please try again.')}</div>
      {details.length > 0 && (
        <ul className="list-disc ml-5 mt-1">
          {details.map((d, i) => (
            <li key={i}>{plainError(d)}</li>
          ))}
        </ul>
      )}
    </div>
  )
}

export const inputCls =
  'w-full rounded-xl border-[1.5px] border-paline bg-[#fbfdfb] px-3 py-2.5 text-sm text-ink focus:outline-none focus:border-pa-500'
export const btnCls =
  'rounded-xl bg-pa-700 text-white text-sm font-bold px-4 py-2.5 hover:bg-pa-900 disabled:opacity-50'
export const btnGhostCls =
  'rounded-xl border-[1.5px] border-pa-300 bg-white text-pa-700 text-sm font-bold px-4 py-2 hover:bg-pa-50'
