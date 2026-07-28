<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventosTable extends Migration
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
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'local' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'data_inicio' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'data_fim' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'ativo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
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
        $this->forge->createTable('eventos', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('eventos', true);
    }
}
