// @vitest-environment jsdom
import { act } from 'react'
import { createRoot } from 'react-dom/client'
import { afterEach, expect, it, vi } from 'vitest'
import Login from '../../src/pages/Login'
vi.mock('react-router-dom', () => ({ useNavigate: () => vi.fn() }))
afterEach(() => vi.unstubAllEnvs())

it('hides demo identities and default credentials when demo mode is not enabled', async () => {
  vi.stubEnv('VITE_DEMO_MODE', 'false')
  Object.assign(globalThis, { IS_REACT_ACT_ENVIRONMENT: true })
  const host = document.createElement('div')
  const root = createRoot(host)
  await act(async () => root.render(<Login />))
  expect(host.textContent).not.toContain('Seeded demo accounts')
  expect(host.querySelector<HTMLInputElement>('#login-email')!.value).toBe('')
  expect(host.querySelector<HTMLInputElement>('#login-password')!.value).toBe('')
  await act(async () => root.unmount())
})

it('shows demo role shortcuts only with explicit demo-mode opt-in', async () => {
  vi.stubEnv('VITE_DEMO_MODE', 'true')
  Object.assign(globalThis, { IS_REACT_ACT_ENVIRONMENT: true })
  const host = document.createElement('div')
  const root = createRoot(host)
  await act(async () => root.render(<Login />))
  expect(host.textContent).toContain('Seeded demo accounts')
  expect(host.querySelector<HTMLInputElement>('#login-email')!.value).toBe('admin@eventflow.test')
  await act(async () => root.unmount())
})
