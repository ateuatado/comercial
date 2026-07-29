<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.search-container {
    max-width: 720px;
    margin: 0 auto;
    padding: 16px 12px 60px 12px;
}
.banner-evento {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.banner-evento h2 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 4px 0;
}
.banner-evento p {
    font-size: 0.85rem;
    margin: 0;
    opacity: 0.9;
}
.search-box-wrap {
    background: white;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 16px;
}
.search-box {
    display: flex;
    gap: 8px;
}
.search-input {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
}
.search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.btn-search-go {
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0 18px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-search-go:hover {
    background: #1d4ed8;
}

.result-card {
    background: white;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
}
.result-card h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 2px 0;
}
.result-cnpj {
    font-family: monospace;
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 6px;
}
.result-address {
    font-size: 0.8rem;
    color: #475569;
    margin-bottom: 10px;
}
.action-bar-evento {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.btn-registrar-evento {
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
}
.btn-registrar-evento:hover {
    background: #059669;
}
.badge-registrado-evento {
    background: #e0e7ff;
    color: #3730a3;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
}
.spiv-toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.85rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    display: none;
}
.spiv-toast.show {
    display: block;
    animation: fadeIn 0.3s;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, 10px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}
</style>

<div class="search-container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="<?= site_url('vendedor/eventos') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Eventos
        </a>
        <h1 class="h6 mb-0 text-truncate px-2"><i class="bi bi-calendar-event me-1 text-primary"></i><?= esc($evento['nome']) ?></h1>
        <div style="width: 36px;"></div>
    </div>

    <div class="banner-evento">
        <h2><i class="bi bi-qr-code-scan me-1"></i>Prospecção em Evento</h2>
        <p>
            <?php if (!empty($evento['local'])): ?>
                <i class="bi bi-geo-alt me-1"></i><?= esc($evento['local']) ?> · 
            <?php endif; ?>
            Busque o CNPJ ou gerencie suas abordagens.
        </p>
    </div>

    <!-- Navegação em Abas -->
    <ul class="nav nav-pills mb-3 nav-justified bg-white p-1 rounded-3 shadow-sm border" id="eventoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold py-2" id="tabBusca-tab" data-bs-toggle="pill" data-bs-target="#tabBusca" type="button" role="tab" style="font-size: 0.85rem;">
                <i class="bi bi-search me-1"></i>Buscar Empresas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold py-2" id="tabMeusRegistros-tab" data-bs-toggle="pill" data-bs-target="#tabMeusRegistros" type="button" role="tab" style="font-size: 0.85rem;">
                <i class="bi bi-journal-check me-1"></i>Meus Registros <span class="badge bg-primary rounded-pill ms-1" id="myCountBadge">0</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="eventoTabContent">
        <!-- ABA 1: BUSCAR EMPRESAS -->
        <div class="tab-pane fade show active" id="tabBusca" role="tabpanel">
            <div class="search-box-wrap">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Digite CNPJ, Nome ou Endereço..." autocomplete="off">
                    <button type="button" class="btn-search-go" id="btnSearchGo">Pesquisar</button>
                </div>
                <div class="text-muted mt-1 px-1" style="font-size: 10px;">
                    <i class="bi bi-search me-1"></i>Digite pelo menos 3 caracteres.
                </div>
            </div>

            <!-- Resultados -->
            <div id="resultsSection">
                <div class="text-center text-muted py-5" id="initialMsg">
                    <i class="bi bi-building-add" style="font-size: 36px; color: #cbd5e1;"></i>
                    <p class="mt-2 small">Digite o CNPJ ou Nome da empresa acima para buscar.</p>
                </div>
            </div>
        </div>

        <!-- ABA 2: MEUS REGISTROS -->
        <div class="tab-pane fade" id="tabMeusRegistros" role="tabpanel">
            <div id="myRecordsList">
                <div class="text-center text-muted py-5 bg-white rounded-3 border">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <p class="mt-2 small mb-0">Carregando seus registros...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro / Edição de Contato de Evento -->
<div class="modal fade" id="modalRegistroEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-card-checklist me-2"></i><span id="modalRegistroTitle">Registrar Abordagem no Evento</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRegistroEvento">
                    <input type="hidden" id="regContactId" name="contact_id">
                    <input type="hidden" id="regCnpj" name="cnpj">
                    <input type="hidden" id="regRazaoSocial" name="razao_social">

                    <!-- Empresa -->
                    <div class="p-2 mb-3 rounded bg-light border">
                        <div class="fw-bold text-dark fs-6" id="displayEmpresaNome">—</div>
                        <small class="text-muted font-monospace" id="displayEmpresaCnpj">—</small>
                    </div>

                    <!-- Data/Hora Registro -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold mb-1">
                            <i class="bi bi-clock me-1"></i>Data e Hora do Registro
                        </label>
                        <input type="text" id="displayDataHora" class="form-control form-control-sm bg-light" readonly>
                    </div>

                    <!-- Combo Status de Interesse -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">
                            Status da Abordagem / Intenção <span class="text-danger">*</span>
                        </label>
                        <select id="regStatus" class="form-select form-select-sm" required>
                            <option value="">-- Selecione uma opção --</option>
                            <option value="marcar_reuniao">📅 Marcar Reunião</option>
                            <option value="ligar_depois">📞 Ligar Depois</option>
                            <option value="interesse_limitado">⚡ Interesse Limitado</option>
                            <option value="sem_interesse">❌ Sem Interesse</option>
                        </select>
                    </div>

                    <!-- Contrato com os Correios? (Seletor Aberto/Fechado) -->
                    <div class="mb-3 p-2 border rounded bg-white d-flex align-items-center justify-content-between">
                        <div>
                            <label class="form-check-label fw-bold small text-dark d-block mb-0" for="regPossuiContrato">
                                <i class="bi bi-file-earmark-text text-primary me-1"></i>Contrato com os Correios?
                            </label>
                            <small class="text-muted" style="font-size: 10px;">Cliente já possui contrato ativo</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="regPossuiContrato" style="cursor: pointer; transform: scale(1.2);">
                        </div>
                    </div>

                    <!-- Lista de Produtos de Interesse -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1">
                            <i class="bi bi-box-seam me-1"></i>Produtos de Interesse
                        </label>
                        <div class="d-flex flex-wrap gap-2 pt-1 p-2 bg-white rounded border">
                            <div class="form-check form-check-inline me-2">
                                <input class="form-check-input chk-produto" type="checkbox" id="prod_encomenda" value="Encomenda" style="cursor:pointer;">
                                <label class="form-check-label small" for="prod_encomenda" style="cursor:pointer;">Encomenda</label>
                            </div>
                            <div class="form-check form-check-inline me-2">
                                <input class="form-check-input chk-produto" type="checkbox" id="prod_mensagem" value="Mensagem" style="cursor:pointer;">
                                <label class="form-check-label small" for="prod_mensagem" style="cursor:pointer;">Mensagem</label>
                            </div>
                            <div class="form-check form-check-inline me-2">
                                <input class="form-check-input chk-produto" type="checkbox" id="prod_logplus" value="Log+" style="cursor:pointer;">
                                <label class="form-check-label small" for="prod_logplus" style="cursor:pointer;">Log+</label>
                            </div>
                            <div class="form-check form-check-inline me-2">
                                <input class="form-check-input chk-produto" type="checkbox" id="prod_adicionais" value="Adicionais" style="cursor:pointer;">
                                <label class="form-check-label small" for="prod_adicionais" style="cursor:pointer;">Adicionais</label>
                            </div>
                            <div class="form-check form-check-inline me-2">
                                <input class="form-check-input chk-produto" type="checkbox" id="prod_outros" value="Outros" style="cursor:pointer;">
                                <label class="form-check-label small" for="prod_outros" style="cursor:pointer;">Outros</label>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">
                            Observações do Contato / Serviços
                        </label>
                        <textarea id="regObservacao" class="form-control form-control-sm" rows="3" placeholder="Ex: Falei com o diretor de logística, pediu para enviar proposta no e-mail..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSalvarRegistro">
                    <i class="bi bi-check-lg me-1"></i>Salvar Registro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="spivToast" class="spiv-toast">Mensagem do Sistema</div>

<script>
const EVENTO_ID = <?= (int)$evento['id'] ?>;
const searchInput = document.getElementById('searchInput');
const btnSearchGo = document.getElementById('btnSearchGo');
const resultsSection = document.getElementById('resultsSection');

let meusContatosList = [];
let meusContatosSet = new Map(); // cnpj => contatoObj
let userLat = null;
let userLng = null;

// Carregar meus contatos prévios neste evento e solicitar GPS
document.addEventListener("DOMContentLoaded", async () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
            },
            (err) => {},
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    }
    await carregarMeusContatosEvento();
});

async function carregarMeusContatosEvento() {
    try {
        const res = await fetch(`<?= site_url('vendedor/eventos/') ?>${EVENTO_ID}/meus-contatos`);
        const data = await res.json();
        if (data.success && data.contatos) {
            meusContatosList = data.contatos;
            meusContatosSet.clear();
            data.contatos.forEach(c => {
                meusContatosSet.set(c.cnpj, c);
            });
            const badge = document.getElementById('myCountBadge');
            if (badge) badge.textContent = data.contatos.length;
            renderMeusRegistrosList();
        }
    } catch(e) {}
}

function renderMeusRegistrosList() {
    const container = document.getElementById('myRecordsList');
    if (!container) return;

    if (!meusContatosList || meusContatosList.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5 bg-white rounded-3 border">
                <i class="bi bi-journal-x" style="font-size: 36px; color: #cbd5e1;"></i>
                <p class="mt-2 small mb-0">Você ainda não registrou abordagens neste evento.</p>
                <small class="text-secondary">Use a aba <strong>Buscar Empresas</strong> para pesquisar e cadastrar.</small>
            </div>
        `;
        return;
    }

    const statusBadgeMap = {
        'marcar_reuniao':     { label: '📅 Marcar Reunião',     class: 'bg-success' },
        'ligar_depois':       { label: '📞 Ligar Depois',        class: 'bg-primary' },
        'interesse_limitado': { label: '⚡ Interesse Limitado',  class: 'bg-warning text-dark' },
        'sem_interesse':      { label: '❌ Sem Interesse',       class: 'bg-danger' }
    };

    let html = '';
    meusContatosList.forEach(c => {
        const cnpjFmt = c.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");
        const st = statusBadgeMap[c.status] || { label: c.status, class: 'bg-secondary' };
        const prods = c.produtos_interesse ? c.produtos_interesse.split(',').map(p=>p.trim()).filter(Boolean) : [];

        html += `
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">${escHtml(c.razao_social || 'Razão Social Indisponível')}</h6>
                            <small class="text-muted font-monospace">${cnpjFmt}</small>
                        </div>
                        <span class="badge ${st.class}">${st.label}</span>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-2">
                        ${c.possui_contrato ? '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-file-earmark-check me-1"></i>Com Contrato</span>' : '<span class="badge bg-light text-muted border">Sem Contrato</span>'}
                        ${prods.map(p => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;">${escHtml(p)}</span>`).join('')}
                    </div>

                    ${c.observacao ? `<div class="p-2 rounded bg-light text-dark small mb-2" style="font-style:italic;">"${escHtml(c.observacao)}"</div>` : ''}

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-muted" style="font-size:11px;">
                            <i class="bi bi-clock me-1"></i>${c.created_at_fmt || c.created_at}
                        </small>
                        <button type="button" class="btn btn-sm btn-primary btn-editar-meu-registro" data-id="${c.id}">
                            <i class="bi bi-pencil-square me-1"></i>Editar Registro
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    container.querySelectorAll('.btn-editar-meu-registro').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const contactId = btn.dataset.id;
            const contactObj = meusContatosList.find(item => String(item.id) === String(contactId));
            if (contactObj) {
                abrirModalEditarRegistro(contactObj);
            }
        });
    });
}

function escHtml(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function showToast(msg) {
    const t = document.getElementById('spivToast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

btnSearchGo.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') performSearch();
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

    try {
        const res = await fetch('<?= site_url('vendedor/prospectar/pesquisa/buscar') ?>?q=' + encodeURIComponent(q));
        const data = await res.json();

        if (data.success) {
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
                <p class="mt-2 small">Nenhum estabelecimento encontrado.</p>
            </div>
        `;
        return;
    }

    resultsSection.innerHTML = '';
    list.forEach(item => {
        const cleanCnpj = item.cnpj;
        const formattedCnpj = cleanCnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");
        const razao = item.nome_fantasia || item.razao_social;

        const card = document.createElement('div');
        card.className = 'result-card';
        card.dataset.cnpj = cleanCnpj;

        const jaRegistrado = meusContatosSet.get(cleanCnpj);

        let actionHtml = '';
        if (jaRegistrado) {
            const stLabelMap = {
                'marcar_reuniao': '📅 Marcar Reunião',
                'ligar_depois': '📞 Ligar Depois',
                'interesse_limitado': '⚡ Interesse Limitado',
                'sem_interesse': '❌ Sem Interesse'
            };
            const label = stLabelMap[jaRegistrado.status] || jaRegistrado.status;
            actionHtml = `
                <span class="badge-registrado-evento">
                    <i class="bi bi-check-circle-fill me-1"></i>Registrado (${escHtml(label)})
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-modal-reg"
                        data-cnpj="${cleanCnpj}" data-razao="${escHtml(razao)}">
                    <i class="bi bi-pencil-square me-1"></i>Editar Registro
                </button>
            `;
        } else {
            actionHtml = `
                <button type="button" class="btn-registrar-evento btn-abrir-modal-reg"
                        data-cnpj="${cleanCnpj}" data-razao="${escHtml(razao)}">
                    <i class="bi bi-card-checklist me-1"></i>Registrar no Evento
                </button>
            `;
        }

        card.innerHTML = `
            <h3>${escHtml(razao)}</h3>
            <div class="result-cnpj">${formattedCnpj}</div>
            <div class="result-address">
                <i class="bi bi-geo-alt text-muted"></i> ${escHtml(item.endereco_completo || 'Endereço não informado')}
            </div>
            <div class="action-bar-evento" id="actionBar_${cleanCnpj}">
                ${actionHtml}
            </div>
        `;

        resultsSection.appendChild(card);
    });

    resultsSection.querySelectorAll('.btn-abrir-modal-reg').forEach(btn => {
        btn.addEventListener('click', () => {
            abrirModalRegistro(btn.dataset.cnpj, btn.dataset.razao);
        });
    });
}

function getBsModal() {
    const modalEl = document.getElementById('modalRegistroEvento');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        if (bootstrap.Modal.getOrCreateInstance) {
            return bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    }
    return null;
}

function abrirModalRegistro(cnpj, razao) {
    const existing = meusContatosList.find(c => c.cnpj === cnpj);
    if (existing) {
        abrirModalEditarRegistro(existing);
        return;
    }

    const modal = getBsModal();

    const formattedCnpj = cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");

    document.getElementById('regContactId').value = '';
    document.getElementById('regCnpj').value = cnpj;
    document.getElementById('regRazaoSocial').value = razao;
    document.getElementById('displayEmpresaNome').textContent = razao;
    document.getElementById('displayEmpresaCnpj').textContent = formattedCnpj;
    document.getElementById('modalRegistroTitle').textContent = 'Registrar Abordagem no Evento';
    document.getElementById('btnSalvarRegistro').innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar Registro';

    const now = new Date();
    const nowFmt = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    document.getElementById('displayDataHora').value = nowFmt;

    document.getElementById('regStatus').value = '';
    document.getElementById('regObservacao').value = '';
    document.getElementById('regPossuiContrato').checked = false;
    document.querySelectorAll('.chk-produto').forEach(c => c.checked = false);

    if (modal) modal.show();
}

function abrirModalEditarRegistro(c) {
    const modal = getBsModal();

    const formattedCnpj = c.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");

    document.getElementById('regContactId').value = c.id;
    document.getElementById('regCnpj').value = c.cnpj;
    document.getElementById('regRazaoSocial').value = c.razao_social || '';
    document.getElementById('displayEmpresaNome').textContent = c.razao_social || 'Razão Social Indisponível';
    document.getElementById('displayEmpresaCnpj').textContent = formattedCnpj;
    document.getElementById('modalRegistroTitle').textContent = 'Editar Abordagem no Evento';
    document.getElementById('btnSalvarRegistro').innerHTML = '<i class="bi bi-check-lg me-1"></i>Atualizar Registro';

    document.getElementById('displayDataHora').value = c.created_at_fmt || c.created_at;

    document.getElementById('regStatus').value = c.status || '';
    document.getElementById('regObservacao').value = c.observacao || '';
    document.getElementById('regPossuiContrato').checked = !!c.possui_contrato;

    const selectedProds = c.produtos_interesse ? c.produtos_interesse.split(',').map(p=>p.trim().toLowerCase()) : [];
    document.querySelectorAll('.chk-produto').forEach(chk => {
        chk.checked = selectedProds.includes(chk.value.toLowerCase());
    });

    if (modal) modal.show();
}

document.getElementById('btnSalvarRegistro').addEventListener('click', async () => {
    const contactId = document.getElementById('regContactId').value;
    const cnpj = document.getElementById('regCnpj').value;
    const razao = document.getElementById('regRazaoSocial').value;
    const status = document.getElementById('regStatus').value;
    const observacao = document.getElementById('regObservacao').value;
    const possuiContrato = document.getElementById('regPossuiContrato').checked ? '1' : '0';

    const produtosSelecionados = [];
    document.querySelectorAll('.chk-produto:checked').forEach(c => {
        produtosSelecionados.push(c.value);
    });

    if (!status) {
        alert('Por favor, selecione um Status da Abordagem.');
        return;
    }

    const btn = document.getElementById('btnSalvarRegistro');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    try {
        const formData = new FormData();
        if (contactId) {
            formData.append('contact_id', contactId);
        }
        formData.append('evento_id', EVENTO_ID);
        formData.append('cnpj', cnpj);
        formData.append('razao_social', razao);
        formData.append('status', status);
        formData.append('observacao', observacao);
        formData.append('possui_contrato', possuiContrato);
        produtosSelecionados.forEach(p => {
            formData.append('produtos_interesse[]', p);
        });

        if (userLat && userLng) {
            formData.append('latitude', userLat);
            formData.append('longitude', userLng);
        }

        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        const res = await fetch('<?= site_url('vendedor/eventos/registrar') ?>', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            const modal = getBsModal();
            if (modal) modal.hide();
            showToast('✅ ' + data.message);

            await carregarMeusContatosEvento();

            const card = document.querySelector(`.result-card[data-cnpj="${cnpj}"]`);
            if (card) {
                const actionBar = card.querySelector('.action-bar-evento');
                if (actionBar) {
                    const stLabelMap = {
                        'marcar_reuniao': '📅 Marcar Reunião',
                        'ligar_depois': '📞 Ligar Depois',
                        'interesse_limitado': '⚡ Interesse Limitado',
                        'sem_interesse': '❌ Sem Interesse'
                    };
                    const label = stLabelMap[status] || status;
                    actionBar.innerHTML = `
                        <span class="badge-registrado-evento">
                            <i class="bi bi-check-circle-fill me-1"></i>Registrado (${escHtml(label)})
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-modal-reg"
                                data-cnpj="${cnpj}" data-razao="${escHtml(razao)}">
                            <i class="bi bi-pencil-square me-1"></i>Editar Registro
                        </button>
                    `;
                    actionBar.querySelector('.btn-abrir-modal-reg').addEventListener('click', () => {
                        abrirModalRegistro(cnpj, razao);
                    });
                }
            }
        } else {
            alert('❌ ' + (data.error || 'Erro ao registrar contato.'));
        }
    } catch(e) {
        alert('❌ Erro de comunicação com o servidor.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar Registro';
    }
});
</script>

<?= $this->endSection() ?>
