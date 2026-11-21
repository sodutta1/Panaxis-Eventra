export type User = {
  id: number
  name: string
  email: string
  role: string
  company_id: number
}

export type Lead = {
  id: number
  title: string
  contact_name: string
  email?: string
  phone?: string
  source: string
  event_type?: string
  event_date?: string
  estimated_guests?: number
  estimated_budget?: string
  status: string
}

export type Client = {
  id: number
  client_code: string
  name: string
  company_name?: string
  email?: string
  phone?: string
  city?: string
  client_type: string
  status: string
  events_count?: number
}

export type EventItem = {
  id: number
  event_code: string
  title: string
  event_type: string
  status: string
  start_date: string
  end_date: string
  expected_guests?: number
  confirmed_guests?: number
  contract_value?: string
  amount_received?: string
  client?: Client
  venue?: Venue
  category?: { id: number; name: string }
  manager?: User
  schedules?: Schedule[]
  tasks?: Task[]
}

export type Schedule = {
  id: number
  start_time: string
  end_time?: string
  title: string
  location_within_venue?: string
  in_charge_person?: string
}

export type Task = {
  id: number
  event_id?: number
  title: string
  due_date?: string
  priority: string
  status: string
  phase?: string
  depends_on?: number | null
  blocked?: boolean
  assigned_user?: User
}

export type Venue = {
  id: number
  name: string
  type: string
  city: string
  capacity: number
  rental_cost_per_day: string
  status: string
  contact_person?: string
  contact_phone?: string
  contact_email?: string
  state?: string
  address?: string
  amenities?: string
}

export type VendorCategory = {
  id: number
  name: string
}

export type TicketType = {
  id: number
  event_id: number
  name: string
  price: string | number
  total_capacity?: number
  sold_count?: number
}

export type Vendor = {
  id: number
  vendor_code: string
  name: string
  business_name?: string
  city?: string
  rating: string
  status: string
  category?: { id: number; name: string }
}

export type Quotation = {
  id: number
  quotation_number: string
  client?: Client
  issue_date: string
  valid_until?: string
  subtotal: string
  tax_rate: string
  tax_amount: string
  grand_total: string
  status: string
}

export type Invoice = {
  id: number
  invoice_number: string
  client?: Client
  client_id: number
  invoice_date: string
  due_date: string
  grand_total: string
  paid_amount: string
  balance_due: string
  status: string
}

export type Expense = {
  id: number
  expense_number: string
  title: string
  amount: string
  incurred_date: string
  payment_method: string
  status: string
}

export type Guest = {
  id: number
  event_id: number
  name: string
  email?: string
  plus_ones: number
  table_number?: string
  dietary_preference: string
  rsvp_status: string
  checked_in: boolean
  checked_in_at?: string
}

export type Ticket = {
  id: number
  ticket_number: string
  qr_token: string
  holder_name: string
  price_paid: string
  payment_status: string
  ticket_status: string
  checked_in_at?: string
  event?: { id: number; title: string }
  ticket_type?: { id: number; name: string }
}

export const inr = (v?: string | number | null) =>
  v === undefined || v === null
    ? '-'
    : '₹' + Number(v).toLocaleString('en-IN', { maximumFractionDigits: 0 })

// Compact Indian formatting for headline figures: ₹1.84 Cr, ₹23.6 L
export const inrCompact = (v?: string | number | null) => {
  if (v === undefined || v === null) return '-'
  const n = Number(v)
  if (!Number.isFinite(n)) return '-'
  if (Math.abs(n) >= 1e7) return '₹' + (n / 1e7).toFixed(2).replace(/\.?0+$/, '') + ' Cr'
  if (Math.abs(n) >= 1e5) return '₹' + (n / 1e5).toFixed(1).replace(/\.0$/, '') + ' L'
  return inr(n)
}

export const STATUS_COLORS: Record<string, string> = {
  confirmed: 'bg-emerald-100 text-emerald-800',
  in_planning: 'bg-amber-100 text-amber-800',
  proposed: 'bg-sky-100 text-sky-800',
  completed: 'bg-slate-200 text-slate-700',
  cancelled: 'bg-rose-100 text-rose-800',
  new: 'bg-sky-100 text-sky-800',
  qualified: 'bg-amber-100 text-amber-800',
  converted: 'bg-emerald-100 text-emerald-800',
  lost: 'bg-rose-100 text-rose-800',
  active: 'bg-emerald-100 text-emerald-800',
  paid: 'bg-emerald-100 text-emerald-800',
  unpaid: 'bg-rose-100 text-rose-800',
  partially_paid: 'bg-amber-100 text-amber-800',
  overdue: 'bg-rose-100 text-rose-800',
  draft: 'bg-slate-200 text-slate-700',
  accepted: 'bg-emerald-100 text-emerald-800',
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  valid: 'bg-emerald-100 text-emerald-800',
  checked_in: 'bg-sky-100 text-sky-800',
  assigned: 'bg-sky-100 text-sky-800',
  in_progress: 'bg-amber-100 text-amber-800',
  inactive: 'bg-slate-200 text-slate-700',
}

export type BudgetItem = {
  id: number
  category: string
  item_name: string
  estimated_cost: string
  actual_cost?: string
  notes?: string
}

export type Budget = {
  id: number
  event_id: number
  status: string
  total_estimated_cost: string
  total_actual_cost: string
  contingency_percent: string
  contingency_amount: string
  items: BudgetItem[]
}

export type WorkOrder = {
  id: number
  event_id: number
  vendor_id: number
  service_title: string
  scope_of_work?: string
  contract_amount: string
  paid_amount: string
  status: string
  vendor?: Vendor
}

export type AdminUser = {
  id: number
  name: string
  email: string
  role: string
  status: string
}
