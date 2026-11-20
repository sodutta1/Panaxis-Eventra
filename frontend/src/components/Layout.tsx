import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { api, clearSession, getUser } from '../api'

type IconProps = { d: string | string[]; circle?: { cx: number; cy: number; r: number } }

function Icon({ d, circle }: IconProps) {
  const paths = Array.isArray(d) ? d : [d]
  return (
    <svg
      aria-hidden
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.8}
      strokeLinecap="round"
      strokeLinejoin="round"
      className="w-[18px] h-[18px] shrink-0"
    >
      {paths.map((p, i) => (
        <path key={i} d={p} />
      ))}
      {circle && <circle cx={circle.cx} cy={circle.cy} r={circle.r} />}
    </svg>
  )
}

const ICONS = {
  dashboard: { d: ['M4 20V12', 'M10 20V4', 'M16 20v-6', 'M2 20h20'] },
  leads: { d: 'M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z' },
  clients: { d: ['M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2', 'M17 3.5a4 4 0 010 7'], circle: { cx: 9, cy: 7, r: 4 } },
  events: { d: ['M5 21V4', 'M5 5c4-2.5 8 2 13 0v9c-5 2-9-2.5-13 0'] },
  venues: { d: 'M12 21s-6.5-5.6-6.5-10.2a6.5 6.5 0 1113 0C18.5 15.4 12 21 12 21z', circle: { cx: 12, cy: 10.6, r: 2.3 } },
  vendors: { d: 'M14.7 6.3a4.6 4.6 0 00-6.1 6.1L3 18l3 3 5.6-5.6a4.6 4.6 0 006.1-6.1L14 12.6 11.4 10l3.3-3.7z' },
  quotations: { d: ['M6 2h9l4 4v16H6V2z', 'M14 2v5h5', 'M9 12h7', 'M9 16h7'] },
  budgets: { d: ['M3 7h18v12H3z', 'M3 10h18', 'M7 15h4'] },
  guests: { d: ['M8 3h8l1 3H7l1-3z', 'M7 6h10v15H7z', 'M9.5 13.5l2 2 3.5-4'] },
  tickets: { d: 'M3 9V7h18v2a2.5 2.5 0 000 5v2H3v-2a2.5 2.5 0 000-5zM13 7v2m0 3v2m0 3v2' },
  invoices: { d: ['M6 2h12v20l-3-2-3 2-3-2-3 2V2z', 'M9 8h6', 'M9 12h6'] },
}

const NAV = [
  { to: '/dashboard', label: 'Dashboard', icon: ICONS.dashboard },
  { to: '/leads', label: 'Leads & CRM', icon: ICONS.leads },
  { to: '/clients', label: 'Clients', icon: ICONS.clients },
  { to: '/events', label: 'Events', icon: ICONS.events },
  { to: '/venues', label: 'Venues', icon: ICONS.venues },
  { to: '/vendors', label: 'Vendors', icon: ICONS.vendors },
  { to: '/quotations', label: 'Quotations', icon: ICONS.quotations },
  { to: '/expenses', label: 'Budgets & Expenses', icon: ICONS.budgets },
  { to: '/guests', label: 'Guests & RSVP', icon: ICONS.guests },
  { to: '/tickets', label: 'Tickets & Gate', icon: ICONS.tickets },
  { to: '/invoices', label: 'Invoices & GST', icon: ICONS.invoices },
]

function initials(name?: string) {
  if (!name) return '?'
  return name
    .split(/\s+/)
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

export default function Layout() {
  const navigate = useNavigate()
  const user = getUser()
  const [menuOpen, setMenuOpen] = useState(false)

  const logout = async () => {
    try {
      await api('/auth/logout', { method: 'POST' })
    } catch {
      // token may already be gone
    }
    clearSession()
    navigate('/login')
  }

  const sidebar = (
    <aside
      className={`fixed inset-y-0 left-0 z-40 w-[248px] bg-pa-900 text-[#cfe0d5] flex flex-col transform transition-transform duration-200 md:static md:translate-x-0 ${
        menuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
      }`}
    >
      <div className="flex items-center gap-3 px-4 py-4 border-b border-white/10">
        <img src="/logo.jpg" alt="Panaxis" className="w-9 h-9 rounded-lg object-cover" />
        <span className="font-bold text-white text-[16px] tracking-tight">Panaxis Eventra</span>
      </div>
      <nav className="flex-1 overflow-y-auto px-3 py-3">
        {NAV.map((n) => (
          <NavLink
            key={n.to}
            to={n.to}
            onClick={() => setMenuOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13.5px] font-semibold mb-0.5 transition-colors ${
                isActive
                  ? 'bg-gradient-to-br from-pa-700 to-pa-500 text-white'
                  : 'text-[#b9cec2] hover:bg-white/5 hover:text-white'
              }`
            }
          >
            <Icon {...n.icon} />
            {n.label}
          </NavLink>
        ))}
      </nav>
      <div className="px-4 py-3.5 border-t border-white/10 text-[11.5px] leading-relaxed text-[#7fa08d]">
        <div>
          Signed in as <span className="text-[#b9cec2] font-semibold">{user?.name}</span> ·{' '}
          <span className="capitalize">{(user?.role ?? '').replace('_', ' ')}</span>
        </div>
        <div>Panaxis Eventra Pvt. Ltd.</div>
      </div>
    </aside>
  )

  return (
    <div className="flex min-h-screen bg-paper">
      {menuOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/40 md:hidden"
          onClick={() => setMenuOpen(false)}
          aria-hidden
        />
      )}
      {sidebar}
      <div className="flex-1 min-w-0 flex flex-col">
        <header className="sticky top-0 z-20 bg-white border-b border-paline px-4 md:px-6 py-3 flex items-center justify-between">
          <button
            className="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border border-paline bg-white text-pa-700"
            onClick={() => setMenuOpen(true)}
            aria-label="Open menu"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" className="w-5 h-5">
              <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
          </button>
          <div className="hidden md:block text-[12.5px] text-pamuted">
            Multi-tenant event management · live from /api/v1
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={logout}
              className="text-xs font-semibold text-pa-700 hover:text-pa-900 border border-paline rounded-full px-3 py-1.5 bg-white hover:bg-pa-50"
            >
              Sign out
            </button>
            <div
              title={user?.email}
              className="w-9 h-9 rounded-full bg-gradient-to-br from-pa-700 to-pa-300 text-white flex items-center justify-center font-extrabold text-[13px]"
            >
              {initials(user?.name)}
            </div>
          </div>
        </header>
        <main className="flex-1 p-4 md:p-6 overflow-x-auto min-w-0">
          <div className="max-w-[1080px]">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  )
}
