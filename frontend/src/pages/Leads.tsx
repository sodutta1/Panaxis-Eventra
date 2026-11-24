import { IconAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, getUser, type Paginated } from '../api'
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
  const [view, setView] = useState<'board' | 'table'>('table')
  const [form, setForm] = useState(emptyForm)
  const [editing, setEditing] = useState<Lead | null>(null)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')
  const [dragId, setDragId] = useState<number | null>(null)
  const [busyId, setBusyId] = useState<number | null>(null)

  const load = () => api<Paginated<Lead>>('/leads').then((r) => setLeads(r.data)).catch(setError)
  useEffect(() => { load(); window.addEventListener('list-query', load); return () => window.removeEventListener('list-query', load) }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }

  const openEdit = (l: Lead) => {
    setEditing(l)
    setForm({
      title: l.title,
      contact_name: l.contact_name,
      email: l.email ?? '',
      phone: l.phone ?? '',
      source: l.source,
      event_date: l.event_date ?? '',
      estimated_budget: l.estimated_budget ? String(l.estimated_budget) : '',
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body = { ...form, estimated_budget: form.estimated_budget ? Number(form.estimated_budget) : undefined, event_date: form.event_date || undefined }
    try {
      if (editing) await api(`/leads/${editing.id}`, { method: 'PATCH', body })
      else await api('/leads', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (l: Lead) => {
    if (!confirm(`Delete lead "${l.title}"?`)) return
    setError(null)
    try {
      await api(`/leads/${l.id}`, { method: 'DELETE' })
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
      <PageHeader title="Leads / CRM" sub="Follow enquiries from first contact to confirmed business.">
        <button className={btnGhostCls} onClick={() => setView(view === 'board' ? 'table' : 'board')}>
          {view === 'board' ? 'Table view' : 'Board view'}
        </button>
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>{showForm && !editing ? 'Close' : '+ New lead'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit lead: ${editing.title}` : 'Add a lead'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input aria-label="Lead title" className={inputCls} placeholder="Lead title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
            <input aria-label="Contact name" className={inputCls} placeholder="Contact name" value={form.contact_name} onChange={(e) => setForm({ ...form, contact_name: e.target.value })} required />
            <input aria-label="Email" className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input aria-label="Phone" className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} type="date" value={form.event_date} onChange={(e) => setForm({ ...form, event_date: e.target.value })} />
            <input aria-label="Estimated budget (₹)" className={inputCls} type="number" placeholder="Estimated budget (₹)" value={form.estimated_budget} onChange={(e) => setForm({ ...form, estimated_budget: e.target.value })} />
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create lead'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}

      {view === 'board' ? (
        <div className="flex gap-3 overflow-x-auto pb-4 max-md:flex-col max-md:overflow-visible">
          {STAGES.map((stage) => {
            const cards = leads.filter((l) => l.status === stage.key || (stage.key === 'new' && !STAGES.some((s) => s.key === l.status)))
            return (
              <div
                key={stage.key}
                className="min-w-[230px] w-[230px] shrink-0 max-md:w-full max-md:min-w-0"
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
                      <div className="flex justify-end gap-2 mt-2">
                        <IconAction label="Edit" onClick={() => openEdit(l)}/>
                        {isAdmin && <IconAction label="Delete" onClick={() => remove(l)}/>}
                      </div>
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
                <td className="px-4 py-3 text-slate-600">{l.contact_name}{l.phone && <a className="block text-xs text-pa-700 hover:underline" href={`tel:${l.phone}`}>{l.phone}</a>}{l.email && <a className="block text-xs text-slate-400 hover:underline" href={`mailto:${l.email}`}>{l.email}</a>}</td>
                <td className="px-4 py-3 text-slate-600">{l.source}</td>
                <td className="px-4 py-3 text-slate-600">{l.event_date ?? '-'}</td>
                <td className="px-4 py-3">{inr(l.estimated_budget)}</td>
                <td className="px-4 py-3"><Badge value={l.status} /></td>
                <td className="px-4 py-3 text-right whitespace-nowrap">
                  {l.status !== 'converted' && (
                    <button className={`${btnGhostCls} mr-3`} onClick={() => convert(l.id)}>Convert → client</button>
                  )}
                  <IconAction label="Edit" onClick={() => openEdit(l)}/>
                  {isAdmin && <IconAction label="Delete" onClick={() => remove(l)}/>}
                </td>
              </tr>
            ))}
          </Table>
        </Card>
      )}
    </div>
  )
}
