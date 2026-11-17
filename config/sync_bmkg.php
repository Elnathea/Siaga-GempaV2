<?php
/**
 * syncDataGempaBMKG — fetch latest earthquakes from BMKG open data,
 * deduplicate by (waktu + wilayah), insert new rows into data_gempa.
 *
 * Uses a temp-file lock so it only hits BMKG at most once every 5 minutes
 * unless $force = true.
 *
 * @param PDO  $pdo
 * @param bool $force  Skip cooldown and always fetch
 * @return int Number of rows inserted
 */
function syncDataGempaBMKG(PDO $pdo, bool $force = false): int
{
    $lockFile = sys_get_temp_dir() . '/siaga_bmkg_sync.lock';
    $cooldown = 300; // 5 minutes

    if (!$force && file_exists($lockFile) && (time() - filemtime($lockFile)) < $cooldown) {
        return 0; // too soon
    }

    // BMKG open-data endpoints (JSON)
    $endpoints = [
        'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json',
        'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json',
        'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json',
    ];

    $rows    = [];
    $context = stream_context_create(['http' => ['timeout' => 8]]);

    foreach ($endpoints as $url) {
        $raw = @file_get_contents($url, false, $context);
        if (!$raw) continue;
        $json = json_decode($raw, true);
        if (!$json) continue;

        // Both endpoints wrap data differently
        $items = $json['Infogempa']['gempa'] ?? [];
        if (isset($items['Tanggal'])) $items = [$items]; // single-item endpoint

        foreach ($items as $item) {
            if (empty($item['Tanggal']) || empty($item['Jam'])) continue;
            $waktu   = date('Y-m-d H:i:s', strtotime($item['Tanggal'] . ' ' . $item['Jam']));
            $wilayah = trim($item['Wilayah'] ?? '');
            if (!$waktu || !$wilayah) continue;

            // Parse coordinates — BMKG gives e.g. "2.33 LS" / "116.26 BT"
            $lat = $lng = null;
            if (!empty($item['Coordinates'])) {
                [$lat, $lng] = array_map('floatval', explode(',', $item['Coordinates']));
            } elseif (!empty($item['Lintang']) && !empty($item['Bujur'])) {
                $lat = (float) preg_replace('/[^0-9.\-]/', '', $item['Lintang']);
                $lng = (float) preg_replace('/[^0-9.\-]/', '', $item['Bujur']);
                if (strpos($item['Lintang'], 'LS') !== false) $lat = -abs($lat);
                if (strpos($item['Bujur'],   'BB') !== false) $lng = -abs($lng);
            }

            $rows[] = [
                'waktu'         => $waktu,
                'wilayah'       => $wilayah,
                'magnitudo'     => (float)($item['Magnitude'] ?? $item['Mag'] ?? 0),
                'kedalaman'     => (int) preg_replace('/[^0-9]/', '', $item['Kedalaman'] ?? '10'),
                'latitude'      => $lat,
                'longitude'     => $lng,
                'status_tsunami'=> $item['Potensi'] ?? 'Tidak',
            ];
        }
    }

    touch($lockFile); // update timestamp regardless of insert count

    if (empty($rows)) return 0;

    $inserted = 0;
    $check = $pdo->prepare('SELECT 1 FROM data_gempa WHERE waktu = ? AND wilayah = ? LIMIT 1');
    $ins   = $pdo->prepare(
        'INSERT INTO data_gempa (waktu, wilayah, magnitudo, kedalaman, latitude, longitude, status_tsunami)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($rows as $r) {
        $check->execute([$r['waktu'], $r['wilayah']]);
        if ($check->fetchColumn()) continue; // duplicate
        $ins->execute([$r['waktu'], $r['wilayah'], $r['magnitudo'], $r['kedalaman'],
                       $r['latitude'], $r['longitude'], $r['status_tsunami']]);
        $inserted++;
    }

    return $inserted;
}
