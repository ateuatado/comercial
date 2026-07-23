<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* ── Mobile-first card swipe interface ── */
:root {
    --card-radius: 20px;
    --cat-bronze: #cd7f32;
    --cat-ouro: #b8860b;
    --cat-prata: #8a8a8a;
    --cat-diamante: #185abc;
    --cat-platinum: #6b21a8;
    --cat-infinite: #1e293b;
    --cat-clube: #047857;
}

.swipe-container {
    max-width: 480px;
    margin: 0 auto;
    padding: 0;
    min-height: 100vh;
    background: #f0f2f5;
    position: relative;
    overflow-x: hidden;
}

/* Top bar */
.swipe-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 100;
}

.swipe-topbar .back-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f3f4f6;
    border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: #374151;
    cursor: pointer;
    transition: background .2s;
}
.swipe-topbar .back-btn:hover { background: #e5e7eb; }
.swipe-topbar .counter {
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
}

/* Search & Filter bar */
.filter-bar {
    padding: 10px 16px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}
.search-wrap {
    position: relative;
    margin-bottom: 8px;
}
.search-wrap input {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    background: #f9fafb;
    transition: border-color .2s;
    outline: none;
}
.search-wrap input:focus { border-color: #3b82f6; background: #fff; }
.search-wrap .search-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 16px;
}
.filter-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
}
.filter-chips::-webkit-scrollbar { display: none; }
.filter-chip {
    flex-shrink: 0;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
.filter-chip.active {
    background: #1e40af;
    color: #fff;
    border-color: #1e40af;
}
.filter-chip:hover:not(.active) { border-color: #3b82f6; color: #3b82f6; }

/* Category specific colors */
.filter-chip[data-value="BRONZE"] { border-color: #cd7f32; color: #cd7f32; }
.filter-chip[data-value="BRONZE"].active { background: #cd7f32 !important; color: #fff !important; border-color: #cd7f32 !important; }
.filter-chip[data-value="BRONZE"]:hover:not(.active) { background: #cd7f32; color: #fff; border-color: #cd7f32; }

.filter-chip[data-value="OURO"] { border-color: #b8860b; color: #b8860b; }
.filter-chip[data-value="OURO"].active { background: #b8860b !important; color: #fff !important; border-color: #b8860b !important; }
.filter-chip[data-value="OURO"]:hover:not(.active) { background: #b8860b; color: #fff; border-color: #b8860b; }

.filter-chip[data-value="PRATA"] { border-color: #8a8a8a; color: #8a8a8a; }
.filter-chip[data-value="PRATA"].active { background: #8a8a8a !important; color: #fff !important; border-color: #8a8a8a !important; }
.filter-chip[data-value="PRATA"]:hover:not(.active) { background: #8a8a8a; color: #fff; border-color: #8a8a8a; }

.filter-chip[data-value="DIAMANTE"] { border-color: #185abc; color: #185abc; }
.filter-chip[data-value="DIAMANTE"].active { background: #185abc !important; color: #fff !important; border-color: #185abc !important; }
.filter-chip[data-value="DIAMANTE"]:hover:not(.active) { background: #185abc; color: #fff; border-color: #185abc; }

.filter-chip[data-value="PLATINUM"] { border-color: #6b21a8; color: #6b21a8; }
.filter-chip[data-value="PLATINUM"].active { background: #6b21a8 !important; color: #fff !important; border-color: #6b21a8 !important; }
.filter-chip[data-value="PLATINUM"]:hover:not(.active) { background: #6b21a8; color: #fff; border-color: #6b21a8; }

.filter-chip[data-value="INFINITE"] { border-color: #1e293b; color: #1e293b; }
.filter-chip[data-value="INFINITE"].active { background: #1e293b !important; color: #fff !important; border-color: #1e293b !important; }
.filter-chip[data-value="INFINITE"]:hover:not(.active) { background: #1e293b; color: #fff; border-color: #1e293b; }

.filter-chip[data-value="CLUBE"] { border-color: #047857; color: #047857; }
.filter-chip[data-value="CLUBE"].active { background: #047857 !important; color: #fff !important; border-color: #047857 !important; }
.filter-chip[data-value="CLUBE"]:hover:not(.active) { background: #047857; color: #fff; border-color: #047857; }


/* Card deck area */
.card-deck {
    padding: 16px;
    min-height: calc(100vh - 200px);
    position: relative;
}

/* Individual card */
.client-card {
    background: #fff;
    border-radius: var(--card-radius);
    box-shadow: 0 4px 24px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.04);
    padding: 0;
    margin-bottom: 16px;
    overflow: hidden;
    transform: translateX(0);
    transition: transform .3s ease, opacity .3s ease;
    will-change: transform;
    -webkit-user-select: none;
    user-select: none;
}
.client-card.swiping {
    transition: none;
}
.client-card.swipe-left {
    transform: translateX(-120%);
    opacity: 0;
}
.client-card.swipe-right {
    transform: translateX(120%);
    opacity: 0;
}

/* Category banner */
.card-banner {
    padding: 14px 20px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-banner .cat-name {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: .9;
}
.card-banner .cat-badge {
    font-size: 10px;
    background: rgba(255,255,255,.2);
    padding: 3px 10px;
    border-radius: 10px;
    font-weight: 600;
    backdrop-filter: blur(4px);
}

/* Card body */
.card-body-custom {
    padding: 18px 20px;
}
.client-name {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 4px;
}
.client-cnpj {
    font-size: 13px;
    color: #64748b;
    font-family: 'Courier New', monospace;
    letter-spacing: .5px;
    margin-bottom: 12px;
}

/* Info rows */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-label {
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    margin-bottom: 2px;
}
.info-value {
    font-size: 13px;
    color: #334155;
    font-weight: 500;
    line-height: 1.3;
}
.info-value.masked {
    color: #cbd5e1;
    cursor: pointer;
    transition: color .2s;
}
.info-value.masked.revealed {
    color: #334155;
}

/* Tags */
.tag-row {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 14px;
}
.tag {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.tag.ciclo {
    background: #dbeafe;
    color: #1e40af;
}
.tag.prospeccao-sim {
    background: #dcfce7;
    color: #166534;
}
.tag.prospeccao-nao {
    background: #fef3c7;
    color: #92400e;
}

/* Action buttons */
.card-actions {
    display: flex;
    gap: 0;
    border-top: 1px solid #f1f5f9;
}
.card-actions .action-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #64748b;
    transition: background .2s, color .2s;
}
.card-actions .action-btn:hover {
    background: #f8fafc;
    color: #1e40af;
}
.card-actions .action-btn:not(:last-child) {
    border-right: 1px solid #f1f5f9;
}

/* Loading */
.loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.loading-spinner {
    width: 40px; height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin .8s linear infinite;
    margin-bottom: 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.empty-state .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

/* Swipe hint */
.swipe-hint {
    text-align: center;
    padding: 8px;
    color: #94a3b8;
    font-size: 11px;
}

/* Filter drawer */
.filter-drawer {
    position: fixed;
    bottom: -100%;
    left: 0; right: 0;
    background: #fff;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -4px 24px rgba(0,0,0,.1);
    padding: 20px;
    z-index: 200;
    transition: bottom .3s ease;
    max-width: 480px;
    margin: 0 auto;
}
.filter-drawer.open { bottom: 0; }
.filter-drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.3);
    z-index: 199;
    display: none;
}
.filter-drawer-backdrop.open { display: block; }
.drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}
.drawer-header h6 { font-weight: 700; margin: 0; }
.drawer-close {
    width: 32px; height: 32px; border-radius: 50%;
    background: #f3f4f6; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.drawer-section { margin-bottom: 14px; }
.drawer-section label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    color: #64748b; letter-spacing: .5px; margin-bottom: 6px; display: block;
}
.drawer-section select {
    width: 100%; padding: 10px 12px; border-radius: 10px;
    border: 1.5px solid #e5e7eb; font-size: 14px; background: #f9fafb;
}
.drawer-actions { display: flex; gap: 8px; margin-top: 16px; }
.drawer-actions .btn { flex: 1; border-radius: 12px; padding: 10px; font-weight: 600; }
</style>

<div class="swipe-container" id="swipeApp">

    <!-- Top bar -->
    <div class="swipe-topbar">
        <a href="<?= site_url('vendedor') ?>" class="back-btn" title="Voltar">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span style="font-weight:700;font-size:15px;">Meus Clientes</span>
        <span class="counter" id="counter">—</span>
    </div>

    <!-- Search & quick filters -->
    <div class="filter-bar">
        <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Buscar por CNPJ ou razão social...">
        </div>
        <div class="filter-chips">
            <button class="filter-chip active" data-filter="all">Todos</button>
            <?php foreach ($categorias as $cat): ?>
                <button class="filter-chip" data-filter="categoria" data-value="<?= esc($cat) ?>"><?= esc($cat) ?></button>
            <?php endforeach; ?>
            <button class="filter-chip" data-filter="more" id="btnFilters">
                <i class="bi bi-funnel"></i> Mais
            </button>
        </div>
    </div>

    <!-- Card deck -->
    <div class="card-deck" id="cardDeck">
        <div class="loading-overlay" id="loadingState">
            <div class="loading-spinner"></div>
            <span>Carregando carteira...</span>
        </div>
    </div>

    <!-- Filter drawer -->
    <div class="filter-drawer-backdrop" id="drawerBackdrop"></div>
    <div class="filter-drawer" id="filterDrawer">
        <div class="drawer-header">
            <h6><i class="bi bi-funnel"></i> Filtros Avançados</h6>
            <button class="drawer-close" id="drawerClose"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="drawer-section">
            <label>Categoria</label>
            <select id="filterCategoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="drawer-section">
            <label>Segmento de Mercado</label>
            <select id="filterSegmento">
                <option value="">Todos</option>
                <?php foreach ($segmentos as $seg): ?>
                    <option value="<?= esc($seg) ?>"><?= esc($seg) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="drawer-section">
            <label>Ciclo de Vida</label>
            <select id="filterCiclo">
                <option value="">Todos</option>
                <?php foreach ($ciclos as $ciclo): ?>
                    <option value="<?= esc($ciclo) ?>"><?= esc($ciclo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="drawer-actions">
            <button class="btn btn-outline-secondary" id="filterClear">Limpar</button>
            <button class="btn btn-primary" id="filterApply">Aplicar</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    'use strict';

    const API_URL = '<?= site_url("vendedor/clientes/api") ?>';

    const CAT_COLORS = {
        'BRONZE':   '#cd7f32', 'OURO':     '#b8860b', 'PRATA':    '#8a8a8a',
        'DIAMANTE': '#185abc', 'PLATINUM': '#6b21a8', 'INFINITE': '#1e293b',
        'CLUBE':    '#047857'
    };

    let allClients = [];
    let currentIndex = 0;
    let touchStartX = 0;
    let touchDeltaX = 0;

    // ── Load clients ──
    async function loadClients(params = {}) {
        const deck = document.getElementById('cardDeck');
        deck.innerHTML = '<div class="loading-overlay"><div class="loading-spinner"></div><span>Carregando...</span></div>';

        const qs = new URLSearchParams(params).toString();
        const url = qs ? `${API_URL}?${qs}` : API_URL;

        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            allClients = data.clientes || [];
            currentIndex = 0;
            renderCards();
        } catch (e) {
            deck.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><p>Erro ao carregar clientes</p></div>';
        }
    }

    // ── Render cards ──
    function renderCards() {
        const deck = document.getElementById('cardDeck');
        const counter = document.getElementById('counter');

        if (allClients.length === 0) {
            deck.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>Nenhum cliente encontrado</p><small>Tente ajustar os filtros</small></div>';
            counter.textContent = '0';
            return;
        }

        counter.textContent = `${allClients.length} clientes`;

        // Render visible cards (current + next 2 for perf)
        const visibleCount = Math.min(allClients.length, 20);
        let html = '';

        for (let i = 0; i < visibleCount; i++) {
            html += buildCardHtml(allClients[i], i);
        }

        if (allClients.length > 20) {
            html += `<div class="swipe-hint">Exibindo 20 de ${allClients.length} · Use filtros para refinar</div>`;
        }

        deck.innerHTML = html;

        // Bind touch events
        deck.querySelectorAll('.client-card').forEach(bindSwipe);

        // Bind mask reveals
        deck.querySelectorAll('.masked').forEach(el => {
            el.addEventListener('click', function(e) {
                e.stopPropagation();
                if (this.classList.contains('revealed')) {
                    this.textContent = '●●●●●●';
                    this.classList.remove('revealed');
                } else {
                    this.textContent = this.dataset.real;
                    this.classList.add('revealed');
                }
            });
        });
    }

    // ── Build card HTML ──
    function buildCardHtml(c, idx) {
        const cat = (c.categoria || '').toUpperCase();
        const catColor = CAT_COLORS[cat] || '#64748b';
        const cnpjFormatted = formatCnpj(c.cnpj);
        const canais = c.canais_vendas ? c.canais_vendas.split(',').map(ch => ch.trim()).filter(Boolean) : [];
        const isProsp = (c.prospeccao || '').toUpperCase() === 'SIM';

        return `
        <div class="client-card" data-index="${idx}" data-cnpj="${c.cnpj}">
            <div class="card-banner" style="background: linear-gradient(135deg, ${catColor}, ${catColor}dd);">
                <span class="cat-name">${escHtml(c.categoria || 'Sem categoria')}</span>
                <span class="cat-badge">${escHtml(c.ciclo_de_vida || '')}</span>
            </div>
            <div class="card-body-custom">
                <div class="client-name">${escHtml(c.razao_social || '—')}</div>
                <div class="client-cnpj">${cnpjFormatted}</div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Segmento</span>
                        <span class="info-value">${escHtml(c.segmento_mercado || '—')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CNAE</span>
                        <span class="info-value">${escHtml(c.cnae || '—')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nat. Jurídica</span>
                        <span class="info-value">${escHtml(c.nat_juridica || '—')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Capital Social</span>
                        <span class="info-value masked" data-real="${escHtml(c.capital_social || '—')}">●●●●●●</span>
                    </div>
                </div>

                <div class="tag-row">
                    <span class="tag ciclo">${escHtml(c.ciclo_de_vida || '—')}</span>
                    ${isProsp ? '<span class="tag prospeccao-sim">Prospecção</span>' : '<span class="tag prospeccao-nao">Sem prospecção</span>'}
                    ${canais.map(ch => `<span class="tag">${escHtml(ch)}</span>`).join('')}
                </div>

                ${(c.cnaes_detalhados && c.cnaes_detalhados.length > 0)
                    ? `<div style="margin-top:10px;padding:8px 10px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
                            <span><i class="bi bi-briefcase text-primary me-1"></i> CNAEs & Atividades (${c.cnaes_detalhados.length})</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:5px;max-height:140px;overflow-y:auto;padding-right:2px;">
                            ${c.cnaes_detalhados.map(item => `
                                <div style="font-size:11px;line-height:1.3;color:#334155;background:#fff;padding:6px 8px;border-radius:6px;border:1px solid #f1f5f9;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                                        <span style="font-weight:700;font-family:monospace;color:#1e40af;"># ${escHtml(item.codigo)}</span>
                                        <span class="badge ${item.tipo === 'Principal' ? 'bg-primary' : 'bg-secondary'}" style="font-size:8px;padding:2px 5px;border-radius:4px;">${escHtml(item.tipo)}</span>
                                    </div>
                                    <div style="color:#475569;font-weight:500;">${escHtml(item.descricao)}</div>
                                </div>
                            `).join('')}
                        </div>
                       </div>`
                    : (c.cnae_desc ? `<div style="font-size:11px;color:#94a3b8;margin-top:4px;line-height:1.3"><i class="bi bi-info-circle"></i> ${escHtml(c.cnae_desc)}</div>` : '')
                }
            </div>
            <div class="card-actions">
                <a href="<?= site_url('vendedor/cliente/') ?>${c.cnpj}" class="action-btn">
                    <i class="bi bi-eye"></i> Detalhe
                </a>
                <a href="<?= site_url('vendedor/cliente/') ?>${c.cnpj}/nota" class="action-btn">
                    <i class="bi bi-journal-plus"></i> Nota
                </a>
                <button class="action-btn" onclick="navigator.share?.({title: '${escHtml(c.razao_social)}', text: 'CNPJ: ${cnpjFormatted}'}).catch(()=>{})">
                    <i class="bi bi-share"></i> Enviar
                </button>
            </div>
        </div>`;
    }

    // ── Swipe handling ──
    function bindSwipe(card) {
        card.addEventListener('touchstart', e => {
            touchStartX = e.touches[0].clientX;
            card.classList.add('swiping');
        }, { passive: true });

        card.addEventListener('touchmove', e => {
            touchDeltaX = e.touches[0].clientX - touchStartX;
            card.style.transform = `translateX(${touchDeltaX}px) rotate(${touchDeltaX * 0.03}deg)`;
            card.style.opacity = Math.max(0.3, 1 - Math.abs(touchDeltaX) / 400);
        }, { passive: true });

        card.addEventListener('touchend', () => {
            card.classList.remove('swiping');
            if (Math.abs(touchDeltaX) > 120) {
                card.classList.add(touchDeltaX > 0 ? 'swipe-right' : 'swipe-left');
                setTimeout(() => card.remove(), 300);
            } else {
                card.style.transform = '';
                card.style.opacity = '';
            }
            touchDeltaX = 0;
        });
    }

    // ── Filters ──
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadClients(buildFilterParams());
        }, 400);
    });

    document.querySelectorAll('.filter-chip[data-filter="categoria"]').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            loadClients({ categoria: chip.dataset.value });
        });
    });

    document.querySelector('.filter-chip[data-filter="all"]').addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        document.querySelector('.filter-chip[data-filter="all"]').classList.add('active');
        loadClients({});
    });

    // Filter drawer
    const drawer = document.getElementById('filterDrawer');
    const backdrop = document.getElementById('drawerBackdrop');
    document.getElementById('btnFilters').addEventListener('click', () => {
        drawer.classList.add('open');
        backdrop.classList.add('open');
    });
    document.getElementById('drawerClose').addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    function closeDrawer() {
        drawer.classList.remove('open');
        backdrop.classList.remove('open');
    }

    document.getElementById('filterApply').addEventListener('click', () => {
        closeDrawer();
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        loadClients(buildFilterParams());
    });

    document.getElementById('filterClear').addEventListener('click', () => {
        document.getElementById('filterCategoria').value = '';
        document.getElementById('filterSegmento').value = '';
        document.getElementById('filterCiclo').value = '';
        searchInput.value = '';
        closeDrawer();
        document.querySelector('.filter-chip[data-filter="all"]').classList.add('active');
        loadClients({});
    });

    function buildFilterParams() {
        const params = {};
        const cat = document.getElementById('filterCategoria').value;
        const seg = document.getElementById('filterSegmento').value;
        const ciclo = document.getElementById('filterCiclo').value;
        const busca = searchInput.value.trim();
        if (cat) params.categoria = cat;
        if (seg) params.segmento = seg;
        if (ciclo) params.ciclo = ciclo;
        if (busca) params.busca = busca;
        return params;
    }

    // ── Helpers ──
    function formatCnpj(cnpj) {
        if (!cnpj || cnpj.length !== 14) return cnpj || '—';
        return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // ── Init ──
    loadClients();
})();
</script>
<?= $this->endSection() ?>
