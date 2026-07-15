<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\LGPD\RopaRegistry;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller: Admin\RopaController
 * 
 * Expõe o Registro de Operações de Tratamento de Dados Pessoais (ROPA)
 * do SPIV para os administradores, conforme Art. 37 da LGPD.
 */
class RopaController extends BaseController
{
    /**
     * Exibe a tela com o inventário ROPA mapeado no sistema.
     */
    public function index(): string
    {
        $ropas = RopaRegistry::getAll();

        return view('admin/ropa/index', [
            'page_title' => 'Inventário LGPD (ROPA)',
            'ropas'      => $ropas,
        ]);
    }

    /**
     * Exporta os registros ROPA em formato JSON estrito.
     * Útil para integrações de governança corporativa e envio à DPO.
     */
    public function export(): ResponseInterface
    {
        $ropas = RopaRegistry::getAll();

        // O response->setJSON já serializa automaticamente os objetos
        // que implementam JsonSerializable.
        return $this->response->setJSON([
            'gerado_em' => date('Y-m-d\TH:i:sP'),
            'sistema'   => 'SPIV - Sistema de Prospecção',
            'registros' => $ropas
        ]);
    }
}
