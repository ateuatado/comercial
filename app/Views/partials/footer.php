<footer class="spiv-footer">
    <div class="container">
        <div class="row g-4">

            <!-- Coluna Marca -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <img src="<?= base_url('assets/images/logo.png') ?>" alt="SPIV">
                    <p>SPIV — Sistema de gestão de carteira de prospecção dos Correios.<br>Uso interno. Rede segura.</p>
                </div>
            </div>

            <!-- Coluna Módulos -->
            <div class="col-lg-4 col-md-6">
                <h5>Módulos</h5>
                <ul>
                    <?php if (auth()->loggedIn() && auth()->user()->inGroup('admin')): ?>
                        <li><a href="<?= base_url('admin/dashboard') ?>"><i class="bi bi-chevron-right me-1"></i>Dashboard</a></li>
                        <li><a href="<?= base_url('admin/vendors') ?>"><i class="bi bi-chevron-right me-1"></i>Vendedores</a></li>
                        <li><a href="<?= base_url('admin/distribuicao') ?>"><i class="bi bi-chevron-right me-1"></i>Distribuição</a></li>
                        <li><a href="<?= base_url('admin/prospecting') ?>"><i class="bi bi-chevron-right me-1"></i>Suspeitas</a></li>
                        <li><a href="<?= base_url('admin/historico') ?>"><i class="bi bi-chevron-right me-1"></i>Histórico</a></li>
                        <li><a href="<?= base_url('admin/busca') ?>"><i class="bi bi-chevron-right me-1 text-primary"></i>Consulta RFB</a></li>
                        <li><a href="<?= base_url('admin/ropa') ?>"><i class="bi bi-chevron-right me-1 text-success"></i>LGPD (ROPA)</a></li>
                    <?php elseif (auth()->loggedIn()): ?>
                        <li><a href="<?= base_url('carteira') ?>"><i class="bi bi-chevron-right me-1"></i>Minha Carteira</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Coluna Contato -->
            <div class="col-lg-4 col-md-6">
                <h5>Suporte</h5>
                <ul>
                    <li>
                        <a href="#"><i class="bi bi-envelope-fill me-2"></i>suporte@correios.com.br</a>
                    </li>
                    <li>
                        <a href="#"><i class="bi bi-clock me-2"></i>Seg–Sex, 08h às 18h</a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Rodapé inferior -->
        <div class="footer-divider">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> SPIV — Empresa Brasileira de Correios e Telégrafos.</span>
                <div class="footer-bottom-links">
                    <a href="#">Privacidade</a>
                    <a href="#">Suporte</a>
                </div>
            </div>
        </div>
    </div>
</footer>
