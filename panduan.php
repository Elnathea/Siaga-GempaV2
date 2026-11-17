<?php
require_once __DIR__ . '/config/koneksi.php';

$pdo = getDB();

// Get all categories
$categories = $pdo->query('SELECT DISTINCT kategori, icon, MIN(urutan) as u FROM panduan_siaga GROUP BY kategori, icon ORDER BY u')->fetchAll();

// Active category from URL
$activeKat = $_GET['kategori'] ?? ($categories[0]['kategori'] ?? '');

// Get steps for active category
$steps = [];
if ($activeKat) {
  $stmt = $pdo->prepare('SELECT * FROM panduan_siaga WHERE kategori = ? ORDER BY urutan');
  $stmt->execute([$activeKat]);
  $steps = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panduan Mitigasi — Siaga Gempa</title>
  <meta name="description" content="Panduan lengkap kesiapsiagaan gempa: sebelum, saat, dan setelah gempa.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ═══ NAVIGATION ═══════════════════════════════════════════ -->
<nav class="nav">
  <div class="nav__inner">
    <div class="nav__brand">
      <div class="nav__logo" title="Siaga Gempa">
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
      <li><a href="index.php"   class="nav__link">Beranda</a></li>
      <li><a href="peta.php"    class="nav__link">Peta Rawan Gempa</a></li>
      <li><a href="panduan.php" class="nav__link active">Panduan Mitigasi</a></li>
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
  <a href="index.php"   class="nav__mobile-link">Beranda</a>
  <a href="peta.php"    class="nav__mobile-link">Peta Rawan Gempa</a>
  <a href="panduan.php" class="nav__mobile-link active">Panduan Mitigasi</a>
  <a href="darurat.php" class="nav__mobile-link">Kontak Darurat</a>
  <a href="darurat.php" class="nav__mobile-link" style="color:var(--accent);font-weight:600;">📞 117 Darurat</a>
</nav>

<main class="page-wrap">
  <div class="panduan-layout">

    <!-- ═══ SIDEBAR ══════════════════════════════════════════ -->
    <aside class="panduan-sidebar">
      <h3>KATEGORI PANDUAN</h3>

      <?php foreach ($categories as $cat): ?>
        <?php $isActive = $cat['kategori'] === $activeKat; ?>
        <a href="panduan.php?kategori=<?= urlencode($cat['kategori']) ?>"
           class="panduan-nav-item<?= $isActive ? ' active' : '' ?>">
          <span><?= $cat['icon'] ?></span>
          <span><?= htmlspecialchars($cat['kategori']) ?></span>
        </a>
      <?php endforeach; ?>

      <hr style="border-color:var(--border);margin:16px 8px">
      <div style="padding:0 8px">
        <a href="darurat.php" class="btn-primary" style="width:100%;justify-content:center;font-size:0.82rem;padding:10px 16px;">
          📞 Kontak Darurat
        </a>
      </div>
    </aside>

    <!-- ═══ MAIN CONTENT ═════════════════════════════════════ -->
    <div class="panduan-main">

      <?php if ($activeKat && !empty($steps)): ?>
        <div class="panduan-category-header">
          <div class="section-label">PANDUAN KESIAPSIAGAAN</div>
          <h1 class="section-title"><?= htmlspecialchars($activeKat) ?></h1>
          <p class="section-sub">
            <?= count($steps) ?> langkah penting yang perlu Anda ketahui dan praktikkan.
          </p>
        </div>

        <div class="panduan-steps">
          <?php foreach ($steps as $i => $step): ?>
            <div class="panduan-step">
              <div class="panduan-step__header">
                <div class="panduan-step__num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="panduan-step__title"><?= htmlspecialchars($step['judul']) ?></div>
              </div>
              <div class="panduan-step__body"><?= nl2br(htmlspecialchars($step['isi'])) ?></div>
              <?php if ($activeKat === 'Saat Gempa' && stripos($step['judul'], 'Drop') !== false): ?>
                <div class="panduan-step__video">
                  <iframe
                    src="https://www.youtube.com/embed/aV89_yUJunM"
                    title="Drop, Cover, Hold On — Cara Berlindung Saat Gempa"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                  </iframe>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif (empty($categories)): ?>
        <div class="error-state">Panduan belum tersedia. Pastikan database sudah diimport.</div>
      <?php else: ?>
        <div class="loading-state">Pilih kategori panduan di panel kiri.</div>
      <?php endif; ?>

      <!-- Quick links ke semua kategori (mobile-friendly) -->
      <?php if (!empty($categories) && $activeKat): ?>
        <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--border)">
          <div class="section-label" style="margin-bottom:12px">KATEGORI LAINNYA</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach ($categories as $cat): ?>
              <?php if ($cat['kategori'] !== $activeKat): ?>
                <a href="panduan.php?kategori=<?= urlencode($cat['kategori']) ?>"
                   class="btn-secondary" style="font-size:0.8rem;padding:8px 16px;">
                  <?= $cat['icon'] ?> <?= htmlspecialchars($cat['kategori']) ?>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
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

<script src="assets/js/script.js"></script>
</body>
</html>
