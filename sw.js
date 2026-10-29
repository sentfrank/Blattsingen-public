const C="blattsingen-v59";
const FILES=["./","index.html","manifest.webmanifest","icon-192.png","icon-512.png","apple-touch-icon.png","lib/pdf.min.js","lib/pdf.worker.min.js","lib/jszip.min.js"];
self.addEventListener("install",e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(FILES)));self.skipWaiting();});
self.addEventListener("activate",e=>{e.waitUntil(caches.keys().then(ks=>Promise.all(ks.filter(k=>k!==C).map(k=>caches.delete(k)))));self.clients.claim();});
self.addEventListener("fetch",e=>{
  if(e.request.method!=="GET") return;
  const u=new URL(e.request.url);
  if(u.origin!==self.location.origin||/[?&]a=/.test(u.search)||u.pathname.includes("/dienst/")) return;   // Dienst-Aufrufe nie zwischenspeichern
  const fresh=e.request.mode==="navigate"||/\/(index\.html|sw\.js|version\.json|manifest\.webmanifest)?$/.test(u.pathname);
  e.respondWith(fetch(e.request,fresh?{cache:"no-store"}:undefined).then(r=>{const cp=r.clone();caches.open(C).then(c=>c.put(e.request,cp)).catch(()=>{});return r;}).catch(()=>caches.match(e.request).then(r=>r||caches.match("index.html"))));
});
