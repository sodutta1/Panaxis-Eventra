import { describe, expect, it } from 'vitest'
import { EVENT_TIME_ZONE, formatEventDateTime } from '../../src/dateTime'

describe('public event date formatting', () => {
  it('renders the event wall time in India time regardless of browser timezone', () => {
    expect(EVENT_TIME_ZONE).toBe('Asia/Kolkata')
    expect(formatEventDateTime('2026-11-22T23:42:00+05:30')).toBe('22 Nov 2026, 11:42 pm')
  })

  it('does not shift a local event time to the browser timezone', () => {
    expect(formatEventDateTime('2026-11-22T23:42:00+05:30')).not.toContain('5:12 am')
  })
})
