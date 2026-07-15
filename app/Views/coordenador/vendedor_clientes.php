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
            <div class="ct-item">
                <div class="ct-cat" style="background:<?= $cor ?>"></div>
                <div class="ct-info">
                    <div class="name"><?= esc($c['razao_social'] ?? '—') ?></div>
                    <div class="sub"><?= $cnpjFmt ?> · <?= esc($c['segmento_mercado'] ?? '') ?></div>
                </div>
                <span class="ct-badge" style="background:<?= $cor ?>22;color:<?= $cor ?>"><?= esc($c['categoria'] ?? '—') ?></span>
            </div>
        <?php endforeach; ?>

        <?php if (empty($clientes)): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <p>Nenhum cliente encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
