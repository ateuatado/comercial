<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Vendedores</h1>
        <a href="/admin/vendors/novo" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Novo Vendedor
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>
    <?php if (session()->getFlashdata('info')): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('info')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Estado/SE</th>
                        <th>Lotação</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendors)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nenhum vendedor cadastrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendors as $v): ?>
                            <tr class="<?= $v['ativo'] ? '' : 'text-muted' ?>">
                                <td><?= esc($v['matricula']) ?></td>
                                <td><?= esc($v['nome']) ?></td>
                                <td>
                                    <?php if ($v['tipo_acom']): ?>
                                        <span class="badge bg-secondary">ACOM <?= esc($v['tipo_acom']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Gerente de Conta</span>
                                    <?php endif ?>
                                </td>
                                <td><?= esc($v['estado_se'] ?? '—') ?></td>
                                <td><?= esc($v['lotacao'] ?? '—') ?></td>
                                <td>
                                    <?php if ($v['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Inativo</span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end">
                                    <a href="/admin/vendors/<?= $v['id'] ?>/editar"
                                       class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($v['ativo']): ?>
                                        <form method="POST"
                                              action="/admin/vendors/<?= $v['id'] ?>/desativar"
                                              class="d-inline"
                                              onsubmit="return confirm('Desativar <?= esc($v['nome'], 'js') ?>?')">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
