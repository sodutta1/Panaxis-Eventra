import { useEffect, useRef, useState } from 'react'
import { api } from '../api'
import { Card, PageHeader, btnGhostCls } from '../components/ui'

type ScanResult = { status: 'success' | 'warning' | 'error'; message: string; at: string }

declare global {
  interface Window {
    BarcodeDetector?: new (opts?: { formats?: string[] }) => {
      detect: (source: CanvasImageSource) => Promise<{ rawValue: string }[]>
    }
  }
}

export default function GateScanner() {
  const videoRef = useRef<HTMLVideoElement>(null)
  const [supported] = useState(() => typeof window !== 'undefined' && 'BarcodeDetector' in window)
  const [running, setRunning] = useState(false)
  const [cameraError, setCameraError] = useState<string | null>(null)
  const [last, setLast] = useState<ScanResult | null>(null)
  const [history, setHistory] = useState<ScanResult[]>([])
  const busyRef = useRef(false)
  const lastTokenRef = useRef<{ token: string; at: number } | null>(null)

  const verifyToken = async (token: string) => {
    // Debounce the same code for 3s so a held QR does not spam the API.
    const now = Date.now()
    if (lastTokenRef.current && lastTokenRef.current.token === token && now - lastTokenRef.current.at < 3000) return
    lastTokenRef.current = { token, at: now }
    if (busyRef.current) return
    busyRef.current = true
    let res: ScanResult
    try {
      const r = await api<{ status: string; message: string }>('/tickets/verify', { method: 'POST', body: { token } })
      res = { status: 'success', message: r.message, at: new Date().toLocaleTimeString() }
    } catch (e) {
      const status = (e as { status?: number }).status
      const cls = status === 404 ? 'error' : status === 409 ? 'warning' : 'error'
      res = { status: cls, message: (e as Error).message, at: new Date().toLocaleTimeString() }
    }
    setLast(res)
    setHistory((h) => [res, ...h].slice(0, 8))
    busyRef.current = false
  }

  useEffect(() => {
    if (!running || !supported) return
    let stream: MediaStream | null = null
    let raf = 0
    let stopped = false

    const start = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        if (stopped) { stream.getTracks().forEach((t) => t.stop()); return }
        const video = videoRef.current
        if (!video) return
        video.srcObject = stream
        await video.play()
        const detector = new window.BarcodeDetector!({ formats: ['qr_code'] })
        const tick = async () => {
          if (stopped) return
          if (video.readyState >= 2) {
            try {
              const codes = await detector.detect(video)
              if (codes.length > 0 && codes[0].rawValue) {
                void verifyToken(codes[0].rawValue.trim())
              }
            } catch {
              // detection failure on a frame is normal - keep scanning
            }
          }
          raf = requestAnimationFrame(tick)
        }
        raf = requestAnimationFrame(tick)
      } catch (e) {
        setCameraError((e as Error).message)
        setRunning(false)
      }
    }

    void start()
    return () => {
      stopped = true
      cancelAnimationFrame(raf)
      stream?.getTracks().forEach((t) => t.stop())
    }
  }, [running, supported])

  const tone = last?.status === 'success'
    ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
    : last?.status === 'warning'
      ? 'bg-amber-50 border-amber-300 text-amber-800'
      : 'bg-rose-50 border-rose-300 text-rose-800'

  return (
    <div>
      <PageHeader title="Gate Scanner" sub="Camera QR scan → POST /api/v1/tickets/verify">
        {supported && (
          <button className={btnGhostCls} onClick={() => setRunning((r) => !r)}>
            {running ? 'Stop camera' : 'Start camera'}
          </button>
        )}
      </PageHeader>

      {!supported && (
        <Card className="p-5 mb-4 text-sm text-slate-700">
          This browser does not expose the native BarcodeDetector API. Use Chrome/Edge (or Safari 17.4+), or scan via the manual entry on the Tickets page.
        </Card>
      )}
      {cameraError && (
        <div className="bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl px-4 py-3 mb-4">
          Camera unavailable: {cameraError}. Check camera permission for this site.
        </div>
      )}

      <div className="grid md:grid-cols-2 gap-4">
        <Card className="p-4">
          <div className="rounded-xl overflow-hidden bg-slate-900 aspect-[4/3] flex items-center justify-center">
            {running ? (
              <video ref={videoRef} className="w-full h-full object-cover" muted playsInline />
            ) : (
              <div className="text-slate-400 text-sm">Camera off</div>
            )}
          </div>
          <p className="text-xs text-pamuted mt-2">Point the camera at a ticket QR code. The same code is ignored for 3 seconds after a scan.</p>
        </Card>

        <div>
          {last && (
            <div className={`rounded-xl border px-4 py-3 text-sm font-medium mb-3 ${tone}`}>
              <div className="text-[11px] uppercase tracking-wide opacity-70 mb-0.5">Latest scan · {last.at}</div>
              {last.message}
            </div>
          )}
          <Card className="p-4">
            <h3 className="text-sm font-extrabold text-pa-900 mb-2">Scan log</h3>
            {history.length === 0 && <p className="text-sm text-pamuted">No scans yet.</p>}
            {history.map((h, i) => (
              <div key={i} className={`text-[13px] rounded-lg border px-3 py-2 mb-2 ${h.status === 'success' ? 'border-emerald-200 text-emerald-800' : h.status === 'warning' ? 'border-amber-200 text-amber-800' : 'border-rose-200 text-rose-800'}`}>
                <span className="text-pamuted mr-2">{h.at}</span>{h.message}
              </div>
            ))}
          </Card>
        </div>
      </div>
    </div>
  )
}
