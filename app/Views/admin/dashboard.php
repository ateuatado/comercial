<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4" style="max-width: 1100px;">

    <!-- Header -->
    <div class="mb-4">
        <h4 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i>Painel Administrativo</h4>
        <p class="text-muted small mb-0">Visão consolidada da base de carteiras do SPIV.</p>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                    <div class="fs-2 fw-bold text-primary mt-2"><?= number_format($totalCarteira, 0, ',', '.') ?></div>
                    <div class="text-muted small fw-bold text-uppercase">Clientes na Base</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge"></i></div>
                    <div class="fs-2 fw-bold text-success mt-2"><?= number_format($totalVendedores, 0, ',', '.') ?></div>
                    <div class="text-muted small fw-bold text-uppercase">Vendedores Ativos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="fs-2 fw-bold text-info mt-2"><?= $totalSEs ?></div>
                    <div class="text-muted small fw-bold text-uppercase">Superintendências</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-tags-fill"></i></div>
                    <div class="fs-2 fw-bold text-warning mt-2"><?= $totalSegmentos ?></div>
                    <div class="text-muted small fw-bold text-uppercase">Segmentos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <!-- Categorias -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2"></i>Por Categoria</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartCategorias" style="max-height:220px;"></canvas>
                </div>
            </div>
        </div>
        <!-- Top SEs -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2"></i>Top 10 SEs</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartSEs" style="max-height:220px;"></canvas>
                </div>
            </div>
        </div>
        <!-- Ciclo de Vida -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2"></i>Ciclo de Vida</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartCiclos" style="max-height:220px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações rápidas + Saúde da base -->
    <div class="row g-3 mb-4">
        <!-- Ações rápidas -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning me-2"></i>Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= site_url('admin/importar') ?>" class="btn btn-outline-primary d-flex align-items-center gap-2">
                            <i class="bi bi-cloud-upload fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Importar Carteira</div>
                                <small class="text-muted">Upload CSV do relatório geral</small>
                            </div>
                        </a>
                        <a href="<?= site_url('admin/vendors') ?>" class="btn btn-outline-success d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Gerenciar Vendedores</div>
                                <small class="text-muted"><?= number_format($totalVendedores, 0, ',', '.') ?> ativos</small>
                            </div>
                        </a>
                        <a href="<?= site_url('admin/mensagens') ?>" class="btn btn-outline-info d-flex align-items-center gap-2">
                            <i class="bi bi-chat-square-text fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Mensagens do Sistema</div>
                                <small class="text-muted">Editar mensagens para vendedores</small>
                            </div>
                        </a>
                        <a href="<?= site_url('admin/busca') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-search fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Consulta RFB</div>
                                <small class="text-muted">Buscar empresas na Receita Federal</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saúde da base -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-heart-pulse me-2"></i>Saúde da Base</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-database me-2 text-primary"></i>Registros importados</span>
                            <span class="badge bg-primary rounded-pill"><?= number_format($totalCarteira, 0, ',', '.') ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-person-check me-2 text-success"></i>Vendedores com carteira</span>
                            <span class="badge bg-success rounded-pill"><?= number_format($totalVendedores, 0, ',', '.') ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-journal-text me-2 text-info"></i>Notas (últimos 7 dias)</span>
                            <span class="badge bg-info rounded-pill"><?= $notasRecentes ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-clock me-2 text-secondary"></i>Última importação</span>
                            <span class="text-muted small">
                                <?php if ($ultimaImport): ?>
                                    <?= date('d/m/Y H:i', strtotime($ultimaImport->created_at)) ?>
                                    <span class="badge bg-success ms-1"><?= number_format($ultimaImport->inserted, 0, ',', '.') ?> reg.</span>
                                <?php else: ?>
                                    <span class="text-warning">Nenhuma importação via web</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.kpi-icon {
    width: 48px; height: 48px; border-radius: 14px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 22px; margin: 0 auto;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const CAT_COLORS = {
        'BRONZE': '#cd7f32', 'OURO': '#daa520', 'PRATA': '#a0a0a0',
        'DIAMANTE': '#2563eb', 'PLATINUM': '#7c3aed', 'INFINITE': '#1e293b', 'CLUBE': '#059669'
    };

    // Categorias Doughnut
    const catData = <?= json_encode($categorias) ?>;
    new Chart(document.getElementById('chartCategorias'), {
        type: 'doughnut',
        data: {
            labels: catData.map(c => c.categoria),
            datasets: [{
                data: catData.map(c => c.total),
                backgroundColor: catData.map(c => CAT_COLORS[c.categoria] || '#94a3b8'),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });

    // SEs Horizontal Bar
    const seData = <?= json_encode($topSEs) ?>;
    new Chart(document.getElementById('chartSEs'), {
        type: 'bar',
        data: {
            labels: seData.map(s => s.se),
            datasets: [{
                data: seData.map(s => s.total),
                backgroundColor: '#3b82f6',
                borderRadius: 6, barThickness: 16,
            }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { ticks: { font: { size: 11 } } } }
        }
    });

    // Ciclo de Vida Doughnut
    const cicloData = <?= json_encode($ciclos) ?>;
    const CICLO_COLORS = ['#22c55e','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#94a3b8'];
    new Chart(document.getElementById('chartCiclos'), {
        type: 'doughnut',
        data: {
            labels: cicloData.map(c => c.ciclo_de_vida),
            datasets: [{
                data: cicloData.map(c => c.total),
                backgroundColor: cicloData.map((_, i) => CICLO_COLORS[i % CICLO_COLORS.length]),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });
})();
</script>
<?= $this->endSection() ?>
