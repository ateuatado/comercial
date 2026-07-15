<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-cloud-upload"></i> Importação de Carteiras</h4>
            <p class="text-muted small mb-0">Faça upload de um CSV do relatório geral de carteiras.</p>
        </div>
        <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats da base -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small text-uppercase fw-bold">Registros na Base</div>
                    <div class="fs-2 fw-bold text-primary"><?= number_format($totalCarteira, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small text-uppercase fw-bold">Vendedores Ativos</div>
                    <div class="fs-2 fw-bold text-success"><?= number_format($totalVendedores, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="<?= site_url('admin/importar/upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm">
                <?= csrf_field() ?>

                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('csv_file').click()">
                    <i class="bi bi-file-earmark-spreadsheet" style="font-size:48px;color:#3b82f6;"></i>
                    <h5 class="mt-3 mb-1">Arraste o CSV aqui ou clique para selecionar</h5>
                    <p class="text-muted small mb-2">Aceita: .csv, .txt — Máximo: 50MB</p>
                    <p class="text-muted small" id="fileInfo" style="display:none;">
                        <i class="bi bi-check-circle text-success"></i>
                        <span id="fileName"></span> — <span id="fileSize"></span>
                    </p>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" style="display:none;" required>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnUpload" disabled>
                        <i class="bi bi-upload me-2"></i>Enviar e Pré-visualizar
                    </button>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Atenção:</strong> A importação substitui toda a base atual. Cada CSV é tratado como a foto completa das carteiras.
                        O arquivo deve conter as 25 colunas do relatório geral, separadas por <code>;</code> (ponto e vírgula).
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Histórico de importações -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="bi bi-clock-history"></i>
            <h6 class="mb-0 fw-bold">Histórico de Importações</h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox" style="font-size:32px;"></i>
                    <p class="mt-2">Nenhuma importação registrada.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Arquivo</th>
                                <th class="text-end">Inseridos</th>
                                <th class="text-end">Ignorados</th>
                                <th>Por</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><small><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></small></td>
                                    <td><code class="small"><?= esc($log['filename']) ?></code></td>
                                    <td class="text-end"><?= number_format($log['inserted'], 0, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format($log['skipped'], 0, ',', '.') ?></td>
                                    <td><small><?= esc($log['imported_by'] ?? '—') ?></small></td>
                                    <td>
                                        <?php
                                        $badge = match($log['status']) {
                                            'concluido'    => 'bg-success',
                                            'processando'  => 'bg-warning text-dark',
                                            'erro'         => 'bg-danger',
                                            default        => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= esc($log['status']) ?></span>
                                        <?php if ($log['status'] === 'erro' && !empty($log['error_message'])): ?>
                                            <i class="bi bi-exclamation-circle text-danger" title="<?= esc($log['error_message']) ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.upload-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: #f8fafc;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: #3b82f6;
    background: #eff6ff;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    const zone = document.getElementById('uploadZone');
    const input = document.getElementById('csv_file');
    const btn = document.getElementById('btnUpload');
    const info = document.getElementById('fileInfo');
    const nameEl = document.getElementById('fileName');
    const sizeEl = document.getElementById('fileSize');

    // Drag & drop
    ['dragenter','dragover'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('dragover'); }));
    zone.addEventListener('drop', ev => {
        const files = ev.dataTransfer.files;
        if (files.length) { input.files = files; showFile(files[0]); }
    });

    input.addEventListener('change', () => { if (input.files.length) showFile(input.files[0]); });

    function showFile(f) {
        nameEl.textContent = f.name;
        sizeEl.textContent = (f.size / 1024 / 1024).toFixed(1) + ' MB';
        info.style.display = 'block';
        btn.disabled = false;
    }
})();
</script>
<?= $this->endSection() ?>
