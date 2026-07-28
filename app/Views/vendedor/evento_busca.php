<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --border-color: #e2e8f0;
    --neutral-light: #f8fafc;
}

.event-search-container {
    max-width: 540px;
    margin: 0 auto;
    background: var(--neutral-light);
    min-height: 100vh;
    padding-bottom: 40px;
}

.event-search-header {
    background: #fff;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.event-search-header h1 {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    color: var(--primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}

.back-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
}

.banner-evento {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    color: #fff;
    padding: 14px 16px;
    margin: 12px;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
}

.banner-evento h2 {
    font-size: 16px;
    font-weight: 800;
    margin: 0 0 4px 0;
}

.banner-evento p {
    font-size: 11px;
    margin: 0;
    opacity: 0.9;
}

.search-box-wrap {
    margin: 12px;
}

.search-box {
    display: flex;
    gap: 8px;
    background: #fff;
    padding: 6px;
    border-radius: 12px;
    border: 1.5px solid var(--border-color);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.search-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 8px 10px;
    font-size: 13px;
    background: transparent;
}

.btn-search-go {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.btn-search-go:hover {
    background: var(--primary-light);
}

.result-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid var(--border-color);
    padding: 14px;
    margin: 0 12px 12px 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.result-card h3 {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.result-cnpj {
    font-family: monospace;
    font-size: 11px;
    color: #64748b;
}

.result-address {
    font-size: 11px;
    color: #64748b;
    margin-top: 6px;
    line-height: 1.3;
}

.action-bar-evento {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

.btn-registrar-evento {
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-registrar-evento:hover {
    background: #1d4ed8;
}

.badge-registrado-evento {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Toast */
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    white-space: nowrap;
}
.spiv-toast.show { opacity: 1; }
</style>

<div class="event-search-container">
    <div class="event-search-header">
        <a href="<?= site_url('vendedor/eventos') ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1><i class="bi bi-calendar-event me-1"></i><?= esc($evento['nome']) ?></h1>
        <div style="width: 36px;"></div>
    </div>

    <div class="banner-evento">
        <h2><i class="bi bi-qr-code-scan me-1"></i>Prospecção em Evento</h2>
        <p>
            <?php if (!empty($evento['local'])): ?>
                <i class="bi bi-geo-alt me-1"></i><?= esc($evento['local']) ?> · 
            <?php endif; ?>
            Busque o CNPJ da empresa e registre a abordagem.
        </p>
    </div>

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

<!-- Modal Registro de Contato de Evento -->
<div class="modal fade" id="modalRegistroEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-card-checklist me-2"></i>Registrar Abordagem no Evento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRegistroEvento">
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

                    <!-- Observações -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">
                            Observações do Contato
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

let meusContatosSet = new Map(); // cnpj => contatoObj

// Carregar meus contatos prévios neste evento
document.addEventListener("DOMContentLoaded", async () => {
    await carregarMeusContatosEvento();
});

async function carregarMeusContatosEvento() {
    try {
        const res = await fetch(`<?= site_url('vendedor/eventos/') ?>${EVENTO_ID}/meus-contatos`);
        const data = await res.json();
        if (data.success && data.contatos) {
            meusContatosSet.clear();
            data.contatos.forEach(c => {
                meusContatosSet.set(c.cnpj, c);
            });
        }
    } catch(e) {}
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
                    <i class="bi bi-check-circle-fill"></i> Registrado (${escHtml(label)})
                </span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-modal-reg"
                        data-cnpj="${cleanCnpj}" data-razao="${escHtml(razao)}">
                    <i class="bi bi-plus-lg"></i> Adicionar Outro
                </button>
            `;
        } else {
            actionHtml = `
                <button type="button" class="btn-registrar-evento btn-abrir-modal-reg"
                        data-cnpj="${cleanCnpj}" data-razao="${escHtml(razao)}">
                    <i class="bi bi-card-checklist"></i> Registrar no Evento
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

    // Bind botões do modal
    resultsSection.querySelectorAll('.btn-abrir-modal-reg').forEach(btn => {
        btn.addEventListener('click', () => {
            abrirModalRegistro(btn.dataset.cnpj, btn.dataset.razao);
        });
    });
}

const modalEl = document.getElementById('modalRegistroEvento');
const bsModal = new bootstrap.Modal(modalEl);

function abrirModalRegistro(cnpj, razao) {
    const formattedCnpj = cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");

    document.getElementById('regCnpj').value = cnpj;
    document.getElementById('regRazaoSocial').value = razao;
    document.getElementById('displayEmpresaNome').textContent = razao;
    document.getElementById('displayEmpresaCnpj').textContent = formattedCnpj;

    // Data / Hora atual
    const now = new Date();
    const nowFmt = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    document.getElementById('displayDataHora').value = nowFmt;

    // Reset campos
    document.getElementById('regStatus').value = '';
    document.getElementById('regObservacao').value = '';

    bsModal.show();
}

document.getElementById('btnSalvarRegistro').addEventListener('click', async () => {
    const cnpj = document.getElementById('regCnpj').value;
    const razao = document.getElementById('regRazaoSocial').value;
    const status = document.getElementById('regStatus').value;
    const observacao = document.getElementById('regObservacao').value;

    if (!status) {
        alert('Por favor, selecione um Status da Abordagem.');
        return;
    }

    const btn = document.getElementById('btnSalvarRegistro');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    try {
        const formData = new FormData();
        formData.append('evento_id', EVENTO_ID);
        formData.append('cnpj', cnpj);
        formData.append('razao_social', razao);
        formData.append('status', status);
        formData.append('observacao', observacao);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        const res = await fetch('<?= site_url('vendedor/eventos/registrar') ?>', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            bsModal.hide();
            showToast('✅ ' + data.message);

            // Atualizar mapa local
            meusContatosSet.set(cnpj, { cnpj: cnpj, status: status, observacao: observacao, created_at: data.created_at });

            // Atualizar action bar do card
            const actionBar = document.getElementById(`actionBar_${cnpj}`);
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
                        <i class="bi bi-check-circle-fill"></i> Registrado (${escHtml(label)})
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-modal-reg"
                            data-cnpj="${cnpj}" data-razao="${escHtml(razao)}">
                        <i class="bi bi-plus-lg"></i> Adicionar Outro
                    </button>
                `;
                actionBar.querySelector('.btn-abrir-modal-reg').addEventListener('click', () => {
                    abrirModalRegistro(cnpj, razao);
                });
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
