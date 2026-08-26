<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Área administrativa — somente admin
$routes->group('admin', ['filter' => 'session'], static function ($routes): void {
    // Dashboard
    $routes->get('dashboard', '\App\Controllers\AdminController::dashboard');
    $routes->get('historico', '\App\Controllers\AdminController::historicalMovements', ['as' => 'admin_historical']);

    // Vendedores (CRUD)
    $routes->get('vendors', '\App\Controllers\Admin\VendorsController::index');
    $routes->get('vendors/novo', '\App\Controllers\Admin\VendorsController::create');
    $routes->post('vendors/novo', '\App\Controllers\Admin\VendorsController::store');
    $routes->get('vendors/(:num)/editar', '\App\Controllers\Admin\VendorsController::edit/$1');
    $routes->post('vendors/(:num)/editar', '\App\Controllers\Admin\VendorsController::update/$1');
    $routes->post('vendors/(:num)/desativar', '\App\Controllers\Admin\VendorsController::deactivate/$1');

    // Distribuição de carteira
    $routes->get('distribuicao', '\App\Controllers\Admin\DistributionController::index');
    $routes->post('distribuicao/executar', '\App\Controllers\Admin\DistributionController::distribute');
    $routes->post('distribuicao/reatribuir', '\App\Controllers\Admin\DistributionController::reassign');

    // Prospecção antifraude
    $routes->get('prospecting',                  '\App\Controllers\Admin\ProspectingController::index',  ['as' => 'admin_prospecting']);
    $routes->get('prospecting/nova',             '\App\Controllers\Admin\ProspectingController::create', ['as' => 'admin_prospecting_create']);
    $routes->post('prospecting/nova',            '\App\Controllers\Admin\ProspectingController::store',  ['as' => 'admin_prospecting_store']);
    $routes->get('prospecting/(:num)',            '\App\Controllers\Admin\ProspectingController::show/$1',   ['as' => 'admin_prospecting_show']);
    $routes->get('prospecting/(:num)/revisar',   '\App\Controllers\Admin\ProspectingController::review/$1', ['as' => 'admin_prospecting_review']);
    $routes->post('prospecting/(:num)/revisar',  '\App\Controllers\Admin\ProspectingController::decide/$1', ['as' => 'admin_prospecting_decide']);

    // LGPD - ROPA
    $routes->get('ropa',        '\App\Controllers\Admin\RopaController::index',  ['as' => 'admin_ropa']);
    $routes->get('ropa/export', '\App\Controllers\Admin\RopaController::export', ['as' => 'admin_ropa_export']);

    // Consulta RFB (Receita Federal)
    $routes->get('busca',                  '\App\Controllers\Admin\SearchController::index',  ['as' => 'admin_search']);
    $routes->get('busca/empresa/(:segment)', '\App\Controllers\Admin\SearchController::show/$1', ['as' => 'admin_search_show']);

    // Mensagens do sistema (Fase 2.10)
    $routes->get('mensagens',              '\App\Controllers\Admin\SystemMessagesController::index',  ['as' => 'admin_messages']);
    $routes->get('mensagens/(:segment)',   '\App\Controllers\Admin\SystemMessagesController::edit/$1', ['as' => 'admin_messages_edit']);
    $routes->post('mensagens/(:segment)',  '\App\Controllers\Admin\SystemMessagesController::update/$1', ['as' => 'admin_messages_update']);

    // Importação de carteiras (CSV upload)
    $routes->get('importar',              '\App\Controllers\Admin\ImportController::index',   ['as' => 'admin_import']);
    $routes->post('importar/upload',      '\App\Controllers\Admin\ImportController::upload',  ['as' => 'admin_import_upload']);
    $routes->post('importar/confirmar',   '\App\Controllers\Admin\ImportController::confirm', ['as' => 'admin_import_confirm']);

    // Gestão Manual de Localizações (Fase 2.6b)
    $routes->get('localizacao',           '\App\Controllers\AdminController::localizacaoManual');
    $routes->post('localizacao',          '\App\Controllers\AdminController::localizacaoManualSalvar');

    // Scoring Preditivo (Fase 3)
    $routes->get('scoring',                   '\App\Controllers\AdminController::scoringConfig');
    $routes->post('scoring/salvar',            '\App\Controllers\AdminController::scoringSalvar');
    $routes->post('scoring/recalcular',        '\App\Controllers\AdminController::scoringRecalcular');
    $routes->get('scoring/progresso',          '\App\Controllers\AdminController::scoringProgresso');
    $routes->post('scoring/cnae/adicionar',    '\App\Controllers\AdminController::cnaeAdicionar');
    $routes->post('scoring/cnae/remover',      '\App\Controllers\AdminController::cnaeRemover');

    // CNAE Postal Score (Ranking de Prospects)
    $routes->get('cnae-postal',                '\App\Controllers\AdminController::cnaePostalIndex');
    $routes->post('cnae-postal/salvar',        '\App\Controllers\AdminController::cnaePostalSalvar');
    $routes->post('cnae-postal/reclassificar', '\App\Controllers\AdminController::cnaePostalReclassificar');

    // Scanner Reclame Aqui (Fase 3.5)
    $routes->get('reclame-aqui',               '\App\Controllers\AdminController::reclameAqui');
    $routes->post('reclame-aqui/scan',         '\App\Controllers\AdminController::reclameAquiScan');

    // Pedidos de Captação (PR-CAP — Fase 3.5)
    $routes->get('captacoes',              '\App\Controllers\AdminController::captacoesIndex');
    $routes->get('captacoes/(:num)',       '\App\Controllers\AdminController::captacaoDetalhe/$1');
    $routes->post('captacoes/decisao',     '\App\Controllers\AdminController::captacaoDecisao');
    $routes->get('captacoes/anexo/(:num)', '\App\Controllers\AdminController::captacaoAnexo/$1');

    // Gestão de Eventos / Feiras
    $routes->get('eventos',                '\App\Controllers\Admin\EventosController::index');
    $routes->get('eventos/(:num)',         '\App\Controllers\Admin\EventosController::show/$1');
    $routes->post('eventos/novo',          '\App\Controllers\Admin\EventosController::store');
    $routes->post('eventos/(:num)/editar', '\App\Controllers\Admin\EventosController::update/$1');
    $routes->post('eventos/(:num)/toggle', '\App\Controllers\Admin\EventosController::toggle/$1');

    // Fundação do Vendedor Eventual — autorização administrativa explícita.
    $routes->group('vendedor-eventual', ['filter' => 'permission:campaign.manage'], static function ($routes): void {
        $routes->get('/', '\App\Controllers\Admin\VendedorEventualController::index');
        $routes->post('campanhas', '\App\Controllers\Admin\VendedorEventualController::createCampaign', ['filter' => 'csrf']);
        $routes->post('campanhas/(:num)/estado', '\App\Controllers\Admin\VendedorEventualController::changeCampaignStatus/$1', ['filter' => 'csrf']);
        $routes->post('aplicacoes/(:num)/estado', '\App\Controllers\Admin\VendedorEventualController::toggleApplication/$1', ['filter' => 'csrf']);
        $routes->post('capacitacoes', '\App\Controllers\Admin\VendedorEventualController::createLearningVersion', ['filter' => 'csrf']);
        $routes->post('capacitacoes/(:num)/publicar', '\App\Controllers\Admin\VendedorEventualController::publishLearningVersion/$1', ['filter' => 'csrf']);
        $routes->post('catalogo/produtos', '\App\Controllers\Admin\VendedorEventualController::createProductVersion', ['filter' => 'csrf']);
        $routes->post('catalogo/questionarios', '\App\Controllers\Admin\VendedorEventualController::createQuestionnaireVersion', ['filter' => 'csrf']);
        $routes->post('catalogo/(:segment)/(:num)/publicar', '\App\Controllers\Admin\VendedorEventualController::publishCatalogVersion/$1/$2', ['filter' => 'csrf']);
    });
    $routes->group('vendedor-eventual', ['filter' => 'permission:entitlements.manage'], static function ($routes): void {
        $routes->post('concessoes', '\App\Controllers\Admin\VendedorEventualController::grant', ['filter' => 'csrf']);
        $routes->post('concessoes/(:num)/revogar', '\App\Controllers\Admin\VendedorEventualController::revoke/$1', ['filter' => 'csrf']);
        $routes->post('adesoes/(:num)/suspender', '\App\Controllers\Admin\VendedorEventualController::suspendEnrollment/$1', ['filter' => 'csrf']);
    });
});

// Portal operacional — acom e gerente_conta (legado Fase 1)
$routes->get('carteira', 'CarteiraController::index', ['filter' => 'session']);
$routes->post('carteira/status', 'CarteiraController::updateStatus', ['filter' => 'session', 'as' => 'carteira_update_status']);

// Vendedor — interface mobile-first (Fase 2)
$routes->group('vendedor', ['filter' => 'session'], static function ($routes): void {
    $routes->get('/', 'VendedorController::index');
    $routes->get('clientes', 'VendedorController::clientesView');
    $routes->get('clientes/api', 'VendedorController::clientesApi');
    $routes->get('clientes/mapa', 'VendedorController::clientesMapaApi');   // API: meus clientes com coords
    $routes->get('clientes/ver-mapa', 'VendedorController::clientesMapaView'); // View do mapa
    $routes->get('livres/mapa', 'VendedorController::livresMapaApi');          // API: livres com coords
    $routes->get('cliente/(:segment)', 'VendedorController::clienteDetalhe/$1');
    $routes->get('cliente/(:segment)/nota', 'VendedorController::notaForm/$1');
    $routes->post('nota', 'VendedorController::notaSalvar');
    $routes->post('nota/(:num)/visibilidade', 'VendedorController::notaTogglePublica/$1');
    $routes->get('servicos/(:segment)', 'VendedorController::servicosSegmento/$1');
    $routes->post('estrategia', 'VendedorController::estrategiaSalvar');
    
    // Geolocalização e Prospecção (Fase 2.6b)
    $routes->get('prospectar', 'VendedorController::prospectarView');
    $routes->get('prospectar/api', 'VendedorController::prospectarApi');
    $routes->get('prospectar/pesquisa', 'VendedorController::prospeccaoPesquisaView');
    $routes->get('prospectar/pesquisa/buscar', 'VendedorController::prospeccaoBuscarApi');
    $routes->get('prospectar/pesquisa/ranking', 'VendedorController::rankingApi');
    $routes->post('pre-visita', 'VendedorController::preVisitaSalvar');
    $routes->get('maps-contract', 'VendedorController::mockGoogleMaps');
    $routes->get('cnpj/verificar/(:segment)', 'VendedorController::verificarCnpj/$1');
    $routes->post('cnpj/geolocalizar/(:segment)', 'VendedorController::geolocalizarCnpj/$1');
    
    // Redes Sociais e OSINT
    $routes->get('cnpj/redes-sociais/buscar/(:segment)', 'VendedorController::buscarRedesSociais/$1');
    $routes->post('cnpj/redes-sociais/validar/(:num)', 'VendedorController::validarRedeSocial/$1');
    $routes->post('cnpj/redes-sociais/rejeitar/(:num)', 'VendedorController::rejeitarRedeSocial/$1');
    $routes->post('cliente/(:segment)/reclame-aqui', 'VendedorController::reclameAquiScan/$1');
    $routes->post('serper-key', 'VendedorController::serperKeySalvar');   // Chave Serper pessoal (AJAX)

    // Captação de Clientes (PR-CAP — Fase 3.5)
    $routes->get('captacao/solicitar/(:segment)', 'VendedorController::captacaoSolicitar/$1');
    $routes->post('captacao/salvar', 'VendedorController::captacaoSalvar');
    $routes->get('captacao/anexo/(:num)', 'VendedorController::captacaoAnexo/$1');
    $routes->get('minhas-captacoes', 'VendedorController::minhasCaptacoes');
    $routes->get('minhas-notas', 'VendedorController::minhasNotas');

    // Eventos / Feiras
    $routes->get('eventos',                      'VendedorController::eventosView');
    $routes->get('eventos/(:num)/busca',         'VendedorController::eventoBuscaView/$1');
    $routes->post('eventos/registrar',           'VendedorController::eventoRegistrarContato');
    $routes->get('eventos/(:num)/meus-contatos', 'VendedorController::eventoContatosApi/$1');
    $routes->get('eventos/(:num)/exportar-csv',   'VendedorController::eventoExportarCsv/$1');
});

// Coordenador — visão do time (Fase 2.9) + gestão avançada (Fase 3)
$routes->group('coordenador', ['filter' => 'session'], static function ($routes): void {
    // Fase 2.9 — Visão do time
    $routes->get('/', 'CoordenadorController::index');
    $routes->get('vendedor/(:segment)', 'CoordenadorController::vendedorDetalhe/$1');
    $routes->get('vendedor/(:segment)/clientes', 'CoordenadorController::vendedorClientes/$1');

    // Fase 3.1 — CRUD de vendedores pelo coordenador
    $routes->get('vendedores/novo', 'CoordenadorController::novoVendedor');
    $routes->post('vendedores/salvar', 'CoordenadorController::salvarVendedor');
    $routes->get('vendedor/(:segment)/editar', 'CoordenadorController::editarVendedor/$1');
    $routes->post('vendedor/(:segment)/atualizar', 'CoordenadorController::atualizarVendedor/$1');
    $routes->post('vendedor/(:segment)/desativar', 'CoordenadorController::desativarVendedor/$1');

    // Fase 3.2 — Clientes livres e atribuição
    $routes->get('clientes-livres', 'CoordenadorController::clientesLivres');
    $routes->post('clientes-livres/atribuir', 'CoordenadorController::atribuirClientes');

    // Fase 3.3 — Transferência de clientes entre vendedores
    $routes->post('vendedor/(:segment)/transferir-clientes', 'CoordenadorController::processarTransferenciaClientes/$1');

    // Fase 3.4 — Transferência de vendedor entre coordenadores
    $routes->get('vendedor/(:segment)/transferir', 'CoordenadorController::formTransferirVendedor/$1');
    $routes->post('vendedor/(:segment)/transferir', 'CoordenadorController::processarTransferenciaVendedor/$1');

    // Coordenador também pode decidir sobre PR-CAPs
    $routes->get('captacoes', 'AdminController::captacoesIndex');
    $routes->get('captacoes/(:num)', 'AdminController::captacaoDetalhe/$1');
    $routes->post('captacoes/decisao', 'AdminController::captacaoDecisao');
});

// Sem carteira — tela informativa
$routes->get('sem-carteira', 'SemCarteiraController::index', ['filter' => 'session']);

// Hub de aplicações e fundação do Vendedor Eventual.
$routes->get('aplicacoes', 'ApplicationsController::index', ['filter' => 'session']);
$routes->group('vendedor-eventual', ['filter' => 'applicationAccess:vendedor_eventual,access'], static function ($routes): void {
    $routes->get('/', '\App\Controllers\VendedorEventual\HomeController::index');
    $routes->post('campanhas/(:num)/aderir', '\App\Controllers\VendedorEventual\HomeController::startEnrollment/$1', ['filter' => 'csrf']);
    $routes->get('campanhas/(:num)/capacitacao', '\App\Controllers\VendedorEventual\HomeController::training/$1');
    $routes->post('campanhas/(:num)/capacitacao', '\App\Controllers\VendedorEventual\HomeController::completeTraining/$1', ['filter' => 'csrf']);
    $routes->post('campanhas/(:num)/participacao/(:segment)', '\App\Controllers\VendedorEventual\HomeController::changeEnrollmentStatus/$1/$2', ['filter' => 'csrf']);
    $routes->get('campanhas/(:num)/catalogo', '\App\Controllers\VendedorEventual\HomeController::catalog/$1');
});

// Override das rotas de login — registrado ANTES do Shield (CI4 usa first-match).
// Suporta switch LDAP_ENABLED via .env sem alterar código.
$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');
$routes->get('logout', '\App\Controllers\Auth\LoginController::logoutAction');

// Demais rotas do Shield (register desabilitado em Auth.php)
service('auth')->routes($routes);
