<?php

namespace App\Controllers;

use App\Models\VendorUserModel;

/**
 * Controller: Coordenador
 * Visão do time — hierarquia coordenador → vendedores.
 */
class CoordenadorController extends BaseController
{
    protected VendorUserModel $vendorModel;

    public function __construct()
    {
        $this->vendorModel = new VendorUserModel();
    }

    /**
     * Retorna o vendor_user logado, ou null.
     */
    private function getLoggedVendor(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;
        return $this->vendorModel->findByShieldUserId((int) $user->id);
    }

    /**
     * Dashboard do coordenador — lista de vendedores do time.
     */
    public function index()
    {
        $vendorUser = $this->getLoggedVendor();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $matricula = $vendorUser['matricula'];

        // Verifica se realmente é coordenador
        if (!$this->vendorModel->isCoordinator($matricula)) {
            return redirect()->to('/vendedor')->with('error', 'Você não é coordenador.');
        }

        // Vendedores do time
        $vendedores = $this->vendorModel->getByCoordinator($matricula);

        // KPIs por vendedor
        $db = db_connect();
        $kpis = [];
        foreach ($vendedores as &$v) {
            $total = $db->query("SELECT COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ?", [$v['matricula']])->getRow()->total;
            $v['total_clientes'] = $total;

            $cats = $db->query("SELECT categoria, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY categoria ORDER BY total DESC", [$v['matricula']])->getResultArray();
            $v['categorias'] = $cats;
        }

        // KPIs do time
        $totalVendedores = count($vendedores);
        $totalClientesTime = array_sum(array_column($vendedores, 'total_clientes'));

        return view('coordenador/index', [
            'vendorUser'        => $vendorUser,
            'vendedores'        => $vendedores,
            'totalVendedores'   => $totalVendedores,
            'totalClientesTime' => $totalClientesTime,
        ]);
    }

    /**
     * Detalhe de um vendedor do time (read-only).
     */
    public function vendedorDetalhe(string $matricula)
    {
        $vendorUser = $this->getLoggedVendor();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        if (!$this->vendorModel->isCoordinator($vendorUser['matricula'])) {
            return redirect()->to('/vendedor');
        }

        // Verifica se o vendedor pertence ao time
        $vendedor = $this->vendorModel->findByMatricula($matricula);
        if (!$vendedor || ($vendedor['mtr_coordenador'] ?? '') !== $vendorUser['matricula']) {
            return redirect()->to('/coordenador')->with('error', 'Vendedor não pertence ao seu time.');
        }

        $db = db_connect();
        $totalClientes = $db->query("SELECT COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ?", [$matricula])->getRow()->total;
        $categorias = $db->query("SELECT categoria, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY categoria ORDER BY total DESC", [$matricula])->getResultArray();
        $ciclos = $db->query("SELECT ciclo_de_vida, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY ciclo_de_vida ORDER BY total DESC", [$matricula])->getResultArray();

        return view('coordenador/vendedor_detalhe', [
            'vendorUser'     => $vendorUser,
            'vendedor'       => $vendedor,
            'totalClientes'  => $totalClientes,
            'categorias'     => $categorias,
            'ciclos'         => $ciclos,
        ]);
    }

    /**
     * Lista de clientes de um vendedor (read-only).
     */
    public function vendedorClientes(string $matricula)
    {
        $vendorUser = $this->getLoggedVendor();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        if (!$this->vendorModel->isCoordinator($vendorUser['matricula'])) {
            return redirect()->to('/vendedor');
        }

        $vendedor = $this->vendorModel->findByMatricula($matricula);
        if (!$vendedor || ($vendedor['mtr_coordenador'] ?? '') !== $vendorUser['matricula']) {
            return redirect()->to('/coordenador')->with('error', 'Vendedor não pertence ao seu time.');
        }

        $db = db_connect();
        $clientes = $db->query(
            "SELECT cnpj, razao_social, categoria, segmento_mercado, ciclo_de_vida, cnae FROM carteira_raw WHERE matricula_mcmcu = ? ORDER BY razao_social",
            [$matricula]
        )->getResultArray();

        return view('coordenador/vendedor_clientes', [
            'vendorUser' => $vendorUser,
            'vendedor'   => $vendedor,
            'clientes'   => $clientes,
        ]);
    }
}
