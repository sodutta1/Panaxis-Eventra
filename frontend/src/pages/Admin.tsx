import { useEffect, useState } from 'react'
import { api } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, inputCls } from '../components/ui'
import type { AdminUser } from '../types'

const ROLE_LABELS: Record<string, string> = {
  super_admin: 'Super admin', company_admin: 'Company admin', admin: 'Admin',
  event_manager: 'Event manager', sales_executive: 'Sales executive', accountant: 'Accountant',
  staff: 'Staff', client: 'Client', vendor: 'Vendor',
}

export default function Admin() {
  const [users, setUsers] = useState<AdminUser[]>([])
  const [roles, setRoles] = useState<string[]>([])
  const [error, setError] = useState<unknown>(null)
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'staff' })

  const load = () => {
    api<{ data: AdminUser[]; roles: string[] }>('/admin/users').then((r) => {
      setUsers(r.data)
      setRoles(r.roles)
    }).catch(setError)
  }

  useEffect(() => { load(); window.addEventListener('list-query',load); return ()=>window.removeEventListener('list-query',load) }, [])

  const create = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    try {
      await api('/admin/users', { method: 'POST', body: form })
      setForm({ name: '', email: '', password: '', role: 'staff' })
      load()
    } catch (err) {
      setError(err)
    }
  }

  const update = async (id: number, body: Record<string, string>) => {
    setError(null)
    try {
      await api(`/admin/users/${id}`, { method: 'PATCH', body })
      load()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <PageHeader title="Admin · Users" sub="Manage the people who can use your company workspace." />
      <ErrorBox error={error} />
      <Card className="mb-5">
        <Table head={['Name', 'Email', 'Role', 'Status', 'Actions']}>
          {users.map((u) => (
            <tr key={u.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-semibold">{u.name}</td>
              <td className="px-4 py-3 text-slate-600">{u.email}</td>
              <td className="px-4 py-3">{ROLE_LABELS[u.role] ?? u.role}</td>
              <td className="px-4 py-3"><Badge value={u.status} /></td>
              <td className="px-4 py-3">
                <div className="flex gap-2">
                  <select className="text-xs border border-paline rounded-lg px-1.5 py-1 text-ink" value={u.role} onChange={(e) => update(u.id, { role: e.target.value })}>
                    {roles.map((r) => (
                      <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>
                    ))}
                  </select>
                  <button
                    className="text-xs font-bold text-pa-700 hover:underline"
                    onClick={() => update(u.id, { status: u.status === 'active' ? 'inactive' : 'active' })}
                  >
                    {u.status === 'active' ? 'Deactivate' : 'Activate'}
                  </button>
                </div>
              </td>
            </tr>
          ))}
        </Table>
      </Card>

      <Card className="p-5 max-w-2xl">
        <h2 className="text-sm font-extrabold text-pa-900 mb-3">Create user</h2>
        <form onSubmit={create} className="grid grid-cols-2 gap-3">
          <input aria-label="Full name" required placeholder="Full name" className={inputCls} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <input aria-label="Email" required type="email" placeholder="Email" className={inputCls} value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
          <input aria-label="Password (min 8 chars)" required type="password" minLength={8} placeholder="Password (min 8 chars)" className={inputCls} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
          <select className={inputCls} value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
            {roles.map((r) => (
              <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>
            ))}
          </select>
          <div className="col-span-2">
            <button className={btnCls} type="submit">Create user</button>
          </div>
        </form>
      </Card>
    </div>
  )
}
