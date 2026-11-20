import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import { inr, type Client, type EventItem } from '../types'

const STATUSES = ['', 'proposed', 'in_planning', 'confirmed', 'completed', 'cancelled']

export default function Events() {
  const [events, setEvents] = useState<EventItem[]>([])
  const [status, setStatus] = useState('')
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ title: '', client_id: '', start_date: '', end_date: '', contract_value: '' })

  const load = () => {
    api<Paginated<EventItem>>(`/events${status ? `?status=${status}` : ''}`)
      .then((r) => setEvents(r.data))
      .catch(setError)
  }

  useEffect(load, [status])
  useEffect(() => {
    api<Paginated<Client>>('/clients').then((r) => setClients(r.data)).catch(() => {})
  }, [])

  const create = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
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
      setShowForm(false)
      setForm({ title: '', client_id: '', start_date: '', end_date: '', contract_value: '' })
      load()
    } catch (e) {
      setError(e)
    }
  }

  return (
    <div>
      <PageHeader title="Events" sub="GET/POST /api/v1/events">
        <button className={btnCls} onClick={() => setShowForm(!showForm)}>
          {showForm ? 'Close' : '+ New event'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <form onSubmit={create} className="grid md:grid-cols-3 gap-4">
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
            <div className="flex items-end">
              <button className={btnCls}>Create event</button>
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
        <Table head={['Code', 'Event', 'Client', 'Dates', 'Contract value', 'Received', 'Status']}>
          {events.map((e) => (
            <tr key={e.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{e.event_code}</td>
              <td className="px-4 py-3">
                <Link to={`/events/${e.id}`} className="text-indigo-700 font-medium hover:underline">{e.title}</Link>
              </td>
              <td className="px-4 py-3 text-slate-600">{e.client?.name ?? '-'}</td>
              <td className="px-4 py-3 text-slate-600 text-xs">{String(e.start_date).slice(0, 10)} → {String(e.end_date).slice(0, 10)}</td>
              <td className="px-4 py-3">{inr(e.contract_value)}</td>
              <td className="px-4 py-3">{inr(e.amount_received)}</td>
              <td className="px-4 py-3"><Badge value={e.status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
