<?php
/**
 * API endpoint untuk lazy loading data Analysis Sales via AJAX
 * Mengembalikan data aggregated untuk satu cabang (khusus untuk asales.php)
 * 
 * STRATEGI:
 * - Cabang LOKAL (self_apl=1): Query langsung ke database lokal
 * - Cabang REMOTE (self_apl=0): cURL ke getRanalisData.php di server cabang
 *   dengan autentikasi encrypt = md5(tgl + "#" + key_apl)
 * 
 * PARAMETER:
 * - id_apl: ID aplikasi/cabang (required)
 * - tahun: Tahun data (default: tahun sekarang)
 * 
 * RESPONSE FORMAT:
 * {
 *   "success": true,
 *   "id_apl": "1",
 *   "nama_apl": "PURI",
 *   "data": {
 *     "sales": {
 *       "ofcode": {
 *         "principle_id": {
 *           "month": value
 *         }
 *       }
 *     },
 *     "principle": {
 *       "principle_id": {
 *         "month": value
 *       }
 *     }
 *   }
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "error" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');

$secu = new Security;
$base = new DB;
$conn = $base->open();

// GET parameters
$idApl = isset($_GET['id_apl']) ? $secu->injection($_GET['id_apl']) : null;
$tahun = isset($_GET['tahun']) ? $secu->injection($_GET['tahun']) : date('Y');
$explicitSpecialMode = isset($_GET['special_mode']) ? strtolower(trim($secu->injection($_GET['special_mode']))) : '';
$specialRequestOrigin = isset($_GET['special_origin']) ? strtolower(trim($secu->injection($_GET['special_origin']))) : '';

if (!$idApl) {
    http_response_code(400);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "error" => "Parameter id_apl tidak ditemukan"]);
    exit;
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

function detectSpecialSchema($conn, $requiredTables, &$debugInfo = null) {
    $currentSchema = null;
    try {
        $currentSchema = $conn->query("SELECT DATABASE()")->fetchColumn();
    } catch (Exception $e) {
        $currentSchema = null;
    }

    $candidates = getAccessibleSchemas($conn, $currentSchema);

    if (is_array($debugInfo)) {
        $debugInfo['current_schema'] = $currentSchema;
        $debugInfo['candidates'] = $candidates;
        $debugInfo['checks'] = [];
    }

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

    if (is_array($debugInfo)) {
        $debugInfo['direct_current_db_matches'] = $currentDbMatches;
        $debugInfo['required_tables'] = count($requiredTables);
    }

    if ($currentDbMatches === count($requiredTables)) {
        if (is_array($debugInfo)) {
            $debugInfo['selected_schema'] = '(current_db)';
        }
        return '';
    }

    $tablePlaceholders = implode(',', array_fill(0, count($requiredTables), '?'));

    foreach ($candidates as $schema) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT table_name) FROM information_schema.tables WHERE table_schema = ? AND table_name IN ($tablePlaceholders)");
            $stmt->execute(array_merge([$schema], $requiredTables));
            $matched = (int)$stmt->fetchColumn();

            if (is_array($debugInfo)) {
                $debugInfo['checks'][] = [
                    'schema' => $schema,
                    'matched_tables' => $matched,
                    'required_tables' => count($requiredTables),
                ];
            }

            if ($matched !== count($requiredTables)) {
                continue;
            }
            if (is_array($debugInfo)) {
                $lastIndex = count($debugInfo['checks']) - 1;
                if ($lastIndex >= 0) {
                    $debugInfo['checks'][$lastIndex]['probe_table'] = qualifySchemaTable($schema, $requiredTables[0]);
                }
            }
            if (is_array($debugInfo)) {
                $debugInfo['selected_schema'] = $schema;
            }
            return $schema;
        } catch (Exception $e) {
            if (is_array($debugInfo)) {
                $debugInfo['checks'][] = [
                    'schema' => $schema,
                    'error' => $e->getMessage(),
                ];
            }
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

function callRemoteSpecialBranchLazyData($aplikasi, $tahun, $specialMode) {
    $baseUrl = trim((string)($aplikasi['base_url_apl'] ?? ''));
    if ($baseUrl === '') {
        throw new Exception('Base URL Puri B kosong');
    }

    $apiUrl = rtrim($baseUrl, '/') . '/api/getSalesLazyData.php?'
        . http_build_query([
            'id_apl' => $aplikasi['id_apl'],
            'tahun' => $tahun,
            'special_mode' => $specialMode,
            'special_origin' => 'proxy',
        ]);

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
        throw new Exception('Remote special branch request gagal: ' . $curlError);
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        throw new Exception('Remote special branch response bukan JSON valid');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('Remote special branch HTTP ' . $httpCode);
    }

    if (empty($payload['success'])) {
        throw new Exception((string)($payload['error'] ?? 'Remote special branch success=false'));
    }

    return $payload;
}

// Resolve virtual/special branch IDs (_special_malang_b, _special_medan_b)
// These are local special-table branches housed under Puri B (self_apl=1)
$specialMode = null;
if ($explicitSpecialMode === 'malang_b' || $explicitSpecialMode === 'medan_b') {
    $specialMode = $explicitSpecialMode;
} elseif ($idApl === '_special_malang_b') {
    $specialMode = 'malang_b';
} elseif ($idApl === '_special_medan_b') {
    $specialMode = 'medan_b';
}

try {
    $debugMeta = [
        'special_mode' => $specialMode,
        'route' => null,
        'resolved_aplikasi' => null,
        'special_schema' => [],
        'warnings' => [],
    ];

    // Get aplikasi info
    if ($specialMode !== null) {
        $aplikasi = resolveSpecialAnchorAplikasi($conn);
    } else {
        $stmtAplikasi = $conn->prepare("SELECT id_apl, nama_apl, base_url_apl, key_apl, self_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
        $stmtAplikasi->bindParam(':id_apl', $idApl, PDO::PARAM_STR);
        $stmtAplikasi->execute();
        $aplikasi = $stmtAplikasi->fetch(PDO::FETCH_ASSOC);
    }

    if (!$aplikasi) {
        throw new Exception("Aplikasi tidak ditemukan untuk id_apl=$idApl");
    }

    $debugMeta['resolved_aplikasi'] = [
        'id_apl' => $aplikasi['id_apl'] ?? null,
        'nama_apl' => $aplikasi['nama_apl'] ?? null,
        'self_apl' => isset($aplikasi['self_apl']) ? (int)$aplikasi['self_apl'] : null,
    ];

    // Use display name matching the virtual branch
    $namaApl = $specialMode === 'malang_b' ? 'Malang B'
             : ($specialMode === 'medan_b'  ? 'Medan B'
             : $aplikasi['nama_apl']);
    $selfApl = (int)$aplikasi['self_apl'];

    if ($specialMode !== null && shouldProxySpecialBranchRequest($specialRequestOrigin)) {
        try {
            $proxyPayload = callRemoteSpecialBranchLazyData($aplikasi, $tahun, $specialMode);
            $proxyPayload['id_apl'] = $idApl;
            $proxyPayload['nama_apl'] = $namaApl;

            $proxyDebug = isset($proxyPayload['debug']) && is_array($proxyPayload['debug'])
                ? $proxyPayload['debug']
                : [];
            $proxyDebug['proxy'] = [
                'mode' => 'special_remote_proxy',
                'target_id_apl' => $aplikasi['id_apl'] ?? null,
                'target_base_url' => $aplikasi['base_url_apl'] ?? null,
                'special_mode' => $specialMode,
            ];
            $proxyPayload['debug'] = $proxyDebug;

            http_response_code(200);
            header('Access-Control-Allow-Origin: *');
            header("Content-type: application/json; charset=utf-8");
            echo json_encode($proxyPayload);
            $conn = $base->close();
            exit;
        } catch (Exception $proxyException) {
            $debugMeta['warnings'][] = [
                'source' => 'special_remote_proxy',
                'message' => $proxyException->getMessage(),
            ];
        }
    }

    // Prepare result structure
    $result = [
        "success" => true,
        "id_apl" => $idApl,
        "nama_apl" => $namaApl,
        "debug" => &$debugMeta,
        "data" => [
            "sales" => [],      // ofcode -> principle_id -> month -> value
            "principle" => []   // principle_id -> month -> value (aggregate across all ofcodes)
        ]
    ];

    // Helper: proses data dari array cendo/pim ke dalam $result
    $processSourceData = function($sourceRows, &$result) {
        foreach ($sourceRows as $ofcode => $principleData) {
            if (!isset($result['data']['sales'][$ofcode])) {
                $result['data']['sales'][$ofcode] = [];
            }
            foreach ($principleData as $idMp => $monthData) {
                if (!isset($result['data']['sales'][$ofcode][$idMp])) {
                    $result['data']['sales'][$ofcode][$idMp] = [];
                }
                if (!isset($result['data']['principle'][$idMp])) {
                    $result['data']['principle'][$idMp] = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $result['data']['principle'][$idMp][$m] = 0;
                    }
                }
                foreach ($monthData as $month => $data) {
                    $value = isset($data['total_sebelum_ppn']) ? (float)$data['total_sebelum_ppn'] : 0;
                    $m = (int)$month;
                    if (!isset($result['data']['sales'][$ofcode][$idMp][$m])) {
                        $result['data']['sales'][$ofcode][$idMp][$m] = 0;
                    }
                    $result['data']['sales'][$ofcode][$idMp][$m] += $value;
                    $result['data']['principle'][$idMp][$m] += $value;
                }
            }
        }
    };

    if ($specialMode !== null || $selfApl == 1) {
        $debugMeta['route'] = ($specialMode !== null) ? 'special_local' : 'local';
       
        // Get list ofcode
        $queryOfcode = $conn->query("SELECT DISTINCT UPPER(ofcode_out) as ofcode_out FROM outlet WHERE ofcode_out IS NOT NULL AND ofcode_out != '' ORDER BY ofcode_out ASC");
        $ofcodeList = [];
        while ($row = $queryOfcode->fetch(PDO::FETCH_ASSOC)) {
            $ofcodeList[] = $row['ofcode_out'];
        }

        // Get list principle
        $queryPrinciple = $conn->query("SELECT id_mp, nama_principle FROM master_principle ORDER BY nama_principle ASC");
        $principleList = [];
        while ($row = $queryPrinciple->fetch(PDO::FETCH_ASSOC)) {
            $principleList[$row['id_mp']] = $row['nama_principle'];
        }

        // Initialize arrays
        foreach ($ofcodeList as $ofcode) {
            $result['data']['sales'][$ofcode] = [];
            foreach ($principleList as $idMp => $namaMp) {
                $result['data']['sales'][$ofcode][$idMp] = [];
                for ($month = 1; $month <= 12; $month++) {
                    $result['data']['sales'][$ofcode][$idMp][$month] = 0;
                }
            }
        }
        foreach ($principleList as $idMp => $namaMp) {
            $result['data']['principle'][$idMp] = [];
            for ($month = 1; $month <= 12; $month++) {
                $result['data']['principle'][$idMp][$month] = 0;
            }
        }

        // ---- tambah: cek tabel transaksi_faktur_c + transaksi_fakturdetail_c (CENDO)
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

        // Sumber transaksi CENDO
        $cendoFakturSource = $hasFakturC
            ? "(SELECT id_tfk, id_out, tgl_tfk, created_at, subtot_tfk, total_tfk FROM transaksi_faktur
                UNION ALL
               SELECT id_tfk, id_out, tgl_tfk, created_at, subtot_tfk, total_tfk FROM transaksi_faktur_c)"
            : "transaksi_faktur";

        // Sumber detail CENDO
        $cendoDetailSource = $hasDetailC
            ? "(SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail
                UNION ALL
               SELECT id_tfd, id_tfk, id_pro, total_tfd FROM transaksi_fakturdetail_c)"
            : "transaksi_fakturdetail";

        // Query CENDO — skip for special-only modes
        if ($specialMode !== null) goto skip_cendo_pim;
        try {
            $queryCendo = $conn->prepare("SELECT
                UPPER(O.ofcode_out) as ofcode_out,
                P.nama_p as id_mp,
                MONTH(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) as month,
                SUM(D.total_tfd) as total_sebelum_ppn
            FROM $cendoFakturSource A
            INNER JOIN $cendoDetailSource D ON A.id_tfk = D.id_tfk
            INNER JOIN produk P ON D.id_pro = P.id_pro
            INNER JOIN outlet O ON A.id_out = O.id_out
            WHERE YEAR(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :tahun
            GROUP BY UPPER(O.ofcode_out), P.nama_p, month");
            $queryCendo->bindParam(':tahun', $tahun, PDO::PARAM_STR);
            $queryCendo->execute();
            while ($row = $queryCendo->fetch(PDO::FETCH_ASSOC)) {
                $ofcode = $row['ofcode_out'];
                $idMp   = $row['id_mp'];
                $m      = (int)$row['month'];
                $value  = (float)$row['total_sebelum_ppn'];
                if (isset($result['data']['sales'][$ofcode][$idMp][$m])) {
                    $result['data']['sales'][$ofcode][$idMp][$m] += $value;
                }
                if (isset($result['data']['principle'][$idMp][$m])) {
                    $result['data']['principle'][$idMp][$m] += $value;
                }
            }
        } catch (Exception $qe) {
            $debugMeta['warnings'][] = [
                'source' => 'local_cendo',
                'message' => $qe->getMessage(),
            ];
            error_log("getSalesLazyData CENDO error: " . $qe->getMessage());
        }

        // Query PIM
        try {
            $queryPim = $conn->prepare("SELECT
                UPPER(O.ofcode_out) as ofcode_out,
                P.nama_p as id_mp,
                MONTH(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) as month,
                SUM(D.total_tfd) as total_sebelum_ppn
            FROM transaksi_faktur_pim A
            INNER JOIN transaksi_fakturdetail_pim D ON A.id_tfk = D.id_tfk
            INNER JOIN produk P ON D.id_pro = P.id_pro
            INNER JOIN outlet O ON A.id_out = O.id_out
            WHERE YEAR(CASE WHEN A.tgl_tfk IS NULL OR A.tgl_tfk = '0000-00-00' THEN A.created_at ELSE A.tgl_tfk END) = :tahun
            GROUP BY UPPER(O.ofcode_out), P.nama_p, month");
            $queryPim->bindParam(':tahun', $tahun, PDO::PARAM_STR);
            $queryPim->execute();
            while ($row = $queryPim->fetch(PDO::FETCH_ASSOC)) {
                $ofcode = $row['ofcode_out'];
                $idMp   = $row['id_mp'];
                $m      = (int)$row['month'];
                $value  = (float)$row['total_sebelum_ppn'];
                if (isset($result['data']['sales'][$ofcode][$idMp][$m])) {
                    $result['data']['sales'][$ofcode][$idMp][$m] += $value;
                }
                if (isset($result['data']['principle'][$idMp][$m])) {
                    $result['data']['principle'][$idMp][$m] += $value;
                }
            }
        } catch (Exception $qe) {
            $debugMeta['warnings'][] = [
                'source' => 'local_pim',
                'message' => $qe->getMessage(),
            ];
            error_log("getSalesLazyData PIM error: " . $qe->getMessage());
        }

        skip_cendo_pim:
        // ── SPECIAL TABLES: Malang B ──────────────────────────────────────────────
        // Hanya ada di cabang Puri B. Cek keberadaan tabel terlebih dahulu.
        // When a specific specialMode is active, only run that branch's tables.
        $specialMalangSchema = null;
        $hasMalangB = false;
        if ($specialMode === null || $specialMode === 'malang_b') {
            $debugMeta['special_schema']['malang_b'] = [];
            $specialMalangSchema = detectSpecialSchema($conn, [
                'transaksi_faktur_np_malang',
                'transaksi_faktur_np_malang_b',
                'transaksi_faktur_np_malang_c',
                'transaksi_fakturdetail_np_malang',
                'transaksi_fakturdetail_np_malang_b',
                'transaksi_fakturdetail_np_malang_c',
            ], $debugMeta['special_schema']['malang_b']);
            $hasMalangB = ($specialMalangSchema !== null);
            $debugMeta['special_schema']['malang_b']['available'] = $hasMalangB;
        }

        if ($hasMalangB && ($specialMode === null || $specialMode === 'malang_b')) {
            try {
                $malangFakturSql = "(SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_faktur_np_malang') . "
                                     UNION ALL
                                     SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_faktur_np_malang_b') . "
                                     UNION ALL
                                     SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_faktur_np_malang_c') . ")";
                $malangDetailSql = "(SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_fakturdetail_np_malang') . "
                                     UNION ALL
                                     SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_fakturdetail_np_malang_b') . "
                                     UNION ALL
                                     SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMalangSchema, 'transaksi_fakturdetail_np_malang_c') . ")";
                $queryMalang = $conn->prepare("SELECT
                    UPPER(O.ofcode_out) as ofcode_out,
                    P.nama_p as id_mp,
                    MONTH(tf.tgl_tfk) as month,
                    SUM(td.total_tfd) as total_sebelum_ppn
                FROM $malangFakturSql tf
                INNER JOIN $malangDetailSql td ON td.id_tfk = tf.id_tfk
                INNER JOIN " . qualifySchemaTable($specialMalangSchema, 'produk') . " P ON td.id_pro = P.id_pro
                INNER JOIN " . qualifySchemaTable($specialMalangSchema, 'outlet') . " O ON tf.id_out = O.id_out
                WHERE YEAR(tf.tgl_tfk) = :tahun
                GROUP BY UPPER(O.ofcode_out), P.nama_p, MONTH(tf.tgl_tfk)");
                $queryMalang->bindParam(':tahun', $tahun, PDO::PARAM_STR);
                $queryMalang->execute();
                while ($row = $queryMalang->fetch(PDO::FETCH_ASSOC)) {
                    $oc    = $row['ofcode_out'];
                    $idMp  = $row['id_mp'];
                    $m     = (int)$row['month'];
                    $value = (float)$row['total_sebelum_ppn'];
                    if (!isset($result['data']['sales'][$oc])) $result['data']['sales'][$oc] = [];
                    if (!isset($result['data']['sales'][$oc][$idMp])) $result['data']['sales'][$oc][$idMp] = array_fill(1, 12, 0);
                    $result['data']['sales'][$oc][$idMp][$m] = ($result['data']['sales'][$oc][$idMp][$m] ?? 0) + $value;
                    if (!isset($result['data']['principle'][$idMp])) $result['data']['principle'][$idMp] = array_fill(1, 12, 0);
                    $result['data']['principle'][$idMp][$m] = ($result['data']['principle'][$idMp][$m] ?? 0) + $value;
                }
            } catch (Exception $qe) {
                $debugMeta['warnings'][] = [
                    'source' => 'special_malang_b',
                    'message' => $qe->getMessage(),
                ];
                error_log("getSalesLazyData MALANG_B error: " . $qe->getMessage());
            }
        } elseif ($specialMode === 'malang_b') {
            $debugMeta['warnings'][] = [
                'source' => 'special_malang_b',
                'message' => 'Schema atau tabel Malang B tidak terdeteksi',
            ];
        }

        // ── SPECIAL TABLES: Medan B ───────────────────────────────────────────────
        // Hanya ada di cabang Puri B. Cek keberadaan tabel terlebih dahulu.
        $specialMedanSchema = null;
        $hasMedanB = false;
        if ($specialMode === null || $specialMode === 'medan_b') {
            $debugMeta['special_schema']['medan_b'] = [];
            $specialMedanSchema = detectSpecialSchema($conn, [
                'transaksi_faktur_np_medan',
                'transaksi_faktur_b_medan',
                'transaksi_faktur_c_medan',
                'transaksi_fakturdetail_np_medan',
                'transaksi_fakturdetail_medan_b',
                'transaksi_fakturdetail_c_medan',
            ], $debugMeta['special_schema']['medan_b']);
            $hasMedanB = ($specialMedanSchema !== null);
            $debugMeta['special_schema']['medan_b']['available'] = $hasMedanB;
        }

        if ($hasMedanB && ($specialMode === null || $specialMode === 'medan_b')) {
            try {
                $medanFakturSql = "(SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_faktur_np_medan') . "
                                    UNION ALL
                                    SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_faktur_b_medan') . "
                                    UNION ALL
                                    SELECT id_tfk, id_out, tgl_tfk FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_faktur_c_medan') . ")";
                $medanDetailSql = "(SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_fakturdetail_np_medan') . "
                                    UNION ALL
                                    SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_fakturdetail_medan_b') . "
                                    UNION ALL
                                    SELECT id_tfk, id_pro, total_tfd FROM " . qualifySchemaTable($specialMedanSchema, 'transaksi_fakturdetail_c_medan') . ")";
                $queryMedan = $conn->prepare("SELECT
                    UPPER(O.ofcode_out) as ofcode_out,
                    P.nama_p as id_mp,
                    MONTH(tf.tgl_tfk) as month,
                    SUM(td.total_tfd) as total_sebelum_ppn
                FROM $medanFakturSql tf
                INNER JOIN $medanDetailSql td ON td.id_tfk = tf.id_tfk
                INNER JOIN " . qualifySchemaTable($specialMedanSchema, 'produk') . " P ON td.id_pro = P.id_pro
                INNER JOIN " . qualifySchemaTable($specialMedanSchema, 'outlet') . " O ON tf.id_out = O.id_out
                WHERE YEAR(tf.tgl_tfk) = :tahun
                GROUP BY UPPER(O.ofcode_out), P.nama_p, MONTH(tf.tgl_tfk)");
                $queryMedan->bindParam(':tahun', $tahun, PDO::PARAM_STR);
                $queryMedan->execute();
                while ($row = $queryMedan->fetch(PDO::FETCH_ASSOC)) {
                    $oc    = $row['ofcode_out'];
                    $idMp  = $row['id_mp'];
                    $m     = (int)$row['month'];
                    $value = (float)$row['total_sebelum_ppn'];
                    if (!isset($result['data']['sales'][$oc])) $result['data']['sales'][$oc] = [];
                    if (!isset($result['data']['sales'][$oc][$idMp])) $result['data']['sales'][$oc][$idMp] = array_fill(1, 12, 0);
                    $result['data']['sales'][$oc][$idMp][$m] = ($result['data']['sales'][$oc][$idMp][$m] ?? 0) + $value;
                    if (!isset($result['data']['principle'][$idMp])) $result['data']['principle'][$idMp] = array_fill(1, 12, 0);
                    $result['data']['principle'][$idMp][$m] = ($result['data']['principle'][$idMp][$m] ?? 0) + $value;
                }
            } catch (Exception $qe) {
                $debugMeta['warnings'][] = [
                    'source' => 'special_medan_b',
                    'message' => $qe->getMessage(),
                ];
                error_log("getSalesLazyData MEDAN_B error: " . $qe->getMessage());
            }
        } elseif ($specialMode === 'medan_b') {
            $debugMeta['warnings'][] = [
                'source' => 'special_medan_b',
                'message' => 'Schema atau tabel Medan B tidak terdeteksi',
            ];
        }

    } else {
        $debugMeta['route'] = 'remote';
        // ========================================
        // CABANG REMOTE: Panggil getRanalisData.php via cURL
        // ========================================
        $tgl     = date('Y-m-d');
        $keyApl  = $aplikasi['key_apl'];
        $baseUrl = rtrim($aplikasi['base_url_apl'], '/');
        $encrypt = md5($tgl . "#" . $keyApl);

        $apiUrl = $baseUrl . "/api/getRanalisData.php"
                . "?encrypt=" . $encrypt
                . "&id_apl="  . urlencode($idApl)
                . "&tahun="   . urlencode($tahun)
                . "&type=both&mode=principle";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("CURL Error untuk $namaApl: " . $curlError);
        }

        $apiData = json_decode($response, true);

        $debugMeta['remote_request'] = [
            'base_url' => $baseUrl,
            'response_bytes' => strlen((string)$response),
        ];

        if (!$apiData || !isset($apiData['result'])) {
            throw new Exception("Response tidak valid dari $namaApl: " . substr($response, 0, 200));
        }

        // Transform: result[cendo|pim][ofcode][principle][month][total_sebelum_ppn] -> sales[ofcode][principle][month]
        foreach (['cendo', 'pim'] as $src) {
            if (!isset($apiData['result'][$src])) continue;
            $processSourceData($apiData['result'][$src], $result);
        }
    }

    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "id_apl" => $idApl,
        "error" => $e->getMessage()
    ]);
}

$conn = $base->close();
exit;
?>
