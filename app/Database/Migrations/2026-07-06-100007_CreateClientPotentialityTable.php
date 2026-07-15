<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateClientPotentialityTable
 *
 * Armazena dados de potencialidade por CNPJ.
 *
 * No MVP, o capital_social vem de receita.empresas como fallback.
 * A coluna potencialidade_extra (JSONB) reserva espaço para enriquecimentos
 * futuros: tipo de negócio, valuation real, redes sociais, inputs dos vendedores e IA.
 *
 * Quando potencialidade_extra estiver preenchida, ela prevalece sobre capital_social
 * como critério de distribuição conforme spec 001 §3.2.
 */
class CreateClientPotentialityTable extends Migration
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
            // CNPJ da Receita Federal (14 dígitos sem formatação)
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
                'null'       => false,
            ],
            // Capital social da tabela receita.empresas; atualizado na distribuição.
            'capital_social' => [
                'type'       => 'NUMERIC',
                'constraint' => '20,2',
                'null'       => true,
            ],
            // Placeholder para enriquecimento futuro (JSONB nativo do PostgreSQL)
            'potencialidade_extra' => [
                'type' => 'JSONB',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('cnpj');
        $this->forge->createTable('client_potentiality');
    }

    public function down(): void
    {
        $this->forge->dropTable('client_potentiality', true);
    }
}
