<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.coord-container { max-width:600px; margin:0 auto; background:#f0f2f5; min-height:100vh; }
.coord-topbar { display:flex; align-items:center; gap:12px; padding:12px 16px; background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:100; }
.coord-topbar .back-btn { width:36px; height:36px; border-radius:50%; background:#f3f4f6; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; color:#374151; cursor:pointer; text-decoration:none; }
.coord-topbar h6 { margin:0; font-weight:700; font-size:15px; flex:1; }
.search-container { padding: 16px; background: #fff; border-bottom: 1px solid #e5e7eb; position:sticky; top:61px; z-index:99; }
.search-input { width:100%; padding:10px 14px; border-radius:10px; border:1px solid #e5e7eb; font-size:14px; }
.search-input:focus { border-color:#3b82f6; outline:none; }
.kpi-row { padding: 16px; display:flex; justify-content:center; }
.kpi-badge { background:#1e40af; color:#fff; padding:6px 12px; border-radius:20px; font-weight:700; font-size:13px; }
.client-list { padding:0 16px 80px; }
.ct-item { display:flex; align-items:center; gap:10px; background:#fff; border-radius:12px; padding:12px 14px; margin-bottom:8px; box-shadow:0 1px 3px rgba(0,0,0,.03); cursor:pointer; }
.ct-checkbox { width:20px; height:20px; flex-shrink:0; cursor:pointer; }
.ct-info { flex:1; min-width:0; }
.ct-info .name { font-size:13px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ct-info .sub { font-size:11px; color:#94a3b8; margin-top:2px; }
.ct-badge { font-size:10px; padding:3px 8px; border-radius:6px; font-weight:600; flex-shrink:0; background:#e2e8f0; color:#475569; }
.sticky-bottom-bar { position:fixed; bottom:0; left:0; width:100%; background:#fff; padding:16px; box-shadow:0 -4px 12px rgba(0,0,0,.05); border-top:1px solid #e5e7eb; display:none; z-index:100; text-align:center; }
.sticky-bottom-bar.show { display:block; }
</style>

<div class="coord-container">
    <div class="coord-topbar">
        <a href="<?= site_url('coordenador') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>Clientes Livres</h6>
    </div>

    <div class="search-container">
        <form action="<?= site_url('coordenador/clientes-livres') ?>" method="get" id="searchForm">
            <input type="text" name="q" class="search-input" placeholder="Buscar por razão social ou CNPJ..." value="<?= esc($busca ?? '') ?>" id="searchInput">
        </form>
    </div>

    <div class="kpi-row">
        <div class="kpi-badge">
            <?= count($clientes) ?> Clientes Encontrados
        </div>
    </div>

    <div class="client-list">
        <?php if (empty($clientes)): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <p>Nenhum cliente livre encontrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clientes as $c): 
                $cnpj = $c['cnpj'] ?? '';
                $cnpjFmt = strlen($cnpj) === 14 ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2) : $cnpj;
            ?>
                <label class="ct-item">
                    <input type="checkbox" name="cnpjs[]" value="<?= esc($cnpj) ?>" class="ct-checkbox client-cb">
                    <div class="ct-info">
                        <div class="name"><?= esc($c['razao_social'] ?? '—') ?></div>
                        <div class="sub"><?= $cnpjFmt ?> · <?= esc($c['segmento_mercado'] ?? '') ?></div>
                    </div>
                    <span class="ct-badge"><?= esc($c['categoria'] ?? '—') ?></span>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="sticky-bottom-bar" id="assignBar">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal" style="width: 100%; max-width: 600px; border-radius: 12px; font-weight: 600; background:#8b5cf6; border-color:#8b5cf6;">
        Atribuir Selecionados (<span id="selCount">0</span>)
    </button>
</div>

<!-- Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <form action="<?= site_url('coordenador/clientes-livres/atribuir') ?>" method="post" id="assignForm">
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <h5 class="modal-title" style="font-weight:700; font-size:16px;">Atribuir Clientes Livres</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hiddenInputs"></div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px; font-weight:600; color:#374151;">Vendedor Destino</label>
                        <select name="matricula_destino" class="form-select" required style="border-radius:10px;">
                            <option value="">Selecione...</option>
                            <?php if(!empty($time)): foreach($time as $v): ?>
                                <option value="<?= esc($v['matricula']) ?>"><?= esc($v['nome'] ?? $v['matricula']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:none;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px; font-weight:600;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px; font-weight:600; background:#8b5cf6; border-color:#8b5cf6;">Confirmar Atribuição</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let timeout = null;
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchForm.submit();
            }, 600);
        });
    }

    const checkboxes = document.querySelectorAll('.client-cb');
    const assignBar = document.getElementById('assignBar');
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
            assignBar.classList.add('show');
        } else {
            assignBar.classList.remove('show');
        }
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
});
</script>

<?= $this->endSection() ?>
