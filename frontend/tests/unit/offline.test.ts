// @vitest-environment jsdom
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { api, ApiError, clearSession, OFFLINE_MESSAGE } from '../../src/api'

const ok = (body: unknown) => Promise.resolve(new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } }))

describe('offline support', () => {
  beforeEach(() => {
    localStorage.clear()
    localStorage.setItem('ef_user', JSON.stringify({ id: 7 }))
    vi.restoreAllMocks()
  })

  it('serves the last saved copy of a read when the network is down', async () => {
    vi.spyOn(globalThis, 'fetch').mockImplementationOnce(() => ok({ data: [{ id: 1, name: 'Acme' }] }))
    await api('/clients-lookup')
    vi.spyOn(globalThis, 'fetch').mockRejectedValueOnce(new TypeError('Failed to fetch'))
    const again = await api<{ data: { name: string }[] }>('/clients-lookup')
    expect(again.data[0].name).toBe('Acme')
  })

  it('fails a read with no saved copy', async () => {
    vi.spyOn(globalThis, 'fetch').mockRejectedValueOnce(new TypeError('Failed to fetch'))
    await expect(api('/never-loaded')).rejects.toBeInstanceOf(TypeError)
  })

  it('never queues a write: it fails with a plain offline message', async () => {
    vi.spyOn(globalThis, 'fetch').mockRejectedValueOnce(new TypeError('Failed to fetch'))
    await expect(api('/clients', { method: 'POST', body: { name: 'x' } })).rejects.toMatchObject({ status: 0, message: OFFLINE_MESSAGE })
  })

  it('does not hand saved data to another user, and clears it on sign out', async () => {
    vi.spyOn(globalThis, 'fetch').mockImplementationOnce(() => ok({ data: [1] }))
    await api('/clients-lookup')
    localStorage.setItem('ef_user', JSON.stringify({ id: 8 }))
    vi.spyOn(globalThis, 'fetch').mockRejectedValueOnce(new TypeError('Failed to fetch'))
    await expect(api('/clients-lookup')).rejects.toBeInstanceOf(TypeError)
    localStorage.setItem('ef_user', JSON.stringify({ id: 7 }))
    clearSession()
    expect(Object.keys(localStorage).some((k) => k.startsWith('ef_cache:'))).toBe(false)
    expect(ApiError).toBeDefined()
  })
})
