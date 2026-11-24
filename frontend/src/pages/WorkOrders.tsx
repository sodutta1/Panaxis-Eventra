import { useSearchParams } from 'react-router-dom'
import { useEffect, useState } from 'react'
import { api, allOptions } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import { inr, type EventItem, type Vendor, type WorkOrder } from '../types'

export default function WorkOrders() {
  const [params,setParams]=useSearchParams()
  const [events, setEvents] = useState<EventItem[]>([])
  const [vendors, setVendors] = useState<Vendor[]>([])
  const [eventId, setEventId] = useState<number | null>(null)
  const [orders, setOrders] = useState<WorkOrder[]>([])
  const [error, setError] = useState<unknown>(null)
  const [payFor, setPayFor] = useState<number | null>(null)
  const [payAmount, setPayAmount] = useState('')
  const [form, setForm] = useState({ vendor_id: '', service_title: '', scope_of_work: '', contract_amount: '' })

  useEffect(() => {
    allOptions<EventItem>('/events?per_page=50').then((r) => {
      setEvents(r.data)
      if (r.data.length > 0) {const chosen=Number(params.get('event_id'))||r.data[0].id;setEventId(chosen);if(!params.has('event_id'))setParams({event_id:String(chosen)},{replace:true})}
    }).catch(setError)
    allOptions<Vendor>('/vendors?per_page=100').then((r) => setVendors(r.data)).catch(setError)
  }, [])

  const load = (id: number) => {
    api<{ data: WorkOrder[] }>(`/events/${id}/work-orders?${window.location.search.slice(1)}`).then((r) => setOrders(r.data)).catch(setError)
  }

  useEffect(() => {
    if (eventId) load(eventId)
  }, [eventId,params.toString()])

  const create = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!eventId) return
    setError(null)
    try {
      await api(`/events/${eventId}/work-orders`, {
        method: 'POST',
        body: {
          vendor_id: Number(form.vendor_id),
          service_title: form.service_title,
          scope_of_work: form.scope_of_work || null,
          contract_amount: Number(form.contract_amount),
        },
      })
      setForm({ vendor_id: '', service_title: '', scope_of_work: '', contract_amount: '' })
      load(eventId)
    } catch (err) {
      setError(err)
    }
  }

  const pay = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!payFor) return
    setError(null)
    try {
      await api(`/work-orders/${payFor}/payment`, { method: 'POST', body: { amount: Number(payAmount) } })
      setPayFor(null)
      setPayAmount('')
      if (eventId) load(eventId)
    } catch (err) {
      setError(err)
    }
  }

  const setStatus = async (id: number, status: string) => {
    setError(null)
    try {
      await api(`/work-orders/${id}/status`, { method: 'POST', body: { status } })
      if (eventId) load(eventId)
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <PageHeader title="Vendor Work Orders" sub="Track supplier commitments and payment balances." />
      <ErrorBox error={error} />
      <div className="mb-4 max-w-md">
        <label className="block text-[11.5px] font-bold uppercase tracking-wide text-pamuted mb-1">Event</label>
        <select className={inputCls} value={eventId ?? ''} onChange={(e) => {setEventId(Number(e.target.value));setParams({event_id:e.target.value})}}>
          {events.map((e) => (
            <option key={e.id} value={e.id}>{e.event_code} — {e.title}</option>
          ))}
        </select>
      </div>

      <Card className="mb-5">
        <Table head={['Vendor', 'Service', 'Contract', 'Paid', 'Balance', 'Status', 'Actions']}>
          {orders.map((wo) => (
            <tr key={wo.id} className="hover:bg-slate-50 align-top">
              <td className="px-4 py-3 font-semibold">{wo.vendor?.name ?? `#${wo.vendor_id}`}</td>
              <td className="px-4 py-3">
                <div>{wo.service_title}</div>
                {wo.scope_of_work && <div className="text-xs text-pamuted max-w-[240px] truncate">{wo.scope_of_work}</div>}
              </td>
              <td className="px-4 py-3">{inr(wo.contract_amount)}</td>
              <td className="px-4 py-3">{inr(wo.paid_amount)}</td>
              <td className="px-4 py-3 font-semibold">{inr(Number(wo.contract_amount) - Number(wo.paid_amount))}</td>
              <td className="px-4 py-3"><Badge value={wo.status} /></td>
              <td className="px-4 py-3">
                {payFor === wo.id ? (
                  <form onSubmit={pay} className="flex gap-2 items-center">
                    <input autoFocus required type="number" min="0.01" step="0.01" placeholder="₹ amount" className={`${inputCls} !w-28 !py-1.5`} value={payAmount} onChange={(e) => setPayAmount(e.target.value)} />
                    <button className="text-pa-700 font-bold text-xs" type="submit">Save</button>
                    <button className="text-pamuted text-xs" type="button" onClick={() => setPayFor(null)}>Cancel</button>
                  </form>
                ) : (
                  <div className="flex gap-3">
                    <button className="text-pa-700 font-bold text-xs hover:underline" onClick={() => { setPayFor(wo.id); setPayAmount('') }}>Add payment</button>
                    <select className="text-xs border border-paline rounded-lg px-1.5 py-1 text-ink" value={wo.status} onChange={(e) => setStatus(wo.id, e.target.value)}>
                      {['assigned', 'in_progress', 'completed', 'paid', 'cancelled'].map((st) => (
                        <option key={st} value={st}>{st.replaceAll('_', ' ')}</option>
                      ))}
                    </select>
                  </div>
                )}
              </td>
            </tr>
          ))}
          {orders.length === 0 && (
            <tr><td colSpan={7} className="px-4 py-8 text-center text-pamuted">No work orders for this event yet.</td></tr>
          )}
        </Table>
      </Card>

      <Card className="p-5 max-w-2xl">
        <h2 className="text-sm font-extrabold text-pa-900 mb-3">New work order</h2>
        <form onSubmit={create} className="grid grid-cols-2 gap-3">
          <select required className={inputCls} value={form.vendor_id} onChange={(e) => setForm({ ...form, vendor_id: e.target.value })}>
            <option value="">Select vendor…</option>
            {vendors.map((v) => (
              <option key={v.id} value={v.id}>{v.name}</option>
            ))}
          </select>
          <input required placeholder="Service (e.g. Catering)" className={inputCls} value={form.service_title} onChange={(e) => setForm({ ...form, service_title: e.target.value })} />
          <input required type="number" min="0" step="0.01" placeholder="Contract amount ₹" className={inputCls} value={form.contract_amount} onChange={(e) => setForm({ ...form, contract_amount: e.target.value })} />
          <input placeholder="Scope of work (optional)" className={inputCls} value={form.scope_of_work} onChange={(e) => setForm({ ...form, scope_of_work: e.target.value })} />
          <div className="col-span-2">
            <button className={btnCls} type="submit">Create work order</button>
          </div>
        </form>
      </Card>
    </div>
  )
}
