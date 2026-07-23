<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('assets/css/leaflet.css') ?>" />
<script src="<?= base_url('assets/js/leaflet.js') ?>"></script>

<style>
:root { --primary: #1e3a8a; --border-color: #e2e8f0; }

.mapa-container {
    max-width: 480px; margin: 0 auto;
    background: #f8fafc; min-height: 100vh;
    display: flex; flex-direction: column;
}
.mapa-header {
    background: #fff; padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky; top: 0; z-index: 1100;
    display: flex; align-items: center; justify-content: space-between;
}
.mapa-header h1 { font-size: 15px; font-weight: 700; margin: 0; color: var(--primary); }
.back-btn {
    width: 34px; height: 34px; border-radius: 50%;
    background: #f1f5f9; border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569; cursor: pointer;
}

/* Toggle bar */
.layer-bar {
    background: #fff;
    border-bottom: 1px solid var(--border-color);
    padding: 8px 12px;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
.layer-toggle {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 10px; border-radius: 99px;
    border: 1.5px solid transparent;
    font-size: 11px; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    background: #f1f5f9; color: #475569;
    user-select: none;
}
.layer-toggle .dot {
    width: 10px; height: 10px;
    border-radius: 50%; flex-shrink: 0;
}
.layer-toggle.active-layer { background: #fff; }
.layer-toggle.active-layer.layer-meus  { border-color: #3b82f6; color: #1d4ed8; }
.layer-toggle.active-layer.layer-livres{ border-color: #10b981; color: #15803d; }
.layer-toggle.active-layer.layer-ocup  { border-color: #ef4444; color: #b91c1c; }

.counter-pill {
    margin-left: auto; font-size: 10px; color: #94a3b8;
    white-space: nowrap;
}

#map {
    flex: 1; min-height: calc(100vh - 110px); z-index: 10;
}

/* Bottom sheet */
.client-sheet {
    position: fixed; bottom: 0; left: 50%;
    transform: translateX(-50%) translateY(100%);
    width: 100%; max-width: 480px;
    background: #fff;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 32px rgba(0,0,0,0.18);
    padding: 18px 20px 32px; z-index: 2000;
    transition: transform 0.3s cubic-bezier(0.32,0.72,0,1);
}
.client-sheet.open { transform: translateX(-50%) translateY(0); }
.sheet-handle {
    width: 36px; height: 4px; background: #e2e8f0;
    border-radius: 99px; margin: 0 auto 14px;
}
.sheet-type-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 99px;
    font-size: 10px; font-weight: 700; margin-bottom: 8px;
}
.sheet-type-pill.meu    { background: #eff6ff; color: #1d4ed8; }
.sheet-type-pill.livre  { background: #f0fdf4; color: #15803d; }
.sheet-type-pill.ocupado{ background: #fef2f2; color: #b91c1c; }
.sheet-name { font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 4px; line-height: 1.3; }
.sheet-cnpj { font-size: 11px; color: #64748b; font-family: monospace; margin-bottom: 10px; }
.sheet-meta { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
.sheet-tag {
    background: #f1f5f9; color: #475569;
    padding: 3px 8px; border-radius: 6px;
    font-size: 10px; font-weight: 600;
}
.sheet-score-row {
    display: flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 7px 12px; margin-bottom: 12px;
}
.sheet-score-bar { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.sheet-score-fill { height: 100%; border-radius: 99px; transition: width .5s ease; }
.sheet-actions { display: flex; gap: 8px; }
.btn-sh-primary {
    flex: 1; background: var(--primary); color: #fff;
    border: none; border-radius: 12px; padding: 11px;
    font-size: 13px; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.btn-sh-primary.verde { background: #16a34a; }
.btn-sh-secondary {
    background: #f1f5f9; color: #475569; border: none;
    border-radius: 12px; padding: 11px 13px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 5px;
}
</style>

<div class="mapa-container">
    <div class="mapa-header">
        <button class="back-btn" onclick="window.history.back()"><i class="bi bi-arrow-left"></i></button>
        <h1><i class="bi bi-map me-1"></i> Mapa da Carteira</h1>
        <div style="width:34px;"></div>
    </div>

    <!-- Toggles de camada -->
    <div class="layer-bar">
        <button class="layer-toggle active-layer layer-meus" id="toggleMeus" onclick="toggleLayer('meus')">
            <span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:2px;flex-shrink:0;"></span> Meus Clientes
        </button>
        <button class="layer-toggle active-layer layer-livres" id="toggleLivres" onclick="toggleLayer('livres')">
            <span style="display:inline-block;width:9px;height:9px;background:#10b981;transform:rotate(45deg);flex-shrink:0;margin:1px 3px 1px 1px;"></span> Fora da Carteira
        </button>
        <button class="layer-toggle active-layer layer-ocup" id="toggleOcup" onclick="toggleLayer('ocup')">
            <span style="display:inline-block;width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-bottom:10px solid #ef4444;flex-shrink:0;"></span> Outro Vendedor
        </button>
        <span class="counter-pill" id="counterPill">Carregando...</span>
    </div>

    <div id="map"></div>
</div>

<!-- Bottom Sheet -->
<div class="client-sheet" id="clientSheet">
    <div class="sheet-handle"></div>
    <div id="sheetContent"></div>
</div>
<div id="sheetOverlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.25);z-index:1999;"
     onclick="closeSheet()"></div>

<script>
// ── Helpers ──────────────────────────────────────────────────
function scoreColor(score) {
    if (score >= 60) return '#22c55e';
    if (score >= 30) return '#f59e0b';
    if (score > 0)  return '#3b82f6';
    return '#9ca3af';
}
function fmtCnpj(c) {
    return c.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}

// ── Ícones Geométricos Personalizados (SVG) ───────────────────
// 1. Quadrado Azul (Meus Clientes)
function squareBlueIcon(size = 18) {
    return L.divIcon({
        html: `<svg width="${size}" height="${size}" viewBox="0 0 18 18" style="filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.35)); cursor:pointer;">
                 <rect x="2" y="2" width="14" height="14" rx="2" fill="#3b82f6" stroke="#ffffff" stroke-width="2"/>
               </svg>`,
        className: '',
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2]
    });
}

// 2. Triângulo Vermelho (Clientes de Outros Vendedores)
function triangleRedIcon(size = 18) {
    return L.divIcon({
        html: `<svg width="${size}" height="${size}" viewBox="0 0 18 18" style="filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.35)); cursor:pointer;">
                 <polygon points="9,1 17,16 1,16" fill="#ef4444" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/>
               </svg>`,
        className: '',
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2]
    });
}

// 3. Losango Verde (Clientes Fora de Qualquer Carteira / Livres)
function diamondGreenIcon(size = 18) {
    return L.divIcon({
        html: `<svg width="${size}" height="${size}" viewBox="0 0 18 18" style="filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.35)); cursor:pointer;">
                 <polygon points="9,1 17,9 9,17 1,9" fill="#10b981" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/>
               </svg>`,
        className: '',
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2]
    });
}

// ── Mapa ─────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: true }).setView([-23.5505, -46.6333], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const layerMeus   = L.layerGroup().addTo(map);
const layerLivres = L.layerGroup().addTo(map);
const layerOcup   = L.layerGroup().addTo(map);
const layerState  = { meus: true, livres: true, ocup: true };

let totalMeus = 0, totalLivres = 0, totalOcup = 0;

// GPS do usuário
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
        const latlng = [pos.coords.latitude, pos.coords.longitude];
        map.setView(latlng, 13);
        L.marker(latlng, {
            icon: L.divIcon({
                html: '<i class="bi bi-person-circle" style="font-size:22px;color:#3b82f6;text-shadow:0 1px 3px rgba(0,0,0,.3);"></i>',
                className: '', iconSize: [22,22], iconAnchor: [11,11]
            })
        }).addTo(map).bindPopup('Você está aqui');
    }, () => {});
}

// ── Toggle de camada ─────────────────────────────────────────
function toggleLayer(which) {
    layerState[which] = !layerState[which];
    const ids = { meus: 'toggleMeus', livres: 'toggleLivres', ocup: 'toggleOcup' };
    const layers = { meus: layerMeus, livres: layerLivres, ocup: layerOcup };
    const btn   = document.getElementById(ids[which]);
    const layer = layers[which];

    if (layerState[which]) {
        map.addLayer(layer);
        btn.classList.add('active-layer');
    } else {
        map.removeLayer(layer);
        btn.classList.remove('active-layer');
    }
    updateCounter();
}

function updateCounter() {
    const m = layerState.meus   ? totalMeus   : 0;
    const l = layerState.livres ? totalLivres : 0;
    const o = layerState.ocup   ? totalOcup   : 0;
    document.getElementById('counterPill').textContent =
        `${m} meus · ${l} fora da carteira · ${o} outros`;
}

// ── Carregar dados ───────────────────────────────────────────
async function loadAll() {
    const [resMeus, resLivres] = await Promise.all([
        fetch('<?= site_url('vendedor/clientes/mapa') ?>'),
        fetch('<?= site_url('vendedor/livres/mapa') ?>')
    ]);
    const dataMeus   = await resMeus.json();
    const dataLivres = await resLivres.json();

    totalMeus   = dataMeus.total   || 0;
    totalLivres = 0;
    totalOcup   = 0;

    // Marcadores — meus clientes (Quadrado Azul)
    (dataMeus.clientes || []).forEach(c => {
        L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], { icon: squareBlueIcon(18) })
            .addTo(layerMeus)
            .on('click', () => openSheet(c, 'meu'));
    });

    // Marcadores — fora de qualquer carteira (Losango Verde) e outro vendedor (Triângulo Vermelho)
    (dataLivres.livres || []).forEach(c => {
        const isOcup = c.ocupado === true || c.ocupado === 't' || c.ocupado === '1' || c.ocupado === 1;
        const icon   = isOcup ? triangleRedIcon(18) : diamondGreenIcon(18);
        const layer  = isOcup ? layerOcup : layerLivres;
        if (isOcup) totalOcup++; else totalLivres++;
        L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], { icon: icon })
            .addTo(layer)
            .on('click', () => openSheet(c, isOcup ? 'ocupado' : 'livre'));
    });

    updateCounter();

    // Ajusta bounds para mostrar tudo
    const allPoints = [
        ...(dataMeus.clientes || []).map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]),
        ...(dataLivres.livres  || []).map(c => [parseFloat(c.latitude), parseFloat(c.longitude)])
    ].filter(p => !isNaN(p[0]) && !isNaN(p[1]));

    if (allPoints.length > 0) {
        map.fitBounds(L.latLngBounds(allPoints).pad(0.1));
    }

    updateCounter();
}

// ── Bottom Sheet ─────────────────────────────────────────────
function openSheet(c, tipo) {
    const score = parseInt(c.score) || 0;
    const color = scoreColor(score);
    const cnpjFmt = fmtCnpj(c.cnpj);
    const scoreLabel = score >= 60 ? '🔥 Alto' : score >= 30 ? '⚡ Médio' : score > 0 ? '· Baixo' : '—';

    const pill = tipo === 'livre'
        ? `<span class="sheet-type-pill livre"><span style="display:inline-block;width:8px;height:8px;background:#10b981;transform:rotate(45deg);margin-right:4px;"></span> Fora de qualquer carteira</span>`
        : tipo === 'ocupado'
        ? `<span class="sheet-type-pill ocupado"><span style="display:inline-block;width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-bottom:8px solid #ef4444;margin-right:4px;"></span> Carteira de outro vendedor</span>`
        : `<span class="sheet-type-pill meu"><span style="display:inline-block;width:8px;height:8px;background:#3b82f6;border-radius:1.5px;margin-right:4px;"></span> Meu Cliente</span>`;

    const scoreRow = score > 0 ? `
        <div class="sheet-score-row">
            <span style="font-size:10px;color:#64748b;white-space:nowrap;">${scoreLabel}</span>
            <div class="sheet-score-bar">
                <div class="sheet-score-fill" style="width:${score}%;background:${color};"></div>
            </div>
            <span style="font-size:12px;font-weight:800;color:#1e3a8a;">${score}</span>
        </div>` : '';

    const tags = [c.segmento_mercado, c.ciclo_de_vida, c.status_operacional, c.cnae]
        .filter(Boolean)
        .map(t => `<span class="sheet-tag">${t}</span>`)
        .join('');

    const actionBtn = tipo === 'livre'
        ? `<button class="btn-sh-primary verde" onclick="location.href='<?= site_url('vendedor/cliente/') ?>${c.cnpj}'">
               <i class="bi bi-person-plus-fill"></i> Prospectar
           </button>`
        : `<button class="btn-sh-primary" onclick="location.href='<?= site_url('vendedor/cliente/') ?>${c.cnpj}'">
               <i class="bi bi-person-lines-fill"></i> Ver Detalhe
           </button>`;

    document.getElementById('sheetContent').innerHTML = `
        ${pill}
        <div class="sheet-name">${c.razao_social || c.cnpj}</div>
        <div class="sheet-cnpj">${cnpjFmt}</div>
        ${tags ? `<div class="sheet-meta">${tags}</div>` : ''}
        ${scoreRow}
        <div class="sheet-actions">
            ${actionBtn}
            <button class="btn-sh-secondary" onclick="openGMaps(${c.latitude},${c.longitude})">
                <i class="bi bi-geo-alt-fill"></i>
            </button>
            <button class="btn-sh-secondary" onclick="closeSheet()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;

    document.getElementById('clientSheet').classList.add('open');
    document.getElementById('sheetOverlay').style.display = 'block';
    map.panTo([parseFloat(c.latitude), parseFloat(c.longitude)], { animate: true });
}

function closeSheet() {
    document.getElementById('clientSheet').classList.remove('open');
    document.getElementById('sheetOverlay').style.display = 'none';
}

function openGMaps(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

loadAll();
</script>

<?= $this->endSection() ?>
