<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$cnpjFmt = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj']);
$score   = (int)($enrichment['logistics_score'] ?? 0);
$scoreColor = $score >= 60 ? '#22c55e' : ($score >= 30 ? '#f59e0b' : '#9ca3af');

$statusCfg = [
    'pendente'  => ['label' => '⏳ Aguardando Decisão', 'cls' => 'warning'],
    'mais_info' => ['label' => '🔵 Mais Informações Solicitadas', 'cls' => 'info'],
    'aprovado'  => ['label' => '✅ Aprovado',  'cls' => 'success'],
    'rejeitado' => ['label' => '❌ Rejeitado', 'cls' => 'danger'],
];
$sc = $statusCfg[$pedido['status']] ?? ['label' => $pedido['status'], 'cls' => 'secondary'];

$canaisArr = json_decode($pedido['canais_contato'] ?? '[]', true) ?: [];
?>

<div class="mb-3">
    <a href="<?= site_url('admin/captacoes') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar à lista
    </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- Coluna esquerda: Dados do pedido -->
    <div class="col-lg-7">

        <!-- Cabeçalho do pedido -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-1"><?= esc($pedido['razao_social']) ?></h5>
                        <span class="font-monospace text-muted"><?= $cnpjFmt ?></span>
                        <?php if ($pedido['cnpj_em_outra_carteira']): ?>
                            <div class="alert alert-warning mt-2 py-2 mb-0">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>CNPJ em disputa:</strong> pertencia à carteira de
                                <code><?= esc($pedido['carteira_anterior']) ?></code> no momento do pedido.
                                Aprovar irá <strong>transferir</strong> o cliente.
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-<?= $sc['cls'] ?> fs-6"><?= $sc['label'] ?></span>
                </div>
            </div>
        </div>

        <!-- Dados da Receita Federal -->
        <?php if ($receita): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2">
                <i class="bi bi-building me-1"></i> Dados da Receita Federal
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-6"><strong>Situação:</strong><br><?= esc($receita['situacao_desc'] ?? '—') ?></div>
                    <div class="col-6"><strong>Capital Social:</strong><br>R$ <?= number_format(($receita['capital_social'] ?? 0)/100, 2, ',', '.') ?></div>
                    <div class="col-6"><strong>Município:</strong><br><?= esc($receita['municipio_nome'] ?? '—') ?> / <?= esc($receita['uf'] ?? '') ?></div>
                    <div class="col-6"><strong>CNAE:</strong><br><?= esc($receita['cnae_fiscal_principal'] ?? '—') ?></div>
                    <div class="col-12"><strong>Endereço:</strong><br>
                        <?= esc(trim(($receita['tipo_logradouro'] ?? '') . ' ' . ($receita['logradouro'] ?? '') . ', ' . ($receita['numero'] ?? '') . ' - ' . ($receita['bairro'] ?? '') . ' - CEP ' . ($receita['cep'] ?? ''))) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Declaração do Vendedor -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2">
                <i class="bi bi-person-lines-fill me-1"></i>
                Declaração de <?= esc($pedido['nome_vendedor'] ?? $pedido['matricula']) ?>
                <small class="text-muted ms-2">(<?= esc($pedido['matricula']) ?>)</small>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Justificativa:</strong>
                    <div class="mt-1 p-3 bg-light rounded"><?= nl2br(esc($pedido['justificativa'])) ?></div>
                </div>
                <?php if ($pedido['tempo_contato']): ?>
                <div class="mb-3">
                    <strong><i class="bi bi-clock me-1"></i>Tempo em contato:</strong>
                    <div><?= esc($pedido['tempo_contato']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($canaisArr)): ?>
                <div class="mb-3">
                    <strong><i class="bi bi-chat-square me-1"></i>Canais utilizados:</strong>
                    <div class="mt-1">
                        <?php foreach ($canaisArr as $canal): ?>
                            <span class="badge bg-secondary me-1"><?= esc($canal) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['referencia_doc']): ?>
                <div>
                    <strong><i class="bi bi-file-earmark-text me-1"></i>Referência a documento:</strong>
                    <div class="mt-1 p-2 border rounded small"><?= nl2br(esc($pedido['referencia_doc'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notas do Vendedor sobre este CNPJ -->
        <?php if (!empty($notas)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2">
                <i class="bi bi-journal-text me-1"></i> Notas Registradas no Sistema
            </div>
            <div class="card-body p-0">
                <?php foreach ($notas as $nota): ?>
                <div class="p-3 border-bottom">
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?>
                        · <?= esc($nota['nome_autor'] ?? '—') ?>
                        · <span class="badge bg-secondary"><?= esc($nota['tipo']) ?></span>
                    </small>
                    <div class="mt-1 small"><?= nl2br(esc($nota['conteudo'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Coluna direita: Evidências do sistema + Score + Decisão -->
    <div class="col-lg-5">

        <!-- Score Preditivo -->
        <?php if ($score > 0): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2">
                <i class="bi bi-graph-up me-1"></i> Score Preditivo
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:64px;height:64px;border-radius:50%;background:<?= $scoreColor ?>22;border:3px solid <?= $scoreColor ?>;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:<?= $scoreColor ?>;">
                        <?= $score ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?= $score >= 60 ? '🔥 Alto Potencial' : ($score >= 30 ? '⚡ Potencial Médio' : '· Baixo Potencial') ?></div>
                        <small class="text-muted">Pontuação de 0 a 100</small>
                    </div>
                </div>
                <?php
                $breakdown = json_decode($enrichment['score_breakdown'] ?? '{}', true);
                if (!empty($breakdown)):
                ?>
                <hr class="my-2">
                <div class="small">
                    <?php foreach ($breakdown as $fator => $pts): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><?= esc(ucfirst(str_replace('_', ' ', $fator))) ?></span>
                        <strong><?= (int)$pts ?> pts</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Evidências do Sistema (Timeline) -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2">
                <i class="bi bi-clock-history me-1"></i> Evidências do Sistema
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2 small">
                    <?php
                    $eventos = [];
                    if (!empty($locLog['updated_at'])) {
                        $eventos[] = ['📍', 'Geolocalizado', $locLog['updated_at'], 'success'];
                    }
                    if (!empty($walletLog['rfb_verificado_em'])) {
                        $eventos[] = ['✅', 'CNPJ verificado na RFB', $walletLog['rfb_verificado_em'], 'success'];
                    }
                    if (!empty($socialLog['dt'])) {
                        $eventos[] = ['🔍', 'Redes sociais buscadas', $socialLog['dt'], 'info'];
                    }
                    usort($eventos, fn($a, $b) => strcmp($b[2], $a[2]));
                    ?>
                    <?php if (empty($eventos)): ?>
                        <div class="text-muted text-center py-2">Nenhuma ação registrada no sistema.</div>
                    <?php else: ?>
                        <?php foreach ($eventos as [$icon, $label, $dt, $cor]): ?>
                        <div class="d-flex align-items-center gap-2 p-2 bg-<?= $cor ?>-subtle rounded">
                            <span><?= $icon ?></span>
                            <div>
                                <div class="fw-semibold"><?= $label ?></div>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($dt)) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($carteiraAtual) && $carteiraAtual['matricula_mcmcu'] !== $pedido['matricula']): ?>
                    <div class="alert alert-warning mt-2 mb-0 py-2 small">
                        <i class="bi bi-person-fill me-1"></i>
                        Atualmente em carteira de <strong><?= esc($carteiraAtual['forca_vendas_nome'] ?? $carteiraAtual['matricula_mcmcu']) ?></strong>
                        (<?= esc($carteiraAtual['categoria']) ?>)
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Painel de Decisão -->
        <?php if (in_array($pedido['status'], ['pendente', 'mais_info'])): ?>
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold py-2">
                <i class="bi bi-gavel me-1"></i> Decisão Administrativa
            </div>
            <div class="card-body">
                <form action="<?= site_url('admin/captacoes/decisao') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observação</label>
                        <textarea name="admin_obs" class="form-control" rows="3"
                            placeholder="Obrigatório para rejeitar ou pedir mais informações. Opcional para aprovação."></textarea>
                        <small class="text-muted">Será exibida ao vendedor.</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="decisao" value="aprovar"
                                class="btn btn-success fw-bold"
                                onclick="return confirm('Confirmar APROVAÇÃO? O cliente será adicionado à carteira do vendedor.')">
                            <i class="bi bi-check-circle me-1"></i> Aprovar — Adicionar à Carteira
                        </button>
                        <button type="submit" name="decisao" value="mais_info"
                                class="btn btn-info fw-bold text-dark">
                            <i class="bi bi-chat-dots me-1"></i> Pedir Mais Informações
                        </button>
                        <button type="submit" name="decisao" value="rejeitar"
                                class="btn btn-outline-danger fw-bold"
                                onclick="return confirm('Confirmar REJEIÇÃO?')">
                            <i class="bi bi-x-circle me-1"></i> Rejeitar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="mb-1"><strong>Decisão:</strong> <?= $sc['label'] ?></p>
                <?php if ($pedido['decided_at']): ?>
                    <p class="mb-1 small text-muted">Em <?= date('d/m/Y H:i', strtotime($pedido['decided_at'])) ?> por <?= esc($pedido['respondido_por']) ?></p>
                <?php endif; ?>
                <?php if ($pedido['admin_obs']): ?>
                    <div class="mt-2 p-2 bg-light rounded small"><?= nl2br(esc($pedido['admin_obs'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?= $this->endSection() ?>
