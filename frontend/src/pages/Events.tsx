import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Client, type EventItem } from '../types'

const STATUSES = ['', 'proposed', 'in_planning', 'confirmed', 'completed', 'cancelled']
const EDIT_STATUSES = STATUSES.slice(1)
const emptyForm = { title: '', client_id: '', start_date: '', end_date: '', contract_value: '', status: 'proposed' }

export default function Events() {
  const [events, setEvents] = useState<EventItem[]>([])
  const [status, setStatus] = useState('')
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<EventItem | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => {
    api<Paginated<EventItem>>(`/events${status ? `?status=${status}` : ''}`)
      .then((r) => setEvents(r.data))
      .catch(setError)
  }

  useEffect(load, [status])
  useEffect(() => {
    api<Paginated<Client>>('/clients').then((r) => setClients(r.data)).catch(() => {})
  }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (e: EventItem) => {
    setEditing(e)
    setForm({
      title: e.title,
      client_id: String(e.client?.id ?? (e as any).client_id ?? ''),
      start_date: String(e.start_date).slice(0, 10),
      end_date: String(e.end_date).slice(0, 10),
      contract_value: e.contract_value ? String(e.contract_value) : '',
      status: e.status,
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      if (editing) {
        await api(`/events/${editing.id}`, {
          method: 'PATCH',
          body: {
            title: form.title,
            client_id: Number(form.client_id),
            start_date: form.start_date,
            end_date: form.end_date,
            contract_value: form.contract_value ? Number(form.contract_value) : undefined,
            status: form.status,
          },
        })
      } else {
        await api('/events', {
          method: 'POST',
          body: {
            title: form.title,
            client_id: Number(form.client_id),
            event_category_id: 1,
            event_type: 'corporate',
            start_date: form.start_date,
            end_date: form.end_date,
            contract_value: form.contract_value ? Number(form.contract_value) : undefined,
            status: 'proposed',
          },
        })
      }
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) {
      setError(e)
    }
  }

  const remove = async (e: EventItem) => {
    if (!confirm(`Delete event "${e.title}"? This cannot be undone.`)) return
    setError(null)
    try {
      await api(`/events/${e.id}`, { method: 'DELETE' })
      load()
    } catch (err) { setError(err) }
  }

  return (
    <div>
      <PageHeader title="Events" sub="GET/POST /api/v1/events">
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>
          {showForm && !editing ? 'Close' : '+ New event'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit event: ${editing.title}` : 'Add an event'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Event title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
            <select className={inputCls} value={form.client_id} onChange={(e) => setForm({ ...form, client_id: e.target.value })} required>
              <option value="">Select client…</option>
              {clients.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <input className={inputCls} placeholder="Contract value (₹)" type="number" value={form.contract_value} onChange={(e) => setForm({ ...form, contract_value: e.target.value })} />
            <div>
              <label className="text-xs text-slate-500">Start date</label>
              <input className={inputCls} type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} required />
            </div>
            <div>
              <label className="text-xs text-slate-500">End date</label>
              <input className={inputCls} type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} required />
            </div>
            {editing && (
              <select className={inputCls} value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                {EDIT_STATUSES.map((s) => <option key={s} value={s}>{s.replace('_', ' ')}</option>)}
              </select>
            )}
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create event'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <div className="flex gap-2 mb-4">
        {STATUSES.map((s) => (
          <button
            key={s}
            onClick={() => setStatus(s)}
            className={`text-xs rounded-full px-3 py-1.5 border ${status === s ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'}`}
          >
            {s === '' ? 'All' : s.replace('_', ' ')}
          </button>
        ))}
      </div>
      <Card>
        <Table head={['Code', 'Event', 'Client', 'Dates', 'Contract value', 'Received', 'Status', '']}>
          {events.map((e) => (
            <tr key={e.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{e.event_code}</td>
              <td className="px-4 py-3">
                <Link to={`/events/${e.id}`} className="text-pa-700 font-semibold hover:underline">{e.title}</Link>
              </td>
              <td className="px-4 py-3 text-slate-600">{e.client?.name ?? '-'}</td>
              <td className="px-4 py-3 text-slate-600 text-xs">{String(e.start_date).slice(0, 10)} → {String(e.end_date).slice(0, 10)}</td>
              <td className="px-4 py-3">{inr(e.contract_value)}</td>
              <td className="px-4 py-3">{inr(e.amount_received)}</td>
              <td className="px-4 py-3"><Badge value={e.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <button className="text-pa-700 font-bold text-xs hover:underline mr-3" onClick={() => openEdit(e)}>Edit</button>
                {isAdmin && <button className="text-rose-600 font-bold text-xs hover:underline" onClick={() => remove(e)}>Delete</button>}
              </td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
