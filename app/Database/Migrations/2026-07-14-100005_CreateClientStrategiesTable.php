<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientStrategiesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'matricula_vendedor' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'service_id' => [
                'type' => 'INT',
            ],
            'observacao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['matricula_vendedor', 'cnpj']);
        $this->forge->addForeignKey('service_id', 'segment_services', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('client_strategies', true);
    }

    public function down()
    {
        $this->forge->dropTable('client_strategies', true);
    }
}
