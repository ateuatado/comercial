<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3">Suspeita #<?= $flag['id'] ?></h1>
            <p class="text-muted">
                <?= $flag['razao_social'] ? esc($flag['razao_social']) : 'Empresa não identificada' ?>
                · <code><?= esc($flag['cnpj']) ?></code>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($flag['status'] === 'pendente'): ?>
                <a href="/admin/prospecting/<?= $flag['id'] ?>/revisar" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Revisar
                </a>
            <?php endif; ?>
            <a href="/admin/prospecting" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (session()->has('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= session('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= session('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Evidências da suspeita -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Evidências</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <?php
                                $statusClass = match ($flag['status']) {
                                    'pendente'  => 'badge bg-warning text-dark',
                                    'liberado'  => 'badge bg-success',
                                    'rejeitado' => 'badge bg-danger',
                                    default     => 'badge bg-secondary',
                                };
                            ?>
                            <span class="<?= $statusClass ?>"><?= ucfirst($flag['status']) ?></span>
                        </dd>

                        <dt class="col-sm-4">CNPJ</dt>
                        <dd class="col-sm-8"><code><?= esc($flag['cnpj']) ?></code></dd>

                        <dt class="col-sm-4">CPF do Sócio</dt>
                        <dd class="col-sm-8"><code><?= esc($flag['cpf_socio']) ?></code></dd>

                        <?php if ($flag['cnpj_relacionado']): ?>
                            <dt class="col-sm-4">CNPJ Relacionado</dt>
                            <dd class="col-sm-8"><code><?= esc($flag['cnpj_relacionado']) ?></code></dd>
                        <?php endif; ?>

                        <dt class="col-sm-4">Registrado em</dt>
                        <dd class="col-sm-8"><small><?= date('d/m/Y \à\s H:i', strtotime($flag['created_at'])) ?></small></dd>

                        <?php if ($flag['analisado_em']): ?>
                            <dt class="col-sm-4">Analisado em</dt>
                            <dd class="col-sm-8"><small><?= date('d/m/Y \à\s H:i', strtotime($flag['analisado_em'])) ?></small></dd>
                        <?php endif; ?>

                        <dt class="col-sm-4 mt-3">Motivo</dt>
                        <dd class="col-sm-8 mt-3">
                            <p class="mb-0"><?= nl2br(esc($flag['motivo'])) ?></p>
                        </dd>

                        <?php if ($flag['complemento']): ?>
                            <dt class="col-sm-4 mt-3">Complemento</dt>
                            <dd class="col-sm-8 mt-3">
                                <p class="mb-0 text-muted"><?= nl2br(esc($flag['complemento'])) ?></p>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Histórico de revisões -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histórico de Revisões</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted mb-0">Nenhuma revisão registrada.</p>
                    <?php else: ?>
                        <div class="spiv-timeline">
                            <?php foreach ($reviews as $rev): ?>
                                <?php $decClass = $rev['decisao'] === 'liberado' ? 'success' : 'danger'; ?>
                                <div class="spiv-timeline-item">
                                    <div class="spiv-timeline-badge bg-<?= $decClass ?>">
                                        <i class="bi bi-<?= $rev['decisao'] === 'liberado' ? 'check-lg' : 'x-lg' ?>"></i>
                                    </div>
                                    <div class="spiv-timeline-body">
                                        <div class="fw-semibold text-<?= $decClass ?>">
                                            <?= ucfirst($rev['decisao']) ?>
                                        </div>
                                        <small class="text-muted d-block mb-1">
                                            <?= esc($rev['revisado_por_nome'] ?? '—') ?> ·
                                            <?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?>
                                        </small>
                                        <p class="mb-0 small"><?= nl2br(esc($rev['justificativa'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?= $this->endSection() ?>
