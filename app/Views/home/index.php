<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- ── Hero Banner ─────────────────────────────────────────── -->
<section class="spiv-hero" id="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p class="text-uppercase mb-2" style="color:rgba(255,204,0,0.8);font-size:0.8rem;font-weight:700;letter-spacing:2px;">
                    <i class="bi bi-briefcase-fill me-1"></i> Gestão de Carteira de Prospecção
                </p>
                <h1 class="animate-in">
                    Prospecte com <span>inteligência</span><br>
                    e organize sua equipe de vendas
                </h1>
                <p class="lead my-3 animate-in animate-in-delay-1">
                    Gerencie leads vindos da Receita Federal (CNPJ), distribua carteiras
                    para os <strong style="color:#FFCC00;">56 vendedores</strong> e colete feedbacks
                    de visitas para enriquecer continuamente o potencial de prospecção.
                </p>
                <div class="d-flex flex-wrap gap-3 animate-in animate-in-delay-2">
                    <a href="<?= base_url('vendedor/login') ?>" class="btn-hero-primary" id="cta-login-vendedor">
                        <i class="bi bi-person-badge-fill"></i> Área do Vendedor
                    </a>
                    <a href="<?= base_url('adm') ?>" class="btn-hero-secondary" id="cta-admin">
                        <i class="bi bi-shield-lock-fill"></i> Painel Admin
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div style="position:relative;">
                    <!-- Ícone decorativo -->
                    <div style="background:rgba(255,204,0,0.12);border-radius:50%;width:260px;height:260px;
                                display:flex;align-items:center;justify-content:center;
                                border:2px dashed rgba(255,204,0,0.25);">
                        <i class="bi bi-people-fill" style="font-size:6rem;color:rgba(255,204,0,0.6);"></i>
                    </div>
                    <!-- Badges flutuantes -->
                    <div style="position:absolute;top:10px;right:-20px;
                                background:#FFCC00;color:#003087;border-radius:10px;
                                padding:8px 14px;font-size:0.8rem;font-weight:700;
                                box-shadow:0 4px 12px rgba(255,204,0,0.4);">
                        <i class="bi bi-person-check me-1"></i>56 Vendedores
                    </div>
                    <div style="position:absolute;bottom:20px;left:-25px;
                                background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);
                                border:1px solid rgba(255,255,255,0.2);border-radius:10px;
                                padding:8px 14px;font-size:0.8rem;color:#fff;
                                box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                        <i class="bi bi-building me-1" style="color:#FFCC00;"></i>Dados da Receita Federal
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Barra de Estatísticas ───────────────────────────────── -->
<div class="spiv-stats-bar">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-target="56">56</div>
                <div class="stat-label">Vendedores Ativos</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-target="0">0</div>
                <div class="stat-label">Leads na Carteira</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-target="0">0</div>
                <div class="stat-label">Visitas Registradas</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-number" data-target="0">0</div>
                <div class="stat-label">CNPJs Processados</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Conteúdo Principal — 2 Colunas ────────────────────── -->
<main class="spiv-main" id="conteudo-principal">
    <div class="container">
        <div class="row g-4">

            <!-- ═══ COLUNA 1 — Módulos do Sistema ════════════════ -->
            <div class="col-lg-8">
                <h2 class="spiv-section-title animate-in">
                    <i class="bi bi-grid-3x3-gap-fill me-2"></i>Módulos do Sistema
                </h2>

                <div class="row g-3">

                    <!-- Card: Ingestão de CNPJs -->
                    <div class="col-md-6 animate-in animate-in-delay-1">
                        <a href="<?= base_url('ingestao') ?>" class="spiv-card" id="card-ingestao-cnpj">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-cloud-upload-fill"></i></div>
                                <h3 class="card-title-top">Ingestão de CNPJs</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Importe arquivos com CNPJs fornecidos pela Receita Federal e processe automaticamente os dados cadastrais.</p>
                                <span class="card-link">Importar dados <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                    <!-- Card: Validação de Base -->
                    <div class="col-md-6 animate-in animate-in-delay-2">
                        <a href="<?= base_url('validacao') ?>" class="spiv-card" id="card-validacao">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-patch-check-fill"></i></div>
                                <h3 class="card-title-top">Verificação de Clientes</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Cruze os CNPJs importados com a base existente e identifique automaticamente quem já é cliente da empresa.</p>
                                <span class="card-link">Verificar base <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                    <!-- Card: Distribuição de Carteiras -->
                    <div class="col-md-6 animate-in animate-in-delay-1">
                        <a href="<?= base_url('distribuicao') ?>" class="spiv-card" id="card-distribuicao">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-diagram-3-fill"></i></div>
                                <h3 class="card-title-top">Distribuição de Carteiras</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Divida e atribua os leads prospectáveis entre os 56 vendedores conforme as regras de negócio definidas.</p>
                                <span class="card-link">Distribuir leads <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                    <!-- Card: Minha Carteira (Vendedor) -->
                    <div class="col-md-6 animate-in animate-in-delay-2">
                        <a href="<?= base_url('vendedor/carteira') ?>" class="spiv-card" id="card-carteira-vendedor">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-person-lines-fill"></i></div>
                                <h3 class="card-title-top">Minha Carteira</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Acesse os leads atribuídos ao seu perfil, consulte dados do CNPJ e planeje suas visitas de prospecção.</p>
                                <span class="card-link">Ver minha carteira <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                    <!-- Card: Registro de Visitas -->
                    <div class="col-md-6 animate-in animate-in-delay-1">
                        <a href="<?= base_url('visitas') ?>" class="spiv-card" id="card-visitas">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-geo-alt-fill"></i></div>
                                <h3 class="card-title-top">Registro de Visitas</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Registre o resultado de cada visita realizada e forneça feedback para enriquecer o perfil do prospect.</p>
                                <span class="card-link">Registrar visita <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                    <!-- Card: Relatórios Gerenciais -->
                    <div class="col-md-6 animate-in animate-in-delay-2">
                        <a href="<?= base_url('relatorios') ?>" class="spiv-card" id="card-relatorios">
                            <div class="card-icon-bar">
                                <div class="icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                                <h3 class="card-title-top">Relatórios Gerenciais</h3>
                            </div>
                            <div class="card-body-content">
                                <p>Acompanhe o desempenho da equipe, taxa de conversão por vendedor e evolução dos dados de prospecção.</p>
                                <span class="card-link">Ver relatórios <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>

                </div><!-- /row cards -->
            </div><!-- /col-lg-8 -->

            <!-- ═══ COLUNA 2 — Acesso Rápido + Fluxo ════════════ -->
            <div class="col-lg-4">

                <!-- Acesso Rápido -->
                <h2 class="spiv-section-title animate-in">
                    <i class="bi bi-lightning-charge-fill me-2"></i>Acesso Rápido
                </h2>

                <div class="spiv-access-panel animate-in animate-in-delay-1 mb-4" id="painel-acesso-rapido">
                    <div class="panel-header">
                        <h3><i class="bi bi-bookmark-star-fill me-2"></i>Entrar no Sistema</h3>
                    </div>
                    <a href="<?= base_url('vendedor/login') ?>" class="access-btn" id="btn-login-vendedor">
                        <div class="btn-icon"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Sou Vendedor</div>
                            <div style="font-size:0.75rem;color:#888;">Acesse com matrícula e senha</div>
                        </div>
                    </a>
                    <a href="<?= base_url('adm/login') ?>" class="access-btn" id="btn-login-admin">
                        <div class="btn-icon"><i class="bi bi-shield-lock-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Sou Administrador</div>
                            <div style="font-size:0.75rem;color:#888;">Gestão e configurações</div>
                        </div>
                    </a>
                    <a href="<?= base_url('ingestao') ?>" class="access-btn" id="btn-importar">
                        <div class="btn-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Importar CNPJs</div>
                            <div style="font-size:0.75rem;color:#888;">Upload de arquivo da Receita</div>
                        </div>
                    </a>
                    <a href="<?= base_url('distribuicao') ?>" class="access-btn" id="btn-distribuir">
                        <div class="btn-icon"><i class="bi bi-diagram-3-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Distribuir Carteiras</div>
                            <div style="font-size:0.75rem;color:#888;">Atribuir leads aos vendedores</div>
                        </div>
                    </a>
                    <a href="<?= base_url('visitas') ?>" class="access-btn" id="btn-visitas">
                        <div class="btn-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Registrar Visita</div>
                            <div style="font-size:0.75rem;color:#888;">Feedback da prospecção</div>
                        </div>
                    </a>
                    <a href="<?= base_url('relatorios') ?>" class="access-btn" id="btn-relatorios">
                        <div class="btn-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                        <div>
                            <div style="font-weight:600;">Relatórios</div>
                            <div style="font-size:0.75rem;color:#888;">Desempenho e conversão</div>
                        </div>
                    </a>
                </div>

                <!-- Fluxo do Processo -->
                <h2 class="spiv-section-title animate-in">
                    <i class="bi bi-diagram-2-fill me-2"></i>Como Funciona
                </h2>

                <div class="spiv-news-panel animate-in animate-in-delay-2" id="painel-fluxo">
                    <div class="panel-header" style="background:var(--spiv-azul);">
                        <h3 style="color:var(--spiv-amarelo);">
                            <i class="bi bi-arrow-right-circle-fill me-2"></i>Fluxo de Prospecção
                        </h3>
                    </div>

                    <div class="news-item">
                        <span class="news-badge badge-info">1</span>
                        <p class="news-title">
                            <i class="bi bi-building me-1 text-spiv-azul"></i>
                            Receita Federal envia arquivo de CNPJs
                        </p>
                        <span class="news-date">Ingestão de dados brutos</span>
                    </div>

                    <div class="news-item">
                        <span class="news-badge badge-info">2</span>
                        <p class="news-title">
                            <i class="bi bi-patch-check me-1 text-spiv-azul"></i>
                            Sistema verifica quem já é cliente
                        </p>
                        <span class="news-date">Deduplicação automática</span>
                    </div>

                    <div class="news-item">
                        <span class="news-badge badge-info">3</span>
                        <p class="news-title">
                            <i class="bi bi-diagram-3 me-1 text-spiv-azul"></i>
                            Leads distribuídos aos 56 vendedores
                        </p>
                        <span class="news-date">Divisão por regras de negócio</span>
                    </div>

                    <div class="news-item">
                        <span class="news-badge badge-info">4</span>
                        <p class="news-title">
                            <i class="bi bi-person-check me-1 text-spiv-azul"></i>
                            Vendedor acessa sua carteira e visita
                        </p>
                        <span class="news-date">Login com matrícula e senha de rede</span>
                    </div>

                    <div class="news-item">
                        <span class="news-badge badge-novo">5</span>
                        <p class="news-title">
                            <i class="bi bi-star-fill me-1" style="color:#FFCC00;"></i>
                            Feedback enriquece o perfil do prospect
                        </p>
                        <span class="news-date">Dados para maior potencial de conversão</span>
                    </div>

                </div>

            </div><!-- /col-lg-4 -->

        </div><!-- /row principal -->
    </div><!-- /container -->
</main>

<?= $this->endSection() ?>
