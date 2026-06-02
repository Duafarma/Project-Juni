<?php
/**
 * API: Detail outlet per ofcode untuk halaman Analysis Sales (asales.php)
 *
 * STRATEGI PENGAMBILAN DATA:
 * ─────────────────────────────────────────────────────────────────────────────
 *  1. CABANG LOKAL (self_apl = 1)
 *     • getLocalData()       → query DB lokal (CENDO + PIM) dengan INNER JOIN outlet
 *                              hanya ambil transaksi yang outlet-nya terdaftar lokal
 *     • getLocalGrandTotal() → SUM langsung, identik getSalesLazyData.php
 *
 *  2. CABANG REMOTE (self_apl = 0)
 *     • getRemoteData()      → cURL ke getRanalisDetailData.php di server cabang
 *                              ➜ outlet yang tidak ada di DB lokal AKAN muncul di sini
 *                                 karena diambil dari DB cabang (mereka punya outlet-nya)
 *
 *  3. PENGGABUNGAN
 *     • mergeInto()          → gabung lokal + semua remote
 *                              kunci: LOWER(ofcode)_LOWER(nama_out)
 *     • $grandTotal          → lokal via getLocalGrandTotal + remote via sum rows
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Parameter GET:
 *   ofcode  - kode sales
 *   bulan   - nomor bulan (1–12)
 *   tahun   - 4 digit tahun
 *   cabang  - id_apl atau 'ALL'
 *   id_mp   - filter principle (id dari master_principle), kosong = semua
 *   cari    - string pencarian nama outlet (opsional)
 *   page    - halaman pagination (default 1)
 *   status_order - status order (belum_order, sudah_order, normal)
 *   limit   - jumlah data per halaman (default 25)
 */

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');

$base = new DB;
$secu = new Security;
$conn = $base->open();

$ofcode = $secu->injection($_GET['ofcode'] ?? '');
$bulan  = (int)($secu->injection($_GET['bulan']  ?? date('n')));
$tahun  = $secu->injection($_GET['tahun']  ?? date('Y'));
$cabang = $secu->injection($_GET['cabang'] ?? 'ALL');
$cari   = $secu->injection($_GET['cari']   ?? '');
$page   = max(1, (int)($secu->injection($_GET['page'] ?? 1)));
$statusOrderFilter = strtolower(trim($secu->injection($_GET['status_order'] ?? '')));
$limitReq = (int)($secu->injection($_GET['limit'] ?? 25));
$limit  = $limitReq > 0 ? min($limitReq, 999999) : 25;
$offset = ($page - 1) * $limit;
$id_mp  = trim($secu->injection($_GET['id_mp'] ?? ''));
$explicitSpecialMode = isset($_GET['special_mode']) ? strtolower(trim($secu->injection($_GET['special_mode']))) : '';
$specialRequestOrigin = isset($_GET['special_origin']) ? strtolower(trim($secu->injection($_GET['special_origin']))) : '';

$specialMode = null;
if ($explicitSpecialMode === 'malang_b' || $explicitSpecialMode === 'medan_b') {
    $specialMode = $explicitSpecialMode;
} elseif ($cabang === '_special_malang_b') {
    $specialMode = 'malang_b';
} elseif ($cabang === '_special_medan_b') {
    $specialMode = 'medan_b';
}

function qualifySchemaTable($schema, $table) {
    return ($schema !== null && $schema !== '') ? ($schema . '.' . $table) : $table;
}

function tableExistsDirect($conn, $tableName) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function getAccessibleSchemas($conn, $currentSchema = null) {
    $schemas = [];

    if ($currentSchema !== null && $currentSchema !== '') {
        $schemas[] = $currentSchema;
    }

    try {
        $stmt = $conn->query("SELECT schema_name FROM information_schema.schemata ORDER BY schema_name ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $schemaName = isset($row['schema_name']) ? trim((string)$row['schema_name']) : '';
            if ($schemaName === '' || in_array($schemaName, ['information_schema', 'mysql', 'performance_schema', 'sys'], true)) {
                continue;
            }
            $schemas[] = $schemaName;
        }
    } catch (Exception $e) {
        try {
            $stmt = $conn->query("SHOW DATABASES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $schemaName = isset($row[0]) ? trim((string)$row[0]) : '';
                if ($schemaName === '' || in_array($schemaName, ['information_schema', 'mysql', 'performance_schema', 'sys'], true)) {
                    continue;
                }
                $schemas[] = $schemaName;
            }
        } catch (Exception $innerException) {
            // Fallback to current schema only.
        }
    }

    return array_values(array_unique(array_filter($schemas)));
}

function detectSpecialSchema($conn, $requiredTables) {
    $currentSchema = null;
    try {
        $currentSchema = $conn->query("SELECT DATABASE()")->fetchColumn();
    } catch (Exception $e) {
        $currentSchema = null;
    }

    $candidates = getAccessibleSchemas($conn, $currentSchema);

    if (empty($candidates) || empty($requiredTables)) {
        return null;
    }

    $currentDbMatches = 0;
    foreach ($requiredTables as $tableName) {
        if (!tableExistsDirect($conn, $tableName)) {
            break;
        }
        $currentDbMatches++;
    }

    if ($currentDbMatches === count($requiredTables)) {
        return '';
    }

    $tablePlaceholders = implode(',', array_fill(0, count($requiredTables), '?'));

    foreach ($candidates as $schema) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT table_name) FROM information_schema.tables WHERE table_schema = ? AND table_name IN ($tablePlaceholders)");
            $stmt->execute(array_merge([$schema], $requiredTables));
            $matched = (int)$stmt->fetchColumn();

            if ($matched !== count($requiredTables)) {
                continue;
            }

            return $schema;
        } catch (Exception $e) {
            continue;
        }
    }

    return null;
}

function resolveSpecialAnchorAplikasi($conn) {
    $stmt = $conn->prepare("SELECT id_apl, nama_apl, base_url_apl, key_apl, self_apl FROM aplikasi WHERE active_apl = 1 AND LOWER(nama_apl) = LOWER('Puri B') ORDER BY id_apl ASC LIMIT 1");
    $stmt->execute();

    $aplikasi = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($aplikasi) {
        return $aplikasi;
    }

    $fallback = $conn->prepare("SELECT id_apl, nama_apl, base_url_apl, key_apl, self_apl FROM aplikasi WHERE self_apl = 1 AND active_apl = 1 LIMIT 1");
    $fallback->execute();
    return $fallback->fetch(PDO::FETCH_ASSOC);
}

function shouldProxySpecialBranchRequest($specialRequestOrigin) {
    return $specialRequestOrigin !== 'proxy';
}

function callRemoteSpecialBranchDetailOutlet($aplikasi, $params) {
    $baseUrl = trim((string)($aplikasi['base_url_apl'] ?? ''));
    if ($baseUrl === '') {
        throw new Exception('Base URL Puri B kosong');
    }

    $apiUrl = rtrim($baseUrl, '/') . '/api/getSalesDetailOutlet.php?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        throw new Exception('Remote detail outlet request gagal: ' . $curlError);
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        throw new Exception('Remote detail outlet response bukan JSON valid');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('Remote detail outlet HTTP ' . $httpCode);
    }

    if (empty($payload['success'])) {
        throw new Exception((string)($payload['error'] ?? 'Remote detail outlet success=false'));
    }

    return $payload;
}

// NEW: jika master_principle nama = "Cendo" => nonaktifkan filter 2 tahun
$principleName = '';
$isCendoPrinciple = false;
if ($id_mp !== '') {
    $qP = $conn->prepare("SELECT nama_principle FROM master_principle WHERE id_mp = :id LIMIT 1");
    $qP->bindParam(':id', $id_mp, PDO::PARAM_STR);
    $qP->execute();
    $principleName = (string)($qP->fetchColumn() ?: '');
    $isCendoPrinciple = (strtolower(trim($principleName)) === 'cendo');
}

// window aktif (2 tahun) — EXCEPTION: principle "Cendo" => 0 (tidak pakai filter 2 tahun)
$activeYears = $isCendoPrinciple ? 0 : 2;

// Hitung bulan lalu
$bulanLalu = $bulan - 1;
$tahunLalu = $tahun;
if ($bulanLalu < 1) { $bulanLalu = 12; $tahunLalu = (int)$tahun - 1; }

// NEW: helper window tanggal (awal = 2 tahun sebelum tanggal 1 bulan terpilih, akhir = last day bulan terpilih)
function calcActiveWindowDates($tahun, $bulan, $activeYears = 2) {
    $anchor = new DateTime(sprintf('%04d-%02d-01', (int)$tahun, (int)$bulan));
    $start  = (clone $anchor)->modify('-' . (int)$activeYears . ' years');        // inclusive
    $end    = (clone $anchor)->modify('last day of this month');                  // inclusive
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

/**
 * NEW getLocalData():
 * - hanya outlet yang punya transaksi dalam 2 tahun terakhir (berdasarkan id_mp jika ada)
 * - list tetap memuat outlet yg tidak order di bulan terpilih (total=0) selama masih aktif 2 tahun terakhir
 * - tanpa N+1 query (bulan lalu) -> 1 query agregasi
 */
function getLocalData($conn, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp = '', $activeYears = 2) {
    // cek union CENDO _c
    $hasFakturC = false;
    $hasDetailC = false;
    try {
        $cek1 = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_c'");
        $hasFakturC = (bool)$cek1->fetchColumn();

        $cek2 = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_c'");
        $hasDetailC = (bool)$cek2->fetchColumn();
    } catch (Exception $e) {
        $hasFakturC = false;
        $hasDetailC = false;
    }
    $useFakturC = ($hasFakturC && $hasDetailC);

    $cendoHeaderSql = $useFakturC
        ? "(SELECT id_tfk, id_out, tgl_tfk, created_at FROM transaksi_faktur
            UNION ALL
           SELECT id_tfk, id_out, tgl_tfk, created_at FROM transaksi_faktur_c)"
        : "transaksi_faktur";

    $cendoDetailSql = $useFakturC
        ? "(SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail
            UNION ALL
           SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail_c)"
        : "transaksi_fakturdetail";

    $whereMp   = $id_mp !== '' ? " AND P.nama_p = ? " : "";
    $whereCari = $cari  !== '' ? " AND O.nama_out LIKE ? " : "";

    $dateExprCendo = "DATE(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END)";
    $dateExprPim   = "DATE(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END)";

    $eligibleParams = [];
    $twoMonthsParams = [];
    $outerParams = [
        (int)$tahun,     (int)$bulan,
        (int)$tahunLalu, (int)$bulanLalu,
    ];

    if ((int)$activeYears > 0) {
        [$startDate, $endDate] = calcActiveWindowDates($tahun, $bulan, $activeYears);

        $eligibleCendo = "
            SELECT O.id_out, UPPER(O.ofcode_out) as ofcode_out, O.nama_out
            FROM $cendoHeaderSql A
            INNER JOIN $cendoDetailSql D ON A.id_tfk = D.id_tfk
            INNER JOIN produk P ON D.id_pro = P.id_pro
            INNER JOIN outlet O ON A.id_out = O.id_out
            WHERE UPPER(O.ofcode_out) = UPPER(?)
              AND $dateExprCendo BETWEEN ? AND ?
              $whereMp
              $whereCari
            GROUP BY O.id_out, O.ofcode_out, O.nama_out
        ";

        $eligiblePim = "
            SELECT O.id_out, UPPER(O.ofcode_out) as ofcode_out, O.nama_out
            FROM transaksi_faktur_pim A
            INNER JOIN transaksi_fakturdetail_pim D ON A.id_tfk = D.id_tfk
            INNER JOIN produk P ON D.id_pro = P.id_pro
            INNER JOIN outlet O ON A.id_out = O.id_out
            WHERE UPPER(O.ofcode_out) = UPPER(?)
              AND $dateExprPim BETWEEN ? AND ?
              $whereMp
              $whereCari
            GROUP BY O.id_out, O.ofcode_out, O.nama_out
        ";

        $eligibleUnion = "(
            SELECT DISTINCT id_out, ofcode_out, nama_out FROM (
                $eligibleCendo
                UNION
                $eligiblePim
            ) X
        )";

        $eligibleParams[] = $ofcode;
        $eligibleParams[] = $startDate;
        $eligibleParams[] = $endDate;
        if ($id_mp !== '') $eligibleParams[] = $id_mp;
        if ($cari  !== '') $eligibleParams[] = "%$cari%";

        $eligibleParams[] = $ofcode;
        $eligibleParams[] = $startDate;
        $eligibleParams[] = $endDate;
        if ($id_mp !== '') $eligibleParams[] = $id_mp;
        if ($cari  !== '') $eligibleParams[] = "%$cari%";
    } else {
        $eligibleUnion = "(
            SELECT O.id_out, UPPER(O.ofcode_out) as ofcode_out, O.nama_out
            FROM outlet O
            WHERE UPPER(O.ofcode_out) = UPPER(?)
              $whereCari
        )";

        $eligibleParams[] = $ofcode;
        if ($cari !== '') $eligibleParams[] = "%$cari%";
    }

    $twoMonthsCendo = "
        SELECT O.id_out,
               YEAR($dateExprCendo) as yr,
               MONTH($dateExprCendo) as mo,
               D.total_tfd as total
        FROM $cendoHeaderSql A
        INNER JOIN $cendoDetailSql D ON A.id_tfk = D.id_tfk
        INNER JOIN produk P ON D.id_pro = P.id_pro
        INNER JOIN outlet O ON A.id_out = O.id_out
        WHERE UPPER(O.ofcode_out) = UPPER(?)
          AND (
                (YEAR($dateExprCendo) = ? AND MONTH($dateExprCendo) = ?)
             OR (YEAR($dateExprCendo) = ? AND MONTH($dateExprCendo) = ?)
          )
          $whereMp
          $whereCari
    ";

    $twoMonthsPim = "
        SELECT O.id_out,
               YEAR($dateExprPim) as yr,
               MONTH($dateExprPim) as mo,
               D.total_tfd as total
        FROM transaksi_faktur_pim A
        INNER JOIN transaksi_fakturdetail_pim D ON A.id_tfk = D.id_tfk
        INNER JOIN produk P ON D.id_pro = P.id_pro
        INNER JOIN outlet O ON A.id_out = O.id_out
        WHERE UPPER(O.ofcode_out) = UPPER(?)
          AND (
                (YEAR($dateExprPim) = ? AND MONTH($dateExprPim) = ?)
             OR (YEAR($dateExprPim) = ? AND MONTH($dateExprPim) = ?)
          )
          $whereMp
          $whereCari
    ";

    $twoMonthsUnion = "(
        $twoMonthsCendo
        UNION ALL
        $twoMonthsPim
    )";

    $sql = "
        SELECT E.id_out, E.ofcode_out, E.nama_out,
               COALESCE(SUM(CASE WHEN T.yr = ? AND T.mo = ? THEN T.total ELSE 0 END), 0) as total_now,
               COALESCE(SUM(CASE WHEN T.yr = ? AND T.mo = ? THEN T.total ELSE 0 END), 0) as total_bl
        FROM $eligibleUnion E
        LEFT JOIN $twoMonthsUnion T ON T.id_out = E.id_out
        GROUP BY E.id_out, E.ofcode_out, E.nama_out
        ORDER BY total_now DESC, E.nama_out ASC
    ";

    $twoMonthsParams[] = $ofcode;
    $twoMonthsParams[] = (int)$tahun;
    $twoMonthsParams[] = (int)$bulan;
    $twoMonthsParams[] = (int)$tahunLalu;
    $twoMonthsParams[] = (int)$bulanLalu;
    if ($id_mp !== '') $twoMonthsParams[] = $id_mp;
    if ($cari  !== '') $twoMonthsParams[] = "%$cari%";

    $twoMonthsParams[] = $ofcode;
    $twoMonthsParams[] = (int)$tahun;
    $twoMonthsParams[] = (int)$bulan;
    $twoMonthsParams[] = (int)$tahunLalu;
    $twoMonthsParams[] = (int)$bulanLalu;
    if ($id_mp !== '') $twoMonthsParams[] = $id_mp;
    if ($cari  !== '') $twoMonthsParams[] = "%$cari%";

    // PENTING:
    // urutan harus mengikuti posisi placeholder di SQL:
    // 1. outer CASE
    // 2. eligibleUnion
    // 3. twoMonthsUnion
    $params = array_merge($outerParams, $eligibleParams, $twoMonthsParams);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $rows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            'ofcode_out' => strtoupper(trim($r['ofcode_out'] ?? $ofcode)),
            'nama_out'   => $r['nama_out'],
            'total'      => (float)$r['total_now'],
            'total_bl'   => (float)$r['total_bl'],
        ];
    }

    return $rows;
}

function getSpecialBranchConfig($specialMode) {
    if ($specialMode === 'malang_b') {
        return [
            'label' => 'Malang B',
            'required_tables' => [
                'transaksi_faktur_np_malang',
                'transaksi_faktur_np_malang_b',
                'transaksi_faktur_np_malang_c',
                'transaksi_fakturdetail_np_malang',
                'transaksi_fakturdetail_np_malang_b',
                'transaksi_fakturdetail_np_malang_c',
                'produk',
                'outlet',
            ],
            'faktur_tables' => [
                'transaksi_faktur_np_malang',
                'transaksi_faktur_np_malang_b',
                'transaksi_faktur_np_malang_c',
            ],
            'detail_tables' => [
                'transaksi_fakturdetail_np_malang',
                'transaksi_fakturdetail_np_malang_b',
                'transaksi_fakturdetail_np_malang_c',
            ],
        ];
    }

    if ($specialMode === 'medan_b') {
        return [
            'label' => 'Medan B',
            'required_tables' => [
                'transaksi_faktur_np_medan',
                'transaksi_faktur_b_medan',
                'transaksi_faktur_c_medan',
                'transaksi_fakturdetail_np_medan',
                'transaksi_fakturdetail_medan_b',
                'transaksi_fakturdetail_c_medan',
                'produk',
                'outlet',
            ],
            'faktur_tables' => [
                'transaksi_faktur_np_medan',
                'transaksi_faktur_b_medan',
                'transaksi_faktur_c_medan',
            ],
            'detail_tables' => [
                'transaksi_fakturdetail_np_medan',
                'transaksi_fakturdetail_medan_b',
                'transaksi_fakturdetail_c_medan',
            ],
        ];
    }

    return null;
}

function buildSpecialUnionSql($schema, $tables, $columns) {
    $parts = [];
    foreach ($tables as $table) {
        $parts[] = "SELECT $columns FROM " . qualifySchemaTable($schema, $table);
    }
    return '(' . implode("\n            UNION ALL\n           ", $parts) . ')';
}

function getSpecialBranchData($conn, $specialMode, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp = '', $activeYears = 2) {
    $config = getSpecialBranchConfig($specialMode);
    if (!$config) {
        return [];
    }

    $schema = detectSpecialSchema($conn, $config['required_tables']);
    if ($schema === null) {
        return [];
    }

    $fakturSql = buildSpecialUnionSql($schema, $config['faktur_tables'], 'id_tfk, id_out, tgl_tfk');
    $detailSql = buildSpecialUnionSql($schema, $config['detail_tables'], 'id_tfk, id_pro, total_tfd');
    $produkSql = qualifySchemaTable($schema, 'produk');
    $outletSql = qualifySchemaTable($schema, 'outlet');

    $whereMp = $id_mp !== '' ? ' AND P.nama_p = ? ' : '';
    $whereCari = $cari !== '' ? ' AND O.nama_out LIKE ? ' : '';
    $dateExpr = 'DATE(A.tgl_tfk)';

    $eligibleParams = [];
    $twoMonthsParams = [];
    $outerParams = [
        (int)$tahun,     (int)$bulan,
        (int)$tahunLalu, (int)$bulanLalu,
    ];

    if ((int)$activeYears > 0) {
        [$startDate, $endDate] = calcActiveWindowDates($tahun, $bulan, $activeYears);
        $eligibleUnion = "(
            SELECT O.id_out, UPPER(O.ofcode_out) as ofcode_out, O.nama_out
            FROM $fakturSql A
            INNER JOIN $detailSql D ON A.id_tfk = D.id_tfk
            INNER JOIN $produkSql P ON D.id_pro = P.id_pro
            INNER JOIN $outletSql O ON A.id_out = O.id_out
            WHERE UPPER(O.ofcode_out) = UPPER(?)
              AND $dateExpr BETWEEN ? AND ?
              $whereMp
              $whereCari
            GROUP BY O.id_out, O.ofcode_out, O.nama_out
        )";

        $eligibleParams[] = $ofcode;
        $eligibleParams[] = $startDate;
        $eligibleParams[] = $endDate;
        if ($id_mp !== '') $eligibleParams[] = $id_mp;
        if ($cari !== '') $eligibleParams[] = "%$cari%";
    } else {
        $eligibleUnion = "(
            SELECT O.id_out, UPPER(O.ofcode_out) as ofcode_out, O.nama_out
            FROM $outletSql O
            WHERE UPPER(O.ofcode_out) = UPPER(?)
              $whereCari
        )";

        $eligibleParams[] = $ofcode;
        if ($cari !== '') $eligibleParams[] = "%$cari%";
    }

    $twoMonthsUnion = "(
        SELECT O.id_out,
               YEAR($dateExpr) as yr,
               MONTH($dateExpr) as mo,
               D.total_tfd as total
        FROM $fakturSql A
        INNER JOIN $detailSql D ON A.id_tfk = D.id_tfk
        INNER JOIN $produkSql P ON D.id_pro = P.id_pro
        INNER JOIN $outletSql O ON A.id_out = O.id_out
        WHERE UPPER(O.ofcode_out) = UPPER(?)
          AND (
                (YEAR($dateExpr) = ? AND MONTH($dateExpr) = ?)
             OR (YEAR($dateExpr) = ? AND MONTH($dateExpr) = ?)
          )
          $whereMp
          $whereCari
    )";

    $twoMonthsParams[] = $ofcode;
    $twoMonthsParams[] = (int)$tahun;
    $twoMonthsParams[] = (int)$bulan;
    $twoMonthsParams[] = (int)$tahunLalu;
    $twoMonthsParams[] = (int)$bulanLalu;
    if ($id_mp !== '') $twoMonthsParams[] = $id_mp;
    if ($cari !== '') $twoMonthsParams[] = "%$cari%";

    $sql = "
        SELECT E.id_out, E.ofcode_out, E.nama_out,
               COALESCE(SUM(CASE WHEN T.yr = ? AND T.mo = ? THEN T.total ELSE 0 END), 0) as total_now,
               COALESCE(SUM(CASE WHEN T.yr = ? AND T.mo = ? THEN T.total ELSE 0 END), 0) as total_bl
        FROM $eligibleUnion E
        LEFT JOIN $twoMonthsUnion T ON T.id_out = E.id_out
        GROUP BY E.id_out, E.ofcode_out, E.nama_out
        ORDER BY total_now DESC, E.nama_out ASC
    ";

    $params = array_merge($outerParams, $eligibleParams, $twoMonthsParams);
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $rows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            'ofcode_out' => strtoupper(trim($r['ofcode_out'] ?? $ofcode)),
            'nama_out'   => $r['nama_out'],
            'total'      => (float)$r['total_now'],
            'total_bl'   => (float)$r['total_bl'],
        ];
    }

    return $rows;
}

// ─────────────────────────────────────────────────────────────
// Helper: call remote getRanalisDetailData.php (cendo+pim)
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
// getRemoteData()
// Mengambil data per-outlet dari cabang remote via cURL ke getRanalisDetailData.php.
//
// Outlet yang tidak ada di DB lokal AKAN muncul di sini karena diambil dari
// DB cabang masing-masing (server remote punya tabel outlet-nya sendiri).
// Dua tipe dipanggil: cendo + pim → hasilnya digabung per nama_out.
//
// Return: ['rows' => [...], 'grandTotal' => float]
//   - rows: array per-outlet untuk ditampilkan di tabel
//   - grandTotal: SUM langsung dari API (total_penjualan_keseluruhan), bukan sum of rows
// ─────────────────────────────────────────────────────────────────────────────
function getRemoteData($aplInfo, $ofcode, $tahun, $bulan, $cari, $id_mp = '', $activeYears = 2) {
    $merged     = [];
    $grandTotal = 0;
    $tgl        = date('Y-m-d');
    $encrypt    = md5($tgl . "#" . $aplInfo['key_apl']);
    $baseUrl    = rtrim($aplInfo['base_url_apl'], '/') . "/api/getRanalisDetailData.php";

    foreach (['cendo', 'pim'] as $tipe) {
        // NEW: kirim active_years=2 ke cabang
        $url = $baseUrl
            . "?encrypt=" . $encrypt
            . "&id_apl="  . urlencode($aplInfo['id_apl'])
            . "&tipe="    . $tipe
            . "&ofcode="  . urlencode($ofcode)
            . "&tahun="   . urlencode($tahun)
            . "&bulan="   . urlencode($bulan)
            . "&id_mp="   . urlencode($id_mp)
            . "&active_years=" . urlencode((int)$activeYears)
            . "&cari="    . urlencode($cari)
            . "&page=1&limit=999999";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_SSL_VERIFYHOST  => 0,
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) continue;
        $d = json_decode($resp, true);
        if (!$d || !isset($d['result']['data'])) continue;

        $grandTotal += (float)($d['result']['total_penjualan_keseluruhan'] ?? 0);

        foreach ($d['result']['data'] as $item) {
            $oc  = strtoupper(trim($item['ofcode_out'] ?? $ofcode));
            $key = strtolower($oc) . '_' . strtolower(trim($item['nama_out']));
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'ofcode_out' => $oc,
                    'nama_out'   => $item['nama_out'],
                    'total'      => 0,
                    'total_bl'   => 0,
                ];
            }
            $merged[$key]['total']    += (float)($item['total_penjualan'] ?? 0);
            $merged[$key]['total_bl'] += (float)($item['total_bulan_lalu'] ?? 0);
        }
    }

    return [
        'rows'       => array_values($merged),
        'grandTotal' => $grandTotal,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// getLocalGrandTotal()
// Total penjualan lokal dengan SUM langsung (tanpa GROUP BY).
// INNER JOIN outlet → identik 100% dengan getSalesLazyData.php.
// Outlet di cabang lain akan masuk via getRemoteData().
// ─────────────────────────────────────────────────────────────────────────────
function getLocalGrandTotal($conn, $ofcode, $tahun, $bulan, $id_mp = '') {
    $total = 0;

    // === TAMBAHAN: gabungkan transaksi_faktur + transaksi_faktur_c (jika ada) untuk CENDO ===
    $hasFakturC = false;
    $hasDetailC = false;
    try {
        $cek1 = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_c'");
        $hasFakturC = (bool)$cek1->fetchColumn();

        $cek2 = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_c'");
        $hasDetailC = (bool)$cek2->fetchColumn();
    } catch (Exception $e) {
        $hasFakturC = false;
        $hasDetailC = false;
    }
    $useFakturC = ($hasFakturC && $hasDetailC);

    $cendoHeaderSql = $useFakturC
        ? "(SELECT id_tfk, id_out, tgl_tfk, created_at FROM transaksi_faktur
            UNION ALL
           SELECT id_tfk, id_out, tgl_tfk, created_at FROM transaksi_faktur_c)"
        : "transaksi_faktur";

    $cendoDetailSql = $useFakturC
        ? "(SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail
            UNION ALL
           SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail_c)"
        : "transaksi_fakturdetail";

    $tablePairs = [
        $cendoHeaderSql        => $cendoDetailSql,               // CENDO (detail ikut _c)
        'transaksi_faktur_pim' => 'transaksi_fakturdetail_pim',  // PIM
    ];

    foreach ($tablePairs as $tFaktur => $tDetail) {
        $joinProduk = !empty($id_mp)
            ? "INNER JOIN $tDetail D ON A.id_tfk = D.id_tfk
               INNER JOIN produk P ON D.id_pro = P.id_pro AND P.nama_p = :id_mp"
            : "INNER JOIN $tDetail D ON A.id_tfk = D.id_tfk";

        $q = $conn->prepare("
            SELECT COALESCE(SUM(D.total_tfd), 0)
            FROM $tFaktur A
            $joinProduk
            INNER JOIN outlet O ON A.id_out = O.id_out
            WHERE UPPER(O.ofcode_out) = UPPER(:ofcode)
              AND YEAR(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00'
                            THEN A.created_at ELSE A.tgl_tfk END) = :tahun
              AND MONTH(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00'
                             THEN A.created_at ELSE A.tgl_tfk END) = :bulan
        ");
        $q->bindParam(':ofcode', $ofcode, PDO::PARAM_STR);
        $q->bindParam(':tahun',  $tahun,  PDO::PARAM_STR);
        $q->bindParam(':bulan',  $bulan,  PDO::PARAM_INT);
        if (!empty($id_mp)) $q->bindParam(':id_mp', $id_mp, PDO::PARAM_STR);
        $q->execute();
        $total += (float)$q->fetchColumn();
    }
    return $total;
}

function getSpecialBranchGrandTotal($conn, $specialMode, $ofcode, $tahun, $bulan, $id_mp = '') {
    $config = getSpecialBranchConfig($specialMode);
    if (!$config) {
        return 0;
    }

    $schema = detectSpecialSchema($conn, $config['required_tables']);
    if ($schema === null) {
        return 0;
    }

    $fakturSql = buildSpecialUnionSql($schema, $config['faktur_tables'], 'id_tfk, id_out, tgl_tfk');
    $detailSql = buildSpecialUnionSql($schema, $config['detail_tables'], 'id_tfk, id_pro, total_tfd');
    $produkSql = qualifySchemaTable($schema, 'produk');
    $outletSql = qualifySchemaTable($schema, 'outlet');

    $joinProduk = !empty($id_mp)
        ? "INNER JOIN $detailSql D ON A.id_tfk = D.id_tfk
           INNER JOIN $produkSql P ON D.id_pro = P.id_pro AND P.nama_p = :id_mp"
        : "INNER JOIN $detailSql D ON A.id_tfk = D.id_tfk";

    $q = $conn->prepare("
        SELECT COALESCE(SUM(D.total_tfd), 0)
        FROM $fakturSql A
        $joinProduk
        INNER JOIN $outletSql O ON A.id_out = O.id_out
        WHERE UPPER(O.ofcode_out) = UPPER(:ofcode)
          AND YEAR(A.tgl_tfk) = :tahun
          AND MONTH(A.tgl_tfk) = :bulan
    ");
    $q->bindParam(':ofcode', $ofcode, PDO::PARAM_STR);
    $q->bindParam(':tahun', $tahun, PDO::PARAM_STR);
    $q->bindParam(':bulan', $bulan, PDO::PARAM_INT);
    if (!empty($id_mp)) $q->bindParam(':id_mp', $id_mp, PDO::PARAM_STR);
    $q->execute();

    return (float)$q->fetchColumn();
}

// ─────────────────────────────────────────────────────────────────────────────
// mergeInto(): gabungkan baris-baris dari satu cabang ke $allData bersama
// Kunci gabung: LOWER(ofcode)_LOWER(nama_out) — case-insensitive
// ─────────────────────────────────────────────────────────────────────────────
$allData    = [];
$grandTotal = 0;  // total final yang ditampilkan di header modal

function mergeInto(&$allData, $rows) {
    foreach ($rows as $row) {
        $oc  = strtoupper(trim($row['ofcode_out'] ?? ''));
        $key = strtolower($oc) . '_' . strtolower(trim($row['nama_out']));
        if (!isset($allData[$key])) {
            $allData[$key] = [
                'ofcode_out' => $oc,
                'nama_out'   => $row['nama_out'],
                'total'      => 0,
                'total_bl'   => 0,
            ];
        }
        $allData[$key]['total']    += $row['total'];
        $allData[$key]['total_bl'] += $row['total_bl'];
    }
}

try {
    if ($specialMode !== null && shouldProxySpecialBranchRequest($specialRequestOrigin)) {
        $specialAnchor = resolveSpecialAnchorAplikasi($conn);
        if ($specialAnchor) {
            try {
                $proxyPayload = callRemoteSpecialBranchDetailOutlet($specialAnchor, [
                    'ofcode' => $ofcode,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'cabang' => $specialAnchor['id_apl'],
                    'id_mp' => $id_mp,
                    'cari' => $cari,
                    'page' => $page,
                    'limit' => $limit,
                    'status_order' => $statusOrderFilter,
                    'special_mode' => $specialMode,
                    'special_origin' => 'proxy',
                ]);

                http_response_code(200);
                header('Access-Control-Allow-Origin: *');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($proxyPayload);
                $conn = $base->close();
                exit;
            } catch (Exception $proxyException) {
                error_log('getSalesDetailOutlet special proxy fallback: ' . $proxyException->getMessage());
            }
        }
    }

    if ($cabang === 'ALL') {
        // ── Ambil semua aplikasi aktif dan proses satu per satu
        $qApl = $conn->query("SELECT id_apl, nama_apl, self_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY id_apl ASC");
        while ($apl = $qApl->fetch(PDO::FETCH_ASSOC)) {
            if ($apl['self_apl'] == 1) {
                // NEW: local juga difilter aktif 2 tahun terakhir
                mergeInto($allData, getLocalData($conn, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp, $activeYears));
                $grandTotal += getLocalGrandTotal($conn, $ofcode, $tahun, $bulan, $id_mp);
            } else {
                $remote = getRemoteData($apl, $ofcode, $tahun, $bulan, $cari, $id_mp, $activeYears);
                mergeInto($allData, $remote['rows']);
                $grandTotal += $remote['grandTotal'];
            }
        }

        foreach (['malang_b', 'medan_b'] as $specialBranch) {
            mergeInto($allData, getSpecialBranchData($conn, $specialBranch, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp, $activeYears));
            $grandTotal += getSpecialBranchGrandTotal($conn, $specialBranch, $ofcode, $tahun, $bulan, $id_mp);
        }
    } elseif ($specialMode !== null) {
        mergeInto($allData, getSpecialBranchData($conn, $specialMode, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp, $activeYears));
        $grandTotal += getSpecialBranchGrandTotal($conn, $specialMode, $ofcode, $tahun, $bulan, $id_mp);
    } else {
        // ── Satu cabang spesifik
        $qApl = $conn->prepare("SELECT id_apl, nama_apl, self_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id AND active_apl = 1");
        $qApl->bindParam(':id', $cabang, PDO::PARAM_STR);
        $qApl->execute();
        $apl = $qApl->fetch(PDO::FETCH_ASSOC);

        if ($apl) {
            if ($apl['self_apl'] == 1) {
                mergeInto($allData, getLocalData($conn, $ofcode, $tahun, $bulan, $tahunLalu, $bulanLalu, $cari, $id_mp, $activeYears));
                $grandTotal += getLocalGrandTotal($conn, $ofcode, $tahun, $bulan, $id_mp);
            } else {
                $remote = getRemoteData($apl, $ofcode, $tahun, $bulan, $cari, $id_mp, $activeYears);
                mergeInto($allData, $remote['rows']);
                $grandTotal += $remote['grandTotal'];
            }
        }
    }
} catch (Exception $e) {
    error_log("getSalesDetailOutlet error: " . $e->getMessage());
}

// ── Urutkan:
//  (0) Sudah order (bulan berjalan > 0)               -> paling atas (sort total desc)
//  (1) Belum order tapi bulan lalu ada order (> 0)    -> setelah itu (sort total_bl desc)
//  (2) Belum order & bulan lalu 0                     -> paling bawah (sort nama)
uasort($allData, function($a, $b) {
    $aTotal = (float)($a['total'] ?? 0);
    $aPrev  = (float)($a['total_bl'] ?? 0);
    $bTotal = (float)($b['total'] ?? 0);
    $bPrev  = (float)($b['total_bl'] ?? 0);

    $aRank = ($aTotal > 0) ? 0 : (($aPrev > 0) ? 1 : 2);
    $bRank = ($bTotal > 0) ? 0 : (($bPrev > 0) ? 1 : 2);

    if ($aRank !== $bRank) return $aRank <=> $bRank;

    if ($aRank === 0) {
        if ($aTotal !== $bTotal) return $bTotal <=> $aTotal;
    } elseif ($aRank === 1) {
        if ($aPrev !== $bPrev) return $bPrev <=> $aPrev;
    }

    return strcmp((string)($a['nama_out'] ?? ''), (string)($b['nama_out'] ?? ''));
});

// Summary data full result
$sortedData = array_values($allData);

$summaryTotalPenjualan = 0;
$summaryTotalOrder     = 0;
$summaryTotalOutlet    = count($sortedData);

foreach ($sortedData as $row) {
    $rowTotal = (float)($row['total'] ?? 0);
    $summaryTotalPenjualan += $rowTotal;
    if ($rowTotal > 0) {
        $summaryTotalOrder++;
    }
}

$summaryTotalBelumOrder = $summaryTotalOutlet - $summaryTotalOrder;
$summaryPersenProduktifitas = $summaryTotalOutlet > 0
    ? round($summaryTotalOrder / $summaryTotalOutlet * 100, 1)
    : 0;

// Apply filter untuk modal "Belum Order"
$filteredData = $sortedData;

if ($statusOrderFilter === 'belum_order') {
    $filteredData = array_values(array_filter($sortedData, function($row) {
        return (float)($row['total'] ?? 0) <= 0;
    }));
} elseif ($statusOrderFilter === 'sudah_order' || $statusOrderFilter === 'normal') {
    $filteredData = array_values(array_filter($sortedData, function($row) {
        return (float)($row['total'] ?? 0) > 0;
    }));
}

$totalOutlet = count($filteredData);
$totalPages  = max(1, (int)ceil($totalOutlet / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$pagedData = array_slice($filteredData, $offset, $limit);

// ── Susun baris hasil dengan field tambahan (selisih, persentase, status)
$rows = [];
$no   = $offset + 1;
foreach ($pagedData as $item) {
    $selisih = $item['total'] - $item['total_bl'];
    $persen  = 0;
    if ($item['total_bl'] > 0) {
        $persen = round((($item['total'] - $item['total_bl']) / $item['total_bl']) * 100, 2);
    } elseif ($item['total'] > 0) {
        $persen = 100;
    }

    $statusOrder = $item['total'] == 0 ? 'belum_order' : 'normal';

    $rows[] = [
        'no'                       => $no++,
        'ofcode_out'               => $item['ofcode_out'] ?? '',
        'nama_out'                 => $item['nama_out'],
        'total_penjualan'          => $item['total'],
        'total_penjualan_formatted'=> number_format($item['total'],    0, ',', '.'),
        'total_bulan_lalu'         => $item['total_bl'],
        'total_bulan_lalu_formatted' => number_format($item['total_bl'], 0, ',', '.'),
        'selisih'                  => $selisih,
        'selisih_formatted'        => number_format(abs($selisih), 0, ',', '.'),
        'persentase'               => $persen,
        'status_order'             => $statusOrder,
        'keterangan'               => $statusOrder === 'belum_order' ? 'Belum Order' : '',
    ];
}

$conn = $base->close();

// ── Kirim response JSON
// total_penjualan_formatted = grandTotal (lokal + remote)
//   → lokal pakai LEFT JOIN outlet (menampilkan semua outlet termasuk yang
//     belum order), outlet cabang lain masuk via getRemoteData() API
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'                    => true,
    'data'                       => $rows,
    'total'                      => $totalOutlet,
    'total_pages'                => $totalPages,
    'page'                       => $page,
    'total_order'                => $statusOrderFilter === 'belum_order' ? 0 : $summaryTotalOrder,
    'total_belum_order'          => $statusOrderFilter === 'belum_order' ? $totalOutlet : $summaryTotalBelumOrder,
    'persen_produktifitas'       => $statusOrderFilter === 'belum_order' ? 0 : $summaryPersenProduktifitas,
    'total_penjualan_keseluruhan'=> (float)$grandTotal,
    'total_penjualan_formatted'  => number_format((float)$grandTotal, 0, ',', '.'),
]);
?>
