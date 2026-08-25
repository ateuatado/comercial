<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-2">Minhas aplicações</h1>
    <p class="text-muted">
        <?= esc($employee['display_name'] ?? auth()->user()->username) ?>
    </p>

    <?php if ($applications === []): ?>
        <div class="alert alert-info">
            Você não possui aplicações habilitadas neste momento.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($applications as $application): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5"><?= esc($application['name']) ?></h2>
                            <p class="text-muted"><?= esc($application['description'] ?? '') ?></p>
                            <?php if ($application['code'] === 'vendedor_eventual'): ?>
                                <a class="btn btn-primary" href="<?= site_url('vendedor-eventual') ?>">Acessar</a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
