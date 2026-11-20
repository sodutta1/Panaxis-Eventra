import { useEffect, useState } from 'react'
import { api } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import { inr } from '../types'

type VendorPortalData = {
  id: number
  name: string
  company_name?: string
  email?: string
  phone?: string
  category?: { id: number; name: string }
  assignments?: {
    id: number
    service_title: string
    contract_amount: string
    paid_amount: string
    status: string
    event?: { id: number; title: string; event_code: string; start_date: string }
  }[]
  expenses?: { id: number; title: string; amount: string; status: string; incurred_date?: string }[]
}

export default function VendorPortal() {
  const [data, setData] = useState<VendorPortalData | null>(null)
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<{ data: VendorPortalData }>('/portal/vendor').then((r) => setData(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Vendor Portal" sub="GET /api/v1/portal/vendor" />
      <ErrorBox error={error} />
      {data && (
        <>
          <Card className="p-5 mb-5">
            <h2 className="text-lg font-extrabold text-pa-900">{data.name}</h2>
            <p className="text-sm text-slate-600">
              {data.category?.name} {data.company_name ? `· ${data.company_name}` : ''} {data.email ? `· ${data.email}` : ''}
            </p>
          </Card>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">My work orders</h3>
          <Card className="mb-5">
            <Table head={['Event', 'Service', 'Event date', 'Contract', 'Paid', 'Status']}>
              {(data.assignments ?? []).map((a) => (
                <tr key={a.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-semibold">{a.event?.title ?? '—'}</td>
                  <td className="px-4 py-3">{a.service_title}</td>
                  <td className="px-4 py-3 text-slate-600">{a.event?.start_date ? String(a.event.start_date).slice(0, 10) : '—'}</td>
                  <td className="px-4 py-3">{inr(a.contract_amount)}</td>
                  <td className="px-4 py-3">{inr(a.paid_amount)}</td>
                  <td className="px-4 py-3"><Badge value={a.status} /></td>
                </tr>
              ))}
              {(data.assignments ?? []).length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-pamuted">No assignments yet.</td></tr>}
            </Table>
          </Card>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">My expense claims</h3>
          <Card>
            <Table head={['Title', 'Amount', 'Date', 'Status']}>
              {(data.expenses ?? []).map((x) => (
                <tr key={x.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3">{x.title}</td>
                  <td className="px-4 py-3">{inr(x.amount)}</td>
                  <td className="px-4 py-3 text-slate-600">{x.incurred_date ?? '—'}</td>
                  <td className="px-4 py-3"><Badge value={x.status} /></td>
                </tr>
              ))}
              {(data.expenses ?? []).length === 0 && <tr><td colSpan={4} className="px-4 py-8 text-center text-pamuted">No expenses.</td></tr>}
            </Table>
          </Card>
        </>
      )}
    </div>
  )
}
