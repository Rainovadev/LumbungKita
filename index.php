<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LumbungKita — Platform Gotong Royong Digital Koperasi</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
<div id="app">
  <header id="lk-header">
    <div class="brand">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 5C9.8 3.8 7.5 3.5 5 5c0 2.5.5 4.8 2 6"/>
          <path d="M13 5c1.2-1.2 3.5-1.5 6 0 0 2.5-.5 4.8-2 6"/>
          <path d="M7 11c-1.5 1.5-1.5 4 0 6 2.5 0 5-.5 6-2"/>
          <path d="M17 11c1.5 1.5 1.5 4 0 6-2.5 0-5-.5-6-2"/>
          <line x1="12" y1="21" x2="12" y2="10"/>
        </svg>
      </div>
      <div>
        <h1 class="lk-title">LumbungKita</h1>
        <p class="lk-tagline">Satu lumbung digital, seribu panen desa, satu pasar nasional.</p>
      </div>
    </div>
    <div class="stat-pill">
      <div class="v" id="header-vol">Rp0</div>
      <div class="l">Volume usaha tersinkron ke SIMKOPDES</div>
    </div>
  </header>

  <nav class="tabs" id="nav-tabs">
    <button class="tab active" data-tab="koperasi">
      <span class="num">1</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Koperasi
    </button>
    <button class="tab" data-tab="lumbung">
      <span class="num">2</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Lumbung
    </button>
    <button class="tab" data-tab="pembeli">
      <span class="num">3</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      Pembeli
    </button>
    <button class="tab" data-tab="dashboard">
      <span class="num">4</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Dashboard
    </button>
    <button class="btn-ghost" id="btn-reset" title="Reset data ke kondisi awal untuk demo ulang" style="margin-left:auto">↺ Reset Demo</button>
  </nav>

  <main id="content">
    <div class="loading">Memuat data</div>
  </main>

</div>
<script>
'use strict';
const S = {
  tab:       'koperasi',
  koperasi:  [],
  bundles:   [],
  matches:   [],
  transaksi: [],
  stats:     { totalVolume:0, jumlahTransaksi:0, jumlahKoperasi:0, nilaiStok:0, ledger:[] },
  loading:   false,
  formErr:   '',
};

const KATEGORI = ['Kopi','Beras','Kerajinan Bambu','Kakao'];
const REGION   = ['Jawa Barat','Jawa Timur','Sumatra Utara','Sulawesi Selatan'];

// FORMAT HELPERS
function fmtRp(n) {
  return 'Rp' + Math.round(n).toLocaleString('id-ID');
}
function fmtNum(n) {
  return Math.round(n).toLocaleString('id-ID');
}
function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
// API
async function api(action, body = null) {
  try {
    const opts = body
      ? { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }
      : {};
    const res = await fetch('api.php?action=' + action, opts);
    return await res.json();
  } catch (e) {
    console.error('API error:', e);
    return null;
  }
}

async function loadAll() {
  const [koperasi, bundles, matches, transaksi, stats] = await Promise.all([
    api('koperasi'), api('bundles'), api('matches'), api('transaksi'), api('stats')
  ]);
  if (koperasi)  S.koperasi  = koperasi;
  if (bundles)   S.bundles   = bundles;
  if (matches)   S.matches   = matches;
  if (transaksi) S.transaksi = transaksi;
  if (stats)     S.stats     = stats;
}

// untutk visualisasi "lumbung" mengisi
function granary(pct) {
  const safe  = pct === null ? 0 : Math.min(100, pct);
  const label = pct === null ? '–' : Math.min(100, Math.round(pct)) + '%';
  const title = pct === null ? 'Belum ada target pembeli' : Math.round(pct) + '% dari target pembeli';
  return `
    <div class="granary" title="${esc(title)}">
      <div class="granary-fill" style="height:${safe}%"></div>
      <div class="granary-lbl">${esc(label)}</div>
    </div>`;
}

function targetForBundle(bundle) {
  const buyerTargets = {
    'Kopi|Jawa Barat':          400,
    'Beras|Jawa Timur':        1500,
    'Kerajinan Bambu|Jawa Barat': 50,
  };
  return buyerTargets[bundle.id] || null;
}

function renderKoperasi() {
  const rows = S.koperasi.length ? S.koperasi.map(k => `
    <div class="kop-row">
      <div>
        <div class="kop-nama">${esc(k.nama)}</div>
        <div class="kop-meta">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8z"/></svg>
          ${esc(k.desa)}, ${esc(k.region)}
        </div>
      </div>
      <div class="kop-right">
        <span class="badge">${esc(k.kategori)}</span>
        <span class="mono">${fmtNum(k.stok)} kg</span>
        <span class="mono text-muted">${fmtRp(k.harga)}/kg</span>
      </div>
    </div>`).join('') : `<div class="empty">Belum ada koperasi terdaftar.</div>`;

  const katOpts  = KATEGORI.map(k => `<option value="${esc(k)}">${esc(k)}</option>`).join('');
  const regOpts  = REGION.map(r   => `<option value="${esc(r)}">${esc(r)}</option>`).join('');

  return `
    <p class="sec-title">Daftar koperasi & input panen</p>
    <p class="sec-sub">Setiap koperasi mendaftarkan komoditas siap jual. Data ini menjadi bahan baku mesin bundling di tab Lumbung.</p>

    <div class="card" style="margin-bottom:14px">
      <form id="form-koperasi">
        <div class="form-grid">
          <div class="field"><label for="f-nama">Nama koperasi</label><input id="f-nama" name="nama" placeholder="Koperasi Tani Sejahtera" required></div>
          <div class="field"><label for="f-desa">Desa</label><input id="f-desa" name="desa" placeholder="Cisarua" required></div>
          <div class="field"><label for="f-kat">Kategori produk</label><select id="f-kat" name="kategori">${katOpts}</select></div>
          <div class="field"><label for="f-reg">Wilayah</label><select id="f-reg" name="region">${regOpts}</select></div>
          <div class="field"><label for="f-stok">Stok (kg / unit)</label><input id="f-stok" name="stok" type="number" min="1" placeholder="200" required></div>
          <div class="field"><label for="f-harga">Harga per unit (Rp)</label><input id="f-harga" name="harga" type="number" min="1" placeholder="42000" required></div>
        </div>
        <p class="err" id="form-err">${esc(S.formErr)}</p>
        <button type="submit" class="btn" id="btn-tambah">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah koperasi
        </button>
      </form>
    </div>

    <div class="card">${rows}</div>`;
}

function renderLumbung() {
  if (!S.bundles.length) return `
    <p class="sec-title">Lumbung gotong royong</p>
    <p class="sec-sub">Tambahkan koperasi di tab 1, lalu lihat bundling otomatis di sini.</p>
    <div class="empty">Belum ada stok terdaftar di lumbung.</div>`;

  const cards = S.bundles.map(b => {
    const target = targetForBundle(b);
    const pct    = target ? Math.round((b.totalStok / target) * 100) : null;
    const chipList = b.anggota.map(a => `<span class="chip">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      ${esc(a.nama)}
    </span>`).join('');

    const hematHtml = b.hematOngkir > 0 ? `
      <div class="hemat">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Estimasi hemat ongkir gabungan: <strong>${fmtRp(b.hematOngkir)}</strong> vs kirim sendiri-sendiri
      </div>` : '';

    return `
      <div class="card">
        <div class="bundle-head">
          ${granary(pct)}
          <div>
            <div class="bundle-title">${esc(b.kategori)}</div>
            <div class="bundle-loc">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8z"/></svg>
              ${esc(b.region)}
              ${pct !== null ? `<span class="badge-${pct >= 100 ? 'green' : 'red'}" style="margin-left:6px">${pct >= 100 ? '✓ Siap jual' : 'Perlu tambah stok'}</span>` : ''}
            </div>
          </div>
        </div>
        <div class="bundle-stats">
          <div class="item"><div class="v">${fmtNum(b.totalStok)} kg</div><div class="l">Total stok gabungan</div></div>
          <div class="item"><div class="v">${fmtRp(b.hargaRata)}</div><div class="l">Harga rata-rata</div></div>
          <div class="item"><div class="v">${b.anggota.length}</div><div class="l">Koperasi anggota</div></div>
          ${target ? `<div class="item"><div class="v">${fmtNum(target)} kg</div><div class="l">Target volume pembeli</div></div>` : ''}
        </div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Koperasi dalam lumbung ini:</div>
        <div class="chip-list">${chipList}</div>
        ${hematHtml}
      </div>`;
  }).join('');

  return `
    <p class="sec-title">Lumbung gotong royong</p>
    <p class="sec-sub">Mesin bundling otomatis mengelompokkan stok dari beberapa koperasi berdasarkan kategori + wilayah — kekuatan kolektif untuk bersaing di pasar nasional.</p>
    <div class="grid-2">${cards}</div>`;
}

function renderPembeli() {
  const cards = S.matches.map(({ request: req, candidates }) => {
    const top = candidates[0];
    const topScoreHtml = top
      ? `<div class="score-ring">${top.score}<span>/100</span></div>` : '';

    const reasonsHtml = top ? `
      <ul class="reasons">
        ${top.reasons.map(r => `
          <li>
            <span>${esc(r.label)}</span>
            <span class="pts">+${r.pts}</span>
          </li>`).join('')}
      </ul>` : '';

    const footHtml = top ? `
      <div class="match-foot">
        <span class="text-muted" style="font-size:12.5px">
          Bundle: ${esc(top.bundle.region)} · stok ${fmtNum(top.bundle.totalStok)} kg · ${fmtRp(top.bundle.hargaRata)}/kg
        </span>
        <button class="btn btn-sm" data-action="accept"
          data-bundle-id="${esc(top.bundle.id)}"
          data-request-id="${esc(req.id)}"
          ${top.bundle.totalStok <= 0 ? 'disabled' : ''}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          Terima & sinkronkan
        </button>
      </div>` : `<div class="empty" style="margin-top:10px">Belum ada bundle untuk kategori ini.</div>`;

    return `
      <div class="card">
        <div class="match-head">
          <div>
            <div class="match-nama">${esc(req.nama)}</div>
            <div class="match-meta">
              ${esc(req.kategori)} · ${esc(req.region)} · butuh min. ${fmtNum(req.minQty)} kg · maks. ${fmtRp(req.maxPrice)}/kg
            </div>
          </div>
          ${topScoreHtml}
        </div>
        <div class="divider"></div>
        ${reasonsHtml}
        ${footHtml}
      </div>`;
  }).join('');

  return `
    <p class="sec-title">Pencocokan ke pembeli nasional</p>
    <p class="sec-sub">Setiap permintaan pembeli dicocokkan ke bundle lumbung dengan skor transparan — bukan AI kotak hitam, melainkan logika yang bisa dipertanggungjawabkan.</p>
    ${cards || '<div class="empty">Belum ada data pencocokan.</div>'}`;
}

function renderDashboard() {
  const { totalVolume, jumlahTransaksi, jumlahKoperasi, nilaiStok, ledger } = S.stats;
  const maxLedger = ledger.length ? ledger[0].nilai : 1;

  const ledgerHtml = ledger.length ? ledger.map(p => `
    <div class="ledger-row">
      <div class="ledger-name" title="${esc(p.nama)}">${esc(p.nama)}</div>
      <div class="ledger-track">
        <div class="ledger-fill" style="width:${Math.round((p.nilai/maxLedger)*100)}%"></div>
      </div>
      <div class="ledger-val">${fmtRp(p.nilai)}</div>
    </div>`).join('') :
    `<div class="empty">Belum ada distribusi. Terima pencocokan di tab Pembeli.</div>`;

  const txRows = S.transaksi.length ? S.transaksi.map(t => `
    <tr>
      <td>${esc(t.waktu)}</td>
      <td>${esc(t.buyer)}</td>
      <td>${esc(t.kategori)}</td>
      <td class="mono">${fmtNum(t.qty)} kg</td>
      <td class="mono text-gold">${fmtRp(t.nilai)}</td>
    </tr>`).join('') :
    `<tr><td colspan="5" class="text-muted" style="padding:20px;text-align:center">Belum ada transaksi tersinkron.</td></tr>`;

  return `
    <p class="sec-title">Dashboard volume usaha</p>
    <p class="sec-sub">Setiap transaksi yang diterima otomatis tersinkron ke sini — mensimulasikan sinkronisasi ke pembukuan SIMKOPDES secara real-time.</p>

    <div class="stats-row">
      <div class="stat-card">
        <div class="v">${fmtRp(totalVolume)}</div>
        <div class="l">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          Volume usaha tersinkron ke SIMKOPDES
        </div>
      </div>
      <div class="stat-card">
        <div class="v">${jumlahTransaksi}</div>
        <div class="l">Transaksi berhasil tersinkron</div>
      </div>
      <div class="stat-card">
        <div class="v">${jumlahKoperasi}</div>
        <div class="l">Koperasi aktif di platform</div>
      </div>
      <div class="stat-card">
        <div class="v">${fmtRp(nilaiStok)}</div>
        <div class="l">Nilai stok tersisa di lumbung</div>
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <p style="font-family:var(--ff-display);font-size:16px;font-weight:600;margin-bottom:14px">
          ✦ Transparansi distribusi pendapatan
        </p>
        ${ledgerHtml}
      </div>
      <div class="card">
        <p style="font-family:var(--ff-display);font-size:16px;font-weight:600;margin-bottom:14px">
          Riwayat sinkronisasi SIMKOPDES
        </p>
        <div style="overflow-x:auto">
          <table class="tx">
            <thead><tr><th>Waktu</th><th>Pembeli</th><th>Komoditas</th><th>Volume</th><th>Nilai</th></tr></thead>
            <tbody>${txRows}</tbody>
          </table>
        </div>
      </div>
    </div>`;
}

// RENDER
function render() {
  const content = document.getElementById('content');
  switch (S.tab) {
    case 'koperasi':  content.innerHTML = renderKoperasi();  break;
    case 'lumbung':   content.innerHTML = renderLumbung();   break;
    case 'pembeli':   content.innerHTML = renderPembeli();   break;
    case 'dashboard': content.innerHTML = renderDashboard(); break;
  }
  // Update header stat
  document.getElementById('header-vol').textContent = fmtRp(S.stats.totalVolume || 0);
}

// EVENT DELEGATION
document.getElementById('content').addEventListener('submit', async (e) => {
  if (e.target.id !== 'form-koperasi') return;
  e.preventDefault();
  const form  = e.target;
  const btn   = document.getElementById('btn-tambah');
  const errEl = document.getElementById('form-err');
  btn.disabled = true;
  btn.textContent = 'Menyimpan...';

  const data = {
    nama:     form.nama.value.trim(),
    desa:     form.desa.value.trim(),
    kategori: form.kategori.value,
    region:   form.region.value,
    stok:     Number(form.stok.value),
    harga:    Number(form.harga.value),
  };

  const res = await api('add_koperasi', data);
  if (res && res.success) {
    S.formErr = '';
    form.reset();
    await loadAll();
    render();
  } else {
    S.formErr = (res && res.error) ? res.error : 'Terjadi kesalahan, coba lagi.';
    if (errEl) errEl.textContent = S.formErr;
    btn.disabled = false;
    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Tambah koperasi`;
  }
});

document.getElementById('content').addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-action="accept"]');
  if (!btn) return;

  const bundleId  = btn.dataset.bundleId;
  const requestId = btn.dataset.requestId;
  btn.disabled    = true;
  btn.textContent = 'Memproses...';

  const res = await api('accept_match', { bundleId, requestId });
  if (res && res.success) {
    await loadAll();
    render();
  } else {
    alert((res && res.error) ? res.error : 'Terjadi kesalahan.');
    btn.disabled = false;
    btn.textContent = 'Terima & sinkronkan';
  }
});

// TAB SWITCHING
document.getElementById('nav-tabs').addEventListener('click', async (e) => {
  const tabBtn = e.target.closest('[data-tab]');
  if (!tabBtn) return;

  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  tabBtn.classList.add('active');
  S.tab = tabBtn.dataset.tab;

  document.getElementById('content').innerHTML = '<div class="loading">Memuat data</div>';
  await loadAll();
  render();
});

// RESET DEMO
document.getElementById('btn-reset').addEventListener('click', async () => {
  if (!confirm('Reset semua data ke kondisi awal untuk demo ulang?')) return;
  const btn   = document.getElementById('btn-reset');
  btn.disabled = true;
  await api('reset');
  await loadAll();
  render();
  btn.disabled = false;
});

// INIT
(async () => {
  await loadAll();
  render();
})();
</script>
</body>
</html>
