<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: ClientStatusHistoryModel
 * Histórico imutável de transições de status operacional de clientes.
 */
class ClientStatusHistoryModel extends Model
{
    protected $table            = 'client_status_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = false;

    protected $allowedFields = [
        'cnpj', 'vendor_id', 'status_anterior', 'status_novo', 'alterado_por',
    ];

    /** Registra uma transição de status. */
    public function recordTransition(
        string $cnpj,
        ?int $vendorId,
        ?string $statusAnterior,
        string $statusNovo,
        ?int $alteradoPor
    ): bool {
        return (bool) $this->insert([
            'cnpj'            => $cnpj,
            'vendor_id'       => $vendorId,
            'status_anterior' => $statusAnterior,
            'status_novo'     => $statusNovo,
            'alterado_por'    => $alteradoPor,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
