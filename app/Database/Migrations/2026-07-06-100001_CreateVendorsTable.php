<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateVendorsTable
 *
 * Cadastro de vendedores: ACOMs e Gerentes de Conta.
 * Campos mínimos conforme spec 001 — matrícula, nome, lotação, tipo_acom e estado_se.
 * Soft-delete via campo `ativo` — histórico nunca é perdido.
 */
class CreateVendorsTable extends Migration
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
            // FK para users do Shield — sem constraint FK pois migrations do Shield
            // podem não ter sido executadas ainda; integridade garantida pela aplicação.
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'matricula' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'lotacao' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            // Valores válidos: I, II, III — check constraint adicionado via SQL raw.
            'tipo_acom' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'null'       => false,
            ],
            // UF da Superintendência Regional (ex.: SP, RJ, MG)
            'estado_se' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true,
            ],
            'ativo' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => true,
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
        $this->forge->addUniqueKey('matricula');
        $this->forge->addKey('user_id');
        $this->forge->createTable('vendors');

        // Check constraint para tipo_acom
        $this->db->query("ALTER TABLE vendors ADD CONSTRAINT chk_vendors_tipo_acom CHECK (tipo_acom IN ('I', 'II', 'III'))");
    }

    public function down(): void
    {
        $this->forge->dropTable('vendors', true);
    }
}
