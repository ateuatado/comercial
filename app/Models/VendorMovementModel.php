<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: VendorMovementModel
 * Registra transferências de vendedores entre coordenadores (auditoria).
 */
class VendorMovementModel extends Model
{
    protected $table      = 'vendor_movements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'matricula',
        'coord_origem',
        'nome_coord_origem',
        'coord_destino',
        'nome_coord_destino',
        'gerencia',
        'se',
        'motivo',
        'feito_por',
        'feito_por_perfil',
        'created_at',
    ];

    protected $useTimestamps  = false; // gerenciamos created_at manualmente
    protected $dateFormat     = 'datetime';

    /**
     * Registra uma transferência de vendedor.
     */
    public function registrar(
        string $matricula,
        ?string $coordOrigem,
        ?string $nomeCoordOrigem,
        string  $coordDestino,
        string  $nomeCoordDestino,
        string  $gerencia,
        ?string $se,
        string  $motivo,
        string  $feitoPor,
        string  $feitoPorPerfil = 'coordenador'
    ): int|string
    {
        return $this->insert([
            'matricula'          => $matricula,
            'coord_origem'       => $coordOrigem,
            'nome_coord_origem'  => $nomeCoordOrigem,
            'coord_destino'      => $coordDestino,
            'nome_coord_destino' => $nomeCoordDestino,
            'gerencia'           => $gerencia,
            'se'                 => $se,
            'motivo'             => $motivo,
            'feito_por'          => $feitoPor,
            'feito_por_perfil'   => $feitoPorPerfil,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Histórico de movimentações de um vendedor.
     */
    public function getByVendedor(string $matricula): array
    {
        return $this->where('matricula', $matricula)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Histórico de movimentações dentro de uma gerência.
     */
    public function getByGerencia(string $gerencia, int $limit = 50): array
    {
        return $this->where('gerencia', $gerencia)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }
}
