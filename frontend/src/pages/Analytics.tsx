import { useEffect, useState } from 'react'
import { api } from '../api'
import { Card, ErrorBox, PageHeader } from '../components/ui'
import { Bars, Donut, type Slice } from '../components/charts'
import { inrCompact } from '../types'

type Analytics = {
  kpis: {
    events_this_week: number
    open_tasks: number
    budget_utilization: number
    bills_due: string
    received_revenue: string
  }
  task_completion: { total: number; completed: number; in_progress: number; pending: number; rate: number }
  idle_tasks_by_category: { name: string; count: number }[]
  overdue_tasks_by_category: { name: string; count: number }[]
  leads_funnel: { status: string; count: number }[]
  top_cost_categories: { name: string; total: string }[]
  vendor_burn_rate: { yw: number; week_label: string; total: string }[]
  clients: { total: number; repeat: number; churned: number; new_no_events: number }
}

const titleCase = (s: string) => s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

export default function Analytics() {
  const [data, setData] = useState<Analytics | null>(null)
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<{ data: Analytics }>('/analytics').then((r) => setData(r.data)).catch(setError)
  }, [])

  if (error) return <ErrorBox error={error} />
  if (!data) return <div className="text-slate-500">Loading analytics…</div>

  const k = data.kpis
  const kpis = [
    { label: 'Events this week', value: String(k.events_this_week) },
    { label: 'Open tasks', value: String(k.open_tasks) },
    { label: 'Budget utilization', value: `${k.budget_utilization}%` },
    { label: 'Bills due', value: inrCompact(k.bills_due) },
    { label: 'Received revenue', value: inrCompact(k.received_revenue) },
  ]

  const tc = data.task_completion
  const taskSlices: Slice[] = [
    { name: 'Completed', value: tc.completed },
    { name: 'In progress', value: tc.in_progress },
    { name: 'Pending', value: tc.pending },
  ]
  const donutize = (rows: { name: string; count: number }[]) => rows.map((r) => ({ name: r.name, value: r.count }))
  const money = (rows: { name: string; total: string }[]) => rows.map((r) => ({ name: r.name, value: Number(r.total) }))

  return (
    <div>
      <PageHeader title="Analytics" sub="Review performance across events, tasks, money and clients." />
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        {kpis.map((x) => (
          <Card key={x.label} className="p-4">
            <div className="text-xs text-slate-500">{x.label}</div>
            <div className="text-2xl font-bold mt-1 text-slate-800">{x.value}</div>
          </Card>
        ))}
      </div>

      <div className="grid lg:grid-cols-3 gap-6 mb-6">
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Task completion rate</h3>
          <div className="text-3xl font-bold text-pa-700 mb-3">{tc.rate}%</div>
          <Bars slices={taskSlices} />
        </Card>
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Idle tasks</h3>
          <Donut slices={donutize(data.idle_tasks_by_category)} />
        </Card>
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Extended deadlines</h3>
          <Donut slices={donutize(data.overdue_tasks_by_category)} />
        </Card>
      </div>

      <div className="grid lg:grid-cols-3 gap-6 mb-6">
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Top cost categories</h3>
          <Bars slices={money(data.top_cost_categories)} format={(v) => inrCompact(v)} />
        </Card>
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Leads</h3>
          <Donut slices={data.leads_funnel.map((l) => ({ name: titleCase(l.status), value: l.count }))} />
        </Card>
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Vendor burn rate</h3>
          <Bars
            slices={data.vendor_burn_rate.map((w) => ({ name: `Week of ${w.week_label}`, value: Number(w.total) }))}
            format={(v) => inrCompact(v)}
          />
        </Card>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Repeat clients</h3>
          <div className="text-3xl font-bold text-pa-700">{data.clients.repeat}</div>
          <p className="text-xs text-slate-500 mt-1">clients with more than one event, of {data.clients.total} total</p>
        </Card>
        <Card className="p-5">
          <h3 className="font-semibold mb-3">Churned clients</h3>
          <div className="text-3xl font-bold text-rose-600">{data.clients.churned}</div>
          <p className="text-xs text-slate-500 mt-1">no event in the last 6 months · {data.clients.new_no_events} new clients with no event yet</p>
        </Card>
      </div>
    </div>
  )
}
