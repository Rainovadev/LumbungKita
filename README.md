# LumbungKita (MINIMUM VALUE PRODUCT)
**Kemenkop Hackathon Digital Cooperatives Expo 2026**
Pilar 3 — Ekonomi Desa | Tim: LumbungKita
website lumbungkita : https://mediumturquoise-dog-563025.hostingersite.com/

## Apa itu LumbungKita?
LumbungKita adalah mesin agregator berbasis "gotong royong digital" yang dirancang untuk mengatasi kesenjangan skala usaha koperasi desa. Kami menjembatani koperasi kecil (100–300kg/musim) dengan pasar institusional (minimum order 500–2.000kg) melalui algoritma bundling kolektif yang transparan dan akuntabel, tanpa menghilangkan otonomi masing-masing koperasi.

---

## Cara hosting (shared hosting / cPanel)

1. Upload **semua file** ke folder public_html (atau subfolder, misal `/lumbungkita/`)
2. Pastikan folder `data/` punya izin **write** → di cPanel File Manager, klik kanan folder `data/` → Change Permissions → centang **Write** untuk semua
3. Buka `index.php` di browser → data sample langsung muncul otomatis

## Struktur file
```
lumbungkita/
├── index.php       ← Frontend (buka ini di browser)
├── api.php         ← Backend API + algoritma bundling & matching
├── assets/
    ├── css/
        ├── style.css ← isi style css untuk desain tampilan web
├── data/
│   ├── .htaccess   ← Lindungi JSON dari akses langsung
│   ├── koperasi.json   ← Auto-dibuat saat pertama kali dibuka
│   └── transaksi.json  ← Auto-dibuat saat pertama kali dibuka
└── README.md
```

## Requirements
- PHP >= 7.4
- Ekstensi: `json`
- Folder `data/` harus writable

**Alur demo yang direkomendasikan:**
1. Buka tab **Koperasi** → tunjukkan data 6 koperasi contoh yang sudah ada
2. Klik **Tambah koperasi** → isi form → Submit → data bertambah
3. Pindah ke tab **Lumbung** → mesin bundling otomatis mengelompokkan stok
4. visualisasi "lumbung"
5. Pindah ke tab **Pembeli** → skor matching 0–100 yang transparan
6. Klik **Terima & sinkronkan** → pindah ke tab Dashboard → volume usaha bertambah real-time
7. ini mensimulasikan sinkronisasi otomatis ke pembukuan SIMKOPDES

**Tombol Reset Demo** ada di pojok kanan atas nav — untuk reset data ke kondisi awal sebelum pitching.
