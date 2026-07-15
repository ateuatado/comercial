<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: WalletMovementModel
 * Trilha de auditoria imutável de movimentações da carteira.
 * Sem updatedField — registros não são modificados após criação.
 */
class WalletMovementModel extends Model
{
    protected $table            = 'wallet_movements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = false;

    protected $allowedFields = [
        'cnpj', 'vendor_id_anterior', 'vendor_id_novo',
        'tipo_movimento', 'realizado_por', 'motivo',
    ];

    /** Registra uma movimentação de atribuição/reatribuição. */
    public function recordMovement(
        string $cnpj,
        ?int $vendorAnterior,
        ?int $vendorNovo,
        string $tipo,
        ?int $realizadoPor,
        ?string $motivo = null
    ): bool {
        return (bool) $this->insert([
            'cnpj'               => $cnpj,
            'vendor_id_anterior' => $vendorAnterior,
            'vendor_id_novo'     => $vendorNovo,
            'tipo_movimento'     => $tipo,
            'realizado_por'      => $realizadoPor,
            'motivo'             => $motivo,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
