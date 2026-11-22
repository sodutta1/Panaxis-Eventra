import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api } from '../api'
import { Badge, Card, ErrorBox, PageHeader, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type EventItem, type Task } from '../types'

const NEXT: Record<string, string> = {
  proposed: 'in_planning',
  in_planning: 'confirmed',
  confirmed: 'completed',
}

export default function EventDetail() {
  const { id } = useParams()
  const [event, setEvent] = useState<EventItem | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [sched, setSched] = useState({ start_time: '', title: '', location_within_venue: '' })
  const [task, setTask] = useState({ title: '', priority: 'medium', due_date: '', phase: 'pre_event' })
  const [phases, setPhases] = useState<Record<string, Task[]>>({})
  const [taskError, setTaskError] = useState('')

  const load = () => {
    api<{ data: EventItem }>(`/events/${id}`).then((r) => setEvent(r.data)).catch(setError)
    api<{ data: Record<string, Task[]> }>(`/events/${id}/tasks`).then((r) => setPhases(r.data)).catch(() => {})
  }
  useEffect(load, [id])

  if (error) return <ErrorBox error={error} />
  if (!event) return <div className="text-slate-500">Loading event…</div>

  const advance = async () => {
    const next = NEXT[event.status]
    if (!next) return
    await api(`/events/${event.id}/status`, { method: 'POST', body: { status: next } })
    load()
  }

  const addSchedule = async (ev: React.FormEvent) => {
    ev.preventDefault()
    await api(`/events/${event.id}/schedules`, { method: 'POST', body: sched })
    setSched({ start_time: '', title: '', location_within_venue: '' })
    load()
  }

  const addTask = async (ev: React.FormEvent) => {
    ev.preventDefault()
    await api(`/events/${event.id}/tasks`, { method: 'POST', body: task })
    setTask({ title: '', priority: 'medium', due_date: '', phase: 'pre_event' })
    load()
  }

  const toggle = async (taskId: number) => {
    setTaskError('')
    try {
      await api(`/tasks/${taskId}/toggle`, { method: 'POST' })
    } catch (e) {
      setTaskError((e as Error).message)
    }
    load()
  }

  return (
    <div>
      <PageHeader title={event.title} titleClassName="text-pa-700" sub={`${event.event_code} · ${event.client?.name ?? ''} · ${event.venue?.name ?? 'venue TBD'}`}>
        {NEXT[event.status] && (
          <button className={btnCls} onClick={advance}>
            Move to {NEXT[event.status].replace('_', ' ')} →
          </button>
        )}
      </PageHeader>
      <ErrorBox error={error} />
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="p-4"><div className="text-xs text-slate-500">Status</div><div className="mt-1"><Badge value={event.status} /></div></Card>
        <Card className="p-4"><div className="text-xs text-slate-500">Dates</div><div className="font-semibold text-sm mt-1">{String(event.start_date).slice(0, 10)} → {String(event.end_date).slice(0, 10)}</div></Card>
        <Card className="p-4"><div className="text-xs text-slate-500">Contract value</div><div className="font-semibold mt-1">{inr(event.contract_value)}</div></Card>
        <Card className="p-4"><div className="text-xs text-slate-500">Amount received</div><div className="font-semibold mt-1">{inr(event.amount_received)}</div></Card>
      </div>
      <div className="grid lg:grid-cols-2 gap-6">
        <Card className="p-5">
          <h2 className="font-semibold mb-3">Run of show</h2>
          <ul className="space-y-2 mb-4">
            {(event.schedules ?? []).map((s) => (
              <li key={s.id} className="text-sm flex gap-3">
                <span className="font-mono text-xs text-slate-500 w-24 shrink-0">{String(s.start_time).slice(0, 5)}{s.end_time ? `–${String(s.end_time).slice(0, 5)}` : ''}</span>
                <span>{s.title}{s.location_within_venue ? <span className="text-slate-400"> · {s.location_within_venue}</span> : null}</span>
              </li>
            ))}
            {(event.schedules ?? []).length === 0 && <li className="text-sm text-slate-400">No schedule items yet.</li>}
          </ul>
          <form onSubmit={addSchedule} className="flex gap-2">
            <input className={`${inputCls} w-28`} type="time" value={sched.start_time} onChange={(e) => setSched({ ...sched, start_time: e.target.value })} required />
            <input className={inputCls} placeholder="Segment title" value={sched.title} onChange={(e) => setSched({ ...sched, title: e.target.value })} required />
            <input className={inputCls} placeholder="Location" value={sched.location_within_venue} onChange={(e) => setSched({ ...sched, location_within_venue: e.target.value })} />
            <button className={btnGhostCls}>Add</button>
          </form>
        </Card>
        <Card className="p-5">
          <h2 className="font-semibold mb-3">Tasks management</h2>
          {taskError && <p className="text-xs text-rose-600 mb-2">{taskError}</p>}
          {([['pre_event', 'Pre-event tasks'], ['on_event', 'On-event tasks'], ['post_event', 'Post-event tasks']] as const).map(([phase, label]) => (
            <div key={phase} className="mb-4">
              <div className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">{label} <span className="text-slate-400">({(phases[phase] ?? []).length})</span></div>
              <ul className="space-y-1.5">
                {(phases[phase] ?? []).map((t) => (
                  <li key={t.id} className="text-sm flex items-center gap-3">
                    <input type="checkbox" checked={t.status === 'completed'} onChange={() => toggle(t.id)} className="rounded" disabled={t.blocked} />
                    <span className={t.status === 'completed' ? 'line-through text-slate-400' : ''}>{t.title}</span>
                    {t.blocked && <span className="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">blocked</span>}
                    <span className={`text-[10px] px-1.5 py-0.5 rounded ${t.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : t.priority === 'high' ? 'bg-amber-100 text-amber-800' : t.priority === 'medium' ? 'bg-sky-100 text-sky-800' : 'bg-slate-200 text-slate-600'}`}>{t.priority}</span>
                    <span className="text-xs text-slate-400 ml-auto">{t.due_date ? String(t.due_date).slice(0, 10) : ''}{t.assigned_user ? ` · ${t.assigned_user.name}` : ''}</span>
                  </li>
                ))}
                {(phases[phase] ?? []).length === 0 && <li className="text-xs text-slate-400">No tasks.</li>}
              </ul>
            </div>
          ))}
          <form onSubmit={addTask} className="flex gap-2 flex-wrap">
            <input className={inputCls} placeholder="Task title" value={task.title} onChange={(e) => setTask({ ...task, title: e.target.value })} required />
            <select className={`${inputCls} w-32`} value={task.phase} onChange={(e) => setTask({ ...task, phase: e.target.value })}>
              <option value="pre_event">pre-event</option><option value="on_event">on-event</option><option value="post_event">post-event</option>
            </select>
            <select className={`${inputCls} w-28`} value={task.priority} onChange={(e) => setTask({ ...task, priority: e.target.value })}>
              <option value="low">low</option><option value="medium">medium</option><option value="high">high</option><option value="urgent">urgent</option>
            </select>
            <input className={`${inputCls} w-36`} type="date" value={task.due_date} onChange={(e) => setTask({ ...task, due_date: e.target.value })} />
            <button className={btnGhostCls}>Add task</button>
          </form>
        </Card>
      </div>
    </div>
  )
}
