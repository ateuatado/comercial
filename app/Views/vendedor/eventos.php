<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --neutral-light: #f8fafc;
    --border-color: #e2e8f0;
}

.eventos-container {
    max-width: 540px;
    margin: 0 auto;
    background: var(--neutral-light);
    min-height: 100vh;
    padding-bottom: 30px;
}

.eventos-header {
    background: #fff;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.eventos-header h1 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 6px;
}

.back-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
}

.evento-card {
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid var(--border-color);
    padding: 16px;
    margin: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.15s ease, border-color 0.15s ease;
}

.evento-card:active {
    transform: scale(0.99);
}

.evento-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}

.evento-info {
    font-size: 12px;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 14px;
}

.evento-info-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.evento-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

.meus-contatos-badge {
    font-size: 11px;
    font-weight: 700;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 4px 10px;
    border-radius: 99px;
    border: 1px solid #bfdbfe;
}

.btn-acessar-evento {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}

.btn-acessar-evento:hover {
    background: var(--primary-light);
    color: #fff;
}
</style>

<div class="eventos-container">
    <div class="eventos-header">
        <a href="<?= site_url('vendedor') ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1><i class="bi bi-calendar-event"></i> Eventos & Feiras</h1>
        <div style="width: 36px;"></div>
    </div>

    <div class="p-3">
        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-3" style="font-size: 12px; background: #e0f2fe; color: #0369a1;">
            <i class="bi bi-info-circle-fill me-1"></i>
            Selecione um evento abaixo para buscar CNPJs e registrar abordagens feitas em feiras e congressos.
        </div>

        <?php if (empty($eventos)): ?>
            <div class="text-center py-5 text-muted bg-white rounded-3 border p-4">
                <i class="bi bi-calendar-x" style="font-size: 40px; color: #cbd5e1;"></i>
                <p class="mt-2 mb-0 fw-semibold">Nenhum evento ativo no momento.</p>
                <small>Novos eventos aparecerão aqui quando forem disponibilizados pela equipe comercial.</small>
            </div>
        <?php else: ?>
            <?php foreach ($eventos as $ev): ?>
                <div class="evento-card">
                    <div class="evento-title"><?= esc($ev['nome']) ?></div>
                    <div class="evento-info">
                        <?php if (!empty($ev['local'])): ?>
                            <div class="evento-info-item">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                <span><?= esc($ev['local']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ev['data_inicio'])): ?>
                            <div class="evento-info-item">
                                <i class="bi bi-calendar3 text-primary"></i>
                                <span>
                                    <?= date('d/m/Y', strtotime($ev['data_inicio'])) ?>
                                    <?php if (!empty($ev['data_fim'])): ?>
                                        até <?= date('d/m/Y', strtotime($ev['data_fim'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="evento-footer">
                        <span class="meus-contatos-badge">
                            <i class="bi bi-journal-check me-1"></i><?= (int)$ev['meus_contatos'] ?> registos seus
                        </span>
                        <a href="<?= site_url('vendedor/eventos/' . $ev['id'] . '/busca') ?>" class="btn-acessar-evento">
                            <span>Acessar</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
