// @vitest-environment jsdom
import { act } from 'react'
import { createRoot, type Root } from 'react-dom/client'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { MemoryRouter } from 'react-router-dom'
import GateScanner from '../../src/pages/GateScanner'

const mocks = vi.hoisted(() => ({ start: vi.fn(), stop: vi.fn(), clear: vi.fn(), api: vi.fn() }))
vi.mock('html5-qrcode', () => ({ Html5Qrcode: class {
  start = mocks.start
  stop = mocks.stop
  clear = mocks.clear
} }))
vi.mock('../../src/api', () => ({ api: mocks.api, getUser:()=>({id:1}), API_URL:'/api/v1',getToken:()=>null }))

let root: Root
let host: HTMLDivElement
beforeEach(async () => {
  Object.assign(globalThis, { IS_REACT_ACT_ENVIRONMENT: true })
  vi.resetAllMocks()
  mocks.stop.mockResolvedValue(undefined)
  host = document.createElement('div')
  document.body.appendChild(host)
  root = createRoot(host)
  await act(async () => root.render(<MemoryRouter><GateScanner /></MemoryRouter>))
})
afterEach(async () => {
  await act(async () => root.unmount())
  host.remove()
})
const button = (text: string) => Array.from(host.querySelectorAll('button')).find((b) => b.textContent === text)!

async function click(text: string) {
  await act(async () => button(text).click())
}

describe('camera lifecycle', () => {
  it('prevents double-start while camera permission is unresolved', async () => {
    let resolve!: () => void
    mocks.start.mockReturnValue(new Promise<void>((r) => { resolve = r }))
    await click('Start camera')
    expect(button('Starting camera...').disabled).toBe(true)
    await click('Starting camera...')
    expect(mocks.start).toHaveBeenCalledTimes(1)
    await act(async () => resolve())
    expect(button('Stop camera')).toBeTruthy()
    await click('Stop camera')
    expect(mocks.stop).toHaveBeenCalledTimes(1)
    expect(mocks.clear).toHaveBeenCalledTimes(1)
    expect(button('Start camera')).toBeTruthy()
  })

  it('shows string-valued camera errors and allows retry', async () => {
    mocks.start.mockRejectedValue('Permission denied by browser')
    await click('Start camera')
    expect(host.textContent).toContain('Permission denied by browser')
    expect(mocks.clear).toHaveBeenCalledTimes(1)
    mocks.start.mockResolvedValue(undefined)
    await click('Start camera')
    expect(button('Stop camera')).toBeTruthy()
    expect(host.textContent).not.toContain('Camera unavailable:')
  })

  it('stops a camera which finishes starting after the page unmounts', async () => {
    let resolve!: () => void
    mocks.start.mockReturnValue(new Promise<void>((r) => { resolve = r }))
    await click('Start camera')
    await act(async () => root.unmount())
    await act(async () => resolve())
    expect(mocks.stop).toHaveBeenCalledTimes(2)
    expect(mocks.clear).toHaveBeenCalledTimes(2)
    // afterEach can safely unmount an already unmounted root.
  })

  it('does not debounce a different QR that arrived while an API request was busy', async () => {
    let resolve!: (v: { message: string }) => void
    mocks.api.mockReturnValueOnce(new Promise((r) => { resolve = r })).mockResolvedValue({ message: 'Second accepted' })
    mocks.start.mockResolvedValue(undefined)
    await click('Start camera')
    const decoded = mocks.start.mock.calls[0][2] as (token: string) => void
    await act(async () => { decoded('first'); decoded('second') })
    expect(mocks.api).toHaveBeenCalledTimes(1)
    await act(async () => resolve({ message: 'First accepted' }))
    await act(async () => decoded('second'))
    expect(mocks.api).toHaveBeenCalledTimes(2)
    expect(host.textContent).toContain('Second accepted')
  })
})
