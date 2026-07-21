<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.coord-container { max-width:600px; margin:0 auto; background:#f0f2f5; min-height:100vh; }
.coord-topbar { display:flex; align-items:center; gap:12px; padding:12px 16px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:100; }
.coord-topbar .back-btn { width:36px; height:36px; border-radius:50%; background:#f3f4f6; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; color:#374151; cursor:pointer; text-decoration:none; }
.coord-topbar h6 { margin:0; font-weight:700; font-size:15px; flex:1; }
.coord-topbar .counter { font-size:12px; color:#94a3b8; font-weight:600; }
.client-table { padding:16px; }
.ct-item { display:flex; align-items:center; gap:10px; background:#fff; border-radius:12px; padding:12px 14px; margin-bottom:8px; box-shadow:0 1px 3px rgba(0,0,0,.03); }
.ct-cat { width:8px; height:40px; border-radius:4px; flex-shrink:0; }
.ct-info { flex:1; min-width:0; }
.ct-info .name { font-size:13px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ct-info .sub { font-size:11px; color:#94a3b8; margin-top:2px; }
.ct-badge { font-size:10px; padding:3px 8px; border-radius:6px; font-weight:600; flex-shrink:0; }
.sticky-bottom-bar { position:fixed; bottom:0; left:0; width:100%; background:#fff; padding:16px; box-shadow:0 -4px 12px rgba(0,0,0,.05); border-top:1px solid #e5e7eb; display:none; z-index:100; text-align:center; }
.sticky-bottom-bar.show { display:block; }
.ct-checkbox { width:20px; height:20px; flex-shrink:0; cursor:pointer; }
</style>

<?php
    $catColors = ['BRONZE'=>'#cd7f32','OURO'=>'#b8860b','PRATA'=>'#8a8a8a','DIAMANTE'=>'#185abc','PLATINUM'=>'#6b21a8','INFINITE'=>'#1e293b','CLUBE'=>'#047857'];
?>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('coordenador/vendedor/' . $vendedor['matricula']) ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>Clientes de <?= esc($vendedor['nome'] ?? $vendedor['matricula']) ?></h6>
        <span class="counter"><?= count($clientes) ?></span>
    </div>

    <div class="client-table">
        <?php foreach ($clientes as $c):
            $cat = strtoupper($c['categoria'] ?? '');
            $cor = $catColors[$cat] ?? '#94a3b8';
            $cnpj = $c['cnpj'] ?? '';
            $cnpjFmt = strlen($cnpj) === 14 ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2) : $cnpj;
        ?>
            <label class="ct-item" style="cursor:pointer;">
                <input type="checkbox" name="cnpjs[]" value="<?= esc($cnpj) ?>" class="ct-checkbox client-cb">
                <div class="ct-cat" style="background:<?= $cor ?>"></div>
                <div class="ct-info">
                    <div class="name"><?= esc($c['razao_social'] ?? '—') ?></div>
                    <div class="sub"><?= $cnpjFmt ?> · <?= esc($c['segmento_mercado'] ?? '') ?></div>
                </div>
                <span class="ct-badge" style="background:<?= $cor ?>22;color:<?= $cor ?>"><?= esc($c['categoria'] ?? '—') ?></span>
            </label>
        <?php endforeach; ?>

        <?php if (empty($clientes)): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <p>Nenhum cliente encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="sticky-bottom-bar" id="transferBar">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transferModal" style="width: 100%; max-width: 600px; border-radius: 12px; font-weight: 600;">
        Transferir selecionados (<span id="selCount">0</span>)
    </button>
</div>

<!-- Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <form action="<?= site_url('coordenador/vendedor/' . $vendedor['matricula'] . '/transferir-clientes') ?>" method="post" id="transferForm">
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <h5 class="modal-title" style="font-weight:700; font-size:16px;">Transferir Clientes Selecionados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hiddenInputs"></div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px; font-weight:600; color:#374151;">Vendedor Destino</label>
                        <select name="matricula_destino" class="form-select" required style="border-radius:10px;">
                            <option value="">Selecione...</option>
                            <?php if(!empty($time)): foreach($time as $v): ?>
                                <?php if($v['matricula'] !== $vendedor['matricula']): ?>
                                    <option value="<?= esc($v['matricula']) ?>"><?= esc($v['nome'] ?? $v['matricula']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px; font-weight:600; color:#374151;">Motivo</label>
                        <textarea name="motivo" class="form-control" rows="3" required style="border-radius:10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:none;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px; font-weight:600;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; font-weight:600;">Confirmar Transferência</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.client-cb');
    const transferBar = document.getElementById('transferBar');
    const selCount = document.getElementById('selCount');
    const hiddenInputs = document.getElementById('hiddenInputs');
    
    function updateSelection() {
        let count = 0;
        hiddenInputs.innerHTML = '';
        checkboxes.forEach(cb => {
            if (cb.checked) {
                count++;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cnpjs[]';
                input.value = cb.value;
                hiddenInputs.appendChild(input);
            }
        });
        
        selCount.textContent = count;
        if (count > 0) {
            transferBar.classList.add('show');
        } else {
            transferBar.classList.remove('show');
        }
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
});
</script>

<?= $this->endSection() ?>
