<?php
/**
 * LumbungKita — Backend API
 * Kemenkop Hackathon 2026 | Pilar 3 — Ekonomi Desa
 * Handles: koperasi CRUD, bundling engine, matching engine, transaksi
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// ============================================================
// SETUP & INISIALISASI
// ============================================================
define('DATA_DIR', __DIR__ . '/data');
define('FILE_KOPERASI',  DATA_DIR . '/koperasi.json');
define('FILE_TRANSAKSI', DATA_DIR . '/transaksi.json');

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

$DATA_AWAL_KOPERASI = [
    ['id'=>1,'nama'=>'Koperasi Tani Makmur',   'desa'=>'Cibadak',    'kategori'=>'Kopi',           'region'=>'Jawa Barat',       'stok'=>180,  'harga'=>42000],
    ['id'=>2,'nama'=>'Koperasi Sumber Rejeki',  'desa'=>'Pangalengan','kategori'=>'Kopi',           'region'=>'Jawa Barat',       'stok'=>240,  'harga'=>39000],
    ['id'=>3,'nama'=>'Koperasi Mekar Jaya',     'desa'=>'Ciwidey',    'kategori'=>'Kopi',           'region'=>'Jawa Barat',       'stok'=>95,   'harga'=>45000],
    ['id'=>4,'nama'=>'Koperasi Tani Lestari',   'desa'=>'Lumajang',   'kategori'=>'Beras',          'region'=>'Jawa Timur',       'stok'=>1200, 'harga'=>11500],
    ['id'=>5,'nama'=>'Koperasi Sawah Hijau',    'desa'=>'Jember',     'kategori'=>'Beras',          'region'=>'Jawa Timur',       'stok'=>900,  'harga'=>11000],
    ['id'=>6,'nama'=>'Koperasi Karya Bambu',    'desa'=>'Tasikmalaya','kategori'=>'Kerajinan Bambu','region'=>'Jawa Barat',       'stok'=>60,   'harga'=>85000],
];

if (!file_exists(FILE_KOPERASI))  file_put_contents(FILE_KOPERASI,  json_encode($DATA_AWAL_KOPERASI, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if (!file_exists(FILE_TRANSAKSI)) file_put_contents(FILE_TRANSAKSI, json_encode([], JSON_PRETTY_PRINT));

// Data permintaan pembeli nasional (sistem tetap, bisa dikembangkan ke tabel)
$BUYER_REQUESTS = [
    ['id'=>'b1','nama'=>'Kedai Kopi Nusantara (Horeca)',  'kategori'=>'Kopi',           'region'=>'Jawa Barat', 'minQty'=>400,  'maxPrice'=>44000],
    ['id'=>'b2','nama'=>'Bulog Cabang Jawa Timur',        'kategori'=>'Beras',          'region'=>'Jawa Timur', 'minQty'=>1500, 'maxPrice'=>12000],
    ['id'=>'b3','nama'=>'Toko Kerajinan Nusa Indah',      'kategori'=>'Kerajinan Bambu','region'=>'Jawa Barat', 'minQty'=>50,   'maxPrice'=>90000],
];

$KATEGORI_VALID = ['Kopi', 'Beras', 'Kerajinan Bambu', 'Kakao'];
$REGION_VALID   = ['Jawa Barat', 'Jawa Timur', 'Sumatra Utara', 'Sulawesi Selatan'];

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function baca($file)        { return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : []; }
function tulis($file, $data){ file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }
function input()            { return json_decode(file_get_contents('php://input'), true) ?? []; }

// ============================================================
// BUNDLING ENGINE
// Mengelompokkan stok dari banyak koperasi (kategori + wilayah)
// menjadi satu paket penawaran kolektif — inti gotong royong digital
// ============================================================
function hitungBundles(array $list): array {
    $map = [];
    foreach ($list as $k) {
        $key = $k['kategori'] . '|' . $k['region'];
        if (!isset($map[$key])) {
            $map[$key] = [
                'id'        => $key,
                'kategori'  => $k['kategori'],
                'region'    => $k['region'],
                'totalStok' => 0,
                'totalNilai'=> 0,
                'anggota'   => [],
            ];
        }
        $map[$key]['totalStok']  += $k['stok'];
        $map[$key]['totalNilai'] += $k['stok'] * $k['harga'];
        $map[$key]['anggota'][]   = $k;
    }

    $result = [];
    foreach ($map as $b) {
        if ($b['totalStok'] <= 0) continue;
        $b['hargaRata']  = round($b['totalNilai'] / $b['totalStok']);
        $n               = count($b['anggota']);
        // Estimasi penghematan ongkos kirim gabungan vs kirim sendiri-sendiri
        $b['hematOngkir'] = $n > 1 ? max(0, ($n * 500000) - (500000 + ($n - 1) * 150000)) : 0;
        $result[] = $b;
    }
    return $result;
}

// ============================================================
// MATCHING ENGINE
// Skor transparan (bisa dijelaskan ke juri) — bukan "AI kotak hitam"
// Kriteria: kecocokan wilayah (40) + volume (40) + harga (20) = 100
// ============================================================
function scoreMatch(array $bundle, array $req): array {
    $score   = 0;
    $reasons = [];

    // 1. Wilayah (40 poin)
    if ($bundle['region'] === $req['region']) {
        $score += 40;
        $reasons[] = ['label' => 'Wilayah cocok dengan tujuan pembeli', 'pts' => 40];
    } else {
        $reasons[] = ['label' => 'Wilayah berbeda dari tujuan pembeli', 'pts' => 0];
    }

    // 2. Volume (40 poin, proporsional)
    $ratio   = $req['minQty'] > 0 ? $bundle['totalStok'] / $req['minQty'] : 1;
    $pts     = (int) round(min(1, $ratio) * 40);
    $score  += $pts;
    $reasons[] = [
        'label' => $ratio >= 1
            ? 'Volume minimum terpenuhi sepenuhnya'
            : 'Volume baru ' . round($ratio * 100) . '% dari target pembeli',
        'pts' => $pts,
    ];

    // 3. Harga (20 poin)
    if ($bundle['hargaRata'] <= $req['maxPrice']) {
        $score += 20;
        $reasons[] = ['label' => 'Harga rata-rata sesuai budget pembeli', 'pts' => 20];
    } else {
        $reasons[] = ['label' => 'Harga rata-rata di atas budget pembeli', 'pts' => 0];
    }

    return [
        'score'     => $score,
        'reasons'   => $reasons,
        'fulfilled' => $ratio >= 1,
    ];
}

// ============================================================
// ROUTER
// ============================================================
$action = $_GET['action'] ?? '';

switch ($action) {

    // --- Daftar koperasi ---
    case 'koperasi':
        echo json_encode(baca(FILE_KOPERASI));
        break;

    // --- Tambah koperasi baru ---
    case 'add_koperasi':
        global $KATEGORI_VALID, $REGION_VALID;
        $b     = input();
        $nama  = trim($b['nama']     ?? '');
        $desa  = trim($b['desa']     ?? '');
        $kat   = $b['kategori']      ?? '';
        $reg   = $b['region']        ?? '';
        $stok  = (int)($b['stok']  ?? 0);
        $harga = (int)($b['harga'] ?? 0);

        if (!$nama || !$desa)                    { http_response_code(400); echo json_encode(['error' => 'Nama dan desa wajib diisi.']); break; }
        if (!in_array($kat, $KATEGORI_VALID))    { http_response_code(400); echo json_encode(['error' => 'Kategori tidak valid.']); break; }
        if (!in_array($reg, $REGION_VALID))      { http_response_code(400); echo json_encode(['error' => 'Wilayah tidak valid.']); break; }
        if ($stok <= 0 || $harga <= 0)           { http_response_code(400); echo json_encode(['error' => 'Stok dan harga harus lebih dari 0.']); break; }

        $list   = baca(FILE_KOPERASI);
        $list[] = ['id' => time() . rand(100,999), 'nama' => $nama, 'desa' => $desa, 'kategori' => $kat, 'region' => $reg, 'stok' => $stok, 'harga' => $harga];
        tulis(FILE_KOPERASI, $list);
        echo json_encode(['success' => true, 'total' => count($list)]);
        break;

    // --- Hitung bundle (bundling engine) ---
    case 'bundles':
        echo json_encode(hitungBundles(baca(FILE_KOPERASI)));
        break;

    // --- Hitung pencocokan (matching engine) ---
    case 'matches':
        global $BUYER_REQUESTS;
        $bundles = hitungBundles(baca(FILE_KOPERASI));
        $result  = [];
        foreach ($BUYER_REQUESTS as $req) {
            $cands = [];
            foreach ($bundles as $bun) {
                if ($bun['kategori'] !== $req['kategori']) continue;
                $m       = scoreMatch($bun, $req);
                $cands[] = array_merge(['bundle' => $bun], $m);
            }
            usort($cands, fn($a, $b) => $b['score'] - $a['score']);
            $result[] = ['request' => $req, 'candidates' => $cands];
        }
        echo json_encode($result);
        break;

    // --- Terima pencocokan & sinkron ke "SIMKOPDES" ---
    case 'accept_match':
        global $BUYER_REQUESTS;
        $b      = input();
        $bId    = $b['bundleId']  ?? '';
        $rId    = $b['requestId'] ?? '';

        $req = null;
        foreach ($BUYER_REQUESTS as $r) { if ($r['id'] === $rId) { $req = $r; break; } }
        if (!$req) { http_response_code(400); echo json_encode(['error' => 'Permintaan pembeli tidak ditemukan.']); break; }

        [$kat, $reg] = explode('|', $bId, 2);
        $list        = baca(FILE_KOPERASI);
        $members     = array_filter($list, fn($k) => $k['kategori'] === $kat && $k['region'] === $reg);
        $totalStok   = array_sum(array_column(array_values($members), 'stok'));

        if ($totalStok <= 0) { http_response_code(400); echo json_encode(['error' => 'Stok lumbung sudah habis.']); break; }

        $totalNilai = array_sum(array_map(fn($m) => $m['stok'] * $m['harga'], $members));
        $hargaRata  = $totalNilai / $totalStok;
        $qty        = min($totalStok, $req['minQty']);
        $kontribusi = [];
        $updated    = [];
        $sisaQty    = $qty; // untuk memastikan total terdistribusi pas

        $memberArr = array_values($members);
        foreach ($list as $k) {
            if ($k['kategori'] === $kat && $k['region'] === $reg && $k['stok'] > 0) {
                // Distribusi proporsional ke setiap koperasi anggota
                $isLast  = ($k === end($memberArr));
                $potong  = $isLast ? $sisaQty : (int) round($qty * ($k['stok'] / $totalStok));
                $potong  = min($potong, $k['stok']);
                $sisaQty -= $potong;
                if ($potong > 0) {
                    $kontribusi[] = ['nama' => $k['nama'], 'jumlah' => $potong, 'nilai' => (int) round($potong * $hargaRata)];
                }
                $k['stok'] = max(0, $k['stok'] - $potong);
            }
            $updated[] = $k;
        }

        tulis(FILE_KOPERASI, $updated);

        $txList   = baca(FILE_TRANSAKSI);
        $txList[] = [
            'id'          => time() . rand(100,999),
            'buyer'       => $req['nama'],
            'kategori'    => $kat,
            'region'      => $reg,
            'qty'         => $qty,
            'nilai'       => (int) round($qty * $hargaRata),
            'kontribusi'  => $kontribusi,
            'waktu'       => date('H:i · d/m/Y'),
        ];
        tulis(FILE_TRANSAKSI, $txList);
        echo json_encode(['success' => true]);
        break;

    // --- Riwayat transaksi ---
    case 'transaksi':
        echo json_encode(array_reverse(baca(FILE_TRANSAKSI)));
        break;

    // --- Statistik dashboard ---
    case 'stats':
        $kList      = baca(FILE_KOPERASI);
        $txList     = baca(FILE_TRANSAKSI);
        $volTotal   = 0;
        $pendapatan = [];
        foreach ($txList as $t) {
            $volTotal += $t['nilai'];
            foreach ($t['kontribusi'] as $k) {
                $pendapatan[$k['nama']] = ($pendapatan[$k['nama']] ?? 0) + $k['nilai'];
            }
        }
        arsort($pendapatan);
        $nilaiStok = array_sum(array_map(fn($k) => $k['stok'] * $k['harga'], $kList));
        $ledger    = array_map(fn($nm, $val) => ['nama' => $nm, 'nilai' => $val], array_keys($pendapatan), array_values($pendapatan));
        echo json_encode([
            'totalVolume'      => $volTotal,
            'jumlahTransaksi'  => count($txList),
            'jumlahKoperasi'   => count($kList),
            'nilaiStok'        => $nilaiStok,
            'ledger'           => $ledger,
        ]);
        break;

    // --- Daftar buyer requests (untuk referensi frontend) ---
    case 'buyers':
        global $BUYER_REQUESTS;
        echo json_encode($BUYER_REQUESTS);
        break;

    // --- Reset data ke kondisi awal (berguna untuk demo ulang) ---
    case 'reset':
        global $DATA_AWAL_KOPERASI;
        tulis(FILE_KOPERASI,  $DATA_AWAL_KOPERASI);
        tulis(FILE_TRANSAKSI, []);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action tidak dikenali: ' . htmlspecialchars($action)]);
}
