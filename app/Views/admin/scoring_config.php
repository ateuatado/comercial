<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.scoring-card {
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.scoring-card h2 {
    font-size: 15px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 4px;
}
.scoring-card .subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 20px;
}
.weight-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.weight-label {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.weight-label small {
    display: block;
    font-size: 10px;
    font-weight: 400;
    color: #94a3b8;
}
.weight-input {
    width: 70px;
    text-align: center;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 8px;
    font-size: 14px;
    font-weight: 700;
    color: #1e3a8a;
}
.weight-bar {
    flex: 2;
    height: 8px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
}
.weight-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.3s;
}
.sum-indicator {
    font-size: 20px;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s;
}
.sum-ok   { background: #dcfce7; color: #166534; }
.sum-warn { background: #fef3c7; color: #92400e; }
.sum-err  { background: #fee2e2; color: #991b1b; }

.cnae-table th { font-size: 11px; color: #64748b; font-weight: 600; }
.cnae-table td { font-size: 12px; vertical-align: middle; }
.badge-weight {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
}

.progress-section { display: none; }
.progress-section.active { display: block; }
</style>

<div class="container-fluid py-4" style="max-width: 900px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">⚙️ Scoring Preditivo</h1>
            <p class="text-muted small mb-0">Configure os pesos do algoritmo de qualificação de leads logísticos</p>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= esc($flash_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Bloco 1: Pesos das categorias ─────────────────────── -->
    <div class="scoring-card">
        <h2><i class="bi bi-sliders me-2"></i>Pesos por Categoria</h2>
        <p class="subtitle">A soma dos 5 pesos deve ser exatamente <strong>100</strong>. O sistema bloqueará o envio enquanto a soma divergir.</p>

        <form id="formPesos" method="POST" action="<?= site_url('admin/scoring/salvar') ?>">
            <?= csrf_field() ?>

            <div class="weight-row">
                <div class="weight-label">
                    Ramo de Atividade (CNAE)
                    <small>Comércio varejista de bens físicos</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill bg-primary" id="bar_cnae" style="width:<?= $config['weight_cnae'] ?? 40 ?>%"></div></div>
                <input type="number" name="weight_cnae" id="inp_cnae" class="weight-input" min="0" max="100" value="<?= $config['weight_cnae'] ?? 40 ?>">
            </div>

            <div class="weight-row">
                <div class="weight-label">
                    Porte (Capital Social)
                    <small>Faturamento potencial e volume de envios</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill bg-success" id="bar_capital" style="width:<?= $config['weight_capital'] ?? 20 ?>%"></div></div>
                <input type="number" name="weight_capital" id="inp_capital" class="weight-input" min="0" max="100" value="<?= $config['weight_capital'] ?? 20 ?>">
            </div>

            <div class="weight-row">
                <div class="weight-label">
                    Maturidade Digital (E-mail)
                    <small>E-mail corporativo = indício de site/e-commerce</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill bg-info" id="bar_email" style="width:<?= $config['weight_email'] ?? 15 ?>%"></div></div>
                <input type="number" name="weight_email" id="inp_email" class="weight-input" min="0" max="100" value="<?= $config['weight_email'] ?? 15 ?>">
            </div>

            <div class="weight-row">
                <div class="weight-label">
                    Presença Comercial (Marca)
                    <small>Nome Fantasia ativo na Receita Federal</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill bg-warning" id="bar_marca" style="width:<?= $config['weight_nome_fantasia'] ?? 10 ?>%"></div></div>
                <input type="number" name="weight_nome_fantasia" id="inp_marca" class="weight-input" min="0" max="100" value="<?= $config['weight_nome_fantasia'] ?? 10 ?>">
            </div>

            <div class="weight-row">
                <div class="weight-label">
                    Localização Estratégica
                    <small>Proximidade a CDDs e GEVENs dos Correios</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill bg-danger" id="bar_loc" style="width:<?= $config['weight_localizacao'] ?? 15 ?>%"></div></div>
                <input type="number" name="weight_localizacao" id="inp_loc" class="weight-input" min="0" max="100" value="<?= $config['weight_localizacao'] ?? 15 ?>">
            </div>

            <hr class="my-3">

            <!-- Soma indicadora -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="flex-grow-1">
                    <span class="text-muted small">Total dos pesos:</span>
                </div>
                <div class="sum-indicator sum-ok" id="sumIndicator">100 / 100</div>
            </div>

            <!-- Fator de Amortização -->
            <div class="weight-row mt-3">
                <div class="weight-label">
                    Fator de Amortização — CNAE Secundário
                    <small>% do peso aplicado quando o CNAE de interesse está nos secundários (não no principal)</small>
                </div>
                <div class="weight-bar"><div class="weight-bar-fill" style="background:#8b5cf6; width:<?= $config['amortization_factor'] ?? 70 ?>%"></div></div>
                <input type="number" name="amortization_factor" class="weight-input" min="0" max="100" value="<?= $config['amortization_factor'] ?? 70 ?>" style="color:#7c3aed; border-color:#c4b5fd;">
            </div>

            <!-- Faixas de Capital Social -->
            <div class="row mt-3 g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold small">Capital Alto (acima de R$):</label>
                    <input type="number" name="capital_tier_high" class="form-control form-control-sm" value="<?= $config['capital_tier_high'] ?? 100000 ?>">
                    <small class="text-muted" style="font-size:10px;">→ ganha 100% do peso Capital</small>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small">Capital Médio (acima de R$):</label>
                    <input type="number" name="capital_tier_mid" class="form-control form-control-sm" value="<?= $config['capital_tier_mid'] ?? 20000 ?>">
                    <small class="text-muted" style="font-size:10px;">→ ganha 50% do peso Capital</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" id="btnSalvar" class="btn btn-primary" disabled>
                    <i class="bi bi-save me-1"></i> Salvar Configurações
                </button>
                <button type="button" id="btnRecalcular" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Salvar e Recalcular Score da Base
                </button>
            </div>
        </form>

        <!-- Barra de Progresso -->
        <div class="progress-section mt-4" id="progressSection">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-semibold text-primary">Recalculando scores...</span>
                <span class="small fw-bold text-primary" id="progressPct">0%</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 99px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                     id="progressBar" role="progressbar" style="width:0%"></div>
            </div>
            <p class="text-muted small mt-2 mb-0" id="progressMsg">Aguarde. Processando em lote...</p>
        </div>
    </div>

    <!-- ── Bloco 2: Tabela de CNAEs Cadastrados ──────────────── -->
    <div class="scoring-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <h2><i class="bi bi-diagram-3 me-2"></i>Regras por CNAE</h2>
                <p class="subtitle mb-0"><?= count($cnaeRules) ?> CNAEs cadastrados com pesos individuais</p>
            </div>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalNovoCnae">
                <i class="bi bi-plus-circle me-1"></i>Novo CNAE
            </button>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-sm cnae-table">
                <thead>
                    <tr>
                        <th>Código CNAE</th>
                        <th>Descrição</th>
                        <th class="text-center">Peso</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cnaeRules as $rule): ?>
                    <tr>
                        <td><code><?= esc($rule['cnae_code']) ?></code></td>
                        <td><?= esc($rule['description']) ?></td>
                        <td class="text-center">
                            <?php
                                $w = (int) $rule['weight'];
                                $cls = $w >= 35 ? 'bg-success' : ($w >= 20 ? 'bg-warning text-dark' : 'bg-secondary');
                            ?>
                            <span class="badge-weight <?= $cls ?> text-white"><?= $w ?></span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-danger btn-delete-cnae"
                                    data-code="<?= esc($rule['cnae_code']) ?>"
                                    style="font-size: 10px; padding: 2px 8px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Novo CNAE -->
<div class="modal fade" id="modalNovoCnae" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6 fw-bold">Adicionar Regra de CNAE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Código CNAE <small class="text-muted">(ex: 4781-4/00)</small></label>
                    <input type="text" id="newCnaeCode" class="form-control form-control-sm" placeholder="0000-0/00">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Descrição</label>
                    <input type="text" id="newCnaeDesc" class="form-control form-control-sm" placeholder="Comércio varejista de...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Peso (0 a 100)</label>
                    <input type="number" id="newCnaeWeight" class="form-control form-control-sm" min="0" max="100" value="40">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnSalvarCnae" class="btn btn-sm btn-primary">Salvar CNAE</button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Validador de Soma dos Pesos ───────────────────────────────
const weightInputs = ['inp_cnae', 'inp_capital', 'inp_email', 'inp_marca', 'inp_loc'];
const bars         = ['bar_cnae', 'bar_capital', 'bar_email', 'bar_marca', 'bar_loc'];
const sumEl        = document.getElementById('sumIndicator');
const btnSalvar    = document.getElementById('btnSalvar');

function recalcSum() {
    let sum = 0;
    weightInputs.forEach((id, i) => {
        const val = parseInt(document.getElementById(id).value) || 0;
        sum += val;
        document.getElementById(bars[i]).style.width = Math.min(100, val) + '%';
    });

    sumEl.textContent = sum + ' / 100';
    sumEl.className = 'sum-indicator';

    if (sum === 100) {
        sumEl.classList.add('sum-ok');
        btnSalvar.disabled = false;
    } else if (Math.abs(sum - 100) <= 5) {
        sumEl.classList.add('sum-warn');
        btnSalvar.disabled = true;
    } else {
        sumEl.classList.add('sum-err');
        btnSalvar.disabled = true;
    }
}

weightInputs.forEach(id => {
    document.getElementById(id).addEventListener('input', recalcSum);
});
recalcSum();

// ── Recalcular Score em Lote (assíncrono) ────────────────────
document.getElementById('btnRecalcular').addEventListener('click', async () => {
    if (btnSalvar.disabled) {
        alert('⚠️ A soma dos pesos deve ser 100 antes de recalcular.');
        return;
    }

    const confirmado = confirm('Isso recalculará o score de TODOS os CNPJs da base. O processo roda em background e pode levar alguns minutos. Continuar?');
    if (!confirmado) return;

    // Salvar pesos primeiro
    document.getElementById('formPesos').submit();
});

// ── Polling de Progresso ──────────────────────────────────────
const progressSection = document.getElementById('progressSection');
const progressBar     = document.getElementById('progressBar');
const progressPct     = document.getElementById('progressPct');
const progressMsg     = document.getElementById('progressMsg');

<?php if (!empty($recalculating)): ?>
progressSection.classList.add('active');
const pollingInterval = setInterval(async () => {
    try {
        const res  = await fetch('<?= site_url('admin/scoring/progresso') ?>');
        const data = await res.json();
        const pct  = parseInt(data.progresso) || 0;
        progressBar.style.width = pct + '%';
        progressPct.textContent = pct + '%';

        if (pct >= 100) {
            clearInterval(pollingInterval);
            progressMsg.textContent = '✅ Recálculo concluído! Recarregando...';
            progressBar.classList.remove('progress-bar-animated');
            setTimeout(() => location.reload(), 1500);
        }
    } catch(e) {}
}, 2000);
<?php endif; ?>

// ── AJAX: Adicionar CNAE ──────────────────────────────────────
document.getElementById('btnSalvarCnae').addEventListener('click', async () => {
    const code   = document.getElementById('newCnaeCode').value.trim();
    const desc   = document.getElementById('newCnaeDesc').value.trim();
    const weight = document.getElementById('newCnaeWeight').value;

    if (!code || !weight) { alert('Código e Peso são obrigatórios.'); return; }

    const res  = await fetch('<?= site_url('admin/scoring/cnae/adicionar') ?>', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body:    JSON.stringify({ cnae_code: code, description: desc, weight: weight, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
    });
    const data = await res.json();
    if (data.success) {
        alert('✅ CNAE adicionado! A página será recarregada.');
        location.reload();
    } else {
        alert('❌ ' + (data.error || 'Erro ao salvar.'));
    }
});

// ── AJAX: Excluir CNAE ────────────────────────────────────────
document.querySelectorAll('.btn-delete-cnae').forEach(btn => {
    btn.addEventListener('click', async () => {
        const code = btn.dataset.code;
        if (!confirm(`Remover o CNAE ${code}?`)) return;

        const res  = await fetch('<?= site_url('admin/scoring/cnae/remover') ?>', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    JSON.stringify({ cnae_code: code, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
        });
        const data = await res.json();
        if (data.success) { btn.closest('tr').remove(); }
        else { alert('❌ Erro ao remover.'); }
    });
});
</script>

<?= $this->endSection() ?>
