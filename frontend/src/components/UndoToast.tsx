import { useEffect, useRef, useState } from 'react'
import { api } from '../api'

const NAMES: Record<string, string> = { leads: 'Lead', clients: 'Client', venues: 'Venue', vendors: 'Vendor', guests: 'Guest', events: 'Event', quotations: 'Quotation' }
const SECONDS = 10

/** Shows "<Record> deleted. Undo" for a few seconds after any delete. */
export default function UndoToast() {
  const [item, setItem] = useState<{ type: string; id: number } | null>(null)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const timer = useRef<number | undefined>(undefined)
  useEffect(() => {
    const on = (e: Event) => {
      setItem((e as CustomEvent).detail); setError('')
      window.clearTimeout(timer.current)
      timer.current = window.setTimeout(() => setItem(null), SECONDS * 1000)
    }
    window.addEventListener('record-deleted', on)
    return () => { window.removeEventListener('record-deleted', on); window.clearTimeout(timer.current) }
  }, [])
  if (!item) return null
  const undo = async () => {
    setBusy(true); setError('')
    try {
      await api(`/restore/${item.type}/${item.id}`, { method: 'POST' })
      setItem(null)
      window.dispatchEvent(new Event('list-query'))
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not undo.')
    } finally { setBusy(false) }
  }
  return (
    <div className="undo-toast" role="status" aria-live="polite">
      <span>{NAMES[item.type]} deleted.</span>
      <button type="button" className="tool-button" disabled={busy} onClick={undo}>Undo</button>
      <button type="button" className="icon-action" aria-label="Dismiss" onClick={() => setItem(null)}>×</button>
      {error && <span role="alert" className="undo-error">{error}</span>}
    </div>
  )
}
