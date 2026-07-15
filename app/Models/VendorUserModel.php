<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorUserModel extends Model
{
    protected $table         = 'vendor_users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'matricula',
        'nome',
        'email',
        'perfil_vendedor',
        'se',
        'gerencia',
        'mtr_coordenador',
        'nome_coordenador',
        'gerencia_vendas',
        'shield_user_id',
        'ativo',
    ];

    protected $validationRules = [
        'matricula' => 'required|max_length[20]',
        'nome'      => 'required|max_length[200]',
    ];

    /**
     * Busca vendedor por matrícula.
     */
    public function findByMatricula(string $matricula): ?array
    {
        return $this->where('matricula', $matricula)->first();
    }

    /**
     * Busca vendedor vinculado a um Shield user.
     */
    public function findByShieldUserId(int $userId): ?array
    {
        return $this->where('shield_user_id', $userId)->first();
    }

    /**
     * Vincula um Shield user_id a um vendor_user.
     */
    public function linkShieldUser(int $vendorUserId, int $shieldUserId): bool
    {
        return $this->update($vendorUserId, ['shield_user_id' => $shieldUserId]);
    }

    /**
     * Retorna vendedores ativos sob um coordenador.
     */
    public function getByCoordinator(string $mtrCoordenador): array
    {
        return $this->where('mtr_coordenador', $mtrCoordenador)
                    ->where('ativo', true)
                    ->orderBy('nome', 'ASC')
                    ->findAll();
    }

    /**
     * Verifica se uma matrícula é coordenador de alguém.
     */
    public function isCoordinator(string $matricula): bool
    {
        return $this->where('mtr_coordenador', $matricula)
                    ->where('ativo', true)
                    ->countAllResults() > 0;
    }

    /**
     * Retorna vendedores ativos, opcionalmente filtrados por SE.
     */
    public function getActive(?string $se = null): array
    {
        $builder = $this->where('ativo', true)->orderBy('nome', 'ASC');

        if ($se !== null) {
            $builder->where('se', $se);
        }

        return $builder->findAll();
    }
}
