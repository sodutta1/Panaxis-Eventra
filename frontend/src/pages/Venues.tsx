import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import { inr, type Venue } from '../types'

export default function Venues() {
  const [venues, setVenues] = useState<Venue[]>([])
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<Paginated<Venue>>('/venues').then((r) => setVenues(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Venues" sub="GET /api/v1/venues" />
      <ErrorBox error={error} />
      <Card>
        <Table head={['Venue', 'Type', 'City', 'Capacity', 'Rent / day', 'Status']}>
          {venues.map((v) => (
            <tr key={v.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-medium">{v.name}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{v.type.replaceAll('_', ' ')}</td>
              <td className="px-4 py-3 text-slate-600">{v.city}</td>
              <td className="px-4 py-3">{v.capacity.toLocaleString('en-IN')}</td>
              <td className="px-4 py-3">{inr(v.rental_cost_per_day)}</td>
              <td className="px-4 py-3"><Badge value={v.status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
