import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type Invoice } from '../types'

export default function Invoices() {
  const [items, setItems] = useState<Invoice[]>([])
  const [error, setError] = useState<unknown>(null)
  const [payFor, setPayFor] = useState<Invoice | null>(null)
  const [pay, setPay] = useState({ amount: '', payment_date: new Date().toISOString().slice(0, 10), payment_method: 'bank_transfer', transaction_reference: '' })

  const load = () => api<Paginated<Invoice>>('/invoices').then((r) => setItems(r.data)).catch(setError)
  useEffect(() => { load() }, [])

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

  return (
    <div>
      <PageHeader title="Invoices & Payments" sub="GET /api/v1/invoices · payments are capped at the balance due by the API" />
      <ErrorBox error={error} />
      {payFor && (
        <Card className="p-5 mb-6 border-indigo-300">
          <div className="text-sm mb-3">
            Recording payment for <b>{payFor.invoice_number}</b> · balance due <b>{inr(payFor.balance_due)}</b>
          </div>
          <form onSubmit={record} className="grid md:grid-cols-5 gap-3">
            <input className={inputCls} type="number" placeholder="Amount (₹)" value={pay.amount} onChange={(e) => setPay({ ...pay, amount: e.target.value })} required max={payFor.balance_due} />
            <input className={inputCls} type="date" value={pay.payment_date} onChange={(e) => setPay({ ...pay, payment_date: e.target.value })} required />
            <select className={inputCls} value={pay.payment_method} onChange={(e) => setPay({ ...pay, payment_method: e.target.value })}>
              <option value="bank_transfer">bank transfer</option><option value="upi">UPI</option><option value="cash">cash</option><option value="card">card</option><option value="cheque">cheque</option>
            </select>
            <input className={inputCls} placeholder="UTR / reference" value={pay.transaction_reference} onChange={(e) => setPay({ ...pay, transaction_reference: e.target.value })} />
            <div className="flex gap-2">
              <button className={btnCls}>Record</button>
              <button type="button" className={btnGhostCls} onClick={() => setPayFor(null)}>Cancel</button>
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Number', 'Client', 'Due date', 'Total', 'Paid', 'Balance', 'Status', '']}>
          {items.map((inv) => (
            <tr key={inv.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{inv.invoice_number}</td>
              <td className="px-4 py-3">{inv.client?.name ?? `#${inv.client_id}`}</td>
              <td className="px-4 py-3 text-slate-600">{inv.due_date}</td>
              <td className="px-4 py-3">{inr(inv.grand_total)}</td>
              <td className="px-4 py-3">{inr(inv.paid_amount)}</td>
              <td className="px-4 py-3 font-semibold">{inr(inv.balance_due)}</td>
              <td className="px-4 py-3"><Badge value={inv.status} /></td>
              <td className="px-4 py-3 text-right">
                {inv.status !== 'paid' && (
                  <button className={btnGhostCls} onClick={() => setPayFor(inv)}>Record payment</button>
                )}
              </td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
