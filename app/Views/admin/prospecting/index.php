<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">

    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3">Prospecção Antifraude</h1>
            <p class="text-muted">Suspeitas registradas com base em CPF de sócios e CNPJs com histórico problemático.</p>
        </div>
        <a href="/admin/prospecting/nova" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nova Suspeita
        </a>
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

    <!-- Filtros de status -->
    <div class="btn-group mb-3" role="group" aria-label="Filtrar por status">
        <button type="button" class="btn btn-outline-secondary active" data-filter-status="todos">Todos</button>
        <button type="button" class="btn btn-outline-warning"          data-filter-status="pendente">Pendentes</button>
        <button type="button" class="btn btn-outline-success"          data-filter-status="liberado">Liberados</button>
        <button type="button" class="btn btn-outline-danger"           data-filter-status="rejeitado">Rejeitados</button>
    </div>

    <!-- Tabela de suspeitas -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:14%">CNPJ</th>
                        <th style="width:25%">Empresa</th>
                        <th style="width:12%">CPF Sócio</th>
                        <th style="width:10%">Status</th>
                        <th style="width:14%">Registrado em</th>
                        <th style="width:20%">Motivo (resumo)</th>
                        <th style="width:6%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flags)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-shield-check me-2"></i>Nenhuma suspeita registrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($flags as $flag): ?>
                            <?php
                                $statusClass = match ($flag['status']) {
                                    'pendente'  => 'badge bg-warning text-dark',
                                    'liberado'  => 'badge bg-success',
                                    'rejeitado' => 'badge bg-danger',
                                    default     => 'badge bg-secondary',
                                };
                            ?>
                            <tr data-row-status="<?= esc($flag['status']) ?>">
                                <td><small class="text-muted"><?= $flag['id'] ?></small></td>
                                <td><code><?= esc($flag['cnpj']) ?></code></td>
                                <td>
                                    <small><?= $flag['razao_social'] ? esc($flag['razao_social']) : '<em class="text-muted">Não encontrada</em>' ?></small>
                                </td>
                                <td><code><?= esc($flag['cpf_socio']) ?></code></td>
                                <td><span class="<?= $statusClass ?>"><?= ucfirst($flag['status']) ?></span></td>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($flag['created_at'])) ?></small></td>
                                <td>
                                    <small class="text-muted spiv-text-truncate" title="<?= esc($flag['motivo']) ?>">
                                        <?= esc(mb_strimwidth($flag['motivo'], 0, 60, '…')) ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="/admin/prospecting/<?= $flag['id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Ver detalhe"
                                       data-bs-toggle="tooltip">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($flag['status'] === 'pendente'): ?>
                                        <a href="/admin/prospecting/<?= $flag['id'] ?>/revisar"
                                           class="btn btn-sm btn-outline-warning ms-1"
                                           title="Revisar"
                                           data-bs-toggle="tooltip">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/prospecting.js') ?>"></script>
<?= $this->endSection() ?>
