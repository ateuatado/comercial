<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($page_title ?? 'Scanner Reclame Aqui') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-8">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-search-dollar text-primary me-2"></i> Scanner Reclame Aqui
        </h1>
        <p class="text-muted mb-0">Encontre reclamações sobre logística reversa, frete e atrasos no Reclame Aqui.</p>
    </div>
</div>

<div class="row">
    <!-- Scanner Form -->
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Buscar Empresa pelo CNPJ</h6>
            </div>
            <div class="card-body bg-light">
                <form id="scannerForm" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label for="cnpj" class="visually-hidden">CNPJ</label>
                        <input type="text" class="form-control form-control-lg cnpj-mask" id="cnpj" name="cnpj" placeholder="Digite o CNPJ da empresa" required>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnScan">
                            <i class="fas fa-search me-2"></i> Escanear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Results Area -->
<div class="row d-none" id="resultsArea">
    <div class="col-12">
        <div class="card shadow mb-4 border-left-success">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success" id="resultTitle">Resultados para: <span></span></h6>
            </div>
            <div class="card-body">
                <div id="loadingIndicator" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <h5 class="mt-3 text-muted">Acessando API do Serper...</h5>
                </div>
                
                <div id="errorMessage" class="alert alert-danger d-none"></div>

                <div id="resultsList" class="row g-3">
                    <!-- Resultados via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function() {
    $('.cnpj-mask').mask('00.000.000/0000-00');

    $('#scannerForm').on('submit', function(e) {
        e.preventDefault();
        const cnpj = $('#cnpj').val();
        const btn = $('#btnScan');
        const resultsArea = $('#resultsArea');
        const resultsList = $('#resultsList');
        const loading = $('#loadingIndicator');
        const errorMsg = $('#errorMessage');

        // Reset UI
        btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-2"></i> Buscando...');
        resultsList.empty();
        errorMsg.addClass('d-none');
        resultsArea.removeClass('d-none');
        loading.removeClass('d-none');
        $('#resultTitle span').text(cnpj);

        $.ajax({
            url: '<?= site_url('admin/reclame-aqui/scan') ?>',
            method: 'POST',
            data: { cnpj: cnpj },
            success: function(response) {
                loading.addClass('d-none');
                btn.prop('disabled', false).html('<i class="fas fa-search me-2"></i> Escanear');
                
                if (response.success) {
                    $('#resultTitle span').text(response.empresa);
                    
                    if (response.resultados && response.resultados.length > 0) {
                        response.resultados.forEach(function(item) {
                            const snippet = item.snippet ? item.snippet : 'Sem descrição disponível.';
                            const title = item.title ? item.title : 'Reclamação';
                            
                            const card = `
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-left-warning shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title font-weight-bold text-dark text-truncate" title="${title}">${title}</h6>
                                        <p class="card-text text-muted small">${snippet}</p>
                                        <a href="${item.link}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> Ver no Reclame Aqui
                                        </a>
                                    </div>
                                </div>
                            </div>`;
                            resultsList.append(card);
                        });
                    } else {
                        resultsList.append('<div class="col-12"><div class="alert alert-info">Nenhuma reclamação sobre frete/postal encontrada para esta empresa recente.</div></div>');
                    }
                }
            },
            error: function(xhr) {
                loading.addClass('d-none');
                btn.prop('disabled', false).html('<i class="fas fa-search me-2"></i> Escanear');
                
                let err = 'Erro desconhecido ao processar a busca.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    err = xhr.responseJSON.error;
                }
                
                errorMsg.text(err).removeClass('d-none');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
