<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: VendorModel
 * Acesso à tabela vendors — cadastro de ACOMs e Gerentes de Conta.
 */
class VendorModel extends Model
{
    protected $table            = 'vendors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'user_id', 'matricula', 'nome', 'lotacao',
        'tipo_acom', 'estado_se', 'ativo',
    ];

    protected $validationRules = [
        'matricula' => 'required|max_length[20]',
        'nome'      => 'required|max_length[200]',
        'tipo_acom' => 'permit_empty|in_list[I,II,III]',
        'estado_se' => 'permit_empty|max_length[2]',
        'lotacao'   => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [
        'matricula' => [
            'required'   => 'A matrícula é obrigatória.',
            'max_length' => 'Matrícula deve ter no máximo 20 caracteres.',
        ],
        'nome' => [
            'required'   => 'O nome é obrigatório.',
            'max_length' => 'Nome deve ter no máximo 200 caracteres.',
        ],
        'tipo_acom' => [
            'in_list' => 'Tipo de ACOM deve ser I, II ou III.',
        ],
    ];

    /** Retorna todos os vendedores ativos, ordenados por nome. */
    public function getActive(): array
    {
        return $this->where('ativo', true)->orderBy('nome', 'ASC')->findAll();
    }

    /** Verifica se uma matrícula já está em uso (opcionalmente excluindo um ID). */
    public function isMatriculaTaken(string $matricula, ?int $excludeId = null): bool
    {
        $builder = $this->where('matricula', $matricula);
        if ($excludeId !== null) {
            $builder = $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Soft-deactivate: preserva histórico. */
    public function deactivate(int $id): bool
    {
        return (bool) $this->update($id, ['ativo' => false]);
    }
}
