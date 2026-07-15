<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3">Nova Suspeita de Prospecção</h1>
            <p class="text-muted">Registre um alerta com base em CPF de sócio e CNPJ relacionado.</p>
        </div>
        <a href="/admin/prospecting" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Corrija os erros antes de continuar:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach (session('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="/admin/prospecting/nova" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="row g-3">

                    <!-- CNPJ suspeito -->
                    <div class="col-md-6">
                        <label for="input-cnpj" class="form-label fw-semibold">
                            CNPJ Suspeito <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="input-cnpj"
                               name="cnpj"
                               class="form-control font-monospace"
                               maxlength="14"
                               placeholder="14 dígitos sem formatação"
                               value="<?= esc(old('cnpj')) ?>"
                               required>
                        <div class="form-text">Somente números, sem pontos ou barras.</div>
                    </div>

                    <!-- CPF do sócio -->
                    <div class="col-md-6">
                        <label for="input-cpf-socio" class="form-label fw-semibold">
                            CPF do Sócio <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="input-cpf-socio"
                               name="cpf_socio"
                               class="form-control font-monospace"
                               maxlength="11"
                               placeholder="11 dígitos sem formatação"
                               value="<?= esc(old('cpf_socio')) ?>"
                               required>
                        <div class="form-text">CPF que disparou o alerta.</div>
                    </div>

                    <!-- CNPJ relacionado -->
                    <div class="col-md-6">
                        <label for="input-cnpj-relacionado" class="form-label fw-semibold">
                            CNPJ Relacionado
                        </label>
                        <input type="text"
                               id="input-cnpj-relacionado"
                               name="cnpj_relacionado"
                               class="form-control font-monospace"
                               maxlength="14"
                               placeholder="CNPJ com histórico problemático (opcional)"
                               value="<?= esc(old('cnpj_relacionado')) ?>">
                        <div class="form-text">CNPJ que originou o histórico problemático, se houver.</div>
                    </div>

                    <!-- Motivo -->
                    <div class="col-12">
                        <label for="input-motivo" class="form-label fw-semibold">
                            Motivo do Alerta <span class="text-danger">*</span>
                        </label>
                        <textarea id="input-motivo"
                                  name="motivo"
                                  class="form-control"
                                  rows="4"
                                  minlength="10"
                                  placeholder="Descreva a razão da suspeita de forma clara e objetiva."
                                  required><?= esc(old('motivo')) ?></textarea>
                    </div>

                    <!-- Complemento -->
                    <div class="col-12">
                        <label for="input-complemento" class="form-label fw-semibold">
                            Informações Complementares
                        </label>
                        <textarea id="input-complemento"
                                  name="complemento"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Dados adicionais relevantes para a análise (opcional)."><?= esc(old('complemento')) ?></textarea>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/prospecting" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-exclamation me-1"></i> Registrar Suspeita
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/prospecting.js') ?>"></script>
<?= $this->endSection() ?>
