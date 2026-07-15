<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateClientStatusHistoryTable
 *
 * Histórico de todas as transições de status operacional de clientes.
 * Sem FK em vendor_id e alterado_por para preservar o histórico
 * mesmo após exclusão de vendedores ou usuários.
 */
class CreateClientStatusHistoryTable extends Migration
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
            // Vendedor responsável no momento da transição
            'vendor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // NULL quando o cliente não tinha status anterior (primeira atribuição)
            'status_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'status_novo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
            // user_id de quem executou a transição
            'alterado_por' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('cnpj');
        $this->forge->createTable('client_status_history');
    }

    public function down(): void
    {
        $this->forge->dropTable('client_status_history', true);
    }
}
