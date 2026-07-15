<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <!-- Voltar -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar para a pesquisa
        </a>
    </div>

    <!-- Cabeçalho de Ficha Cadastral -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="bg-primary bg-gradient p-4 text-white">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge bg-white-50 text-white text-uppercase mb-2 small">Ficha Cadastral (Receita Federal)</span>
                    <h2 class="mb-1 h3 text-white fw-bold"><?= esc($empresa['razao_social']) ?></h2>
                    <p class="mb-0 text-white-50">CNPJ Básico: <code class="text-white bg-dark bg-opacity-25 px-2 py-0.5 rounded fw-bold"><?= esc($empresa['cnpj_basico']) ?></code></p>
                </div>
                <div class="text-end">
                    <span class="fs-6 d-block text-white-50">Capital Social</span>
                    <span class="fs-3 fw-bold text-white">
                        R$ <?= number_format(floatval(str_replace(',', '.', $empresa['capital_social'] ?? '0')), 2, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Coluna da Esquerda: Dados Básicos -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Informações de Registro</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted py-2.5" style="width: 35%;">Natureza Jurídica</td>
                                    <td class="fw-semibold text-dark py-2.5">
                                        <code><?= esc($empresa['natureza_juridica']) ?></code>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-2.5">Qualificação do Resp.</td>
                                    <td class="fw-semibold text-dark py-2.5">
                                        Código <?= esc($empresa['qualificacao_responsavel']) ?>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-2.5">Porte da Empresa</td>
                                    <td class="fw-semibold text-dark py-2.5">
                                        <?php 
                                            $porte = $empresa['porte_empresa'] ?? '';
                                            if ($porte === '01') echo 'ME - Microempresa';
                                            elseif ($porte === '03') echo 'EPP - Empresa de Pequeno Porte';
                                            elseif ($porte === '05') echo 'Demais';
                                            else echo 'Não Informado';
                                        ?>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-2.5">Ente Federativo Resp.</td>
                                    <td class="text-dark py-2.5">
                                        <?= esc($empresa['ente_federativo'] ?: 'Nenhum') ?>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted py-2.5">Atualização Ingestão</td>
                                    <td class="text-muted py-2.5 small">
                                        <?= date('d/m/Y H:i:s', strtotime($empresa['updated_at'])) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita: Quadro de Sócios e Administradores (QSA) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people text-primary me-2"></i>Quadro de Sócios (QSA)</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (empty($socios)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-person-x display-6"></i>
                            <p class="mt-2 small mb-0">Nenhum sócio ou administrador registrado para esta empresa.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($socios as $socio): ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark"><?= esc($socio['nome_socio']) ?></span>
                                        <?php if ($socio['identificador_socio'] === '1'): ?>
                                            <span class="badge bg-light text-secondary border">Pessoa Jurídica</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-primary border">Pessoa Física</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>CPF/CNPJ: <code><?= esc($socio['cpf_cnpj_socio'] ? substr($socio['cpf_cnpj_socio'], 0, 3) . '***' . substr($socio['cpf_cnpj_socio'], -3) : '***') ?></code></span>
                                        <span>Qualificação: Código <?= esc($socio['qualificacao_socio']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de baixo: Situação Operacional no SPIV -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3 border-bottom-0 px-4 pt-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-briefcase text-primary me-2"></i>Situação Operacional no SPIV</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <?php if (empty($carteiras)): ?>
                <div class="alert alert-info border-0 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <div>
                        <strong>Fora da Carteira:</strong> Nenhuma filial (CNPJ completo) desta empresa está associada a carteiras ativas do SPIV.
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">CNPJ Completo (Filial)</th>
                                <th style="width: 20%;">Vendedor Responsável</th>
                                <th style="width: 15%;">Matrícula</th>
                                <th style="width: 20%;" class="text-center">Status Operacional</th>
                                <th style="width: 20%;" class="text-center">Data Atribuição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carteiras as $cart): ?>
                                <tr>
                                    <td>
                                        <code class="fw-bold text-dark"><?= esc($cart['cnpj']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($cart['vendor_nome']): ?>
                                            <span class="fw-semibold text-dark"><i class="bi bi-person-fill me-1"></i><?= esc($cart['vendor_nome']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Sem Atribuição</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= esc($cart['vendor_matricula'] ?: '-') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            $st = $cart['status_operacional'];
                                            if ($st === 'novo') echo '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Novo</span>';
                                            elseif ($st === 'em_acompanhamento') echo '<span class="badge bg-info-subtle text-info border border-info-subtle">Em Acompanhamento</span>';
                                            elseif ($st === 'convertido') echo '<span class="badge bg-success-subtle text-success border border-success-subtle">Convertido</span>';
                                            elseif ($st === 'sem_contato') echo '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Sem Contato</span>';
                                            elseif ($st === 'bloqueado') echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Bloqueado</span>';
                                            else echo '<span class="badge bg-light text-dark border">Inativo</span>';
                                        ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= $cart['atribuido_em'] ? date('d/m/Y H:i', strtotime($cart['atribuido_em'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
