const C="blattsingen-v74";
const FILES=["./","index.html","manifest.webmanifest","icon-192.png","icon-512.png","apple-touch-icon.png","lib/pdf.min.js","lib/pdf.worker.min.js","lib/jszip.min.js","lib/lame.min.js","ansagen/ab.mp3","ansagen/auf.mp3","ansagen/gt_frage.mp3","ansagen/gt_frage_leiter.mp3","ansagen/gt_intro_hoeren.mp3","ansagen/gt_intro_leiter.mp3","ansagen/gt_intro_leiter_singen.mp3","ansagen/gt_intro_singen.mp3","ansagen/gt_loesung.mp3","ansagen/gt_outro.mp3","ansagen/gt_sing.mp3","ansagen/i1.mp3","ansagen/i10.mp3","ansagen/i11.mp3","ansagen/i12.mp3","ansagen/i2.mp3","ansagen/i3.mp3","ansagen/i4.mp3","ansagen/i5.mp3","ansagen/i6.mp3","ansagen/i7.mp3","ansagen/i8.mp3","ansagen/i9.mp3","ansagen/intro.mp3","ansagen/outro.mp3","ansagen/s_chrom.mp3","ansagen/s_dor.mp3","ansagen/s_dur.mp3","ansagen/s_ganz.mp3","ansagen/s_harm.mp3","ansagen/s_lyd.mp3","ansagen/s_mel.mp3","ansagen/s_mix.mp3","ansagen/s_moll.mp3","ansagen/s_pdur.mp3","ansagen/s_phr.mp3","ansagen/s_pmoll.mp3","ansagen/u0.mp3","ansagen/u1.mp3","ansagen/u2.mp3","ansagen/u3.mp3","ansagen/u4.mp3","ansagen/u5.mp3","ansagen/u6.mp3","ansagen/u7.mp3","ansagen/u8.mp3","ansagen/zus.mp3"];
self.addEventListener("install",e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(FILES)));self.skipWaiting();});
self.addEventListener("activate",e=>{e.waitUntil(caches.keys().then(ks=>Promise.all(ks.filter(k=>k!==C).map(k=>caches.delete(k)))));self.clients.claim();});
self.addEventListener("fetch",e=>{
  if(e.request.method!=="GET") return;
  const u=new URL(e.request.url);
  if(u.origin!==self.location.origin||/[?&]a=/.test(u.search)||u.pathname.includes("/dienst/")) return;   // Dienst-Aufrufe nie zwischenspeichern
  const fresh=e.request.mode==="navigate"||/\/(index\.html|sw\.js|version\.json|manifest\.webmanifest)?$/.test(u.pathname);
  e.respondWith(fetch(e.request,fresh?{cache:"no-store"}:undefined).then(r=>{const cp=r.clone();caches.open(C).then(c=>c.put(e.request,cp)).catch(()=>{});return r;}).catch(()=>caches.match(e.request).then(r=>r||caches.match("index.html"))));
});
