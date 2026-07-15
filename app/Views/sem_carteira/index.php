<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid p-0" style="max-width: 480px; margin: 0 auto;">

    <!-- Tela informativa para funcionários sem carteira -->
    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 80vh; padding: 24px;">

        <div class="text-center mb-4">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #e2e8f0, #f1f5f9); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="bi bi-info-circle" style="font-size: 36px; color: #64748b;"></i>
            </div>
        </div>

        <div class="card border-0 shadow-sm w-100" style="border-radius: 16px;">
            <div class="card-body p-4">
                <h5 class="fw-bold text-center mb-3"><?= esc($titulo) ?></h5>
                <div class="system-message-content">
                    <?= $conteudo /* HTML do admin — já sanitizado na entrada */ ?>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="<?= site_url('logout') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-left me-1"></i> Sair
            </a>
        </div>

        <div class="mt-4 text-center">
            <small class="text-muted">SPIV — Sistema de Prospecção e Inteligência de Vendas</small>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
