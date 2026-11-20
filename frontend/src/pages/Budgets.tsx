import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import { inr, type Budget, type EventItem } from '../types'

export default function Budgets() {
  const [events, setEvents] = useState<EventItem[]>([])
  const [eventId, setEventId] = useState<number | null>(null)
  const [budget, setBudget] = useState<Budget | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [form, setForm] = useState({ category: '', item_name: '', estimated_cost: '', actual_cost: '', notes: '' })

  useEffect(() => {
    api<Paginated<EventItem>>('/events?per_page=50').then((r) => {
      setEvents(r.data)
      if (r.data.length > 0) setEventId(r.data[0].id)
    }).catch(setError)
  }, [])

  const load = (id: number) => {
    api<{ data: Budget }>(`/events/${id}/budget`).then((r) => setBudget(r.data)).catch(setError)
  }

  useEffect(() => {
    if (eventId) load(eventId)
  }, [eventId])

  const addItem = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!eventId) return
    setError(null)
    try {
      await api(`/events/${eventId}/budget/items`, {
        method: 'POST',
        body: {
          category: form.category,
          item_name: form.item_name,
          estimated_cost: Number(form.estimated_cost),
          actual_cost: form.actual_cost ? Number(form.actual_cost) : null,
          notes: form.notes || null,
        },
      })
      setForm({ category: '', item_name: '', estimated_cost: '', actual_cost: '', notes: '' })
      load(eventId)
    } catch (err) {
      setError(err)
    }
  }

  const markActual = async (itemId: number, current?: string) => {
    const val = window.prompt('Actual cost (₹)', current ?? '')
    if (val === null || val === '') return
    setError(null)
    try {
      await api(`/budget-items/${itemId}`, { method: 'PATCH', body: { actual_cost: Number(val) } })
      if (eventId) load(eventId)
    } catch (err) {
      setError(err)
    }
  }

  const sel = events.find((e) => e.id === eventId)

  return (
    <div>
      <PageHeader title="Event Budgets" sub="GET /api/v1/events/{id}/budget · totals computed on the server" />
      <ErrorBox error={error} />
      <div className="mb-4 max-w-md">
        <label className="block text-[11.5px] font-bold uppercase tracking-wide text-pamuted mb-1">Event</label>
        <select className={inputCls} value={eventId ?? ''} onChange={(e) => setEventId(Number(e.target.value))}>
          {events.map((e) => (
            <option key={e.id} value={e.id}>{e.event_code} — {e.title}</option>
          ))}
        </select>
      </div>

      {budget && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            {[
              ['Estimated cost', inr(budget.total_estimated_cost)],
              ['Actual cost', inr(budget.total_actual_cost)],
              ['Contingency', `${budget.contingency_percent}%`],
              ['Contract value', sel?.contract_value ? inr(sel.contract_value) : '—'],
            ].map(([label, val]) => (
              <Card key={label as string} className="px-4 py-3">
                <div className="text-[11px] font-bold uppercase tracking-wide text-pamuted">{label}</div>
                <div className="text-lg font-extrabold text-ink mt-0.5">{val}</div>
              </Card>
            ))}
          </div>

          <Card className="mb-5">
            <Table head={['Category', 'Item', 'Estimated', 'Actual', 'Variance', 'Notes', '']}>
              {budget.items.map((it) => {
                const variance = Number(it.actual_cost ?? 0) - Number(it.estimated_cost)
                return (
                  <tr key={it.id} className="hover:bg-slate-50">
                    <td className="px-4 py-3 font-semibold">{it.category}</td>
                    <td className="px-4 py-3">{it.item_name}</td>
                    <td className="px-4 py-3">{inr(it.estimated_cost)}</td>
                    <td className="px-4 py-3">{it.actual_cost ? inr(it.actual_cost) : <span className="text-pamuted">—</span>}</td>
                    <td className={`px-4 py-3 font-semibold ${variance > 0 ? 'text-rose-700' : 'text-pa-700'}`}>
                      {it.actual_cost ? inr(Math.abs(variance)) + (variance > 0 ? ' over' : ' under') : '—'}
                    </td>
                    <td className="px-4 py-3 text-slate-600 max-w-[200px] truncate">{it.notes}</td>
                    <td className="px-4 py-3">
                      <button className="text-pa-700 font-bold text-xs hover:underline" onClick={() => markActual(it.id, it.actual_cost)}>
                        Set actual
                      </button>
                    </td>
                  </tr>
                )
              })}
              {budget.items.length === 0 && (
                <tr><td colSpan={7} className="px-4 py-8 text-center text-pamuted">No budget items yet for this event.</td></tr>
              )}
            </Table>
          </Card>

          <Card className="p-5 max-w-2xl">
            <h2 className="text-sm font-extrabold text-pa-900 mb-3">Add budget item</h2>
            <form onSubmit={addItem} className="grid grid-cols-2 gap-3">
              <input required placeholder="Category (e.g. Decor)" className={inputCls} value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} />
              <input required placeholder="Item name" className={inputCls} value={form.item_name} onChange={(e) => setForm({ ...form, item_name: e.target.value })} />
              <input required type="number" min="0" step="0.01" placeholder="Estimated cost ₹" className={inputCls} value={form.estimated_cost} onChange={(e) => setForm({ ...form, estimated_cost: e.target.value })} />
              <input type="number" min="0" step="0.01" placeholder="Actual cost ₹ (optional)" className={inputCls} value={form.actual_cost} onChange={(e) => setForm({ ...form, actual_cost: e.target.value })} />
              <input placeholder="Notes" className={`${inputCls} col-span-2`} value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
              <div className="col-span-2">
                <button className={btnCls} type="submit">Add item</button>
              </div>
            </form>
          </Card>
          <div className="mt-3"><Badge value={budget.status} /></div>
        </>
      )}
    </div>
  )
}
