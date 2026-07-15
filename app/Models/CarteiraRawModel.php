<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: CarteiraRawModel
 *
 * Acesso à tabela carteira_raw que contém todas as 25 colunas
 * do relatório geral de carteiras dos Correios.
 *
 * Esta tabela é descritiva — dados de painéis e relatórios.
 * A tabela operacional continua sendo client_wallets.
 */
class CarteiraRawModel extends Model
{
    protected $table            = 'carteira_raw';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'se', 'id_grupo', 'grupo_cliente', 'categoria',
        'cnpj', 'razao_social',
        'segmento_cliente', 'segmento_mercado', 'canais_vendas', 'canais_vendas_obs',
        'prospeccao',
        'forca_vendas_nome', 'matricula_mcmcu',
        'conta_numero', 'conta_nome',
        'ciclo_de_vida',
        'cnae', 'cnae_desc', 'seg_merc_cnae',
        'nat_juridica',
        'gerencia', 'mtr_cood', 'nome_cood', 'gerencia_vendas', 'forca_vendas_email',
        'created_at',
    ];

    /**
     * Dados completos de um CNPJ.
     * Pode retornar múltiplos registros (mesmo CNPJ, contas/grupos diferentes).
     */
    public function getByCnpj(string $cnpj): array
    {
        return $this->where('cnpj', $cnpj)->findAll();
    }

    /**
     * Todos os clientes de uma carteira (por matrícula/MCMCU).
     */
    public function getByMatricula(string $matricula): array
    {
        return $this->where('matricula_mcmcu', $matricula)->findAll();
    }

    /**
     * Lista de carteiras únicas (forca_vendas_nome + matricula_mcmcu).
     * Retorna com contagem de clientes por carteira.
     */
    public function getDistinctCarteiras(): array
    {
        return $this->db
            ->table('carteira_raw')
            ->select('matricula_mcmcu, forca_vendas_nome, COUNT(*) AS total_clientes')
            ->groupBy('matricula_mcmcu, forca_vendas_nome')
            ->orderBy('total_clientes', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Estatísticas por Superintendência (SE).
     */
    public function countBySe(): array
    {
        return $this->db
            ->table('carteira_raw')
            ->select('se, COUNT(*) AS total')
            ->groupBy('se')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Estatísticas por categoria institucional.
     */
    public function countByCategoria(): array
    {
        return $this->db
            ->table('carteira_raw')
            ->select('categoria, COUNT(*) AS total')
            ->groupBy('categoria')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Estatísticas por ciclo de vida.
     */
    public function countByCiclo(): array
    {
        return $this->db
            ->table('carteira_raw')
            ->select('ciclo_de_vida, COUNT(*) AS total')
            ->groupBy('ciclo_de_vida')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Estatísticas por segmento de cliente.
     */
    public function countBySegmento(): array
    {
        return $this->db
            ->table('carteira_raw')
            ->select('segmento_cliente, COUNT(*) AS total')
            ->groupBy('segmento_cliente')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();
    }
}
