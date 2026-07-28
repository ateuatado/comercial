<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventoContactsTable extends Migration
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
            'evento_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'razao_social' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'matricula_vendedor' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'observacao' => [
                'type' => 'TEXT',
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['evento_id', 'cnpj']);
        $this->forge->addKey('matricula_vendedor');
        $this->forge->createTable('evento_contacts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('evento_contacts', true);
    }
}
