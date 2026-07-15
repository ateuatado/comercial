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

});

// Portal operacional — acom e gerente_conta (legado Fase 1)
$routes->get('carteira', 'CarteiraController::index', ['filter' => 'session']);
$routes->post('carteira/status', 'CarteiraController::updateStatus', ['filter' => 'session', 'as' => 'carteira_update_status']);

// Vendedor — interface mobile-first (Fase 2)
$routes->group('vendedor', ['filter' => 'session'], static function ($routes): void {
    $routes->get('/', 'VendedorController::index');
    $routes->get('clientes', 'VendedorController::clientesView');
    $routes->get('clientes/api', 'VendedorController::clientesApi');
    $routes->get('cliente/(:segment)', 'VendedorController::clienteDetalhe/$1');
    $routes->get('cliente/(:segment)/nota', 'VendedorController::notaForm/$1');
    $routes->post('nota', 'VendedorController::notaSalvar');
    $routes->get('servicos/(:segment)', 'VendedorController::servicosSegmento/$1');
    $routes->post('estrategia', 'VendedorController::estrategiaSalvar');
});

// Coordenador — visão do time (Fase 2.9)
$routes->group('coordenador', ['filter' => 'session'], static function ($routes): void {
    $routes->get('/', 'CoordenadorController::index');
    $routes->get('vendedor/(:segment)', 'CoordenadorController::vendedorDetalhe/$1');
    $routes->get('vendedor/(:segment)/clientes', 'CoordenadorController::vendedorClientes/$1');
});

// Sem carteira — tela informativa
$routes->get('sem-carteira', 'SemCarteiraController::index', ['filter' => 'session']);

// Override das rotas de login — registrado ANTES do Shield (CI4 usa first-match).
// Suporta switch LDAP_ENABLED via .env sem alterar código.
$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');
$routes->get('logout', '\App\Controllers\Auth\LoginController::logoutAction');

// Demais rotas do Shield (register desabilitado em Auth.php)
service('auth')->routes($routes);
