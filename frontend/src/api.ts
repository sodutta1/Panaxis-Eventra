// Central API client for the EventFlow ERP backend.
// Base URL: set VITE_API_URL in frontend/.env, e.g. VITE_API_URL=http://localhost:8000/api/v1
export const API_URL = import.meta.env.VITE_API_URL ?? '/api/v1'

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  constructor(status: number, message: string, errors?: Record<string, string[]>) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export function getToken(): string | null {
  return localStorage.getItem('ef_token')
}

export function setSession(token: string, user: unknown) {
  localStorage.setItem('ef_token', token)
  localStorage.setItem('ef_user', JSON.stringify(user))
}

export function clearSession() {
  localStorage.removeItem('ef_token')
  localStorage.removeItem('ef_user')
}

export function getUser(): { id: number; name: string; email: string; role: string } | null {
  const raw = localStorage.getItem('ef_user')
  return raw ? JSON.parse(raw) : null
}

export async function api<T = any>(
  path: string,
  options: { method?: string; body?: unknown } = {},
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  }
  const token = getToken()
  if (token) headers['Authorization'] = `Bearer ${token}`

  const res = await fetch(`${API_URL}${path}`, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  const json = res.status === 204 ? {} : await res.json().catch(() => ({}))

  if (!res.ok) {
    if (res.status === 401) clearSession()
    throw new ApiError(res.status, (json as any).message ?? `Request failed (${res.status})`, (json as any).errors)
  }
  return json as T
}

// Laravel paginator shape
export type Paginated<T> = {
  data: T[]
  meta: { total: number; current_page: number; last_page: number }
}

// Download an authenticated PDF (Bearer token) and trigger a browser save.
export async function downloadPdf(path: string, filename: string) {
  const res = await fetch(`${API_URL}${path}`, {
    headers: { Authorization: `Bearer ${getToken() ?? ''}`, Accept: 'application/pdf' },
  })
  if (!res.ok) throw new ApiError(res.status, `PDF download failed (${res.status})`)
  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}
