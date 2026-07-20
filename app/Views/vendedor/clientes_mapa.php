<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('assets/css/leaflet.css') ?>" />
<script src="<?= base_url('assets/js/leaflet.js') ?>"></script>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --border-color: #e2e8f0;
}

.mapa-container {
    max-width: 480px;
    margin: 0 auto;
    background: #f8fafc;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.mapa-header {
    background: #fff;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mapa-header h1 { font-size: 16px; font-weight: 700; margin: 0; color: var(--primary); }

.back-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: #f1f5f9; border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569; cursor: pointer;
}

.mapa-legend {
    background: #fff;
    padding: 8px 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 600;
    color: #475569;
}

.legend-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

#map {
    flex: 1;
    min-height: calc(100vh - 130px);
    z-index: 10;
}

/* Bottom sheet do cliente selecionado */
.client-sheet {
    position: fixed;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%) translateY(100%);
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 32px rgba(0,0,0,0.15);
    padding: 20px 20px 32px;
    z-index: 2000;
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

.client-sheet.open { transform: translateX(-50%) translateY(0); }

.sheet-handle {
    width: 36px; height: 4px;
    background: #e2e8f0;
    border-radius: 99px;
    margin: 0 auto 16px;
}

.sheet-categoria {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.sheet-name {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 4px;
    line-height: 1.3;
}

.sheet-cnpj {
    font-size: 11px;
    color: #64748b;
    font-family: monospace;
    margin-bottom: 10px;
}

.sheet-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.sheet-tag {
    background: #f1f5f9;
    color: #475569;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
}

.sheet-score {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    margin-bottom: 14px;
}

.sheet-score-bar {
    flex: 1;
    height: 6px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
}

.sheet-score-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.5s ease;
}

.sheet-actions {
    display: flex;
    gap: 8px;
}

.btn-sheet-primary {
    flex: 1;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-sheet-secondary {
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.map-counter {
    position: absolute;
    bottom: 16px;
    right: 16px;
    z-index: 1050;
    background: var(--primary);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 99px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    pointer-events: none;
}
</style>

<div class="mapa-container">
    <div class="mapa-header">
        <button class="back-btn" onclick="window.history.back()">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h1><i class="bi bi-map me-1"></i> Minha Carteira</h1>
        <div style="width:36px;"></div>
    </div>

    <div class="mapa-legend">
        <div class="legend-item">
            <div class="legend-dot" style="background:#3b82f6;"></div> Meu cliente
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#f59e0b;"></div> Score médio
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#22c55e;"></div> Score alto (≥60)
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#6b7280;"></div> Sem score
        </div>
        <span id="mapCounter" style="margin-left:auto;font-size:10px;color:#94a3b8;">Carregando...</span>
    </div>

    <div style="position:relative;flex:1;">
        <div id="map"></div>
    </div>
</div>

<!-- Bottom Sheet -->
<div class="client-sheet" id="clientSheet">
    <div class="sheet-handle"></div>
    <div id="sheetContent"><!-- preenchido via JS --></div>
</div>
<div id="sheetOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:1999;"
     onclick="closeSheet()"></div>

<script>
const CAT_COLORS = {
    'BRONZE':   '#cd7f32', 'PRATA':    '#a0a0a0', 'OURO':     '#daa520',
    'DIAMANTE': '#2563eb', 'PLATINUM': '#7c3aed', 'INFINITE': '#1e293b',
    'CLUBE':    '#059669'
};

function markerColor(score) {
    if (score <= 0) return '#6b7280';
    if (score >= 60) return '#22c55e';
    if (score >= 30) return '#f59e0b';
    return '#3b82f6';
}

// ── Mapa ─────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: true }).setView([-23.5505, -46.6333], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const markerLayer = L.layerGroup().addTo(map);
let allClientes = [];

// Tentar centrar no GPS
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
        map.setView([pos.coords.latitude, pos.coords.longitude], 12);
        L.marker([pos.coords.latitude, pos.coords.longitude], {
            icon: L.divIcon({
                html: '<i class="bi bi-person-circle" style="font-size:22px;color:#3b82f6;text-shadow:0 1px 3px rgba(0,0,0,.3);"></i>',
                className: '', iconSize: [22,22], iconAnchor: [11,11]
            })
        }).addTo(map).bindPopup('Você está aqui');
    }, () => {});
}

// ── Carregar clientes ────────────────────────────────────────
async function loadClientes() {
    try {
        const res  = await fetch('<?= site_url('vendedor/clientes/mapa') ?>');
        const data = await res.json();
        if (!data.success) return;

        allClientes = data.clientes;
        document.getElementById('mapCounter').textContent = `${data.total} clientes no mapa`;
        renderMarkers(data.clientes);

        if (data.clientes.length > 0) {
            const bounds = L.latLngBounds(data.clientes.map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]));
            map.fitBounds(bounds.pad(0.15));
        }
    } catch(e) {
        document.getElementById('mapCounter').textContent = 'Erro ao carregar';
    }
}

function renderMarkers(list) {
    markerLayer.clearLayers();
    list.forEach(c => {
        const lat   = parseFloat(c.latitude);
        const lng   = parseFloat(c.longitude);
        const score = parseInt(c.score) || 0;
        const color = markerColor(score);

        const icon = L.divIcon({
            html: `<div style="
                width:14px;height:14px;border-radius:50%;
                background:${color};border:2px solid #fff;
                box-shadow:0 1px 4px rgba(0,0,0,0.35);
                cursor:pointer;
            "></div>`,
            className: '',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        });

        L.marker([lat, lng], { icon })
            .addTo(markerLayer)
            .on('click', () => openSheet(c));
    });
}

// ── Bottom Sheet ─────────────────────────────────────────────
function openSheet(c) {
    const score = parseInt(c.score) || 0;
    const color = markerColor(score);
    const catColor = CAT_COLORS[c.categoria] || '#64748b';
    const cnpjFmt  = c.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    const scoreLabel = score >= 60 ? '🔥 Alto' : score >= 30 ? '⚡ Médio' : score > 0 ? '· Baixo' : '— Sem score';

    document.getElementById('sheetContent').innerHTML = `
        <span class="sheet-categoria" style="background:${catColor}20;color:${catColor};">
            ${c.categoria || 'SEM CATEGORIA'}
        </span>
        <div class="sheet-name">${c.razao_social}</div>
        <div class="sheet-cnpj">${cnpjFmt}</div>
        <div class="sheet-meta">
            ${c.segmento_mercado ? `<span class="sheet-tag">${c.segmento_mercado}</span>` : ''}
            ${c.ciclo_de_vida    ? `<span class="sheet-tag">${c.ciclo_de_vida}</span>` : ''}
            ${c.status_operacional ? `<span class="sheet-tag">${c.status_operacional}</span>` : ''}
        </div>
        ${score > 0 ? `
        <div class="sheet-score">
            <span style="font-size:10px;color:#64748b;white-space:nowrap;">${scoreLabel}</span>
            <div class="sheet-score-bar">
                <div class="sheet-score-fill" style="width:${score}%;background:${color};"></div>
            </div>
            <span style="font-size:12px;font-weight:800;color:#1e3a8a;">${score}</span>
        </div>` : ''}
        <div class="sheet-actions">
            <button class="btn-sheet-primary" onclick="location.href='<?= site_url('vendedor/cliente/') ?>${c.cnpj}'">
                <i class="bi bi-person-lines-fill"></i> Ver Detalhe
            </button>
            <button class="btn-sheet-secondary" onclick="openGoogleMaps(${c.latitude},${c.longitude})">
                <i class="bi bi-geo-alt-fill"></i>
            </button>
            <button class="btn-sheet-secondary" onclick="closeSheet()">
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

function openGoogleMaps(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

loadClientes();
</script>

<?= $this->endSection() ?>
