// App shell cache so the app still opens with no connection. API data is never cached here (the app keeps its own per-user copy and clears it on sign out).
const SHELL = 'eventra-shell-v1'
self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== SHELL).map((k) => caches.delete(k)))).then(() => self.clients.claim()))
})
self.addEventListener('fetch', (e) => {
  const req = e.request
  const url = new URL(req.url)
  if (req.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/api')) return
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).then((res) => { const copy = res.clone(); caches.open(SHELL).then((c) => c.put('/index.html', copy)); return res })
        .catch(() => caches.match('/index.html').then((r) => r || Response.error())),
    )
    return
  }
  e.respondWith(
    caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok && (url.pathname.startsWith('/assets/') || /\.(svg|jpg|png|css|js|woff2?)$/.test(url.pathname))) { const copy = res.clone(); caches.open(SHELL).then((c) => c.put(req, copy)) }
      return res
    })),
  )
})
