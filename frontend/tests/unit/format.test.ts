import { describe, expect, it } from 'vitest'
import { inr, inrCompact, STATUS_COLORS } from '../../src/types'

describe('inr', () => {
  it('formats Indian grouping', () => {
    expect(inr(2500000)).toBe('₹25,00,000')
    expect(inr('1700000.00')).toBe('₹17,00,000')
    expect(inr(0)).toBe('₹0')
  })
  it('handles empty values', () => {
    expect(inr(null)).toBe('-')
    expect(inr(undefined)).toBe('-')
  })
})

describe('inrCompact', () => {
  it('uses crore and lakh units', () => {
    expect(inrCompact(19250000)).toBe('₹1.93 Cr')
    expect(inrCompact(2500000)).toBe('₹25 L')
    expect(inrCompact(1050000)).toBe('₹10.5 L')
  })
  it('falls back to inr below a lakh', () => {
    expect(inrCompact(45000)).toBe('₹45,000')
  })
  it('handles empty and invalid values', () => {
    expect(inrCompact(null)).toBe('-')
    expect(inrCompact('abc')).toBe('-')
  })
})

describe('STATUS_COLORS', () => {
  it('covers every status used across the app', () => {
    const required = [
      'confirmed', 'in_planning', 'proposed', 'completed', 'cancelled',
      'new', 'qualified', 'converted', 'lost', 'active', 'inactive',
      'paid', 'unpaid', 'partially_paid', 'overdue', 'draft', 'accepted',
      'pending', 'approved', 'valid', 'checked_in', 'assigned', 'in_progress',
    ]
    for (const s of required) {
      expect(STATUS_COLORS[s], `missing status color: ${s}`).toBeTruthy()
    }
  })
})
