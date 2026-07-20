<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="bi bi-inbox-fill me-2"></i>Pedidos de Captação</h4>
        <small class="text-muted">Solicitações de vendedores para adição de clientes à carteira</small>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Abas de filtro por status -->
<ul class="nav nav-tabs mb-3">
    <?php
    $abas = [
        ''          => ['label' => 'Todos',          'icon' => 'bi-list-ul'],
        'pendente'  => ['label' => 'Pendentes',       'icon' => 'bi-hourglass-split', 'cor' => 'warning'],
        'mais_info' => ['label' => 'Mais Informações','icon' => 'bi-chat-dots',        'cor' => 'info'],
        'aprovado'  => ['label' => 'Aprovados',       'icon' => 'bi-check-circle',     'cor' => 'success'],
        'rejeitado' => ['label' => 'Rejeitados',      'icon' => 'bi-x-circle',         'cor' => 'danger'],
    ];
    foreach ($abas as $key => $aba):
        $total = $totais[$key] ?? ($key === '' ? array_sum($totais) : 0);
        $active = ($filtroStatus === $key) ? 'active' : '';
    ?>
    <li class="nav-item">
        <a class="nav-link <?= $active ?>" href="<?= site_url('admin/captacoes') ?>?status=<?= $key ?>">
            <i class="<?= $aba['icon'] ?> me-1"></i><?= $aba['label'] ?>
            <?php if ($total > 0): ?>
                <span class="badge bg-<?= $aba['cor'] ?? 'secondary' ?> ms-1"><?= $total ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($pedidos)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size: 48px;"></i>
        <p class="mt-3">Nenhum pedido encontrado.</p>
    </div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Cliente / CNPJ</th>
                    <th>Vendedor</th>
                    <th>Score</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p):
                    $cnpjFmt = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $p['cnpj']);
                    $score = (int)($p['score'] ?? 0);
                    $scoreColor = $score >= 60 ? 'success' : ($score >= 30 ? 'warning' : 'secondary');

                    $statusCfg = [
                        'pendente'  => ['label' => '⏳ Pendente',          'badge' => 'warning text-dark'],
                        'mais_info' => ['label' => '🔵 Mais Informações',  'badge' => 'info text-dark'],
                        'aprovado'  => ['label' => '✅ Aprovado',           'badge' => 'success'],
                        'rejeitado' => ['label' => '❌ Rejeitado',          'badge' => 'danger'],
                    ];
                    $sc = $statusCfg[$p['status']] ?? ['label' => $p['status'], 'badge' => 'secondary'];
                ?>
                <tr>
                    <td><small class="text-muted">#<?= $p['id'] ?></small></td>
                    <td>
                        <div class="fw-semibold"><?= esc($p['razao_social']) ?></div>
                        <small class="text-muted font-monospace"><?= $cnpjFmt ?></small>
                        <?php if ($p['cnpj_em_outra_carteira']): ?>
                            <span class="badge bg-warning text-dark ms-1" title="CNPJ está em outra carteira">⚠️ Em disputa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= esc($p['nome_vendedor'] ?? $p['matricula']) ?></div>
                        <small class="text-muted"><?= esc($p['matricula']) ?></small>
                    </td>
                    <td>
                        <?php if ($score > 0): ?>
                            <span class="badge bg-<?= $scoreColor ?>"><?= $score ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></small>
                    </td>
                    <td><span class="badge bg-<?= $sc['badge'] ?>"><?= $sc['label'] ?></span></td>
                    <td>
                        <a href="<?= site_url('admin/captacoes/' . $p['id']) ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Analisar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
