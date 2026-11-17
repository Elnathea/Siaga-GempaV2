<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Peta Rawan Gempa — Siaga Gempa</title>
  <meta name="description" content="Peta interaktif zona rawan gempa dan sesar aktif di Indonesia.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Leaflet CSS — loaded first, before custom CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* ── Full-viewport map layout fix ── */
    html, body { height: 100%; overflow: hidden; }
    .page-wrap  { height: calc(100vh - 72px); overflow: hidden; display: flex; flex-direction: column; }
    .map-layout { flex: 1; min-height: 0; display: grid; grid-template-columns: 300px 1fr; overflow: hidden; }
    .map-wrapper { position: relative; flex: 1; min-height: 0; overflow: hidden; }
    #map { position: absolute; inset: 0; width: 100%; height: 100%; background: #060D1A; }

    /* ── Mobile: sidebar collapses to top strip ── */
    @media (max-width: 900px) {
      .map-layout { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
      .map-sidebar { max-height: 180px; overflow-y: auto; }
      .map-wrapper { min-height: 0; }
    }

    /* ── Pulse marker animation ── */
    @keyframes pulse-ring {
      0%   { transform: scale(0.6); opacity: 0.9; }
      100% { transform: scale(2.4); opacity: 0; }
    }
    .pulse-marker { position: relative; }
    .pulse-ring {
      position: absolute;
      border-radius: 50%;
      animation: pulse-ring 1.8s ease-out infinite;
      pointer-events: none;
    }
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
      <li><a href="index.php"   class="nav__link">Beranda</a></li>
      <li><a href="peta.php"    class="nav__link active">Peta Rawan Gempa</a></li>
      <li><a href="panduan.php" class="nav__link">Panduan Mitigasi</a></li>
      <li><a href="darurat.php" class="nav__link">Kontak Darurat</a></li>
    </ul>

    <div class="nav__right">
      <span class="nav__live">● LIVE</span>
      <a href="darurat.php" class="nav__btn-outline">117 Darurat</a>
      <a href="panduan.php" class="nav__btn-primary">Panduan</a>
    </div>

    <button class="nav__hamburger" id="nav-hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<nav class="nav__mobile-menu" id="nav-mobile-menu">
  <a href="index.php"   class="nav__mobile-link">Beranda</a>
  <a href="peta.php"    class="nav__mobile-link active">Peta Rawan Gempa</a>
  <a href="panduan.php" class="nav__mobile-link">Panduan Mitigasi</a>
  <a href="darurat.php" class="nav__mobile-link">Kontak Darurat</a>
  <a href="darurat.php" class="nav__mobile-link" style="color:var(--accent);font-weight:600;">📞 117 Darurat</a>
</nav>

<main class="page-wrap">
  <div class="map-layout">

    <!-- ═══ SIDEBAR ══════════════════════════════════════════ -->
    <aside class="map-sidebar">
      <div class="map-sidebar__header">
        <div class="map-sidebar__title">Peta Zona Rawan Gempa</div>
        <div class="map-sidebar__sub">Klik sesar atau titik gempa untuk detail</div>
      </div>

      <!-- Filter risk buttons -->
      <div class="filter-group">
        <button class="filter-btn active" data-risk="Semua">Semua</button>
        <button class="filter-btn" data-risk="Normal">Normal</button>
        <button class="filter-btn" data-risk="Rendah">Rendah</button>
        <button class="filter-btn" data-risk="Tinggi">Tinggi</button>
        <button class="filter-btn" data-risk="Ekstrem">Ekstrem</button>
      </div>

      <!-- Sesar + Gempa list -->
      <div class="map-sidebar__content">
        <div class="sidebar-section-label">Jalur Sesar Aktif</div>
        <div id="sesar-list">
          <div class="loading-state" style="padding:16px 4px;">
            <div style="display:flex;align-items:center;gap:8px">
              <span class="nav__live" style="font-size:0.7rem">●</span>
              Memuat jalur sesar…
            </div>
          </div>
        </div>

        <hr style="border-color:var(--border);margin:14px 0 10px">

        <div class="sidebar-section-label">Gempa Terkini (15 kejadian)</div>
        <div id="gempa-list">
          <div class="loading-state" style="padding:16px 4px;">Memuat data gempa…</div>
        </div>
      </div>
    </aside>

    <!-- ═══ MAP CONTAINER ═══════════════════════════════════ -->
    <div class="map-wrapper">
      <div id="map"></div>

      <!-- Layer toggle bar -->
      <div class="layer-toggle-bar">
        <span class="layer-toggle-label">LAYER</span>
        <button class="layer-toggle-btn active" data-layer="sesar-lines"  title="Jalur sesar aktif">Jalur Sesar</button>
        <button class="layer-toggle-btn active" data-layer="episentrum"   title="Titik episentrum gempa">Episentrum</button>
        <button class="layer-toggle-btn active" data-layer="wilayah"      title="Highlight wilayah terdampak">Wilayah</button>
      </div>

      <!-- Legend overlay -->
      <div class="map-legend">
        <h4>LEGENDA</h4>
        <div class="legend-group-title">ZONA SESAR</div>
        <div class="legend-item">
          <div class="legend-swatch" style="background:#EF4444;box-shadow:0 0 8px #EF4444"></div>
          <span>Risiko Ekstrem</span>
        </div>
        <div class="legend-item">
          <div class="legend-swatch" style="background:#F59E0B;box-shadow:0 0 4px #F59E0B"></div>
          <span>Risiko Tinggi</span>
        </div>
        <div class="legend-item">
          <div class="legend-swatch" style="background:#3B82F6"></div>
          <span>Risiko Rendah</span>
        </div>
        <div class="legend-item">
          <div class="legend-swatch" style="background:#10B981;opacity:0.8"></div>
          <span>Risiko Normal</span>
        </div>

        <hr class="legend-sep">

        <div class="legend-group-title">EPISENTRUM GEMPA</div>
        <div class="legend-item">
          <div class="legend-dot-swatch" style="background:#EF4444;box-shadow:0 0 6px #EF4444"></div>
          <span>M ≥ 7.0</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot-swatch" style="background:#F97316"></div>
          <span>M 6.0 – 6.9</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot-swatch" style="background:#FACC15"></div>
          <span>M 5.0 – 5.9</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot-swatch" style="background:#3B82F6"></div>
          <span>M 4.0 – 4.9</span>
        </div>
        <div class="legend-item">
          <div class="legend-dot-swatch" style="background:#10B981"></div>
          <span>M &lt; 4.0</span>
        </div>
      </div>

      <!-- Detail panel (shown on click) -->
      <div class="detail-panel" id="detail-panel">
        <button class="detail-panel__close" title="Tutup" aria-label="Tutup">✕</button>
        <div class="detail-panel__inner">
          <div class="detail-panel__subtitle"></div>
          <div class="detail-panel__title"></div>
          <span class="detail-panel__badge"></span>
          <div class="detail-panel__desc"></div>
          <div class="detail-panel__coords"></div>
        </div>
      </div>
    </div>

  </div><!-- /.map-layout -->
</main>

<!-- Leaflet JS — must be before script.js -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="assets/js/script.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    initMap();
    populateGempaListSidebar();
  });

  async function populateGempaListSidebar() {
    const container = document.getElementById('gempa-list');
    try {
      const res  = await fetch('api/gempa.php?action=gempa&limit=15');
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (!json.success) throw new Error('API error');
      let html = '';
      json.data.forEach(function(g) {
        const mag  = parseFloat(g.magnitudo);
        const col  = mag >= 7 ? '#EF4444' : mag >= 6 ? '#F97316' : mag >= 5 ? '#FACC15' : '#3B82F6';
        const waktu = g.waktu ? new Date(g.waktu.replace(' ', 'T')).toLocaleDateString('id-ID',
          {day:'2-digit', month:'short', year:'numeric'}) : '—';
        html += '<div class="sesar-card" data-gempa-id="' + g.id + '" style="cursor:pointer">'
          + '<div class="sesar-card__header">'
          + '<span style="font-size:1rem;font-weight:800;color:' + col + ';min-width:48px;flex-shrink:0">M ' + g.magnitudo + '</span>'
          + '<div style="min-width:0">'
          + '<div class="sesar-card__name" style="font-size:0.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + g.wilayah + '</div>'
          + '<div style="font-size:0.68rem;color:var(--text-muted)">' + waktu + ' &middot; ' + g.kedalaman + ' km</div>'
          + '</div></div></div>';
      });
      container.innerHTML = html || '<div class="loading-state" style="padding:12px 4px">Tidak ada data.</div>';

      // Wire click → highlight on map + show detail panel
      container.addEventListener('click', function(e) {
        const card = e.target.closest('[data-gempa-id]');
        if (!card) return;
        document.querySelectorAll('#gempa-list .sesar-card').forEach(function(c) { c.classList.remove('active'); });
        card.classList.add('active');
        const gid = card.dataset.gempaId;
        if (typeof highlightGempaArea === 'function') highlightGempaArea(gid);
      });
    } catch (err) {
      container.innerHTML = '<div class="error-state" style="padding:12px 4px">Gagal memuat data gempa.<br><small>' + err.message + '</small></div>';
    }
  }
</script>
</body>
</html>
