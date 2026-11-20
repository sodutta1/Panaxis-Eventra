import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, inputCls } from '../components/ui'
import type { Guest } from '../types'

export default function Guests() {
  const [items, setItems] = useState<Guest[]>([])
  const [eventId, setEventId] = useState('')
  const [error, setError] = useState<unknown>(null)

  const load = () =>
    api<Paginated<Guest>>(`/guests${eventId ? `?event_id=${eventId}` : ''}`)
      .then((r) => setItems(r.data))
      .catch(setError)
  useEffect(() => { load() }, [eventId])

  const toggle = async (id: number) => {
    await api(`/guests/${id}/checkin`, { method: 'POST' })
    load()
  }

  return (
    <div>
      <PageHeader title="Guests & RSVP" sub="GET /api/v1/guests · click a row to toggle check-in">
        <input className={`${inputCls} w-40`} placeholder="Filter event id" value={eventId} onChange={(e) => setEventId(e.target.value)} />
      </PageHeader>
      <ErrorBox error={error} />
      <Card>
        <Table head={['Guest', 'Event', 'Plus ones', 'Table', 'Dietary', 'RSVP', 'Checked in']}>
          {items.map((g) => (
            <tr key={g.id} className="hover:bg-slate-50 cursor-pointer" onClick={() => toggle(g.id)}>
              <td className="px-4 py-3 font-medium">{g.name}<div className="text-xs text-slate-400">{g.email}</div></td>
              <td className="px-4 py-3 text-slate-600">#{g.event_id}</td>
              <td className="px-4 py-3">{g.plus_ones}</td>
              <td className="px-4 py-3">{g.table_number ?? '-'}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{g.dietary_preference}</td>
              <td className="px-4 py-3"><Badge value={g.rsvp_status} /></td>
              <td className="px-4 py-3">{g.checked_in ? <Badge value="checked_in" /> : <span className="text-xs text-slate-400">not yet</span>}</td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-400">No guests found.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
