<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <a href="<?= site_url('admin/eventos') ?>" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i>Voltar para Eventos
    </a>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-1"><i class="bi bi-calendar-event-fill me-2 text-primary"></i><?= esc($evento['nome']) ?></h4>
            <div class="text-muted small">
                <?php if (!empty($evento['local'])): ?>
                    <span class="me-3"><i class="bi bi-geo-alt me-1"></i><?= esc($evento['local']) ?></span>
                <?php endif; ?>
                <?php if ($evento['data_inicio']): ?>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($evento['data_inicio'])) ?>
                    <?php if ($evento['data_fim']): ?> até <?= date('d/m/Y', strtotime($evento['data_fim'])) ?><?php endif; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($evento['ativo']): ?>
                <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Evento Ativo</span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6"><i class="bi bi-pause-circle me-1"></i>Evento Inativo</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filtros de Status -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="row align-items-center g-2">
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-1">
                    <?php
                    $statusList = [
                        ''                   => ['label' => 'Todos',               'icon' => 'bi-list-ul',       'badge' => 'secondary'],
                        'marcar_reuniao'     => ['label' => 'Marcar Reunião',     'icon' => 'bi-calendar-check','badge' => 'success'],
                        'ligar_depois'       => ['label' => 'Ligar Depois',        'icon' => 'bi-telephone-outbound','badge' => 'primary'],
                        'interesse_limitado' => ['label' => 'Interesse Limitado',  'icon' => 'bi-exclamation-circle','badge' => 'warning text-dark'],
                        'sem_interesse'      => ['label' => 'Sem Interesse',       'icon' => 'bi-x-circle',      'badge' => 'danger'],
                    ];
                    foreach ($statusList as $stKey => $stCfg):
                        $total = $totais[$stKey] ?? ($stKey === '' ? array_sum($totais) : 0);
                        $isActive = ($filtroStatus === $stKey);
                        $queryStr = http_build_query(array_filter(['status' => $stKey, 'vendedor' => $filtroVendedor]));
                    ?>
                        <a href="<?= site_url('admin/eventos/' . $evento['id']) ?>?<?= $queryStr ?>"
                           class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="<?= $stCfg['icon'] ?> me-1"></i><?= $stCfg['label'] ?>
                            <span class="badge bg-<?= $stCfg['badge'] ?> ms-1"><?= $total ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filtro Vendedor -->
            <div class="col-md-4">
                <form action="<?= site_url('admin/eventos/' . $evento['id']) ?>" method="GET" class="d-flex align-items-center gap-2">
                    <?php if ($filtroStatus): ?>
                        <input type="hidden" name="status" value="<?= esc($filtroStatus) ?>">
                    <?php endif; ?>
                    <select name="vendedor" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todos os Vendedores --</option>
                        <?php foreach ($vendedores as $v): ?>
                            <option value="<?= esc($v['matricula_vendedor']) ?>" <?= $filtroVendedor === $v['matricula_vendedor'] ? 'selected' : '' ?>>
                                <?= esc($v['nome_vendedor']) ?> (<?= esc($v['matricula_vendedor']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Contatos -->
<?php if (empty($contatos)): ?>
    <div class="text-center py-5 text-muted card border-0 shadow-sm">
        <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i>
        <p class="mt-3">Nenhum contato captado encontrado para os filtros selecionados.</p>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data / Hora</th>
                        <th>CNPJ / Razão Social</th>
                        <th>Vendedor</th>
                        <th>Status</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contatos as $c):
                        $cnpjFmt = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $c['cnpj']);
                        
                        $statusBadgeMap = [
                            'marcar_reuniao'     => ['label' => '📅 Marcar Reunião',     'class' => 'bg-success'],
                            'ligar_depois'       => ['label' => '📞 Ligar Depois',        'class' => 'bg-primary'],
                            'interesse_limitado' => ['label' => '⚡ Interesse Limitado',  'class' => 'bg-warning text-dark'],
                            'sem_interesse'      => ['label' => '❌ Sem Interesse',       'class' => 'bg-danger'],
                        ];
                        $st = $statusBadgeMap[$c['status']] ?? ['label' => $c['status'], 'class' => 'bg-secondary'];
                    ?>
                    <tr>
                        <td>
                            <small class="fw-semibold text-muted">
                                <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                            </small>
                            <div class="small text-muted" style="font-size:11px;">
                                <?= date('H:i', strtotime($c['created_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($c['razao_social'] ?: 'Razão Social Indisponível') ?></div>
                            <small class="text-muted font-monospace"><?= $cnpjFmt ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= esc($c['nome_vendedor']) ?></div>
                            <small class="text-muted" style="font-size:11px;"><?= esc($c['matricula_vendedor']) ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                        </td>
                        <td style="max-width: 300px;">
                            <?php if (!empty($c['observacao'])): ?>
                                <span class="small text-dark"><?= nl2br(esc($c['observacao'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
