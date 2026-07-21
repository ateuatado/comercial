<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.coord-container { max-width:600px; margin:0 auto; background:#f0f2f5; min-height:100vh; }
.coord-topbar { display:flex; align-items:center; gap:12px; padding:12px 16px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:100; }
.coord-topbar .back-btn { width:36px; height:36px; border-radius:50%; background:#f3f4f6; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; color:#374151; cursor:pointer; text-decoration:none; }
.coord-topbar h6 { margin:0; font-weight:700; font-size:15px; }
.form-card { background:#fff; border-radius:14px; padding:20px; margin:16px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border-radius:10px; border:1px solid #e5e7eb; padding:10px 14px; font-size:14px; }
.form-control:focus, .form-select:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
</style>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('coordenador') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6><?= esc($titulo ?? 'Novo Vendedor') ?></h6>
    </div>

    <div class="form-card">
        <form action="<?= esc($action) ?>" method="post">
            <div class="mb-3">
                <label class="form-label">Matrícula <span class="text-danger">*</span></label>
                <input type="text" name="matricula" class="form-control" value="<?= esc($vendedor['matricula'] ?? '') ?>" required <?= !empty($vendedor['matricula']) ? 'readonly' : '' ?>>
                <?php if(!empty($vendedor['matricula'])): ?>
                    <small class="text-muted">A matrícula não pode ser alterada.</small>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" name="nome" class="form-control" value="<?= esc($vendedor['nome'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" value="<?= esc($vendedor['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Perfil Vendedor <span class="text-danger">*</span></label>
                <select name="perfil_vendedor" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php if(!empty($perfis)): foreach($perfis as $p): ?>
                        <option value="<?= esc($p) ?>" <?= (isset($vendedor['perfil_vendedor']) && $vendedor['perfil_vendedor'] === $p) ? 'selected' : '' ?>><?= esc($p) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">SE</label>
                <input type="text" class="form-control bg-light" value="<?= esc($vendorUser['se'] ?? '') ?>" readonly>
            </div>

            <div class="mb-4">
                <label class="form-label">Gerência</label>
                <input type="text" class="form-control bg-light" value="<?= esc($vendorUser['gerencia'] ?? '') ?>" readonly>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= site_url('coordenador') ?>" class="btn btn-light" style="flex:1; border-radius:10px; font-weight:600;">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="flex:1; border-radius:10px; font-weight:600;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
