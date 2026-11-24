import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { api, homeFor, setSession } from '../api'
import { ErrorBox, btnCls, inputCls } from '../components/ui'

const DEMO = [
  ['admin@eventflow.test', 'Super admin'],
  ['manager@eventflow.test', 'Event manager'],
  ['sales@eventflow.test', 'Sales executive'],
  ['finance@eventflow.test', 'Accountant'],
  ['client@eventflow.test', 'Client'],
  ['vendor@eventflow.test', 'Vendor'],
]

export default function Login() {
  const demoMode = import.meta.env.VITE_DEMO_MODE === 'true'
  const [email, setEmail] = useState(demoMode ? 'admin@eventflow.test' : '')
  const [password, setPassword] = useState(demoMode ? 'password' : '')
  const [error, setError] = useState<unknown>(null)
  const [busy, setBusy] = useState(false)
  const navigate = useNavigate()

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setBusy(true)
    setError(null)
    try {
      const res = await api<{ token: string; user: { role?: string } }>('/auth/login', {
        method: 'POST',
        body: { email, password, device_name: 'eventflow-frontend' },
      })
      setSession(res.token, res.user)
      navigate(homeFor(res.user?.role))
    } catch (e) {
      setError(e)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="min-h-screen bg-paper flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <img src="/logo.jpg" alt="Panaxis" className="w-16 h-16 rounded-2xl object-cover mx-auto mb-4 shadow-sm" />
          <div className="text-[28px] font-extrabold text-pa-900 tracking-tight">Panaxis Eventra</div>
          <div className="text-pamuted text-sm mt-1">Sign in to your event workspace</div>
        </div>
        <form onSubmit={submit} className="bg-white rounded-2xl border border-paline shadow-sm p-8 space-y-4">
          <ErrorBox error={error} />
          <div>
            <label htmlFor="login-email" className="block text-xs font-bold uppercase tracking-wide text-pamuted mb-1.5">Email</label>
            <input id="login-email" className={inputCls} value={email} onChange={(e) => setEmail(e.target.value)} type="email" required />
          </div>
          <div>
            <label htmlFor="login-password" className="block text-xs font-bold uppercase tracking-wide text-pamuted mb-1.5">Password</label>
            <input id="login-password" className={inputCls} value={password} onChange={(e) => setPassword(e.target.value)} type="password" required />
          </div>
          <button className={`${btnCls} w-full`} disabled={busy}>
            {busy ? 'Signing in…' : 'Sign in'}
          </button>
          {demoMode && <div className="pt-3 border-t border-slate-100">
            <div className="text-xs text-pamuted mb-2">Seeded demo accounts (password: password)</div>
            <div className="flex flex-wrap gap-1.5">
              {DEMO.map(([mail, label]) => (
                <button
                  key={mail}
                  type="button"
                  onClick={() => { setEmail(mail); setPassword('password') }}
                  className="text-xs rounded-full border border-paline text-pa-700 px-2.5 py-1 hover:bg-pa-50"
                  title={mail}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>}
        </form>
        <div className="text-center text-[11.5px] text-pamuted mt-6">Panaxis Eventra Pvt. Ltd.</div>
      </div>
    </main>
  )
}
