<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="bi bi-calendar-event-fill me-2 text-primary"></i>Eventos & Feiras</h4>
        <small class="text-muted">Gestão de eventos para prospecção presencial de vendedores</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoEvento">
        <i class="bi bi-plus-lg me-1"></i>Novo Evento
    </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($eventos)): ?>
    <div class="text-center py-5 text-muted card border-0 shadow-sm">
        <i class="bi bi-calendar-x" style="font-size: 48px; color: #cbd5e1;"></i>
        <p class="mt-3 mb-1 fw-semibold">Nenhum evento cadastrado.</p>
        <small>Clique em "Novo Evento" para cadastrar uma feira ou congresso.</small>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($eventos as $ev): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 <?= !$ev['ativo'] ? 'opacity-75 bg-light' : '' ?>">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0"><?= esc($ev['nome']) ?></h5>
                                <?php if ($ev['ativo']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inativo</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($ev['local'])): ?>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-geo-alt me-1"></i><?= esc($ev['local']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="small text-muted mb-3">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php if ($ev['data_inicio']): ?>
                                    <?= date('d/m/Y', strtotime($ev['data_inicio'])) ?>
                                    <?php if ($ev['data_fim']): ?>
                                        até <?= date('d/m/Y', strtotime($ev['data_fim'])) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Data não definida
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                    <i class="bi bi-people-fill me-1"></i><?= (int)$ev['total_contatos'] ?> contatos
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarEvento<?= $ev['id'] ?>" title="Editar Evento">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="<?= site_url('admin/eventos/' . $ev['id'] . '/toggle') ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm <?= $ev['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $ev['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                            <i class="bi <?= $ev['ativo'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                        </button>
                                    </form>
                                    <a href="<?= site_url('admin/eventos/' . $ev['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-map me-1"></i>Ver Lista & Mapa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Editar Evento -->
            <div class="modal fade" id="modalEditarEvento<?= $ev['id'] ?>" tabindex="-1" aria-labelledby="modalEditarEventoLabel<?= $ev['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="<?= site_url('admin/eventos/' . $ev['id'] . '/editar') ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEditarEventoLabel<?= $ev['id'] ?>"><i class="bi bi-pencil-square me-2"></i>Editar Evento / Feira</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-start">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nome do Evento <span class="text-danger">*</span></label>
                                    <input type="text" name="nome" class="form-control" value="<?= esc($ev['nome']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Local / Cidade</label>
                                    <input type="text" name="local" class="form-control" value="<?= esc($ev['local'] ?? '') ?>" placeholder="Ex: Anhembi - São Paulo/SP">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Data de Início</label>
                                        <input type="date" name="data_inicio" class="form-control" value="<?= esc($ev['data_inicio'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Data de Término</label>
                                        <input type="date" name="data_fim" class="form-control" value="<?= esc($ev['data_fim'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Novo Evento -->
<div class="modal fade" id="modalNovoEvento" tabindex="-1" aria-labelledby="modalNovoEventoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= site_url('admin/eventos/novo') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoEventoLabel"><i class="bi bi-calendar-plus me-2"></i>Novo Evento / Feira</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Evento <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Feira Internacional de Logística 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local / Cidade</label>
                        <input type="text" name="local" class="form-control" placeholder="Ex: Expo Center Norte - São Paulo/SP">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Início</label>
                            <input type="date" name="data_inicio" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Término</label>
                            <input type="date" name="data_fim" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Cadastrar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
