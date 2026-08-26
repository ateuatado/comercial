<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-2">Vendedor Eventual</h1>
        <p class="text-muted mb-0">Escolha uma campanha disponível para iniciar sua participação voluntária.</p>
    </div>

    <?php if (session('success')): ?><div class="alert alert-success" role="status"><?= esc(session('success')) ?></div><?php endif ?>
    <?php if (session('error')): ?><div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div><?php endif ?>

    <?php if ($campaigns === []): ?>
        <div class="alert alert-info">Não há campanhas ativas e vigentes disponíveis para você neste momento.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($campaigns as $campaign): ?>
                <?php $started = ! empty($campaign['enrollment_id']); ?>
                <div class="col-12 col-lg-6">
                    <article class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <span class="badge text-bg-primary mb-2"><?= esc($campaign['code']) ?></span>
                                    <h2 class="h5 mb-1"><?= esc($campaign['name']) ?></h2>
                                    <p class="text-muted small mb-0">Campanha demonstrativa, sem geração de direito financeiro.</p>
                                </div>
                                <?php if ($started): ?><span class="badge text-bg-success">Adesão iniciada</span><?php endif ?>
                            </div>

                            <dl class="row small mb-4">
                                <dt class="col-4">Vigência</dt>
                                <dd class="col-8 mb-1"><?= esc(date('d/m/Y', strtotime((string) $campaign['starts_at']))) ?> a <?= esc(date('d/m/Y', strtotime((string) $campaign['ends_at']))) ?></dd>
                                <dt class="col-4">Participação</dt>
                                <dd class="col-8 mb-0">Voluntária e adicional ao perfil funcional</dd>
                            </dl>

                            <?php if (! $started): ?>
                                <form method="post" action="<?= site_url('vendedor-eventual/campanhas/' . $campaign['id'] . '/aderir') ?>" class="mt-auto">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-primary">Quero ser Vendedor Eventual</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning mt-auto mb-0" role="status">
                                    <strong>Próxima etapa: capacitação.</strong><br>
                                    O conteúdo, a avaliação e os termos ainda aguardam validação para publicação. Sua adesão foi preservada e nenhuma habilitação comercial foi concedida automaticamente.
                                </div>
                            <?php endif ?>
                        </div>
                    </article>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
