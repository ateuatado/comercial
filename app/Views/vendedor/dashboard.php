<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container py-4">

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Saudação -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 bg-primary text-white" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Olá, <?= esc($vendorUser['nome']) ?> 👋</h4>
                            <p class="mb-0 opacity-75">
                                <?= esc($vendorUser['perfil_vendedor'] ?? 'Vendedor') ?>
                                · <?= esc($vendorUser['se'] ?? '') ?>
                                <?php if ($vendorUser['gerencia']): ?>
                                    · <?= esc($vendorUser['gerencia']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <small class="opacity-75">Matrícula</small><br>
                            <span class="fw-bold"><?= esc($vendorUser['matricula']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-primary"><?= number_format($totalClientes, 0, ',', '.') ?></div>
                    <small class="text-muted">Clientes</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-success"><?= $segmentos ?></div>
                    <small class="text-muted">Segmentos</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-warning"><?= count($categorias) ?></div>
                    <small class="text-muted">Categorias</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorias -->
    <?php if (!empty($categorias)): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-award"></i> Categorias</h6>
            <?php foreach ($categorias as $cat): ?>
                <?php
                    $colors = [
                        'BRONZE' => '#cd7f32', 'OURO' => '#ffd700', 'PRATA' => '#c0c0c0',
                        'DIAMANTE' => '#185abc', 'PLATINUM' => '#6b21a8', 'INFINITE' => '#1e293b',
                        'CLUBE' => '#22c55e'
                    ];
                    $color = $colors[strtoupper($cat['categoria'] ?? '')] ?? '#64748b';
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>
                        <span class="badge me-2" style="background:<?= $color ?>;"><?= esc($cat['categoria']) ?></span>
                    </span>
                    <span class="fw-bold"><?= number_format($cat['total'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ciclo de Vida -->
    <?php if (!empty($ciclos)): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-arrow-repeat"></i> Ciclo de Vida</h6>
            <?php foreach ($ciclos as $ciclo): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?= esc($ciclo['ciclo_de_vida']) ?></span>
                    <span class="fw-bold"><?= number_format($ciclo['total'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ação principal -->
    <div class="d-grid gap-2 mb-4">
        <a href="<?= site_url('vendedor/clientes') ?>" class="btn btn-primary btn-lg" style="border-radius: 12px;">
            <i class="bi bi-people-fill me-2"></i> Ver Meus Clientes
        </a>
        <?php if ($isCoordenador): ?>
            <a href="<?= site_url('coordenador') ?>" class="btn btn-outline-primary btn-lg" style="border-radius: 12px;">
                <i class="bi bi-diagram-3 me-2"></i> Visão do Time
            </a>
        <?php endif; ?>
    </div>

    <!-- Últimas Notas -->
    <?php if (!empty($ultimasNotas)): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-journal-text"></i> Últimas Notas</h6>
            <?php
                $tipoIcons = ['visita' => '🟢', 'observacao' => '🔵', 'contato_telefonico' => '🟠', 'reuniao' => '🟣', 'estrategia' => '⚡'];
            ?>
            <?php foreach ($ultimasNotas as $nota): ?>
                <div class="d-flex gap-2 mb-2 pb-2 border-bottom">
                    <span><?= $tipoIcons[$nota['tipo']] ?? '📝' ?></span>
                    <div class="flex-grow-1">
                        <small class="text-muted"><?= esc($nota['cnpj']) ?> · <?= date('d/m H:i', strtotime($nota['created_at'])) ?></small>
                        <div class="small"><?= esc(mb_strimwidth($nota['conteudo'], 0, 80, '...')) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
