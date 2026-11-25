import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, CheckCheck, X } from 'lucide-react'
import { api } from '../api'

export type AppNotice = { id: number; type: string; title: string; body: string | null; link: string | null; read: boolean; created_at: string }

export function timeAgo(iso: string) {
  const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000)
  if (s < 60) return 'just now'
  if (s < 3600) return `${Math.floor(s / 60)} min ago`
  if (s < 86400) return `${Math.floor(s / 3600)} h ago`
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', timeZone: 'Asia/Kolkata' })
}

/** Unread count, refreshed every 60s while the tab is visible and when it regains focus. */
export function useUnread() {
  const [count, setCount] = useState(0)
  const refresh = useCallback(() => {
    if (document.hidden) return
    api<{ unread_count: number }>('/notifications/unread-count').then(r => setCount(r.unread_count)).catch(() => {})
  }, [])
  useEffect(() => {
    refresh()
    const t = setInterval(refresh, 60_000)
    document.addEventListener('visibilitychange', refresh)
    window.addEventListener('focus', refresh)
    return () => { clearInterval(t); document.removeEventListener('visibilitychange', refresh); window.removeEventListener('focus', refresh) }
  }, [refresh])
  return { count, setCount, refresh }
}

export function NoticeRow({ n, onOpen }: { n: AppNotice; onOpen: (n: AppNotice) => void }) {
  return <button className={`notice-row ${n.read ? '' : 'unread'}`} onClick={() => onOpen(n)} data-testid="notice-row">
    <span className="notice-dot" aria-hidden="true" />
    <span className="notice-text"><strong>{n.title}</strong>{n.body && <span>{n.body}</span>}<small>{timeAgo(n.created_at)}{n.read ? '' : ' · unread'}</small></span>
  </button>
}

export function useOpenNotice(setCount: (f: (c: number) => number) => void, after?: () => void) {
  const navigate = useNavigate()
  return (n: AppNotice) => {
    if (!n.read) { api(`/notifications/${n.id}/read`, { method: 'POST' }).catch(() => {}); setCount(c => Math.max(0, c - 1)) }
    after?.()
    if (n.link) navigate(n.link)
  }
}

export default function NotificationBell() {
  const { count, setCount, refresh } = useUnread()
  const [open, setOpen] = useState(false)
  const [items, setItems] = useState<AppNotice[] | null>(null)
  const [err, setErr] = useState(false)
  const navigate = useNavigate()
  const box = useRef<HTMLDivElement>(null)
  const openNotice = useOpenNotice(setCount, () => setOpen(false))
  const load = () => { setErr(false); api<{ data: AppNotice[]; unread_count: number }>('/notifications?per_page=8').then(r => { setItems(r.data); setCount(() => r.unread_count) }).catch(() => setErr(true)) }
  useEffect(() => { if (open) load() }, [open])
  useEffect(() => {
    if (!open) return
    const f = (e: MouseEvent) => { if (box.current && !box.current.contains(e.target as Node)) setOpen(false) }
    const k = (e: KeyboardEvent) => { if (e.key === 'Escape') setOpen(false) }
    document.addEventListener('mousedown', f); document.addEventListener('keydown', k)
    return () => { document.removeEventListener('mousedown', f); document.removeEventListener('keydown', k) }
  }, [open])
  const readAll = async () => { await api('/notifications/read-all', { method: 'POST' }).catch(() => {}); setCount(() => 0); setItems(l => l?.map(n => ({ ...n, read: true })) ?? l); refresh() }
  return <div className="notice-wrap" ref={box}>
    <button className="icon-action notice-bell" aria-label={count ? `Notifications, ${count} unread` : 'Notifications'} aria-expanded={open} title="Notifications" onClick={() => setOpen(v => !v)}>
      <Bell size={18} />{count > 0 && <span className="notice-badge" data-testid="notice-badge">{count > 99 ? '99+' : count}</span>}
    </button>
    {open && <section className="notice-panel" role="dialog" aria-label="Notifications">
      <header><h2>Notifications</h2><span>
        {count > 0 && <button className="tool-button" onClick={readAll}><CheckCheck size={15} /> Mark all read</button>}
        <button className="icon-action notice-close" aria-label="Close notifications" onClick={() => setOpen(false)}><X size={18} /></button></span></header>
      <div className="notice-list">
        {err && <p className="notice-empty">Could not load notifications. <button className="tool-button" onClick={load}>Retry</button></p>}
        {!err && items === null && <p className="notice-empty">Loading...</p>}
        {!err && items?.length === 0 && <p className="notice-empty">You are all caught up.</p>}
        {items?.map(n => <NoticeRow key={n.id} n={n} onOpen={openNotice} />)}
      </div>
      <footer><button className="tool-button" onClick={() => { setOpen(false); navigate('/notifications') }}>See all notifications</button></footer>
    </section>}
  </div>
}
