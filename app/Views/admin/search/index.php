<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3"><i class="bi bi-search text-primary me-2"></i>Consulta Cadastral RFB</h1>
            <p class="text-muted mb-0">Pesquise informações diretamente na base oficial da Receita Federal (68 milhões+ de registros).</p>
        </div>
    </div>

    <!-- Card de Filtro / Busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="<?= base_url('admin/busca') ?>" method="GET" class="row g-3">
                <div class="col-md-9">
                    <label for="q" class="form-label fw-bold">Termo de Pesquisa</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" 
                               name="q" 
                               id="q" 
                               class="form-control" 
                               placeholder="Digite CNPJ (8 ou 14 algarismos), CPF do sócio (11 algarismos) ou Nome da Empresa..." 
                               value="<?= esc($q) ?>" 
                               required>
                    </div>
                    <div class="form-text mt-2">
                        <span class="badge bg-light text-dark border me-1">CNPJ</span> Exatos 8 ou 14 dígitos.
                        <span class="badge bg-light text-dark border ms-2 me-1">CPF</span> Exatos 11 dígitos.
                        <span class="badge bg-light text-dark border ms-2 me-1">Texto</span> Busca por início da Razão Social (busca indexada).
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                        <i class="bi bi-filter me-1"></i> Pesquisar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <?php if ($q !== ''): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="mb-0 text-dark">
                    Resultados da busca por: 
                    <span class="text-primary fw-bold">"<?= esc($q) ?>"</span>
                    <span class="badge bg-secondary ms-2 small">
                        <?php 
                            if ($searchType === 'cnpj') echo 'Tipo: CNPJ';
                            elseif ($searchType === 'cpf') echo 'Tipo: CPF Sócio';
                            else echo 'Tipo: Razão Social (Início)';
                        ?>
                    </span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($results)): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox text-muted display-4"></i>
                        <p class="mt-3 text-muted">Nenhum registro correspondente foi encontrado na base da Receita Federal.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">CNPJ Básico</th>
                                    <th>Razão Social</th>
                                    <th style="width: 15%;" class="text-end">Capital Social (R$)</th>
                                    <th style="width: 12%;" class="text-center">Porte</th>
                                    <th style="width: 18%;" class="text-center">SPIV Carteira</th>
                                    <th style="width: 8%;" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td>
                                            <code class="fw-bold text-secondary"><?= esc($row['cnpj_basico']) ?></code>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= esc($row['razao_social']) ?></div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?php 
                                                $cap = floatval(str_replace(',', '.', $row['capital_social'] ?? '0'));
                                                echo number_format($cap, 2, ',', '.');
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                $porte = $row['porte_empresa'] ?? '';
                                                if ($porte === '01') echo '<span class="badge bg-light text-dark border">ME</span>';
                                                elseif ($porte === '03') echo '<span class="badge bg-light text-dark border">EPP</span>';
                                                elseif ($porte === '05') echo '<span class="badge bg-light text-dark border">Demais</span>';
                                                else echo '<span class="badge bg-light text-muted border">Não Inf.</span>';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['em_carteira_qtd'] > 0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-3 rounded-pill">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Em Carteira (<?= $row['em_carteira_qtd'] ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border py-1.5 px-3 rounded-pill">
                                                    <i class="bi bi-dash-circle me-1"></i> Fora da Carteira
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('admin/busca/empresa/' . $row['cnpj_basico']) ?>" 
                                               class="btn btn-sm btn-outline-primary shadow-sm" 
                                               title="Visualizar Ficha Completa">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($hasPrevPage || $hasNextPage): ?>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 border-top">
                            <div>
                                <span class="text-muted small">Página <strong><?= $page ?></strong></span>
                            </div>
                            <div class="btn-group shadow-sm">
                                <?php if ($hasPrevPage): ?>
                                    <a href="<?= base_url('admin/busca') . '?q=' . urlencode($q) . '&page=' . ($page - 1) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-chevron-left me-1"></i> Anterior
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="bi bi-chevron-left me-1"></i> Anterior
                                    </button>
                                <?php endif; ?>

                                <?php if ($hasNextPage): ?>
                                    <a href="<?= base_url('admin/busca') . '?q=' . urlencode($q) . '&page=' . ($page + 1) ?>" class="btn btn-sm btn-outline-secondary">
                                        Próxima <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        Próxima <i class="bi bi-chevron-right ms-1"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
