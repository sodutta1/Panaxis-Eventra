import { useEffect, useRef, useState } from 'react'
import { Html5Qrcode } from 'html5-qrcode'
import { api } from '../api'
import { Card, PageHeader, btnCls, btnGhostCls, inputCls } from '../components/ui'

type ScanResult = { status: 'success' | 'warning' | 'error'; message: string; at: string }

const READER_ID = 'qr-reader'

export default function GateScanner() {
  const scannerRef = useRef<Html5Qrcode | null>(null)
  const [cameraState, setCameraState] = useState<'idle' | 'starting' | 'running' | 'stopping'>('idle')
  const running = cameraState === 'running'
  const cameraBusyRef = useRef(false)
  const generationRef = useRef(0)
  const mountedRef = useRef(true)
  const [verifying, setVerifying] = useState(false)
  const [cameraError, setCameraError] = useState<string | null>(null)
  const [last, setLast] = useState<ScanResult | null>(null)
  const [history, setHistory] = useState<ScanResult[]>([])
  const [manual, setManual] = useState('')
  const busyRef = useRef(false)
  const lastTokenRef = useRef<{ token: string; at: number } | null>(null)

  const verifyToken = async (token: string) => {
    // Debounce the same code for 3s so a held QR does not spam the API.
    const now = Date.now()
    if (busyRef.current) return false
    if (lastTokenRef.current && lastTokenRef.current.token === token && now - lastTokenRef.current.at < 3000) return false
    lastTokenRef.current = { token, at: now }
    busyRef.current = true
    setVerifying(true)
    let res: ScanResult
    try {
      const r = await api<{ status: string; message: string }>('/tickets/verify', { method: 'POST', body: { token } })
      res = { status: 'success', message: r.message, at: new Date().toLocaleTimeString() }
    } catch (e) {
      const status = (e as { status?: number }).status
      const cls = status === 404 ? 'error' : status === 409 ? 'warning' : 'error'
      res = { status: cls, message: (e as Error).message, at: new Date().toLocaleTimeString() }
    }
    if (mountedRef.current) {
      setLast(res)
      setHistory((h) => [res, ...h].slice(0, 8))
      setVerifying(false)
    }
    busyRef.current = false
    return true
  }

  const dispose = async (scanner: Html5Qrcode) => {
    try { await scanner.stop() } catch { /* not running or still starting */ }
    try { scanner.clear() } catch { /* detached element or still starting */ }
  }

  const start = async () => {
    if (cameraBusyRef.current || scannerRef.current) return
    cameraBusyRef.current = true
    const generation = ++generationRef.current
    setCameraState('starting')
    setCameraError(null)
    let scanner: Html5Qrcode | null = null
    try {
      scanner = new Html5Qrcode(READER_ID)
      scannerRef.current = scanner
      await scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decoded) => { if (mountedRef.current) void verifyToken(decoded.trim()) },
        () => {},
      )
      if (!mountedRef.current || generation !== generationRef.current) {
        await dispose(scanner)
        return
      }
      setCameraState('running')
    } catch (e) {
      if (scanner) await dispose(scanner)
      if (mountedRef.current && generation === generationRef.current) {
        scannerRef.current = null
        setCameraError(e instanceof Error ? e.message : String(e))
        setCameraState('idle')
      }
    } finally {
      if (generation === generationRef.current) cameraBusyRef.current = false
    }
  }

  const stop = async () => {
    if (cameraBusyRef.current) return
    cameraBusyRef.current = true
    ++generationRef.current
    setCameraState('stopping')
    const scanner = scannerRef.current
    scannerRef.current = null
    if (scanner) await dispose(scanner)
    if (mountedRef.current) setCameraState('idle')
    cameraBusyRef.current = false
  }

  useEffect(() => {
    mountedRef.current = true
    const invalidatePendingStart = () => { ++generationRef.current }
    return () => {
      mountedRef.current = false
      invalidatePendingStart()
      const scanner = scannerRef.current
      scannerRef.current = null
      if (scanner) void dispose(scanner)
    }
  }, [])

  const submitManual = async (ev: React.FormEvent) => {
    ev.preventDefault()
    const t = manual.trim()
    if (!t) return
    if (await verifyToken(t)) setManual('')
  }

  const tone = last?.status === 'success'
    ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
    : last?.status === 'warning'
      ? 'bg-amber-50 border-amber-300 text-amber-800'
      : 'bg-rose-50 border-rose-300 text-rose-800'

  return (
    <div>
      <PageHeader title="Gate Scanner" sub="Scan a ticket or enter its number to check in a guest.">
        <button className={btnGhostCls} disabled={cameraState === 'starting' || cameraState === 'stopping'} onClick={() => (running ? void stop() : void start())}>
          {cameraState === 'starting' ? 'Starting camera...' : cameraState === 'stopping' ? 'Stopping camera...' : running ? 'Stop camera' : 'Start camera'}
        </button>
      </PageHeader>

      {cameraError && (
        <div className="bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl px-4 py-3 mb-4">
          Camera unavailable: {cameraError}. Check camera permission for this site, or use manual entry below.
        </div>
      )}

      <div className="grid md:grid-cols-2 gap-4">
        <Card className="p-4">
          <div className="rounded-xl overflow-hidden bg-slate-900 aspect-[4/3] flex items-center justify-center">
            <div id={READER_ID} className="w-full h-full" />
            {!running && (
              <div className="absolute text-slate-400 text-sm pointer-events-none">Camera off</div>
            )}
          </div>
          <p className="text-xs text-pamuted mt-2">Point the camera at a ticket QR code. The same code is ignored for 3 seconds after a scan.</p>
          <form onSubmit={submitManual} className="flex gap-2 mt-3">
            <input className={inputCls} placeholder="Or paste QR token / ticket number" value={manual} onChange={(e) => setManual(e.target.value)} />
            <button className={btnCls} disabled={verifying}>{verifying ? 'Verifying...' : 'Verify'}</button>
          </form>
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
