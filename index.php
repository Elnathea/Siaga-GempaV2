<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/sync_bmkg.php';

$pdo    = getDB();
syncDataGempaBMKG($pdo);
$gempa3 = $pdo->query('SELECT * FROM data_gempa ORDER BY waktu DESC LIMIT 3')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Siaga Gempa — Sistem Informasi Kebencanaan</title>
  <meta name="description" content="Kenali risikonya. Siapkan langkahnya. Lindungi sesama. Jelajahi zona rawan gempa dan panduan mitigasi bencana Indonesia.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Leaflet (for mini-map) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Mini-map container in explore card */
    #mini-map {
      position: absolute; inset: 0;
      width: 100%; height: 100%;
      cursor: pointer;
      z-index: 1;
    }
    .mini-map-overlay {
      position: absolute; inset: 0;
      z-index: 2;
      display: flex; align-items: center; justify-content: center;
      pointer-events: none;
    }
    .mini-map-hint {
      background: rgba(8,15,29,0.65);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 8px 14px;
      font-size: 0.75rem; font-weight: 600;
      color: var(--accent);
      backdrop-filter: blur(4px);
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .explore-card__map-preview:hover .mini-map-hint { opacity: 1; }
  </style>
</head>
<body>

<!-- ═══ NAVIGATION ═══════════════════════════════════════════ -->
<nav class="nav">
  <div class="nav__inner">
    <div class="nav__brand">
      <div class="nav__logo" title="Siaga Gempa">
        <!-- Globe SVG icon -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="9.5" stroke="#080F1D" stroke-width="1.6"/>
          <ellipse cx="12" cy="12" rx="4" ry="9.5" stroke="#080F1D" stroke-width="1.6"/>
          <line x1="2.5" y1="9" x2="21.5" y2="9" stroke="#080F1D" stroke-width="1.6" stroke-linecap="round"/>
          <line x1="2.5" y1="15" x2="21.5" y2="15" stroke="#080F1D" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <div class="nav__title">Siaga Gempa</div>
        <div class="nav__sub">Bersiap. Tanggap. Selamat.</div>
      </div>
    </div>

    <ul class="nav__links">
      <li><a href="index.php"   class="nav__link active">Beranda</a></li>
      <li><a href="peta.php"    class="nav__link">Peta Rawan Gempa</a></li>
      <li><a href="panduan.php" class="nav__link">Panduan Mitigasi</a></li>
      <li><a href="darurat.php" class="nav__link">Kontak Darurat</a></li>
    </ul>

    <div class="nav__right">
      <span class="nav__live">● LIVE</span>
      <a href="darurat.php" class="nav__btn-outline">117 Darurat</a>
      <a href="peta.php"    class="nav__btn-primary">Buka Peta</a>
    </div>

    <button class="nav__hamburger" id="nav-hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<nav class="nav__mobile-menu" id="nav-mobile-menu">
  <a href="index.php"   class="nav__mobile-link active">Beranda</a>
  <a href="peta.php"    class="nav__mobile-link">Peta Rawan Gempa</a>
  <a href="panduan.php" class="nav__mobile-link">Panduan Mitigasi</a>
  <a href="darurat.php" class="nav__mobile-link">Kontak Darurat</a>
  <a href="darurat.php" class="nav__mobile-link" style="color:var(--accent);font-weight:600;">📞 117 Darurat</a>
</nav>

<main class="page-wrap">

  <!-- ═══ HERO ═══════════════════════════════════════════════ -->
  <section class="hero">
    <div class="hero__grid"></div>

    <div class="hero__badge">● SISTEM INFORMASI &amp; MITIGASI BENCANA GEMPA BUMI</div>

    <h1>
      <div class="hero__title-top">Indonesia</div>
      <div class="hero__title-main">Siaga Gempa</div>
    </h1>

    <p class="hero__desc">
      Kenali risikonya. Siapkan langkahnya. Lindungi sesama.<br>
      Jelajahi zona rawan gempa dan panduan mitigasi bencana,<br>
      dalam satu ruang informasi untuk Indonesia yang lebih tangguh.
    </p>

    <div class="hero__actions">
      <a href="peta.php"    class="btn-primary">Buka Peta Interaktif ↗</a>
      <a href="panduan.php" class="btn-secondary">Panduan Mitigasi</a>
    </div>

    <!-- Seismograph widget -->
    <div class="hero__seismo">
      <div class="hero__seismo-header">
        <span class="hero__seismo-label">AKTIVITAS SEISMIK</span>
        <span class="hero__seismo-live">● Pemantauan aktif</span>
      </div>
      <svg id="seismo-svg" class="hero__seismo-wave" viewBox="0 0 700 70" preserveAspectRatio="none">
        <line x1="0" y1="23" x2="700" y2="23" stroke="#1B3047" stroke-width="1"/>
        <line x1="0" y1="35" x2="700" y2="35" stroke="#1B3047" stroke-width="1"/>
        <line x1="0" y1="47" x2="700" y2="47" stroke="#1B3047" stroke-width="1"/>
        <path class="wave-path" d=""/>
      </svg>
      <div class="seismo-times">
        <span>08.00</span><span>12.00</span><span>16.00 WIB</span>
      </div>
    </div>

    <small style="position:absolute;bottom:16px;left:clamp(16px,4vw,40px);font-size:0.68rem;color:var(--text-muted)">
      01 / KESIAPSIAGAAN DIMULAI DARI INFORMASI
    </small>
    <small style="position:absolute;bottom:16px;right:clamp(16px,4vw,40px);font-size:0.68rem;color:var(--text-muted)">
      Gulir untuk menjelajah ↓
    </small>
  </section>

  <!-- ═══ STATS BAR ═══════════════════════════════════════════ -->
  <div class="stats-bar">
    <div class="stats-bar__inner">
      <div class="stat-card">
        <div class="stat-card__num" id="stat-zona">20</div>
        <div>
          <div class="stat-card__label">Total zona rawan</div>
          <div class="stat-card__sub">Tersebar di seluruh Indonesia</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num" id="stat-sesar">13</div>
        <div>
          <div class="stat-card__label">Jalur sesar aktif</div>
          <div class="stat-card__sub">Kenali sumber risiko terdekat</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num accent" id="stat-hari">—</div>
        <div>
          <div class="stat-card__label">Gempa hari ini</div>
          <div class="stat-card__sub">Pemantauan harian</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__num" id="stat-dampak">—</div>
        <div>
          <div class="stat-card__label">Wilayah terdampak (30 hari)</div>
          <div class="stat-card__sub">Jelajahi sebaran dan risikonya</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ GEMPA TERKINI ════════════════════════════════════════ -->
  <div class="section">
    <div class="section-label">GEMPA TERKINI</div>
    <h2 class="section-title">Aktivitas Seismik Terbaru</h2>
    <p class="section-sub">Pantau perkembangan gempa di berbagai wilayah Indonesia secara berkala.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
      <?php foreach ($gempa3 as $g): ?>
        <?php
          $mag = (float)$g['magnitudo'];
          $col = $mag >= 7 ? '#EF4444' : ($mag >= 6 ? '#F97316' : ($mag >= 5 ? '#FACC15' : '#3B82F6'));
          $tsClass = match($g['status_tsunami']) {
            'Ya'      => 'tsunami-ya',
            'Potensi' => 'tsunami-potensi',
            default   => 'tsunami-tidak',
          };
          $waktu = date('d M Y, H:i', strtotime($g['waktu']));
        ?>
        <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:10px;cursor:pointer;" onclick="location.href='peta.php'">
          <div style="display:flex;align-items:center;gap:12px;width:100%">
            <div style="font-size:1.6rem;font-weight:800;color:<?= $col ?>">M <?= $g['magnitudo'] ?></div>
            <div>
              <div style="font-size:0.85rem;font-weight:600"><?= htmlspecialchars($g['wilayah']) ?></div>
              <div style="font-size:0.72rem;color:var(--text-muted)"><?= $waktu ?></div>
            </div>
          </div>
          <div style="display:flex;gap:12px;font-size:0.75rem;color:var(--text-muted);width:100%">
            <span>Kedalaman: <strong style="color:var(--text-primary)"><?= $g['kedalaman'] ?> km</strong></span>
            <span>Tsunami: <strong class="<?= $tsClass ?>"><?= $g['status_tsunami'] ?></strong></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:16px;text-align:right">
      <a href="peta.php" class="btn-secondary" style="display:inline-flex">Lihat semua di peta ↗</a>
    </div>
  </div>

  <!-- ═══ JELAJAHI WILAYAH (mini-map Leaflet) ════════════════ -->
  <div class="section" style="padding-top:0">
    <div class="explore-card">
      <div class="explore-card__info">
        <div class="section-label">KENALI WILAYAH ANDA</div>
        <h2 class="explore-card__title">Lebih kenal risikonya,<br>lebih siap menghadapinya.</h2>
        <p class="explore-card__desc">
          Lihat persebaran zona rawan, jalur sesar aktif, dan tingkat risiko
          di berbagai wilayah Indonesia secara interaktif.
        </p>
        <a href="peta.php" class="btn-primary" style="align-self:flex-start">Jelajahi Peta ↗</a>
      </div>
      <!-- Mini-map replaces the old oval SVG -->
      <div class="explore-card__map-preview" style="overflow:hidden;">
        <div id="mini-map"></div>
        <div class="mini-map-overlay">
          <span class="mini-map-hint">Klik untuk membuka peta penuh ↗</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ LANGKAH KESIAPSIAGAAN ════════════════════════════════ -->
  <div class="section" style="padding-top:0">
    <div class="section-label">SIAP DALAM SETIAP FASE</div>
    <h2 class="section-title">Kesiapsiagaan adalah perlindungan pertama.</h2>
    <p class="section-sub">Langkah sederhana yang dapat Anda pelajari dan praktikkan bersama keluarga.</p>

    <div class="guide-grid">
      <div class="guide-card" onclick="location.href='panduan.php?kategori=Sebelum+Gempa'">
        <div class="guide-card__num">01</div>
        <h3 class="guide-card__title">Sebelum Gempa</h3>
        <p class="guide-card__desc">Siapkan tas siaga, kenali titik kumpul, dan amankan benda berat di rumah.</p>
        <span class="guide-card__link">Pelajari langkahnya ↗</span>
      </div>
      <div class="guide-card" onclick="location.href='panduan.php?kategori=Saat+Gempa'">
        <div class="guide-card__num">02</div>
        <h3 class="guide-card__title">Saat Gempa</h3>
        <p class="guide-card__desc">Merunduk, berlindung, dan berpegangan. Lindungi kepala serta leher Anda.</p>
        <span class="guide-card__link">Pelajari langkahnya ↗</span>
      </div>
      <div class="guide-card" onclick="location.href='panduan.php?kategori=Setelah+Gempa'">
        <div class="guide-card__num">03</div>
        <h3 class="guide-card__title">Setelah Gempa</h3>
        <p class="guide-card__desc">Keluar dengan tertib, periksa kondisi, dan ikuti arahan sumber informasi resmi.</p>
        <span class="guide-card__link">Pelajari langkahnya ↗</span>
      </div>
    </div>
  </div>

  <!-- ═══ EMERGENCY BANNER ════════════════════════════════════ -->
  <div class="emergency-banner">
    <div>
      <h2 class="emergency-banner__title">Simpan nomor penting sebelum dibutuhkan.</h2>
      <p class="emergency-banner__sub">Akses cepat bantuan bencana, tim pencarian, dan layanan medis darurat.</p>
    </div>
    <a href="darurat.php">Lihat Kontak Darurat ↗</a>
  </div>

</main>

<!-- ═══ FOOTER ════════════════════════════════════════════════ -->
<footer class="footer">
  <div class="footer__inner">
    <div>
      <div class="footer__brand-name">Siaga Gempa</div>
      <div class="footer__brand-sub">Bersiap. Tanggap. Selamat.</div>
    </div>
    <div class="footer__meta">
      <p>Sistem informasi edukasi kebencanaan gempa bumi Indonesia.</p>
      <p>Rujuk informasi resmi BMKG dan BNPB untuk keputusan keselamatan.</p>
    </div>
  </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="assets/js/script.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    initMiniMap();
  });
</script>
</body>
</html>
