<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.coord-container { max-width:600px; margin:0 auto; background:#f0f2f5; min-height:100vh; }
.coord-topbar { display:flex; align-items:center; gap:12px; padding:12px 16px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:100; }
.coord-topbar .back-btn { width:36px; height:36px; border-radius:50%; background:#f3f4f6; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; color:#374151; cursor:pointer; text-decoration:none; }
.coord-topbar h6 { margin:0; font-weight:700; font-size:15px; }
.profile-card { margin:16px; background:#fff; border-radius:14px; padding:18px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.profile-card .name { font-size:18px; font-weight:800; color:#1e293b; margin-bottom:4px; }
.profile-card .meta { font-size:12px; color:#94a3b8; display:flex; gap:12px; flex-wrap:wrap; }
.info-card { background:#fff; border-radius:14px; padding:16px; margin:0 16px 12px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.info-card h6 { font-size:12px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:10px; }
.dist-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f8fafc; }
.dist-row:last-child { border:none; }
.dist-row .label { font-size:13px; color:#374151; }
.dist-row .value { font-size:13px; font-weight:700; color:#1e40af; }
.action-row { display:flex; gap:8px; padding:0 16px 16px; }
.action-row a { flex:1; padding:12px; border-radius:12px; border:1.5px solid #e5e7eb; background:#fff; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; color:#374151; text-decoration:none; transition:all .2s; }
.action-row a:hover { border-color:#3b82f6; color:#1e40af; }
</style>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('coordenador') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>Vendedor</h6>
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

    <div class="profile-card">
        <div class="name"><?= esc($vendedor['nome'] ?? 'Matrícula ' . $vendedor['matricula']) ?></div>
        <div class="meta">
            <span><i class="bi bi-person-badge"></i> <?= esc($vendedor['matricula']) ?></span>
            <span><i class="bi bi-tag"></i> <?= esc($vendedor['perfil_vendedor'] ?? '—') ?></span>
            <span><i class="bi bi-geo-alt"></i> <?= esc($vendedor['se'] ?? '—') ?></span>
        </div>
    </div>

    <div class="action-row" style="padding: 0 16px 12px;">
        <a href="<?= site_url('coordenador/vendedor/' . $vendedor['matricula'] . '/editar') ?>" style="color:#1e40af;">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= site_url('coordenador/vendedor/' . $vendedor['matricula'] . '/transferir') ?>" style="color:#f59e0b;">
            <i class="bi bi-shuffle"></i> Transferir
        </a>
        <form action="<?= site_url('coordenador/vendedor/' . $vendedor['matricula'] . '/desativar') ?>" method="post" onsubmit="return confirm('Tem certeza que deseja desativar este vendedor?');" style="display:flex; flex:1;">
            <button type="submit" style="flex:1; padding:12px; border-radius:12px; border:1.5px solid #e5e7eb; background:#fff; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; color:#dc2626; transition:all .2s;">
                <i class="bi bi-person-x"></i> Desativar
            </button>
        </form>
    </div>

    <div style="display:flex;gap:10px;padding:0 16px 12px;">
        <div class="info-card" style="flex:1;margin:0;">
            <div style="text-align:center;">
                <div style="font-size:28px;font-weight:800;color:#1e40af;"><?= $totalClientes ?></div>
                <div style="font-size:11px;color:#94a3b8;font-weight:600;">CLIENTES</div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h6><i class="bi bi-bar-chart"></i> Por Categoria</h6>
        <?php foreach ($categorias as $c): ?>
            <div class="dist-row">
                <span class="label"><?= esc($c['categoria'] ?? '—') ?></span>
                <span class="value"><?= $c['total'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="info-card">
        <h6><i class="bi bi-recycle"></i> Por Ciclo de Vida</h6>
        <?php foreach ($ciclos as $c): ?>
            <div class="dist-row">
                <span class="label"><?= esc($c['ciclo_de_vida'] ?? '—') ?></span>
                <span class="value"><?= $c['total'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="action-row">
        <a href="<?= site_url('coordenador/vendedor/' . $vendedor['matricula'] . '/clientes') ?>">
            <i class="bi bi-list-ul"></i> Ver Clientes
        </a>
    </div>
</div>

<?= $this->endSection() ?>
