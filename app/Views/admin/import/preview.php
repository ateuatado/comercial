<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-eye"></i> Pré-visualização da Importação</h4>
            <p class="text-muted small mb-0">Confira os dados antes de confirmar.</p>
        </div>
        <a href="<?= site_url('admin/importar') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Cancelar
        </a>
    </div>

    <?php if (!empty($missingCols)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Colunas ausentes no CSV:</strong>
            <code><?= implode(', ', $missingCols) ?></code>
            <br><small>A importação pode falhar. Verifique se o arquivo está no formato correto.</small>
        </div>
    <?php endif; ?>

    <!-- Resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small text-uppercase fw-bold">Linhas no CSV</div>
                    <div class="fs-2 fw-bold text-primary"><?= number_format($totalLines, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small text-uppercase fw-bold">Colunas Detectadas</div>
                    <div class="fs-2 fw-bold text-info"><?= count($header) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small text-uppercase fw-bold">Tamanho</div>
                    <div class="fs-2 fw-bold text-secondary"><?= number_format($fileSize / 1024 / 1024, 1, ',', '.') ?> MB</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview das primeiras linhas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Primeiras <?= count($previewRows) ?> linhas</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 300px; overflow: auto;">
                <table class="table table-sm table-striped mb-0" style="font-size: 11px;">
                    <thead class="table-light" style="position: sticky; top: 0;">
                        <tr>
                            <?php foreach ($header as $i => $col): ?>
                                <?php if ($i < 10): ?>
                                    <th class="text-nowrap"><?= esc($col) ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (count($header) > 10): ?>
                                <th class="text-muted">+<?= count($header) - 10 ?> cols</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewRows as $row): ?>
                            <tr>
                                <?php foreach ($row as $i => $val): ?>
                                    <?php if ($i < 10): ?>
                                        <td class="text-nowrap"><?= esc(mb_substr($val, 0, 30)) ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count($row) > 10): ?>
                                    <td class="text-muted">...</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmação -->
    <div class="card border-0 shadow-sm border-warning">
        <div class="card-body">
            <div class="alert alert-warning mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Atenção:</strong> Esta ação irá <strong>substituir toda a base atual</strong> de carteiras
                pelos <?= number_format($totalLines, 0, ',', '.') ?> registros deste CSV.
            </div>

            <form action="<?= site_url('admin/importar/confirmar') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="filename" value="<?= esc($filename) ?>">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-lg flex-fill" onclick="this.disabled=true;this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Importando...';this.form.submit();">
                        <i class="bi bi-check-lg me-2"></i>Confirmar Importação
                    </button>
                    <a href="<?= site_url('admin/importar') ?>" class="btn btn-outline-secondary btn-lg">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
