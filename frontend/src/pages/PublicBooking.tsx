import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { API_URL } from '../api'
import { formatEventDateTime } from '../dateTime'

type TicketType = { id: number; name: string; price: string; benefits: string | null; remaining: number }
type PublicEvent = {
  id: number
  title: string
  event_code: string
  description: string | null
  start_date: string
  end_date: string
  venue: { name: string; city: string | null } | null
  bookable: boolean
  status: string
  company: { name: string; logo_url: string | null }
  ticket_types: TicketType[]
  currency_symbol: string
  payment: { provider: string; test_mode: boolean; key: string }
}
type ConfirmedTicket = { ticket_number: string; holder_name: string; eticket_url: string }

async function pub<T>(path: string, body?: unknown): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    method: body === undefined ? 'GET' : 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: body === undefined ? undefined : JSON.stringify(body),
  })
  const json = await res.json().catch(() => ({}))
  if (!res.ok) {
    const msg = (json as any).message ?? `Request failed (${res.status})`
    const first = (json as any).errors ? String(Object.values((json as any).errors)[0]) : msg
    throw new Error(Array.isArray(first) ? first[0] : first)
  }
  return json as T
}

export default function PublicBooking() {
  const { eventId } = useParams()
  const [ev, setEv] = useState<PublicEvent | null>(null)
  const [error, setError] = useState('')
  const [typeId, setTypeId] = useState<number | null>(null)
  const [qty, setQty] = useState(1)
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [busy, setBusy] = useState(false)
  const [tickets, setTickets] = useState<ConfirmedTicket[] | null>(null)

  useEffect(() => {
    pub<{ data: PublicEvent }>(`/public/events/${eventId}`)
      .then((r) => {
        setEv(r.data)
        const first = r.data.ticket_types.find((t) => t.remaining > 0)
        setTypeId(first?.id ?? null)
      })
      .catch((e) => setError(e.message))
  }, [eventId])

  if (error) return <div className="public-booking"><p className="pb-error">{error}</p></div>
  if (!ev) return <div className="public-booking"><p>Loading booking page...</p></div>

  const selected = ev.ticket_types.find((t) => t.id === typeId) ?? null
  const total = selected ? Number(selected.price) * qty : 0

  async function pay() {
    if (!ev || !typeId) return
    setBusy(true)
    setError('')
    try {
      const order = await pub<{ data: { booking_id: number } }>(`/public/events/${ev.id}/book`, {
        ticket_type_id: typeId, quantity: qty, holder_name: name, holder_email: email, holder_phone: phone || undefined,
      })
      const payment = await pub<{ data: { razorpay_payment_id: string; razorpay_signature: string } }>(
        `/public/bookings/${order.data.booking_id}/simulate-payment`,
        {},
      )
      const confirmed = await pub<{ data: { tickets: ConfirmedTicket[] } }>(
        `/public/bookings/${order.data.booking_id}/confirm`,
        payment.data,
      )
      setTickets(confirmed.data.tickets)
    } catch (e) {
      setError((e as Error).message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="public-booking">
      <div className="pb-card">
        <div className="pb-head">
          {ev.company.logo_url ? <img className="pb-logo" src={ev.company.logo_url} alt={ev.company.name} /> : null}
          <div>
            <div className="pb-company">{ev.company.name}</div>
            <h1>{ev.title}</h1>
            <div className="pb-meta">
              {formatEventDateTime(ev.start_date)}
              {ev.venue ? ` · ${ev.venue.name}${ev.venue.city ? `, ${ev.venue.city}` : ''}` : ''}
            </div>
          </div>
        </div>

        {ev.description ? <p className="pb-desc">{ev.description}</p> : null}

        {tickets ? (
          <div className="pb-success">
            <h2>Booking confirmed!</h2>
            <p>Your e-tickets are ready. A confirmation has been sent to {email}.</p>
            <ul>
              {tickets.map((t) => (
                <li key={t.ticket_number}>
                  <strong>{t.ticket_number}</strong> — {t.holder_name}{' '}
                  <a href={t.eticket_url} target="_blank" rel="noreferrer">Download e-ticket (PDF)</a>
                </li>
              ))}
            </ul>
          </div>
        ) : !ev.bookable ? (
          <p className="pb-error">This event is not open for booking ({ev.status}).</p>
        ) : (
          <>
            <div className="pb-types">
              {ev.ticket_types.map((t) => (
                <button
                  key={t.id}
                  type="button"
                  className={`pb-type ${t.id === typeId ? 'selected' : ''}`}
                  disabled={t.remaining === 0}
                  onClick={() => setTypeId(t.id)}
                >
                  <span className="pb-type-name">{t.name}</span>
                  <span className="pb-type-price">{ev.currency_symbol}{Number(t.price).toLocaleString('en-IN')}</span>
                  <span className="pb-type-left">{t.remaining === 0 ? 'Sold out' : `${t.remaining} left`}</span>
                  {t.benefits ? <span className="pb-type-benefits">{t.benefits}</span> : null}
                </button>
              ))}
            </div>

            <div className="pb-form">
              <label>Quantity
                <select value={qty} onChange={(e) => setQty(Number(e.target.value))}>
                  {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}</option>)}
                </select>
              </label>
              <label>Name <input value={name} onChange={(e) => setName(e.target.value)} placeholder="Full name" /></label>
              <label>Email <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@example.com" /></label>
              <label>Phone <input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+91..." /></label>
            </div>

            {error ? <p className="pb-error">{error}</p> : null}

            <button className="pb-pay" disabled={busy || !selected || !name || !email} onClick={pay}>
              {busy ? 'Processing...' : `Pay ${ev.currency_symbol}${total.toLocaleString('en-IN')}`}
            </button>
            {ev.payment.test_mode ? (
              <p className="pb-testmode">Razorpay TEST MODE - no real money is charged.</p>
            ) : null}
          </>
        )}
      </div>
    </div>
  )
}
