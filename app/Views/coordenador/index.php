<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.coord-container { max-width: 600px; margin: 0 auto; background: #f0f2f5; min-height: 100vh; }
.coord-topbar { display:flex; align-items:center; gap:12px; padding:12px 16px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:100; }
.coord-topbar .back-btn { width:36px; height:36px; border-radius:50%; background:#f3f4f6; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; color:#374151; cursor:pointer; text-decoration:none; }
.coord-topbar h6 { margin:0; font-weight:700; font-size:15px; }
.kpi-row { display:flex; gap:10px; padding:16px; }
.kpi-card { flex:1; background:#fff; border-radius:14px; padding:16px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.kpi-card .number { font-size:28px; font-weight:800; color:#1e40af; }
.kpi-card .label { font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; }
.vendor-list { padding:0 16px 16px; }
.vendor-item { display:flex; align-items:center; gap:12px; background:#fff; border-radius:14px; padding:14px 16px; margin-bottom:10px; box-shadow:0 1px 4px rgba(0,0,0,.04); text-decoration:none; color:inherit; transition:transform .15s; }
.vendor-item:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.08); }
.vendor-avatar { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#1e40af,#3b82f6); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; flex-shrink:0; }
.vendor-info { flex:1; min-width:0; }
.vendor-info .name { font-size:14px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.vendor-info .meta { font-size:11px; color:#94a3b8; margin-top:2px; }
.vendor-info .meta span { margin-right:8px; }
.vendor-stats { text-align:right; flex-shrink:0; }
.vendor-stats .count { font-size:18px; font-weight:800; color:#1e40af; }
.vendor-stats .slabel { font-size:10px; color:#94a3b8; }
</style>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('vendedor') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>👥 Visão do Time</h6>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div style="margin: 16px 16px 0; background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 12px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-check-circle-fill"></i> <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div style="margin: 16px 16px 0; background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 12px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="number"><?= $totalVendedores ?></div>
            <div class="label">Vendedores</div>
        </div>
        <div class="kpi-card">
            <div class="number"><?= number_format($totalClientesTime, 0, ',', '.') ?></div>
            <div class="label">Clientes no Time</div>
        </div>
    </div>

    <div class="vendor-list">
        <!-- Ações rápidas do coordenador -->
        <a href="<?= site_url('coordenador/captacoes') ?>"
           style="display:flex;align-items:center;gap:12px;background:#fff;border-radius:14px;padding:14px 16px;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,.04);text-decoration:none;color:inherit;border-left:4px solid #f59e0b;">
            <i class="bi bi-inbox-fill" style="font-size:24px;color:#f59e0b;"></i>
            <div>
                <div style="font-weight:700;font-size:14px;color:#1e293b;">Pedidos de Captação</div>
                <div style="font-size:11px;color:#94a3b8;">Analisar e decidir sobre solicitações do time</div>
            </div>
            <i class="bi bi-chevron-right" style="margin-left:auto;color:#94a3b8;"></i>
        </a>

        <a href="<?= site_url('coordenador/vendedores/novo') ?>"
           style="display:flex;align-items:center;gap:12px;background:#fff;border-radius:14px;padding:14px 16px;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,.04);text-decoration:none;color:inherit;border-left:4px solid #10b981;">
            <i class="bi bi-person-plus-fill" style="font-size:24px;color:#10b981;"></i>
            <div>
                <div style="font-weight:700;font-size:14px;color:#1e293b;">Novo Vendedor</div>
                <div style="font-size:11px;color:#94a3b8;">Cadastrar funcionário no seu time</div>
            </div>
            <i class="bi bi-chevron-right" style="margin-left:auto;color:#94a3b8;"></i>
        </a>

        <a href="<?= site_url('coordenador/clientes-livres') ?>"
           style="display:flex;align-items:center;gap:12px;background:#fff;border-radius:14px;padding:14px 16px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.04);text-decoration:none;color:inherit;border-left:4px solid #8b5cf6;">
            <i class="bi bi-shop-window" style="font-size:24px;color:#8b5cf6;"></i>
            <div>
                <div style="font-weight:700;font-size:14px;color:#1e293b;">Clientes Livres</div>
                <div style="font-size:11px;color:#94a3b8;">Atribuir clientes sem dono ao time</div>
            </div>
            <i class="bi bi-chevron-right" style="margin-left:auto;color:#94a3b8;"></i>
        </a>

        <h6 style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;padding:0 4px;">
            <i class="bi bi-people"></i> Seus Vendedores
        </h6>

        <?php foreach ($vendedores as $v): ?>
            <div class="vendor-item" style="position:relative;">
                <a href="<?= site_url('coordenador/vendedor/' . $v['matricula']) ?>" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit; flex:1;">
                    <div class="vendor-avatar"><?= mb_substr($v['nome'] ?? '?', 0, 2) ?></div>
                    <div class="vendor-info">
                        <div class="name"><?= esc($v['nome'] ?? 'Matrícula ' . $v['matricula']) ?></div>
                        <div class="meta">
                            <span><i class="bi bi-person-badge"></i> <?= esc($v['matricula']) ?></span>
                            <span><i class="bi bi-tag"></i> <?= esc($v['perfil_vendedor'] ?? '—') ?></span>
                        </div>
                    </div>
                    <div class="vendor-stats">
                        <div class="count"><?= $v['total_clientes'] ?? 0 ?></div>
                        <div class="slabel">clientes</div>
                    </div>
                </a>
                <div class="dropdown" style="margin-left:8px;">
                    <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown" style="padding:0;font-size:20px;border:none;">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="border-radius:12px; border:none; box-shadow:0 4px 12px rgba(0,0,0,.1); padding:8px;">
                        <li><a class="dropdown-item" href="<?= site_url('coordenador/vendedor/' . $v['matricula'] . '/clientes') ?>" style="font-size:13px; padding:8px 12px; border-radius:8px;"><i class="bi bi-list-ul me-2"></i>Ver Clientes</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('coordenador/vendedor/' . $v['matricula'] . '/editar') ?>" style="font-size:13px; padding:8px 12px; border-radius:8px;"><i class="bi bi-pencil me-2 text-primary"></i>Editar</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('coordenador/vendedor/' . $v['matricula'] . '/transferir') ?>" style="font-size:13px; padding:8px 12px; border-radius:8px;"><i class="bi bi-shuffle me-2 text-warning"></i>Transferir</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= site_url('coordenador/vendedor/' . $v['matricula'] . '/desativar') ?>" method="post" onsubmit="return confirm('Tem certeza que deseja desativar este vendedor?');">
                                <button type="submit" class="dropdown-item text-danger" style="font-size:13px; padding:8px 12px; border-radius:8px;"><i class="bi bi-person-x me-2"></i>Desativar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($vendedores)): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <div style="font-size:48px;margin-bottom:8px;">👥</div>
                <p>Nenhum vendedor vinculado ao seu time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
