import { useCallback, useEffect, useMemo, useState } from 'react'
import { ArrowDown, ArrowUp } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, allOptions } from '../api'
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

type SortDir = 'asc' | 'desc'
const PRIORITY_RANK: Record<string, number> = { urgent: 0, high: 1, medium: 2, low: 3 }
const PAGE = 6

function useControls<T>(rows: T[], text: (r: T) => string, sortValue: (r: T, key: string) => string | number, initial: string) {
  const [q, setQ] = useState('')
  const [key, setKey] = useState(initial)
  const [dir, setDir] = useState<SortDir>('asc')
  const [limit, setLimit] = useState(PAGE)
  const visible = useMemo(() => {
    const needle = q.trim().toLowerCase()
    const filtered = needle ? rows.filter((r) => text(r).toLowerCase().includes(needle)) : rows
    const sorted = [...filtered].sort((a, b) => {
      const x = sortValue(a, key), y = sortValue(b, key)
      const c = typeof x === 'number' && typeof y === 'number' ? x - y : String(x).localeCompare(String(y))
      return dir === 'asc' ? c : -c
    })
    return { total: filtered.length, rows: sorted.slice(0, limit) }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rows, q, key, dir, limit])
  const toggle = (k: string) => {
    if (k === key) setDir(dir === 'asc' ? 'desc' : 'asc')
    else { setKey(k); setDir('asc') }
  }
  return { q, setQ: (v: string) => { setQ(v); setLimit(PAGE) }, key, dir, toggle, visible, more: () => setLimit(limit + PAGE), less: () => setLimit(PAGE), limit }
}

function SortTh({ label, k, c, className = '' }: { label: string; k: string; c: { key: string; dir: SortDir; toggle: (k: string) => void }; className?: string }) {
  const active = c.key === k
  return (
    <th className={`py-2.5 font-bold ${className}`} aria-sort={active ? (c.dir === 'asc' ? 'ascending' : 'descending') : 'none'}>
      <button type="button" onClick={() => c.toggle(k)} className="inline-flex items-center gap-1 uppercase tracking-wide font-bold">
        {label}
        {active && (c.dir === 'asc' ? <ArrowUp size={12} aria-hidden /> : <ArrowDown size={12} aria-hidden />)}
      </button>
    </th>
  )
}

function ListFooter({ c }: { c: { visible: { total: number; rows: unknown[] }; more: () => void; less: () => void; limit: number } }) {
  const shown = c.visible.rows.length
  if (c.visible.total <= PAGE) return null
  return (
    <div className="flex items-center justify-between pt-3 text-xs text-pamuted">
      <span>Showing {shown} of {c.visible.total}</span>
      <span className="flex gap-2">
        {shown < c.visible.total && <button type="button" onClick={c.more} className="font-bold text-pa-700 hover:underline">Show more</button>}
        {c.limit > PAGE && <button type="button" onClick={c.less} className="font-bold text-pa-700 hover:underline">Show fewer</button>}
      </span>
    </div>
  )
}

export default function Dashboard() {
  const [data, setData] = useState<DashData | null>(null)
  const [eventNames, setEventNames] = useState<Record<number, string>>({})
  const [error, setError] = useState<unknown>(null)
  const [busyTask, setBusyTask] = useState<number | null>(null)
  const evRows = useMemo(() => data?.upcoming_events ?? [], [data])
  const taskRows = useMemo(() => data?.pending_tasks ?? [], [data])
  const evC = useControls<EventItem>(
    evRows,
    (e) => `${e.title} ${e.client?.name ?? ''} ${e.venue?.name ?? ''} ${e.status}`,
    (e, k) => (k === 'title' ? e.title : k === 'client' ? e.client?.name ?? '' : k === 'venue' ? e.venue?.name ?? '' : k === 'status' ? e.status : String(e.start_date)),
    'date',
  )
  const taskC = useControls<Task>(
    taskRows,
    (t) => `${t.title} ${eventNames[t.event_id ?? -1] ?? ''} ${t.assigned_user?.name ?? ''} ${t.priority}`,
    (t, k) => (k === 'title' ? t.title : k === 'event' ? eventNames[t.event_id ?? -1] ?? '' : k === 'owner' ? t.assigned_user?.name ?? '' : k === 'priority' ? PRIORITY_RANK[t.priority] ?? 9 : String((t as { due_date?: string }).due_date ?? '9999')),
    'priority',
  )

  const load = useCallback(() => {
    api<{ data: DashData }>('/dashboard').then((r) => setData(r.data)).catch(setError)
    allOptions<EventItem>('/events?per_page=100')
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
        <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
          <h2 className="text-[16px] font-bold text-pa-900">Upcoming events</h2>
          <input type="search" aria-label="Search upcoming events" placeholder="Search events" value={evC.q} onChange={(e) => evC.setQ(e.target.value)} className="rounded-full border border-paline px-3 py-1.5 text-xs" />
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-[13.5px]">
            <thead>
              <tr className="text-left text-[11.5px] uppercase tracking-wide text-pamuted border-b border-paline">
                <SortTh label="Event" k="title" c={evC} className="pr-4" />
                <SortTh label="Client" k="client" c={evC} className="pr-4" />
                <SortTh label="Date" k="date" c={evC} className="pr-4" />
                <SortTh label="Venue" k="venue" c={evC} className="pr-4" />
                <SortTh label="Status" k="status" c={evC} className="" />
              </tr>
            </thead>
            <tbody>
              {evC.visible.rows.map((e) => (
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
              {evC.visible.rows.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-6 text-center text-pamuted">
                    {evC.q ? 'No events match your search' : 'No upcoming events'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <ListFooter c={evC} />
      </Card>

      <Card className="p-5">
        <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
          <h2 className="text-[16px] font-bold text-pa-900">Urgent tasks</h2>
          <input type="search" aria-label="Search tasks" placeholder="Search tasks" value={taskC.q} onChange={(e) => taskC.setQ(e.target.value)} className="rounded-full border border-paline px-3 py-1.5 text-xs" />
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-[13.5px]">
            <thead>
              <tr className="text-left text-[11.5px] uppercase tracking-wide text-pamuted border-b border-paline">
                <SortTh label="Task" k="title" c={taskC} className="pr-4" />
                <SortTh label="Event" k="event" c={taskC} className="pr-4" />
                <SortTh label="Owner" k="owner" c={taskC} className="pr-4" />
                <SortTh label="Priority" k="priority" c={taskC} className="pr-4" />
                <th className="py-2.5 font-bold text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              {taskC.visible.rows.map((t) => (
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
              {taskC.visible.rows.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-6 text-center text-pamuted">
                    {taskC.q ? 'No tasks match your search' : 'Nothing pending. All caught up.'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <ListFooter c={taskC} />
      </Card>

      <div className="mt-5 text-xs text-pamuted">
        Money booked across all events: {inr(data.total_revenue)} · Guests checked in: {data.checked_in_guests} /{' '}
        {data.total_guests}
      </div>
    </div>
  )
}
