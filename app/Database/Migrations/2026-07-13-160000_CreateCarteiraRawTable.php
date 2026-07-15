<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateCarteiraRawTable
 *
 * Tabela com todas as 25 colunas do relatório geral de carteiras dos Correios.
 * Serve como fonte descritiva completa para painéis e relatórios.
 *
 * JOIN com client_wallets via CNPJ.
 * JOIN com vendors via matricula_mcmcu.
 *
 * CNPJ NÃO é UNIQUE aqui — o mesmo CNPJ pode aparecer em múltiplas linhas
 * do CSV (diferentes contas/grupos). A unicidade está na client_wallets.
 */
class CreateCarteiraRawTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // --- Localização organizacional ---
            'se' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            // --- Grupo do cliente ---
            'id_grupo' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'grupo_cliente' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            // --- Identificação do cliente ---
            'cnpj' => [
                'type'       => 'CHAR',
                'constraint' => 14,
                'null'       => false,
            ],
            'razao_social' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            // --- Segmentação ---
            'segmento_cliente' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'segmento_mercado' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'canais_vendas' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'canais_vendas_obs' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // --- Prospecção ---
            'prospeccao' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            // --- Carteira / Vendedor ---
            'forca_vendas_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'matricula_mcmcu' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            // --- Conta comercial ---
            'conta_numero' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'conta_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            // --- Ciclo de vida ---
            'ciclo_de_vida' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            // --- CNAE ---
            'cnae' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
            ],
            'cnae_desc' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'seg_merc_cnae' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            // --- Natureza jurídica ---
            'nat_juridica' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            // --- Hierarquia de gestão ---
            'gerencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'mtr_cood' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nome_cood' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'gerencia_vendas' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'forca_vendas_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            // --- Timestamp ---
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('carteira_raw');

        // Índices para JOINs e filtros nos painéis
        $this->db->query('CREATE INDEX idx_carteira_raw_cnpj ON carteira_raw (cnpj)');
        $this->db->query('CREATE INDEX idx_carteira_raw_matricula ON carteira_raw (matricula_mcmcu)');
        $this->db->query('CREATE INDEX idx_carteira_raw_se ON carteira_raw (se)');
        $this->db->query('CREATE INDEX idx_carteira_raw_categoria ON carteira_raw (categoria)');
        $this->db->query('CREATE INDEX idx_carteira_raw_ciclo ON carteira_raw (ciclo_de_vida)');
        $this->db->query('CREATE INDEX idx_carteira_raw_segmento ON carteira_raw (segmento_cliente)');
    }

    public function down(): void
    {
        $this->forge->dropTable('carteira_raw', true);
    }
}
