// Small dependency-free chart components: colorful donuts and horizontal bars.

export const PALETTE = ['#22543d', '#77957c', '#a15d20', '#a9b8a7', '#5d6860', '#91b496', '#b6a58b', '#163a2b']

export type Slice = { name: string; value: number }

export function Donut({ slices, size = 140 }: { slices: Slice[]; size?: number }) {
  const total = slices.reduce((s, x) => s + x.value, 0)
  const r = 45
  const c = 2 * Math.PI * r
  let offset = 0
  return (
    <div className="flex items-center gap-4">
      <svg width={size} height={size} viewBox="0 0 120 120" role="img" aria-label="donut chart">
        <circle cx="60" cy="60" r={r} fill="none" stroke="#e2e9e3" strokeWidth="16" />
        {total > 0 && slices.map((s, i) => {
          const frac = s.value / total
          const el = (
            <circle
              key={s.name}
              cx="60" cy="60" r={r} fill="none"
              stroke={PALETTE[i % PALETTE.length]}
              strokeWidth="16"
              strokeDasharray={`${frac * c} ${c}`}
              strokeDashoffset={-offset * c}
              transform="rotate(-90 60 60)"
            />
          )
          offset += frac
          return el
        })}
        <text x="60" y="64" textAnchor="middle" fontSize="18" fontWeight="700" fill="var(--text)">{total}</text>
      </svg>
      <ul className="text-xs space-y-1">
        {slices.map((s, i) => (
          <li key={s.name} className="flex items-center gap-2">
            <span className="inline-block w-2.5 h-2.5 rounded-full" style={{ background: PALETTE[i % PALETTE.length] }} />
            <span className="text-slate-600">{s.name}</span>
            <span className="font-semibold ml-1">{s.value}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}

export function Bars({ slices, format }: { slices: Slice[]; format?: (v: number) => string }) {
  const max = Math.max(1, ...slices.map((s) => s.value))
  return (
    <div className="space-y-2">
      {slices.map((s, i) => (
        <div key={s.name}>
          <div className="flex justify-between text-xs mb-0.5">
            <span className="text-slate-600">{s.name}</span>
            <span className="font-semibold">{format ? format(s.value) : s.value}</span>
          </div>
          <div className="h-2.5 rounded-full bg-slate-100 overflow-hidden">
            <div
              className="h-full rounded-full"
              style={{ width: `${(s.value / max) * 100}%`, background: PALETTE[i % PALETTE.length] }}
            />
          </div>
        </div>
      ))}
      {slices.length === 0 && <p className="text-xs text-slate-500">No data yet.</p>}
    </div>
  )
}
