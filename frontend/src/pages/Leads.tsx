import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Lead } from '../types'

const STAGES: { key: string; label: string }[] = [
  { key: 'new', label: 'New' },
  { key: 'contacted', label: 'Contacted' },
  { key: 'qualified', label: 'Qualified' },
  { key: 'proposal_sent', label: 'Proposal Sent' },
  { key: 'negotiating', label: 'Negotiating' },
  { key: 'converted', label: 'Converted' },
  { key: 'lost', label: 'Lost' },
]

const emptyForm = { title: '', contact_name: '', email: '', phone: '', source: 'website', event_date: '', estimated_budget: '' }

export default function Leads() {
  const [leads, setLeads] = useState<Lead[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [view, setView] = useState<'board' | 'table'>('board')
  const [form, setForm] = useState(emptyForm)
  const [dragId, setDragId] = useState<number | null>(null)
  const [busyId, setBusyId] = useState<number | null>(null)

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
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const convert = async (id: number) => {
    setError(null)
    setBusyId(id)
    try {
      const res = await api<{ message: string }>(`/leads/${id}/convert`, { method: 'POST' })
      alert(res.message)
      load()
    } catch (e) { setError(e) } finally { setBusyId(null) }
  }

  const moveTo = async (lead: Lead, stage: string) => {
    if (lead.status === stage) return
    setError(null)
    if (stage === 'converted') {
      if (!confirm(`Convert "${lead.title}" into a client?`)) return
      return convert(lead.id)
    }
    if (stage === 'lost' && !confirm(`Mark "${lead.title}" as lost?`)) return
    setBusyId(lead.id)
    try {
      await api(`/leads/${lead.id}/status`, { method: 'POST', body: { status: stage } })
      load()
    } catch (e) { setError(e) } finally { setBusyId(null) }
  }

  return (
    <div>
      <PageHeader title="Leads / CRM" sub="Drag cards between stages · Converted and Lost ask first">
        <button className={btnGhostCls} onClick={() => setView(view === 'board' ? 'table' : 'board')}>
          {view === 'board' ? 'Table view' : 'Board view'}
        </button>
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

      {view === 'board' ? (
        <div className="flex gap-3 overflow-x-auto pb-4">
          {STAGES.map((stage) => {
            const cards = leads.filter((l) => l.status === stage.key || (stage.key === 'new' && !STAGES.some((s) => s.key === l.status)))
            return (
              <div
                key={stage.key}
                className="min-w-[230px] w-[230px] shrink-0"
                onDragOver={(e) => e.preventDefault()}
                onDrop={() => { const lead = leads.find((l) => l.id === dragId); if (lead) moveTo(lead, stage.key); setDragId(null) }}
              >
                <div className="flex items-center justify-between px-1 mb-2">
                  <span className="text-[12px] font-bold uppercase tracking-wide text-pa-900">{stage.label}</span>
                  <span className="text-[11px] font-bold text-pamuted bg-white border border-paline rounded-full px-2 py-0.5">{cards.length}</span>
                </div>
                <div className="space-y-2 min-h-[120px] rounded-xl bg-slate-100/60 p-2">
                  {cards.map((l) => (
                    <div
                      key={l.id}
                      draggable
                      onDragStart={() => setDragId(l.id)}
                      className={`bg-white rounded-xl border border-paline p-3 cursor-grab active:cursor-grabbing ${busyId === l.id ? 'opacity-50' : ''}`}
                    >
                      <div className="font-semibold text-[13px] text-ink leading-snug">{l.title}</div>
                      <div className="text-[11.5px] text-pamuted mt-1">{l.contact_name}</div>
                      <div className="flex items-center justify-between mt-2">
                        <span className="text-[12px] font-bold text-ink">{inr(l.estimated_budget)}</span>
                        {l.event_date && <span className="text-[10.5px] text-pamuted">{l.event_date}</span>}
                      </div>
                      {l.status !== 'converted' && l.status !== 'lost' && (
                        <button className="mt-2 w-full text-[11px] font-bold text-pa-700 border border-paline rounded-lg py-1 hover:bg-pa-50" onClick={() => convert(l.id)}>
                          Convert → client
                        </button>
                      )}
                    </div>
                  ))}
                  {cards.length === 0 && <div className="text-[11px] text-pamuted text-center py-6">Drop leads here</div>}
                </div>
              </div>
            )
          })}
        </div>
      ) : (
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
      )}
    </div>
  )
}
