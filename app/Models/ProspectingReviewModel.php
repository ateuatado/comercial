<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: ProspectingReviewModel
 * Decisões de aprovação ou rejeição de suspeitas de prospecção.
 * Registros imutáveis — sem updatedField.
 */
class ProspectingReviewModel extends Model
{
    protected $table            = 'prospecting_reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = false;

    protected $allowedFields = [
        'flag_id', 'revisado_por', 'decisao', 'justificativa',
    ];

    protected $validationRules = [
        'flag_id'       => 'required|integer',
        'revisado_por'  => 'required|integer',
        'decisao'       => 'required|in_list[liberado,rejeitado]',
        'justificativa' => 'required',
    ];

    /**
     * Persiste a decisão de revisão e atualiza o status da flag em transação.
     * Regra de negócio: uma flag só avança de 'pendente' para 'liberado' ou 'rejeitado'.
     *
     * @throws \RuntimeException quando a transação falhar
     */
    public function decide(int $flagId, int $revisadoPor, string $decisao, string $justificativa): bool
    {
        $db = $this->db;
        $db->transStart();

        $this->insert([
            'flag_id'       => $flagId,
            'revisado_por'  => $revisadoPor,
            'decisao'       => $decisao,
            'justificativa' => $justificativa,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $db->table('prospecting_flags')
           ->where('id', $flagId)
           ->update([
               'status'      => $decisao,
               'analisado_por' => $revisadoPor,
               'analisado_em'  => date('Y-m-d H:i:s'),
               'updated_at'    => date('Y-m-d H:i:s'),
           ]);

        $db->transComplete();

        return $db->transStatus();
    }
}

