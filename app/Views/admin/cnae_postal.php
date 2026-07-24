<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.cnae-card {
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.stat-pill strong { font-size: 14px; color: #0f172a; }
.badge-score-5 { background: #15803d; color: #fff; }
.badge-score-4 { background: #16a34a; color: #fff; }
.badge-score-3 { background: #ca8a04; color: #fff; }
.badge-score-2 { background: #0284c7; color: #fff; }
.badge-score-1 { background: #64748b; color: #fff; }
.badge-score-0 { background: #94a3b8; color: #fff; }

.select-score-inline {
    width: 60px;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 6px;
}
.select-cat-inline {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 6px;
    max-width: 130px;
}
.cnae-subclasse {
    font-family: monospace;
    font-weight: 700;
    font-size: 13px;
    color: #1e3a8a;
}
</style>

<div class="container-fluid py-4" style="max-width: 1200px;">
    <!-- Cabeçalho -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">🏷️ Classificação Postal por CNAE</h1>
            <p class="text-muted small mb-0">Potencial de utilização dos Correios por setor econômico (Insumo para Ranking de Prospects)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('admin/scoring') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-sliders me-1"></i> Scoring de Carteira
            </a>
            <form method="POST" action="<?= site_url('admin/cnae-postal/reclassificar') ?>" onsubmit="return confirm('Re-executar a classificação automática para CNAEs não revisados?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Reclassificar Automáticos
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= esc($flash_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($flash_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI / Estatísticas -->
    <div class="cnae-card mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap gap-2">
                <div class="stat-pill">Total: <strong><?= number_format($stats['total']) ?></strong> CNAEs</div>
                <div class="stat-pill">Revisados Manualmente: <strong class="text-primary"><?= number_format($stats['revisados']) ?></strong></div>
            </div>
            <!-- Distribuição por score -->
            <div class="d-flex gap-1">
                <?php for ($s = 5; $s >= 0; $s--): ?>
                    <span class="badge badge-score-<?= $s ?> p-2" title="Score <?= $s ?>: <?= number_format($stats['dist'][$s] ?? 0) ?> CNAEs">
                        Score <?= $s ?>: <strong><?= number_format($stats['dist'][$s] ?? 0) ?></strong>
                    </span>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="cnae-card mb-4">
        <form method="GET" action="<?= site_url('admin/cnae-postal') ?>" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Buscar por código ou descrição..." value="<?= esc($search) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <select name="score" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todo Score --</option>
                    <?php for ($s = 5; $s >= 0; $s--): ?>
                        <option value="<?= $s ?>" <?= $scoreFilter !== null && $scoreFilter !== '' && (int)$scoreFilter === $s ? 'selected' : '' ?>>
                            Score <?= $s ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="categoria" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Toda Categoria --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= esc($cat) ?>" <?= $catFilter === $cat ? 'selected' : '' ?>><?= esc(ucfirst($cat)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="revisado" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Status Revisão --</option>
                    <option value="1" <?= $revFilter === '1' ? 'selected' : '' ?>>Somente Revisados</option>
                    <option value="0" <?= $revFilter === '0' ? 'selected' : '' ?>>Somente Automáticos</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <a href="<?= site_url('admin/cnae-postal') ?>" class="btn btn-sm btn-outline-secondary w-100" title="Limpar Filtros">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela Principal -->
    <div class="cnae-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">Exibindo <strong><?= count($cnaes) ?></strong> de <strong><?= number_format($total) ?></strong> CNAEs filtrados</span>
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Alterações no Score ou Categoria são salvas automaticamente</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">CNAE</th>
                        <th>Denominação / Setor</th>
                        <th style="width: 110px;" class="text-center">Score Postal</th>
                        <th style="width: 140px;">Categoria</th>
                        <th style="width: 110px;" class="text-center">Origem</th>
                        <th style="width: 70px;" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cnaes)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum CNAE encontrado com os filtros aplicados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($cnaes as $c): ?>
                        <tr id="row-<?= esc($c['subclasse']) ?>">
                            <td>
                                <span class="cnae-subclasse"><?= esc(substr($c['subclasse'],0,4).'-'.substr($c['subclasse'],4,1).'/'.substr($c['subclasse'],5,2)) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($c['denominacao']) ?></div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <span class="badge bg-light text-dark border"><?= esc(mb_substr($c['secao'] ?? '', 0, 50)) ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <select class="form-select select-score-inline select-score-bg-<?= (int)$c['postal_score'] ?>"
                                        data-subclasse="<?= esc($c['subclasse']) ?>"
                                        onchange="salvarInline('<?= esc($c['subclasse']) ?>')">
                                    <?php for ($s = 5; $s >= 0; $s--): ?>
                                        <option value="<?= $s ?>" <?= (int)$c['postal_score'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-select select-cat-inline"
                                        id="cat-<?= esc($c['subclasse']) ?>"
                                        data-subclasse="<?= esc($c['subclasse']) ?>"
                                        onchange="salvarInline('<?= esc($c['subclasse']) ?>')">
                                    <?php
                                    $cats = ['ecommerce', 'varejo', 'distribuicao', 'industria', 'servico', 'saude', 'educacao', 'agro', 'descarte'];
                                    foreach ($cats as $catItem):
                                    ?>
                                        <option value="<?= $catItem ?>" <?= $c['postal_categoria'] === $catItem ? 'selected' : '' ?>><?= ucfirst($catItem) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <?php if ($c['revisado']): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">
                                        <i class="bi bi-person-check-fill me-1"></i>Manual
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10px;">
                                        <i class="bi bi-robot me-1"></i>Auto
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                        title="Ver / Editar Justificativa"
                                        onclick="abrirModalJustificativa('<?= esc($c['subclasse']) ?>', '<?= esc(addslashes($c['denominacao'])) ?>', <?= (int)$c['postal_score'] ?>, '<?= esc($c['postal_categoria']) ?>', '<?= esc(addslashes($c['postal_justificativa'] ?? '')) ?>')">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">Página <strong><?= $page ?></strong> de <strong><?= $totalPages ?></strong></div>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($_GET, ['page' => 1])) ?>">&laquo; Primeira</a></li>
                        <li class="page-item"><a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a></li>
                    <?php endif; ?>

                    <li class="page-item active"><span class="page-link"><?= $page ?></span></li>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Próxima</a></li>
                        <li class="page-item"><a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">Última &raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Justificativa / Detalhe -->
<div class="modal fade" id="modalJustificativa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6 fw-bold">Editar CNAE <span id="modalSubclasse" class="cnae-subclasse"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Denominação</label>
                    <div id="modalDenominacao" class="small text-dark p-2 bg-light rounded border"></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Score Postal (0–5)</label>
                        <select id="modalScore" class="form-select form-select-sm fw-bold">
                            <option value="5">5 — E-commerce / Varejo Intensivo</option>
                            <option value="4">4 — Varejo / Envio Frequente</option>
                            <option value="3">3 — Distribuição / Atacado / Amostras</option>
                            <option value="2">2 — Indústria D2C / Serviços Médios</option>
                            <option value="1">1 — Baixo Uso / Ocasional</option>
                            <option value="0">0 — Sem Uso / Descarte</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Categoria</label>
                        <select id="modalCategoria" class="form-select form-select-sm">
                            <option value="ecommerce">Ecommerce</option>
                            <option value="varejo">Varejo</option>
                            <option value="distribuicao">Distribuição</option>
                            <option value="industria">Indústria</option>
                            <option value="servico">Serviço</option>
                            <option value="saude">Saúde</option>
                            <option value="educacao">Educação</option>
                            <option value="agro">Agro</option>
                            <option value="descarte">Descarte</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Justificativa / Nota Técnica de Ajuste</label>
                    <textarea id="modalJustificativaText" class="form-control form-control-sm" rows="3" placeholder="Escreva a razão da alteração de peso para auditoria..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="salvarModalJustificativa()">
                    <i class="bi bi-check-circle me-1"></i> Salvar e Marcar como Revisado
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
let csrfHash  = '<?= csrf_hash() ?>';

async function salvarInline(subclasse) {
    const row   = document.getElementById('row-' + subclasse);
    const score = row.querySelector('.select-score-inline').value;
    const cat   = row.querySelector('.select-cat-inline').value;

    const payload = {
        subclasse: subclasse,
        postal_score: score,
        postal_categoria: cat
    };
    payload[csrfToken] = csrfHash;

    try {
        const res  = await fetch('<?= site_url('admin/cnae-postal/salvar') ?>', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            // Atualiza badge de origem se necessário
            const badgeOrigem = row.querySelector('.badge');
            if (badgeOrigem) {
                badgeOrigem.className = 'badge bg-primary-subtle text-primary border border-primary-subtle';
                badgeOrigem.innerHTML = '<i class="bi bi-person-check-fill me-1"></i>Manual';
            }
        } else {
            alert('❌ Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
        }
    } catch(e) {
        console.error(e);
    }
}

let activeSubclasse = '';

function abrirModalJustificativa(subclasse, denom, score, cat, just) {
    activeSubclasse = subclasse;
    document.getElementById('modalSubclasse').textContent = subclasse.replace(/(\d{4})(\d{1})(\d{2})/, '$1-$2/$3');
    document.getElementById('modalDenominacao').textContent = denom;
    document.getElementById('modalScore').value = score;
    document.getElementById('modalCategoria').value = cat;
    document.getElementById('modalJustificativaText').value = just;

    const modal = new bootstrap.Modal(document.getElementById('modalJustificativa'));
    modal.show();
}

async function salvarModalJustificativa() {
    const score = document.getElementById('modalScore').value;
    const cat   = document.getElementById('modalCategoria').value;
    const just  = document.getElementById('modalJustificativaText').value;

    const payload = {
        subclasse: activeSubclasse,
        postal_score: score,
        postal_categoria: cat,
        postal_justificativa: just
    };
    payload[csrfToken] = csrfHash;

    try {
        const res  = await fetch('<?= site_url('admin/cnae-postal/salvar') ?>', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert('❌ Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
        }
    } catch(e) {
        console.error(e);
    }
}
</script>

<?= $this->endSection() ?>
