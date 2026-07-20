<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .prcap-container {
        max-width: 480px;
        margin: 0 auto;
        padding: 16px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: #333;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .prcap-header {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e9ecef;
    }

    .btn-back {
        background: none;
        border: none;
        font-size: 24px;
        color: #495057;
        margin-right: 16px;
        cursor: pointer;
        padding: 0;
    }

    .prcap-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: #212529;
    }

    .prcap-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .cnpj-formatted {
        font-family: monospace;
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .company-name {
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 8px 0;
        color: #212529;
    }

    .company-location {
        font-size: 14px;
        color: #495057;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .badge-score {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        color: white;
    }
    .score-high { background-color: #20c997; }
    .score-medium { background-color: #ffc107; color: #333; }
    .score-low { background-color: #adb5bd; }

    .alert-orange {
        background-color: #fff3cd;
        border: 1px solid #ffe69c;
        color: #856404;
        padding: 12px;
        border-radius: 8px;
        margin-top: 16px;
        font-size: 14px;
        display: flex;
        gap: 8px;
    }

    .alert-blue {
        background-color: #cce5ff;
        border: 1px solid #b8daff;
        color: #004085;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        gap: 8px;
    }

    .evidence-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 16px 0;
        color: #495057;
    }

    .evidence-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 14px;
        color: #495057;
    }
    
    .evidence-item i {
        color: #0d6efd;
        font-size: 18px;
        margin-top: -2px;
    }

    .evidence-item:last-child {
        margin-bottom: 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        color: #495057;
        cursor: pointer;
    }

    .checkbox-label input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .btn-submit {
        background-color: #0d6efd;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 14px 20px;
        font-size: 16px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
    }

    .btn-submit:hover {
        background-color: #0b5ed7;
    }
</style>

<div class="prcap-container">
    <div class="prcap-header">
        <button class="btn-back" onclick="history.back()" aria-label="Voltar">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="prcap-title">Solicitar Adição à Carteira</h1>
    </div>

    <!-- Identificação -->
    <div class="prcap-card">
        <h2 class="company-name"><?= esc($receita['razao_social'] ?? 'Razão Social Indisponível') ?></h2>
        
        <?php $cnpjFormatado = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cleanCnpj); ?>
        <div class="cnpj-formatted"><?= esc($cnpjFormatado) ?></div>
        
        <div class="company-location">
            <i class="bi bi-geo-alt-fill text-secondary"></i>
            <?= esc($receita['municipio_nome'] ?? '') ?>/<?= esc($receita['uf'] ?? '') ?> 
            - <?= esc($receita['situacao_desc'] ?? '') ?>
        </div>

        <?php 
            $score = $enrichment['logistics_score'] ?? 0;
            $scoreClass = $score >= 60 ? 'score-high' : ($score >= 30 ? 'score-medium' : 'score-low');
        ?>
        <div class="badge-score <?= $scoreClass ?>">
            <i class="bi bi-graph-up-arrow me-1" style="margin-right: 6px;"></i>
            Score Preditivo: <?= esc($score) ?>/100
        </div>

        <?php if ($outraCarteira): ?>
            <div class="alert-orange">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>Este CNPJ pertence à carteira de outro vendedor. Sua solicitação será analisada pelo admin.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Evidências -->
    <div class="prcap-card">
        <h3 class="evidence-title">Evidências do Sistema</h3>
        
        <div class="evidence-item">
            <i class="bi bi-geo-alt"></i>
            <div>
                <strong>Geolocalizado em:</strong><br>
                <?= isset($locLog['updated_at']) ? date('d/m/Y H:i', strtotime($locLog['updated_at'])) : 'Não geolocalizado' ?>
            </div>
        </div>

        <div class="evidence-item">
            <i class="bi bi-check-circle"></i>
            <div>
                <strong>CNPJ verificado em:</strong><br>
                <?= isset($walletLog['rfb_verificado_em']) ? date('d/m/Y H:i', strtotime($walletLog['rfb_verificado_em'])) : 'Não verificado' ?>
            </div>
        </div>

        <div class="evidence-item">
            <i class="bi bi-search"></i>
            <div>
                <strong>Redes sociais buscadas em:</strong><br>
                <?= isset($socialLog['dt']) ? date('d/m/Y H:i', strtotime($socialLog['dt'])) : 'Não pesquisadas' ?>
            </div>
        </div>

        <div class="evidence-item">
            <i class="bi bi-journal-text"></i>
            <div>
                <strong>Notas registradas:</strong><br>
                <?= isset($notasLog['total']) ? esc($notasLog['total']) : 0 ?> notas 
                <?= isset($notasLog['dt']) ? '(última em ' . date('d/m/Y H:i', strtotime($notasLog['dt'])) . ')' : '' ?>
            </div>
        </div>
    </div>

    <!-- Feedback do Admin (Se complementação) -->
    <?php if (isset($pedidoExistente) && $pedidoExistente['status'] === 'mais_info'): ?>
        <div class="alert-blue">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <strong>Admin solicitou mais informações:</strong><br>
                <?= esc($pedidoExistente['admin_obs']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form action="<?= site_url('vendedor/captacao/salvar') ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="cnpj" value="<?= esc($cleanCnpj) ?>">

        <div class="form-group">
            <label for="justificativa" class="form-label">Por que você quer este cliente na sua carteira? Descreva o histórico de relacionamento. *</label>
            <textarea name="justificativa" id="justificativa" class="form-control" required placeholder="Ex: Cliente antigo da época de..."><?= isset($pedidoExistente) ? esc($pedidoExistente['justificativa']) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label for="tempo_contato" class="form-label">Há quanto tempo está em negociação?</label>
            <input type="text" name="tempo_contato" id="tempo_contato" class="form-control" placeholder="Ex: 2 meses, 3 semanas...">
        </div>

        <div class="form-group">
            <label class="form-label">Canais de contato utilizados:</label>
            <div class="checkbox-group">
                <label class="checkbox-label"><input type="checkbox" name="canais_contato[]" value="Telefone"> Telefone</label>
                <label class="checkbox-label"><input type="checkbox" name="canais_contato[]" value="E-mail"> E-mail</label>
                <label class="checkbox-label"><input type="checkbox" name="canais_contato[]" value="Visita presencial"> Visita presencial</label>
                <label class="checkbox-label"><input type="checkbox" name="canais_contato[]" value="WhatsApp"> WhatsApp</label>
                <label class="checkbox-label"><input type="checkbox" name="canais_contato[]" value="Outro"> Outro</label>
            </div>
        </div>

        <div class="form-group">
            <label for="referencia_doc" class="form-label">Referência a documento externo (opcional)</label>
            <textarea name="referencia_doc" id="referencia_doc" class="form-control" placeholder="Ex: Link para e-mail, proposta enviada..." style="min-height: 80px;"></textarea>
        </div>

        <button type="submit" class="btn-submit">
            <i class="bi bi-send-fill"></i> Enviar Pedido de Captação
        </button>
    </form>
</div>

<?= $this->endSection() ?>
