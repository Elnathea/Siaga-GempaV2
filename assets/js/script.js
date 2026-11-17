/* =============================================
   SIAGA GEMPA — script.js  v2
   Nav, seismograph, map (Leaflet), panduan, kontak
   ============================================= */

// ── Helpers ─────────────────────────────────────
const $  = (s, ctx = document) => ctx.querySelector(s);
const $$ = (s, ctx = document) => [...ctx.querySelectorAll(s)];

// ── Nav hamburger ────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  var burger     = document.getElementById('nav-hamburger');
  var mobileMenu = document.getElementById('nav-mobile-menu');
  if (burger && mobileMenu) {
    burger.addEventListener('click', function (e) {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!mobileMenu.contains(e.target)) mobileMenu.classList.remove('open');
    });
  }

  // ── Seismograph on beranda ─────────────────────
  var svgEl = document.getElementById('seismo-svg');
  if (svgEl) drawSeismograph(svgEl);

  // ── Stats fetch on beranda ─────────────────────
  if (document.getElementById('stat-zona')) fetchStats();
});

// ═══════════════════════════════════════════════
//  SEISMOGRAPH
// ═══════════════════════════════════════════════
function drawSeismograph(svg) {
  var W = svg.viewBox.baseVal.width || 700;
  var H = svg.viewBox.baseVal.height || 70;
  var pts = [];
  for (var x = 0; x <= W; x += 3) {
    var phase = x / W;
    var y = H / 2;
    if (phase > 0.18 && phase < 0.33) {
      y += Math.sin(phase * 130) * 20 * (Math.random() * 0.6 + 0.4);
    } else if (phase > 0.52 && phase < 0.74) {
      y += Math.sin(phase * 95) * 26 * (Math.random() - 0.5);
    } else {
      y += (Math.random() - 0.5) * 7;
    }
    pts.push(x.toFixed(1) + ',' + Math.min(H - 2, Math.max(2, y)).toFixed(1));
  }
  var path = svg.querySelector('.wave-path');
  if (!path) return;
  path.setAttribute('d', 'M ' + pts.join(' L '));
  var len = 0;
  try { len = path.getTotalLength(); } catch (e) { len = 2000; }
  path.style.strokeDasharray  = len;
  path.style.strokeDashoffset = len;
  path.style.animation = 'none';
  // Force reflow then restart
  void path.getBoundingClientRect();
  path.style.animation = 'drawWave 3.5s ease forwards';
}

// ═══════════════════════════════════════════════
//  STATS FETCH (Beranda)
// ═══════════════════════════════════════════════
async function fetchStats() {
  try {
    var res  = await fetch('api/gempa.php?action=statistik');
    if (!res.ok) return;
    var json = await res.json();
    if (!json.success) return;
    var d = json.data;
    setTextSafe('stat-zona',   d.total_zona    ?? '—');
    setTextSafe('stat-sesar',  d.sesar_aktif   ?? '—');
    setTextSafe('stat-hari',   d.gempa_terkini ?? '—');
    setTextSafe('stat-dampak', d.zona_terdampak ?? '—');
  } catch (e) { /* silent */ }
}
function setTextSafe(id, val) {
  var el = document.getElementById(id);
  if (el) el.textContent = val;
}

// ═══════════════════════════════════════════════
//  MINI-MAP (Beranda — #mini-map element)
// ═══════════════════════════════════════════════
function initMiniMap() {
  var el = document.getElementById('mini-map');
  if (!el || typeof L === 'undefined') return;

  var mini = L.map('mini-map', {
    center: [-2.5, 118.0],
    zoom: 4,
    zoomControl: false,
    scrollWheelZoom: false,
    dragging: false,
    doubleClickZoom: false,
    touchZoom: false,
    keyboard: false,
    attributionControl: false,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    subdomains: 'abc', maxZoom: 10,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    className: 'dark-map-tiles',
  }).addTo(mini);

  // Load sesar lines for mini-map
  fetch('api/gempa.php?action=sesar').then(function (r) { return r.json(); }).then(function (json) {
    if (!json.success) return;
    json.data.forEach(function (s) {
      var coords = safeParseCoords(s.koordinat_jalur);
      if (!coords) return;
      var color  = s.kode_warna_hex || RISK_COLOR[s.tingkat_risiko] || '#FF8A3D';
      var weight = s.tingkat_risiko === 'Ekstrem' ? 3 : 2;
      L.polyline(coords, { color: color, weight: weight, opacity: 0.9 }).addTo(mini);
    });
  }).catch(function () {});

  // Load gempa markers for mini-map
  fetch('api/gempa.php?action=gempa&limit=20').then(function (r) { return r.json(); }).then(function (json) {
    if (!json.success) return;
    json.data.forEach(function (g) {
      var lat = parseFloat(g.latitude);
      var lng = parseFloat(g.longitude);
      if (isNaN(lat) || isNaN(lng)) return;
      var mag = parseFloat(g.magnitudo);
      var c   = magColor(mag);
      L.circleMarker([lat, lng], {
        radius: Math.max(4, mag * 2.5),
        color: c, fillColor: c, fillOpacity: 0.7, weight: 1.5,
      }).addTo(mini);
    });
  }).catch(function () {});

  // Click on mini-map → go to peta.php
  el.style.cursor = 'pointer';
  el.addEventListener('click', function () { location.href = 'peta.php'; });
}

// ═══════════════════════════════════════════════
//  MAP PAGE (peta.php) — MAIN MAP
// ═══════════════════════════════════════════════
var mapInstance  = null;
var sesarLayers  = {};   // { id: { line, hit, risk, data } }
var gempaMarkers = [];   // { marker, data }
var activeFilter = 'Semua';

// Layer groups
var lgSesarLines      = null;
var lgGempaMarkers    = null;
var lgRegionHighlight = null;

// ── Risk/mag colour helpers ──────────────────────
var RISK_COLOR = {
  Normal:  '#10B981',
  Rendah:  '#3B82F6',
  Tinggi:  '#F59E0B',
  Ekstrem: '#EF4444',
};
var RISK_WEIGHT = { Normal: 1.5, Rendah: 1.5, Tinggi: 1.5, Ekstrem: 1.5 };

function magColor(mag) {
  if (mag >= 7) return '#EF4444';
  if (mag >= 6) return '#F97316';
  if (mag >= 5) return '#FACC15';
  if (mag >= 4) return '#3B82F6';
  return '#10B981';
}
function magRadius(mag) { return mag >= 7 ? 9 : mag >= 6 ? 8 : mag >= 5 ? 7 : 6; }

// Safe JSON coordinate parser
function safeParseCoords(raw) {
  if (!raw) return null;
  try {
    var arr = JSON.parse(raw);
    if (!Array.isArray(arr) || arr.length < 2) return null;
    for (var i = 0; i < arr.length; i++) {
      if (!Array.isArray(arr[i]) || arr[i].length < 2) return null;
      if (isNaN(parseFloat(arr[i][0])) || isNaN(parseFloat(arr[i][1]))) return null;
    }
    return arr;
  } catch (e) {
    return null;
  }
}

// ── Init main map ────────────────────────────────
function initMap() {
  var mapEl = document.getElementById('map');
  if (!mapEl) { console.error('[SiagaGempa] #map element not found'); return; }
  if (typeof L === 'undefined') { console.error('[SiagaGempa] Leaflet not loaded'); return; }

  mapInstance = L.map('map', {
    center:             [-2.5, 118.0],
    zoom:               5,
    minZoom:            2,
    maxZoom:            16,
    zoomControl:        true,
    scrollWheelZoom:    true,
    preferCanvas:       true,
    maxBounds:          [[-85, -180], [85, 180]],
    maxBoundsViscosity: 1.0,
  });

  // Esri World Dark Gray Base — free, no API key, natively dark
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri',
    maxZoom:     16,
    noWrap:      true,
    bounds:      [[-85, -180], [85, 180]],
  }).addTo(mapInstance);

  // Layer groups: region highlight → sesar → gempa (top)
  lgRegionHighlight = L.layerGroup().addTo(mapInstance);
  lgSesarLines      = L.layerGroup().addTo(mapInstance);
  lgGempaMarkers    = L.layerGroup().addTo(mapInstance);

  setTimeout(function () { mapInstance.invalidateSize(); }, 200);

  loadSesarLayers();
  loadGempaMarkers();
  loadRegionHighlights();
  setupMapFilters();
  setupLayerToggles();
}

// ── Load sesar lines ─────────────────────────────
async function loadSesarLayers() {
  var listEl = document.getElementById('sesar-list');
  try {
    var res = await fetch('api/gempa.php?action=sesar');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    var json = await res.json();
    if (!json.success) throw new Error('API: ' + JSON.stringify(json));

    var data = json.data;
    if (!data || data.length === 0) throw new Error('Tidak ada data sesar');

    data.forEach(function (sesar) {
      var coords = safeParseCoords(sesar.koordinat_jalur);
      if (!coords) {
        console.warn('[SiagaGempa] Koordinat tidak valid untuk sesar:', sesar.nama_sesar, sesar.koordinat_jalur);
        return;
      }

      var color = sesar.kode_warna_hex || RISK_COLOR[sesar.tingkat_risiko] || '#FF8A3D';
      var popup = buildSesarPopup(sesar);

      // Visual polyline — thick, coloured
      var line = L.polyline(coords, {
        color: color, weight: 3.5, opacity: 0.9, lineCap: 'round', lineJoin: 'round',
      });
      line.bindPopup(popup, { maxWidth: 320, className: 'dark-popup' });
      line.on('click', function (e) {
        showDetailPanel(sesar, 'sesar');
        line.openPopup(e.latlng);
      });
      line.addTo(lgSesarLines);

      // Invisible hit-target — wide transparent line for easy clicking
      var hit = L.polyline(coords, {
        color: color, weight: 16, opacity: 0, lineCap: 'round', lineJoin: 'round',
      });
      hit.bindPopup(popup, { maxWidth: 320, className: 'dark-popup' });
      hit.on('click', function (e) {
        showDetailPanel(sesar, 'sesar');
        line.openPopup(e.latlng);
      });
      hit.addTo(lgSesarLines);
      hit.bringToFront();

      sesarLayers[sesar.id] = { line: line, hit: hit, risk: sesar.tingkat_risiko, data: sesar };
    });

    renderSesarList(data);

  } catch (err) {
    console.error('[SiagaGempa] loadSesarLayers error:', err);
    if (listEl) listEl.innerHTML = '<div class="error-state" style="padding:12px 4px">Gagal memuat jalur sesar.<br>'
        + '<small style="color:var(--text-muted)">' + err.message + '</small></div>';
  }
}

function buildSesarPopup(s) {
  var rc = (s.tingkat_risiko || '').toLowerCase();
  var desc = s.deskripsi ? s.deskripsi.substring(0, 180) + (s.deskripsi.length > 180 ? '…' : '') : '';
  return '<div class="gempa-popup">'
    + '<h3>' + (s.nama_sesar || '—') + '</h3>'
    + '<table>'
    + '<tr><td>Risiko</td><td><span class="chip ' + rc + '">' + s.tingkat_risiko + '</span></td></tr>'
    + '</table>'
    + (desc ? '<p style="margin-top:8px;font-size:0.78rem;color:#9DAFC7">' + desc + '</p>' : '')
    + '</div>';
}

// ── Load gempa markers — circleMarker, legend colours ──
async function loadGempaMarkers() {
  try {
    var res = await fetch('api/gempa.php?action=gempa&limit=60');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    var json = await res.json();
    if (!json.success) throw new Error('API error');

    json.data.forEach(function (g) {
      var lat = parseFloat(g.latitude);
      var lng = parseFloat(g.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      var mag    = parseFloat(g.magnitudo);
      var color  = magColor(mag);
      var radius = magRadius(mag);

      var marker = L.circleMarker([lat, lng], {
        radius:      radius,
        color:       '#ffffff',
        weight:      1.2,
        fillColor:   color,
        fillOpacity: 1,
        opacity:     0.9,
        className:   'gempa-dot',
      });

      marker.bindPopup(buildGempaPopup(g), { maxWidth: 300, className: 'dark-popup' });
      marker.addTo(lgGempaMarkers);

      var entry = { marker: marker, data: g };
      gempaMarkers.push(entry);

      marker.on('click', function () {
        showDetailPanel(g, 'gempa');
        marker.openPopup();
      });
    });

  } catch (err) {
    console.error('[SiagaGempa] loadGempaMarkers error:', err);
  }
}

function buildGempaPopup(g) {
  var tsClass = 'tsunami-' + (g.status_tsunami || '').toLowerCase();
  var waktu = '—';
  try { waktu = new Date((g.waktu || '').replace(' ', 'T')).toLocaleString('id-ID',
    { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(e) {}
  return '<div class="gempa-popup">'
    + '<h3>Gempa M ' + g.magnitudo + '</h3><table>'
    + '<tr><td>Wilayah</td><td>' + (g.wilayah || '—') + '</td></tr>'
    + '<tr><td>Kedalaman</td><td>' + g.kedalaman + ' km</td></tr>'
    + '<tr><td>Waktu</td><td>' + waktu + '</td></tr>'
    + '<tr><td>Tsunami</td><td class="' + tsClass + '">' + (g.status_tsunami || '—') + '</td></tr>'
    + '<tr><td>Koordinat</td><td>' + parseFloat(g.latitude).toFixed(4) + ', ' + parseFloat(g.longitude).toFixed(4) + '</td></tr>'
    + '</table></div>';
}

// ── Region highlights — kabupaten/kota polygons near gempa ──
async function loadRegionHighlights() {
  if (!lgRegionHighlight) return;

  var epicentres = [];
  try {
    var gRes = await fetch('api/gempa.php?action=gempa&limit=60');
    if (gRes.ok) {
      var gJson = await gRes.json();
      if (gJson.success) {
        gJson.data.forEach(function (g) {
          var lat = parseFloat(g.latitude);
          var lng = parseFloat(g.longitude);
          if (!isNaN(lat) && !isNaN(lng)) {
            epicentres.push({ lat: lat, lng: lng, mag: parseFloat(g.magnitudo), data: g });
          }
        });
      }
    }
  } catch (e) {
    console.warn('[SiagaGempa] loadRegionHighlights: gempa fetch failed', e);
    return;
  }
  if (epicentres.length === 0) return;

  // Fetch kabupaten/kota GeoJSON; fallback to province-simple
  var KABURL  = 'https://raw.githubusercontent.com/superpikar/indonesia-geojson/master/indonesia-kabupaten-kota.json';
  var PROVURL = 'https://raw.githubusercontent.com/superpikar/indonesia-geojson/master/indonesia-province-simple.json';
  var geojson = null;
  try {
    var r1 = await fetch(KABURL);
    if (r1.ok) { geojson = await r1.json(); } else { throw new Error('kab fail'); }
  } catch (e1) {
    try { var r2 = await fetch(PROVURL); if (r2.ok) geojson = await r2.json(); }
    catch (e2) { console.warn('[SiagaGempa] loadRegionHighlights: GeoJSON unavailable', e2); return; }
  }
  if (!geojson || !Array.isArray(geojson.features)) return;

  // Ray-casting point-in-polygon
  function ptInPoly(lat, lng, ring) {
    var inside = false;
    for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
      var xi = ring[i][0], yi = ring[i][1];
      var xj = ring[j][0], yj = ring[j][1];
      var intersect = ((yi > lat) !== (yj > lat)) &&
        (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi);
      if (intersect) inside = !inside;
    }
    return inside;
  }

  // Returns the highest-mag epicentre affecting this feature, or null
  function matchEpicentre(feature) {
    var geom = feature.geometry;
    if (!geom) return null;
    var rings = [];
    if (geom.type === 'Polygon') { rings = [geom.coordinates[0]]; }
    else if (geom.type === 'MultiPolygon') {
      geom.coordinates.forEach(function (poly) { rings.push(poly[0]); });
    }
    if (rings.length === 0) return null;

    var allLng = [], allLat = [];
    rings.forEach(function (ring) {
      ring.forEach(function (pt) { allLng.push(pt[0]); allLat.push(pt[1]); });
    });
    var minLng = Math.min.apply(null, allLng), maxLng = Math.max.apply(null, allLng);
    var minLat = Math.min.apply(null, allLat), maxLat = Math.max.apply(null, allLat);
    var THRESHOLD = 0.35;

    var best = null;
    epicentres.forEach(function (ep) {
      if (ep.lng < minLng - THRESHOLD || ep.lng > maxLng + THRESHOLD) return;
      if (ep.lat < minLat - THRESHOLD || ep.lat > maxLat + THRESHOLD) return;

      var inside = rings.some(function (ring) { return ptInPoly(ep.lat, ep.lng, ring); });
      if (inside) { if (!best || ep.mag > best.mag) best = ep; return; }

      var close = rings.some(function (ring) {
        return ring.some(function (pt) {
          var dlat = pt[1] - ep.lat, dlng = pt[0] - ep.lng;
          return Math.sqrt(dlat * dlat + dlng * dlng) <= THRESHOLD;
        });
      });
      if (close && (!best || ep.mag > best.mag)) best = ep;
    });
    return best;
  }

  function buildKabPopup(propName, ep) {
    var g = ep.data, col = magColor(ep.mag), waktu = '—';
    try { waktu = new Date((g.waktu || '').replace(' ', 'T'))
      .toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(e) {}
    return '<div class="gempa-popup"><h3>' + (propName || '—') + '</h3><table>'
      + '<tr><td>Gempa terdekat</td><td style="color:' + col + ';font-weight:700">M ' + g.magnitudo + '</td></tr>'
      + '<tr><td>Wilayah</td><td>' + (g.wilayah || '—') + '</td></tr>'
      + '<tr><td>Kedalaman</td><td>' + g.kedalaman + ' km</td></tr>'
      + '<tr><td>Waktu</td><td>' + waktu + '</td></tr>'
      + '</table></div>';
  }

  geojson.features.forEach(function (feature) {
    var ep = matchEpicentre(feature);
    if (!ep) return;                                          // skip unaffected regions
    var props    = feature.properties || {};
    var propName = props.NAME_2 || props.NAME_1 || props.name || props.Name || props.KABKOT || '';
    var color    = magColor(ep.mag);
    var geoLayer = L.geoJSON(feature, {
      style: { color: color, weight: 1.3, opacity: 0.7, fillColor: color, fillOpacity: 0.25 },
      onEachFeature: function (feat, layer) {
        layer.bindPopup(buildKabPopup(propName, ep), { maxWidth: 300, className: 'dark-popup' });
        layer.on('click', function (e) {
          showDetailPanel(ep.data, 'gempa');
          layer.openPopup(e.latlng || e.target.getBounds().getCenter());
        });
      },
    });
    geoLayer.addTo(lgRegionHighlight);
    geoLayer.bringToBack();                                   // keep polygons beneath sesar + gempa
  });

  // Ensure sesar lines and gempa markers render above the region polygons
  if (lgSesarLines)   lgSesarLines.bringToFront();
  if (lgGempaMarkers) lgGempaMarkers.bringToFront();
}

// ── Highlight (fly + detail) for a gempa id ────────
function highlightGempaArea(gempaId) {
  gempaMarkers.forEach(function (e) {
    e.marker.setStyle({ weight: 1.2, color: '#ffffff' });
    e.marker.unbindTooltip();
  });

  var found = gempaMarkers.find(function (e) { return String(e.data.id) === String(gempaId); });
  if (!found) return;

  var lat   = parseFloat(found.data.latitude);
  var lng   = parseFloat(found.data.longitude);
  var color = magColor(parseFloat(found.data.magnitudo));

  found.marker.setStyle({ weight: 2.5, color: color });
  found.marker.bindTooltip(
    '<strong>' + (found.data.wilayah || '—') + '</strong>'
    + '<br><small>M ' + found.data.magnitudo + ' · ' + found.data.kedalaman + ' km</small>',
    { permanent: true, direction: 'top', className: 'dark-popup', offset: [0, -10] }
  ).openTooltip();

  showDetailPanel(found.data, 'gempa');
  mapInstance && mapInstance.flyTo([lat, lng], 7, { duration: 0.9, easeLinearity: 0.5 });
}

// ── Sidebar sesar list ───────────────────────────
function renderSesarList(data) {
  var container = document.getElementById('sesar-list');
  if (!container) return;
  container.innerHTML = '';

  if (!data || data.length === 0) {
    container.innerHTML = '<div class="loading-state" style="padding:12px 4px">Tidak ada data sesar.</div>';
    return;
  }

  data.forEach(function (s) {
    var div = document.createElement('div');
    div.className    = 'sesar-card';
    div.dataset.id   = s.id;
    div.dataset.risk = s.tingkat_risiko;

    var rc    = (s.tingkat_risiko || '').toLowerCase();
    var color = s.kode_warna_hex || RISK_COLOR[s.tingkat_risiko] || '#FF8A3D';
    var desc  = s.deskripsi ? s.deskripsi.substring(0, 95) + '…' : '';

    div.innerHTML = '<div class="sesar-card__header">'
      + '<span class="sesar-card__dot" style="background:' + color + ';box-shadow:0 0 6px ' + color + '"></span>'
      + '<span class="sesar-card__name">' + (s.nama_sesar || '—') + '</span>'
      + '<span class="sesar-card__badge chip ' + rc + '">' + s.tingkat_risiko + '</span>'
      + '</div>'
      + (desc ? '<div class="sesar-card__desc">' + desc + '</div>' : '');

    div.addEventListener('click', function () {
      $$('.sesar-card').forEach(function (c) { c.classList.remove('active'); });
      div.classList.add('active');
      showDetailPanel(s, 'sesar');
      flyToSesar(s);
      var entry = sesarLayers[s.id];
      if (entry && entry.line) entry.line.openPopup();
    });
    container.appendChild(div);
  });
}

function flyToSesar(s) {
  if (!mapInstance) return;
  var coords = safeParseCoords(s.koordinat_jalur);
  if (!coords || coords.length === 0) return;
  var mid = coords[Math.floor(coords.length / 2)];
  mapInstance.flyTo([parseFloat(mid[0]), parseFloat(mid[1])], 7, { duration: 1.0, easeLinearity: 0.5 });
}

// ── Detail panel ─────────────────────────────────
function showDetailPanel(data, type) {
  var panel = document.getElementById('detail-panel');
  if (!panel) return;

  var title = '', subtitle = '', badge = '', badgeClass = '', desc = '', coords = '';

  if (type === 'sesar') {
    var rc   = (data.tingkat_risiko || '').toLowerCase();
    title      = data.nama_sesar || '—';
    subtitle   = 'Jalur Sesar Aktif';
    badge      = data.tingkat_risiko || '';
    badgeClass = 'chip ' + rc;
    desc       = data.deskripsi || '';
    var mid    = (function() { try { var c = JSON.parse(data.koordinat_jalur); return c[Math.floor(c.length/2)]; } catch(e){ return null; } })();
    coords     = mid ? mid[0].toFixed(4) + ', ' + mid[1].toFixed(4) : '';
  } else if (type === 'gempa') {
    title      = data.wilayah || '—';
    subtitle   = 'Episentrum Gempa';
    badge      = 'M ' + data.magnitudo;
    badgeClass = 'dp-badge-mag';
    desc       = 'Kedalaman ' + data.kedalaman + ' km · Tsunami: ' + (data.status_tsunami || '—');
    coords     = parseFloat(data.latitude).toFixed(4) + ', ' + parseFloat(data.longitude).toFixed(4);
  }

  panel.querySelector('.detail-panel__subtitle').textContent = subtitle;
  panel.querySelector('.detail-panel__title').textContent    = title;
  panel.querySelector('.detail-panel__badge').textContent    = badge;
  panel.querySelector('.detail-panel__badge').className      = 'detail-panel__badge ' + badgeClass;
  panel.querySelector('.detail-panel__desc').textContent     = desc;
  panel.querySelector('.detail-panel__coords').textContent   = coords ? '📍 ' + coords : '';
  panel.classList.add('show');
}

document.addEventListener('click', function (e) {
  if (e.target.classList.contains('detail-panel__close')) {
    var panel = document.getElementById('detail-panel');
    if (panel) panel.classList.remove('show');
  }
});

// ── Filter buttons ───────────────────────────────
function setupMapFilters() {
  var btns = document.querySelectorAll('.filter-btn');
  btns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      btns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      activeFilter = btn.dataset.risk;
      applyFilter(activeFilter);
    });
  });
}

function applyFilter(risk) {
  Object.values(sesarLayers).forEach(function (entry) {
    var show = (risk === 'Semua' || entry.risk === risk);
    var lineStyle = show ? { opacity: 0.9, weight: 3.5 } : { opacity: 0.07, weight: 1 };
    entry.line.setStyle(lineStyle);
    entry.hit.setStyle({ opacity: 0, weight: show ? 16 : 0 });
  });
  document.querySelectorAll('.sesar-card[data-risk]').forEach(function (card) {
    card.style.display = (risk === 'Semua' || card.dataset.risk === risk) ? '' : 'none';
  });
}

// ── Layer toggle buttons ──────────────────────────
function setupLayerToggles() {
  document.querySelectorAll('.layer-toggle-btn').forEach(function (btn) {
    var target = btn.dataset.layer;
    btn.addEventListener('click', function () {
      var isOn = btn.classList.toggle('active');
      var lg = { 'sesar-lines': lgSesarLines, episentrum: lgGempaMarkers, wilayah: lgRegionHighlight }[target];
      if (!lg) return;
      if (isOn) lg.addTo(mapInstance); else mapInstance.removeLayer(lg);
    });
  });
}

// ═══════════════════════════════════════════════
//  PANDUAN PAGE
// ═══════════════════════════════════════════════
async function initPanduan() {
  var navContainer  = document.getElementById('panduan-nav');
  var mainContainer = document.getElementById('panduan-content');
  if (!navContainer || !mainContainer) return;

  try {
    var res  = await fetch('api/gempa.php?action=panduan');
    var json = await res.json();
    if (!json.success || !json.data.length) return;

    json.data.forEach(function (cat, i) {
      var btn = document.createElement('button');
      btn.className    = 'panduan-nav-item' + (i === 0 ? ' active' : '');
      btn.dataset.cat  = cat.kategori;
      btn.innerHTML    = '<span>' + cat.icon + '</span><span>' + cat.kategori + '</span>';
      btn.addEventListener('click', function () { loadPanduanCategory(cat.kategori, btn); });
      navContainer.appendChild(btn);
    });

    loadPanduanCategory(json.data[0].kategori, navContainer.querySelector('.panduan-nav-item'));
  } catch (e) {
    if (mainContainer) mainContainer.innerHTML = '<div class="error-state">Gagal memuat panduan.</div>';
  }
}

async function loadPanduanCategory(kategori, activeBtn) {
  var mainContainer = document.getElementById('panduan-content');
  if (!mainContainer) return;
  document.querySelectorAll('.panduan-nav-item').forEach(function (b) { b.classList.remove('active'); });
  if (activeBtn) activeBtn.classList.add('active');
  mainContainer.innerHTML = '<div class="loading-state">Memuat panduan…</div>';

  try {
    var res  = await fetch('api/gempa.php?action=panduan&kategori=' + encodeURIComponent(kategori));
    var json = await res.json();
    if (!json.success) throw new Error();

    var html = '<div class="panduan-category-header">'
      + '<div class="section-label">PANDUAN KESIAPSIAGAAN</div>'
      + '<h2 class="section-title">' + kategori + '</h2>'
      + '</div><div class="panduan-steps">';

    json.data.forEach(function (step, i) {
      html += '<div class="panduan-step">'
        + '<div class="panduan-step__header">'
        + '<div class="panduan-step__num">' + String(i + 1).padStart(2, '0') + '</div>'
        + '<div class="panduan-step__title">' + step.judul + '</div>'
        + '</div>'
        + '<div class="panduan-step__body">' + step.isi + '</div>'
        + '</div>';
    });
    html += '</div>';
    mainContainer.innerHTML = html;
  } catch (e) {
    mainContainer.innerHTML = '<div class="error-state">Gagal memuat panduan.</div>';
  }
}

// ═══════════════════════════════════════════════
//  KONTAK PAGE
// ═══════════════════════════════════════════════
async function initKontak() {
  var grid = document.getElementById('kontak-grid');
  if (!grid) return;
  grid.innerHTML = '<div class="loading-state">Memuat kontak…</div>';
  try {
    var res  = await fetch('api/gempa.php?action=kontak');
    var json = await res.json();
    if (!json.success) throw new Error();
    var emergency = ['117', '119', '110', '113', '115'];
    var html = '';
    json.data.forEach(function (k) {
      var isEmerg  = emergency.some(function (n) { return k.nomor_telepon.includes(n); });
      var telHref  = 'tel:' + k.nomor_telepon.replace(/[^0-9+]/g, '');
      html += '<div class="kontak-card' + (isEmerg ? ' emergency' : '') + '">'
        + '<div class="kontak-card__header">'
        + '<div class="kontak-card__icon">' + k.icon + '</div>'
        + '<div><div class="kontak-card__name">' + k.nama_instansi + '</div>'
        + '<div class="kontak-card__cat">' + k.kategori + '</div></div>'
        + '</div>'
        + '<hr class="kontak-card__divider">'
        + '<div class="kontak-card__number"><a href="' + telHref + '">' + k.nomor_telepon + '</a></div>'
        + '<div class="kontak-card__desc">' + k.keterangan + '</div>'
        + '</div>';
    });
    grid.innerHTML = html || '<div class="loading-state">Tidak ada data.</div>';
  } catch (e) {
    grid.innerHTML = '<div class="error-state">Gagal memuat kontak darurat.</div>';
  }
}
