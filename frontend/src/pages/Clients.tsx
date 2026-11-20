import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import type { Client } from '../types'

export default function Clients() {
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ name: '', company_name: '', email: '', phone: '', city: '', client_type: 'corporate' })

  const load = () => api<Paginated<Client>>('/clients').then((r) => setClients(r.data)).catch(setError)
  useEffect(() => { load() }, [])

  const create = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      await api('/clients', { method: 'POST', body: form })
      setShowForm(false)
      setForm({ name: '', company_name: '', email: '', phone: '', city: '', client_type: 'corporate' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Clients" sub="GET/POST /api/v1/clients">
        <button className={btnCls} onClick={() => setShowForm(!showForm)}>{showForm ? 'Close' : '+ New client'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <form onSubmit={create} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            <input className={inputCls} placeholder="Company" value={form.company_name} onChange={(e) => setForm({ ...form, company_name: e.target.value })} />
            <input className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
            <select className={inputCls} value={form.client_type} onChange={(e) => setForm({ ...form, client_type: e.target.value })}>
              <option value="corporate">corporate</option><option value="individual">individual</option><option value="association">association</option>
            </select>
            <div><button className={btnCls}>Create client</button></div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Code', 'Client', 'Contact', 'City', 'Type', 'Events', 'Status']}>
          {clients.map((c) => (
            <tr key={c.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{c.client_code}</td>
              <td className="px-4 py-3 font-medium">{c.name}<div className="text-xs text-slate-400">{c.company_name}</div></td>
              <td className="px-4 py-3 text-slate-600">{c.email}<div className="text-xs text-slate-400">{c.phone}</div></td>
              <td className="px-4 py-3 text-slate-600">{c.city ?? '-'}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{c.client_type}</td>
              <td className="px-4 py-3">{c.events_count ?? '-'}</td>
              <td className="px-4 py-3"><Badge value={c.status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
