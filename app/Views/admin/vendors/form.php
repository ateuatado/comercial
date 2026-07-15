<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container py-4" style="max-width: 640px">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="/admin/vendors" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><?= esc($page_title) ?></h1>
    </div>

    <?php
        $old    = $old    ?? ($vendor ?? []);
        $errors = $errors ?? [];
        $val    = static fn(string $k) => esc($old[$k] ?? '');
    ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $action_url ?>">

                <!-- Matrícula -->
                <div class="mb-3">
                    <label for="matricula" class="form-label fw-semibold">
                        Matrícula <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="matricula" name="matricula"
                           class="form-control <?= isset($errors['matricula']) ? 'is-invalid' : '' ?>"
                           value="<?= $val('matricula') ?>"
                           maxlength="20" required>
                    <?php if (isset($errors['matricula'])): ?>
                        <div class="invalid-feedback"><?= esc($errors['matricula']) ?></div>
                    <?php endif ?>
                </div>

                <!-- Nome -->
                <div class="mb-3">
                    <label for="nome" class="form-label fw-semibold">
                        Nome <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="nome" name="nome"
                           class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>"
                           value="<?= $val('nome') ?>"
                           maxlength="200" required>
                    <?php if (isset($errors['nome'])): ?>
                        <div class="invalid-feedback"><?= esc($errors['nome']) ?></div>
                    <?php endif ?>
                </div>

                <!-- Tipo de ACOM -->
                <div class="mb-3">
                    <label for="tipo_acom" class="form-label fw-semibold">Tipo</label>
                    <select id="tipo_acom" name="tipo_acom"
                            class="form-select <?= isset($errors['tipo_acom']) ? 'is-invalid' : '' ?>">
                        <option value="">Gerente de Conta</option>
                        <?php foreach (['I', 'II', 'III'] as $tipo): ?>
                            <option value="<?= $tipo ?>"
                                <?= ($val('tipo_acom') === $tipo) ? 'selected' : '' ?>>
                                ACOM <?= $tipo ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <?php if (isset($errors['tipo_acom'])): ?>
                        <div class="invalid-feedback"><?= esc($errors['tipo_acom']) ?></div>
                    <?php endif ?>
                    <div class="form-text">Deixe em branco para Gerente de Conta.</div>
                </div>

                <!-- Estado / SE -->
                <div class="mb-3">
                    <label for="estado_se" class="form-label fw-semibold">Estado / SE</label>
                    <input type="text" id="estado_se" name="estado_se"
                           class="form-control <?= isset($errors['estado_se']) ? 'is-invalid' : '' ?>"
                           value="<?= $val('estado_se') ?>"
                           maxlength="2" placeholder="Ex.: SP">
                    <?php if (isset($errors['estado_se'])): ?>
                        <div class="invalid-feedback"><?= esc($errors['estado_se']) ?></div>
                    <?php endif ?>
                    <div class="form-text">Sigla da Superintendência Regional (UF).</div>
                </div>

                <!-- Lotação -->
                <div class="mb-4">
                    <label for="lotacao" class="form-label fw-semibold">Lotação</label>
                    <input type="text" id="lotacao" name="lotacao"
                           class="form-control <?= isset($errors['lotacao']) ? 'is-invalid' : '' ?>"
                           value="<?= $val('lotacao') ?>"
                           maxlength="100">
                    <?php if (isset($errors['lotacao'])): ?>
                        <div class="invalid-feedback"><?= esc($errors['lotacao']) ?></div>
                    <?php endif ?>
                    <div class="form-text">Dado cadastral — não influencia a distribuição no MVP.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Salvar
                    </button>
                    <a href="/admin/vendors" class="btn btn-outline-secondary">Cancelar</a>
                </div>

            </form>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
