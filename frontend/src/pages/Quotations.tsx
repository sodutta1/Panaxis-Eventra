import { useEffect, useState } from 'react'
import { api, downloadPdf, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import { inr, type Quotation } from '../types'

export default function Quotations() {
  const [items, setItems] = useState<Quotation[]>([])
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<Paginated<Quotation>>('/quotations').then((r) => setItems(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Quotations" sub="GET /api/v1/quotations" />
      <ErrorBox error={error} />
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
              <td className="px-4 py-3 text-right">
                <button
                  className="text-pa-700 font-bold text-xs hover:underline"
                  onClick={() => downloadPdf(`/quotations/${q.id}/pdf`, `${q.quotation_number}.pdf`).catch(setError)}
                >
                  PDF
                </button>
              </td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
