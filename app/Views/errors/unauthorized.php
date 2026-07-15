<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5 text-center">
    <div class="card border-danger shadow-sm mx-auto" style="max-width: 500px;">
        <div class="card-body py-5">
            <i class="bi bi-exclamation-octagon text-danger mb-3" style="font-size: 3rem;"></i>
            <h2 class="card-title h4 text-danger">Acesso Não Autorizado</h2>
            <p class="card-text text-muted">
                <?= isset($message) ? esc($message) : 'Você não tem permissão para acessar esta página.' ?>
            </p>
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary mt-3">Voltar ao Início</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
