import { useEffect, useState } from 'react'
import { WifiOff } from 'lucide-react'

export default function OfflineBanner() {
  const [offline, setOffline] = useState(typeof navigator !== 'undefined' && navigator.onLine === false)
  const [stale, setStale] = useState(false)
  useEffect(() => {
    const down = () => setOffline(true)
    const up = () => { setOffline(false); setStale(false) }
    const st = (e: Event) => setStale(Boolean((e as CustomEvent).detail?.stale))
    window.addEventListener('offline', down)
    window.addEventListener('online', up)
    window.addEventListener('offline-state', st)
    return () => { window.removeEventListener('offline', down); window.removeEventListener('online', up); window.removeEventListener('offline-state', st) }
  }, [])
  if (!offline && !stale) return null
  return (
    <div role="status" className="offline-banner" style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 16px', background: '#fff4d6', color: '#5c4300', fontSize: 13, fontWeight: 600 }}>
      <WifiOff size={16} aria-hidden="true" />
      <span>You are offline. Showing the last data saved on this device. Changes cannot be saved until the connection is back.</span>
    </div>
  )
}
