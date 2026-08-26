<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-3">Fundação do Vendedor Eventual</h1>

    <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>

    <div class="alert <?= $featureEnabled ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center gap-2" role="status">
        <i class="bi <?= $featureEnabled ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>" aria-hidden="true"></i>
        <div>
            <strong>Trava global:</strong>
            <code>VENDOR_EVENTUAL_ENABLED</code> está
            <strong><?= $featureEnabled ? 'ligada' : 'desligada' ?></strong> neste ambiente.
            <?php if (! $featureEnabled): ?>A habilitação da aplicação no quadro abaixo não libera o acesso enquanto essa configuração estiver desligada.<?php endif ?>
        </div>
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

    <div class="card mt-4">
        <div class="card-header">Participações dos empregados</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Empregado</th><th>Campanha</th><th>Estado</th><th>Motivo</th><th>Ação administrativa</th></tr></thead>
                <tbody>
                <?php if ($enrollments === []): ?><tr><td colspan="5" class="text-muted">Nenhuma participação registrada.</td></tr><?php endif ?>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?= esc($enrollment['employee_code'] . ' — ' . $enrollment['display_name']) ?></td>
                        <td><?= esc($enrollment['campaign_name']) ?></td>
                        <td><span class="badge text-bg-<?= $enrollment['status'] === 'qualified' ? 'success' : ($enrollment['status'] === 'suspended' ? 'danger' : 'secondary') ?>"><?= esc($enrollment['status']) ?></span></td>
                        <td><?= esc($enrollment['status_reason'] ?? '—') ?></td>
                        <td>
                            <?php if (! in_array($enrollment['status'], ['suspended', 'closed'], true)): ?>
                                <form method="post" action="<?= site_url('admin/vendedor-eventual/adesoes/' . $enrollment['id'] . '/suspender') ?>" class="d-flex gap-2">
                                    <?= csrf_field() ?><input class="form-control form-control-sm" name="reason" required maxlength="500" placeholder="Motivo obrigatório"><button class="btn btn-sm btn-outline-danger">Suspender</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header">Nova versão de capacitação e termos</div>
                <div class="card-body">
                    <div class="alert alert-info small">O conteúdo só fica disponível ao empregado depois de publicado.</div>
                    <form method="post" action="<?= site_url('admin/vendedor-eventual/capacitacoes') ?>" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-8"><label class="form-label">Campanha</label><select class="form-select" name="campaign_id" required><?php foreach ($campaigns as $campaign): ?><option value="<?= $campaign['id'] ?>"><?= esc($campaign['name']) ?></option><?php endforeach ?></select></div>
                        <div class="col-md-4"><label class="form-label">Versão</label><input class="form-control" name="version" placeholder="v1" required maxlength="60"></div>
                        <div class="col-12"><label class="form-label">Título</label><input class="form-control" name="title" required maxlength="180"></div>
                        <div class="col-12"><label class="form-label">Conteúdo da capacitação</label><textarea class="form-control" name="training_content" rows="6" required></textarea></div>
                        <div class="col-12"><label class="form-label">Termos de participação</label><textarea class="form-control" name="terms_content" rows="6" required></textarea></div>
                        <div class="col-12"><label class="form-label">Pergunta da avaliação</label><input class="form-control" name="assessment_question" required></div>
                        <div class="col-md-6"><label class="form-label">Alternativa A</label><input class="form-control" name="assessment_options[]" required></div>
                        <div class="col-md-6"><label class="form-label">Alternativa B</label><input class="form-control" name="assessment_options[]" required></div>
                        <div class="col-md-6"><label class="form-label">Alternativa C (opcional)</label><input class="form-control" name="assessment_options[]"></div>
                        <div class="col-md-6"><label class="form-label">Resposta correta</label><select class="form-select" name="correct_option" required><option value="0">Alternativa A</option><option value="1">Alternativa B</option><option value="2">Alternativa C</option></select></div>
                        <div class="col-12"><button class="btn btn-primary">Salvar rascunho</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">Versões cadastradas</div>
                <div class="list-group list-group-flush">
                    <?php if ($learningVersions === []): ?><div class="list-group-item text-muted">Nenhuma versão cadastrada.</div><?php endif ?>
                    <?php foreach ($learningVersions as $version): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-3"><div><strong><?= esc($version['title']) ?></strong><br><small><?= esc($version['campaign_name'] . ' · ' . $version['version']) ?></small></div><span class="badge text-bg-<?= $version['status'] === 'published' ? 'success' : 'secondary' ?>"><?= esc($version['status']) ?></span></div>
                            <?php if ($version['status'] === 'draft'): ?><form method="post" action="<?= site_url('admin/vendedor-eventual/capacitacoes/' . $version['id'] . '/publicar') ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-sm btn-success">Publicar versão</button></form><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-xl-7"><div class="card"><div class="card-header">Novo produto versionado</div><div class="card-body"><div class="alert alert-warning small">Publique somente conteúdo validado pelo responsável do portfólio. Não informe preços ou condições não aprovadas.</div><form method="post" action="<?= site_url('admin/vendedor-eventual/catalogo/produtos') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-8"><label class="form-label">Campanha</label><select class="form-select" name="campaign_id" required><?php foreach ($campaigns as $campaign): ?><option value="<?= $campaign['id'] ?>"><?= esc($campaign['name']) ?></option><?php endforeach ?></select></div><div class="col-md-4"><label class="form-label">Versão</label><input class="form-control" name="version" required placeholder="v1"></div><div class="col-md-6"><label class="form-label">Nome simples</label><input class="form-control" name="name" required></div><div class="col-md-6"><label class="form-label">Nome oficial (opcional)</label><input class="form-control" name="official_name"></div><?php foreach (['problem_solved'=>'Problema resolvido','target_profile'=>'Perfil indicado','benefits'=>'Benefícios','restrictions'=>'Restrições','requirements'=>'Requisitos','documents'=>'Documentos','sales_script'=>'Roteiro aprovado','faq'=>'Perguntas frequentes'] as $field => $label): ?><div class="col-md-6"><label class="form-label"><?= esc($label) ?></label><textarea class="form-control" name="<?= $field ?>" rows="3" required></textarea></div><?php endforeach ?><div class="col-12"><button class="btn btn-primary">Salvar rascunho</button></div></form></div></div></div>
        <div class="col-12 col-xl-5"><div class="card mb-4"><div class="card-header">Produtos cadastrados</div><div class="list-group list-group-flush"><?php if ($productVersions === []): ?><div class="list-group-item text-muted">Nenhum produto cadastrado.</div><?php endif ?><?php foreach ($productVersions as $product): ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?= esc($product['name']) ?></strong><br><small><?= esc($product['campaign_name'] . ' · ' . $product['version']) ?></small></div><div class="text-end"><span class="badge text-bg-<?= $product['status'] === 'published' ? 'success' : 'secondary' ?>"><?= esc($product['status']) ?></span><?php if ($product['status'] === 'draft'): ?><form method="post" action="<?= site_url('admin/vendedor-eventual/catalogo/produto/' . $product['id'] . '/publicar') ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-sm btn-success">Publicar</button></form><?php endif ?></div></div><?php endforeach ?></div></div>
        <div class="card"><div class="card-header">Questionário versionado</div><div class="card-body"><form method="post" action="<?= site_url('admin/vendedor-eventual/catalogo/questionarios') ?>" class="row g-3"><?= csrf_field() ?><div class="col-8"><label class="form-label">Campanha</label><select class="form-select" name="campaign_id" required><?php foreach ($campaigns as $campaign): ?><option value="<?= $campaign['id'] ?>"><?= esc($campaign['name']) ?></option><?php endforeach ?></select></div><div class="col-4"><label class="form-label">Versão</label><input class="form-control" name="version" required placeholder="v1"></div><div class="col-12"><label class="form-label">Título</label><input class="form-control" name="title" required></div><div class="col-12"><label class="form-label">Perguntas (JSON)</label><textarea class="form-control font-monospace" name="questions" rows="5" required placeholder='[{&quot;id&quot;:&quot;q1&quot;,&quot;text&quot;:&quot;Pergunta validada?&quot;,&quot;options&quot;:[&quot;sim&quot;,&quot;não&quot;]}]'></textarea></div><div class="col-12"><label class="form-label">Regras (JSON)</label><textarea class="form-control font-monospace" name="recommendation_rules" rows="5" required placeholder='[{&quot;when&quot;:{&quot;q1&quot;:&quot;sim&quot;},&quot;products&quot;:[&quot;Produto validado&quot;],&quot;reason&quot;:&quot;Motivo aprovado&quot;}]'></textarea></div><div class="col-12"><button class="btn btn-primary">Salvar rascunho</button></div></form></div><div class="list-group list-group-flush"><?php foreach ($questionnaireVersions as $questionnaire): ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?= esc($questionnaire['title']) ?></strong><br><small><?= esc($questionnaire['campaign_name'] . ' · ' . $questionnaire['version']) ?></small></div><div class="text-end"><span class="badge text-bg-<?= $questionnaire['status'] === 'published' ? 'success' : 'secondary' ?>"><?= esc($questionnaire['status']) ?></span><?php if ($questionnaire['status'] === 'draft'): ?><form method="post" action="<?= site_url('admin/vendedor-eventual/catalogo/questionario/' . $questionnaire['id'] . '/publicar') ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-sm btn-success">Publicar</button></form><?php endif ?></div></div><?php endforeach ?></div></div></div>
    </div>
</div>
<?= $this->endSection() ?>
