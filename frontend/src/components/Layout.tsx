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
  workorders: { d: ['M4 7h10v10H4z', 'M14 10h4l3 3v4h-7', 'M7 21a2 2 0 100-4 2 2 0 000-4z'], circle: { cx: 17, cy: 19, r: 2 } },
  scanner: { d: ['M3 8V4h4', 'M17 4h4v4', 'M20 16v4h-4', 'M7 20H3v-4', 'M3 12h18'] },
  portal: { d: ['M12 3a9 9 0 100 18 9 9 0 000-18z', 'M3.5 9h17', 'M3.5 15h17', 'M12 3c3 3.5 3 14.5 0 18', 'M12 3c-3 3.5-3 14.5 0 18'] },
  reports: { d: ['M4 20V10', 'M10 20V4', 'M16 20v-8', 'M2 20h20', 'M16 6l4-2'] },
  admin: { d: ['M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z'] },
}

const NAV = [
  { to: '/dashboard', label: 'Dashboard', icon: ICONS.dashboard },
  { to: '/leads', label: 'Leads & CRM', icon: ICONS.leads },
  { to: '/clients', label: 'Clients', icon: ICONS.clients },
  { to: '/events', label: 'Events', icon: ICONS.events },
  { to: '/venues', label: 'Venues', icon: ICONS.venues },
  { to: '/vendors', label: 'Vendors', icon: ICONS.vendors },
  { to: '/quotations', label: 'Quotations', icon: ICONS.quotations },
  { to: '/expenses', label: 'Expenses', icon: ICONS.budgets },
  { to: '/guests', label: 'Guests & RSVP', icon: ICONS.guests },
  { to: '/tickets', label: 'Tickets & Gate', icon: ICONS.tickets },
  { to: '/invoices', label: 'Invoices & GST', icon: ICONS.invoices },
  { to: '/budgets', label: 'Budgets', icon: ICONS.budgets },
  { to: '/work-orders', label: 'Work Orders', icon: ICONS.workorders },
  { to: '/gate', label: 'Gate Scanner', icon: ICONS.scanner },
  { to: '/portal/client', label: 'Client Portal', icon: ICONS.portal },
  { to: '/portal/vendor', label: 'Vendor Portal', icon: ICONS.portal },
  { to: '/analytics', label: 'Analytics', icon: ICONS.reports },
  { to: '/reports', label: 'Reports', icon: ICONS.reports },
  { to: '/admin', label: 'Admin', icon: ICONS.admin },
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
      className={`fixed inset-y-0 left-0 z-40 w-[84px] bg-white border-r border-slate-200 flex flex-col transform transition-transform duration-200 md:static md:translate-x-0 ${
        menuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
      }`}
    >
      <div className="flex items-center justify-center py-3 border-b border-slate-100">
        <img src="/logo.jpg" alt="Panaxis" className="w-9 h-9 rounded-lg object-cover" />
      </div>
      <nav className="flex-1 overflow-y-auto py-2">
        {NAV.map((n) => (
          <NavLink
            key={n.to}
            to={n.to}
            onClick={() => setMenuOpen(false)}
            title={n.label}
            className={({ isActive }) =>
              `flex flex-col items-center gap-1 mx-1.5 my-0.5 px-1 py-2.5 rounded-xl text-[9.5px] font-semibold tracking-tight text-center leading-tight transition-colors border-l-2 ${
                isActive
                  ? 'text-pa-700 bg-pa-50 border-pa-700'
                  : 'text-slate-500 border-transparent hover:text-pa-700 hover:bg-pa-50'
              }`
            }
          >
            <Icon {...n.icon} />
            {n.label.replace(' & ', ' &\n').split('\n').map((part, k) => (
              <span key={k} className="block">{part}</span>
            ))}
          </NavLink>
        ))}
      </nav>
      <div className="flex justify-center py-3 border-t border-slate-100">
        <span className="w-8 h-8 rounded-full bg-pa-700 text-white text-[11px] font-bold flex items-center justify-center" title={`${user?.name} · ${(user?.role ?? '').replace('_', ' ')}`}>
          {initials(user?.name)}
        </span>
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
