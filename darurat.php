<?php
require_once __DIR__ . '/config/koneksi.php';

$pdo = getDB();
$contacts = $pdo->query('SELECT * FROM kontak_darurat ORDER BY id')->fetchAll();

$emergencyNums = ['117', '119', '110', '113', '115'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kontak Darurat — Siaga Gempa</title>
  <meta name="description" content="Daftar kontak darurat untuk situasi bencana gempa: BMKG, Basarnas, BNPB, ambulans, pemadam kebakaran, dan lainnya.">
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
      <li><a href="panduan.php" class="nav__link">Panduan Mitigasi</a></li>
      <li><a href="darurat.php" class="nav__link active">Kontak Darurat</a></li>
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
  <a href="panduan.php" class="nav__mobile-link">Panduan Mitigasi</a>
  <a href="darurat.php" class="nav__mobile-link active">Kontak Darurat</a>
</nav>

<main class="page-wrap" style="overflow-y:auto">

  <!-- ═══ PAGE HEADER ══════════════════════════════════════════ -->
  <div style="background:radial-gradient(ellipse at 50% 0%,#122B4D 0%,var(--bg-deep) 60%);padding:clamp(40px,6vh,72px) clamp(16px,4vw,64px) clamp(24px,4vh,48px)">
    <div style="max-width:1440px;margin:0 auto">
      <div class="section-label">NOMOR PENTING</div>
      <h1 class="section-title" style="font-size:clamp(1.8rem,4vw,2.8rem)">Kontak Darurat Bencana</h1>
      <p class="section-sub">
        Simpan nomor-nomor penting ini sebelum bencana terjadi.
        Hubungi layanan yang sesuai dengan situasi yang Anda hadapi.
      </p>

      <!-- Quick call buttons -->
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:4px">
        <a href="tel:117" class="btn-primary">📞 117 — BNPB</a>
        <a href="tel:119" class="btn-primary" style="background:var(--risk-extreme)">🚑 119 — Ambulans</a>
        <a href="tel:115" class="btn-secondary">🚁 115 — Basarnas</a>
        <a href="tel:110" class="btn-secondary">👮 110 — Polri</a>
        <a href="tel:113" class="btn-secondary">🚒 113 — Damkar</a>
      </div>
    </div>
  </div>

  <!-- ═══ EMERGENCY ALERT BAR ═══════════════════════════════════ -->
  <div style="background:#271C1D;border-top:1px solid #55342A;border-bottom:1px solid #55342A;padding:14px clamp(16px,4vw,64px)">
    <div style="max-width:1440px;margin:0 auto;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <span style="font-size:1.1rem">⚠️</span>
      <span style="font-size:0.85rem;color:#CFBDB4">
        <strong style="color:var(--text-primary)">Situasi darurat?</strong>
        Hubungi <strong style="color:var(--risk-extreme)">119</strong> (Ambulans) atau
        <strong style="color:var(--risk-extreme)">117</strong> (BNPB) segera.
        Data resmi: <a href="https://www.bmkg.go.id" target="_blank" rel="noopener" style="color:var(--accent)">bmkg.go.id</a> &
        <a href="https://www.bnpb.go.id" target="_blank" rel="noopener" style="color:var(--accent)">bnpb.go.id</a>
      </span>
    </div>
  </div>

  <!-- ═══ CONTACT CARDS ════════════════════════════════════════ -->
  <div class="section">

    <div class="kontak-grid">
      <?php foreach ($contacts as $k): ?>
        <?php
          $isEmergency = array_reduce($emergencyNums, fn($carry, $n) => $carry || str_contains($k['nomor_telepon'], $n), false);
          $telNum = preg_replace('/[^0-9+]/', '', $k['nomor_telepon']);
          $telHref = $telNum ? "tel:$telNum" : '#';
        ?>
        <div class="kontak-card<?= $isEmergency ? ' emergency' : '' ?>">
          <div class="kontak-card__header">
            <div class="kontak-card__icon"><?= $k['icon'] ?></div>
            <div>
              <div class="kontak-card__name"><?= htmlspecialchars($k['nama_instansi']) ?></div>
              <div class="kontak-card__cat"><?= htmlspecialchars($k['kategori']) ?></div>
            </div>
          </div>
          <hr class="kontak-card__divider">
          <div class="kontak-card__number">
            <?php if ($telHref !== '#'): ?>
              <a href="<?= $telHref ?>"><?= htmlspecialchars($k['nomor_telepon']) ?></a>
            <?php else: ?>
              <?= htmlspecialchars($k['nomor_telepon']) ?>
            <?php endif; ?>
          </div>
          <div class="kontak-card__desc"><?= htmlspecialchars($k['keterangan']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($contacts)): ?>
      <div class="error-state">Data kontak belum tersedia. Pastikan database sudah diimport.</div>
    <?php endif; ?>

  </div>

  <!-- ═══ INFO TAMBAHAN ════════════════════════════════════════ -->
  <div style="max-width:1440px;margin:0 auto;padding:0 clamp(16px,4vw,64px) clamp(32px,5vh,56px)">
    <div class="emergency-banner">
      <div>
        <h2 class="emergency-banner__title">Sudah siap menghadapi gempa?</h2>
        <p class="emergency-banner__sub">Pelajari panduan lengkap dan kenali jalur evakuasi di wilayah Anda.</p>
      </div>
      <a href="panduan.php">Baca Panduan Mitigasi ↗</a>
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
