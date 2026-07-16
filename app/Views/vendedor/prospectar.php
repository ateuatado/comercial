<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --success: #10b981; /* Verde para Livres */
    --danger: #ef4444;  /* Vermelho para Ocupados */
    --neutral-light: #f8fafc;
    --border-color: #e2e8f0;
}

.radar-container {
    max-width: 480px;
    margin: 0 auto;
    background: var(--neutral-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
}

.radar-header {
    background: #fff;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.radar-header h1 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: var(--primary);
}

.back-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569;
    cursor: pointer;
}

#map {
    height: 280px;
    width: 100%;
    border-bottom: 1px solid var(--border-color);
    z-index: 10;
}

.pre-visit-card {
    background: #fff;
    margin: 12px;
    padding: 16px;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
}

.pre-visit-card h3 {
    font-size: 14px;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 8px;
    color: #334155;
}

.form-group {
    display: flex;
    gap: 8px;
}

.form-group input {
    flex: 1;
    padding: 10px 14px;
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
}

.form-group input:focus {
    border-color: var(--primary-light);
}

.btn-search {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-search:hover {
    background: var(--primary-light);
}

.list-section {
    flex: 1;
    padding: 0 12px 24px;
}

.list-title {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 10px;
    padding-left: 4px;
}

.radar-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.radar-item {
    background: #fff;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1.5px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: 6px;
    position: relative;
    border-left-width: 5px;
    transition: transform 0.2s;
}

.radar-item:active {
    transform: scale(0.98);
}

.radar-item.livre {
    border-left-color: var(--success);
}

.radar-item.ocupado {
    border-left-color: var(--danger);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.item-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}

.status-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    text-transform: uppercase;
}

.livre .status-badge {
    background: #d1fae5;
    color: #065f46;
}

.ocupado .status-badge {
    background: #fee2e2;
    color: #991b1b;
}

.item-details {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
}

.item-distance {
    font-size: 11px;
    font-weight: 600;
    color: var(--primary-light);
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}

.action-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
    border-top: 1px solid #f1f5f9;
    padding-top: 8px;
}

.btn-prospect {
    background: var(--success);
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-prospect:hover {
    opacity: 0.9;
}

.btn-google-maps {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid var(--border-color);
    padding: 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0 12px 12px;
    cursor: pointer;
}

.btn-google-maps:hover {
    background: #e2e8f0;
}
</style>

<div class="radar-container">
    <div class="radar-header">
        <button class="back-btn" onclick="window.history.back()">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h1>Radar de Prospecção</h1>
        <div style="width: 36px;"></div> <!-- Spacer -->
    </div>

    <!-- Mapa Leaflet/OSM -->
    <div id="map"></div>

    <!-- Botão Boneco do Maps -->
    <button class="btn-google-maps" onclick="location.href='<?= site_url('vendedor/maps-contract') ?>'">
        <i class="bi bi-google"></i> Usar Google Maps API (Requer Contrato)
    </button>

    <!-- Pré-Visita Card -->
    <div class="pre-visit-card">
        <h3>Planejar Rota (Pré-Visita)</h3>
        <form id="preVisitForm" onsubmit="handlePreVisit(event)">
            <div class="form-group">
                <input type="text" id="bairroInput" placeholder="Ex: Itaquera ou Centro..." required>
                <button type="submit" class="btn-search">Cadastrar</button>
            </div>
        </form>
    </div>

    <!-- Lista de Empresas -->
    <div class="list-section">
        <h2 class="list-title">Empresas na Região</h2>
        <div class="radar-list" id="radarList">
            <div style="text-align:center; padding: 20px; color:#64748b;">
                <i class="bi bi-geo-alt" style="font-size: 24px;"></i>
                <p style="margin-top:8px;">Ative o GPS ou planeje uma Pré-Visita acima.</p>
            </div>
        </div>
    </div>
</div>

<script>
let map;
let markersGroup;
let currentCoords = [-23.5505, -46.6333]; // Padrão São Paulo se falhar GPS

// Inicializar Mapa
document.addEventListener("DOMContentLoaded", function() {
    map = L.map('map').setView(currentCoords, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    markersGroup = L.layerGroup().addTo(map);
    
    // Obter GPS Real
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            currentCoords = [position.coords.latitude, position.coords.longitude];
            map.setView(currentCoords, 14);
            
            // Marcador do vendedor
            L.marker(currentCoords, {
                icon: L.divIcon({
                    html: '<i class="bi bi-person-circle" style="font-size: 24px; color: #3b82f6;"></i>',
                    className: 'user-gps-marker',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                })
            }).addTo(map).bindPopup("Sua Localização").openPopup();
            
            loadRadar(currentCoords[0], currentCoords[1]);
        }, () => {
            loadRadar(currentCoords[0], currentCoords[1]);
        });
    } else {
        loadRadar(currentCoords[0], currentCoords[1]);
    }
});

async function loadRadar(lat, lng) {
    const listContainer = document.getElementById('radarList');
    listContainer.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Carregando empresas...</div>';
    
    try {
        const res = await fetch(`<?= site_url('vendedor/prospectar/api') ?>?lat=${lat}&lng=${lng}`);
        const data = await res.json();
        
        markersGroup.clearLayers();
        listContainer.innerHTML = '';
        
        if (!data.empresas || data.empresas.length === 0) {
            listContainer.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Nenhuma empresa cadastrada próxima.</div>';
            return;
        }
        
        data.empresas.forEach(emp => {
            // Cor do marcador
            const markerColor = emp.status === 'Livre' ? '#10b981' : '#ef4444';
            const iconHtml = `<i class="bi bi-geo-alt-fill" style="font-size: 24px; color: ${markerColor};"></i>`;
            
            // Adiciona no mapa
            L.marker([emp.latitude, emp.longitude], {
                icon: L.divIcon({
                    html: iconHtml,
                    className: 'pin-marker',
                    iconSize: [24, 24],
                    iconAnchor: [12, 24]
                })
            }).addTo(markersGroup).bindPopup(`<b>${emp.razao_social}</b><br>${emp.status === 'Livre' ? 'Livre para Prospecção' : 'Já possui carteira'}`);
            
            // Adiciona na lista
            const item = document.createElement('div');
            item.className = `radar-item ${emp.status === 'Livre' ? 'livre' : 'ocupado'}`;
            item.innerHTML = `
                <div class="item-header">
                    <span class="item-name">${emp.razao_social}</span>
                    <span class="status-badge">${emp.status}</span>
                </div>
                <div class="item-details">
                    <div><b>CNPJ:</b> ${emp.cnpj}</div>
                    <div><b>Endereço:</b> ${emp.endereco}</div>
                    <div class="item-distance"><i class="bi bi-compass"></i> ${emp.distancia.toFixed(2)} km</div>
                </div>
                ${emp.status === 'Livre' ? `
                    <div class="action-row">
                        <button class="btn-prospect" onclick="iniciarProspecção('${emp.cnpj}')">Prospectar Cliente</button>
                    </div>
                ` : ''}
            `;
            listContainer.appendChild(item);
        });
    } catch(e) {
        listContainer.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Falha ao obter dados do radar.</div>';
    }
}

async function handlePreVisit(e) {
    e.preventDefault();
    const bairro = document.getElementById('bairroInput').value;
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    btn.textContent = 'Aguarde...';
    
    try {
        const res = await fetch('<?= site_url('vendedor/pre-visita') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `bairro=${encodeURIComponent(bairro)}&lat=${currentCoords[0]}&lng=${currentCoords[1]}`
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Região cadastrada com sucesso! Os pontos foram carregados no radar.');
            loadRadar(currentCoords[0], currentCoords[1]);
        } else {
            alert('Não encontramos essa região no banco ou ocorreu um erro.');
        }
    } catch(e) {
        alert('Erro de comunicação.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Cadastrar';
    }
}

function iniciarProspecção(cnpj) {
    if (confirm('Deseja iniciar a prospecção deste cliente? Você será redirecionado para registrar uma nota de visita.')) {
        location.href = `<?= site_url('vendedor/cliente') ?>/${cnpj}/nota`;
    }
}
</script>

<?= $this->endSection() ?>
