<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3"><i class="bi bi-shield-lock-fill text-success me-2"></i>Inventário LGPD (ROPA)</h1>
            <p class="text-muted mb-0">Registro de Operações de Tratamento de Dados Pessoais em conformidade com o Art. 37 da Lei nº 13.709/2018.</p>
        </div>
        <div>
            <a href="<?= url_to('admin_ropa_export') ?>" target="_blank" class="btn btn-outline-primary shadow-sm">
                <i class="bi bi-download me-1"></i> Exportar JSON
            </a>
        </div>
    </div>

    <!-- Cards de Registro -->
    <div class="row g-4">
        <?php foreach ($ropas as $index => $ropa): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-secondary rounded-pill">Processo <?= $index + 1 ?></span>
                        </div>
                        <h4 class="mb-0 text-primary"><?= esc($ropa->getProcessoTratamento()) ?></h4>
                        <small class="text-muted d-block mt-1">Controlador: <?= esc($ropa->getIdentificacaoControlador()) ?></small>
                    </div>
                    <div class="card-body">
                        
                        <!-- Categorias -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-people-fill me-1"></i> Categorias de Titulares</h6>
                                <ul class="mb-0">
                                    <?php foreach ($ropa->getCategoriasTitulares() as $titular): ?>
                                        <li><?= esc($titular) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-database-fill me-1"></i> Dados Pessoais Tratados</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($ropa->getCategoriasDadosPessoais() as $dado): ?>
                                        <span class="badge bg-light text-dark border"><?= esc($dado) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Finalidade e Base Legal -->
                        <div class="bg-light p-3 rounded mb-4 border">
                            <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-bullseye me-1"></i> Finalidade e Base Legal</h6>
                            <p class="mb-2"><strong>Finalidade:</strong> <?= esc($ropa->getFinalidadeTratamento()) ?></p>
                            <p class="mb-0"><strong>Base Legal:</strong> <span class="text-success fw-semibold"><?= esc($ropa->getBaseLegal()) ?></span></p>
                        </div>

                        <!-- Ciclo de Vida e Compartilhamento -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-clock-history me-1"></i> Retenção e Descarte</h6>
                                <p class="small mb-0"><?= esc($ropa->getPrazoRetencaoDescarte()) ?></p>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-share-fill me-1"></i> Compartilhamento e Transferência</h6>
                                <p class="small mb-1">
                                    <strong>Destinatários:</strong>
                                    <?= $ropa->getCompartilhamentoDados() ? esc(implode('; ', $ropa->getCompartilhamentoDados())) : 'Não há' ?>
                                </p>
                                <p class="small mb-0">
                                    <strong>Internacional:</strong> <?= esc($ropa->getTransferenciaInternacional() ?? 'Não há') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Medidas e Direitos -->
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-shield-check me-1"></i> Medidas de Segurança</h6>
                                <ul class="small text-muted mb-0">
                                    <?php foreach ($ropa->getMedidasSeguranca() as $medida): ?>
                                        <li><?= esc($medida) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h6 class="fw-bold text-uppercase text-muted small"><i class="bi bi-person-check me-1"></i> Direitos dos Titulares</h6>
                                <p class="small text-muted mb-0"><?= esc($ropa->getAtendimentoDireitosTitulares()) ?></p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?= $this->endSection() ?>
