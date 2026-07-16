<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><i class="bi bi-geo"></i> Gestão Manual de Localizações</h1>
            <p class="text-muted">Busque clientes da carteira geral para salvar as coordenadas geográficas copiadas manualmente do Google Maps.</p>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= site_url('admin/localizacao') ?>" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por CNPJ ou Razão Social..." value="<?= esc($busca ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grid de Clientes -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0" style="font-size: 14px; font-weight:700;">Resultados Encontrados</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Razão Social / CNPJ</th>
                        <th>Endereço Cadastrado</th>
                        <th style="width: 320px;">Coordenadas (Lat, Long)</th>
                        <th style="width: 120px; text-align: right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Nenhum cliente encontrado. Digite um termo acima.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($c['razao_social']) ?></div>
                                    <small class="text-muted text-monospace"><?= esc($c['cnpj']) ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= esc(($c['tipo_logradouro'] ?? '') . ' ' . ($c['logradouro'] ?? '')) ?>, <?= esc($c['numero'] ?? '') ?> - 
                                        <?= esc($c['bairro'] ?? '') ?>, <?= esc($c['municipio_nome'] ?? '') ?>/<?= esc($c['uf'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <form id="form-<?= esc($c['cnpj']) ?>" onsubmit="salvarCoordenadas(event, '<?= esc($c['cnpj']) ?>')">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="lat" class="form-control" placeholder="Latitude (Ex: -23.5505)" value="<?= esc($c['latitude'] ?? '') ?>" required>
                                            <input type="text" name="lng" class="form-control" placeholder="Longitude (Ex: -46.6333)" value="<?= esc($c['longitude'] ?? '') ?>" required>
                                        </div>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <button type="submit" form="form-<?= esc($c['cnpj']) ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-save"></i> Salvar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function salvarCoordenadas(event, cnpj) {
    event.preventDefault();
    const form = event.target;
    const lat = form.elements['lat'].value;
    const lng = form.elements['lng'].value;
    const btn = form.querySelector('button') || document.querySelector(`button[form="form-${cnpj}"]`);
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    try {
        const res = await fetch('<?= site_url('admin/localizacao') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cnpj=${cnpj}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Coordenadas salvas com sucesso!');
        } else {
            alert('Erro: ' + (data.error || 'Falha ao salvar.'));
        }
    } catch(e) {
        alert('Erro na comunicação com o servidor.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save"></i> Salvar';
    }
}
</script>

<?= $this->endSection() ?>
