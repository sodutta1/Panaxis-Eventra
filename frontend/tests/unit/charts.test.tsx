// @vitest-environment jsdom
import { act } from 'react'
import { createRoot, type Root } from 'react-dom/client'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { Bars, Donut, PALETTE } from '../../src/components/charts'

let root: Root
let host: HTMLDivElement
beforeEach(() => {
  Object.assign(globalThis, { IS_REACT_ACT_ENVIRONMENT: true })
  host = document.createElement('div')
  document.body.appendChild(host)
  root = createRoot(host)
})
afterEach(async () => {
  await act(async () => root.unmount())
  host.remove()
})

describe('Donut', () => {
  it('renders the total and a legend entry per slice', async () => {
    await act(async () => root.render(<Donut slices={[{ name: 'Conference', value: 3 }, { name: 'Wedding', value: 2 }]} />))
    expect(host.textContent).toContain('Conference')
    expect(host.textContent).toContain('Wedding')
    expect(host.textContent).toContain('5') // total in the middle
    const arcs = host.querySelectorAll('circle[stroke-dasharray]')
    expect(arcs.length).toBe(2)
    expect(arcs[0].getAttribute('stroke')).toBe(PALETTE[0])
    expect(arcs[1].getAttribute('stroke')).toBe(PALETTE[1])
  })

  it('handles empty data without dividing by zero', async () => {
    await act(async () => root.render(<Donut slices={[]} />))
    expect(host.textContent).toContain('0')
  })
})

describe('Bars', () => {
  it('scales bar widths against the max value', async () => {
    await act(async () => root.render(<Bars slices={[{ name: 'Venue', value: 100 }, { name: 'Decor', value: 50 }]} />))
    const bars = host.querySelectorAll<HTMLElement>('.h-full')
    expect(bars[0].style.width).toBe('100%')
    expect(bars[1].style.width).toBe('50%')
    expect(host.textContent).toContain('Venue')
  })

  it('formats values when a formatter is given', async () => {
    await act(async () => root.render(<Bars slices={[{ name: 'Catering', value: 1200 }]} format={(v) => `₹${v}`} />))
    expect(host.textContent).toContain('₹1200')
  })
})
