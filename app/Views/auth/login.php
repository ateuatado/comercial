<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
    <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">

            <div class="text-center mb-4">
                <h4 class="fw-bold mb-0">SPIV</h4>
                <p class="text-muted small">Sistema de Gestão de Vendas</p>
            </div>

            <?php if (session('error')): ?>
                <div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('errors')): ?>
                <div class="alert alert-danger py-2 small">
                    <?php foreach ((array) session('errors') as $e): ?>
                        <?= esc($e) ?><br>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <?php if (session('message')): ?>
                <div class="alert alert-success py-2 small"><?= esc(session('message')) ?></div>
            <?php endif ?>

            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Matrícula</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           value="<?= old('username') ?>"
                           autocomplete="username"
                           autofocus
                           required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Senha</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           autocomplete="current-password"
                           required>
                </div>

                <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" id="remember" name="remember"
                               class="form-check-input"
                               <?= old('remember') ? 'checked' : '' ?>>
                        <label for="remember" class="form-check-label small">
                            Manter conectado
                        </label>
                    </div>
                <?php endif ?>

                <button type="submit" class="btn btn-primary w-100">
                    Entrar
                </button>

            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
