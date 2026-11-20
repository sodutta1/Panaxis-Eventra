import { useEffect, useState } from 'react'
import { api } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import { inr, type EventItem } from '../types'

type PortalData = {
  id: number
  name: string
  company_name?: string
  email?: string
  phone?: string
  events?: EventItem[]
  invoices?: { id: number; invoice_number: string; status: string; grand_total: string; balance_due: string; due_date?: string }[]
  quotations?: { id: number; quotation_number: string; status: string; grand_total: string }[]
}

export default function ClientPortal() {
  const [data, setData] = useState<PortalData | null>(null)
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<{ data: PortalData }>('/portal/client').then((r) => setData(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Client Portal" sub="GET /api/v1/portal/client" />
      <ErrorBox error={error} />
      {data && (
        <>
          <Card className="p-5 mb-5">
            <h2 className="text-lg font-extrabold text-pa-900">{data.name}</h2>
            <p className="text-sm text-slate-600">{data.company_name} {data.email ? `· ${data.email}` : ''} {data.phone ? `· ${data.phone}` : ''}</p>
          </Card>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">My events</h3>
          <Card className="mb-5">
            <Table head={['Code', 'Event', 'Dates', 'Venue', 'Status']}>
              {(data.events ?? []).map((e) => (
                <tr key={e.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-mono text-xs text-slate-500">{e.event_code}</td>
                  <td className="px-4 py-3 font-semibold">{e.title}</td>
                  <td className="px-4 py-3 text-slate-600">{String(e.start_date).slice(0, 10)} → {String(e.end_date).slice(0, 10)}</td>
                  <td className="px-4 py-3 text-slate-600">{e.venue?.name ?? '—'}</td>
                  <td className="px-4 py-3"><Badge value={e.status} /></td>
                </tr>
              ))}
              {(data.events ?? []).length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-pamuted">No events yet.</td></tr>}
            </Table>
          </Card>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">Invoices</h3>
          <Card className="mb-5">
            <Table head={['Number', 'Total', 'Balance due', 'Due date', 'Status']}>
              {(data.invoices ?? []).map((i) => (
                <tr key={i.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-mono text-xs text-slate-500">{i.invoice_number}</td>
                  <td className="px-4 py-3">{inr(i.grand_total)}</td>
                  <td className="px-4 py-3 font-semibold">{inr(i.balance_due)}</td>
                  <td className="px-4 py-3 text-slate-600">{i.due_date ?? '—'}</td>
                  <td className="px-4 py-3"><Badge value={i.status} /></td>
                </tr>
              ))}
              {(data.invoices ?? []).length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-pamuted">No invoices.</td></tr>}
            </Table>
          </Card>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">Quotations</h3>
          <Card>
            <Table head={['Number', 'Total', 'Status']}>
              {(data.quotations ?? []).map((q) => (
                <tr key={q.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-mono text-xs text-slate-500">{q.quotation_number}</td>
                  <td className="px-4 py-3">{inr(q.grand_total)}</td>
                  <td className="px-4 py-3"><Badge value={q.status} /></td>
                </tr>
              ))}
              {(data.quotations ?? []).length === 0 && <tr><td colSpan={3} className="px-4 py-8 text-center text-pamuted">No quotations.</td></tr>}
            </Table>
          </Card>
        </>
      )}
    </div>
  )
}
