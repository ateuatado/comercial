<nav class="spiv-navbar navbar navbar-expand-lg" id="spiv-main-nav">
    <div class="container">

        <!-- Logo + Nome da Marca -->
        <a class="navbar-brand" href="<?= base_url('/') ?>">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="SPIV Logo">
            <div>
                <span class="brand-text">SPIV</span>
                <span class="brand-tagline">Carteira de Prospecção</span>
            </div>
        </a>

        <!-- Botão mobile -->
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSPIV"
                aria-controls="navbarSPIV"
                aria-expanded="false"
                aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links de navegação condicionais por perfil -->
        <div class="collapse navbar-collapse" id="navbarSPIV">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= (uri_string() === '' || uri_string() === '/') ? 'active' : '' ?>" href="<?= base_url('/') ?>">
                        <i class="bi bi-house-door-fill me-1"></i>Início
                    </a>
                </li>

                <?php if (auth()->loggedIn()): ?>

                    <?php if (auth()->user()->inGroup('admin')): ?>
                        <!-- Área administrativa -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/dashboard') ?>">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/importar') ?>">
                                <i class="bi bi-cloud-upload me-1"></i>Importar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/vendors') ?>">
                                <i class="bi bi-people-fill me-1"></i>Vendedores
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/mensagens') ?>">
                                <i class="bi bi-chat-square-text me-1"></i>Mensagens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/cnae-postal') ?>">
                                <i class="bi bi-tags me-1"></i>CNAE Postal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('admin/busca') ?>">
                                <i class="bi bi-search me-1"></i>Consulta RFB
                            </a>
                        </li>

                    <?php elseif (auth()->user()->inGroup('acom', 'gerente_conta')): ?>
                        <!-- Portal operacional -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('carteira') ?>">
                                <i class="bi bi-briefcase-fill me-1"></i>Minha Carteira
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('vendedor/prospectar/pesquisa') ?>">
                                <i class="bi bi-search me-1"></i>Prospecção
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>

            </ul>
        </div>

    </div>
</nav>
