import { IconAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import type { Client } from '../types'

const emptyForm = { name: '', company_name: '', email: '', phone: '', city: '', client_type: 'corporate' }

export default function Clients() {
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Client | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => api<Paginated<Client>>('/clients').then((r) => setClients(r.data)).catch(setError)
  useEffect(() => { load(); window.addEventListener('list-query', load); return () => window.removeEventListener('list-query', load) }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (c: Client) => {
    setEditing(c)
    setForm({ name: c.name, company_name: c.company_name ?? '', email: c.email ?? '', phone: c.phone ?? '', city: c.city ?? '', client_type: c.client_type })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body = { ...form, company_name: form.company_name || undefined, email: form.email || undefined, phone: form.phone || undefined, city: form.city || undefined }
    try {
      if (editing) await api(`/clients/${editing.id}`, { method: 'PATCH', body })
      else await api('/clients', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (c: Client) => {
    if (!confirm(`Delete client "${c.name}"? This cannot be undone.`)) return
    setError(null)
    try {
      await api(`/clients/${c.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Clients" sub="Keep client details and event history in one place.">
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>{showForm && !editing ? 'Close' : '+ New client'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit client: ${editing.name}` : 'Add a client'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            <input className={inputCls} placeholder="Company" value={form.company_name} onChange={(e) => setForm({ ...form, company_name: e.target.value })} />
            <input className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
            <select className={inputCls} value={form.client_type} onChange={(e) => setForm({ ...form, client_type: e.target.value })}>
              <option value="corporate">corporate</option><option value="individual">individual</option><option value="association">association</option>
            </select>
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create client'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Code', 'Client', 'Contact', 'City', 'Type', 'Events', 'Status', '']}>
          {clients.map((c) => (
            <tr key={c.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{c.client_code}</td>
              <td className="px-4 py-3 font-medium">{c.name}<div className="text-xs text-slate-400">{c.company_name}</div></td>
              <td className="px-4 py-3 text-slate-600">{c.email}<div className="text-xs text-slate-400">{c.phone}</div></td>
              <td className="px-4 py-3 text-slate-600">{c.city ?? '-'}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{c.client_type}</td>
              <td className="px-4 py-3">{c.events_count ?? '-'}</td>
              <td className="px-4 py-3"><Badge value={c.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <IconAction label="Edit" onClick={() => openEdit(c)}/>
                {isAdmin && <IconAction label="Delete" onClick={() => remove(c)}/>}
              </td>
            </tr>
          ))}
          {clients.length === 0 && (
            <tr><td colSpan={8} className="px-4 py-6 text-center text-slate-400">No clients yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
