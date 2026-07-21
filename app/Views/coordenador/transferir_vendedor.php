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
.form-card { background:#fff; border-radius:14px; padding:20px; margin:0 16px 16px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border-radius:10px; border:1px solid #e5e7eb; padding:10px 14px; font-size:14px; }
.form-control:focus, .form-select:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
.alert-warning { background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px; border-radius:10px; font-size:13px; display:flex; gap:8px; align-items:flex-start; margin-bottom:16px; }
</style>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('coordenador/vendedor/' . ($vendedor['matricula'] ?? '')) ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>Transferir Vendedor</h6>
    </div>

    <div class="profile-card">
        <div class="name"><?= esc($vendedor['nome'] ?? 'Matrícula ' . ($vendedor['matricula'] ?? '')) ?></div>
        <div class="meta">
            <span><i class="bi bi-person-badge"></i> <?= esc($vendedor['matricula'] ?? '') ?></span>
            <span><i class="bi bi-tag"></i> <?= esc($vendedor['perfil_vendedor'] ?? '—') ?></span>
            <span><i class="bi bi-diagram-3"></i> <?= esc($vendorUser['gerencia'] ?? '—') ?></span>
        </div>
    </div>

    <div class="form-card">
        <?php if(empty($coordenadores)): ?>
            <div style="text-align:center; padding:20px; color:#94a3b8;">
                <i class="bi bi-info-circle" style="font-size:32px; color:#94a3b8; margin-bottom:12px; display:block;"></i>
                <p>Não há outros coordenadores disponíveis na mesma gerência (<?= esc($vendorUser['gerencia'] ?? '—') ?>) para realizar a transferência.</p>
            </div>
            <div class="d-flex justify-content-center">
                <a href="<?= site_url('coordenador/vendedor/' . ($vendedor['matricula'] ?? '')) ?>" class="btn btn-light" style="border-radius:10px; font-weight:600;">Voltar</a>
            </div>
        <?php else: ?>
            <form action="<?= site_url('coordenador/vendedor/' . ($vendedor['matricula'] ?? '') . '/transferir') ?>" method="post">
                
                <div class="alert-warning">
                    <i class="bi bi-exclamation-triangle-fill" style="margin-top:2px;"></i>
                    <div>
                        <strong>Atenção:</strong> Os clientes do vendedor não são transferidos — apenas o vínculo hierárquico muda.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Coordenador Destino <span class="text-danger">*</span></label>
                    <select name="matricula_destino" class="form-select" required>
                        <option value="">Selecione um coordenador...</option>
                        <?php foreach($coordenadores as $c): ?>
                            <option value="<?= esc($c['matricula']) ?>"><?= esc($c['nome'] ?? $c['matricula']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <textarea name="motivo" class="form-control" rows="3" required></textarea>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= site_url('coordenador/vendedor/' . ($vendedor['matricula'] ?? '')) ?>" class="btn btn-light" style="flex:1; border-radius:10px; font-weight:600;">Cancelar</a>
                    <button type="submit" class="btn btn-danger" style="flex:1; border-radius:10px; font-weight:600;">Confirmar Transferência</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
