<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">
    <?php if (session('success')): ?>
        <div class="alert alert-success" role="status"><?= esc(session('success')) ?></div>
    <?php endif ?>
    <?php if (session('error')): ?>
        <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
    <?php endif ?>

    <div class="d-flex justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Oportunidade</h1>
            <code><?= esc($opportunity['correlation_id']) ?></code>
        </div>
        <a class="btn btn-outline-secondary align-self-start" href="<?= site_url('vendedor-eventual') ?>">Voltar</a>
    </div>

    <div class="alert alert-primary">
        <strong>Objetivo desta jornada:</strong> ajudar qualquer empregado habilitado a identificar a necessidade,
        consultar a situação do cliente, aplicar o diagnóstico e encaminhar a solução adequada sem perder sua autoria.
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-header">Dados iniciais</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>CNPJ</dt><dd><?= esc($opportunity['cnpj']) ?></dd>
                        <dt>Campanha</dt><dd><?= esc($opportunity['campaign_name']) ?></dd>
                        <dt>Canal</dt><dd><?= esc($opportunity['channel']) ?></dd>
                        <dt>Estado</dt><dd><?= esc($opportunity['status']) ?></dd>
                        <dt>Questionário vinculado</dt><dd><?= esc($opportunity['questionnaire_version'] ?? 'nenhum publicado no registro') ?></dd>
                        <dt>Contexto</dt><dd class="mb-0"><?= nl2br(esc($opportunity['contact_context'])) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Situação na carteira</div>
                <div class="card-body">
                    <div class="alert alert-<?= $opportunity['portfolio']['state'] === 'assigned' ? 'warning' : ($opportunity['portfolio']['state'] === 'unavailable' ? 'secondary' : 'success') ?> mb-3">
                        <strong><?= esc($opportunity['portfolio']['label']) ?></strong>
                    </div>
                    <?php if ($opportunity['portfolio']['state'] === 'assigned'): ?>
                        <dl class="mb-3">
                            <dt>Responsável</dt><dd><?= esc($opportunity['portfolio']['responsible_name'] ?? 'Não informado') ?></dd>
                            <dt>Unidade</dt><dd><?= esc($opportunity['portfolio']['responsible_unit'] ?? 'Não informada') ?></dd>
                            <dt>Estado operacional</dt><dd><?= esc($opportunity['portfolio']['operational_status'] ?? 'Não informado') ?></dd>
                        </dl>
                        <p class="small text-muted mb-0">A existência de carteira não bloqueia o atendimento. O responsável poderá ser envolvido na continuidade.</p>
                    <?php elseif ($opportunity['portfolio']['state'] === 'unavailable'): ?>
                        <p class="small text-muted mb-0">O registro da oportunidade permanece disponível mesmo sem integração com a carteira.</p>
                    <?php else: ?>
                        <p class="small text-muted mb-0">A oportunidade pode prosseguir e será usada para solicitar inclusão provisória em carteira.</p>
                    <?php endif ?>
                </div>
            </div>

            <?php if ($opportunity['portfolio_request'] === null): ?>
                <div class="card mt-4 border-primary">
                    <div class="card-header">Solicitação provisória de carteira</div>
                    <div class="card-body">
                        <p>Esta solicitação preserva a autoria e cria uma reserva técnica para análise. Ela não altera responsável, distribuição nem dados da carteira atual.</p>
                        <form method="post" action="<?= site_url('vendedor-eventual/oportunidades/' . $opportunity['id'] . '/solicitacao-carteira') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-primary">Solicitar análise de carteira</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mt-4 border-info">
                    <div class="card-header">Solicitação provisória registrada</div>
                    <div class="card-body">
                        <p class="mb-1">Referência da solicitação: <code><?= esc($opportunity['portfolio_request']['request_id']) ?></code></p>
                        <p class="mb-0">Reserva técnica: <code><?= esc($opportunity['portfolio_request']['reservation_reference']) ?></code>. Aguarde a análise; nenhuma carteira foi transferida.</p>
                    </div>
                </div>
            <?php endif ?>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-header">Próximas ações comerciais</div>
                <div class="card-body">
                    <ol class="mb-3">
                        <li>Confirmar os dados públicos da empresa.</li>
                        <li>Aplicar o questionário de diagnóstico.</li>
                        <li>Apresentar até três soluções recomendadas e registrar o interesse.</li>
                    </ol>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary" href="<?= site_url('vendedor-eventual/campanhas/' . $opportunity['campaign_id'] . '/catalogo') ?>">Consultar catálogo</a>
                        <?php if ($opportunity['diagnostic'] === null): ?>
                            <a class="btn btn-success" href="<?= site_url('vendedor-eventual/oportunidades/' . $opportunity['id'] . '/diagnostico') ?>">Iniciar diagnóstico</a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary" disabled>Diagnóstico concluído</button>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Linha do tempo</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($opportunity['events'] as $event): ?>
                        <?php $eventLabel = match ($event['event_type']) {
                            'portfolio_request_created' => 'Solicitação provisória de carteira registrada',
                            'diagnostic_completed' => 'Diagnóstico concluído',
                            default => 'Oportunidade registrada',
                        }; ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-3">
                                <strong><?= esc($eventLabel) ?></strong>
                                <small><?= esc(date('d/m/Y H:i', strtotime($event['occurred_at']))) ?></small>
                            </div>
                            <small class="text-muted">Evento <?= esc($event['event_id']) ?> · canal <?= esc($event['channel']) ?><?php if ($event['content_version']): ?> · questionário <?= esc($event['content_version']) ?><?php endif ?></small>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($opportunity['diagnostic'] !== null): ?>
    <div class="container pb-4">
        <div class="card border-success">
            <div class="card-header">Recomendações do diagnóstico</div>
            <div class="card-body">
                <?php if ($opportunity['diagnostic']['recommendations'] === []): ?>
                    <div class="alert alert-info mb-0">Nenhum produto publicado correspondeu às respostas. Solicite apoio especializado.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($opportunity['diagnostic']['recommendations'] as $recommendation): ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h2 class="h5"><?= esc($recommendation['product']) ?></h2>
                                    <p class="mb-0"><?= esc($recommendation['reason']) ?></p>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
<?php endif ?>

<?= $this->endSection() ?>
