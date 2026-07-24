<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --neutral-light: #f8fafc;
    --border-color: #e2e8f0;
}

.search-container {
    max-width: 480px;
    margin: 0 auto;
    background: var(--neutral-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding-bottom: 60px;
}

.search-header {
    background: #fff;
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.search-header h1 {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 12px;
}

.search-box {
    position: relative;
    display: flex;
    gap: 8px;
}

.search-box input {
    flex: 1;
    padding: 10px 14px 10px 38px;
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    font-size: 13px;
    outline: none;
    transition: all 0.2s;
}

.search-box input:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.search-box i.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 14px;
}

.btn-search-go {
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

.btn-search-go:hover {
    background: var(--primary-light);
}

.results-section {
    padding: 16px;
    flex: 1;
}

.result-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid var(--border-color);
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s;
}

.result-card h3 {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin-top: 0;
    margin-bottom: 4px;
    line-height: 1.3;
}

.result-cnpj {
    font-size: 11px;
    color: #64748b;
    font-family: monospace;
    font-weight: 600;
}

.result-address {
    font-size: 12px;
    color: #475569;
    margin-top: 8px;
    line-height: 1.4;
}

.action-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.action-bar button {
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.status-badge-inline {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 4px;
    margin-top: 6px;
    display: inline-block;
}

.social-suggestions {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 11px;
}

/* Toast Notification */
.spiv-toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #fff;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    white-space: nowrap;
}
.spiv-toast.show {
    opacity: 1;
}
</style>

<style>
/* ── Tabs ── */
.prospect-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 0;
}
.prospect-tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 10px 8px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.prospect-tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
/* ── Ranking Cards ── */
.rank-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid var(--border-color);
    padding: 14px 14px 14px 14px;
    margin-bottom: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    display: flex;
    gap: 12px;
    align-items: flex-start;
    transition: transform 0.15s;
}
.rank-card:active { transform: scale(0.98); }
.rank-position {
    min-width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    flex-shrink: 0;
}
.rank-pos-gold   { background: #fef9c3; color: #854d0e; border: 2px solid #fde047; }
.rank-pos-silver { background: #f1f5f9; color: #475569; border: 2px solid #cbd5e1; }
.rank-pos-bronze { background: #fff7ed; color: #9a3412; border: 2px solid #fed7aa; }
.rank-pos-normal { background: #f8fafc; color: #64748b; border: 1.5px solid #e2e8f0; }
.rank-body { flex: 1; min-width: 0; }
.rank-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.rank-meta {
    font-size: 10px;
    color: #64748b;
    font-family: monospace;
    margin-bottom: 6px;
}
.score-bar-wrap {
    background: #f1f5f9;
    border-radius: 99px;
    height: 6px;
    overflow: hidden;
    margin-bottom: 4px;
}
.score-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
}
.score-bar-high   { background: linear-gradient(90deg, #22c55e, #86efac); }
.score-bar-medium { background: linear-gradient(90deg, #eab308, #fde047); }
.score-bar-low    { background: linear-gradient(90deg, #94a3b8, #cbd5e1); }
.rank-breakdown {
    font-size: 9px;
    color: #94a3b8;
}

/* ── Selo de Ranking (Badge Interativo com Hover & Popover) ── */
.selo-ranking {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    user-select: none;
    line-height: 1;
}
.selo-ranking:hover {
    transform: translateY(-1px) scale(1.04);
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
}
.selo-ranking-excelente {
    background: linear-gradient(135deg, #fef08a 0%, #facc15 100%);
    color: #713f12;
    border: 1.5px solid #eab308;
}
.selo-ranking-alto {
    background: linear-gradient(135deg, #dcfce7 0%, #4ade80 100%);
    color: #14532d;
    border: 1.5px solid #22c55e;
}
.selo-ranking-medio {
    background: linear-gradient(135deg, #e0f2fe 0%, #38bdf8 100%);
    color: #0c4a6e;
    border: 1.5px solid #0284c7;
}
.selo-ranking-baixo {
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid #cbd5e1;
}

/* ── Tooltip de Score Expandido ── */
.score-info-btn {
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    color: #94a3b8;
    cursor: pointer;
    vertical-align: middle;
    margin-left: 4px;
    flex-shrink: 0;
    transition: all 0.2s;
}
.score-info-btn:hover,
.score-info-btn.open { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }

.score-tooltip-panel {
    display: none;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    margin-top: 8px;
    animation: fadeSlideDown 0.18s ease;
}
.score-tooltip-panel.open { display: block; }
@keyframes fadeSlideDown {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.score-factor-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 0;
    border-bottom: 1px solid #f1f5f9;
}
.score-factor-row:last-child { border-bottom: none; }
.score-factor-icon {
    font-size: 14px;
    flex-shrink: 0;
    width: 22px;
    text-align: center;
}
.score-factor-body { flex: 1; min-width: 0; }
.score-factor-label {
    font-size: 10px;
    font-weight: 700;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.score-factor-desc {
    font-size: 9px;
    color: #64748b;
    line-height: 1.3;
    margin-top: 1px;
}
.score-factor-mini-bar {
    width: 36px;
    height: 4px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
    flex-shrink: 0;
}
.score-factor-mini-fill { height: 100%; border-radius: 99px; }
.score-factor-pts {
    font-size: 11px;
    font-weight: 800;
    color: #1e3a8a;
    flex-shrink: 0;
    min-width: 26px;
    text-align: right;
}
.score-total-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 6px;
    margin-top: 4px;
    border-top: 1.5px solid #e2e8f0;
}
.score-total-label { font-size: 10px; font-weight: 700; color: #334155; }
.score-total-pts {
    font-size: 16px;
    font-weight: 800;
    color: #1e3a8a;
}
.score-total-pts small { font-size: 10px; font-weight: 500; color: #64748b; }
.rank-action-btn {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    align-self: center;
    flex-shrink: 0;
    white-space: nowrap;
}
.rank-action-btn:hover { background: var(--primary-light); }
</style>

<div class="search-container">
    <div class="search-header">
        <h1>Prospecção de Clientes</h1>

        <!-- Abas -->
        <div class="prospect-tabs mb-2">
            <button class="prospect-tab-btn active" id="tabBusca" onclick="switchTab('busca')">
                <i class="bi bi-search"></i> Buscar
            </button>
            <button class="prospect-tab-btn" id="tabRanking" onclick="switchTab('ranking')">
                <i class="bi bi-graph-up-arrow"></i> Ranking de Potencial
            </button>
        </div>

        <!-- Painel Busca -->
        <div id="panelBusca">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="CNPJ, Nome ou Endereço..." autocomplete="off">
                <button class="btn-search-go" id="btnSearchGo">Pesquisar</button>
            </div>
            <div class="mt-2 d-flex align-items-center justify-content-between">
                <div class="text-muted small" style="font-size: 10px;">Mínimo 3 caracteres. Base RFB.</div>
                <div class="form-check form-switch small">
                    <input class="form-check-input" type="checkbox" role="switch" id="chkOnlyCorpEmail" style="cursor: pointer;">
                    <label class="form-check-label text-muted" for="chkOnlyCorpEmail" style="font-size: 10px; cursor: pointer; user-select: none;">Só e-mail corporativo</label>
                </div>
            </div>
        </div>

        <!-- Painel Ranking header -->
        <div id="panelRankingHeader" style="display:none;">
            <div class="d-flex align-items-center justify-content-between">
                <div style="font-size: 11px; color: #64748b;">
                    <i class="bi bi-funnel me-1"></i>Leads livres · maior potencial primeiro
                </div>
                <span id="rankTotalBadge" class="badge bg-primary" style="font-size: 10px;"></span>
            </div>
        </div>
    </div>

    <!-- Busca Results -->
    <div class="results-section" id="resultsSection" style="display:block;">
        <div class="text-center text-muted py-5" id="initialMsg">
            <i class="bi bi-search" style="font-size: 32px; color: #cbd5e1;"></i>
            <p class="mt-2 small">Digite um termo acima para iniciar a busca.</p>
        </div>
    </div>

    <!-- Ranking Results -->
    <div class="results-section" id="rankingSection" style="display:none;">
        <div class="text-center text-muted py-5" id="rankingLoading">
            <div class="spinner-border spinner-border-sm text-primary" style="width:24px;height:24px;"></div>
            <p class="mt-2 small">Carregando ranking...</p>
        </div>
    </div>
</div>

<!-- Toast Div -->
<div id="spivToast" class="spiv-toast">Mensagem do Sistema</div>

<script>
const searchInput = document.getElementById('searchInput');
const btnSearchGo = document.getElementById('btnSearchGo');
const resultsSection = document.getElementById('resultsSection');
const chkOnlyCorpEmail = document.getElementById('chkOnlyCorpEmail');

function escHtml(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

btnSearchGo.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') performSearch();
});

// Restaurar busca salva ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
    const savedQuery = sessionStorage.getItem('last_prospect_query');
    const savedResults = sessionStorage.getItem('last_prospect_results');
    const savedOnlyCorp = sessionStorage.getItem('last_prospect_only_corp');
    if (savedQuery) {
        searchInput.value = savedQuery;
    }
    if (savedOnlyCorp === '1' && chkOnlyCorpEmail) {
        chkOnlyCorpEmail.checked = true;
    }
    if (savedResults) {
        renderResults(JSON.parse(savedResults));
    }
});

async function performSearch() {
    const q = searchInput.value.trim();
    if (q.length < 3) {
        showToast('⚠️ Digite pelo menos 3 caracteres.');
        return;
    }

    btnSearchGo.disabled = true;
    btnSearchGo.textContent = 'Buscando...';
    resultsSection.innerHTML = '<div class="text-center py-5 text-muted">Buscando na base de dados...</div>';

    const onlyCorp = chkOnlyCorpEmail && chkOnlyCorpEmail.checked ? '1' : '0';

    try {
        const res = await fetch('<?= site_url('vendedor/prospectar/pesquisa/buscar') ?>?q=' + encodeURIComponent(q) + '&only_corp_email=' + onlyCorp);
        const data = await res.json();

        if (data.success) {
            // Salvar no cache de sessão para quando o usuário voltar
            sessionStorage.setItem('last_prospect_query', q);
            sessionStorage.setItem('last_prospect_only_corp', onlyCorp);
            sessionStorage.setItem('last_prospect_results', JSON.stringify(data.resultados));
            renderResults(data.resultados);
        } else {
            resultsSection.innerHTML = '<div class="text-center py-5 text-danger">Erro ao realizar busca.</div>';
        }
    } catch(e) {
        resultsSection.innerHTML = '<div class="text-center py-5 text-danger">Erro de comunicação com o servidor.</div>';
    } finally {
        btnSearchGo.disabled = false;
        btnSearchGo.textContent = 'Pesquisar';
    }
}

// ── Badge de Score Preditivo ──────────────────────────────────
const SCORE_STYLE = document.createElement('style');
SCORE_STYLE.textContent = `
.score-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 7px; border-radius: 99px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.3px;
}
.score-badge.high   { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.score-badge.medium { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
.score-badge.low    { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
`;
document.head.appendChild(SCORE_STYLE);

// A função buildScoreTooltipHtml é compartilhada entre Busca e Ranking
function renderRankingSelo(score, isHoverable = true) {
    const s = parseFloat(score) || 0;
    let cls = 'selo-ranking-baixo';
    let icon = '·';
    let label = 'Baixo';
    
    if (s >= 80) {
        cls = 'selo-ranking-excelente';
        icon = '🏆';
        label = 'Excelente';
    } else if (s >= 60) {
        cls = 'selo-ranking-alto';
        icon = '🔥';
        label = 'Alto Potencial';
    } else if (s >= 40) {
        cls = 'selo-ranking-medio';
        icon = '⚡';
        label = 'Médio';
    }

    return `
        <span class="selo-ranking ${cls}" ${isHoverable ? 'title="Passe o mouse ou clique para ver a explicação do score"' : ''}>
            <span>${icon}</span>
            <span>Score ${s.toFixed(0)}</span>
            <small class="opacity-75" style="font-size:9.5px;font-weight:700;">(${label})</small>
        </span>
    `;
}

function buildScoreTooltipHtml(item, score) {
    // Se for o objeto do novo modelo de 4 pilares
    if (item && item.score_cnae !== undefined) {
        const sCnae  = parseFloat(item.score_cnae || 0);
        const sIdade = parseFloat(item.score_idade || 0);
        const fSetor = parseFloat(item.fator_setor || 1.0);
        const sCap   = parseFloat(item.score_capital || 0);
        const sEmail = parseFloat(item.score_email || 0);
        const sFinal = parseFloat(score || item.logistics_score || item.score_final || 0);

        const pCnae  = (sCnae * 0.30).toFixed(1);
        const pIdade = (sIdade * fSetor * 0.30).toFixed(1);
        const pCap   = (sCap * 0.25).toFixed(1);
        const pEmail = (sEmail * 0.15).toFixed(1);

        const catName = (item.postal_categoria || 'servico').toUpperCase();
        const idadeTxt = item.idade_anos !== undefined ? `${item.idade_anos} anos de atividade` : 'Recente';
        
        const capVal = item.capital_social ? parseFloat(item.capital_social) : 0;
        const medVal = item.mediana_setor ? parseFloat(item.mediana_setor) : 5000;
        const capFmt = capVal.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL', maximumFractionDigits: 0});
        const medFmt = medVal.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL', maximumFractionDigits: 0});

        let emailDesc = 'Sem e-mail cadastrado (0 pts)';
        if (sEmail >= 100) {
            emailDesc = 'Domínio corporativo próprio (Alta maturidade digital)';
        } else if (sEmail >= 40) {
            emailDesc = 'Webmail genérico (Gmail/Hotmail)';
        }

        return `
            <div style="padding: 2px 0;">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                    <span style="font-size:11px;font-weight:700;color:#1e3a8a;">
                        <i class="bi bi-diagram-3-fill me-1"></i> Composição Ponderada do Score (4 Pilares)
                    </span>
                    <span class="badge bg-primary" style="font-size:10px;">${sFinal.toFixed(1)} pts</span>
                </div>
                
                <div class="score-factor-row">
                    <div class="score-factor-icon">🏭</div>
                    <div class="score-factor-body">
                        <div class="score-factor-label">1. Atração CNAE Postal (Peso 30%)</div>
                        <div class="score-factor-desc">Setor ${catName} · Nota Bruta: ${sCnae}/100</div>
                    </div>
                    <div class="score-factor-mini-bar">
                        <div class="score-factor-mini-fill" style="width:${Math.min(100, (pCnae/30)*100)}%;background:#3b82f6;"></div>
                    </div>
                    <div class="score-factor-pts">${pCnae} <small style="font-size:9px;color:#94a3b8;">/30</small></div>
                </div>

                <div class="score-factor-row">
                    <div class="score-factor-icon">⏳</div>
                    <div class="score-factor-body">
                        <div class="score-factor-label">2. Idade x Mortalidade Setorial (Peso 30%)</div>
                        <div class="score-factor-desc">${idadeTxt} · Fator Mortalidade ${fSetor}x</div>
                    </div>
                    <div class="score-factor-mini-bar">
                        <div class="score-factor-mini-fill" style="width:${Math.min(100, (pIdade/36)*100)}%;background:#10b981;"></div>
                    </div>
                    <div class="score-factor-pts">${pIdade} <small style="font-size:9px;color:#94a3b8;">/36</small></div>
                </div>

                <div class="score-factor-row">
                    <div class="score-factor-icon">💼</div>
                    <div class="score-factor-body">
                        <div class="score-factor-label">3. Adequação de Capital Social (Peso 25%)</div>
                        <div class="score-factor-desc">Capital ${capFmt} (Mediana Setor: ${medFmt})</div>
                    </div>
                    <div class="score-factor-mini-bar">
                        <div class="score-factor-mini-fill" style="width:${Math.min(100, (pCap/25)*100)}%;background:#f59e0b;"></div>
                    </div>
                    <div class="score-factor-pts">${pCap} <small style="font-size:9px;color:#94a3b8;">/25</small></div>
                </div>

                <div class="score-factor-row">
                    <div class="score-factor-icon">✉️</div>
                    <div class="score-factor-body">
                        <div class="score-factor-label">4. Maturidade Digital (Peso 15%)</div>
                        <div class="score-factor-desc">${emailDesc}</div>
                    </div>
                    <div class="score-factor-mini-bar">
                        <div class="score-factor-mini-fill" style="width:${Math.min(100, (pEmail/15)*100)}%;background:#8b5cf6;"></div>
                    </div>
                    <div class="score-factor-pts">${pEmail} <small style="font-size:9px;color:#94a3b8;">/15</small></div>
                </div>

                <div class="score-total-line mt-2 pt-2 border-top">
                    <span class="score-total-label">Score Final Combinado</span>
                    <span class="score-total-pts text-primary">${sFinal.toFixed(1)} <small style="font-size:10px;font-weight:600;">pts</small></span>
                </div>
            </div>
        `;
    }

    // Fallback para modelo antigo se houver
    const bd = item || {};
    const maxes = { cnae: 40, capital: 20, email: 15, nome_fantasia: 10, localizacao: 15 };
    const factors = [
        { key: 'cnae', icon: '🏭', label: 'Ramo de Atividade (CNAE)', color: '#3b82f6', desc: () => (bd.cnae||0) >= 35 ? 'CNAE fortemente relacionado a e-commerce/logística.' : 'CNAE geral.' },
        { key: 'capital', icon: '💰', label: 'Porte da Empresa (Capital Social)', color: '#22c55e', desc: () => (bd.capital||0) >= 20 ? 'Capital social alto.' : 'Capital social médio.' },
        { key: 'email', icon: '✉️', label: 'Maturidade Digital (E-mail)', color: '#06b6d4', desc: () => (bd.email||0) > 0 ? 'Possui e-mail corporativo.' : 'Sem e-mail.' },
    ];
    let rows = '';
    factors.forEach(f => {
        const pts = bd[f.key] || 0;
        const pct = Math.round((pts / (maxes[f.key] || 1)) * 100);
        rows += `
            <div class="score-factor-row">
                <div class="score-factor-icon">${f.icon}</div>
                <div class="score-factor-body">
                    <div class="score-factor-label">${f.label}</div>
                    <div class="score-factor-desc">${f.desc()}</div>
                </div>
                <div class="score-factor-mini-bar">
                    <div class="score-factor-mini-fill" style="width:${pct}%;background:${f.color};"></div>
                </div>
                <div class="score-factor-pts">${pts}</div>
            </div>`;
    });
    rows += `
        <div class="score-total-line">
            <span class="score-total-label">Pontuação Total</span>
            <span class="score-total-pts">${score} <small>/100</small></span>
        </div>`;
    return rows;
}

function renderScoreBadge(score, hasTooltip) {
    return renderRankingSelo(score, hasTooltip);
}

function renderRaBadge(raStatus, raTotal) {
    if (!raStatus) return '';
    if (raStatus === 'encontrado') {
        return `<span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:8px;padding:2px 5px;border-radius:99px;" title="Reclamações logísticas encontradas"><i class="bi bi-radar"></i> ${raTotal} reclam.</span>`;
    }
    if (raStatus === 'nao_encontrado') {
        return `<span class="badge" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:8px;padding:2px 5px;border-radius:99px;" title="Nenhuma reclamação encontrada"><i class="bi bi-check-circle"></i> RA: limpo</span>`;
    }
    return '';
}

function renderResults(list) {

    if (!list || list.length === 0) {
        resultsSection.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-x-circle" style="font-size: 32px; color: #cbd5e1;"></i>
                <p class="mt-2 small">Nenhum estabelecimento encontrado na base local.</p>
            </div>
        `;
        return;
    }

    resultsSection.innerHTML = '';
    list.forEach(item => {
        const card = document.createElement('div');
        card.className = 'result-card';
        card.dataset.cnpj = item.cnpj;

        const cleanCnpj = item.cnpj;
        const formattedCnpj = cleanCnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");

        // Determinar status do localizador
        const isGeocoded = item.loc_lat && item.loc_lng;
        const isVerified = item.rfb_situacao_cadastral;

        let statusAlertHtml = '';
        if (isVerified) {
            const isAtivo = item.rfb_situacao_cadastral.toUpperCase() === 'ATIVA';
            statusAlertHtml = isAtivo 
                ? `<div class="status-badge-inline" style="background-color: #dcfce7; color: #166534;"><i class="bi bi-check-circle-fill"></i> Ativa (RFB)</div>`
                : `<div class="status-badge-inline" style="background-color: #fee2e2; color: #991b1b;"><i class="bi bi-exclamation-triangle-fill"></i> Inativa (${item.rfb_situacao_cadastral})</div>`;
        }

        let addressMarkerHtml = '';
        if (isGeocoded) {
            addressMarkerHtml = `<span class="badge bg-success text-white ms-2" style="font-size: 9px;"><i class="bi bi-geo-alt-fill"></i> Localizado</span>`;
        }

        const isEncarteirado = item.encarteirado === true;
        let carteiraStatusHtml = '';
        if (isEncarteirado) {
            const vNome = item.vendedor_nome ? `: ${item.vendedor_nome}` : '';
            carteiraStatusHtml = `<span class="badge bg-secondary text-white" style="font-size: 9px;"><i class="bi bi-lock-fill"></i> Em Carteira${vNome}</span>`;
            card.style.opacity = '0.88';
            card.style.backgroundColor = '#fafafa';
        } else {
            carteiraStatusHtml = `<span class="badge bg-success text-white" style="font-size: 9px;"><i class="bi bi-unlock-fill"></i> Livre para Prospecção</span>`;
        }

        const bd         = item.score_breakdown ? (typeof item.score_breakdown === 'string' ? JSON.parse(item.score_breakdown) : item.score_breakdown) : null;
        const tooltipId  = `stt_${cleanCnpj}`;

        const cnaesSearchHtml = (item.cnaes_detalhados && item.cnaes_detalhados.length > 0)
            ? `<div style="margin-top:8px;padding:6px 10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:11px;">
                <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">
                    <i class="bi bi-briefcase text-primary me-1"></i> CNAEs & Atividades (${item.cnaes_detalhados.length})
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;max-height:110px;overflow-y:auto;">
                    ${item.cnaes_detalhados.map(cnae => `
                        <div style="line-height:1.3;color:#334155;background:#fff;padding:4px 6px;border-radius:4px;border:1px solid #f1f5f9;">
                            <span class="badge ${cnae.tipo === 'Principal' ? 'bg-primary' : 'bg-secondary'}" style="font-size:8px;padding:1px 4px;"># ${escHtml(cnae.codigo)} · ${escHtml(cnae.tipo)}</span>
                            <span style="font-weight:500;">${escHtml(cnae.descricao)}</span>
                        </div>
                    `).join('')}
                </div>
               </div>`
            : '';

        card.innerHTML = `
            <h3>${item.nome_fantasia || item.razao_social}</h3>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                <div class="result-cnpj">${formattedCnpj}</div>
                ${carteiraStatusHtml}
                ${renderRankingSelo(item.logistics_score, true)}
                <span class="ra-badge-slot">${renderRaBadge(item.ra_status, item.ra_total)}</span>
            </div>
            <div class="score-tooltip-panel" id="${tooltipId}">${buildScoreTooltipHtml(bd || item, parseInt(item.logistics_score)||0)}</div>
            <div class="result-address">
                <i class="bi bi-geo-alt text-muted"></i> ${item.endereco_completo}
                <span class="geo-status-indicator">${addressMarkerHtml}</span>
            </div>
            
            <div class="rfb-status-container">${statusAlertHtml}</div>
            ${cnaesSearchHtml}

            <div class="action-bar">
                <button class="btn btn-xs btn-outline-secondary btn-verificar-cnpj" data-cnpj="${cleanCnpj}">
                    <i class="bi bi-shield-check"></i> Verificar Status
                </button>
                <button class="btn btn-xs btn-outline-secondary btn-geolocalizar" data-cnpj="${cleanCnpj}" ${isGeocoded ? 'style="display:none;"' : ''}>
                    <i class="bi bi-geo-alt"></i> Mapear Lat/Lng
                </button>
                <button class="btn btn-xs btn-outline-secondary btn-buscar-redes" data-cnpj="${cleanCnpj}">
                    <i class="bi bi-share"></i> Buscar Redes
                </button>
                <button class="btn btn-xs btn-outline-danger btn-ra-scanner" data-cnpj="${cleanCnpj}">
                    <i class="bi bi-radar"></i> Reclame Aqui
                </button>
                <button class="btn btn-xs btn-primary btn-carteira-prospect" data-cnpj="${cleanCnpj}">
                    <i class="bi bi-briefcase"></i> Detalhes
                </button>
            </div>

            <div class="social-box-container" style="display: none;"></div>
            <div class="ra-box-container" style="display: none; margin-top: 10px; background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 8px; padding: 8px;"></div>
        `;

        // Bind mouseover e click no Selo de Ranking
        const seloBtn = card.querySelector('.selo-ranking');
        const panel   = card.querySelector('.score-tooltip-panel');
        if (seloBtn && panel) {
            seloBtn.addEventListener('mouseenter', () => panel.classList.add('open'));
            seloBtn.addEventListener('mouseleave', (e) => {
                if (!panel.contains(e.relatedTarget)) panel.classList.remove('open');
            });
            panel.addEventListener('mouseleave', (e) => {
                if (!seloBtn.contains(e.relatedTarget)) panel.classList.remove('open');
            });
            seloBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                panel.classList.toggle('open');
            });
        }

        // Pré-popula o ra-box-container se já há um scan salvo no banco
        if (item.ra_status) {
            const raBox = card.querySelector('.ra-box-container');
            const dataStr = item.ra_pesquisado_em
                ? ` <span style="font-size:8px;color:#94a3b8;">· ${new Date(item.ra_pesquisado_em).toLocaleDateString('pt-BR')}</span>`
                : '';
            if (item.ra_status === 'encontrado') {
                raBox.style.display = 'block';
                raBox.innerHTML = `<div style="font-size:10px;color:#9f1239;"><i class="bi bi-radar"></i> <strong>${item.ra_total}</strong> reclamação(ões) logística(s) encontrada(s) no último scan.${dataStr} <span style="color:#64748b;">Clique em "Reclame Aqui" para ver os detalhes.</span></div>`;
            } else if (item.ra_status === 'nao_encontrado') {
                raBox.style.display = 'block';
                raBox.style.background = '#f0fdf4';
                raBox.style.borderColor = '#bbf7d0';
                raBox.innerHTML = `<div style="font-size:10px;color:#166534;"><i class="bi bi-check-circle"></i> Nenhuma reclamação logística encontrada no último scan.${dataStr}</div>`;
            }
        }

        resultsSection.appendChild(card);
    });

    bindCardActions();
}

function bindCardActions() {
    // Verificar Status
    resultsSection.querySelectorAll('.btn-verificar-cnpj').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cnpj = btn.dataset.cnpj;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span>...';
            try {
                const res = await fetch('<?= site_url('vendedor/cnpj/verificar/') ?>' + cnpj);
                const data = await res.json();
                if (data.success) {
                    showToast('✅ Status do CNPJ atualizado!');
                    const card = btn.closest('.result-card');
                    const container = card.querySelector('.rfb-status-container');
                    if (data.ativo) {
                        container.innerHTML = `<div class="status-badge-inline" style="background-color: #dcfce7; color: #166534;"><i class="bi bi-check-circle-fill"></i> Ativa (RFB)</div>`;
                    } else {
                        container.innerHTML = `<div class="status-badge-inline" style="background-color: #fee2e2; color: #991b1b;"><i class="bi bi-exclamation-triangle-fill"></i> Inativa (${data.situacao_cadastral})</div>`;
                    }
                } else {
                    showToast('❌ ' + data.error);
                }
            } catch(e) {
                showToast('❌ Erro na requisição.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-shield-check"></i> Verificar Status';
            }
        });
    });

    // Geolocalizar
    resultsSection.querySelectorAll('.btn-geolocalizar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cnpj = btn.dataset.cnpj;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span>...';
            try {
                const res = await fetch('<?= site_url('vendedor/cnpj/geolocalizar/') ?>' + cnpj, { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    showToast('✅ Coordenadas mapeadas com sucesso!');
                    btn.style.display = 'none';
                    const card = btn.closest('.result-card');
                    const indicator = card.querySelector('.geo-status-indicator');
                    indicator.innerHTML = `<span class="badge bg-success text-white ms-2" style="font-size: 9px;"><i class="bi bi-geo-alt-fill"></i> Localizado</span>`;
                } else {
                    showToast('❌ ' + data.error);
                }
            } catch(e) {
                showToast('❌ Erro na requisição.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-alt"></i> Mapear Lat/Lng';
            }
        });
    });

    // Buscar Redes
    resultsSection.querySelectorAll('.btn-buscar-redes').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cnpj = btn.dataset.cnpj;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span>...';
            try {
                const res = await fetch('<?= site_url('vendedor/cnpj/redes-sociais/buscar/') ?>' + cnpj);
                const data = await res.json();
                if (data.success) {
                    showToast('🔍 Redes sociais pesquisadas!');
                    const card = btn.closest('.result-card');
                    const socialContainer = card.querySelector('.social-box-container');
                    socialContainer.style.display = 'block';

                    if (!data.redes || data.redes.length === 0) {
                        socialContainer.innerHTML = `<div class="social-suggestions text-muted">Nenhuma rede social encontrada.</div>`;
                    } else {
                        let html = '<div class="social-suggestions"><strong>Sugestões encontradas:</strong>';
                        const icons = { instagram: 'bi-instagram', linkedin: 'bi-linkedin', facebook: 'bi-facebook', website: 'bi-globe' };
                        data.redes.forEach(r => {
                            const iconClass = icons[r.network] || 'bi-globe';
                            html += `
                                <div class="d-flex align-items-center justify-content-between mt-1 py-1 border-bottom">
                                    <span><i class="bi ${iconClass} text-muted me-1"></i> <a href="${r.url}" target="_blank" class="text-decoration-none">${r.url.replace(/https?:\/\/(www\.)?/, '')}</a></span>
                                    <span class="badge bg-warning text-dark" style="font-size: 8px;">Sugestão</span>
                                </div>
                            `;
                        });
                        html += '</div>';
                        socialContainer.innerHTML = html;
                    }
                } else {
                    showToast('❌ ' + data.error);
                }
            } catch(e) {
                showToast('❌ Erro na requisição.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-share"></i> Buscar Redes';
            }
        });
    });

    // Reclame Aqui Scanner
    resultsSection.querySelectorAll('.btn-ra-scanner').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cnpj = btn.dataset.cnpj;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span>...';

            const card = btn.closest('.result-card');
            const raContainer = card.querySelector('.ra-box-container');
            raContainer.style.display = 'block';
            raContainer.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm text-danger" style="width:14px;height:14px;"></span><div class="small text-danger mt-1" style="font-size:10px;">Buscando reclamações logísticas...</div></div>';

            try {
                const res = await fetch('<?= site_url('vendedor/cliente/') ?>' + cnpj + '/reclame-aqui', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const data = await res.json();

                // ── Sem chave configurada → formulário inline ────────────────
                if (res.status === 402 || data.error_type === 'NO_API_KEY' || data.error_type === 'INVALID_KEY') {
                    const isInvalid = data.error_type === 'INVALID_KEY';
                    raContainer.innerHTML = `
                        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:12px 14px;">
                            <div style="font-size:11px;font-weight:700;color:#92400e;margin-bottom:6px;">
                                🔑 ${isInvalid ? 'Chave Serper inválida ou sem créditos' : 'Chave Serper não configurada'}
                            </div>
                            <p style="font-size:10px;color:#78350f;margin-bottom:8px;line-height:1.4;">
                                Este scanner usa a <strong>API Serper.dev</strong> para pesquisar reclamações logísticas no Reclame Aqui.
                                O plano gratuito tem <strong>2.500 buscas/mês</strong> sem cartão de crédito.<br>
                                <a href="https://serper.dev" target="_blank" style="color:#1d4ed8;font-weight:600;">👉 Criar conta gratuita em serper.dev</a>
                            </p>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <input type="text" class="ra-key-input-${cnpj}" placeholder="Cole sua API Key do Serper.dev"
                                    style="flex:1;padding:6px 10px;border:1.5px solid #d1d5db;border-radius:7px;font-size:10px;outline:none;" />
                                <button class="ra-key-save-${cnpj}"
                                    style="padding:6px 10px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:10px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    Salvar e Tentar
                                </button>
                            </div>
                            <div class="ra-key-msg-${cnpj}" style="font-size:10px;margin-top:4px;display:none;"></div>
                        </div>`;

                    raContainer.querySelector(`.ra-key-save-${cnpj}`).addEventListener('click', async () => {
                        const key = raContainer.querySelector(`.ra-key-input-${cnpj}`).value.trim();
                        const msg = raContainer.querySelector(`.ra-key-msg-${cnpj}`);
                        if (!key) { msg.style.display='block'; msg.style.color='#dc2626'; msg.textContent='Cole sua chave antes de salvar.'; return; }

                        const saveBtn = raContainer.querySelector(`.ra-key-save-${cnpj}`);
                        saveBtn.disabled = true; saveBtn.textContent = 'Salvando...';

                        try {
                            const body = new URLSearchParams({ serper_api_key: key });
                            const sr = await fetch('<?= site_url('vendedor/serper-key') ?>', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                                body, credentials: 'same-origin'
                            });
                            const sd = await sr.json();
                            if (sd.success) {
                                msg.style.color='#16a34a'; msg.style.display='block';
                                msg.textContent = '✅ Chave salva! Iniciando busca...';
                                // Re-trigger: simula clique no botão original
                                setTimeout(() => { btn.disabled = false; btn.click(); }, 800);
                            } else {
                                msg.style.color='#dc2626'; msg.style.display='block';
                                msg.textContent = '❌ ' + (sd.error || 'Erro ao salvar.');
                                saveBtn.disabled = false; saveBtn.textContent = 'Salvar e Tentar';
                            }
                        } catch(e) {
                            msg.style.color='#dc2626'; msg.style.display='block';
                            msg.textContent = '❌ Erro de rede.';
                            saveBtn.disabled = false; saveBtn.textContent = 'Salvar e Tentar';
                        }
                    });
                    return;
                }

                // ── CNPJ sem nome na base local ──────────────────────────────
                if (res.status === 404) {
                    raContainer.innerHTML = `<div style="font-size:10px;background:#f1f5f9;border-radius:8px;padding:8px 12px;color:#475569;text-align:center;">
                        ℹ️ <strong>CNPJ não encontrado na base local da Receita Federal.</strong><br>
                        <span style="font-size:9px;">A busca usa o Nome Fantasia/Razão Social cadastrado na RFB. Sem esse dado, não é possível pesquisar.</span>
                    </div>`;
                    return;
                }

                // ── Sucesso ──────────────────────────────────────────────────
                if (data.success && Array.isArray(data.resultados)) {
                    if (data.resultados.length === 0) {
                        raContainer.innerHTML = '<div style="background:#dcfce7;color:#166534;font-size:10px;border-radius:6px;padding:8px 12px;"><i class="bi bi-emoji-smile"></i> Nenhuma reclamação logística recente encontrada.</div>';
                        raContainer.style.background = '#f0fdf4';
                        raContainer.style.borderColor = '#bbf7d0';
                    } else {
                        raContainer.style.background = '#fdf2f8';
                        raContainer.style.borderColor = '#fbcfe8';
                        let html = `<div style="font-size:10px;font-weight:700;color:#9f1239;margin-bottom:6px;"><i class="bi bi-radar"></i> ${data.resultados.length} reclamação(ões) mapeada(s):</div><div style="max-height:150px;overflow-y:auto;padding-right:4px;">`;
                        data.resultados.forEach(item => {
                            html += `
                                <div style="background:#fff;border-radius:6px;margin-bottom:4px;border-left:3px solid #ef4444;padding:5px 8px;">
                                    <a href="${item.link}" target="_blank" style="font-size:11px;font-weight:700;color:#dc2626;text-decoration:none;display:block;line-height:1.2;">${item.title.replace(' - Reclame Aqui', '')}</a>
                                    <p style="font-size:9px;color:#64748b;margin:2px 0 0;line-height:1.3;">${item.snippet}</p>
                                </div>`;
                        });
                        html += '</div>';
                        raContainer.innerHTML = html;
                    }
                    showToast('🔍 Pesquisa Reclame Aqui concluída!');

                    // Atualiza o badge de RA no header do card
                    const badgeSlot = card.querySelector('.ra-badge-slot');
                    if (badgeSlot) {
                        badgeSlot.innerHTML = renderRaBadge(data.cache_status, data.cache_total);
                    }
                } else {
                    // Erro genérico com mensagem útil
                    raContainer.innerHTML = `<div style="background:#fee2e2;color:#991b1b;font-size:10px;border-radius:8px;padding:8px 12px;text-align:center;">❌ ${data.error || 'Erro ao buscar dados.'}</div>`;
                }
            } catch(e) {
                raContainer.innerHTML = `<div style="background:#fee2e2;color:#991b1b;font-size:10px;border-radius:8px;padding:8px 12px;text-align:center;">❌ Erro de rede: ${e.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-radar"></i> Reclame Aqui';
            }
        });
    });

    // Acessar Detalhes (Prospectar / Vínculo e Detalhes)
    resultsSection.querySelectorAll('.btn-carteira-prospect').forEach(btn => {
        btn.addEventListener('click', () => {
            const cnpj = btn.dataset.cnpj;
            // Redireciona diretamente para a tela de detalhes, onde o controlador criará o vínculo se não existir!
            location.href = '<?= site_url('vendedor/cliente/') ?>' + cnpj;
        });
    });
}

function showToast(msg) {
    const toast = document.getElementById('spivToast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2500);
}

// ── Controle de Abas ─────────────────────────────────────────
function switchTab(tab) {
    const isBusca = tab === 'busca';
    document.getElementById('tabBusca').classList.toggle('active', isBusca);
    document.getElementById('tabRanking').classList.toggle('active', !isBusca);
    document.getElementById('panelBusca').style.display         = isBusca ? '' : 'none';
    document.getElementById('panelRankingHeader').style.display = isBusca ? 'none' : '';
    document.getElementById('resultsSection').style.display     = isBusca ? '' : 'none';
    document.getElementById('rankingSection').style.display     = isBusca ? 'none' : '';
    if (!isBusca && !rankingLoaded) loadRanking(0);
}

// ── Ranking ───────────────────────────────────────────────────
let rankingOffset  = 0;
let rankingTotal   = 0;
let rankingLoaded  = false;
let rankingLoading = false;
const RANK_LIMIT   = 50;

async function loadRanking(offset) {
    if (rankingLoading) return;
    rankingLoading = true;

    const section = document.getElementById('rankingSection');
    if (offset === 0) {
        section.innerHTML = `<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm text-primary" style="width:24px;height:24px;"></div><p class="mt-2 small">Carregando ranking...</p></div>`;
    }

    try {
        const res  = await fetch(`<?= site_url('vendedor/prospectar/pesquisa/ranking') ?>?limit=${RANK_LIMIT}&offset=${offset}`);
        const data = await res.json();

        if (!data.success) { section.innerHTML = '<div class="text-center text-danger py-4">Erro ao carregar ranking.</div>'; return; }

        rankingTotal  = data.total;
        rankingOffset = offset + data.ranking.length;
        rankingLoaded = true;

        document.getElementById('rankTotalBadge').textContent = rankingTotal.toLocaleString('pt-BR') + ' leads livres';

        if (offset === 0) section.innerHTML = '';
        else document.getElementById('btnCarregarMais')?.remove();

        data.ranking.forEach((item, i) => {
            const pos   = offset + i + 1;
            const score = item.logistics_score;
            const cls   = score >= 60 ? 'high' : (score >= 30 ? 'medium' : 'low');
            const bar   = `score-bar-${cls}`;

            let posCls = 'rank-pos-normal';
            if      (pos === 1) posCls = 'rank-pos-gold';
            else if (pos === 2) posCls = 'rank-pos-silver';
            else if (pos === 3) posCls = 'rank-pos-bronze';

            const nome   = item.nome_fantasia || item.razao_social || 'Sem nome';
            const cnpj   = item.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            const cidade = [item.municipio_nome, item.uf].filter(Boolean).join(' - ');
            const email  = item.email ? `· ${item.email}` : '';

            // Reutiliza a função compartilhada
            const tooltipId = `tt_${item.cnpj}`;
            const seloHtml  = renderRankingSelo(score, true);

            const card = document.createElement('div');
            card.className = 'rank-card';
            card.innerHTML = `
                <div class="rank-position ${posCls}">${pos <= 3 ? ['🥇','🥈','🥉'][pos-1] : '#' + pos}</div>
                <div class="rank-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                        <div class="rank-name m-0" title="${nome}">${nome}</div>
                        <div>${seloHtml}</div>
                    </div>
                    <div class="rank-meta mb-1">${cnpj} ${cidade ? '· ' + cidade : ''}</div>
                    <div class="score-tooltip-panel" id="${tooltipId}">
                        ${buildScoreTooltipHtml(item, score)}
                    </div>
                </div>
                <button class="rank-action-btn" onclick="location.href='<?= site_url('vendedor/cliente/') ?>' + '${item.cnpj}'" title="Ver detalhes do lead">
                    <i class="bi bi-arrow-right"></i>
                </button>
            `;

            // Bind mouseover e click no Selo de Ranking
            const seloBtn = card.querySelector('.selo-ranking');
            const panel   = card.querySelector('.score-tooltip-panel');
            if (seloBtn && panel) {
                seloBtn.addEventListener('mouseenter', () => panel.classList.add('open'));
                seloBtn.addEventListener('mouseleave', (e) => {
                    if (!panel.contains(e.relatedTarget)) panel.classList.remove('open');
                });
                panel.addEventListener('mouseleave', (e) => {
                    if (!seloBtn.contains(e.relatedTarget)) panel.classList.remove('open');
                });
                seloBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    panel.classList.toggle('open');
                });
            }

            section.appendChild(card);

        });

        // Botão carregar mais
        if (rankingOffset < rankingTotal) {
            const btn = document.createElement('button');
            btn.id        = 'btnCarregarMais';
            btn.className = 'btn btn-outline-primary w-100 mt-2 mb-4';
            btn.style.fontSize = '12px';
            btn.textContent = `Carregar mais (${rankingOffset} de ${rankingTotal})`;
            btn.addEventListener('click', () => loadRanking(rankingOffset));
            section.appendChild(btn);
        } else if (rankingOffset > 0) {
            const end = document.createElement('div');
            end.className = 'text-center text-muted small py-3';
            end.textContent = `✅ Todos os ${rankingTotal} leads exibidos`;
            section.appendChild(end);
        }

    } catch(e) {
        section.innerHTML = '<div class="text-center text-danger py-4">Erro de comunicação.</div>';
    } finally {
        rankingLoading = false;
    }
}

</script>

<?= $this->endSection() ?>
