<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImportLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'auto_increment' => true,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'total_lines' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'inserted' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'skipped' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'imported_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pendente',
            ],
            'error_message' => [
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
        $this->forge->createTable('import_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('import_logs', true);
    }
}
