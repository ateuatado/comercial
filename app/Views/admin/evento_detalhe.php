<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('assets/css/leaflet.css') ?>" />
<script src="<?= base_url('assets/js/leaflet.js') ?>"></script>

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
                <?php if (!empty($evento['data_inicio'])): ?>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($evento['data_inicio'])) ?>
                    <?php if (!empty($evento['data_fim'])): ?> até <?= date('d/m/Y', strtotime($evento['data_fim'])) ?><?php endif; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if (!empty($evento['ativo'])): ?>
                <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Evento Ativo</span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6"><i class="bi bi-pause-circle me-1"></i>Evento Inativo</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Card do Mapa Interativo de Abordagens -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-map-fill text-danger me-2"></i>Mapa de Abordagens dos Vendedores
        </h6>
        <span class="badge bg-primary-subtle text-primary fw-bold" id="mapPointsCount">
            Carregando pontos...
        </span>
    </div>
    <div class="card-body p-0">
        <div id="mapEventoAdmin" style="height: 380px; width: 100%; border-radius: 0 0 8px 8px; background: #f8fafc; z-index: 1;"></div>
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
                    <?php if (!empty($filtroStatus)): ?>
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
    <div class="text-center py-5 text-muted card border-0 shadow-sm mb-4">
        <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i>
        <p class="mt-3">Nenhum contato captado encontrado para os filtros selecionados.</p>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data / Hora</th>
                        <th>CNPJ / Razão Social</th>
                        <th>Vendedor</th>
                        <th>Local do Registro (GPS)</th>
                        <th>Status / Contrato</th>
                        <th>Produtos de Interesse</th>
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
                        $possuiContrato = !empty($c['possui_contrato']) && ($c['possui_contrato'] === true || $c['possui_contrato'] === 't' || $c['possui_contrato'] === 1 || $c['possui_contrato'] === '1');
                        $produtos = !empty($c['produtos_interesse']) ? array_filter(array_map('trim', explode(',', $c['produtos_interesse']))) : [];
                        $hasGps = !empty($c['latitude']) && !empty($c['longitude']);
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
                            <?php if ($hasGps): ?>
                                <a href="https://www.google.com/maps?q=<?= esc($c['latitude']) ?>,<?= esc($c['longitude']) ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-danger py-0 px-2"
                                   style="font-size: 11px;"
                                   title="Abrir no Google Maps">
                                    <i class="bi bi-geo-alt-fill me-1"></i>Ver Mapa
                                </a>
                                <div class="text-muted font-monospace mt-1" style="font-size: 10px;">
                                    <?= round((float)$c['latitude'], 5) ?>, <?= round((float)$c['longitude'], 5) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $st['class'] ?> mb-1 d-inline-block"><?= $st['label'] ?></span>
                            <div>
                                <?php if ($possuiContrato): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle" title="Cliente já tem contrato com os Correios">
                                        <i class="bi bi-file-earmark-check me-1"></i>Com Contrato
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border" title="Não possui contrato">
                                        Sem Contrato
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($produtos)): ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($produtos as $prod): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;">
                                            <?= esc($prod) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width: 260px;">
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

<?php
$gpsContatos = [];
if (!empty($contatos)) {
    foreach ($contatos as $c) {
        if (!empty($c['latitude']) && !empty($c['longitude'])) {
            $gpsContatos[] = [
                'id'                 => $c['id'],
                'cnpj'               => $c['cnpj'],
                'razao_social'       => $c['razao_social'] ?: 'Razão Social Indisponível',
                'nome_vendedor'      => $c['nome_vendedor'],
                'matricula_vendedor' => $c['matricula_vendedor'],
                'status'             => $c['status'],
                'possui_contrato'    => !empty($c['possui_contrato']) && ($c['possui_contrato'] === true || $c['possui_contrato'] === 't' || $c['possui_contrato'] === 1 || $c['possui_contrato'] === '1'),
                'produtos_interesse' => $c['produtos_interesse'],
                'observacao'         => $c['observacao'],
                'created_at'         => date('d/m/Y H:i', strtotime($c['created_at'])),
                'lat'                => (float)$c['latitude'],
                'lng'                => (float)$c['longitude'],
            ];
        }
    }
}
?>

<script>
const pointsData = <?= json_encode($gpsContatos) ?>;

document.addEventListener("DOMContentLoaded", function() {
    const pointsCountEl = document.getElementById('mapPointsCount');
    if (pointsCountEl) {
        pointsCountEl.textContent = `${pointsData.length} ponto(s) no mapa`;
    }

    if (!document.getElementById('mapEventoAdmin')) return;

    let defaultCoords = [-23.5505, -46.6333];
    if (pointsData.length > 0) {
        defaultCoords = [pointsData[0].lat, pointsData[0].lng];
    }

    const map = L.map('mapEventoAdmin').setView(defaultCoords, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    if (pointsData.length === 0) {
        L.popup()
            .setLatLng(defaultCoords)
            .setContent('<div class="text-muted p-2 text-center">Nenhum registro com GPS capturado ainda neste evento.</div>')
            .openOn(map);
        return;
    }

    const bounds = L.latLngBounds();

    const statusColors = {
        'marcar_reuniao': '#10b981',
        'ligar_depois': '#3b82f6',
        'interesse_limitado': '#f59e0b',
        'sem_interesse': '#ef4444'
    };

    const statusLabels = {
        'marcar_reuniao': '📅 Marcar Reunião',
        'ligar_depois': '📞 Ligar Depois',
        'interesse_limitado': '⚡ Interesse Limitado',
        'sem_interesse': '❌ Sem Interesse'
    };

    pointsData.forEach(pt => {
        const color = statusColors[pt.status] || '#64748b';
        const label = statusLabels[pt.status] || pt.status;
        const cnpjFmt = pt.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");

        const iconHtml = `<i class="bi bi-geo-alt-fill" style="font-size: 26px; color: ${color}; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>`;

        const marker = L.marker([pt.lat, pt.lng], {
            icon: L.divIcon({
                html: iconHtml,
                className: 'spiv-map-marker',
                iconSize: [26, 26],
                iconAnchor: [13, 26]
            })
        }).addTo(map);

        const popupHtml = `
            <div style="font-family: system-ui, -apple-system, sans-serif; font-size: 12px; min-width: 220px;">
                <h6 style="font-size: 13px; font-weight: 700; margin: 0 0 4px 0; color: #1e293b;">${escHtml(pt.razao_social)}</h6>
                <div style="font-family: monospace; font-size: 11px; color: #64748b; margin-bottom: 6px;">${cnpjFmt}</div>
                
                <div style="margin-bottom: 4px;">
                    <strong>Vendedor:</strong> ${escHtml(pt.nome_vendedor)} <small class="text-muted">(${escHtml(pt.matricula_vendedor)})</small>
                </div>
                
                <div style="margin-bottom: 4px;">
                    <strong>Status:</strong> <span style="color:${color}; font-weight:bold;">${label}</span>
                </div>

                ${pt.possui_contrato ? '<div style="color:#0284c7; font-weight:600; margin-bottom:4px;"><i class="bi bi-file-earmark-check"></i> Possui Contrato Correios</div>' : ''}
                ${pt.produtos_interesse ? `<div style="margin-bottom:4px;"><strong>Produtos:</strong> ${escHtml(pt.produtos_interesse)}</div>` : ''}
                ${pt.observacao ? `<div style="font-style:italic; background:#f8fafc; padding:4px 6px; border-radius:4px; margin-top:4px;">"${escHtml(pt.observacao)}"</div>` : ''}

                <div style="font-size:10px; color:#94a3b8; margin-top:6px; text-align:right;">
                    Registrado em ${pt.created_at}
                </div>
            </div>
        `;

        marker.bindPopup(popupHtml);
        bounds.extend([pt.lat, pt.lng]);
    });

    if (pointsData.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30] });
    }
});

function escHtml(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>

<?= $this->endSection() ?>
