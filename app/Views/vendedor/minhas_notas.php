<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.notas-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0;
    min-height: 100vh;
    background: #f8fafc;
}
.notas-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; background: #fff;
    border-bottom: 1px solid #e2e8f0;
    position: sticky; top: 0; z-index: 100;
}
.notas-topbar .back-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: #f1f5f9; border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #334155; cursor: pointer;
    text-decoration: none; transition: background .2s;
}
.notas-topbar .back-btn:hover { background: #e2e8f0; }

.search-filter-box {
    padding: 12px 16px; background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.search-input-wrap {
    position: relative; margin-bottom: 10px;
}
.search-input-wrap input {
    width: 100%; padding: 10px 12px 10px 38px;
    border: 1.5px solid #e2e8f0; border-radius: 12px;
    font-size: 14px; background: #f8fafc; outline: none;
    transition: border-color .2s;
}
.search-input-wrap input:focus { border-color: #3b82f6; background: #fff; }
.search-input-wrap i {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 16px;
}

.chips-scroll {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px;
    scrollbar-width: none;
}
.chips-scroll::-webkit-scrollbar { display: none; }
.chip-btn {
    flex-shrink: 0; padding: 6px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; border: 1.5px solid #e2e8f0;
    background: #fff; color: #475569; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
    transition: all .2s; cursor: pointer;
}
.chip-btn.active {
    background: #1e40af; color: #fff; border-color: #1e40af;
}

.note-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 16px; margin: 12px 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    transition: transform .2s, box-shadow .2s;
}
.note-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.note-card .company-name {
    font-size: 15px; font-weight: 700; color: #0f172a;
    line-height: 1.3; text-decoration: none;
    display: block; margin-bottom: 2px;
}
.note-card .company-name:hover { color: #1d4ed8; }
.note-card .cnpj-code {
    font-size: 12px; color: #64748b; font-family: monospace;
}

.tipo-pill {
    font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: .5px;
}
.tipo-pill.visita { background: #dcfce7; color: #15803d; }
.tipo-pill.observacao { background: #dbeafe; color: #1e40af; }
.tipo-pill.contato_telefonico { background: #fef3c7; color: #b45309; }
.tipo-pill.reuniao { background: #f3e8ff; color: #6b21a8; }
.tipo-pill.estrategia { background: #fee2e2; color: #b91c1c; }

.note-text-box {
    font-size: 13.5px; color: #334155; line-height: 1.5;
    background: #f8fafc; padding: 12px; border-radius: 10px;
    border-left: 3px solid #cbd5e1; margin-top: 10px;
    white-space: pre-wrap; word-break: break-word;
}
</style>

<div class="notas-container">

    <!-- Topbar -->
    <div class="notas-topbar">
        <a href="<?= site_url('vendedor') ?>" class="back-btn" title="Voltar ao Painel">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="text-center">
            <h6 class="m-0 fw-bold fs-6">Minhas Notas</h6>
            <small class="text-muted" style="font-size: 11px;">Histórico Geral de Registros</small>
        </div>
        <span class="badge bg-primary text-white" style="font-size: 11px; padding: 5px 10px; border-radius: 99px;">
            <?= count($notas) ?> <?= count($notas) === 1 ? 'nota' : 'notas' ?>
        </span>
    </div>

    <!-- Filtros e Busca -->
    <div class="search-filter-box">
        <form method="get" action="<?= site_url('vendedor/minhas-notas') ?>" id="formFilter">
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="busca" value="<?= esc($busca) ?>" placeholder="Buscar por cliente, CNPJ ou teor da nota..." onchange="this.form.submit()">
            </div>

            <!-- Chips de Tipo -->
            <div class="chips-scroll mb-2">
                <a href="<?= site_url('vendedor/minhas-notas?' . http_build_query(array_merge($_GET, ['tipo' => '']))) ?>"
                   class="chip-btn <?= empty($tipo) ? 'active' : '' ?>">
                    Todos <span class="opacity-75">(<?= $totalGeral ?>)</span>
                </a>
                <?php
                    $tiposInfo = [
                        'visita' => ['label' => 'Visitas', 'icon' => '🟢'],
                        'observacao' => ['label' => 'Observações', 'icon' => '🔵'],
                        'contato_telefonico' => ['label' => 'Contatos', 'icon' => '🟠'],
                        'reuniao' => ['label' => 'Reuniões', 'icon' => '🟣'],
                        'estrategia' => ['label' => 'Estratégia', 'icon' => '⚡'],
                    ];
                ?>
                <?php foreach ($tiposInfo as $key => $info): ?>
                    <?php $count = $totaisPorTipo[$key] ?? 0; ?>
                    <a href="<?= site_url('vendedor/minhas-notas?' . http_build_query(array_merge($_GET, ['tipo' => $key]))) ?>"
                       class="chip-btn <?= $tipo === $key ? 'active' : '' ?>">
                        <?= $info['icon'] ?> <?= $info['label'] ?> <span class="opacity-75">(<?= $count ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Chips de Visibilidade -->
            <div class="chips-scroll">
                <a href="<?= site_url('vendedor/minhas-notas?' . http_build_query(array_merge($_GET, ['publica' => '']))) ?>"
                   class="chip-btn <?= $publica === '' ? 'active' : '' ?>">
                    Todas Visibilidades
                </a>
                <a href="<?= site_url('vendedor/minhas-notas?' . http_build_query(array_merge($_GET, ['publica' => '1']))) ?>"
                   class="chip-btn <?= $publica === '1' ? 'active' : '' ?>">
                    🌐 Públicas
                </a>
                <a href="<?= site_url('vendedor/minhas-notas?' . http_build_query(array_merge($_GET, ['publica' => '0']))) ?>"
                   class="chip-btn <?= $publica === '0' ? 'active' : '' ?>">
                    🔒 Privadas
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Notas -->
    <div class="py-2">
        <?php if (empty($notas)): ?>
            <div class="text-center py-5 px-3 text-muted">
                <div style="font-size: 42px; margin-bottom: 8px;">📝</div>
                <h6 class="fw-bold">Nenhuma nota encontrada</h6>
                <p class="small mb-3">Não encontramos notas para os filtros selecionados.</p>
                <a href="<?= site_url('vendedor/minhas-notas') ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 10px;">
                    Limpar Filtros
                </a>
            </div>
        <?php else: ?>
            <?php
                $tipoLabels = [
                    'visita' => 'Visita',
                    'observacao' => 'Observação',
                    'contato_telefonico' => 'Contato',
                    'reuniao' => 'Reunião',
                    'estrategia' => 'Estratégia',
                ];
                $sentimentIcons = ['positivo' => '😊', 'neutro' => '😐', 'negativo' => '😟'];
            ?>
            <?php foreach ($notas as $nota): ?>
                <?php
                    $cnpj = $nota['cnpj'];
                    $cnpjFmt = strlen($cnpj) === 14
                        ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2)
                        : $cnpj;
                    $isPublica = !empty($nota['publica']);
                ?>
                <div class="note-card">
                    <!-- Nome do cliente e CNPJ -->
                    <a href="<?= site_url('vendedor/cliente/' . $cnpj) ?>" class="company-name">
                        <?= esc($nota['razao_social'] ?? 'Cliente ' . $cnpj) ?>
                    </a>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="cnpj-code"><i class="bi bi-building me-1"></i><?= $cnpjFmt ?></span>
                        <small class="text-muted" style="font-size: 11px;">
                            <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?>
                        </small>
                    </div>

                    <!-- Tags / Badges -->
                    <div class="d-flex align-items-center gap-1.5 flex-wrap mb-2">
                        <span class="tipo-pill <?= esc($nota['tipo']) ?>">
                            <?= esc($tipoLabels[$nota['tipo']] ?? $nota['tipo']) ?>
                        </span>
                        <?php if (!empty($nota['sentimento'])): ?>
                            <span style="font-size: 14px;"><?= $sentimentIcons[$nota['sentimento']] ?? '' ?></span>
                        <?php endif; ?>

                        <button class="btn-nota-toggle border-0 bg-transparent p-0 ms-auto"
                                data-id="<?= $nota['id'] ?>"
                                data-publica="<?= $isPublica ? '1' : '0' ?>">
                            <?php if ($isPublica): ?>
                                <span class="nota-vis-badge publica" style="font-size:9.5px;background:#dcfce7;color:#166534;border-radius:4px;padding:2px 7px;font-weight:700;">🌐 Pública</span>
                            <?php else: ?>
                                <span class="nota-vis-badge privada" style="font-size:9.5px;background:#f1f5f9;color:#64748b;border-radius:4px;padding:2px 7px;font-weight:700;">🔒 Privada</span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- Conteúdo / Teor da Nota -->
                    <div class="note-text-box">
                        <?= esc($nota['conteudo']) ?>
                    </div>

                    <!-- Rodapé com Ação -->
                    <div class="d-flex justify-content-end mt-3">
                        <a href="<?= site_url('vendedor/cliente/' . $cnpj) ?>" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 4px 10px; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Detalhes do Cliente
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Permite alternar a visibilidade de qualquer nota diretamente da lista
    document.querySelectorAll('.btn-nota-toggle').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            btn.disabled = true;

            try {
                const res = await fetch('<?= site_url('vendedor/nota/') ?>' + id + '/visibilidade', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    const isPub = data.publica;
                    btn.dataset.publica = isPub ? '1' : '0';
                    btn.innerHTML = isPub 
                        ? '<span class="nota-vis-badge publica" style="font-size:9.5px;background:#dcfce7;color:#166534;border-radius:4px;padding:2px 7px;font-weight:700;">🌐 Pública</span>'
                        : '<span class="nota-vis-badge privada" style="font-size:9.5px;background:#f1f5f9;color:#64748b;border-radius:4px;padding:2px 7px;font-weight:700;">🔒 Privada</span>';
                }
            } catch (err) {
                console.error(err);
            } finally {
                btn.disabled = false;
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
