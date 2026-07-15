<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientStrategyModel extends Model
{
    protected $table         = 'client_strategies';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    protected $allowedFields = [
        'matricula_vendedor',
        'cnpj',
        'service_id',
        'observacao',
        'created_at',
    ];

    /**
     * Retorna estratégias de um cliente com dados do serviço.
     */
    public function getByClient(string $cnpj, string $matricula): array
    {
        return $this->select('client_strategies.*, segment_services.servico_nome, segment_services.icone, segment_services.cor')
                    ->join('segment_services', 'segment_services.id = client_strategies.service_id')
                    ->where('client_strategies.cnpj', $cnpj)
                    ->where('client_strategies.matricula_vendedor', $matricula)
                    ->orderBy('client_strategies.created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Remove todas as estratégias de um cliente para um vendedor (para rebuild).
     */
    public function clearForClient(string $cnpj, string $matricula): bool
    {
        return $this->where('cnpj', $cnpj)
                    ->where('matricula_vendedor', $matricula)
                    ->delete();
    }
}
