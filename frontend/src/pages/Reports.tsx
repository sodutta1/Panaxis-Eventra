import { useEffect, useState } from 'react'
import { api } from '../api'
import { Card, ErrorBox, PageHeader } from '../components/ui'
import { inr } from '../types'

type Dash = {
  total_events: number
  active_events: number
  total_revenue: string
  received_revenue: string
  total_expenses: string
  outstanding_invoices: string
  total_clients: number
  total_leads: number
  converted_leads: number
  total_guests: number
  checked_in_guests: number
  events_by_category: { name: string; count: number }[]
}

export default function Reports() {
  const [d, setD] = useState<Dash | null>(null)
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<{ data: Dash }>('/dashboard').then((r) => setD(r.data)).catch(setError)
  }, [])

  const maxCat = Math.max(1, ...(d?.events_by_category ?? []).map((c) => c.count))

  return (
    <div>
      <PageHeader title="Reports" sub="Review event revenue, costs and business performance." />
      <ErrorBox error={error} />
      {d && (
        <>
          <h3 className="text-sm font-extrabold text-pa-900 mb-2">Financial</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            {[
              ['Contracted revenue', inr(d.total_revenue)],
              ['Received', inr(d.received_revenue)],
              ['Expenses', inr(d.total_expenses)],
              ['Outstanding invoices', inr(d.outstanding_invoices)],
            ].map(([label, val], i) => (
              <Card key={label as string} className="px-4 py-3">
                <div className="text-[11px] font-bold uppercase tracking-wide text-pamuted">{label}</div>
                <div className={`text-lg font-extrabold mt-0.5 ${i === 3 ? 'text-amber-700' : 'text-ink'}`}>{val}</div>
              </Card>
            ))}
          </div>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">Pipeline & guests</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            {[
              ['Events', `${d.active_events} active / ${d.total_events} total`],
              ['Clients', String(d.total_clients)],
              ['Leads converted', `${d.converted_leads} / ${d.total_leads}`],
              ['Guests checked in', `${d.checked_in_guests} / ${d.total_guests}`],
            ].map(([label, val]) => (
              <Card key={label as string} className="px-4 py-3">
                <div className="text-[11px] font-bold uppercase tracking-wide text-pamuted">{label}</div>
                <div className="text-lg font-extrabold text-ink mt-0.5">{val}</div>
              </Card>
            ))}
          </div>

          <h3 className="text-sm font-extrabold text-pa-900 mb-2">Events by category</h3>
          <Card className="p-5 max-w-2xl">
            {(d.events_by_category ?? []).map((c) => (
              <div key={c.name} className="flex items-center gap-3 mb-2.5">
                <div className="w-32 text-[13px] font-semibold truncate">{c.name}</div>
                <div className="flex-1 h-5 rounded-lg bg-slate-100 overflow-hidden">
                  <div className="h-full rounded-lg bg-gradient-to-r from-pa-700 to-pa-500" style={{ width: `${(c.count / maxCat) * 100}%` }} />
                </div>
                <div className="w-8 text-right text-[13px] font-bold">{c.count}</div>
              </div>
            ))}
          </Card>
        </>
      )}
    </div>
  )
}
