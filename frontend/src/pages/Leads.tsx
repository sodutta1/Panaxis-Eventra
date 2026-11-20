import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Lead } from '../types'

export default function Leads() {
  const [leads, setLeads] = useState<Lead[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ title: '', contact_name: '', email: '', phone: '', source: 'website', event_date: '', estimated_budget: '' })

  const load = () => api<Paginated<Lead>>('/leads').then((r) => setLeads(r.data)).catch(setError)
  useEffect(() => { load() }, [])

  const create = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      await api('/leads', {
        method: 'POST',
        body: { ...form, estimated_budget: form.estimated_budget ? Number(form.estimated_budget) : undefined, event_date: form.event_date || undefined },
      })
      setShowForm(false)
      setForm({ title: '', contact_name: '', email: '', phone: '', source: 'website', event_date: '', estimated_budget: '' })
      load()
    } catch (e) { setError(e) }
  }

  const convert = async (id: number) => {
    setError(null)
    try {
      const res = await api<{ message: string }>(`/leads/${id}/convert`, { method: 'POST' })
      alert(res.message)
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Leads / CRM" sub="GET/POST /api/v1/leads · convert turns a lead into a client">
        <button className={btnCls} onClick={() => setShowForm(!showForm)}>{showForm ? 'Close' : '+ New lead'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <form onSubmit={create} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Lead title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
            <input className={inputCls} placeholder="Contact name" value={form.contact_name} onChange={(e) => setForm({ ...form, contact_name: e.target.value })} required />
            <input className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} type="date" value={form.event_date} onChange={(e) => setForm({ ...form, event_date: e.target.value })} />
            <input className={inputCls} type="number" placeholder="Estimated budget (₹)" value={form.estimated_budget} onChange={(e) => setForm({ ...form, estimated_budget: e.target.value })} />
            <div><button className={btnCls}>Create lead</button></div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Lead', 'Contact', 'Source', 'Event date', 'Est. budget', 'Status', '']}>
          {leads.map((l) => (
            <tr key={l.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-medium">{l.title}</td>
              <td className="px-4 py-3 text-slate-600">{l.contact_name}<div className="text-xs text-slate-400">{l.email}</div></td>
              <td className="px-4 py-3 text-slate-600">{l.source}</td>
              <td className="px-4 py-3 text-slate-600">{l.event_date ?? '-'}</td>
              <td className="px-4 py-3">{inr(l.estimated_budget)}</td>
              <td className="px-4 py-3"><Badge value={l.status} /></td>
              <td className="px-4 py-3 text-right">
                {l.status !== 'converted' && (
                  <button className={btnGhostCls} onClick={() => convert(l.id)}>Convert → client</button>
                )}
              </td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
