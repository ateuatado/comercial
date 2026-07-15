<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClientWalletModel;
use App\Models\VendorModel;
use App\Models\WalletMovementModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller: Admin\DistributionController
 * Gerencia a distribuição automática e reatribuição manual da carteira.
 *
 * Regra de distribuição (spec 001 §3):
 *   - capital_social alto → carteiras menores (Gerente de Conta / ACOM I)
 *   - capital_social baixo → carteiras maiores (ACOM II / ACOM III)
 *
 * Pesos de capacidade por tipo:
 *   - Gerente de Conta (tipo_acom NULL): peso 1
 *   - ACOM I:   peso 2
 *   - ACOM II:  peso 3
 *   - ACOM III: peso 5
 */
class DistributionController extends BaseController
{
    /** GET /admin/distribuicao */
    public function index(): string
    {
        $walletModel = new ClientWalletModel();
        $vendorModel = new VendorModel();

        $se = $this->getAdminSE();

        if ($se) {
            $walletModel->where("vendor_id IN (SELECT id FROM vendors WHERE estado_se = '{$se}')", null, false);
            $vendorModel->where('estado_se', $se);
            $totalClients = $walletModel->countAllResults(false);
            $unassigned   = 0; // Admins regionais não visualizam clientes sem atribuição (visão nacional)
        } else {
            $totalClients = $walletModel->countAll();
            $unassigned   = $walletModel->where('vendor_id IS NULL', null, false)->countAllResults();
        }

        $totalVendors   = $vendorModel->where('ativo', true)->countAllResults(false);
        // Reseta as instâncias para as próximas queries customizadas
        $walletModel = new ClientWalletModel();
        $vendorModel = new VendorModel();

        if ($se) {
            $byVendor = $walletModel->where("vendor_id IN (SELECT id FROM vendors WHERE estado_se = '{$se}')", null, false)->countByVendor();
            $activeVendors = $vendorModel->where('estado_se', $se)->getActive();
        } else {
            $byVendor = $walletModel->countByVendor();
            $activeVendors = $vendorModel->getActive();
        }

        return view('admin/distribution/index', [
            'page_title'    => 'Distribuição de Carteira',
            'total_clients' => $totalClients,
            'unassigned'    => $unassigned,
            'total_vendors' => $totalVendors,
            'by_vendor'     => $byVendor,
            'active_vendors' => $activeVendors,
        ]);
    }

    /** POST /admin/distribuicao/executar — executa distribuição automática. */
    public function distribute(): RedirectResponse
    {
        $userId = (int) auth()->id();
        $result = $this->runDistribution($userId);

        if (isset($result['error'])) {
            return redirect()->to('/admin/distribuicao')->with('error', $result['error']);
        }

        if (isset($result['info'])) {
            return redirect()->to('/admin/distribuicao')->with('info', $result['info']);
        }

        return redirect()->to('/admin/distribuicao')
            ->with('success', "Distribuição concluída: {$result['assigned']} cliente(s) atribuído(s).");
    }

    /** POST /admin/distribuicao/reatribuir — reatribuição manual com auditoria. */
    public function reassign(): RedirectResponse
    {
        $cnpj     = $this->request->getPost('cnpj');
        $vendorId = $this->request->getPost('vendor_id');
        $motivo   = $this->request->getPost('motivo');
        $userId   = (int) auth()->id();

        if (empty($cnpj)) {
            return redirect()->to('/admin/distribuicao')->with('error', 'CNPJ é obrigatório.');
        }

        $walletModel   = new ClientWalletModel();
        $movementModel = new WalletMovementModel();

        $wallet = $walletModel->where('cnpj', $cnpj)->first();
        if (! $wallet) {
            return redirect()->to('/admin/distribuicao')
                ->with('error', 'CNPJ não encontrado na carteira.');
        }

        $novoVendorId = $vendorId !== '' ? (int) $vendorId : null;
        $db           = db_connect();

        $db->transStart();

        $db->table('client_wallets')
            ->where('cnpj', $cnpj)
            ->update([
                'vendor_id'         => $novoVendorId,
                'origem_atribuicao' => 'manual',
                'atribuido_em'      => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

        $movementModel->recordMovement(
            $cnpj,
            $wallet['vendor_id'],
            $novoVendorId,
            'manual',
            $userId,
            $motivo ?: null
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/admin/distribuicao')
                ->with('error', 'Erro ao reatribuir cliente. Nenhuma alteração foi salva.');
        }

        return redirect()->to('/admin/distribuicao')
            ->with('success', "Cliente {$cnpj} reatribuído com sucesso.");
    }

    /**
     * Executa a distribuição automática de clientes sem responsável.
     *
     * Ordena clientes por capital_social DESC (maior capital → menor carteira).
     * Divide proporcionalmente entre os grupos de vendedores por peso de capacidade.
     *
     * @return array{assigned: int}|array{error: string}|array{info: string}
     */
    private function runDistribution(int $userId): array
    {
        $vendorModel   = new VendorModel();
        $movementModel = new WalletMovementModel();
        $db            = db_connect();

        $se = $this->getAdminSE();

        if ($se) {
            $vendors = $vendorModel->where('estado_se', $se)->getActive();
        } else {
            $vendors = $vendorModel->getActive();
        }

        if (empty($vendors)) {
            return ['error' => 'Nenhum vendedor ativo encontrado' . ($se ? " na SE {$se}." : '.')];
        }

        if ($se) {
            return ['error' => 'Distribuição automática de clientes sem atribuição está restrita ao Super Admin (Nacional).'];
        }

        // CNPJs sem responsável, ordenados por capital_social DESC.
        // capital_social em receita.empresas é VARCHAR — convertido com CAST seguro.
        // client_potentiality.capital_social tem precedência quando disponível.
        $unassigned = $db->query("
            SELECT
                cw.id,
                cw.cnpj,
                COALESCE(
                    cp.capital_social,
                    CAST(NULLIF(REPLACE(TRIM(e.capital_social), ',', '.'), '') AS NUMERIC)
                ) AS capital_social
            FROM client_wallets cw
            LEFT JOIN receita.empresas e
                   ON e.cnpj_basico = cw.cnpj_basico
            LEFT JOIN client_potentiality cp
                   ON cp.cnpj = cw.cnpj
            WHERE cw.vendor_id IS NULL
            ORDER BY capital_social DESC NULLS LAST
        ")->getResultArray();

        if (empty($unassigned)) {
            return ['info' => 'Não há clientes sem atribuição para distribuir.'];
        }

        // Pesos: tipo_acom NULL (Gerente de Conta) = 1; I = 2; II = 3; III = 5
        $weightMap   = ['' => 1, 'I' => 2, 'II' => 3, 'III' => 5];
        $orderedKeys = ['', 'I', 'II', 'III']; // '' representa Gerente de Conta

        // Agrupa vendedores por tipo_acom
        $groups = [];
        foreach ($vendors as $v) {
            $key           = $v['tipo_acom'] ?? '';
            $groups[$key][] = $v;
        }

        // Calcula capacidade total ponderada
        $totalCapacity = 0;
        foreach ($orderedKeys as $key) {
            if (isset($groups[$key])) {
                $totalCapacity += $weightMap[$key] * count($groups[$key]);
            }
        }

        if ($totalCapacity === 0) {
            return ['error' => 'Capacidade total de vendedores é zero.'];
        }

        $n           = count($unassigned);
        $assignments = []; // [cnpj => vendor_id]
        $offset      = 0;

        // Distribui proporcionalmente: grupos com menor peso recebem clientes
        // de maior capital (pois aparecem primeiro na iteração orderedKeys).
        foreach ($orderedKeys as $key) {
            if (! isset($groups[$key]) || $offset >= $n) {
                continue;
            }

            $groupVendors  = $groups[$key];
            $groupCapacity = $weightMap[$key] * count($groupVendors);
            $groupShare    = (int) round(($groupCapacity / $totalCapacity) * $n);
            $vendorCount   = count($groupVendors);

            for ($i = 0; $i < $groupShare && $offset < $n; $i++, $offset++) {
                $vendor                                   = $groupVendors[$i % $vendorCount];
                $assignments[$unassigned[$offset]['cnpj']] = $vendor['id'];
            }
        }

        // Remanescentes por arredondamento → último grupo disponível
        if ($offset < $n) {
            $fallbackVendor = null;
            foreach (array_reverse($orderedKeys) as $key) {
                if (isset($groups[$key])) {
                    $fallbackVendor = $groups[$key][0];
                    break;
                }
            }

            while ($offset < $n && $fallbackVendor !== null) {
                $assignments[$unassigned[$offset]['cnpj']] = $fallbackVendor['id'];
                $offset++;
            }
        }

        // Persiste em transação
        $now      = date('Y-m-d H:i:s');
        $assigned = 0;

        $db->transStart();

        foreach ($assignments as $cnpj => $vendorId) {
            $db->table('client_wallets')
                ->where('cnpj', $cnpj)
                ->update([
                    'vendor_id'         => $vendorId,
                    'origem_atribuicao' => 'automatica',
                    'atribuido_em'      => $now,
                    'updated_at'        => $now,
                ]);

            $db->table('wallet_movements')->insert([
                'cnpj'               => $cnpj,
                'vendor_id_anterior' => null,
                'vendor_id_novo'     => $vendorId,
                'tipo_movimento'     => 'automatico',
                'realizado_por'      => $userId,
                'motivo'             => 'Distribuição automática por capital social',
                'created_at'         => $now,
            ]);

            $assigned++;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['error' => 'Erro durante a distribuição. Nenhuma alteração foi salva.'];
        }

        return ['assigned' => $assigned];
    }
}
