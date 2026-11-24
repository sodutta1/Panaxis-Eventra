import { PdfAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, allOptions, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Client, type Invoice } from '../types'

type ItemRow = { description: string; quantity: string; unit_price: string }
const emptyItem: ItemRow = { description: '', quantity: '1', unit_price: '' }

export default function Invoices() {
  const [items, setItems] = useState<Invoice[]>([])
  const [clients, setClients] = useState<Client[]>([])
  const [error, setError] = useState<unknown>(null)
  const [payFor, setPayFor] = useState<Invoice | null>(null)
  const [pay, setPay] = useState({ amount: '', payment_date: new Date().toISOString().slice(0, 10), payment_method: 'bank_transfer', transaction_reference: '' })
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ client_id: '', invoice_date: new Date().toISOString().slice(0, 10), due_date: '', tax_rate: '18', notes: '' })
  const [rows, setRows] = useState<ItemRow[]>([{ ...emptyItem }])

  const load = () => api<Paginated<Invoice>>('/invoices').then((r) => setItems(r.data)).catch(setError)
  useEffect(() => { load(); window.addEventListener('list-query', load); return () => window.removeEventListener('list-query', load) }, [])
  useEffect(() => {
    allOptions<Client>('/clients').then((r) => setClients(r.data)).catch(() => {})
  }, [])

  const record = async (ev: React.FormEvent) => {
    ev.preventDefault()
    if (!payFor) return
    setError(null)
    try {
      await api(`/invoices/${payFor.id}/payments`, {
        method: 'POST',
        body: { ...pay, amount: Number(pay.amount), transaction_reference: pay.transaction_reference || undefined },
      })
      setPayFor(null)
      setPay({ amount: '', payment_date: new Date().toISOString().slice(0, 10), payment_method: 'bank_transfer', transaction_reference: '' })
      load()
    } catch (e) { setError(e) }
  }

  const setRow = (i: number, patch: Partial<ItemRow>) => setRows((rs) => rs.map((r, j) => (j === i ? { ...r, ...patch } : r)))

  const create = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      await api('/invoices', {
        method: 'POST',
        body: {
          client_id: Number(form.client_id),
          invoice_date: form.invoice_date,
          due_date: form.due_date,
          tax_rate: Number(form.tax_rate),
          notes: form.notes || undefined,
          items: rows.map((r) => ({ description: r.description, quantity: Number(r.quantity), unit_price: Number(r.unit_price) })),
        },
      })
      setShowForm(false)
      setForm({ client_id: '', invoice_date: new Date().toISOString().slice(0, 10), due_date: '', tax_rate: '18', notes: '' })
      setRows([{ ...emptyItem }])
      load()
    } catch (e) { setError(e) }
  }


  return (
    <div>
      <PageHeader title="Invoices & Payments" sub="Track invoices, due dates and client payments.">
        <button className={btnCls} onClick={() => setShowForm(!showForm)}>{showForm ? 'Close' : '+ New invoice'}</button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">Create an invoice</div>
          <form onSubmit={create} className="space-y-4">
            <div className="grid md:grid-cols-4 gap-4">
              <select className={inputCls} value={form.client_id} onChange={(e) => setForm({ ...form, client_id: e.target.value })} required>
                <option value="">Select client…</option>
                {clients.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <div>
                <label className="text-xs text-slate-500">Invoice date</label>
                <input className={inputCls} type="date" value={form.invoice_date} onChange={(e) => setForm({ ...form, invoice_date: e.target.value })} required />
              </div>
              <div>
                <label className="text-xs text-slate-500">Due date</label>
                <input className={inputCls} type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} required />
              </div>
              <input aria-label="Tax rate %" className={inputCls} type="number" min={0} step="0.01" placeholder="Tax rate %" value={form.tax_rate} onChange={(e) => setForm({ ...form, tax_rate: e.target.value })} required />
            </div>
            <div>
              <div className="text-xs font-bold text-pamuted uppercase tracking-wide mb-2">Line items</div>
              {rows.map((r, i) => (
                <div key={i} className="grid md:grid-cols-[1fr_110px_140px_36px] gap-2 mb-2">
                  <input aria-label="Description" className={inputCls} placeholder="Description" value={r.description} onChange={(e) => setRow(i, { description: e.target.value })} required />
                  <input aria-label="Qty" className={inputCls} type="number" min={1} placeholder="Qty" value={r.quantity} onChange={(e) => setRow(i, { quantity: e.target.value })} required />
                  <input aria-label="Unit price (₹)" className={inputCls} type="number" min={0} step="0.01" placeholder="Unit price (₹)" value={r.unit_price} onChange={(e) => setRow(i, { unit_price: e.target.value })} required />
                  <button type="button" className="text-rose-600 font-bold text-xs hover:underline disabled:opacity-30" disabled={rows.length === 1} onClick={() => setRows((rs) => rs.filter((_, j) => j !== i))}>✕</button>
                </div>
              ))}
              <button type="button" className={btnGhostCls} onClick={() => setRows((rs) => [...rs, { ...emptyItem }])}>+ Add line</button>
            </div>
            <input aria-label="Notes (optional)" className={inputCls} placeholder="Notes (optional)" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            <div><button className={btnCls}>Create invoice</button></div>
          </form>
        </Card>
      )}
      {payFor && (
        <Card className="p-5 mb-6 border-indigo-300">
          <div className="text-sm mb-3">
            Recording payment for <b>{payFor.invoice_number}</b> · balance due <b>{inr(payFor.balance_due)}</b>
          </div>
          <form onSubmit={record} className="grid md:grid-cols-5 gap-3">
            <input aria-label="Amount (₹)" className={inputCls} type="number" placeholder="Amount (₹)" value={pay.amount} onChange={(e) => setPay({ ...pay, amount: e.target.value })} required max={payFor.balance_due} />
            <input className={inputCls} type="date" value={pay.payment_date} onChange={(e) => setPay({ ...pay, payment_date: e.target.value })} required />
            <select className={inputCls} value={pay.payment_method} onChange={(e) => setPay({ ...pay, payment_method: e.target.value })}>
              <option value="bank_transfer">bank transfer</option><option value="upi">UPI</option><option value="cash">cash</option><option value="card">card</option><option value="cheque">cheque</option>
            </select>
            <input aria-label="UTR / reference" className={inputCls} placeholder="UTR / reference" value={pay.transaction_reference} onChange={(e) => setPay({ ...pay, transaction_reference: e.target.value })} />
            <div className="flex gap-2">
              <button className={btnCls}>Record</button>
              <button type="button" className={btnGhostCls} onClick={() => setPayFor(null)}>Cancel</button>
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Number', 'Client', 'Due date', 'Total', 'Paid', 'Balance', 'Status', '', '']}>
          {items.map((inv) => (
            <tr key={inv.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{inv.invoice_number}</td>
              <td className="px-4 py-3">{inv.client?.name ?? `#${inv.client_id}`}</td>
              <td className="px-4 py-3 text-slate-600">{inv.due_date}</td>
              <td className="px-4 py-3">{inr(inv.grand_total)}</td>
              <td className="px-4 py-3">{inr(inv.paid_amount)}</td>
              <td className="px-4 py-3 font-semibold">{inr(inv.balance_due)}</td>
              <td className="px-4 py-3"><Badge value={inv.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                {inv.status !== 'paid' && (
                  <button className={btnGhostCls} onClick={() => setPayFor(inv)}>Record payment</button>
                )}
              </td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <PdfAction path={`/invoices/${inv.id}/pdf`} name={`${inv.invoice_number}.pdf`}/>
                
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={9} className="px-4 py-6 text-center text-slate-400">No invoices yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
