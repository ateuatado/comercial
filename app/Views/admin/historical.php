<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="mb-4">
        <h1 class="h3">Histórico de Movimentações</h1>
        <p class="text-muted">Todas as movimentações de clientes entre responsáveis. Total de registros: <strong><?= $total ?></strong></p>
    </div>

    <!-- Tabela de histórico -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">CNPJ</th>
                        <th style="width: 30%;">Empresa</th>
                        <th style="width: 15%;">De</th>
                        <th style="width: 15%;">Para</th>
                        <th style="width: 15%;">Tipo</th>
                        <th style="width: 13%;">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $mov): ?>
                        <tr>
                            <td>
                                <code><?= esc($mov['cnpj']) ?></code>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $mov['razao_social'] ? esc($mov['razao_social']) : '<em>Não encontrada</em>' ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $mov['vendor_anterior'] ? esc($mov['vendor_anterior']) : '<em>Atribuição inicial</em>' ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $mov['vendor_novo'] ? esc($mov['vendor_novo']) : '—' ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                    $tipoClass = match ($mov['tipo_movimento']) {
                                        'atribuicao_inicial' => 'badge bg-success',
                                        'reatribuicao_manual' => 'badge bg-warning text-dark',
                                        'reatribuicao_automatica' => 'badge bg-info',
                                        default => 'badge bg-secondary',
                                    };
                                    $tipoLabel = ucfirst(str_replace('_', ' ', $mov['tipo_movimento']));
                                ?>
                                <span class="<?= $tipoClass ?>"><?= $tipoLabel ?></span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($mov['created_at'])) ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($historico)): ?>
            <div class="alert alert-info m-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Nenhuma movimentação registrada até o momento.
            </div>
        <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url_to('admin_historical') ?>?page=1">Primeira</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= url_to('admin_historical') ?>?page=<?= $page - 1 ?>">Anterior</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i >= $page - 2 && $i <= $page + 2): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url_to('admin_historical') ?>?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url_to('admin_historical') ?>?page=<?= $page + 1 ?>">Próxima</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?= url_to('admin_historical') ?>?page=<?= $total_pages ?>">Última</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
