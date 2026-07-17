<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateWalletMovementsTable
 *
 * Trilha de auditoria de movimentações da carteira.
 * Registra cada atribuição/reatribuição de CNPJ entre vendedores.
 *
 * Sem FK em vendor_id_anterior/novo e realizado_por para preservar
 * o histórico mesmo após exclusão de vendedores ou usuários.
 */
class CreateWalletMovementsTable extends Migration
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
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
                'null'       => false,
            ],
            // NULL = cliente não tinha responsável antes
            'vendor_id_anterior' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // NULL = cliente ficou sem responsável
            'vendor_id_novo' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // automatico = distribuição; manual = ação do admin
            'tipo_movimento' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
            // user_id do admin que executou (NULL em distribuições automáticas)
            'realizado_por' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'motivo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('cnpj');
        $this->forge->addKey('vendor_id_anterior');
        $this->forge->addKey('vendor_id_novo');
        $this->forge->createTable('wallet_movements');

        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query("ALTER TABLE wallet_movements ADD CONSTRAINT chk_movements_tipo CHECK (tipo_movimento IN ('automatico','manual'))");
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('wallet_movements', true);
    }
}
