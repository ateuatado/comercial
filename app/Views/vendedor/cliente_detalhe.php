<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.detalhe-container {
    max-width: 480px;
    margin: 0 auto;
    background: #f0f2f5;
    min-height: 100vh;
}
.detalhe-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 100;
}
.detalhe-topbar .back-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: #f3f4f6; border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #374151; cursor: pointer;
}
.detalhe-topbar .back-btn:hover { background: #e5e7eb; }
.detalhe-topbar h6 { margin: 0; font-weight: 700; font-size: 15px; }

/* Banner */
.detalhe-banner {
    padding: 20px;
    color: #fff;
}
.detalhe-banner .cat-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1.5px; opacity: .8; margin-bottom: 6px;
}
.detalhe-banner .nome {
    font-size: 18px; font-weight: 800; line-height: 1.3; margin-bottom: 4px;
}
.detalhe-banner .cnpj-fmt {
    font-size: 14px; font-family: 'Courier New', monospace;
    opacity: .85; letter-spacing: .5px;
}

/* Tabs */
.tab-nav {
    display: flex;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 60px;
    z-index: 99;
}
.tab-nav button {
    flex: 1;
    padding: 12px 8px;
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all .2s;
}
.tab-nav button.active {
    color: #1e40af;
    border-bottom-color: #1e40af;
}
.tab-panel {
    display: none;
    padding: 16px;
}
.tab-panel.active { display: block; }

/* Info cards */
.info-card {
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.info-card h6 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
}
.info-row:last-child { border-bottom: none; }
.info-row .label {
    font-size: 12px; color: #94a3b8; font-weight: 500; min-width: 110px;
}
.info-row .value {
    font-size: 13px; color: #1e293b; font-weight: 600; text-align: right;
    flex: 1;
}
.info-row .value.masked {
    color: #cbd5e1; cursor: pointer; user-select: none;
}
.info-row .value.masked.revealed { color: #1e293b; }

/* Tags */
.tags-wrap {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;
}
.tag-pill {
    font-size: 11px; padding: 4px 10px; border-radius: 8px;
    font-weight: 600;
}
.tag-blue { background: #dbeafe; color: #1e40af; }
.tag-green { background: #dcfce7; color: #166534; }
.tag-amber { background: #fef3c7; color: #92400e; }
.tag-gray { background: #f1f5f9; color: #475569; }
.tag-purple { background: #f3e8ff; color: #6b21a8; }

/* Notas timeline */
.note-item {
    display: flex; gap: 10px; padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}
.note-item:last-child { border-bottom: none; }
.note-dot {
    width: 10px; height: 10px; border-radius: 50%;
    flex-shrink: 0; margin-top: 4px;
}
.note-dot.visita { background: #22c55e; }
.note-dot.observacao { background: #3b82f6; }
.note-dot.contato_telefonico { background: #f59e0b; }
.note-dot.reuniao { background: #8b5cf6; }
.note-dot.estrategia { background: #ef4444; }
.note-content { flex: 1; }
.note-content .note-meta {
    font-size: 11px; color: #94a3b8; margin-bottom: 2px;
}
.note-content .note-text {
    font-size: 13px; color: #334155; line-height: 1.4;
}
.sentiment { font-size: 16px; }

/* Action bar */
.action-bar {
    display: flex; gap: 8px; padding: 12px 16px;
    background: #fff; border-top: 1px solid #e5e7eb;
    position: sticky; bottom: 0;
}
.action-bar .act-btn {
    flex: 1; padding: 10px; border-radius: 12px;
    border: 1.5px solid #e5e7eb; background: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    color: #374151; transition: all .2s;
}
.action-bar .act-btn:hover { border-color: #3b82f6; color: #1e40af; }
.action-bar .act-btn.primary {
    background: #1e40af; color: #fff; border-color: #1e40af;
}

/* Empty notes */
.empty-notes {
    text-align: center; padding: 30px; color: #94a3b8;
}
.empty-notes .icon { font-size: 36px; margin-bottom: 8px; }
</style>

<?php
    $catColors = [
        'BRONZE'=>'#cd7f32','OURO'=>'#b8860b','PRATA'=>'#8a8a8a',
        'DIAMANTE'=>'#185abc','PLATINUM'=>'#6b21a8','INFINITE'=>'#1e293b','CLUBE'=>'#047857'
    ];
    $cat = strtoupper($cliente['categoria'] ?? '');
    $catColor = $catColors[$cat] ?? '#64748b';

    $cnpj = $cliente['cnpj'] ?? '';
    $cnpjFmt = strlen($cnpj) === 14
        ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2)
        : $cnpj;

    $tipoLabels = [
        'visita' => 'Visita',
        'observacao' => 'Observação',
        'contato_telefonico' => 'Contato telefônico',
        'reuniao' => 'Reunião',
        'estrategia' => 'Estratégia',
    ];
    $sentimentIcons = ['positivo' => '😊', 'neutro' => '😐', 'negativo' => '😟'];
?>

<div class="detalhe-container">

    <!-- Top bar -->
    <div class="detalhe-topbar">
        <a href="<?= site_url('vendedor/clientes') ?>" class="back-btn"><i class="bi bi-arrow-left"></i></a>
        <h6>Detalhe do Cliente</h6>
    </div>

    <!-- Banner -->
    <div class="detalhe-banner" style="background: linear-gradient(135deg, <?= $catColor ?>, <?= $catColor ?>cc);">
        <div class="cat-label"><?= esc($cliente['categoria'] ?? 'Sem categoria') ?></div>
        <div class="nome"><?= esc($cliente['razao_social'] ?? '—') ?></div>
        <div class="cnpj-fmt"><?= $cnpjFmt ?></div>
    </div>

    <!-- Tabs -->
    <div class="tab-nav">
        <button class="active" data-tab="basicos"><i class="bi bi-card-text"></i> Básicos</button>
        <button data-tab="estendida"><i class="bi bi-journal-text"></i> Notas</button>
        <button data-tab="estrategia"><i class="bi bi-lightbulb"></i> Estratégia</button>
    </div>

    <!-- TAB 1: Dados Básicos -->
    <div class="tab-panel active" id="tab-basicos">
        <!-- Status alerta do CNPJ da API pública -->
        <?php if (!empty($cliente['rfb_situacao_cadastral'])): ?>
            <?php 
                $isAtivo = (strtoupper(trim($cliente['rfb_situacao_cadastral'])) === 'ATIVA');
                $fmtDate = date('d/m/Y H:i', strtotime($cliente['rfb_verificado_em']));
            ?>
            <?php if ($isAtivo): ?>
                <div id="cnpjStatusAlert" class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm py-2.5 px-3 mb-3" style="border-radius: 12px; background-color: #dcfce7; color: #166534; display: flex;">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div style="font-size: 12px; font-weight: 600;">
                        CNPJ ATIVO na Receita Federal (Verificado em <?= $fmtDate ?>).
                    </div>
                </div>
            <?php else: ?>
                <div id="cnpjStatusAlert" class="alert alert-danger d-flex align-items-center gap-2 border-0 shadow-sm py-3 px-3 mb-3" style="border-radius: 12px; background-color: #fee2e2; color: #991b1b; display: flex;">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                    <div style="font-size: 12px; font-weight: 700;">
                        CNPJ INATIVO NA RECEITA FEDERAL<br>
                        <span class="fw-normal" style="font-size: 11px;">Situação Cadastral: <strong class="text-uppercase"><?= esc($cliente['rfb_situacao_cadastral']) ?></strong> (Verificado em <?= $fmtDate ?>)</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div id="cnpjStatusAlert" style="display: none;" class="mb-3"></div>
        <?php endif; ?>

        <div class="info-card">
            <h6><i class="bi bi-building"></i> Dados da Empresa</h6>
            <div class="info-row align-items-center">
                <span class="label">CNPJ</span>
                <span class="value d-flex align-items-center justify-content-end gap-1 flex-wrap">
                    <span id="cnpjVal"><?= $cnpjFmt ?></span>
                    <button class="btn btn-xs btn-outline-primary" id="btnVerificarCnpj" style="font-size: 10px; padding: 2px 6px; border-radius: 6px;">
                        <i class="bi bi-shield-check"></i> Verificar
                    </button>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Razão Social</span>
                <span class="value"><?= esc($cliente['razao_social'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Nat. Jurídica</span>
                <span class="value"><?= esc($cliente['nat_juridica'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Capital Social</span>
                <span class="value masked" onclick="this.classList.toggle('revealed')"
                      data-real="<?= esc($cliente['capital_social'] ?? '—') ?>">
                    ●●●●●●
                </span>
            </div>
        </div>

        <div class="info-card">
            <h6><i class="bi bi-telephone text-primary me-2"></i>Contato e Endereço</h6>
            <div class="info-row">
                <span class="label">Endereço</span>
                <span class="value" style="font-size: 11px; text-align: right; line-height: 1.4;">
                    <?php 
                        $endParts = [];
                        if (!empty($cliente['tipo_logradouro'])) $endParts[] = trim($cliente['tipo_logradouro']);
                        if (!empty($cliente['logradouro'])) $endParts[] = trim($cliente['logradouro']);
                        if (!empty($cliente['numero'])) $endParts[] = 'Nº ' . trim($cliente['numero']);
                        if (!empty($cliente['complemento'])) $endParts[] = trim($cliente['complemento']);
                        if (!empty($cliente['bairro'])) $endParts[] = trim($cliente['bairro']);
                        if (!empty($cliente['municipio_nome'])) $endParts[] = trim($cliente['municipio_nome']);
                        if (!empty($cliente['uf'])) $endParts[] = trim($cliente['uf']);
                        if (!empty($cliente['cep'])) $endParts[] = 'CEP ' . trim($cliente['cep']);
                        
                        echo !empty($endParts) ? esc(implode(', ', $endParts)) : '—';
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Telefone(s)</span>
                <span class="value">
                    <?php 
                        $phones = [];
                        if (!empty($cliente['telefone_1'])) {
                            $ddd1 = !empty($cliente['ddd_1']) ? '(' . trim($cliente['ddd_1']) . ') ' : '';
                            $phones[] = $ddd1 . trim($cliente['telefone_1']);
                        }
                        if (!empty($cliente['telefone_2'])) {
                            $ddd2 = !empty($cliente['ddd_2']) ? '(' . trim($cliente['ddd_2']) . ') ' : '';
                            $phones[] = $ddd2 . trim($cliente['telefone_2']);
                        }
                        echo !empty($phones) ? esc(implode(' / ', $phones)) : '—';
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">E-mail</span>
                <span class="value" style="text-transform: lowercase;">
                    <?= !empty($cliente['email']) ? esc($cliente['email']) : '—' ?>
                </span>
            </div>
        </div>

        <div class="info-card">
            <h6><i class="bi bi-tags"></i> Classificação</h6>
            <div class="info-row">
                <span class="label">Categoria</span>
                <span class="value">
                    <span class="tag-pill" style="background:<?= $catColor ?>22;color:<?= $catColor ?>">
                        <?= esc($cliente['categoria'] ?? '—') ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Segmento</span>
                <span class="value"><?= esc($cliente['segmento_mercado'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Tipo Cliente</span>
                <span class="value"><?= esc($cliente['segmento_cliente'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Ciclo de Vida</span>
                <span class="value"><?= esc($cliente['ciclo_de_vida'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">CNAE</span>
                <span class="value"><?= esc($cliente['cnae'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Desc. CNAE</span>
                <span class="value" style="font-size:11px;"><?= esc($cliente['cnae_desc'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Prospecção</span>
                <span class="value">
                    <?php if (strtoupper($cliente['prospeccao'] ?? '') === 'SIM'): ?>
                        <span class="tag-pill tag-green">Sim</span>
                    <?php else: ?>
                        <span class="tag-pill tag-amber">Não</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="info-card">
            <h6><i class="bi bi-geo-alt"></i> Gestão</h6>
            <div class="info-row">
                <span class="label">SE</span>
                <span class="value"><?= esc($cliente['se'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Gerência</span>
                <span class="value"><?= esc($cliente['gerencia'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Conta</span>
                <span class="value"><?= esc($cliente['conta_nome'] ?? '—') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Grupo</span>
                <span class="value" style="font-size:11px;"><?= esc($cliente['grupo_cliente'] ?? '—') ?></span>
            </div>
            <?php if (!empty($cliente['canais_vendas'])): ?>
            <div style="margin-top:8px">
                <span class="label">Canais de Vendas</span>
                <div class="tags-wrap" style="margin-top:4px;">
                    <?php foreach (explode(',', $cliente['canais_vendas']) as $canal): ?>
                        <span class="tag-pill tag-gray"><?= esc(trim($canal)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 2: Notas e Visitas -->
    <div class="tab-panel" id="tab-estendida">
        <div class="info-card">
            <h6><i class="bi bi-journal-text"></i> Notas e Visitas</h6>

            <?php if (empty($notas)): ?>
                <div class="empty-notes">
                    <div class="icon">📝</div>
                    <p>Nenhuma nota registrada</p>
                    <small>Registre visitas, observações e contatos</small>
                </div>
            <?php else: ?>
                <?php foreach ($notas as $nota): ?>
                    <div class="note-item">
                        <div class="note-dot <?= esc($nota['tipo']) ?>"></div>
                        <div class="note-content">
                            <div class="note-meta">
                                <?= esc($tipoLabels[$nota['tipo']] ?? $nota['tipo']) ?>
                                · <?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?>
                                <?php if (!empty($nota['sentimento'])): ?>
                                    <span class="sentiment"><?= $sentimentIcons[$nota['sentimento']] ?? '' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="note-text"><?= esc($nota['conteudo']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 3: Estratégia -->
    <div class="tab-panel" id="tab-estrategia">
        <!-- Estratégias salvas -->
        <?php if (!empty($estrategias)): ?>
        <div class="info-card">
            <h6><i class="bi bi-check-circle"></i> Estratégia Atual</h6>
            <div class="strat-saved">
                <?php foreach ($estrategias as $e): ?>
                    <div class="strat-block" style="border-left: 4px solid <?= esc($e['cor'] ?? '#ccc') ?>;">
                        <span class="strat-icon"><?= $e['icone'] ?? '📦' ?></span>
                        <span class="strat-name"><?= esc($e['servico_nome'] ?? '—') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Área de composição -->
        <div class="info-card">
            <h6><i class="bi bi-lightbulb"></i> Compor Estratégia</h6>
            <p style="font-size:12px;color:#94a3b8;margin-bottom:12px;">
                Arraste ou toque nos serviços abaixo para adicionar à composição.
            </p>

            <!-- Drop zone -->
            <div class="drop-zone" id="dropZone">
                <div class="drop-placeholder" id="dropPlaceholder">
                    <i class="bi bi-plus-circle"></i>
                    <span>Toque nos serviços para adicionar</span>
                </div>
            </div>

            <!-- Botão salvar -->
            <button class="strat-save-btn" id="btnSalvarEstrategia" style="display:none;">
                <i class="bi bi-check-lg"></i> Salvar Estratégia
            </button>
        </div>

        <!-- Serviços disponíveis -->
        <div class="info-card">
            <h6><i class="bi bi-grid-3x3-gap"></i> Serviços Disponíveis</h6>
            <div class="service-grid" id="serviceGrid">
                <div class="strat-loading">
                    <span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span>
                    Carregando serviços...
                </div>
            </div>
        </div>
    </div>

    <!-- Action bar -->
    <div class="action-bar">
        <a href="<?= site_url('vendedor/cliente/' . $cliente['cnpj'] . '/nota') ?>" class="act-btn primary">
            <i class="bi bi-journal-plus"></i> Nota
        </a>
        <button class="act-btn" onclick="navigator.share?.({title:'<?= esc($cliente['razao_social'] ?? '') ?>',text:'CNPJ: <?= $cnpjFmt ?>'}).catch(()=>{})">
            <i class="bi bi-share"></i> Enviar
        </button>
    </div>

</div>

<!-- Toast -->
<div class="strat-toast" id="stratToast"></div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
/* Strategy blocks */
.strat-saved { display:flex; flex-wrap:wrap; gap:6px; }
.strat-block { display:flex; align-items:center; gap:6px; padding:8px 12px; border-radius:10px; background:#f8fafc; font-size:13px; }
.strat-icon { font-size:18px; }
.strat-name { font-weight:600; color:#334155; }

/* Drop zone */
.drop-zone {
    min-height:80px; border:2px dashed #cbd5e1; border-radius:14px;
    padding:8px; display:flex; flex-wrap:wrap; gap:6px;
    transition: all .2s; margin-bottom:12px;
}
.drop-zone.active { border-color:#3b82f6; background:#eff6ff; }
.drop-placeholder {
    width:100%; display:flex; flex-direction:column; align-items:center;
    justify-content:center; gap:4px; color:#94a3b8; font-size:12px; padding:12px;
}
.drop-placeholder i { font-size:24px; }
.drop-item {
    display:flex; align-items:center; gap:6px; padding:8px 12px;
    border-radius:10px; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.08);
    font-size:13px; cursor:pointer; animation: dropIn .3s ease;
}
.drop-item .remove-btn {
    width:18px; height:18px; border-radius:50%; background:#fee2e2;
    color:#ef4444; border:none; font-size:10px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; margin-left:4px;
}
@keyframes dropIn { from { transform:scale(.8); opacity:0; } to { transform:scale(1); opacity:1; } }

/* Service grid */
.service-grid { display:flex; flex-wrap:wrap; gap:8px; }
.service-block {
    display:flex; align-items:center; gap:8px; padding:10px 14px;
    border-radius:12px; background:#fff; border:2px solid #e5e7eb;
    cursor:pointer; transition: all .2s; flex-basis:calc(50% - 4px);
    min-width:0;
}
.service-block:active { transform:scale(.95); }
.service-block.selected { border-color:#3b82f6; background:#eff6ff; }
.service-block .s-icon { font-size:22px; flex-shrink:0; }
.service-block .s-info { min-width:0; }
.service-block .s-name { font-size:12px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.service-block .s-desc { font-size:10px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Save button */
.strat-save-btn {
    width:100%; padding:12px; border-radius:12px; border:none;
    background:linear-gradient(135deg,#1e40af,#3b82f6); color:#fff;
    font-size:14px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:6px;
    transition:all .2s;
}
.strat-save-btn:hover { box-shadow:0 4px 14px rgba(30,64,175,.3); }
.strat-save-btn:disabled { opacity:.6; cursor:not-allowed; }

.strat-loading { width:100%; text-align:center; padding:20px; color:#94a3b8; font-size:13px; display:flex; align-items:center; justify-content:center; gap:8px; }
.strat-toast { position:fixed; bottom:80px; left:50%; transform:translateX(-50%); background:#1e293b; color:#fff; padding:12px 24px; border-radius:12px; font-size:13px; font-weight:600; box-shadow:0 4px 16px rgba(0,0,0,.2); opacity:0; transition:opacity .3s; pointer-events:none; z-index:999; }
.strat-toast.show { opacity:1; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    'use strict';

    const SEGMENTO = '<?= esc($cliente['segmento_mercado'] ?? '') ?>';
    const CNPJ = '<?= esc($cliente['cnpj']) ?>';
    const API_SERVICOS = '<?= site_url('vendedor/servicos/') ?>';
    const API_ESTRATEGIA = '<?= site_url('vendedor/estrategia') ?>';

    // Tab switching
    document.querySelectorAll('.tab-nav button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-nav button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            // Lazy-load services on first tab open
            if (btn.dataset.tab === 'estrategia' && !servicesLoaded) loadServices();
        });
    });

    // CNPJ API Verification
    const btnVerificar = document.getElementById('btnVerificarCnpj');
    const alertDiv = document.getElementById('cnpjStatusAlert');

    if (btnVerificar && alertDiv) {
        btnVerificar.addEventListener('click', async () => {
            btnVerificar.disabled = true;
            btnVerificar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width:10px;height:10px;display:inline-block;border:.1em solid currentColor;border-right-color:transparent;border-radius:50%;animation:spinner-border .75s linear infinite;"></span>...';
            alertDiv.style.display = 'none';

            try {
                const res = await fetch('<?= site_url('vendedor/cnpj/verificar/') ?>' + CNPJ, { credentials: 'same-origin' });
                const data = await res.json();

                if (data.success) {
                    if (data.ativo) {
                        alertDiv.className = 'alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm py-2.5 px-3';
                        alertDiv.style.borderRadius = '12px';
                        alertDiv.style.backgroundColor = '#dcfce7';
                        alertDiv.style.color = '#166534';
                        alertDiv.innerHTML = `
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div style="font-size: 12px; font-weight: 600;">
                                CNPJ ATIVO na Receita Federal (Situação: ATIVA).
                            </div>
                        `;
                    } else {
                        alertDiv.className = 'alert alert-danger d-flex align-items-center gap-2 border-0 shadow-sm py-3 px-3';
                        alertDiv.style.borderRadius = '12px';
                        alertDiv.style.backgroundColor = '#fee2e2';
                        alertDiv.style.color = '#991b1b';
                        alertDiv.innerHTML = `
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                            <div style="font-size: 12px; font-weight: 700;">
                                CNPJ INATIVO NA RECEITA FEDERAL<br>
                                <span class="fw-normal" style="font-size: 11px;">Situação Cadastral: <strong class="text-uppercase">${data.situacao_cadastral}</strong></span>
                            </div>
                        `;
                    }
                    alertDiv.style.display = 'flex';
                } else {
                    showToast('❌ ' + (data.error || 'Erro ao consultar.'));
                }
            } catch (e) {
                showToast('❌ Erro na requisição.');
            } finally {
                btnVerificar.disabled = false;
                btnVerificar.innerHTML = '<i class="bi bi-shield-check"></i> Verificar';
            }
        });
    }

    // Capital social reveal
    document.querySelectorAll('.masked').forEach(el => {
        el.addEventListener('click', function() {
            if (this.classList.contains('revealed')) {
                this.textContent = '●●●●●●';
                this.classList.remove('revealed');
            } else {
                this.textContent = this.dataset.real;
                this.classList.add('revealed');
            }
        });
    });

    // ─── Drag & Drop / Strategy ─────────────────────────────────
    let servicesLoaded = false;
    let allServices = [];
    let selectedIds = new Set();
    const dropZone = document.getElementById('dropZone');
    const placeholder = document.getElementById('dropPlaceholder');
    const saveBtn = document.getElementById('btnSalvarEstrategia');
    const grid = document.getElementById('serviceGrid');

    async function loadServices() {
        try {
            // Load segment-specific + GERAL
            const [segRes, geralRes] = await Promise.all([
                fetch(API_SERVICOS + encodeURIComponent(SEGMENTO), { credentials: 'same-origin' }).then(r => r.json()),
                fetch(API_SERVICOS + encodeURIComponent('GERAL'), { credentials: 'same-origin' }).then(r => r.json()),
            ]);
            allServices = [...segRes, ...geralRes];
            servicesLoaded = true;
            renderServices();
        } catch (e) {
            grid.innerHTML = '<div class="strat-loading">❌ Erro ao carregar serviços</div>';
        }
    }

    function renderServices() {
        if (allServices.length === 0) {
            grid.innerHTML = '<div class="strat-loading">Nenhum serviço disponível para este segmento.</div>';
            return;
        }
        grid.innerHTML = allServices.map(s => `
            <div class="service-block ${selectedIds.has(s.id) ? 'selected' : ''}" data-id="${s.id}" data-name="${escHtml(s.servico_nome)}" data-icon="${s.icone || '📦'}" data-cor="${s.cor || '#ccc'}">
                <span class="s-icon">${s.icone || '📦'}</span>
                <div class="s-info">
                    <div class="s-name">${escHtml(s.servico_nome)}</div>
                    <div class="s-desc">${escHtml(s.servico_descricao || '')}</div>
                </div>
            </div>
        `).join('');

        // Bind click/tap to add
        grid.querySelectorAll('.service-block').forEach(block => {
            block.addEventListener('click', () => toggleService(block));
        });
    }

    function toggleService(block) {
        const id = parseInt(block.dataset.id);
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            block.classList.remove('selected');
            removeFromDrop(id);
        } else {
            selectedIds.add(id);
            block.classList.add('selected');
            addToDrop(id, block.dataset.icon, block.dataset.name, block.dataset.cor);
        }
        updateUI();
    }

    function addToDrop(id, icon, name, cor) {
        const item = document.createElement('div');
        item.className = 'drop-item';
        item.dataset.id = id;
        item.style.borderLeft = '4px solid ' + cor;
        item.innerHTML = `
            <span class="strat-icon">${icon}</span>
            <span class="strat-name">${escHtml(name)}</span>
            <button class="remove-btn" title="Remover">✕</button>
        `;
        item.querySelector('.remove-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            selectedIds.delete(id);
            item.remove();
            const block = grid.querySelector(`.service-block[data-id="${id}"]`);
            if (block) block.classList.remove('selected');
            updateUI();
        });
        dropZone.appendChild(item);
    }

    function removeFromDrop(id) {
        const item = dropZone.querySelector(`.drop-item[data-id="${id}"]`);
        if (item) item.remove();
    }

    function updateUI() {
        placeholder.style.display = selectedIds.size > 0 ? 'none' : 'flex';
        saveBtn.style.display = selectedIds.size > 0 ? 'flex' : 'none';
    }

    // Save strategy
    saveBtn.addEventListener('click', async () => {
        if (selectedIds.size === 0) return;

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="loading-spinner" style="width:18px;height:18px;border-width:2px;margin:0"></span> Salvando...';

        try {
            const body = new URLSearchParams();
            body.append('cnpj', CNPJ);
            selectedIds.forEach(id => body.append('service_ids[]', id));

            const res = await fetch(API_ESTRATEGIA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body,
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (data.success) {
                showToast('✅ Estratégia salva com sucesso!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.error || 'Erro ao salvar.'));
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar Estratégia';
            }
        } catch (e) {
            showToast('❌ Erro de conexão.');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar Estratégia';
        }
    });

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function showToast(msg) {
        const t = document.getElementById('stratToast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }
})();
</script>
<?= $this->endSection() ?>
