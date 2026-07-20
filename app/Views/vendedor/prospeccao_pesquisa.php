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

<div class="search-container">
    <div class="search-header">
        <h1>Prospecção de Clientes</h1>
        <div class="search-box">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Pesquise por CNPJ, Nome ou Endereço..." autocomplete="off">
            <button class="btn-search-go" id="btnSearchGo">Pesquisar</button>
        </div>
        <div class="mt-2 d-flex align-items-center justify-content-between">
            <div class="text-muted small" style="font-size: 10px;">
                Digite no mínimo 3 caracteres. Busca na base de São Paulo/RFB.
            </div>
            <div class="form-check form-switch small">
                <input class="form-check-input" type="checkbox" role="switch" id="chkOnlyCorpEmail" style="cursor: pointer;">
                <label class="form-check-label text-muted" for="chkOnlyCorpEmail" style="font-size: 10px; cursor: pointer; user-select: none;">Apenas e-mail corporativo</label>
            </div>
        </div>
    </div>

    <div class="results-section" id="resultsSection">
        <div class="text-center text-muted py-5" id="initialMsg">
            <i class="bi bi-search" style="font-size: 32px; color: #cbd5e1;"></i>
            <p class="mt-2 small">Digite um termo acima para iniciar a busca.</p>
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

        card.innerHTML = `
            <h3>${item.nome_fantasia || item.razao_social}</h3>
            <div class="result-cnpj">${formattedCnpj}</div>
            <div class="result-address">
                <i class="bi bi-geo-alt text-muted"></i> ${item.endereco_completo}
                <span class="geo-status-indicator">${addressMarkerHtml}</span>
            </div>
            
            <div class="rfb-status-container">${statusAlertHtml}</div>

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
                <button class="btn btn-xs btn-primary btn-carteira-prospect" data-cnpj="${cleanCnpj}">
                    <i class="bi bi-briefcase"></i> Detalhes
                </button>
            </div>

            <div class="social-box-container" style="display: none;"></div>
        `;
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
</script>

<?= $this->endSection() ?>
