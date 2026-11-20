import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table } from '../components/ui'
import { inr, type Expense } from '../types'

export default function Expenses() {
  const [items, setItems] = useState<Expense[]>([])
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    api<Paginated<Expense>>('/expenses').then((r) => setItems(r.data)).catch(setError)
  }, [])

  return (
    <div>
      <PageHeader title="Expenses" sub="GET /api/v1/expenses" />
      <ErrorBox error={error} />
      <Card>
        <Table head={['Number', 'Title', 'Date', 'Method', 'Amount', 'Status']}>
          {items.map((x) => (
            <tr key={x.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{x.expense_number}</td>
              <td className="px-4 py-3">{x.title}</td>
              <td className="px-4 py-3 text-slate-600">{x.incurred_date}</td>
              <td className="px-4 py-3 capitalize text-slate-600">{x.payment_method.replaceAll('_', ' ')}</td>
              <td className="px-4 py-3 font-semibold">{inr(x.amount)}</td>
              <td className="px-4 py-3"><Badge value={x.status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
