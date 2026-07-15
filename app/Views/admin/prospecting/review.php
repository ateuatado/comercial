<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4">

    <div class="spiv-page-header mb-4">
        <div>
            <h1 class="h3">Revisar Suspeita #<?= $flag['id'] ?></h1>
            <p class="text-muted">
                <code><?= esc($flag['cnpj']) ?></code>
                <?php if ($flag['razao_social']): ?>
                    · <?= esc($flag['razao_social']) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="/admin/prospecting/<?= $flag['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Corrija os erros:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach (session('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Resumo da suspeita (somente leitura) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-shield-exclamation me-2 text-warning"></i>Suspeita</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">CNPJ</dt>
                        <dd class="col-sm-7"><code><?= esc($flag['cnpj']) ?></code></dd>

                        <dt class="col-sm-5">CPF do Sócio</dt>
                        <dd class="col-sm-7"><code><?= esc($flag['cpf_socio']) ?></code></dd>

                        <?php if ($flag['cnpj_relacionado']): ?>
                            <dt class="col-sm-5">CNPJ Rel.</dt>
                            <dd class="col-sm-7"><code><?= esc($flag['cnpj_relacionado']) ?></code></dd>
                        <?php endif; ?>

                        <dt class="col-sm-5 mt-2">Motivo</dt>
                        <dd class="col-sm-7 mt-2"><?= nl2br(esc($flag['motivo'])) ?></dd>

                        <?php if ($flag['complemento']): ?>
                            <dt class="col-sm-5 mt-2">Complemento</dt>
                            <dd class="col-sm-7 mt-2 text-muted"><?= nl2br(esc($flag['complemento'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Formulário de decisão -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-check2-circle me-2"></i>Sua Decisão</h5>
                </div>
                <div class="card-body">
                    <form id="form-review"
                          action="/admin/prospecting/<?= $flag['id'] ?>/revisar"
                          method="post"
                          novalidate>
                        <?= csrf_field() ?>

                        <!-- Decisão -->
                        <fieldset class="mb-4">
                            <legend class="form-label fw-semibold">
                                Decisão <span class="text-danger">*</span>
                            </legend>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="decisao"
                                           id="decisao-liberado"
                                           value="liberado"
                                           <?= old('decisao') === 'liberado' ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label text-success fw-semibold" for="decisao-liberado">
                                        <i class="bi bi-check-circle me-1"></i> Liberar para carteira
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="decisao"
                                           id="decisao-rejeitado"
                                           value="rejeitado"
                                           <?= old('decisao') === 'rejeitado' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-danger fw-semibold" for="decisao-rejeitado">
                                        <i class="bi bi-x-circle me-1"></i> Rejeitar suspeita
                                    </label>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Justificativa -->
                        <div class="mb-4">
                            <label for="input-justificativa" class="form-label fw-semibold">
                                Justificativa <span class="text-danger">*</span>
                            </label>
                            <textarea id="input-justificativa"
                                      name="justificativa"
                                      class="form-control"
                                      rows="5"
                                      minlength="10"
                                      placeholder="Descreva os critérios que embasam esta decisão. Mínimo 10 caracteres."
                                      required><?= esc(old('justificativa')) ?></textarea>
                            <div class="form-text">Este registro é imutável e fica no histórico da suspeita.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/admin/prospecting/<?= $flag['id'] ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Confirmar Decisão
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/prospecting.js') ?>"></script>
<?= $this->endSection() ?>
