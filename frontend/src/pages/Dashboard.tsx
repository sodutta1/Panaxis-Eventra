import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader } from '../components/ui'
import { inr, inrCompact, type EventItem, type Task } from '../types'

type DashData = {
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
  upcoming_events: EventItem[]
  pending_tasks: Task[]
  events_by_category: { name: string; count: number }[]
}

const PRIORITY_PILL: Record<string, string> = {
  urgent: 'bg-rose-100 text-rose-700',
  high: 'bg-amber-100 text-amber-800',
  medium: 'bg-sky-100 text-sky-800',
  low: 'bg-slate-200 text-slate-600',
}

export default function Dashboard() {
  const [data, setData] = useState<DashData | null>(null)
  const [eventNames, setEventNames] = useState<Record<number, string>>({})
  const [error, setError] = useState<unknown>(null)
  const [busyTask, setBusyTask] = useState<number | null>(null)

  const load = useCallback(() => {
    api<{ data: DashData }>('/dashboard').then((r) => setData(r.data)).catch(setError)
    api<Paginated<EventItem>>('/events?per_page=100')
      .then((r) => {
        const map: Record<number, string> = {}
        for (const e of r.data ?? []) map[e.id] = e.title
        setEventNames(map)
      })
      .catch(() => {})
  }, [])

  useEffect(load, [load])

  const markDone = async (taskId: number) => {
    setBusyTask(taskId)
    try {
      await api(`/tasks/${taskId}/toggle`, { method: 'POST' })
      load()
    } catch (e) {
      setError(e)
    } finally {
      setBusyTask(null)
    }
  }

  const collectedPct = useMemo(() => {
    if (!data) return 0
    const total = Number(data.total_revenue)
    if (!total) return 0
    return Math.round((Number(data.received_revenue) / total) * 100)
  }, [data])

  if (error && !data) return <ErrorBox error={error} />
  if (!data) return <div className="text-pamuted">Loading dashboard…</div>

  const stats: { label: string; value: string | number; sub: string; warn?: boolean }[] = [
    { label: 'Active events', value: data.active_events, sub: `${data.total_events} total events` },
    { label: 'Revenue booked', value: inrCompact(data.total_revenue), sub: 'contract value' },
    { label: 'Cash received', value: inrCompact(data.received_revenue), sub: `${collectedPct}% collected` },
    { label: 'Outstanding', value: inrCompact(data.outstanding_invoices), sub: 'open invoice balance', warn: true },
    { label: 'Leads in pipeline', value: data.total_leads, sub: `${data.converted_leads} converted all-time` },
  ]

  return (
    <div>
      <PageHeader title="Dashboard" sub="Track today's events, tasks and outstanding payments." />
      <ErrorBox error={error} />

      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5 mb-6">
        {stats.map((s) => (
          <Card key={s.label} className="p-4">
            <div className="text-[11px] font-bold uppercase tracking-wider text-pamuted">{s.label}</div>
            <div className={`text-[26px] leading-tight font-extrabold mt-1.5 ${s.warn ? 'text-paamber' : 'text-ink'}`}>
              {s.value}
            </div>
            <div className="text-xs font-semibold text-pamuted mt-1">{s.sub}</div>
          </Card>
        ))}
      </div>

      <Card className="p-5 mb-5">
        <h2 className="text-[16px] font-bold text-pa-900 mb-3">Upcoming events</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-[13.5px]">
            <thead>
              <tr className="text-left text-[11.5px] uppercase tracking-wide text-pamuted border-b border-paline">
                <th className="py-2.5 pr-4 font-bold">Event</th>
                <th className="py-2.5 pr-4 font-bold">Client</th>
                <th className="py-2.5 pr-4 font-bold">Date</th>
                <th className="py-2.5 pr-4 font-bold">Venue</th>
                <th className="py-2.5 font-bold">Status</th>
              </tr>
            </thead>
            <tbody>
              {data.upcoming_events.map((e) => (
                <tr key={e.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-3 pr-4">
                    <Link to={`/events/${e.id}`} className="font-semibold text-pa-700 hover:text-pa-700 hover:underline">
                      {e.title}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-slate-600">{e.client?.name ?? '-'}</td>
                  <td className="py-3 pr-4 text-slate-600 whitespace-nowrap">
                    {String(e.start_date).slice(0, 10)}
                    {e.end_date && String(e.end_date).slice(0, 10) !== String(e.start_date).slice(0, 10)
                      ? ` – ${String(e.end_date).slice(0, 10)}`
                      : ''}
                  </td>
                  <td className="py-3 pr-4 text-slate-600">{e.venue?.name ?? '-'}</td>
                  <td className="py-3">
                    <Badge value={e.status} />
                  </td>
                </tr>
              ))}
              {data.upcoming_events.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-6 text-center text-pamuted">
                    No upcoming events
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <Card className="p-5">
        <h2 className="text-[16px] font-bold text-pa-900 mb-3">Urgent tasks</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-[13.5px]">
            <thead>
              <tr className="text-left text-[11.5px] uppercase tracking-wide text-pamuted border-b border-paline">
                <th className="py-2.5 pr-4 font-bold">Task</th>
                <th className="py-2.5 pr-4 font-bold">Event</th>
                <th className="py-2.5 pr-4 font-bold">Owner</th>
                <th className="py-2.5 pr-4 font-bold">Priority</th>
                <th className="py-2.5 font-bold text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              {data.pending_tasks.map((t) => (
                <tr key={t.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-3 pr-4 font-medium text-ink">{t.title}</td>
                  <td className="py-3 pr-4 text-slate-600">{eventNames[t.event_id ?? -1] ?? '-'}</td>
                  <td className="py-3 pr-4 text-slate-600">{t.assigned_user?.name ?? 'Unassigned'}</td>
                  <td className="py-3 pr-4">
                    <span
                      className={`inline-block px-2.5 py-0.5 rounded-full text-[11.5px] font-bold capitalize ${
                        PRIORITY_PILL[t.priority] ?? PRIORITY_PILL.low
                      }`}
                    >
                      {t.priority}
                    </span>
                  </td>
                  <td className="py-3 text-right">
                    <button
                      onClick={() => markDone(t.id)}
                      disabled={busyTask === t.id}
                      className="rounded-full border border-paline bg-white px-4 py-1.5 text-xs font-bold text-pa-700 hover:bg-pa-50 disabled:opacity-50"
                    >
                      {busyTask === t.id ? '…' : 'Done'}
                    </button>
                  </td>
                </tr>
              ))}
              {data.pending_tasks.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-6 text-center text-pamuted">
                    Nothing pending. All caught up.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <div className="mt-5 text-xs text-pamuted">
        Money booked across all events: {inr(data.total_revenue)} · Guests checked in: {data.checked_in_guests} /{' '}
        {data.total_guests}
      </div>
    </div>
  )
}
