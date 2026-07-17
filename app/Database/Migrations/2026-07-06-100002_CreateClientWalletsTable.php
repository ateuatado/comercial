<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateClientWalletsTable
 *
 * Vínculo atual entre cliente (CNPJ) e vendedor responsável.
 * Um CNPJ tem exatamente um registro aqui (UNIQUE em cnpj).
 * O campo vendor_id pode ser NULL quando o cliente ainda não foi atribuído.
 *
 * Dependência: vendors (migration 100001) deve existir.
 */
class CreateClientWalletsTable extends Migration
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
            // Responsável atual — NULL = cliente sem atribuição
            'vendor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // Valores: ativo, inativo, bloqueado, suspeito
            'status_operacional' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'ativo',
            ],
            // Como foi feita a última atribuição: automatica ou manual
            'origem_atribuicao' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'atribuido_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('cnpj');
        $this->forge->addKey('vendor_id');
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('client_wallets');

        // Check constraints - skip on SQLite3
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query("ALTER TABLE client_wallets ADD CONSTRAINT chk_wallets_status CHECK (status_operacional IN ('ativo','inativo','bloqueado','suspeito'))");
            $this->db->query("ALTER TABLE client_wallets ADD CONSTRAINT chk_wallets_origem CHECK (origem_atribuicao IN ('automatica','manual') OR origem_atribuicao IS NULL)");
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('client_wallets', true);
    }
}
