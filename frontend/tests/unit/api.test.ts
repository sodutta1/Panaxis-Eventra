import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api, ApiError, clearSession, getToken, setSession } from '../../src/api'

// Minimal localStorage stub for the node environment
const store = new Map<string, string>()

beforeEach(() => {
  vi.stubGlobal('localStorage', {
    getItem: (k: string) => store.get(k) ?? null,
    setItem: (k: string, v: string) => void store.set(k, v),
    removeItem: (k: string) => void store.delete(k),
  })
})

afterEach(() => {
  vi.unstubAllGlobals()
  store.clear()
})

describe('api client', () => {
  it('sends the bearer token when a session exists', async () => {
    setSession('tok123', { id: 1, name: 'T', email: 't@t', role: 'admin' })
    expect(getToken()).toBe('tok123')
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({ data: [] }) })
    vi.stubGlobal('fetch', fetchMock)
    await api('/events')
    const [, init] = fetchMock.mock.calls[0]
    expect(init.headers.Authorization).toBe('Bearer tok123')
  })

  it('throws ApiError with validation details on 422', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false,
      status: 422,
      json: async () => ({ message: 'Validation failed', errors: { title: ['Required'] } }),
    }))
    const err = await api('/x').catch((e) => e)
    expect(err).toBeInstanceOf(ApiError)
    expect(err.status).toBe(422)
    expect(err.errors.title).toEqual(['Required'])
  })

  it('clears the session on 401', async () => {
    setSession('tok123', { id: 1 })
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 401, json: async () => ({ message: 'Unauthenticated' }) }))
    await expect(api('/x')).rejects.toBeInstanceOf(ApiError)
    expect(getToken()).toBeNull()
  })

  it('download-less 204 responses return an empty object', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, status: 204, json: async () => { throw new Error('no body') } }))
    await expect(api('/x')).resolves.toEqual({})
  })
})

describe('session helpers', () => {
  it('setSession/clearSession round-trip', () => {
    setSession('abc', { id: 9 })
    expect(getToken()).toBe('abc')
    clearSession()
    expect(getToken()).toBeNull()
  })
})
