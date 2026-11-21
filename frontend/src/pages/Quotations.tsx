import { useEffect, useState } from 'react'
import { api, downloadPdf, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Client, type Quotation } from '../types'

type ItemRow = { item_name: string; quantity: string; unit_price: string }
const emptyItem: ItemRow = { item_name: '', quantity: '1', unit_price: '' }

export default function Quotations() {
  const [items, setItems] = useState<Quotation[]>([])
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ client_id: '', issue_date: new Date().toISOString().slice(0, 10), valid_until: '', tax_rate: '18', notes: '' })
  const [rows, setRows] = useState<ItemRow[]>([{ ...emptyItem }])
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => api<Paginated<Quotation>>('/quotations').then((r) => setItems(r.data)).catch(setError)
  useEffect(() => { load() }, [])
  useEffect(() => {
    api<Paginated<Client>>('/clients').then((r) => setClients(r.data)).catch(() => {})
  }, [])

  const setRow = (i: number, patch: Partial<ItemRow>) => setRows((rs) => rs.map((r, j) => (j === i ? { ...r, ...patch } : r)))

  const create = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      await api('/quotations', {
        method: 'POST',
        body: {
          client_id: Number(form.client_id),
          issue_date: form.issue_date,
          valid_until: form.valid_until || undefined,
          tax_rate: Number(form.tax_rate),
          notes: form.notes || undefined,
          items: rows.map((r) => ({ item_name: r.item_name, quantity: Number(r.quantity), unit_price: Number(r.unit_price) })),
        },
      })
      setShowForm(false)
      setForm({ client_id: '', issue_date: new Date().toISOString().slice(0, 10), valid_until: '', tax_rate: '18', notes: '' })
      setRows([{ ...emptyItem }])
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (q: Quotation) => {
    if (!confirm(`Delete quotation ${q.quotation_number}?`)) return
    setError(null)
    try {
      await api(`/quotations/${q.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Quotations" sub="GET/POST /api/v1/quotations">
        <button className={btnCls} onClick={() => setShowForm(!showForm)}>{showForm ? 'Close' : '+ New quotation'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <form onSubmit={create} className="space-y-4">
            <div className="grid md:grid-cols-4 gap-4">
              <select className={inputCls} value={form.client_id} onChange={(e) => setForm({ ...form, client_id: e.target.value })} required>
                <option value="">Select client…</option>
                {clients.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <div>
                <label className="text-xs text-slate-500">Issue date</label>
                <input className={inputCls} type="date" value={form.issue_date} onChange={(e) => setForm({ ...form, issue_date: e.target.value })} required />
              </div>
              <div>
                <label className="text-xs text-slate-500">Valid until</label>
                <input className={inputCls} type="date" value={form.valid_until} onChange={(e) => setForm({ ...form, valid_until: e.target.value })} />
              </div>
              <input className={inputCls} type="number" min={0} step="0.01" placeholder="Tax rate %" value={form.tax_rate} onChange={(e) => setForm({ ...form, tax_rate: e.target.value })} required />
            </div>
            <div>
              <div className="text-xs font-bold text-pamuted uppercase tracking-wide mb-2">Line items</div>
              {rows.map((r, i) => (
                <div key={i} className="grid md:grid-cols-[1fr_110px_140px_36px] gap-2 mb-2">
                  <input className={inputCls} placeholder="Item name" value={r.item_name} onChange={(e) => setRow(i, { item_name: e.target.value })} required />
                  <input className={inputCls} type="number" min={1} placeholder="Qty" value={r.quantity} onChange={(e) => setRow(i, { quantity: e.target.value })} required />
                  <input className={inputCls} type="number" min={0} step="0.01" placeholder="Unit price (₹)" value={r.unit_price} onChange={(e) => setRow(i, { unit_price: e.target.value })} required />
                  <button type="button" className="text-rose-600 font-bold text-xs hover:underline disabled:opacity-30" disabled={rows.length === 1} onClick={() => setRows((rs) => rs.filter((_, j) => j !== i))}>✕</button>
                </div>
              ))}
              <button type="button" className={btnGhostCls} onClick={() => setRows((rs) => [...rs, { ...emptyItem }])}>+ Add line</button>
            </div>
            <input className={inputCls} placeholder="Notes (optional)" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            <div><button className={btnCls}>Create quotation</button></div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Number', 'Client', 'Issued', 'Valid until', 'Subtotal', 'Tax', 'Grand total', 'Status', '']}>
          {items.map((q) => (
            <tr key={q.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{q.quotation_number}</td>
              <td className="px-4 py-3">{q.client?.name ?? `#${(q as any).client_id}`}</td>
              <td className="px-4 py-3 text-slate-600">{q.issue_date}</td>
              <td className="px-4 py-3 text-slate-600">{q.valid_until ?? '-'}</td>
              <td className="px-4 py-3">{inr(q.subtotal)}</td>
              <td className="px-4 py-3">{inr(q.tax_amount)} ({q.tax_rate}%)</td>
              <td className="px-4 py-3 font-semibold">{inr(q.grand_total)}</td>
              <td className="px-4 py-3"><Badge value={q.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <button
                  className="text-pa-700 font-bold text-xs hover:underline mr-3"
                  onClick={() => downloadPdf(`/quotations/${q.id}/pdf`, `${q.quotation_number}.pdf`).catch(setError)}
                >
                  PDF
                </button>
                {isAdmin && <button className="text-rose-600 font-bold text-xs hover:underline" onClick={() => remove(q)}>Delete</button>}
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={9} className="px-4 py-6 text-center text-slate-400">No quotations yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
