import { useEffect, useState } from 'react'
import { api, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import { inr, type Ticket } from '../types'

type VerifyResult = { status: 'success' | 'warning' | 'error'; message: string }

export default function Tickets() {
  const [items, setItems] = useState<Ticket[]>([])
  const [error, setError] = useState<unknown>(null)
  const [token, setToken] = useState('')
  const [result, setResult] = useState<VerifyResult | null>(null)

  const load = () => api<Paginated<Ticket>>('/tickets').then((r) => setItems(r.data)).catch(setError)
  useEffect(() => { load() }, [])

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

  const tone = result?.status === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : result?.status === 'warning' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-rose-50 border-rose-300 text-rose-800'

  return (
    <div>
      <PageHeader title="Tickets & Gate Scan" sub="POST /api/v1/tickets/verify · scan a QR token or ticket number" />
      <ErrorBox error={error} />
      <Card className="p-5 mb-6">
        <form onSubmit={verify} className="flex gap-3">
          <input className={inputCls} placeholder="Scan or paste QR token / ticket number (try a qr_token from the list below)" value={token} onChange={(e) => setToken(e.target.value)} required />
          <button className={btnCls}>Verify at gate</button>
        </form>
        {result && <div className={`mt-3 rounded-lg border px-4 py-3 text-sm font-medium ${tone}`}>{result.message}</div>}
      </Card>
      <Card>
        <Table head={['Ticket #', 'Holder', 'Event', 'Type', 'Price paid', 'Payment', 'Ticket status']}>
          {items.map((t) => (
            <tr key={t.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{t.ticket_number}<div className="text-[10px] text-slate-400">{t.qr_token}</div></td>
              <td className="px-4 py-3 font-medium">{t.holder_name}</td>
              <td className="px-4 py-3 text-slate-600">{(t.event as any)?.title ?? `#${(t as any).event_id}`}</td>
              <td className="px-4 py-3 text-slate-600">{t.ticket_type?.name ?? '-'}</td>
              <td className="px-4 py-3">{inr(t.price_paid)}</td>
              <td className="px-4 py-3"><Badge value={t.payment_status} /></td>
              <td className="px-4 py-3"><Badge value={t.ticket_status} /></td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  )
}
