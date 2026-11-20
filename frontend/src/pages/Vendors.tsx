import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import type { Vendor } from '../types'

export default function Vendors() {
  const [vendors, setVendors] = useState<Vendor[]>([])
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<Paginated<Vendor>>('/vendors').then((r) => setVendors(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Vendors" sub="GET /api/v1/vendors" />
      <ErrorBox error={error} />
      <Card>
        <Table head={['Code', 'Vendor', 'Category', 'City', 'Rating', 'Status']}>
          {vendors.map((v) => (
            <tr key={v.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{v.vendor_code}</td>
              <td className="px-4 py-3 font-medium">{v.name}<div className="text-xs text-slate-400">{v.business_name}</div></td>
              <td className="px-4 py-3 text-slate-600">{v.category?.name ?? '-'}</td>
              <td className="px-4 py-3 text-slate-600">{v.city ?? '-'}</td>
              <td className="px-4 py-3">★ {v.rating}</td>
              <td className="px-4 py-3"><Badge value={v.status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
