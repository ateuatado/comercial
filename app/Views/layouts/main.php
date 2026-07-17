<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= esc($meta_description ?? 'SPIV — Sistema de Gestão de Vendas') ?>">
    <title><?= esc($page_title ?? 'SPIV') ?> | Sistema de Vendas</title>

    <!-- Bootstrap 5.3.3 -->
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>" rel="stylesheet">

    <!-- Google Fonts: Inter (Local) -->
    <link href="<?= base_url('assets/css/inter.css') ?>" rel="stylesheet">

    <!-- SPIV CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo.png') ?>">
</head>
<body>

    <!-- Barra de topo utilitária -->
    <div class="spiv-topbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-telephone-fill me-1"></i> (11) 9999-0000
                    <span class="mx-2">|</span>
                    <i class="bi bi-envelope-fill me-1"></i> contato@spiv.com.br
                </div>
                <div>
                    <?php if (auth()->loggedIn()): ?>
                        <a href="#" class="me-2"><i class="bi bi-person-circle me-1"></i><?= esc(auth()->user()->username) ?></a>
                        <a href="<?= url_to('logout') ?>"><i class="bi bi-box-arrow-right me-1"></i>Sair</a>
                    <?php else: ?>
                        <a href="#" class="me-2"><i class="bi bi-person-circle me-1"></i>Minha Conta</a>
                        <a href="<?= url_to('login') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar</a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navbar Principal -->
    <?= $this->include('partials/navbar') ?>

    <!-- Conteúdo da Página -->
    <?= $this->renderSection('content') ?>

    <!-- Footer -->
    <?= $this->include('partials/footer') ?>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>

    <!-- SPIV JS -->
    <script src="<?= base_url('assets/js/main.js') ?>"></script>

    <!-- Scripts de módulo injetados pela view via section('scripts') -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
