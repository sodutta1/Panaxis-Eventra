import { IconAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, allOptions, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import type { EventItem, Guest } from '../types'

const DIETARY = ['veg', 'non_veg', 'vegan', 'jain']
const RSVP = ['pending', 'confirmed', 'declined']
const emptyForm = { event_id: '', name: '', email: '', phone: '', plus_ones: '', table_number: '', dietary_preference: 'veg', rsvp_status: 'pending' }

export default function Guests() {
  const [items, setItems] = useState<Guest[]>([])
  const [events, setEvents] = useState<EventItem[]>([])
  const [eventId, setEventId] = useState('')
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Guest | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () =>
    api<Paginated<Guest>>(`/guests${eventId ? `?event_id=${eventId}` : ''}`)
      .then((r) => setItems(r.data))
      .catch(setError)
  useEffect(() => { load(); window.addEventListener('list-query',load); return ()=>window.removeEventListener('list-query',load) }, [eventId])
  useEffect(() => {
    allOptions<EventItem>('/events').then((r) => setEvents(r.data)).catch(() => {})
  }, [])

  const checkIn = async (id: number) => {
    // check-in is one-way: the API returns 409 for an already-checked-in guest
    await api(`/guests/${id}/checkin`, { method: 'POST' }).catch(setError)
    load()
  }

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (g: Guest) => {
    setEditing(g)
    setForm({
      event_id: String(g.event_id),
      name: g.name,
      email: g.email ?? '',
      phone: (g as any).phone ?? '',
      plus_ones: String(g.plus_ones ?? ''),
      table_number: g.table_number ?? '',
      dietary_preference: g.dietary_preference,
      rsvp_status: g.rsvp_status,
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body: Record<string, unknown> = {
      event_id: Number(form.event_id),
      name: form.name,
      email: form.email || undefined,
      phone: form.phone || undefined,
      plus_ones: form.plus_ones ? Number(form.plus_ones) : undefined,
      table_number: form.table_number || undefined,
      dietary_preference: form.dietary_preference,
    }
    if (editing) body.rsvp_status = form.rsvp_status
    try {
      if (editing) await api(`/guests/${editing.id}`, { method: 'PATCH', body })
      else await api('/guests', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (g: Guest) => {
    if (!confirm(`Remove guest "${g.name}"?`)) return
    setError(null)
    try {
      await api(`/guests/${g.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Guests & RSVP" sub="Manage invitations, responses and guest check-in.">
        <input className={`${inputCls} w-40`} placeholder="Filter event id" value={eventId} onChange={(e) => setEventId(e.target.value)} />
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>
          {showForm && !editing ? 'Close' : '+ New guest'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6" >
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit guest: ${editing.name}` : 'Add a guest'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <select className={inputCls} value={form.event_id} onChange={(e) => setForm({ ...form, event_id: e.target.value })} required>
              <option value="">Select event…</option>
              {events.map((ev2) => <option key={ev2.id} value={ev2.id}>{ev2.title}</option>)}
            </select>
            <input className={inputCls} placeholder="Guest name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            <input className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} type="number" min={0} placeholder="Plus ones" value={form.plus_ones} onChange={(e) => setForm({ ...form, plus_ones: e.target.value })} />
            <input className={inputCls} placeholder="Table number" value={form.table_number} onChange={(e) => setForm({ ...form, table_number: e.target.value })} />
            <select className={inputCls} value={form.dietary_preference} onChange={(e) => setForm({ ...form, dietary_preference: e.target.value })}>
              {DIETARY.map((d) => <option key={d} value={d}>{d.replaceAll('_', ' ')}</option>)}
            </select>
            {editing && (
              <select className={inputCls} value={form.rsvp_status} onChange={(e) => setForm({ ...form, rsvp_status: e.target.value })}>
                {RSVP.map((r) => <option key={r} value={r}>{r.replaceAll('_', ' ')}</option>)}
              </select>
            )}
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Add guest'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Guest', 'Event', 'Plus ones', 'Table', 'Dietary', 'RSVP', 'Checked in', '']}>
          {items.map((g) => (
            <tr key={g.id} className={g.checked_in ? '' : 'hover:bg-slate-50 cursor-pointer'} onClick={() => { if (!g.checked_in) checkIn(g.id) }}>
              <td className="px-4 py-3 font-medium">{g.name}<div className="text-xs text-slate-400">{g.email}</div></td>
              <td className="px-4 py-3 text-slate-600">#{g.event_id}</td>
              <td className="px-4 py-3">{g.plus_ones}</td>
              <td className="px-4 py-3">{g.table_number ?? '-'}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{g.dietary_preference}</td>
              <td className="px-4 py-3"><Badge value={g.rsvp_status} /></td>
              <td className="px-4 py-3">{g.checked_in ? <Badge value="checked_in" /> : <span className="text-xs text-slate-400">not yet</span>}</td>
              <td className="px-4 py-3 text-right whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                <IconAction label="Edit" onClick={() => openEdit(g)}/>
                {isAdmin && <IconAction label="Delete" onClick={() => remove(g)}/>}
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={8} className="px-4 py-6 text-center text-slate-400">No guests found.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
