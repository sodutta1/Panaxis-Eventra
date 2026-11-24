import { IconAction } from '../components/Actions'
import { useEffect, useState } from 'react'
import { api, getUser, type Paginated } from '../api'
import { Badge, Card, ErrorBox, PageHeader, Table, btnCls, btnGhostCls, inputCls } from '../components/ui'
import type { Vendor, VendorCategory } from '../types'

const emptyForm = { name: '', business_name: '', vendor_category_id: '', email: '', phone: '', city: '', services_offered: '', payment_terms: '' }

export default function Vendors() {
  const [vendors, setVendors] = useState<Vendor[]>([])
  const [categories, setCategories] = useState<VendorCategory[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<Vendor | null>(null)
  const [form, setForm] = useState(emptyForm)
  const isAdmin = ['super_admin', 'company_admin', 'admin'].includes(getUser()?.role ?? '')

  const load = () => api<Paginated<Vendor>>('/vendors').then((r) => setVendors(r.data)).catch(setError)
  useEffect(() => { load(); window.addEventListener('list-query', load); return () => window.removeEventListener('list-query', load) }, [])
  useEffect(() => {
    api<{ data: VendorCategory[] }>('/vendor-categories').then((r) => setCategories(r.data)).catch(() => {})
  }, [])

  const openCreate = () => { setEditing(null); setForm(emptyForm); setShowForm(true) }
  const openEdit = (v: Vendor) => {
    setEditing(v)
    setForm({
      name: v.name,
      business_name: v.business_name ?? '',
      vendor_category_id: String(v.category?.id ?? ''),
      email: (v as any).email ?? '',
      phone: (v as any).phone ?? '',
      city: v.city ?? '',
      services_offered: (v as any).services_offered ?? '',
      payment_terms: (v as any).payment_terms ?? '',
    })
    setShowForm(true)
  }

  const submit = async (ev: React.FormEvent) => {
    ev.preventDefault()
    setError(null)
    const body = {
      name: form.name,
      business_name: form.business_name || undefined,
      vendor_category_id: Number(form.vendor_category_id),
      email: form.email || undefined,
      phone: form.phone || undefined,
      city: form.city || undefined,
      services_offered: form.services_offered || undefined,
      payment_terms: form.payment_terms || undefined,
    }
    try {
      if (editing) await api(`/vendors/${editing.id}`, { method: 'PATCH', body })
      else await api('/vendors', { method: 'POST', body })
      setShowForm(false)
      setEditing(null)
      setForm(emptyForm)
      load()
    } catch (e) { setError(e) }
  }

  const remove = async (v: Vendor) => {
    if (!confirm(`Delete vendor "${v.name}"?`)) return
    setError(null)
    try {
      await api(`/vendors/${v.id}`, { method: 'DELETE' })
      load()
    } catch (e) { setError(e) }
  }

  return (
    <div>
      <PageHeader title="Vendors" sub="Find trusted suppliers and keep their details up to date.">
        <button className={btnCls} onClick={() => (showForm && !editing ? setShowForm(false) : openCreate())}>
          {showForm && !editing ? 'Close' : '+ New vendor'}
        </button>
      </PageHeader>
      <ErrorBox error={error} />
      {showForm && (
        <Card className="p-5 mb-6">
          <div className="text-sm font-bold text-pa-900 mb-3">{editing ? `Edit vendor: ${editing.name}` : 'Add a vendor'}</div>
          <form onSubmit={submit} className="grid md:grid-cols-3 gap-4">
            <input className={inputCls} placeholder="Contact person name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            <input className={inputCls} placeholder="Business name" value={form.business_name} onChange={(e) => setForm({ ...form, business_name: e.target.value })} />
            <select className={inputCls} value={form.vendor_category_id} onChange={(e) => setForm({ ...form, vendor_category_id: e.target.value })} required>
              <option value="">Select category…</option>
              {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
            <input className={inputCls} placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className={inputCls} placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            <input className={inputCls} placeholder="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
            <input className={inputCls} placeholder="Services offered" value={form.services_offered} onChange={(e) => setForm({ ...form, services_offered: e.target.value })} />
            <input className={inputCls} placeholder="Payment terms" value={form.payment_terms} onChange={(e) => setForm({ ...form, payment_terms: e.target.value })} />
            <div className="flex gap-2 items-end">
              <button className={btnCls}>{editing ? 'Save changes' : 'Create vendor'}</button>
              {editing && <button type="button" className={btnGhostCls} onClick={() => { setShowForm(false); setEditing(null) }}>Cancel</button>}
            </div>
          </form>
        </Card>
      )}
      <Card>
        <Table head={['Code', 'Vendor', 'Category', 'City', 'Rating', 'Status', '']}>
          {vendors.map((v) => (
            <tr key={v.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs text-slate-500">{v.vendor_code}</td>
              <td className="px-4 py-3 font-medium">{v.name}<div className="text-xs text-slate-400">{v.business_name}</div></td>
              <td className="px-4 py-3 text-slate-600">{v.category?.name ?? '-'}</td>
              <td className="px-4 py-3 text-slate-600">{v.city ?? '-'}</td>
              <td className="px-4 py-3">★ {v.rating}</td>
              <td className="px-4 py-3"><Badge value={v.status} /></td>
              <td className="px-4 py-3 text-right whitespace-nowrap">
                <IconAction label="Edit" onClick={() => openEdit(v)}/>
                {isAdmin && <IconAction label="Delete" onClick={() => remove(v)}/>}
              </td>
            </tr>
          ))}
          {vendors.length === 0 && (
            <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-400">No vendors yet.</td></tr>
          )}
        </Table>
      </Card>
    </div>
  )
}
