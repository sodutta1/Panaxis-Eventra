import { useEffect, useState } from 'react'
import { api, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Venue } from '../types'

const TYPES = ['banquet_hall', 'hotel', 'lawn', 'resort', 'convention_centre', 'stadium', 'auditorium']
const emptyForm = { name: '', type: 'banquet_hall', city: '', capacity: '', rental_cost_per_day: '', contact_person: '', contact_phone: '', contact_email: '', address: '' }

export default function Venues() {
  const [venues, setVenues] = useState<Venue[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Venue | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => api<Paginated<Venue>>('/venues').then((r) => setVenues(r.data)).catch(setError)
  useEffect(() => { load() }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (v: Venue) => {
    setEditing(v)
    setForm({
      name: v.name,
      type: v.type,
      city: v.city,
      capacity: String(v.capacity),
      rental_cost_per_day: String(v.rental_cost_per_day),
      contact_person: v.contact_person ?? '',
      contact_phone: v.contact_phone ?? '',
      contact_email: v.contact_email ?? '',
      address: v.address ?? '',
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body = {
      name: form.name,
      type: form.type,
      city: form.city,
      capacity: Number(form.capacity),
      rental_cost_per_day: Number(form.rental_cost_per_day),
      contact_person: form.contact_person || undefined,
      contact_phone: form.contact_phone || undefined,
      contact_email: form.contact_email || undefined,
      address: form.address || undefined,
    }
    try {
      if (editing) await api(`/venues/${editing.id}`, { method: 'PATCH', body })
      else await api('/venues', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (v: Venue) => {
    if (!confirm(`Delete venue "${v.name}"?`)) return
    setError(null)
    try {
      await api(`/venues/${v.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Venues" sub="GET/POST /api/v1/venues">
        {isAdmin && (
          <button className={btnCls} onClick={() => (showForm ? setShowForm(false) : openCreate())}>
            {showForm && !editing ? 'Close' : '+ New venue'}
          </button>
        )}
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit venue: ${editing.name}` : 'Add a venue'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Venue name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            <select className={inputCls} value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
              {TYPES.map((t) => <option key={t} value={t}>{t.replaceAll('_', ' ')}</option>)}
            </select>
            <input className={inputCls} placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} required />
            <input className={inputCls} type="number" min={1} placeholder="Capacity" value={form.capacity} onChange={(e) => setForm({ ...form, capacity: e.target.value })} required />
            <input className={inputCls} type="number" min={0} placeholder="Rent per day (₹)" value={form.rental_cost_per_day} onChange={(e) => setForm({ ...form, rental_cost_per_day: e.target.value })} required />
            <input className={inputCls} placeholder="Contact person" value={form.contact_person} onChange={(e) => setForm({ ...form, contact_person: e.target.value })} />
            <input className={inputCls} placeholder="Contact phone" value={form.contact_phone} onChange={(e) => setForm({ ...form, contact_phone: e.target.value })} />
            <input className={inputCls} placeholder="Contact email" value={form.contact_email} onChange={(e) => setForm({ ...form, contact_email: e.target.value })} />
            <input className={inputCls} placeholder="Address" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create venue'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Venue', 'Type', 'City', 'Capacity', 'Rent / day', 'Status', '']}>
          {venues.map((v) => (
            <tr key={v.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-medium">{v.name}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{v.type.replaceAll('_', ' ')}</td>
              <td className="px-4 py-3 text-slate-600">{v.city}</td>
              <td className="px-4 py-3">{v.capacity.toLocaleString('en-IN')}</td>
              <td className="px-4 py-3">{inr(v.rental_cost_per_day)}</td>
              <td className="px-4 py-3"><Badge value={v.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                {isAdmin && (
                  <>
                    <button className="text-pa-700 font-bold text-xs hover:underline mr-3" onClick={() => openEdit(v)}>Edit</button>
                    <button className="text-rose-600 font-bold text-xs hover:underline" onClick={() => remove(v)}>Delete</button>
                  </>
                )}
              </td>
            </tr>
          ))}
          {venues.length === 0 && (
            <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-400">No venues yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
