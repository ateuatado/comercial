<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: ProspectingFlagModel
 * Suspeitas de prospecção antifraude baseadas em CPF de sócios.
 */
class ProspectingFlagModel extends Model
{
    protected $table            = 'prospecting_flags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'cnpj', 'cpf_socio', 'cnpj_relacionado', 'motivo',
        'analisado_por', 'analisado_em', 'status', 'complemento',
    ];

    protected $validationRules = [
        'cnpj'      => 'required|max_length[14]',
        'cpf_socio' => 'required|max_length[11]',
        'motivo'    => 'required',
    ];

    /** Retorna todas as suspeitas pendentes de revisão. */
    public function getPending(): array
    {
        return $this->where('status', 'pendente')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Lista todas as suspeitas com razão social via join na receita.
     * Usa cnpj_basico (8 dígitos) pois receita.empresas não armazena o CNPJ completo.
     */
    public function getAllWithEmpresa(?string $se = null): array
    {
        $seCondition = $se ? "WHERE EXISTS (
            SELECT 1 FROM client_wallets cw
            JOIN vendors v ON v.id = cw.vendor_id
            WHERE cw.cnpj = pf.cnpj AND v.estado_se = " . $this->db->escape($se) . "
        )" : "";

        return $this->db->query("
            SELECT
                pf.id,
                pf.cnpj,
                pf.cpf_socio,
                pf.cnpj_relacionado,
                pf.motivo,
                pf.status,
                pf.analisado_em,
                pf.created_at,
                e.razao_social
            FROM prospecting_flags pf
            LEFT JOIN receita.empresas e
                   ON e.cnpj_basico = SUBSTRING(pf.cnpj, 1, 8)
            {$seCondition}
            ORDER BY pf.created_at DESC
        ")->getResultArray();
    }

    /**
     * Retorna uma suspeita com seu histórico completo de revisões.
     * Retorna null quando não encontrada.
     *
     * @return array{flag: array<string,mixed>, reviews: list<array<string,mixed>>}|null
     */
    public function getByIdWithReviews(int $id): ?array
    {
        $flag = $this->db->query("
            SELECT
                pf.*,
                e.razao_social
            FROM prospecting_flags pf
            LEFT JOIN receita.empresas e
                   ON e.cnpj_basico = SUBSTRING(pf.cnpj, 1, 8)
            WHERE pf.id = ?
        ", [$id])->getRowArray();

        if (! $flag) {
            return null;
        }

        $reviews = $this->db->query("
            SELECT
                pr.id,
                pr.decisao,
                pr.justificativa,
                pr.created_at,
                ui.username AS revisado_por_nome
            FROM prospecting_reviews pr
            LEFT JOIN users ui ON ui.id = pr.revisado_por
            WHERE pr.flag_id = ?
            ORDER BY pr.created_at ASC
        ", [$id])->getResultArray();

        return ['flag' => $flag, 'reviews' => $reviews];
    }
}
