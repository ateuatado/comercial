<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-5">

    <!-- Cabeçalho -->
    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3">Minha Carteira</h1>
            <p class="text-muted">
                <?= esc($vendor['nome']) ?>
                (<?= esc($vendor['tipo_acom'] ?? 'Gerente de Conta') ?>) ·
                <strong><?= $total ?></strong> cliente(s) atribuído(s)
            </p>
        </div>
    </div>

    <!-- Mensagens de sessão -->
    <?php if (session()->has('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= session('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= session('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <!-- Card de resumo -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total</h5>
                    <h2 class="card-text"><?= $total ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de clientes -->
    <?php if (empty($clients)): ?>
        <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle me-2"></i>Nenhum cliente atribuído ainda.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width:13%">CNPJ</th>
                        <th style="width:28%">Razão Social</th>
                        <th style="width:13%">Status</th>
                        <th style="width:11%">Atribuído em</th>
                        <th style="width:11%">Ult. atualiz.</th>
                        <th style="width:10%">Prospecção</th>
                        <th style="width:6%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <?php
                            $statusClass = match ($client['status_operacional']) {
                                'novo'              => 'badge bg-secondary',
                                'em_acompanhamento' => 'badge bg-primary',
                                'convertido'        => 'badge bg-success',
                                'sem_contato'       => 'badge bg-warning text-dark',
                                'bloqueado'         => 'badge bg-danger',
                                'inativo'           => 'badge bg-dark',
                                default             => 'badge bg-light text-dark',
                            };
                            $statusLabel = ucfirst(str_replace('_', ' ', $client['status_operacional']));
                        ?>
                        <tr class="align-middle">
                            <td><code><?= esc($client['cnpj']) ?></code></td>
                            <td>
                                <small class="text-muted d-block">
                                    <?= $client['razao_social'] ? esc($client['razao_social']) : '<em>Não encontrada</em>' ?>
                                </small>
                                <?php if ($client['capital_social']): ?>
                                    <small class="text-muted">
                                        Capital: R$ <?= number_format((float) $client['capital_social'], 2, ',', '.') ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= $statusClass ?>"><?= esc($statusLabel) ?></span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $client['atribuido_em'] ? date('d/m/Y', strtotime($client['atribuido_em'])) : '—' ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $client['updated_at'] ? date('d/m/Y H:i', strtotime($client['updated_at'])) : '—' ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($client['motivo_suspeita']): ?>
                                    <span class="badge bg-danger"
                                          title="<?= esc($client['motivo_suspeita']) ?>"
                                          data-bs-toggle="tooltip">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </span>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Atributos data-* lidos pelo carteira.js — sem inline JS -->
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-status"
                                        data-cnpj="<?= esc($client['cnpj']) ?>"
                                        data-status="<?= esc($client['status_operacional']) ?>"
                                        title="Atualizar status"
                                        aria-label="Atualizar status de <?= esc($client['cnpj']) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<!-- Modal de atualização de status -->
<!-- IDs estáveis usados pelo carteira.js -->
<div class="modal fade" id="modal-status" tabindex="-1" aria-labelledby="modal-status-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-status-label">Atualizar Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= url_to('carteira_update_status') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal-cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control font-monospace" id="modal-cnpj" name="cnpj" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="modal-status-atual" class="form-label">Status Atual</label>
                        <input type="text" class="form-control" id="modal-status-atual" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="modal-status-novo" class="form-label">Novo Status</label>
                        <select class="form-select" id="modal-status-novo" name="status_novo" required>
                            <option value="">— Selecione —</option>
                        </select>
                        <div class="form-text">Transições disponíveis conforme seu perfil.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/carteira.js') ?>"></script>
<?= $this->endSection() ?>
