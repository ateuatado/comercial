<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendorNotesTable extends Migration
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
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'conteudo' => [
                'type' => 'TEXT',
            ],
            'sentimento' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('matricula_vendedor');
        $this->forge->addKey('cnpj');
        $this->forge->addKey(['matricula_vendedor', 'cnpj']);

        $this->forge->createTable('vendor_notes', true);
    }

    public function down()
    {
        $this->forge->dropTable('vendor_notes', true);
    }
}
