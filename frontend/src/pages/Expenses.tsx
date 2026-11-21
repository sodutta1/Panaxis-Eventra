import { useEffect, useState } from 'react'
import { api, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type EventItem, type Expense } from '../types'

const METHODS = ['cash', 'upi', 'bank_transfer', 'card', 'cheque']
const emptyForm = { title: '', amount: '', incurred_date: new Date().toISOString().slice(0, 10), payment_method: 'cash', event_id: '', receipt_number: '', notes: '' }

export default function Expenses() {
  const [items, setItems] = useState<Expense[]>([])
  const [events, setEvents] = useState<EventItem[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Expense | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => api<Paginated<Expense>>('/expenses').then((r) => setItems(r.data)).catch(setError)
  useEffect(() => { load() }, [])
  useEffect(() => {
    api<Paginated<EventItem>>('/events').then((r) => setEvents(r.data)).catch(() => {})
  }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (x: Expense) => {
    setEditing(x)
    setForm({
      title: x.title,
      amount: String(x.amount),
      incurred_date: String(x.incurred_date).slice(0, 10),
      payment_method: x.payment_method,
      event_id: (x as any).event_id ? String((x as any).event_id) : '',
      receipt_number: (x as any).receipt_number ?? '',
      notes: (x as any).notes ?? '',
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body = {
      title: form.title,
      amount: Number(form.amount),
      incurred_date: form.incurred_date,
      payment_method: form.payment_method,
      event_id: form.event_id ? Number(form.event_id) : undefined,
      receipt_number: form.receipt_number || undefined,
      notes: form.notes || undefined,
    }
    try {
      if (editing) await api(`/expenses/${editing.id}`, { method: 'PATCH', body })
      else await api('/expenses', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (x: Expense) => {
    if (!confirm(`Delete expense "${x.title}" (${inr(x.amount)})?`)) return
    setError(null)
    try {
      await api(`/expenses/${x.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Expenses" sub="GET/POST /api/v1/expenses">
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>
          {showForm && !editing ? 'Close' : '+ New expense'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit expense: ${editing.title}` : 'Add an expense'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
            <input className={inputCls} type="number" min={1} step="0.01" placeholder="Amount (₹)" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} required />
            <div>
              <label className="text-xs text-slate-500">Date</label>
              <input className={inputCls} type="date" value={form.incurred_date} onChange={(e) => setForm({ ...form, incurred_date: e.target.value })} required />
            </div>
            <select className={inputCls} value={form.payment_method} onChange={(e) => setForm({ ...form, payment_method: e.target.value })}>
              {METHODS.map((m) => <option key={m} value={m}>{m.replaceAll('_', ' ')}</option>)}
            </select>
            <select className={inputCls} value={form.event_id} onChange={(e) => setForm({ ...form, event_id: e.target.value })}>
              <option value="">No linked event</option>
              {events.map((ev2) => <option key={ev2.id} value={ev2.id}>{ev2.title}</option>)}
            </select>
            <input className={inputCls} placeholder="Receipt number" value={form.receipt_number} onChange={(e) => setForm({ ...form, receipt_number: e.target.value })} />
            <input className={inputCls} placeholder="Notes" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create expense'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Number', 'Title', 'Date', 'Method', 'Amount', 'Status', '']}>
          {items.map((x) => (
            <tr key={x.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{x.expense_number}</td>
              <td className="px-4 py-3">{x.title}</td>
              <td className="px-4 py-3 text-slate-600">{x.incurred_date}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{x.payment_method.replaceAll('_', ' ')}</td>
              <td className="px-4 py-3 font-semibold">{inr(x.amount)}</td>
              <td className="px-4 py-3"><Badge value={x.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <button className="text-pa-700 font-bold text-xs hover:underline mr-3" onClick={() => openEdit(x)}>Edit</button>
                {isAdmin && <button className="text-rose-600 font-bold text-xs hover:underline" onClick={() => remove(x)}>Delete</button>}
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-400">No expenses yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
