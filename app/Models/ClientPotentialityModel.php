<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: ClientPotentialityModel
 * Armazena dados de potencialidade por CNPJ.
 *
 * capital_social: lido de receita.empresas como fallback operacional.
 * potencialidade_extra: JSONB reservado para enriquecimentos futuros.
 */
class ClientPotentialityModel extends Model
{
    protected $table            = 'client_potentiality';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'cnpj', 'capital_social', 'potencialidade_extra', 'updated_at',
    ];

    /**
     * Insere ou atualiza o capital_social para um CNPJ.
     * Usado durante a distribuição para registrar a referência usada.
     */
    public function upsertCapitalSocial(string $cnpj, ?float $capitalSocial): bool
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->where('cnpj', $cnpj)->first();

        if ($existing) {
            return (bool) $this->update($existing['id'], [
                'capital_social' => $capitalSocial,
                'updated_at'     => $now,
            ]);
        }

        return (bool) $this->insert([
            'cnpj'           => $cnpj,
            'capital_social' => $capitalSocial,
            'updated_at'     => $now,
        ]);
    }
}
