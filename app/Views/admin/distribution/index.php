<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <h1 class="h3 mb-4">Distribuição de Carteira</h1>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $cls): ?>
        <?php if ($msg = session()->getFlashdata($key)): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show" role="alert">
                <?= esc($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-primary"><?= number_format($total_clients) ?></div>
                    <div class="text-muted small">Clientes na carteira</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <div class="fs-1 fw-bold <?= $unassigned > 0 ? 'text-warning' : 'text-success' ?>">
                        <?= number_format($unassigned) ?>
                    </div>
                    <div class="text-muted small">Sem responsável</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-secondary"><?= number_format($total_vendors) ?></div>
                    <div class="text-muted small">Vendedores ativos</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Painel de ações -->
        <div class="col-lg-5">

            <!-- Distribuição automática -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Distribuição automática</div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Distribui todos os clientes sem responsável entre os vendedores ativos,
                        priorizando menor carteira para maior capital social.
                    </p>
                    <form method="POST" action="/admin/distribuicao/executar"
                          onsubmit="return confirm('Confirma a distribuição automática? Esta ação atribuirá todos os clientes sem responsável.')">
                        <button type="submit" class="btn btn-primary w-100"
                                <?= $unassigned === 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-lightning-charge me-1"></i>
                            Executar distribuição
                            <?php if ($unassigned > 0): ?>
                                (<?= number_format($unassigned) ?> clientes)
                            <?php endif ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reatribuição manual -->
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Reatribuição manual</div>
                <div class="card-body">
                    <form method="POST" action="/admin/distribuicao/reatribuir">
                        <div class="mb-3">
                            <label for="cnpj" class="form-label small fw-semibold">CNPJ</label>
                            <input type="text" id="cnpj" name="cnpj"
                                   class="form-control form-control-sm"
                                   placeholder="14 dígitos sem formatação"
                                   maxlength="14" required>
                        </div>
                        <div class="mb-3">
                            <label for="vendor_id" class="form-label small fw-semibold">
                                Novo responsável
                            </label>
                            <select id="vendor_id" name="vendor_id" class="form-select form-select-sm">
                                <option value="">— Sem responsável —</option>
                                <?php foreach ($active_vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= esc($v['nome']) ?>
                                        (<?= $v['tipo_acom'] ? 'ACOM ' . $v['tipo_acom'] : 'GC' ?>)
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label small fw-semibold">Motivo</label>
                            <textarea id="motivo" name="motivo" class="form-control form-control-sm"
                                      rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-left-right me-1"></i> Reatribuir
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Tabela de distribuição atual -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Distribuição atual por vendedor</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendedor</th>
                                <th>Tipo</th>
                                <th class="text-end">Clientes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($by_vendor)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Nenhuma atribuição registrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($by_vendor as $row): ?>
                                    <tr>
                                        <td>
                                            <?= esc($row['nome'] ?? '—') ?>
                                            <small class="text-muted"><?= esc($row['matricula'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <?php if ($row['tipo_acom']): ?>
                                                <span class="badge bg-secondary">ACOM <?= esc($row['tipo_acom']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">GC</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format($row['total']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
