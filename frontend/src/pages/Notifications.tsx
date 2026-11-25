import { useEffect, useState } from 'react'
import { CheckCheck } from 'lucide-react'
import { api } from '../api'
import { NoticeRow, useOpenNotice, type AppNotice } from '../components/Notifications'

export default function Notifications() {
  const [items, setItems] = useState<AppNotice[]>([])
  const [page, setPage] = useState(1)
  const [last, setLast] = useState(1)
  const [unread, setUnread] = useState(0)
  const [only, setOnly] = useState(false)
  const [state, setState] = useState<'loading' | 'ok' | 'error'>('loading')
  const open = useOpenNotice(f => setUnread(f(unread)))
  const load = (p: number, replace: boolean) => {
    setState('loading')
    api<{ data: AppNotice[]; unread_count: number; meta: { last_page: number } }>(`/notifications?page=${p}&per_page=20${only ? '&filter=unread' : ''}`)
      .then(r => { setItems(l => replace ? r.data : [...l, ...r.data]); setPage(p); setLast(r.meta.last_page); setUnread(r.unread_count); setState('ok') }).catch(() => setState('error'))
  }
  useEffect(() => { load(1, true) }, [only])
  const readAll = async () => { await api('/notifications/read-all', { method: 'POST' }); load(1, true) }
  return <div className="notice-page">
    <div className="page-header flex items-start justify-between mb-6"><div><h1 className="text-[22px] font-extrabold text-pa-900 tracking-tight">Notifications</h1><p className="text-[13px] text-pamuted mt-0.5">{unread ? `${unread} unread` : 'Nothing unread'}</p></div>
      <div className="header-actions flex gap-2"><button className="tool-button" aria-pressed={only} onClick={() => setOnly(v => !v)}>{only ? 'Showing unread' : 'Unread only'}</button>{unread > 0 && <button className="tool-button" onClick={readAll}><CheckCheck size={15} /> Mark all read</button>}</div></div>
    <div className="notice-list card-like">
      {state === 'error' && <p className="notice-empty">Could not load notifications. <button className="tool-button" onClick={() => load(1, true)}>Retry</button></p>}
      {state === 'ok' && items.length === 0 && <p className="notice-empty">{only ? 'No unread notifications.' : 'No notifications yet. Approvals, payments and assignments will show up here.'}</p>}
      {items.map(n => <NoticeRow key={n.id} n={n} onOpen={x => { open(x); setItems(l => l.map(i => i.id === x.id ? { ...i, read: true } : i)) }} />)}
      {state === 'loading' && <p className="notice-empty">Loading...</p>}
      {state === 'ok' && page < last && <p className="notice-empty"><button className="tool-button" onClick={() => load(page + 1, false)}>Load more</button></p>}
    </div>
  </div>
}
