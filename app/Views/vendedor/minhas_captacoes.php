<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .captacoes-container {
        max-width: 480px;
        margin: 0 auto;
        padding: 16px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: #333;
        background-color: #f8f9fa;
        min-height: 100vh;
        position: relative;
    }

    .captacoes-header {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e9ecef;
    }

    .btn-back {
        background: none;
        border: none;
        font-size: 24px;
        color: #495057;
        margin-right: 16px;
        cursor: pointer;
        padding: 0;
    }

    .captacoes-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: #212529;
    }

    .empty-state {
        text-align: center;
        padding: 48px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        display: block;
        color: #dee2e6;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
    }

    .pedido-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 6px solid #dee2e6;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .status-pendente { border-left-color: #ffc107; }
    .status-aprovado { border-left-color: #20c997; }
    .status-rejeitado { border-left-color: #dc3545; }
    .status-mais_info { border-left-color: #0d6efd; }

    .company-name {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        color: #212529;
    }

    .cnpj-formatted {
        font-family: monospace;
        color: #6c757d;
        font-size: 13px;
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        width: fit-content;
        gap: 6px;
    }

    .badge-pendente { background-color: #fff3cd; color: #856404; }
    .badge-aprovado { background-color: #d1e7dd; color: #0f5132; }
    .badge-rejeitado { background-color: #f8d7da; color: #842029; }
    .badge-mais_info { background-color: #cce5ff; color: #004085; }

    .pedido-date {
        font-size: 12px;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .admin-obs-box {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 12px;
        font-size: 13px;
        color: #495057;
        margin-top: 4px;
        border: 1px solid #e9ecef;
    }

    .admin-obs-box strong {
        display: block;
        margin-bottom: 4px;
        color: #212529;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        margin-top: 8px;
        transition: all 0.2s;
    }

    .btn-action-primary {
        background-color: #0d6efd;
        color: white;
    }
    .btn-action-primary:hover { background-color: #0b5ed7; color: white; }

    .btn-action-success {
        background-color: #198754;
        color: white;
    }
    .btn-action-success:hover { background-color: #157347; color: white; }

    .fab {
        position: fixed;
        bottom: 24px;
        right: max(24px, calc(50% - 240px + 24px));
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
        text-decoration: none;
        transition: transform 0.2s, background-color 0.2s;
        z-index: 100;
    }

    .fab:hover {
        background-color: #0b5ed7;
        transform: scale(1.05);
        color: white;
    }
    
    @media (max-width: 480px) {
        .fab {
            right: 24px;
        }
    }
</style>

<div class="captacoes-container">
    <div class="captacoes-header">
        <button class="btn-back" onclick="history.back()" aria-label="Voltar">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="captacoes-title">Minhas Solicitações</h1>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Você não enviou nenhuma solicitação ainda.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pedidos as $pedido): ?>
            <?php 
                $cnpjFormatado = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj']); 
                $statusClass = 'status-' . $pedido['status'];
            ?>
            <div class="pedido-card <?= esc($statusClass) ?>">
                <div>
                    <h3 class="company-name"><?= esc($pedido['razao_social'] ?? 'Sem Razão Social') ?></h3>
                    <div class="cnpj-formatted"><?= esc($cnpjFormatado) ?></div>
                </div>

                <?php 
                    $badgeClass = '';
                    $badgeIcon = '';
                    $badgeText = '';
                    
                    switch($pedido['status']) {
                        case 'pendente':
                            $badgeClass = 'badge-pendente';
                            $badgeIcon = 'bi-hourglass-split';
                            $badgeText = 'Aguardando análise';
                            break;
                        case 'aprovado':
                            $badgeClass = 'badge-aprovado';
                            $badgeIcon = 'bi-check-circle-fill';
                            $badgeText = 'Aprovado';
                            break;
                        case 'rejeitado':
                            $badgeClass = 'badge-rejeitado';
                            $badgeIcon = 'bi-x-circle-fill';
                            $badgeText = 'Rejeitado';
                            break;
                        case 'mais_info':
                            $badgeClass = 'badge-mais_info';
                            $badgeIcon = 'bi-info-circle-fill';
                            $badgeText = 'Mais informações solicitadas';
                            break;
                    }
                ?>
                <div class="badge-status <?= $badgeClass ?>">
                    <i class="bi <?= $badgeIcon ?>"></i> <?= esc($badgeText) ?>
                </div>

                <div class="pedido-date">
                    <i class="bi bi-calendar3"></i> Pedido: <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                </div>
                
                <?php if ($pedido['status'] === 'aprovado' && !empty($pedido['decided_at'])): ?>
                    <div class="pedido-date">
                        <i class="bi bi-check2-all"></i> Aprovado: <?= date('d/m/Y H:i', strtotime($pedido['decided_at'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pedido['admin_obs'])): ?>
                    <div class="admin-obs-box">
                        <strong>Observação do Admin:</strong>
                        <?= nl2br(esc($pedido['admin_obs'])) ?>
                    </div>
                <?php endif; ?>

                <?php if ($pedido['status'] === 'mais_info'): ?>
                    <a href="<?= site_url('vendedor/captacao/solicitar/' . $pedido['cnpj']) ?>" class="btn-action btn-action-primary">
                        <i class="bi bi-pencil-square"></i> Complementar Pedido
                    </a>
                <?php endif; ?>
                
                <?php if ($pedido['status'] === 'aprovado'): ?>
                    <a href="<?= site_url('vendedor/cliente/' . $pedido['cnpj']) ?>" class="btn-action btn-action-success">
                        <i class="bi bi-person-lines-fill"></i> Ver Cliente
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="<?= site_url('vendedor/prospectar') ?>" class="fab" aria-label="Prospectar novo cliente" title="Prospectar novo cliente">
        <i class="bi bi-plus-lg"></i>
    </a>
</div>

<?= $this->endSection() ?>
