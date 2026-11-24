import { Children, cloneElement, isValidElement, type ReactNode, type ReactElement } from 'react'
import { FilterBar, Pagination, SortHeading, Highlight } from './ListTools'
import { STATUS_COLORS } from '../types'

export function PageHeader({ title, sub, children, titleClassName = '' }: { title: string; sub?: string; children?: ReactNode; titleClassName?: string }) {
  return (
    <><div className="page-header flex items-start justify-between mb-6">
      <div>
        <h1 className={`text-[22px] font-extrabold ${titleClassName || 'text-pa-900'} tracking-tight`}>{title}</h1>
        {sub && <p className="text-[13px] text-pamuted mt-0.5">{sub}</p>}
      </div>
      <div className="header-actions flex gap-2">{children}</div>
    </div><FilterBar /></>
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
 if(typeof node==='string') return <Highlight>{node}</Highlight>
 if(!isValidElement(node)) return node
 const e=node as ReactElement<{children?:ReactNode}>
 if(['button','select','option','input','svg'].includes(String(e.type))) return e
 return cloneElement(e,{},Children.map(e.props.children,highlightTree))
}
export function DataTable({ head, children }: { head: string[]; children: ReactNode }) {
 return <><div className="table-scroll"><table className="w-full text-[13.5px]"><thead><tr>{head.map((h,i)=><th key={i}><SortHeading label={h} index={i}/></th>)}</tr></thead><tbody>{Children.map(children,highlightTree)}</tbody></table></div><Pagination /></>
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
