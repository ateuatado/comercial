<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: ClientWalletModel
 * Vínculo atual entre CNPJ (cliente) e vendedor responsável.
 */
class ClientWalletModel extends Model
{
    protected $table            = 'client_wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'cnpj', 'vendor_id', 'status_operacional',
        'origem_atribuicao', 'atribuido_em',
    ];

    /** Carteira completa de um vendedor. */
    public function getByVendor(int $vendorId): array
    {
        return $this->where('vendor_id', $vendorId)->findAll();
    }

    /** Clientes sem responsável atribuído. */
    public function getUnassigned(): array
    {
        return $this->where('vendor_id IS NULL', null, false)->findAll();
    }

    /**
     * Contagem de clientes agrupada por vendedor.
     * Retorna somente vendedores com ao menos um cliente atribuído.
     */
    public function countByVendor(): array
    {
        return $this->db
            ->table('client_wallets cw')
            ->select('v.id, v.nome, v.matricula, v.tipo_acom, COUNT(cw.id) AS total')
            ->join('vendors v', 'v.id = cw.vendor_id')
            ->groupBy('v.id, v.nome, v.matricula, v.tipo_acom')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }
}
