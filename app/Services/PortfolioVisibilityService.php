<?php

declare(strict_types=1);

namespace App\Services;

class PortfolioVisibilityService
{
    public function statusForCnpj(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        $db = db_connect();
        if (! $db->tableExists('client_wallets') || ! $db->tableExists('vendors')) {
            return ['state' => 'unavailable', 'label' => 'Consulta de carteira indisponível', 'responsible_name' => null, 'responsible_unit' => null, 'operational_status' => null];
        }
        $row = $db->table('client_wallets wallet')
            ->select('wallet.id, wallet.vendor_id, wallet.status_operacional, wallet.origem_atribuicao, vendor.nome AS responsible_name, vendor.lotacao AS responsible_unit')
            ->join('vendors vendor', 'vendor.id = wallet.vendor_id', 'left')
            ->where('wallet.cnpj', $cnpj)->get()->getRowArray();

        if ($row === null) {
            return ['state' => 'available', 'label' => 'Sem carteira identificada', 'responsible_name' => null, 'responsible_unit' => null, 'operational_status' => null];
        }
        if ($row['vendor_id'] === null) {
            return ['state' => 'unassigned', 'label' => 'Cliente cadastrado, sem responsável', 'responsible_name' => null, 'responsible_unit' => null, 'operational_status' => $row['status_operacional']];
        }
        return ['state' => 'assigned', 'label' => 'Cliente já possui responsável', 'responsible_name' => $row['responsible_name'], 'responsible_unit' => $row['responsible_unit'], 'operational_status' => $row['status_operacional']];
    }
}
