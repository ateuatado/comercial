<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --neutral-light: #f8fafc;
    --border-color: #e2e8f0;
}

.mock-container {
    max-width: 480px;
    margin: 0 auto;
    background: var(--neutral-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.mock-header {
    background: #fff;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mock-header h1 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: var(--primary);
}

.back-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex; align-items: center; justify-content: center;
    color: #475569;
    cursor: pointer;
}

.mock-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 24px;
    text-align: center;
}

.mock-card {
    background: #fff;
    border-radius: 24px;
    padding: 32px 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border: 1.5px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-light);
    font-size: 40px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.mock-card h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.mock-card p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

.badge-contract {
    background: #fef3c7;
    color: #d97706;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 16px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-back-action {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: background 0.2s;
}

.btn-back-action:hover {
    background: var(--primary-light);
}
</style>

<div class="mock-container">
    <div class="mock-header">
        <button class="back-btn" onclick="window.history.back()">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h1>Serviço de Geolocalização</h1>
        <div style="width: 36px;"></div> <!-- Spacer -->
    </div>

    <div class="mock-content">
        <div class="mock-card">
            <div class="icon-wrapper">
                <i class="bi bi-google"></i>
            </div>
            
            <span class="badge-contract">Aguardando Contrato</span>
            
            <h2>Google Maps API Enterprise</h2>
            
            <p>
                A integração nativa com o serviço corporativo do Google Maps para geocodificação de endereços em tempo real e visualização de rotas com trânsito ao vivo encontra-se desenvolvida e homologada técnica.
            </p>
            <p>
                A ativação oficial desta funcionalidade em ambiente de produção está condicionada à celebração final do contrato de faturamento sob demanda (Billing Account) junto à Google Cloud Platform (GCP).
            </p>
            
            <button class="btn-back-action" onclick="window.history.back()">
                Voltar ao Radar Alternativo
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
