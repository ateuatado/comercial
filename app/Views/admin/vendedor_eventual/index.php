<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h1 class="h3 mb-3">Fundação do Vendedor Eventual</h1>

    <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>

    <div class="alert alert-warning">
        A liberação efetiva também depende de <code>VENDOR_EVENTUAL_ENABLED</code>. A configuração permanece desligada por padrão.
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card mb-4">
                <div class="card-header">Aplicações</div>
                <div class="card-body">
                    <?php foreach ($applications as $application): ?>
                        <form method="post" action="<?= site_url('admin/vendedor-eventual/aplicacoes/' . $application['id'] . '/estado') ?>" class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <?= csrf_field() ?>
                            <span><?= esc($application['name']) ?></span>
                            <input type="hidden" name="enabled" value="<?= $application['enabled'] ? 'false' : 'true' ?>">
                            <button class="btn btn-sm <?= $application['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= $application['enabled'] ? 'Desabilitar' : 'Habilitar' ?>
                            </button>
                        </form>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Nova campanha demonstrativa</div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('admin/vendedor-eventual/campanhas') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3"><label class="form-label">Código</label><input class="form-control" name="code" required maxlength="60"></div>
                        <div class="mb-3"><label class="form-label">Nome</label><input class="form-control" name="name" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="starts_at"></div>
                        <div class="mb-3"><label class="form-label">Término</label><input class="form-control" type="datetime-local" name="ends_at" required></div>
                        <button class="btn btn-primary">Criar rascunho</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card mb-4">
                <div class="card-header">Campanhas</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Código</th><th>Nome</th><th>Estado</th><th>Próximo estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><?= esc($campaign['code']) ?></td><td><?= esc($campaign['name']) ?></td><td><?= esc($campaign['status']) ?></td>
                                <td>
                                    <form method="post" action="<?= site_url('admin/vendedor-eventual/campanhas/' . $campaign['id'] . '/estado') ?>" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <select class="form-select form-select-sm" name="status" required>
                                            <?php foreach (['published','active','suspended','closed','archived'] as $status): ?><option value="<?= $status ?>"><?= $status ?></option><?php endforeach ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">Aplicar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Conceder acesso</div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('admin/vendedor-eventual/concessoes') ?>" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-6"><label class="form-label">Empregado</label><select class="form-select" name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= $employee['id'] ?>"><?= esc($employee['employee_id'] . ' — ' . $employee['display_name']) ?></option><?php endforeach ?></select></div>
                        <div class="col-md-6"><label class="form-label">Aplicação</label><select class="form-select" name="application_id" required><?php foreach ($applications as $application): ?><option value="<?= $application['id'] ?>"><?= esc($application['name']) ?></option><?php endforeach ?></select></div>
                        <div class="col-md-4"><label class="form-label">Origem</label><select class="form-select" name="source"><option value="administrator">Administrador</option><option value="campaign">Campanha</option></select></div>
                        <div class="col-md-8"><label class="form-label">Campanha</label><select class="form-select" name="campaign_id"><option value="">Sem campanha</option><?php foreach ($campaigns as $campaign): ?><option value="<?= $campaign['id'] ?>"><?= esc($campaign['name']) ?></option><?php endforeach ?></select></div>
                        <div class="col-md-6"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="valid_from"></div>
                        <div class="col-md-6"><label class="form-label">Término</label><input class="form-control" type="datetime-local" name="valid_until"></div>
                        <div class="col-12"><label class="form-label">Justificativa</label><input class="form-control" name="reason" maxlength="500"></div>
                        <div class="col-12"><button class="btn btn-primary">Conceder</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Concessões recentes</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Empregado</th><th>Aplicação</th><th>Origem</th><th>Campanha</th><th>Validade</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($entitlements as $entitlement): ?>
                    <tr>
                        <td><?= esc($entitlement['employee_code'] . ' — ' . $entitlement['display_name']) ?></td>
                        <td><?= esc($entitlement['application_name']) ?></td><td><?= esc($entitlement['source']) ?></td>
                        <td><?= esc($entitlement['campaign_name'] ?? '—') ?></td><td><?= esc($entitlement['valid_until'] ?? 'sem término') ?></td><td><?= esc($entitlement['status']) ?></td>
                        <td>
                            <?php if ($entitlement['status'] === 'active'): ?>
                                <form method="post" action="<?= site_url('admin/vendedor-eventual/concessoes/' . $entitlement['id'] . '/revogar') ?>" class="d-flex gap-2">
                                    <?= csrf_field() ?><input class="form-control form-control-sm" name="reason" placeholder="Motivo"><button class="btn btn-sm btn-outline-danger">Revogar</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
