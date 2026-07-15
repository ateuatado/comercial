<?php

namespace App\Models;

use CodeIgniter\Model;

class SegmentServiceModel extends Model
{
    protected $table         = 'segment_services';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'segmento_mercado',
        'servico_nome',
        'servico_descricao',
        'icone',
        'cor',
        'ordem',
        'ativo',
    ];

    /**
     * Retorna serviços ativos para um segmento de mercado.
     */
    public function getBySegment(string $segmento): array
    {
        return $this->where('segmento_mercado', $segmento)
                    ->where('ativo', true)
                    ->orderBy('ordem', 'ASC')
                    ->findAll();
    }

    /**
     * Retorna todos os segmentos distintos com serviços.
     */
    public function getDistinctSegments(): array
    {
        return $this->select('segmento_mercado, COUNT(*) as total_servicos')
                    ->where('ativo', true)
                    ->groupBy('segmento_mercado')
                    ->orderBy('segmento_mercado', 'ASC')
                    ->findAll();
    }
}
