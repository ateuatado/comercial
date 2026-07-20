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

    /* Upload de Anexos */
    .upload-zone {
        border: 2px dashed #ced4da;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        background: #f8f9fa;
        transition: border-color .2s, background .2s;
        position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: #0d6efd;
        background: #e8f0fe;
    }
    .upload-zone input[type=file] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .upload-zone .icon { font-size: 32px; color: #6c757d; margin-bottom: 6px; }
    .upload-zone .hint { font-size: 13px; color: #6c757d; }
    .upload-zone .hint strong { color: #0d6efd; }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 12px;
    }
    .preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: #e9ecef;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .preview-item img {
        width: 100%; height: 100%; object-fit: cover;
    }
    .preview-item.pdf {
        flex-direction: column;
        gap: 4px;
        font-size: 11px;
        color: #495057;
        padding: 4px;
        text-align: center;
    }
    .preview-item .remove-file {
        position: absolute; top: 3px; right: 3px;
        background: rgba(0,0,0,.55); color: #fff;
        border: none; border-radius: 50%;
        width: 20px; height: 20px; font-size: 11px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        line-height: 1;
    }
    .file-count {
        font-size: 12px; color: #6c757d; margin-top: 6px; text-align: center;
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
    <form action="<?= site_url('vendedor/captacao/salvar') ?>" method="POST" enctype="multipart/form-data">
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

        <!-- Upload de Anexos -->
        <div class="form-group">
            <label class="form-label"><i class="bi bi-paperclip"></i> Anexar evidências (opcional)</label>
            <div class="upload-zone" id="uploadZone">
                <input type="file" name="anexos[]" id="anexosInput" multiple accept="image/jpeg,image/png,image/gif,image/webp,application/pdf">
                <div class="icon">📎</div>
                <div class="hint"><strong>Toque para selecionar</strong> ou arraste aqui</div>
                <div class="hint" style="margin-top:4px;">Fotos (JPG, PNG) e PDFs · Máx. 10MB por arquivo</div>
            </div>
            <div id="previewGrid" class="preview-grid" style="display:none;"></div>
            <div id="fileCount" class="file-count"></div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="bi bi-send-fill"></i> Enviar Pedido de Captação
        </button>
    </form>
</div>

<script>
(function(){
    const input   = document.getElementById('anexosInput');
    const zone    = document.getElementById('uploadZone');
    const grid    = document.getElementById('previewGrid');
    const counter = document.getElementById('fileCount');
    let files = [];

    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });
    input.addEventListener('change', () => { addFiles(input.files); input.value = ''; });

    function addFiles(newFiles) {
        Array.from(newFiles).forEach(f => {
            if (f.size > 10*1024*1024) { alert('Arquivo "' + f.name + '" excede 10MB.'); return; }
            files.push(f);
        });
        renderPreviews();
    }

    function renderPreviews() {
        grid.innerHTML = '';
        if (!files.length) { grid.style.display='none'; counter.textContent=''; return; }
        grid.style.display = 'grid';
        files.forEach((f, idx) => {
            const item = document.createElement('div');
            item.className = f.type.startsWith('image/') ? 'preview-item' : 'preview-item pdf';
            if (f.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                item.appendChild(img);
            } else {
                item.innerHTML = '<i class="bi bi-file-earmark-pdf" style="font-size:28px;color:#dc3545;"></i><span style="word-break:break-all;font-size:10px;">' + f.name.substring(0,20) + '</span>';
            }
            const btn = document.createElement('button');
            btn.type='button'; btn.className='remove-file'; btn.textContent='×';
            btn.onclick = () => { files.splice(idx,1); renderPreviews(); };
            item.appendChild(btn);
            grid.appendChild(item);
        });
        counter.textContent = files.length + ' arquivo(s) selecionado(s)';
        syncInput();
    }

    function syncInput() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }
})();
</script>

<?= $this->endSection() ?>
