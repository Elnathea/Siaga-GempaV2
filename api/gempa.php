<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/koneksi.php';

$action = $_GET['action'] ?? 'gempa';
$pdo    = getDB();

switch ($action) {
    case 'gempa':
        $limit  = min((int)($_GET['limit'] ?? 15), 60);
        $stmt   = $pdo->prepare('SELECT * FROM data_gempa ORDER BY waktu DESC LIMIT ?');
        $stmt->execute([$limit]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sesar':
        $stmt = $pdo->query('SELECT * FROM zona_sesar ORDER BY FIELD(tingkat_risiko,"Ekstrem","Tinggi","Rendah","Normal")');
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'panduan':
        $kategori = $_GET['kategori'] ?? '';
        if ($kategori) {
            $stmt = $pdo->prepare('SELECT * FROM panduan_siaga WHERE kategori = ? ORDER BY urutan');
            $stmt->execute([$kategori]);
        } else {
            $stmt = $pdo->query('SELECT DISTINCT kategori, icon FROM panduan_siaga ORDER BY urutan');
        }
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'kontak':
        $stmt = $pdo->query('SELECT * FROM kontak_darurat ORDER BY id');
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'statistik':
        $stats = [];
        $stats['total_zona']   = $pdo->query('SELECT COUNT(*) FROM zona_sesar')->fetchColumn();
        $stats['sesar_aktif']  = $pdo->query('SELECT COUNT(*) FROM zona_sesar WHERE tingkat_risiko IN ("Tinggi","Ekstrem")')->fetchColumn();
        $stats['gempa_terkini']= $pdo->query('SELECT COUNT(*) FROM data_gempa WHERE DATE(waktu) = CURDATE()')->fetchColumn();
        $stats['zona_terdampak']= $pdo->query('SELECT COUNT(DISTINCT wilayah) FROM data_gempa WHERE waktu >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
        echo json_encode(['success' => true, 'data' => $stats]);
        break;

    case 'sync':
        if (file_exists(__DIR__ . '/../config/sync_bmkg.php')) {
            require_once __DIR__ . '/../config/sync_bmkg.php';
            $n = syncDataGempaBMKG($pdo, true);
            echo json_encode(['success' => true, 'inserted' => $n]);
        } else {
            echo json_encode(['success' => false, 'error' => 'sync_bmkg.php not found']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action tidak dikenal']);
}
