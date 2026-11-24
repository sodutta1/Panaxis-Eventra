import { IconAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, allOptions, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import { inr, type EventItem, type Ticket, type TicketType } from '../types'

type VerifyResult = { status: 'success' | 'warning' | 'error'; message: string }

const PAYMENT_STATUSES = ['paid', 'pending', 'complimentary']
const emptyTicketForm = { event_id: '', ticket_type_id: '', holder_name: '', holder_email: '', holder_phone: '', quantity: '1', price_paid: '', payment_status: 'paid' }
const emptyTypeForm = { event_id: '', name: '', price: '', total_capacity: '' }

export default function Tickets() {
  const [items, setItems] = useState<Ticket[]>([])
  const [events, setEvents] = useState<EventItem[]>([])
  const [types, setTypes] = useState<TicketType[]>([])
  const [error, setError] = useState<unknown>(null)
  const [token, setToken] = useState('')
  const [result, setResult] = useState<VerifyResult | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [showTypeForm, setShowTypeForm] = useState(false)
  const [editing, setEditing] = useState<Ticket | null>(null)
  const [form, setForm] = useState(emptyTicketForm)
  const [typeForm, setTypeForm] = useState(emptyTypeForm)

  const load = () => api<Paginated<Ticket>>('/tickets').then((r) => setItems(r.data)).catch(setError)
  const loadTypes = () => api<{ data: TicketType[] }>('/tickets/types').then((r) => setTypes(r.data)).catch(() => {})
  useEffect(() => { load(); loadTypes() }, [])
  useEffect(() => {
    allOptions<EventItem>('/events').then((r) => setEvents(r.data)).catch(() => {})
  }, [])

  const verify = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setResult(null)
    try {
      const res = await fetchResult(token)
      setResult(res)
      load()
    } catch (e) {
      setResult(e as VerifyResult)
    }
  }

  const fetchResult = async (t: string): Promise<VerifyResult> => {
    // verify returns 404/409 for invalid/duplicate - those are business results, not failures
    return api<VerifyResult>('/tickets/verify', { method: 'POST', body: { token: t } }).catch(async (e) => {
      if ((e as any).status === 404 || (e as any).status === 409) {
        const status = (e as any).status === 404 ? 'error' : 'warning'
        return { status, message: (e as Error).message } as VerifyResult
      }
      throw e
    })
  }

  const openCreate = () => { setEditing(null); setForm(emptyTicketForm); setShowForm(true) }
  const openEdit = (t: Ticket) => {
    setEditing(t)
    setForm({
      event_id: String((t as any).event_id ?? t.event?.id ?? ''),
      ticket_type_id: String((t as any).ticket_type_id ?? t.ticket_type?.id ?? ''),
      holder_name: t.holder_name,
      holder_email: (t as any).holder_email ?? '',
      holder_phone: (t as any).holder_phone ?? '',
      quantity: '1',
      price_paid: String(t.price_paid ?? ''),
      payment_status: t.payment_status,
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      if (editing) {
        await api(`/tickets/${editing.id}`, {
          method: 'PATCH',
          body: {
            holder_name: form.holder_name,
            holder_email: form.holder_email || undefined,
            holder_phone: form.holder_phone || undefined,
            price_paid: form.price_paid ? Number(form.price_paid) : undefined,
            payment_status: form.payment_status,
          },
        })
      } else {
        await api('/tickets', {
          method: 'POST',
          body: {
            event_id: Number(form.event_id),
            ticket_type_id: Number(form.ticket_type_id),
            holder_name: form.holder_name,
            holder_email: form.holder_email || undefined,
            holder_phone: form.holder_phone || undefined,
            quantity: Number(form.quantity),
            price_paid: form.price_paid ? Number(form.price_paid) : undefined,
            payment_status: form.payment_status,
          },
        })
      }
      setShowForm(false)
      setEditing(null)
      setForm(emptyTicketForm)
      load()
      loadTypes()
    } catch (e) { setError(e) }
  }

  const createType = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    try {
      await api('/tickets/types', {
        method: 'POST',
        body: { event_id: Number(typeForm.event_id), name: typeForm.name, price: Number(typeForm.price), total_capacity: Number(typeForm.total_capacity) },
      })
      setShowTypeForm(false)
      setTypeForm(emptyTypeForm)
      loadTypes()
    } catch (e) { setError(e) }
  }


  const tone = result?.status === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : result?.status === 'warning' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-rose-50 border-rose-300 text-rose-800'
  const formTypes = form.event_id ? types.filter((t) => String(t.event_id) === form.event_id) : types

  return (
    <div>
      <PageHeader title="Tickets & Gate Scan" sub="Manage ticket holders, payments and admission.">
        <button className={btnGhostCls} onClick={() => setShowTypeForm(!showTypeForm)}>{showTypeForm ? 'Close' : '+ Ticket type'}</button>
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>
          {showForm && !editing ? 'Close' : '+ New ticket'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showTypeForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">Add a ticket type</div>
          <form onSubmit={createType} className="grid md:grid-cols-4 gap-4">
            <select className={inputCls} value={typeForm.event_id} onChange={(e) => setTypeForm({ ...typeForm, event_id: e.target.value })} required>
              <option value="">Select event…</option>
              {events.map((ev2) => <option key={ev2.id} value={ev2.id}>{ev2.title}</option>)}
            </select>
            <input aria-label="Type name (e.g. VIP)" className={inputCls} placeholder="Type name (e.g. VIP)" value={typeForm.name} onChange={(e) => setTypeForm({ ...typeForm, name: e.target.value })} required />
            <input aria-label="Price (₹)" className={inputCls} type="number" min={0} step="0.01" placeholder="Price (₹)" value={typeForm.price} onChange={(e) => setTypeForm({ ...typeForm, price: e.target.value })} required />
            <input aria-label="Total capacity" className={inputCls} type="number" min={1} placeholder="Total capacity" value={typeForm.total_capacity} onChange={(e) => setTypeForm({ ...typeForm, total_capacity: e.target.value })} required />
            <div><button className={btnCls}>Create type</button></div>
          </form>
        </Card>
      )}
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit ticket: ${editing.ticket_number}` : 'Issue a ticket'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            {!editing && (
              <>
                <select className={inputCls} value={form.event_id} onChange={(e) => setForm({ ...form, event_id: e.target.value, ticket_type_id: '' })} required>
                  <option value="">Select event…</option>
                  {events.map((ev2) => <option key={ev2.id} value={ev2.id}>{ev2.title}</option>)}
                </select>
                <select className={inputCls} value={form.ticket_type_id} onChange={(e) => setForm({ ...form, ticket_type_id: e.target.value })} required>
                  <option value="">Select ticket type…</option>
                  {formTypes.map((t) => <option key={t.id} value={t.id}>{t.name} · {inr(t.price)}</option>)}
                </select>
              </>
            )}
            <input aria-label="Holder name" className={inputCls} placeholder="Holder name" value={form.holder_name} onChange={(e) => setForm({ ...form, holder_name: e.target.value })} required />
            <input aria-label="Holder email" className={inputCls} placeholder="Holder email" value={form.holder_email} onChange={(e) => setForm({ ...form, holder_email: e.target.value })} />
            <input aria-label="Holder phone" className={inputCls} placeholder="Holder phone" value={form.holder_phone} onChange={(e) => setForm({ ...form, holder_phone: e.target.value })} />
            {!editing && (
              <input aria-label="Quantity" className={inputCls} type="number" min={1} max={20} placeholder="Quantity" value={form.quantity} onChange={(e) => setForm({ ...form, quantity: e.target.value })} required />
            )}
            <input aria-label="Price paid (₹, blank = type price)" className={inputCls} type="number" min={0} step="0.01" placeholder="Price paid (₹, blank = type price)" value={form.price_paid} onChange={(e) => setForm({ ...form, price_paid: e.target.value })} />
            <select className={inputCls} value={form.payment_status} onChange={(e) => setForm({ ...form, payment_status: e.target.value })}>
              {PAYMENT_STATUSES.map((s) => <option key={s} value={s}>{s.replaceAll('_', ' ')}</option>)}
            </select>
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Issue ticket'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card className="p-5 mb-6">
        <form onSubmit={verify} className="flex gap-3">
          <input aria-label="Scan or paste QR token / ticket number (try a qr_token from the list below)" className={inputCls} placeholder="Scan or paste QR token / ticket number (try a qr_token from the list below)" value={token} onChange={(e) => setToken(e.target.value)} required />
          <button className={btnCls}>Verify at gate</button>
        </form>
        {result && <div className={`mt-3 rounded-lg border px-4 py-3 text-sm font-medium ${tone}`}>{result.message}</div>}
      </Card>
      <Card>
        <Table head={['Ticket #', 'Holder', 'Event', 'Type', 'Price paid', 'Payment', 'Ticket status', '']}>
          {items.map((t) => (
            <tr key={t.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{t.ticket_number}<div className="text-[10px] text-slate-400">{t.qr_token}</div></td>
              <td className="px-4 py-3 font-medium">{t.holder_name}</td>
              <td className="px-4 py-3 text-slate-600">{(t.event as any)?.title ?? `#${(t as any).event_id}`}</td>
              <td className="px-4 py-3 text-slate-600">{t.ticket_type?.name ?? '-'}</td>
              <td className="px-4 py-3">{inr(t.price_paid)}</td>
              <td className="px-4 py-3"><Badge value={t.payment_status} /></td>
              <td className="px-4 py-3"><Badge value={t.ticket_status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <IconAction label="Edit" onClick={() => openEdit(t)}/>
                
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={8} className="px-4 py-6 text-center text-slate-400">No tickets yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
